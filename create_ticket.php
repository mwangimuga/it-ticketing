<?php
require_once 'auth.php';
require_once 'functions.php';
requireRole('End_User');

$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';

    if ($subject && $description && $category_id) {
        $stmt = $pdo->prepare("SELECT default_priority FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $cat = $stmt->fetch();
        $priority = $cat ? $cat['default_priority'] : 'Medium';

        $uuid = generateUUID();

        $stmt = $pdo->prepare("INSERT INTO tickets (ticket_uuid, requester_id, category_id, priority, status, subject, description) VALUES (?, ?, ?, ?, 'New', ?, ?)");
        if ($stmt->execute([$uuid, $_SESSION['user_id'], $category_id, $priority, $subject, $description])) {
            $ticket_id = $pdo->lastInsertId();
            
            $stmtHist = $pdo->prepare("INSERT INTO ticket_history (ticket_id, changed_by_user_id, old_status, new_status) VALUES (?, ?, NULL, 'New')");
            $stmtHist->execute([$ticket_id, $_SESSION['user_id']]);

            // Handle optional file attachment
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['attachment']['tmp_name'];
                $fileName = $_FILES['attachment']['name'];
                $fileSize = $_FILES['attachment']['size'];
                $fileType = $_FILES['attachment']['type'];
                
                // Allowed file types (security layer)
                $allowedFileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                if (in_array($fileExtension, $allowedFileExtensions)) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/uploads/';
                    $dest_path = $uploadFileDir . $newFileName;
                    
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $stmtFile = $pdo->prepare("INSERT INTO attachments (ticket_id, user_id, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmtFile->execute([$ticket_id, $_SESSION['user_id'], $fileName, 'uploads/' . $newFileName, $fileSize, $fileType]);
                    }
                }
            }

            header("Location: view_ticket.php?id=" . $ticket_id);
            exit;
        } else {
            $error = "Failed to create ticket.";
        }
    } else {
        $error = "All fields (except attachment) are required.";
    }
}

$pageTitle = 'Submit New Ticket';
require_once 'layout/header.php';
require_once 'layout/sidebar.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="end_user_dashboard.php" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1 mb-4 w-fit transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Dashboard
        </a>
        <h2 class="text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">Submit a Request</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-2">Fill out the details below so our IT team can assist you.</p>
    </div>

    <div class="bg-white dark:bg-dark-card shadow-sm sm:rounded-xl border border-slate-200 dark:border-dark-border overflow-hidden transition-colors duration-200">
        <div class="p-6 sm:p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 p-4 rounded-lg border border-red-100 flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="create_ticket.php" enctype="multipart/form-data" class="space-y-6" id="createTicketForm">
                <div>
                    <label for="category_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Issue Category</label>
                    <select id="category_id" name="category_id" class="w-full shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-slate-300 dark:border-dark-border rounded-lg py-2.5 px-3 border outline-none bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors" required>
                        <option value="">-- Choose what this relates to --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="subject" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Subject</label>
                    <input type="text" name="subject" id="subject" class="w-full shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-slate-300 dark:border-dark-border rounded-lg py-2.5 px-3 border outline-none bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors" placeholder="Short summary of the problem" required>
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                    <!-- Quill Container -->
                    <div class="bg-white dark:bg-slate-900 rounded-lg overflow-hidden border border-slate-300 dark:border-dark-border transition-colors focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                        <div id="editor-container" style="height: 200px;"></div>
                    </div>
                    <input type="hidden" name="description" id="hidden_description">
                </div>

                <div>
                    <label for="attachment" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Attachment <span class="text-xs text-slate-400 font-normal">(Optional)</span></label>
                    <input type="file" name="attachment" id="attachment" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/60 transition-all cursor-pointer">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Allowed formats: JPG, PNG, PDF, DOC, TXT, ZIP. Max size: 5MB.</p>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-dark-border">
                    <a href="end_user_dashboard.php" class="bg-white dark:bg-slate-800 py-2.5 px-5 border border-slate-300 dark:border-dark-border rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm">
                        Cancel
                    </a>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 px-6 rounded-lg text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Describe the steps to reproduce, error messages, and any relevant details...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'code-block']
            ]
        }
    });

    var form = document.getElementById('createTicketForm');
    form.onsubmit = function() {
        var description = document.getElementById('hidden_description');
        description.value = quill.root.innerHTML;
        
        if(quill.getText().trim().length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Description is required.',
            });
            return false;
        }
        return true;
    };
});
</script>

<?php require_once 'layout/footer.php'; ?>
