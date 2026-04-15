<?php
require_once 'auth.php';
requireRole('Admin');

// Update SLA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_sla') {
    $priority = $_POST['priority_level'];
    $response = (int)$_POST['response_time_limit'];
    $resolution = (int)$_POST['resolution_time_limit'];

    $stmt = $pdo->prepare("UPDATE slas SET response_time_limit = ?, resolution_time_limit = ? WHERE priority_level = ?");
    $stmt->execute([$response, $resolution, $priority]);
    header("Location: admin_slas.php?success=SLAUpdated");
    exit;
}

$slaQuery = $pdo->query("SELECT * FROM slas ORDER BY FIELD(priority_level, 'Low', 'Medium', 'High', 'Critical')");
$slas = $slaQuery->fetchAll();

$pageTitle = 'SLA Policies';
require_once 'layout/header.php';
require_once 'layout/sidebar.php';

// Helper function to format minutes into human readable text
function formatMinutes($minutes) {
    if ($minutes < 60) return $minutes . 'm';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours < 24) return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
    $days = floor($hours / 24);
    $hrs = $hours % 24;
    return $days . 'd ' . ($hrs > 0 ? $hrs . 'h' : '');
}
?>

<div class="mb-8 border-b border-slate-200 dark:border-dark-border pb-4 flex justify-between items-end">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">SLA Policies</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage Service Level Agreements response and resolution targets.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-border bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">SLA Definitions (in Minutes)</h3>
            </div>
            <div class="p-6">
                <!-- Explanatory text -->
                <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 p-4 rounded-lg text-sm border border-blue-100 dark:border-blue-800/30 shadow-sm leading-relaxed">
                    <p class="font-semibold mb-1">How SLAs work:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Response Time:</strong> The maximum time allowed before an Agent must pick up or first-respond to the ticket.</li>
                        <li><strong>Resolution Time:</strong> The maximum time allowed to set the ticket's status to Reserved or Closed.</li>
                        <li>All values are managed in <strong>minutes</strong> (e.g., 60 = 1 hour, 1440 = 24 hours, 2880 = 2 days).</li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <?php foreach($slas as $sla): ?>
                    <form method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 bg-slate-50 dark:bg-slate-900/50 p-5 rounded-lg border border-slate-200 dark:border-dark-border shadow-sm transition-all hover:shadow-md">
                        <input type="hidden" name="action" value="update_sla">
                        <input type="hidden" name="priority_level" value="<?= e($sla['priority_level']) ?>">
                        
                        <div class="w-32 flex flex-col justify-center">
                            <span class="font-bold text-base <?= $sla['priority_level'] === 'Critical' ? 'text-red-600' : ($sla['priority_level'] === 'High' ? 'text-amber-500' : 'text-slate-700 dark:text-slate-300') ?>">
                                <?= e($sla['priority_level']) ?>
                            </span>
                            <span class="text-xs text-slate-500 font-medium">Priority</span>
                        </div>
                        
                        <div class="flex items-center gap-6 flex-1">
                            <div class="flex-1">
                                <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Response Time</label>
                                <div class="relative">
                                    <input type="number" name="response_time_limit" value="<?= e($sla['response_time_limit']) ?>" required class="w-full text-base font-semibold border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md py-2 px-3 focus:ring-indigo-500 shadow-sm border pr-14">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-medium bg-slate-100 dark:bg-slate-800 rounded-r-md border-l border-slate-300 dark:border-dark-border px-2">
                                        <?= formatMinutes($sla['response_time_limit']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs uppercase font-bold text-slate-500 mb-1">Resolution Time</label>
                                <div class="relative">
                                    <input type="number" name="resolution_time_limit" value="<?= e($sla['resolution_time_limit']) ?>" required class="w-full text-base font-semibold border-slate-300 dark:border-dark-border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-md py-2 px-3 focus:ring-indigo-500 shadow-sm border pr-14">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400 font-medium bg-slate-100 dark:bg-slate-800 rounded-r-md border-l border-slate-300 dark:border-dark-border px-2">
                                        <?= formatMinutes($sla['resolution_time_limit']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 sm:mt-0 self-end sm:self-center ml-2">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-6 py-2.5 rounded-lg shadow-sm transition-colors">Update</button>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="space-y-6 block">
        <!-- Quick Ref Card -->
        <div class="bg-white dark:bg-dark-card shadow sm:rounded-lg border border-slate-200 dark:border-dark-border overflow-hidden p-6 sticky top-8">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-4">Time Conversion Cheat Sheet</h3>
            <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-400 font-medium">
                <li class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-dark-border/50">
                    <span>1 Hour</span> <span class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1 rounded font-bold">60 min</span>
                </li>
                <li class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-dark-border/50">
                    <span>2 Hours</span> <span class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1 rounded font-bold">120 min</span>
                </li>
                <li class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-dark-border/50">
                    <span>4 Hours</span> <span class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1 rounded font-bold">240 min</span>
                </li>
                <li class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-dark-border/50">
                    <span>8 Hours (1 Work Day)</span> <span class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1 rounded font-bold">480 min</span>
                </li>
                <li class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-dark-border/50">
                    <span>24 Hours (1 Day)</span> <span class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1 rounded font-bold">1440 min</span>
                </li>
                <li class="flex justify-between items-center py-2">
                    <span>48 Hours (2 Days)</span> <span class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-1 rounded font-bold">2880 min</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    <?php if(isset($_GET['success']) && $_GET['success'] === 'SLAUpdated'): ?>
    Swal.fire({
        title: 'SLA Updated',
        text: 'SLA timers have been successfully saved.',
        icon: 'success',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
    <?php endif; ?>
});
</script>

<?php require_once 'layout/footer.php'; ?>
