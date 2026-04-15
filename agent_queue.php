<?php
require_once 'auth.php';
requireRole(['IT_Agent', 'IT_Head', 'Admin']);

$pageTitle = 'Support Queue';
require_once 'layout/header.php';
require_once 'layout/sidebar.php';

// Claim logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_ticket_id']) && 
   in_array($_SESSION['role'], ['IT_Agent', 'IT_Head', 'Admin']) && 
   strcasecmp($_SESSION['department'] ?? '', 'IT') === 0) {
    $tid = $_POST['claim_ticket_id'];
    $stmt = $pdo->prepare("UPDATE tickets SET assigned_agent_id = ?, status = 'Open' WHERE id = ? AND assigned_agent_id IS NULL");
    if ($stmt->execute([$_SESSION['user_id'], $tid])) {
        if ($stmt->rowCount() > 0) {
            $stmtHist = $pdo->prepare("INSERT INTO ticket_history (ticket_id, changed_by_user_id, old_status, new_status) VALUES (?, ?, 'New', 'Open')");
            $stmtHist->execute([$tid, $_SESSION['user_id']]);
        }
        header("Location: agent_queue.php?filter=" . ($_GET['filter'] ?? 'all'));
        exit;
    }
}

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereParts = [];
$params = [];

if ($_SESSION['role'] === 'IT_Agent') {
    if ($filter === 'my') {
        $whereParts[] = "t.assigned_agent_id = ?";
        $params[] = $_SESSION['user_id'];
    } elseif ($filter === 'unassigned') {
        $whereParts[] = "t.assigned_agent_id IS NULL";
    }
}

if ($search !== '') {
    $whereParts[] = "(t.subject LIKE ? OR t.ticket_uuid LIKE ? OR u.username LIKE ?)";
    $searchWildcard = "%$search%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

$whereClause = !empty($whereParts) ? "WHERE " . implode(" AND ", $whereParts) : "";

$query = "
    SELECT t.*, c.name as category_name, u.username as requester_name, a.username as agent_name
    FROM tickets t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.requester_id = u.id
    LEFT JOIN users a ON t.assigned_agent_id = a.id
    $whereClause
    ORDER BY 
        CASE t.priority
            WHEN 'Critical' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
            ELSE 5
        END,
        t.created_at ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 dark:border-dark-border pb-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Support Queue</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage incoming requests and assign tickets.</p>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
<style>
    /* Dark mode overrides for simple-datatables */
    .dark .dataTable-wrapper { color: #f8fafc; }
    .dark .dataTable-top, .dark .dataTable-bottom { padding: 1rem; border-color: #334155; }
    .dark .dataTable-input { background: #0f172a; color: #f8fafc; border-color: #334155; }
    .dark .dataTable-selector { background: #0f172a; color: #f8fafc; border-color: #334155; }
    .dark .dataTable-pagination a { background: #1e293b; color: #cbd5e1; border-color: #334155; }
    .dark .dataTable-pagination a:hover { background: #334155; }
    .dark .dataTable-pagination .active a { background: #4f46e5; color: white; border-color: #4f46e5; }
    .dark .dataTable-table > thead > tr > th { border-bottom-color: #334155; }
    .dark .dataTable-table > tbody > tr > td { border-bottom-color: #334155 !important; }
    .dark .dataTable-info { color: #94a3b8; }
</style>

<?php if ($_SESSION['role'] === 'IT_Agent'): ?>
<div class="bg-white dark:bg-dark-card rounded-lg p-1 shadow-sm border border-slate-200 dark:border-dark-border inline-flex mb-6 transition-colors">
    <a href="?filter=all" class="<?= $filter === 'all' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-semibold shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-medium hover:bg-slate-50 dark:hover:bg-slate-800' ?> px-4 py-2 rounded-md text-sm transition-all">All Tickets</a>
    <a href="?filter=my" class="<?= $filter === 'my' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-semibold shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-medium hover:bg-slate-50 dark:hover:bg-slate-800' ?> px-4 py-2 rounded-md text-sm transition-all">My Tickets</a>
    <a href="?filter=unassigned" class="<?= $filter === 'unassigned' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-semibold shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-medium hover:bg-slate-50 dark:hover:bg-slate-800' ?> px-4 py-2 rounded-md text-sm transition-all flex items-center gap-2">Unassigned <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-[10px] px-1.5 rounded-full font-bold">New</span></a>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-dark-card shadow sm:rounded-lg overflow-hidden border border-slate-200 dark:border-dark-border overflow-x-auto transition-colors duration-200">
    <table id="queueTable" class="min-w-full divide-y divide-slate-200 dark:divide-dark-border">
        <thead class="bg-slate-50 dark:bg-slate-800/50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ticket</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Requester</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status / Priority</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assigned To</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Created</th>
                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-dark-card divide-y divide-slate-100 dark:divide-dark-border/50">
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-sm">No tickets found matching your criteria.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex flex-col border-none">
                                <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 truncate max-w-xs block">
                                    <?= e($ticket['subject']) ?>
                                </a>
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-1">#<?= substr($ticket['ticket_uuid'], 0, 8) ?></span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= e($ticket['category_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap border-none">
                            <div class="text-sm font-medium text-slate-900 dark:text-slate-200"><?= e($ticket['requester_name']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap border-none">
                            <div class="flex flex-col gap-2 items-start">
                                <?= getStatusBadge($ticket['status']) ?>
                                <?= getPriorityBadge($ticket['priority']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap border-none">
                            <?php if ($ticket['assigned_agent_id']): ?>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300">
                                        <?= strtoupper(substr($ticket['agent_name'], 0, 1)) ?>
                                    </div>
                                    <span class="text-sm text-slate-700 dark:text-slate-300"><?= e($ticket['agent_name']) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-sm text-slate-400 dark:text-slate-500 italic">Unassigned</span>
                                <?php if (in_array($_SESSION['role'], ['IT_Agent', 'IT_Head', 'Admin']) && strcasecmp($_SESSION['department'] ?? '', 'IT') === 0): ?>
                                    <form method="POST" class="mt-1">
                                        <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                        <input type="hidden" name="claim_ticket_id" value="<?= $ticket['id'] ?>">
                                        <button type="submit" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 px-2 py-1 rounded transition-colors">Claim Ticket</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 border-none">
                            <?= date('M j, Y', strtotime($ticket['created_at'])) ?><br>
                            <span class="text-xs text-slate-400 dark:text-slate-500"><?= date('g:i A', strtotime($ticket['created_at'])) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium border-none">
                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 bg-indigo-50 dark:bg-slate-800 border dark:border-dark-border px-3 py-1.5 rounded-md hover:bg-indigo-100 dark:hover:bg-slate-700 transition-colors shadow-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (document.getElementById("queueTable")) {
        new simpleDatatables.DataTable("#queueTable", {
            searchable: true,
            fixedHeight: true,
            perPage: 15,
        });
    }
});
</script>

<?php require_once 'layout/footer.php'; ?>
