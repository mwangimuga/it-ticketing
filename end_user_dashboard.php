<?php
require_once 'auth.php';
requireRole('End_User');

$pageTitle = 'My Dashboard';
require_once 'layout/header.php';
require_once 'layout/sidebar.php';

$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name
    FROM tickets t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.requester_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 border-b border-slate-200 dark:border-dark-border pb-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">My Support Tickets</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track the status of your requests and issues.</p>
    </div>
    <a href="create_ticket.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg shadow-sm font-semibold transition-all inline-flex items-center gap-2">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Request
    </a>
</div>

<div class="bg-white dark:bg-dark-card shadow-sm sm:rounded-xl border border-slate-200 dark:border-dark-border overflow-hidden transition-colors">
    <?php if (empty($tickets)): ?>
        <div class="py-16 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 mb-4">
                <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-slate-200 mb-1">No tickets yet</h3>
            <p class="text-slate-500 dark:text-slate-400">You haven't submitted any support requests.</p>
        </div>
    <?php else: ?>
        <ul class="divide-y divide-slate-100 dark:divide-dark-border/50">
            <?php foreach ($tickets as $ticket): ?>
                <li>
                    <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="block hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150 p-5 group">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate pr-4">
                                <?= e($ticket['subject']) ?>
                            </h3>
                            <div class="flex-shrink-0 flex gap-2">
                                <?= getPriorityBadge($ticket['priority']) ?>
                                <?= getStatusBadge($ticket['status']) ?>
                            </div>
                        </div>
                        <div class="sm:flex sm:justify-between items-center text-sm text-slate-500 dark:text-slate-400 mt-2">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <?= e($ticket['category_name']) ?>
                                </span>
                                <span class="hidden sm:inline-block w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                <span>ID: <span class="font-mono text-slate-600 dark:text-slate-300"><?= substr($ticket['ticket_uuid'], 0, 8) ?></span></span>
                            </div>
                            <div class="mt-2 sm:mt-0 flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <time datetime="<?= $ticket['created_at'] ?>"><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></time>
                            </div>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once 'layout/footer.php'; ?>
