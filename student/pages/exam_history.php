<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

// Fetch all exam results for this student
$stmt = $conn->prepare("
    SELECT e.subject, e.exam_type, e.class, e.paper_type, r.*
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = :user_id
    ORDER BY r.taken_at DESC
");
$stmt->execute([':user_id' => $user->id]);
$history = $stmt->fetchAll(PDO::FETCH_OBJ);

$totalExams  = count($history);
$avgScore    = $totalExams > 0 ? array_sum(array_column($history, 'percentage')) / $totalExams : 0;
$passedCount = count(array_filter($history, fn($r) => (float) $r->percentage >= 50));
$failedCount = count(array_filter($history, fn($r) => (float) $r->percentage <= 40));
?>

<div class="fadeIn p-4 md:p-10 overflow-x-hidden">

    <!-- ── Page Header ───────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10">
        <div>
            <p class="text-[10px] font-bold text-blue-500 uppercase  mb-1">Academic Records</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Exam History</h1>
            <p class="text-sm text-gray-400 font-medium mt-1">Your scores and performance across all assessments.</p>
        </div>
        <div class="flex flex-col md:flex-row items-center gap-3">
            <div class="relative w-full md:w-64 group no-print">
                <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                <input type="text" id="historySearch" 
                    class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition shadow-sm"
                    placeholder="Search history...">
            </div>
            <button onclick="$('#sideTest').click()"
                class="self-start sm:self-auto flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl font-bold text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 cursor-pointer">
                <i class="bx bx-pencil text-lg"></i> Take New Exam
            </button>
        </div>
    </div>

    <!-- ── Summary Stats ─────────────────────────────────────── -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-3xl p-2 md:p-4 border border-gray-100 shadow-sm flex flex-col gap-2">
            <div class="size-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
                <i class="bx bx-book-content text-xl"></i>
            </div>
            <p class="text-2xl font-black text-gray-800 tabular-nums"><?= $totalExams ?></p>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Exams Taken</p>
        </div>
        <div class="bg-white rounded-3xl p-2 md:p-5 border border-gray-100 shadow-sm flex flex-col gap-2">
            <div class="size-10 rounded-2xl bg-green-50 flex items-center justify-center text-green-600">
                <i class="bx bx-trending-up text-xl"></i>
            </div>
            <p class="text-2xl font-black text-gray-800 tabular-nums"><?= round($avgScore) ?>%</p>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Average Score</p>
        </div>
        <div class="bg-white rounded-3xl p-2 md:p-5 border border-gray-100 shadow-sm flex flex-col gap-2">
            <div class="size-10 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                <i class="bx bx-medal text-xl"></i>
            </div>
            <p class="text-2xl font-black text-gray-800 tabular-nums"><?= $passedCount ?></p>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Passed</p>
        </div>
        <div class="bg-white rounded-3xl p-2 md:p-5 border border-gray-100 shadow-sm flex flex-col gap-2">
            <div class="size-10 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                <i class="bx bxs-sad text-xl"></i>
            </div>
            <p class="text-2xl font-black text-gray-800 tabular-nums"><?= $failedCount ?></p>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Failed</p>
        </div>
    </div>

    <!-- ── Results List ──────────────────────────────────────── -->
    <?php if ($totalExams > 0): ?>
        <div class="space-y-3">
            <?php foreach ($history as $res):
                $pct     = (float)$res->percentage;
                $passed = $pct >= 50;
                $color = $pct >= 70 ? 'green' : ($pct >= 50 ? 'blue' : 'red');
                $circumference = 100.5; // 2 * pi * 16 ≈ 100.5
                $dashOffset = $circumference - ($pct / 100) * $circumference;
            ?>
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 group">
                    <div class="flex items-center gap-4 p-5">

                        <!-- Mini Score Ring -->
                        <div class="relative size-14 shrink-0">
                            <svg class="size-14 -rotate-90" viewBox="0 0 36 36">
                                <circle cx="18" cy="18" r="16" fill="none" stroke="#f3f4f6" stroke-width="3"/>
                                <circle cx="18" cy="18" r="16" fill="none"
                                    stroke="<?= $color === 'green' ? '#16a34a' : ($color === 'blue' ? '#2563eb' : '#dc2626') ?>"
                                    stroke-width="3"
                                    stroke-dasharray="<?= $circumference ?>"
                                    stroke-dashoffset="<?= $dashOffset ?>"
                                    stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-[10px] font-black text-gray-700 tabular-nums"><?= round($pct) ?>%</span>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="text-base font-black text-gray-800 truncate group-hover:text-blue-600 transition-colors">
                                    <?= htmlspecialchars($res->subject) ?>
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wide shrink-0
                                    <?= $passed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
                                    <?= $passed ? 'Passed' : 'Failed' ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 font-medium">
                                <?= htmlspecialchars($res->exam_type) ?> 
                                &bull; Score: <span class="font-bold text-gray-600"><?= $res->score ?>/<?= $res->total_questions ?></span>
                                &bull; <?= date('M j, Y', strtotime($res->taken_at)) ?>
                            </p>
                        </div>

                        <!-- View Button -->
                        <button onclick="viewResult(<?= $res->exam_id ?>)"
                            class="shrink-0 px-5 py-2.5 bg-gray-50 border border-gray-100 text-gray-500 rounded-2xl font-black text-xs uppercase tracking-wide hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all cursor-pointer">
                            Review
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Empty State -->
        <div class="py-28 bg-white rounded-[3rem] border-2 border-dashed border-gray-100 flex flex-col items-center justify-center text-center px-8">
            <div class="size-24 bg-gray-50 rounded-[2rem] border border-gray-100 shadow-inner flex items-center justify-center mb-6">
                <i class="bx bx-history text-5xl text-gray-200"></i>
            </div>
            <h4 class="text-2xl font-black text-gray-800 mb-2">No Exams Taken Yet</h4>
            <p class="text-gray-400 font-medium max-w-xs leading-relaxed text-sm">
                Once you complete an exam, your scores and detailed performance breakdown will appear here.
            </p>
            <button onclick="$('#sideTest').click()"
                class="mt-8 px-8 py-3.5 bg-blue-600 text-white rounded-2xl font-bold text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 cursor-pointer">
                Take Your First Exam
            </button>
        </div>
    <?php endif; ?>

</div>

<script>
    // Initialize search for exam history cards
    $('#historySearch').on('input', function() {
        const q = $(this).val().toLowerCase();
        $('.space-y-3 > .group').each(function() {
            const t = $(this).text().toLowerCase();
            $(this).toggle(t.includes(q));
        });
    });

function viewResult(id) {
    $('#mainContent').fadeOut(200, function() {
        $.ajax({
            url: '/school_app/student/pages/exam_result_view.php',
            type: 'POST',
            data: { exam_id: id },
            success: function(response) {
                $('#mainContent').html(response).fadeIn(200);
            },
            error: function() {
                Swal.fire('Error', 'Failed to load result.', 'error');
                $('#mainContent').fadeIn(200);
            }
        });
    });
}
</script>
