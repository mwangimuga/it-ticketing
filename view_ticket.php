<?php
require_once 'auth.php';
requireLogin();

$ticket_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, u.username as requester_name, a.username as agent_name, a.id as agent_id
    FROM tickets t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.requester_id = u.id
    LEFT JOIN users a ON t.assigned_agent_id = a.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ticket not found.");
}

// Access check
if ($_SESSION['role'] === 'End_User' && $ticket['requester_id'] !== $_SESSION['user_id']) {
    die("Access denied.");
}

// Fetch Ticket Attachments
$aStmt = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = ? AND comment_id IS NULL");
$aStmt->execute([$ticket_id]);
$ticket_attachments = $aStmt->fetchAll();

// Fetch Comment Attachments
$caStmt = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = ? AND comment_id IS NOT NULL");
$caStmt->execute([$ticket_id]);
$comment_attachments_raw = $caStmt->fetchAll();
$comment_attachments_map = [];
foreach ($comment_attachments_raw as $att) {
    if (!isset($comment_attachments_map[$att['comment_id']])) {
        $comment_attachments_map[$att['comment_id']] = [];
    }
    $comment_attachments_map[$att['comment_id']][] = $att;
}

// Process Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $body = trim($_POST['body'] ?? '');
    $is_internal = isset($_POST['is_internal']) && $_SESSION['role'] !== 'End_User' ? 1 : 0;
    
    if ($body) {
        $stmt = $pdo->prepare("INSERT INTO comments (ticket_id, user_id, body, is_internal) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ticket_id, $_SESSION['user_id'], $body, $is_internal]);
        $comment_id = $pdo->lastInsertId();
        
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['attachment']['tmp_name'];
            $fileName = $_FILES['attachment']['name'];
            $fileSize = $_FILES['attachment']['size'];
            $fileType = $_FILES['attachment']['type'];
            
            $allowedFileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            if (in_array($fileExtension, $allowedFileExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/uploads/';
                $dest_path = $uploadFileDir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $stmtFile = $pdo->prepare("INSERT INTO attachments (ticket_id, comment_id, user_id, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmtFile->execute([$ticket_id, $comment_id, $_SESSION['user_id'], $fileName, 'uploads/' . $newFileName, $fileSize, $fileType]);
                }
            }
        }
        
        // Auto assign if staff replies and it's unassigned
        if (in_array($_SESSION['role'], ['IT_Agent', 'IT_Head', 'Admin']) && !$ticket['assigned_agent_id'] && strcasecmp($_SESSION['department'] ?? '', 'IT') === 0) {
             $pdo->prepare("UPDATE tickets SET assigned_agent_id = ?, status = 'In-Progress' WHERE id = ?")->execute([$_SESSION['user_id'], $ticket_id]);
             $pdo->prepare("INSERT INTO ticket_history (ticket_id, changed_by_user_id, old_status, new_status) VALUES (?, ?, ?, 'In-Progress')")->execute([$ticket_id, $_SESSION['user_id'], $ticket['status']]);
        }
    }
    header("Location: view_ticket.php?id=" . $ticket_id);
    exit;
}

// Process Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status' && in_array($_SESSION['role'], ['IT_Agent', 'IT_Head', 'Admin'])) {
    $new_status = $_POST['status'] ?? $ticket['status'];
    if ($new_status !== $ticket['status']) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $ticket_id]);
        
        $pdo->prepare("INSERT INTO ticket_history (ticket_id, changed_by_user_id, old_status, new_status) VALUES (?, ?, ?, ?)")
            ->execute([$ticket_id, $_SESSION['user_id'], $ticket['status'], $new_status]);
    }
    header("Location: view_ticket.php?id=" . $ticket_id);
    exit;
}

