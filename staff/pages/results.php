<?php
require __DIR__ . '/../../auth/check.php';

// Only staff can access this page
if ($user->role !== 'staff') {
    exit('Unauthorized');
}

$staff_fullname = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Fetch results for exams created by this teacher
$stmt = $conn->prepare("
    SELECT 
        e.subject,
        e.exam_type,
        e.class AS exam_class,
        u.first_name,
        u.last_name,
        u.class,
        r.score,
        r.total_questions,
        r.percentage,
        r.taken_at
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    JOIN users u ON r.user_id = u.id
    WHERE e.subject_teacher = :teacher
    ORDER BY r.taken_at DESC
    LIMIT 200
");
$stmt->execute([':teacher' => $staff_fullname]);
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

?>

<div class="fadeIn w-full md:p-8 p-4">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <div class="p-3 rounded-2xl bg-indigo-100 text-indigo-600 shadow-sm">
                <i class="bx bx-bar-chart text-3xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Exam Results</h1>
                <p class="text-sm text-gray-500">View and track student performance in your exams</p>
            </div>
        </div>
        
        <div class="flex flex-col md:flex-row items-center gap-3">
            <div class="relative w-full md:w-64 group">
                <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                <input type="text" id="staffResultSearch" 
                    class="w-full pl-11 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition shadow-sm"
                    placeholder="Search results...">
            </div>
            <button id="staffResultFilter"
                class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-50 transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="bx bx-filter"></i> Filter
            </button>
            <button id="staffResultCSV"
                class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-500 transition shadow-md flex items-center gap-2 cursor-pointer">
                <i class="bx bx-cloud-download"></i> Download CSV
            </button>
        </div>
    </div>

    <!-- Results Table Card -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table id="staffResultTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Exam</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">Score</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Date Taken</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Status</th>
                    </tr>
                </thead>
                <tbody id="staffResultBody" class="divide-y divide-gray-50">
                    <?php if (count($results) > 0): ?>
                        <?php foreach ($results as $item): 
                            $percent = (float)$item->percentage;
                            $statusColor = $percent >= 70 ? 'bg-green-100 text-green-700' : ($percent >= 50 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700');
                            $statusText = $percent >= 50 ? 'Passed' : 'Failed';
                        ?>
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="px-6 py-4 text-sm font-bold text-gray-800">
                                    <?= htmlspecialchars($item->first_name . ' ' . $item->last_name) ?>
                                    <span class="block text-[10px] font-semibold text-gray-400 uppercase"><?= htmlspecialchars($item->class) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($item->subject) ?></p>
                                    <p class="text-xs text-indigo-500"><?= htmlspecialchars($item->exam_type) ?> (<?= htmlspecialchars($item->exam_class) ?>)</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col items-center">
                                        <span class="text-sm font-bold text-gray-800"><?= $item->score ?> <span class="text-gray-400 font-normal">/ <?= $item->total_questions ?></span></span>
                                        <div class="w-20 bg-gray-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="h-full <?= $percent >= 70 ? 'bg-green-500' : ($percent >= 50 ? 'bg-orange-500' : 'bg-red-500') ?>"
                                                style="width: <?= $percent ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    <?= date('M j, Y • g:i A', strtotime($item->taken_at)) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $statusColor ?>">
                                        <?= $statusText ?> (<?= round($percent) ?>%)
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center text-gray-400 italic font-medium">
                                No exam results recorded yet for your exams.
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
        searchId: 'staffResultSearch',
        tableId: 'staffResultTable',
        bodyId: 'staffResultBody',
        filterBtnId: 'staffResultFilter',
        csvBtnId: 'staffResultCSV',
        filters: [
            { col: 1, label: 'Exam' },
            { col: 4, label: 'Status' }
        ],
        csvName: 'exam_results'
    });
</script>
