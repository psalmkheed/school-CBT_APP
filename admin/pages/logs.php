<?php
require '../../auth/check.php';

// Only admins can access this page
if (!in_array($_SESSION['role'], ['admin', 'super'])) {
    header("Location: {$base}auth/login.php");
    exit();
}

try {
    // Fetch logs with user details
    $stmt = $conn->prepare("
        SELECT L.*, U.first_name, U.surname, U.role as user_role 
        FROM activity_logs L
        JOIN users U ON L.user_id = U.id
        ORDER BY L.created_at DESC
        LIMIT 500
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    echo "Error fetching logs: " . $e->getMessage();
    exit;
}
?>

<div class="p-4 md:p-8 min-h-screen bg-gray-50/30">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-200">
                <i class="bx bx-checklist text-white text-2xl"></i>
            </div>
            <div>
                <h3 class="text-2xl font-semibold text-gray-800 tracking-tight">System Activity Logs</h3>
                <p class="text-sm text-gray-400 font-medium tracking-wide">Monitor all administrative and user activities</p>
            </div>
        </div>
        
        <div class="hidden md:flex items-center gap-3">
            <button onclick="location.reload()" class="p-2.5 rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition cursor-pointer" title="Refresh Logs">
                <i class="bx bx-refresh-cw text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Stats summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php
        $countDay = $conn->query("SELECT COUNT(*) FROM activity_logs WHERE created_at >= NOW() - INTERVAL 1 DAY")->fetchColumn();
        $countCrit = $conn->query("SELECT COUNT(*) FROM activity_logs WHERE severity = 'critical' AND created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
        $uniqueUsers = $conn->query("SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();
        ?>
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                <i class="bx bx-bolt-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active (24h)</p>
                <h4 class="text-xl font-semibold text-gray-800"><?= $countDay ?> <span class="text-xs font-normal text-gray-400 ml-1">Logs</span></h4>
            </div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center">
                <i class="bx bx-badge-exclamation text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Security Risks</p>
                <h4 class="text-xl font-semibold text-gray-800"><?= $countCrit ?> <span class="text-xs font-normal text-gray-400 ml-1">Incidents</span></h4>
            </div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
                <i class="bx bx-user-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Logged Actors</p>
                <h4 class="text-xl font-semibold text-gray-800"><?= $uniqueUsers ?> <span class="text-xs font-normal text-gray-400 ml-1">Users</span></h4>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-200/40 overflow-hidden">
        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-left" id="logsTable">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Timestamp</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">User / Role</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Action</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Activity Details</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Severity</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="bx bxs-loader-dots animate-spin text-3xl"></i>
                                    <p class="font-bold">No activity logs found yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                            <tr class="hover:bg-gray-50/50 transition duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-700"><?= date('M j, Y', strtotime($log->created_at)) ?></span>
                                        <span class="text-[10px] text-gray-400"><?= date('h:i A', strtotime($log->created_at)) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-[10px]">
                                            <?= strtoupper($log->first_name[0] . $log->surname[0]) ?>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-semibold text-gray-800"><?= $log->first_name ?> <?= $log->surname ?></span>
                                            <span class="text-[9px] font-bold uppercase tracking-widest <?= $log->user_role === 'admin' ? 'text-red-500' : 'text-blue-500' ?>">
                                                <?= $log->user_role ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-wider">
                                        <?= $log->action ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-xs text-gray-600 font-medium leading-relaxed truncate hover:whitespace-normal transition-all duration-300" title="<?= htmlspecialchars($log->details) ?>">
                                        <?= htmlspecialchars($log->details) ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php 
                                    $badgeClass = match($log->severity) {
                                        'critical' => 'bg-red-100 text-red-700 border-red-200',
                                        'warning'  => 'bg-orange-100 text-orange-700 border-orange-200',
                                        default    => 'bg-emerald-100 text-emerald-700 border-emerald-200'
                                    };
                                    ?>
                                    <span class="px-2 py-0.5 rounded-full border text-[9px] font-semibold uppercase tracking-widest <?= $badgeClass ?>">
                                        <?= $log->severity ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-[10px] font-mono font-medium text-gray-400">
                                        <i class="bx bx-location-pin mr-1 opacity-50"></i>
                                        <?= $log->ip_address ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination / Footer -->
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Showing latest 500 actions</p>
            <div class="flex gap-2">
                <button class="size-8 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-400 opacity-50 cursor-not-allowed">
                    <i class="bx bx-chevron-left"></i>
                </button>
                <button class="size-8 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:bg-gray-50 cursor-pointer">
                    <i class="bx bx-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>
