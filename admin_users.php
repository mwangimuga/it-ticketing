<?php
require_once 'auth.php';
requireRole('Admin');

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department = trim($_POST['department']);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $role, $department]);
        header("Location: admin_users.php?success=UserAdded");
        exit;
    } catch (PDOException $ex) {
        $errorMsg = "Failed to create user. Username or email may already exist.";
    }
}

// Handle Update User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $department = trim($_POST['department']);
    $password = trim($_POST['password']);

    try {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, department = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $department, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, department = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $department, $id]);
        }
        header("Location: admin_users.php?success=UserUpdated");
        exit;
    } catch (PDOException $ex) {
        $errorMsg = "Failed to update user. Username or email may conflict.";
    }
}

$uQuery = $pdo->query("SELECT id, username, email, role, department FROM users ORDER BY id DESC");
$users = $uQuery->fetchAll();

$pageTitle = 'User Management';
require_once 'layout/header.php';
require_once 'layout/sidebar.php';
?>

<div class="mb-8 border-b border-slate-200 dark:border-dark-border pb-4 flex justify-between items-end">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">User Management</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Add, update, and manage system users across all roles.</p>
    </div>
</div>

<?php if (isset($errorMsg)): ?>
    <div class="bg-red-50 text-red-600 border border-red-200 p-4 rounded-lg mb-6 shadow-sm">
        <?= e($errorMsg) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-8">
    <!-- Chart Overview -->
    <div class="bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">User Distribution</h3>
        </div>
        <div class="p-6 flex justify-center">
            <div class="w-full max-w-sm h-48">
                <canvas id="userRolesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Add User Form -->
    <div class="xl:col-span-2 bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Add New User</h3>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_user">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Username</label>
                        <input type="text" name="username" required class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Role</label>
                        <select name="role" class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                            <option value="End_User">End User</option>
                            <option value="IT_Agent">IT Agent</option>
                            <option value="IT_Head">IT Head</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Department</label>
                        <input type="text" name="department" class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm" placeholder="e.g. Finance, HR, IT">
                    </div>
                </div>
                <div class="pt-2 flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-4 py-2 rounded shadow-sm transition-colors">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- All Users List -->
<div class="bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">User Directory</h3>
        <span class="bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-bold px-3 py-1 rounded-full"><?= count($users) ?> Users</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-dark-border">
            <thead class="bg-slate-50 dark:bg-slate-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dept</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-dark-border/50 bg-white dark:bg-dark-card">
                <?php foreach($users as $u): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-slate-200"><?= e($u['username']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?= e($u['email']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 text-xs font-bold uppercase tracking-wider px-2 py-1 rounded shadow-sm border border-indigo-100 dark:border-indigo-500/20">
                            <?= e(str_replace('_', ' ', $u['role'])) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?= e($u['department']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick='openEditModal(<?= json_encode($u) ?>)' class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeEditModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-dark-card rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200 dark:border-dark-border">
            <form method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="bg-white dark:bg-dark-card px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-100 mb-4" id="modal-title">Edit User</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Username</label>
                            <input type="text" name="username" id="edit_username" required class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input type="email" name="email" id="edit_email" required class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Role</label>
                            <select name="role" id="edit_role" class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                                <option value="End_User">End User</option>
                                <option value="IT_Agent">IT Agent</option>
                                <option value="IT_Head">IT Head</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Department</label>
                            <input type="text" name="department" id="edit_department" class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm">
                        </div>
                        <div class="border-t border-slate-200 dark:border-dark-border pt-4 mt-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">New Password <span class="text-slate-500 font-normal">(Leave blank to keep current)</span></label>
                            <input type="password" name="password" class="w-full text-sm border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md focus:ring-indigo-500 p-2 border shadow-sm" placeholder="••••••••">
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-800/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200 dark:border-dark-border">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">Save Changes</button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 dark:border-dark-border shadow-sm px-4 py-2 bg-white dark:bg-slate-900 text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Calculate roles distribution for chart
$roleCounts = ['Admin' => 0, 'IT_Head' => 0, 'IT_Agent' => 0, 'End_User' => 0];
foreach($users as $user) {
    if(isset($roleCounts[$user['role']])) $roleCounts[$user['role']]++;
}
?>
<script>
    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_department').value = user.department;
        document.getElementById('editUserModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    document.addEventListener("DOMContentLoaded", () => {
        <?php if(isset($_GET['success']) && $_GET['success'] === 'UserAdded'): ?>
        Swal.fire({ title: 'Success!', text: 'User created successfully.', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        <?php endif; ?>
        <?php if(isset($_GET['success']) && $_GET['success'] === 'UserUpdated'): ?>
        Swal.fire({ title: 'Success!', text: 'User details updated successfully.', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        <?php endif; ?>

        // Chart initialization
        const ctx = document.getElementById('userRolesChart');
        if (ctx) {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#f8fafc' : '#1e293b';

            new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Admin', 'IT Head', 'IT Agent', 'End User'],
                    datasets: [{
                        data: [<?= $roleCounts['Admin'] ?>, <?= $roleCounts['IT_Head'] ?>, <?= $roleCounts['IT_Agent'] ?>, <?= $roleCounts['End_User'] ?>],
                        backgroundColor: ['#6366f1', '#14b8a6', '#f59e0b', '#64748b'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: textColor } } }
                }
            });
        }
    });
</script>

<?php require_once 'layout/footer.php'; ?>
