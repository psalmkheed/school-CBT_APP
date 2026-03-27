<?php
require __DIR__ . '/../../auth/check.php';
/** @var stdClass|false $user */
// Only staff can access this page
if ($user->role !== 'staff') {
    exit('Unauthorized');
}

$exam_id = $_POST['exam_id'] ?? 0;

if (!$exam_id) {
    echo "<div class='p-8 text-center text-red-500 font-bold'>Invalid Exam Requested.</div>";
    exit;
}

// Fetch exam details
$staff_fullname = $_SESSION['first_name'] . ' ' . $_SESSION['surname'];
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = :id AND subject_teacher = :teacher");
$stmt->execute([':id' => $exam_id, ':teacher' => $staff_fullname]);
$exam = $stmt->fetch(PDO::FETCH_OBJ);

if (!$exam) {
    echo "<div class='p-8 text-center text-red-500 font-bold'>Exam not found or you are not authorized to view these scores.</div>";
    exit;
}

// Fetch results for this specific exam
$stmt = $conn->prepare("
    SELECT 
        u.first_name,
        u.surname,
        u.class,
        r.score,
        r.total_questions,
        r.percentage,
        r.taken_at
    FROM exam_results r
    JOIN users u ON r.user_id = u.id
    WHERE r.exam_id = :exam_id
    ORDER BY r.percentage DESC, r.taken_at ASC
");
$stmt->execute([':exam_id' => $exam_id]);
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

?>

<div class="fadeIn w-full md:p-8 p-4">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-4">
            <button onclick="$('#sideStaffExams').click()"
                class="md:hidden size-10 rounded-2xl bg-white shadow-sm flex items-center justify-center text-gray-400 hover:text-blue-600 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div>
                <h1 class="text-3xl font-semibold text-gray-800 tracking-tight"><?= htmlspecialchars($exam->subject) ?>
                    Results</h1>
                <p class="text-sm text-gray-400 font-medium tracking-tight uppercase">
                    <?= htmlspecialchars($exam->exam_type) ?> • Class: <?= htmlspecialchars($exam->class) ?></p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative w-64 group no-print">
                <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                <input type="text" id="scoreSearch" 
                    class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition shadow-sm"
                    placeholder="Search results...">
            </div>
            <button id="scoreCSV"
                class="px-6 py-3 bg-blue-600 text-white rounded-2xl text-xs font-semibold uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100 flex items-center gap-2 cursor-pointer no-print">
                <i class="bx bx-arrow-big-down-line text-lg"></i> CSV
            </button>
            <button onclick="window.print()"
                class="px-8 py-3 bg-gray-800 text-white rounded-2xl text-xs font-semibold uppercase tracking-widest hover:bg-black transition shadow-xl shadow-gray-200 flex items-center gap-2 cursor-pointer no-print">
                <i class="bx bx-printer text-lg"></i> Print
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
                <i class="bx bx-group text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Students</p>
                <p class="text-xl font-semibold text-gray-800 tabular-nums"><?= count($results) ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-green-50 flex items-center justify-center text-green-600">
                <i class="bx bx-check-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Average</p>
                <?php
                $avg = count($results) > 0 ? array_sum(array_column($results, 'percentage')) / count($results) : 0;
                ?>
                <p class="text-xl font-semibold text-gray-800 tabular-nums"><?= round($avg) ?>%</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="size-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                <i class="bx bx-medal text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Top Score</p>
                <?php
                $top = count($results) > 0 ? max(array_column($results, 'percentage')) : 0;
                ?>
                <p class="text-xl font-semibold text-gray-800 tabular-nums"><?= round($top) ?>%</p>
            </div>
        </div>
    </div>

    <!-- Results Table Card -->
    <div
        class="bg-white rounded-[3rem] border border-gray-100 shadow-2xl shadow-gray-200/40 overflow-hidden print-table px-4">
        <div class="overflow-x-auto">
            <table id="scoreTable" class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="px-8 py-10 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Rank</th>
                        <th class="px-6 py-10 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Student
                            Information</th>
                        <th
                            class="px-6 py-10 text-[11px] font-semibold text-gray-400 uppercase tracking-widest text-center">
                            Score Detail</th>
                        <th
                            class="px-6 py-10 text-[11px] font-semibold text-gray-400 uppercase tracking-widest text-right">
                            Performance Status</th>
                    </tr>
                </thead>
                <tbody id="scoreBody" class="divide-y divide-gray-50">
                    <?php if (count($results) > 0): ?>
                        <?php foreach ($results as $index => $item):
                            $percent = (float) $item->percentage;
                            $hasPassed = $percent >= 50;
                            $color = $hasPassed ? 'green' : 'red';
                            ?>
                            <tr class="hover:bg-gray-50/50 transition-all group">
                                <td class="px-8 py-8">
                                    <div
                                        class="size-10 rounded-xl bg-gray-50 flex items-center justify-center font-semibold text-sm text-gray-400 group-hover:bg-blue-600 group-hover:text-white transition-all shadow-sm">
                                        <?= $index + 1 ?>
                                    </div>
                                </td>
                                <td class="px-6 py-8">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-base font-semibold text-gray-800"><?= htmlspecialchars($item->first_name . ' ' . $item->surname) ?></span>
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-tight"><?= htmlspecialchars($item->class) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-8">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="text-lg font-semibold text-gray-800 tabular-nums"><?= $item->score ?> <span
                                                class="text-gray-300 font-bold">/ <?= $item->total_questions ?></span></span>
                                        <div class="w-32 bg-gray-50 rounded-full h-2 overflow-hidden border border-gray-100">
                                            <div class="h-full bg-<?= $color ?>-500 shadow-[0_0_10px_rgba(var(--tw-color-<?= $color ?>-500),0.3)]"
                                                style="width: <?= $percent ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-8 text-right">
                                    <div
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-<?= $color ?>-50 border border-<?= $color ?>-100 text-<?= $color ?>-600">
                                        <span
                                            class="text-[11px] font-semibold uppercase tracking-widest"><?= $hasPassed ? 'Passed' : 'Failed' ?></span>
                                        <span class="text-sm font-semibold tabular-nums"><?= round($percent) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-8 py-32 text-center">
                                <div
                                    class="size-20 bg-gray-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                                    <i class="bx bx-file text-4xl text-gray-200"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-800 mb-2">Examination Records Empty</h4>
                                <p class="text-sm text-gray-400 font-medium max-w-xs mx-auto leading-relaxed">No students
                                    have submitted their answers for this examination yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.initTableToolkit({
        searchId: 'scoreSearch',
        tableId: 'scoreTable',
        bodyId: 'scoreBody',
        csvBtnId: 'scoreCSV',
        csvName: 'exam_scores'
    });
</script>
<style>
    /* for printing students score */
    @media print {
        body * {
            visibility: hidden;
        }

        .no-print {
            display: none !important;
        }

        #mainContent,
        #mainContent *,
        .print-table,
        .print-table * {
            visibility: visible;
        }

        #mainContent {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 20px;
        }

        .shadow-sm,
        .shadow-xl,
        .shadow-2xl {
            box-shadow: none !important;
            box-shadow: none !important;
        }

        .bg-gray-50\/30,
        .bg-gray-50\/50,
        .bg-gray-50,
        .bg-white\/60 {
            background-color: white !important;
        }

        .rounded-\[3rem\],
        .rounded-\[2rem\],
        .rounded-\[3rem\] {
            border-radius: 0 !important;
        }

        .grid {
            display: block !important;
        }

        .grid>div {
            margin-bottom: 20px;
            border: 1px solid #eee;
            padding: 10px;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        th,
        td {
            border-bottom: 1px solid #eee !important;
            padding: 12px 6px !important;
        }
    }
</style>