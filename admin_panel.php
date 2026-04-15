<?php
require_once 'auth.php';
requireRole('Admin');

$cQuery = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $cQuery->fetchAll();

$pageTitle = 'Admin Panel';
require_once 'layout/header.php';
require_once 'layout/sidebar.php';
?>

<div class="mb-8 border-b border-slate-200 dark:border-dark-border pb-4 flex justify-between items-end">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">System Configuration</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage ticket categories, system defaults, and more.</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
    <div class="space-y-6">
        <div class="bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Ticket Categories</h3>
            </div>
            <div class="p-6">
                <ul class="divide-y divide-slate-100 dark:divide-dark-border/50">
                    <?php foreach($categories as $cat): ?>
                    <li class="py-3 flex justify-between items-center">
                        <div>
                            <div class="font-semibold text-sm text-slate-900 dark:text-slate-200"><?= e($cat['name']) ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Default Priority: <span class="font-medium text-slate-700 dark:text-slate-300"><?= e($cat['default_priority']) ?></span></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="space-y-6">
        <div class="bg-indigo-50 dark:bg-indigo-900/20 shadow sm:rounded-lg border border-indigo-100 dark:border-indigo-800/30 overflow-hidden p-6">
            <h3 class="text-lg font-bold text-indigo-800 dark:text-indigo-300 mb-2">Expanded Management Items</h3>
            <p class="text-sm text-indigo-700 dark:text-indigo-400 mb-6 leading-relaxed">
                User management and SLA configuration have been moved to their dedicated pages for a more comprehensive administration experience.
            </p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="admin_users.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-5 rounded-lg shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex items-center justify-center gap-2 text-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Manage Users
                </a>
                <a href="admin_slas.php" class="bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-indigo-700 dark:text-indigo-300 font-semibold py-2.5 px-5 rounded-lg shadow-sm border border-indigo-200 dark:border-indigo-700/50 transition-all flex items-center justify-center gap-2 text-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Manage SLA Policies
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
