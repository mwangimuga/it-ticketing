<?php
require_once 'auth.php';
requireRole(['IT_Head', 'Admin']);

// Force Assign Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket_id'], $_POST['agent_id'])) {
    $tid = $_POST['assign_ticket_id'];
    $aid = empty($_POST['agent_id']) ? null : $_POST['agent_id'];
    
    // Security check: Verify the target agent is in the IT department
    if ($aid) {
        $stmtCheck = $pdo->prepare("SELECT department FROM users WHERE id = ?");
        $stmtCheck->execute([$aid]);
        $agentDept = $stmtCheck->fetchColumn();
        
        if (strcasecmp($agentDept, 'IT') !== 0) {
             header("Location: head_dashboard.php?error=InvalidDepartment");
             exit;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE tickets SET assigned_agent_id = ?, status = IF(status = 'New' AND ? IS NOT NULL, 'Open', status) WHERE id = ?");
    $stmt->execute([$aid, $aid, $tid]);
    header("Location: head_dashboard.php");
    exit;
}

$agentStmt = $pdo->query("SELECT id, username, role FROM users WHERE department = 'IT' ORDER BY username");
$agents = $agentStmt->fetchAll();

$query = "
    SELECT t.*, c.name as category_name, u.username as requester_name, a.username as agent_name,
    s.response_time_limit, s.resolution_time_limit,
    TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) as minutes_open
    FROM tickets t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.requester_id = u.id
    LEFT JOIN users a ON t.assigned_agent_id = a.id
    LEFT JOIN slas s ON t.priority = s.priority_level
    ORDER BY t.created_at DESC
    LIMIT 250
";
$stmt = $pdo->query($query);
$tickets = $stmt->fetchAll();

$stats = ['New' => 0, 'Open' => 0, 'In-Progress' => 0, 'Pending' => 0, 'Resolved' => 0, 'Closed' => 0];
$slaBreaches = 0;

$processedTickets = [];

foreach ($tickets as $t) {
    if (isset($stats[$t['status']])) {
        $stats[$t['status']]++;
    }
    
    $isBreached = false;
    if (!in_array($t['status'], ['Resolved', 'Closed']) && $t['minutes_open'] > $t['resolution_time_limit']) {
        $slaBreaches++;
        $isBreached = true;
    }
    $t['is_breached'] = $isBreached;
    $processedTickets[] = $t;
}

$pageTitle = 'Service Desk Overview';
require_once 'layout/header.php';
require_once 'layout/sidebar.php';
?>

<div class="mb-8 border-b border-slate-200 dark:border-dark-border pb-4">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Service Desk Overview</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">High-level view of ticket queues and team performance.</p>
</div>

<!-- Stats Dashboard -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
    <?php foreach($stats as $status => $count): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border border-slate-200 dark:border-dark-border p-4 flex flex-col justify-center">
        <div class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1"><?= $status ?></div>
        <div class="text-2xl font-bold text-slate-800 dark:text-slate-100"><?= $count ?></div>
    </div>
    <?php endforeach; ?>
    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl shadow-sm border border-red-100 dark:border-red-500/30 p-4 flex flex-col justify-center">
        <div class="text-red-600 dark:text-red-400 text-xs font-bold uppercase tracking-wider mb-1">SLA Breaches</div>
        <div class="text-2xl font-black text-red-700 dark:text-red-300"><?= $slaBreaches ?></div>
    </div>
</div>

<div class="mb-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Tickets by Status</h3>
        </div>
        <div class="p-6 h-64">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    <div class="bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Tickets by Agent (Top 5)</h3>
        </div>
        <div class="p-6 h-64">
            <canvas id="agentChart"></canvas>
        </div>
    </div>
</div>
</div>

