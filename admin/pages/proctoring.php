<?php
require '../../connections/db.php';

$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Fetch flagged results based on session and term
$stmt = $conn->prepare("
    SELECT 
        r.*, 
        u.first_name, 
        u.surname, 
        u.class, 
        e.subject, 
        e.time_allowed,
        e.num_quest
    FROM exam_results r
    JOIN users u ON r.user_id = u.id
    JOIN exams e ON r.exam_id = e.id
    WHERE 
        e.session = :session AND e.term = :term AND
        ((r.time_taken < (e.time_allowed * 60 * 0.15) AND r.percentage > 80)
        OR (r.time_taken < (r.total_questions * 5) AND r.percentage > 70))
    ORDER BY r.taken_at DESC
");
$stmt->execute([':session' => $active_session, ':term' => $active_term]);
$flagged = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch Live Proctoring Violations
$logs_stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.surname, u.class, e.subject 
    FROM proctoring_logs p
    JOIN users u ON p.student_id = u.id
    JOIN exams e ON p.exam_id = e.id
    WHERE e.session = :session AND e.term = :term
    ORDER BY p.logged_at DESC
");
$logs_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$live_logs = $logs_stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-red-600 text-white shadow-xl shadow-red-100 flex items-center justify-center">
                <i class="bx bx-shield-quarter text-4xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">AI Proctoring & Integrity</h1>
                <p class="text-sm text-gray-400 font-medium italic">Detecting abnormal performance patterns and suspicious test behavior</p>
            </div>
        </div>
        
        <div class="flex items-center gap-2 px-6 py-3 bg-red-50 rounded-2xl border border-red-100">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
            <span class="text-xs font-semibold text-red-600 uppercase tracking-widest"><?= count($flagged) + count($live_logs) ?> Incidents Flagged</span>
        </div>
    </div>

    <!-- Integrity Dashboard -->
    <div class="grid grid-cols-1 gap-8">
        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl overflow-hidden">
            <div class="p-8 border-b border-gray-50 bg-gray-50/30 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">Suspicious Activity Log</h3>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest bg-white px-3 py-1.5 rounded-xl border border-gray-100">Real-time Analysis</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left" id="proctorTable">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Student / Candidate</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Assessment</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Result</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Time Taken</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Violation Type</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($flagged) && empty($live_logs)): ?>
                            <tr>
                                <td colspan="6" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="size-20 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center">
                                            <i class="bx bxs-shield-half text-5xl"></i>
                                        </div>
                                        <h4 class="font-bold text-gray-800">Clean Slate</h4>
                                        <p class="text-sm text-gray-400 max-w-xs">No suspicious exam behaviors have been detected for current assessments.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($live_logs as $log): ?>
                                <tr class="hover:bg-orange-50/30 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="size-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold text-xs">
                                                <?= strtoupper($log->first_name[0]) ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($log->first_name . ' ' . $log->surname) ?></p>
                                                <p class="text-[10px] font-semibold text-gray-400 tracking-widest uppercase"><?= htmlspecialchars($log->class) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-widest bg-gray-50 border border-gray-100 px-3 py-1.5 rounded-lg inline-block"><?= htmlspecialchars($log->subject) ?></p>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <p class="text-[10px] font-semibold text-orange-400 uppercase tracking-widest">In Progress / Logged</p>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="text-xs font-bold text-gray-500"><?= date('H:i:s', strtotime($log->logged_at)) ?></span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="text-[10px] font-semibold uppercase tracking-widest px-3 py-1.5 rounded-lg bg-red-50 text-red-600 border border-red-100 flex items-center justify-center gap-1 mx-auto w-fit">
                                            <i class="bx bx-error-circle"></i> <?= htmlspecialchars($log->reason) ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 flex justify-end">
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php foreach ($flagged as $r): 
                                $minTime = $r->time_allowed * 60 * 0.15;
                                $violation = ($r->time_taken < $minTime) ? 'Impossible Speed' : 'Pattern Inconsistency';
                                $minutes = floor($r->time_taken / 60);
                                $seconds = $r->time_taken % 60;
                            ?>
                                <tr class="hover:bg-red-50/30 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="size-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-xs">
                                                <?= strtoupper($r->first_name[0]) ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-800"><?= $r->first_name . ' ' . $r->surname ?></p>
                                                <p class="text-[10px] font-semibold text-gray-400 tracking-widest uppercase"><?= $r->class ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 font-bold text-gray-700 text-sm">
                                        <?= $r->subject ?>
                                        <span class="block text-[10px] text-gray-400 font-medium italic mt-0.5"><?= $r->num_quest ?> Questions</span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="text-sm font-semibold text-red-600"><?= round($r->percentage) ?>%</span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="text-sm font-bold text-gray-700"><?= sprintf('%02d:%02d', $minutes, $seconds) ?></span>
                                            <span class="text-[9px] font-semibold text-red-400 uppercase tracking-widest mt-0.5">Limit: <?= round($minTime/60) ?>m</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="px-3 py-1.5 bg-red-100 text-red-600 rounded-xl text-[9px] font-semibold uppercase tracking-widest border border-red-200">
                                            <?= $violation ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                         <button onclick="investigateResult(<?= $r->id ?>)" class="p-3 bg-white border border-gray-100 rounded-xl text-gray-400 hover:text-red-600 hover:border-red-200 hover:shadow-xl transition-all">
                                             <i class="bx bx-eye text-xl"></i>
                                         </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function investigateResult(id) {
    // Navigate to exam results view for this specific result ID 
    // to deeply inspect student answers
    window.loadPage('<?= APP_URL ?>admin/pages/view_result.php?id=' + id);
}
</script>