// Fetch Comments
$cStmt = $pdo->prepare("
    SELECT c.*, u.username, u.role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.ticket_id = ? ".($_SESSION['role'] === 'End_User' ? "AND c.is_internal = 0" : "")."
    ORDER BY c.created_at ASC
");
$cStmt->execute([$ticket_id]);
$comments = $cStmt->fetchAll();

// Fetch History for agents
$hStmt = $pdo->prepare("
    SELECT h.*, u.username 
    FROM ticket_history h 
    JOIN users u ON h.changed_by_user_id = u.id 
    WHERE h.ticket_id = ? 
    ORDER BY h.changed_at ASC
");
$hStmt->execute([$ticket_id]);
$historyList = $hStmt->fetchAll();

$pageTitle = 'Ticket #' . substr($ticket['ticket_uuid'], 0, 8);
require_once 'layout/header.php';
require_once 'layout/sidebar.php';
?>

<div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Content: Ticket Details & Comments -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-dark-card shadow sm:rounded-xl border border-slate-200 dark:border-dark-border overflow-hidden transition-colors">
            <div class="p-6 sm:p-8 border-b border-slate-200 dark:border-dark-border">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100"><?= e($ticket['subject']) ?></h2>
                    <div class="flex gap-2">
                        <?= getPriorityBadge($ticket['priority']) ?>
                        <?= getStatusBadge($ticket['status']) ?>
                    </div>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-300 mb-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-100 dark:border-dark-border font-sans ql-editor" style="padding:15px;"><?= strip_tags($ticket['description'], '<p><br><b><strong><i><em><u><ul><ol><li><a><pre><code>') ?></div>
                
                <?php if (!empty($ticket_attachments)): ?>
                <div class="mb-6 border-t border-slate-100 dark:border-dark-border pt-4">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Attachments</h4>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($ticket_attachments as $att): ?>
                            <a href="<?= e($att['file_path']) ?>" target="_blank" class="flex items-center gap-2 px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-dark-border rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shadow-sm">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                <?= e($att['file_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex items-center text-xs text-slate-500 dark:text-slate-400 gap-4">
                    <span class="flex items-center gap-1.5"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> <?= e($ticket['requester_name']) ?></span>
                    <span class="flex items-center gap-1.5"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span>
                </div>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 sm:p-8 transition-colors">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                    <svg class="h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Conversation
                </h3>
                
                <div class="space-y-6 mb-8">
                    <?php if (empty($comments)): ?>
                        <p class="text-slate-500 dark:text-slate-400 text-sm text-center py-4 bg-white dark:bg-dark-card rounded-lg border border-slate-100 dark:border-dark-border">No comments yet. Be the first to reply!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full <?= $c['role'] === 'End_User' ? 'bg-slate-300 dark:bg-slate-700 text-slate-600 dark:text-slate-300' : 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400' ?> flex items-center justify-center font-bold text-sm">
                                        <?= strtoupper(substr($c['username'], 0, 2)) ?>
                                    </div>
                                </div>
                                <div class="flex-1 bg-white dark:bg-dark-card rounded-xl p-4 shadow-sm border <?= $c['is_internal'] ? 'border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-900/10' : 'border-slate-200 dark:border-dark-border' ?>">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-200 flex items-center gap-2">
                                            <?= e($c['username']) ?>
                                            <?php if ($c['role'] !== 'End_User'): ?>
                                                <span class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Staff</span>
                                            <?php endif; ?>
                                            <?php if ($c['is_internal']): ?>
                                                <span class="bg-amber-200 dark:bg-amber-500/20 text-amber-800 dark:text-amber-400 text-[10px] px-2 py-0.5 rounded-full font-bold flex items-center gap-1"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg> Internal Note</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400"><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></div>
                                    </div>
                                    <div class="text-sm text-slate-700 dark:text-slate-300 font-sans ql-editor" style="padding:0; min-height:auto;"><?= strip_tags($c['body'], '<p><br><b><strong><i><em><u><ul><ol><li><a><pre><code>') ?></div>
                                    
                                    <?php if (isset($comment_attachments_map[$c['id']])): ?>
                                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-dark-border/50">
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($comment_attachments_map[$c['id']] as $att): ?>
                                                    <a href="<?= e($att['file_path']) ?>" target="_blank" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                                        <?= e($att['file_name']) ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="view_ticket.php?id=<?= $ticket_id ?>" id="commentForm" enctype="multipart/form-data" class="bg-white dark:bg-dark-card p-4 rounded-xl border border-slate-200 dark:border-dark-border shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-all">
                    <input type="hidden" name="action" value="add_comment">
                    <div class="border border-slate-300 dark:border-dark-border rounded-lg overflow-hidden transition-colors mb-4">
                        <div id="comment-editor-container" style="height: 120px;"></div>
                    </div>
                    <input type="hidden" name="body" id="hidden_body">
                    
                    <div class="mb-4">
                        <label for="attachment" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Attachment <span class="text-[10px] text-slate-400 font-normal">(Optional)</span></label>
                        <input type="file" name="attachment" id="attachment" class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/60 transition-all cursor-pointer">
                    </div>
                    
                    <div class="flex justify-between items-center pt-3 border-t border-slate-100 dark:border-dark-border/50">
                        <?php if ($_SESSION['role'] !== 'End_User'): ?>
                            <label class="flex items-center text-sm text-slate-600 dark:text-slate-400 font-medium cursor-pointer">
                                <input type="checkbox" name="is_internal" class="rounded border-slate-300 dark:border-dark-border text-amber-500 focus:ring-amber-500 mr-2 h-4 w-4 transition-all bg-white dark:bg-slate-900">
                                Internal Note
                            </label>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm transition-colors">Post Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar: Metadata -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white dark:bg-dark-card shadow sm:rounded-xl border border-slate-200 dark:border-dark-border overflow-hidden transition-colors">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Ticket Info</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider mb-1">Ticket UUID</div>
                    <div class="text-sm font-mono text-slate-900 dark:text-slate-200 bg-slate-50 dark:bg-slate-900 px-2 py-1 rounded inline-block border border-slate-100 dark:border-dark-border"><?= e($ticket['ticket_uuid']) ?></div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider mb-1">Category</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-slate-200 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500"></span> <?= e($ticket['category_name']) ?>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider mb-1">Assigned Agent</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-slate-200 flex items-center gap-2">
                        <?php if ($ticket['agent_name']): ?>
                            <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-400 flex items-center justify-center text-xs font-bold"><?= strtoupper(substr($ticket['agent_name'], 0, 1)) ?></div>
                            <?= e($ticket['agent_name']) ?>
                        <?php else: ?>
                            <span class="text-slate-400 italic">Unassigned</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (in_array($_SESSION['role'], ['IT_Agent', 'IT_Head', 'Admin'])): ?>
        <div class="bg-indigo-50 dark:bg-indigo-900/20 shadow sm:rounded-xl border border-indigo-100 dark:border-indigo-500/30 overflow-hidden transition-colors">
            <div class="px-6 py-5">
                <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider mb-4">Agent Actions</h3>
                <form method="POST" action="view_ticket.php?id=<?= $ticket_id ?>" class="space-y-4">
                    <input type="hidden" name="action" value="update_status">
                    <div>
                        <label class="block text-xs font-semibold text-indigo-800 dark:text-indigo-400 mb-1.5 uppercase tracking-wider">Change Status</label>
                        <select name="status" class="w-full text-sm border-indigo-200 dark:border-indigo-500/50 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-lg py-2 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500 shadow-sm transition-colors" onchange="this.form.submit()">
                            <?php 
                            $statuses = ['New', 'Open', 'In-Progress', 'Pending', 'Resolved', 'Closed'];
                            foreach ($statuses as $s) {
                                $selected = $s === $ticket['status'] ? 'selected' : '';
                                echo "<option value=\"$s\" $selected>$s</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-card shadow sm:rounded-xl border border-slate-200 dark:border-dark-border overflow-hidden transition-colors">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Audit Trail</h3>
            </div>
            <div class="p-6 max-h-64 overflow-y-auto">
                <?php if(empty($historyList)): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400 text-center italic">No history recorded.</p>
                <?php else: ?>
                    <ul class="space-y-4 relative before:absolute before:inset-0 before:ml-2 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 dark:before:via-slate-700 before:to-transparent">
                        <?php foreach($historyList as $idx => $h): ?>
                            <li class="relative flex items-center justify-between">
                                <div class="flex items-center gap-3 w-full border-b border-slate-50 dark:border-dark-border/50 pb-3">
                                    <div class="flex-shrink-0 w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-500 z-10 shadow-[0_0_0_4px_white] dark:shadow-[0_0_0_4px_#1e293b]"></div>
                                    <div class="flex-1">
                                        <p class="text-xs text-slate-800 dark:text-slate-300">
                                            <span class="font-semibold"><?= e($h['username']) ?></span> changed status 
                                            <?php if($h['old_status']): ?><span class="line-through text-slate-400 dark:text-slate-500 mx-1"><?= $h['old_status'] ?></span> &rarr; <?php endif; ?>
                                            <span class="font-semibold text-indigo-600 dark:text-indigo-400"><?= $h['new_status'] ?></span>
                                        </p>
                                        <time class="text-[10px] text-slate-400 dark:text-slate-500"><?= date('M j, Y g:i A', strtotime($h['changed_at'])) ?></time>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var quill = new Quill('#comment-editor-container', {
        theme: 'snow',
        placeholder: 'Type your reply here...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'link', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });

    var form = document.getElementById('commentForm');
    form.onsubmit = function() {
        var body = document.getElementById('hidden_body');
        body.value = quill.root.innerHTML;
        
        if(quill.getText().trim().length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Reply cannot be empty.',
            });
            return false;
        }
        return true;
    };
});
</script>

<?php require_once 'layout/footer.php'; ?>