<div class="bg-white dark:bg-dark-card shadow sm:rounded-lg overflow-hidden border border-slate-200 dark:border-dark-border">
    <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">All Active Tickets</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-dark-border">
            <thead class="bg-white dark:bg-dark-card">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left border-b border-slate-200 dark:border-dark-border text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ticket / SLA</th>
                    <th scope="col" class="px-6 py-3 text-left border-b border-slate-200 dark:border-dark-border text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Requester</th>
                    <th scope="col" class="px-6 py-3 text-left border-b border-slate-200 dark:border-dark-border text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status / Priority</th>
                    <th scope="col" class="px-6 py-3 text-left border-b border-slate-200 dark:border-dark-border text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assignee</th>
                    <th scope="col" class="px-6 py-3 text-right border-b border-slate-200 dark:border-dark-border text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-dark-border/50">
                <?php foreach ($processedTickets as $ticket): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors <?= $ticket['is_breached'] ? 'bg-red-50 dark:bg-red-900/10' : '' ?>">
                        <td class="px-6 py-4 max-w-sm">
                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 truncate block">
                                <?= e($ticket['subject']) ?>
                            </a>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">#<?= substr($ticket['ticket_uuid'], 0, 8) ?></span>
                                <?php if($ticket['is_breached']): ?>
                                    <span class="text-[10px] bg-red-600 dark:bg-red-500 text-white font-bold px-1.5 py-0.5 rounded shadow-sm">SLA BREACHED</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900 dark:text-slate-200"><?= e($ticket['requester_name']) ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400"><?= e($ticket['category_name']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-2 items-start">
                                <?= getStatusBadge($ticket['status']) ?>
                                <?= getPriorityBadge($ticket['priority']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="POST" action="head_dashboard.php" class="flex items-center gap-2">
                                <input type="hidden" name="assign_ticket_id" value="<?= $ticket['id'] ?>">
                                <select name="agent_id" class="text-xs border-slate-300 dark:border-dark-border rounded focus:ring-indigo-500 py-1 pl-2 pr-6 shadow-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" onchange="this.form.submit()">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach ($agents as $agent): ?>
                                        <option value="<?= $agent['id'] ?>" <?= $ticket['assigned_agent_id'] == $agent['id'] ? 'selected' : '' ?>>
                                            <?= e($agent['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 border border-slate-200 dark:border-dark-border hover:border-indigo-200 dark:hover:border-indigo-500/30 bg-white dark:bg-dark-card px-3 py-1.5 rounded transition-colors shadow-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>



<?php 
// Calculate agents load
$agentLoads = [];
foreach($tickets as $t) {
    if ($t['agent_name'] && in_array($t['status'], ['Open', 'In-Progress', 'Pending'])) {
        if(!isset($agentLoads[$t['agent_name']])) $agentLoads[$t['agent_name']] = 0;
        $agentLoads[$t['agent_name']]++;
    }
}
arsort($agentLoads);
$topAgents = array_slice($agentLoads, 0, 5, true);
?>
<script>
document.addEventListener("DOMContentLoaded", () => {

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#475569';
    const gridColor = isDark ? '#334155' : '#e2e8f0';

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: ['New', 'Open', 'In-Progress', 'Pending'],
            datasets: [{
                label: 'Tickets',
                data: [<?= $stats['New'] ?>, <?= $stats['Open'] ?>, <?= $stats['In-Progress'] ?>, <?= $stats['Pending'] ?>],
                backgroundColor: '#6366f1',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } },
                x: { ticks: { color: textColor }, grid: { display: false } }
            }
        }
    });

    const agentCtx = document.getElementById('agentChart').getContext('2d');
    new Chart(agentCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($topAgents)) ?>,
            datasets: [{
                label: 'Active Tickets Assigned',
                data: <?= json_encode(array_values($topAgents)) ?>,
                backgroundColor: '#14b8a6',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } },
                x: { ticks: { color: textColor }, grid: { display: false } }
            }
        }
    });
});
</script>

<?php require_once 'layout/footer.php'; ?>
