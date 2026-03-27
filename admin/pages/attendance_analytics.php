<?php
require '../../connections/db.php';

$session_id = $_SESSION['active_session_id'] ?? 0;

// 1. Fetch Class Comparison for the current term/session
$class_perf = $conn->prepare("
    SELECT 
        class,
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
    FROM attendance
    WHERE session_id = :session_id
    GROUP BY class
    ORDER BY (SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) DESC
");
$class_perf->execute([':session_id' => $session_id]);
$class_perf_data = $class_perf->fetchAll(PDO::FETCH_OBJ);

// 2. Fetch Top Absentees (in the current active term limit)
$absentees_stmt = $conn->prepare("
    SELECT 
        u.first_name, u.surname, u.class, u.user_id,
        COUNT(a.id) as absence_count
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    WHERE a.status = 'absent' AND a.session_id = :session_id
    GROUP BY u.id
    ORDER BY absence_count DESC
    LIMIT 5
");
$absentees_stmt->execute([':session_id' => $session_id]);
$absentees = $absentees_stmt->fetchAll(PDO::FETCH_OBJ);

// 3. Overall stats for active term
$overall_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM attendance
    WHERE session_id = :session_id
");
$overall_stmt->execute([':session_id' => $session_id]);
$overall = $overall_stmt->fetch(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4 bg-gray-50/30">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-xl shadow-blue-100 flex items-center justify-center">
                <i class="bx bx-bar-chart-square text-4xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">Attendance Analytics</h1>
                <p class="text-sm text-gray-400 font-medium italic">Strategic insights into student participation and punctuality</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
             <div class="p-4 rounded-2xl bg-white border border-gray-100 shadow-sm flex items-center gap-4">
                 <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Global Punctuality</p>
                    <p class="text-xl font-semibold text-blue-600"><?= $overall->total > 0 ? round(($overall->present / $overall->total) * 100) : 0 ?>%</p>
                 </div>
                 <div class="size-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                     <i class="bx bx-trending-up text-xl"></i>
                 </div>
             </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Trends & Class Perf -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Attendance Trends Chart -->
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-50">
                 <div class="flex items-center justify-between mb-8">
                    <h3 class="text-lg font-semibold text-gray-800">Participation Trends</h3>
                    <select id="trendRange" class="text-xs font-bold text-gray-400 border-none bg-gray-50 rounded-xl px-4 py-2 focus:ring-0">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                    </select>
                 </div>
                 <div class="h-80">
                      <canvas id="attendanceTrendChart"></canvas>
                 </div>
            </div>

            <!-- Class Comparison -->
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-50">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Departmental Insights</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($class_perf_data as $c): 
                        $pct = $c->total_records > 0 ? round(($c->present_count / $c->total_records) * 100) : 0;
                        $color = $pct >= 90 ? 'emerald' : ($pct >= 75 ? 'blue' : 'orange');
                    ?>
                        <div class="p-5 rounded-3xl bg-gray-50/50 border border-gray-100 flex items-center justify-between group hover:bg-white hover:shadow-xl transition-all duration-300">
                            <div>
                                <p class="text-sm font-semibold text-gray-700 uppercase"><?= $c->class ?></p>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1"><?= $c->total_records ?> Record Samples</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xl font-semibold text-<?= $color ?>-600"><?= $pct ?>%</span>
                                <div class="w-20 bg-gray-200 rounded-full h-1.5 mt-2 overflow-hidden">
                                     <div class="bg-<?= $color ?>-500 h-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Top Absentees & Alerts -->
        <div class="space-y-8">
            <!-- Critical Watchlist -->
            <div class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-xl">
                 <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="bx bx-error-circle text-orange-500"></i>
                    Critical Watchlist
                 </h3>
                 <div class="space-y-6">
                    <?php if (empty($absentees)): ?>
                         <p class="text-xs text-gray-400 italic">No critical absences detected.</p>
                    <?php else: ?>
                        <?php foreach ($absentees as $a): ?>
                            <div class="flex items-center gap-4">
                                <div class="size-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold relative">
                                     <?= $a->first_name[0] ?>
                                     <span class="absolute -top-1 -right-1 size-4 bg-red-500 text-white rounded-full flex items-center justify-center text-[8px] font-semibold"><?= $a->absence_count ?></span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800"><?= $a->first_name . ' ' . $a->surname ?></p>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= $a->class ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                 </div>
                 <button onclick="notifyGuardians()" class="w-full mt-10 py-4 bg-gray-900 text-white rounded-2xl font-semibold text-xs uppercase tracking-widest hover:bg-black transition-all shadow-lg cursor-pointer">Notify Guardians</button>
            </div>

            <!-- Heatmap Placeholder -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[2.5rem] p-8 text-white">
                 <h4 class="text-xs font-semibold uppercase tracking-widest opacity-60 mb-4">Integrity Score</h4>
                 <div class="flex items-end gap-1 h-20 mb-6">
                     <?php for($i=0; $i<12; $i++): $h = rand(30, 100); ?>
                        <div class="flex-1 bg-white/20 rounded-t-lg group relative" style="height: <?= $h ?>%">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-white text-blue-600 text-[8px] font-semibold px-1.5 py-0.5 rounded hidden group-hover:block"><?= $h ?>%</div>
                        </div>
                     <?php endfor; ?>
                 </div>
                 <p class="text-xs font-medium text-blue-100 leading-relaxed italic">"Student presence has increased by 12% compared to the previous academic month."</p>
            </div>
        </div>

    </div>
</div>

<script>
function notifyGuardians() {
    Swal.fire({
        title: 'Notify Guardians?',
        text: 'This will send automated SMS/Email alerts to the parents of students in the critical watchlist.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Send Alerts'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Sending...',
                text: 'Please wait while notifications are processed.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: BASE_URL + 'admin/auth/notify_guardians.php',
                method: 'POST',
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Sent!', res.message, 'success');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to communicate with the notification server.', 'error');
                }
            });
        }
    });
}
$(function() {
    let trendChart = null;

    function loadTrends(days = 7) {
        $.ajax({
            url: 'auth/attendance_api.php?action=get_trends&days=' + days,
            success: function(res) {
                if(res.status === 'success') {
                    renderChart(res.labels, res.present, res.absent, res.late);
                }
            }
        });
    }

    function renderChart(labels, present, absent, late) {
        if(trendChart) trendChart.destroy();
        const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Present',
                        data: present,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Absent',
                        data: absent,
                        borderColor: '#ef4444',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    $('#trendRange').on('change', function() {
        loadTrends($(this).val());
    });

    loadTrends(7);
});
</script>
