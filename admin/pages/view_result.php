<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    exit('<div class="p-8 text-center text-red-500 font-bold">Unauthorized Access</div>');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    exit('<div class="p-8 text-center text-red-500 font-bold">Invalid Result Requested.</div>');
}

// Fetch result and corresponding student info
$stmt = $conn->prepare("
    SELECT r.*, e.subject, e.exam_type, e.class, u.first_name, u.surname, u.user_id as student_matno
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    JOIN users u ON r.user_id = u.id
    WHERE r.id = :id
");
$stmt->execute([':id' => $id]);
$result = $stmt->fetch(PDO::FETCH_OBJ);

if (!$result) {
    exit('<div class="p-8 text-center text-red-500 font-bold">Result not found.</div>');
}

$percentage = (float)$result->percentage;
$isPassed = $percentage >= 50;

$studentAnswers = json_decode($result->student_answers, true) ?: [];

// Get all original questions
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = :eid ORDER BY question_number ASC");
$q_stmt->execute([':eid' => $result->exam_id]);
$questions = $q_stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full p-4 md:p-8">
    <div class="max-w-4xl mx-auto flex flex-col gap-6">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <button onclick="window.loadPage('<?= APP_URL ?>admin/pages/proctoring.php')"
                class="bg-white border border-gray-100 size-10 shrink-0 rounded-md flex items-center justify-center text-gray-400 hover:text-gray-800 hover:bg-gray-50 transition-all cursor-pointer shadow-sm">
                <i class="bx bx-arrow-left-stroke text-2xl"></i>
            </button>
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">Result Investigation</h2>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-1">
                    <?= htmlspecialchars($result->first_name . ' ' . $result->surname) ?> (<?= htmlspecialchars($result->student_matno) ?>)
                </p>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="bg-white rounded-[2rem] p-8 border border-gray-100 shadow-sm flex flex-col md:flex-row gap-8 items-center justify-between">
            <div class="flex items-center gap-6">
                <div class="size-20 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center font-semibold text-2xl border border-blue-100 shadow-inner shrink-0">
                   <?= round($percentage) ?>%
                </div>
                <div>
                     <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Exam Score</p>
                     <h3 class="text-xl font-semibold text-gray-800"><?= $result->score ?> / <?= $result->total_questions ?></h3>
                     <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($result->subject) ?> (<?= htmlspecialchars($result->class) ?>)</p>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase w-20">Time Spent:</span>
                    <span class="text-sm font-bold text-gray-800">
                        <?php 
                        $mins = floor($result->time_taken / 60);
                        $secs = $result->time_taken % 60;
                        echo sprintf('%02d:%02d', $mins, $secs);
                        ?>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase w-20">Status:</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider <?= $isPassed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= $isPassed ? 'Passed' : 'Failed' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 text-yellow-800 p-4 rounded-xl border border-yellow-200 text-sm font-semibold flex items-center gap-3">
             <i class="bx bx-info-circle text-xl text-yellow-600"></i>
             Reviewing individual answer patterns can help verify the proctoring engine's integrity flag. Check for suspicious exact matches or impossible completion times per question.
        </div>

        <!-- Full Answer Breakdown -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Answer Breakdown</h3>
            
            <?php foreach($questions as $index => $q): 
                $student_ans = $studentAnswers[$q->id] ?? null;
                $qtype = $q->question_type ?? 'mcq';

                if ($qtype === 'fill_blank') {
                    $isCorrect = $student_ans && strtolower(trim($student_ans)) === strtolower(trim($q->correct_answer));
                } else {
                    $isCorrect = $student_ans && strtoupper($student_ans) === strtoupper($q->correct_answer);
                }
            ?>
                <div class="bg-white p-6 rounded-[1.5rem] border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Q<?= $index + 1 ?></span>
                        <div class="h-1 w-1 bg-gray-300 rounded-full"></div>
                        <span class="text-[10px] font-semibold <?= $isCorrect ? 'text-green-500' : 'text-red-500' ?> uppercase tracking-widest">
                            <?= $isCorrect ? 'Correct' : 'Incorrect' ?>
                        </span>
                    </div>

                    <p class="text-sm font-bold text-gray-800 mb-4 content-html"><?= $q->question_text ?></p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div class="bg-gray-50 p-3 flex flex-col rounded-xl border <?= $isCorrect ? 'border-green-200' : 'border-red-200' ?>">
                             <span class="text-[9px] font-semibold text-gray-400 uppercase mb-1">Student's Answer</span>
                             <span class="font-bold text-sm <?= $isCorrect ? 'text-green-700' : 'text-red-600' ?>">
                                  <?= $student_ans ? htmlspecialchars($student_ans) : '<i>Skipped</i>' ?>
                             </span>
                         </div>
                         <div class="bg-green-50/50 p-3 flex flex-col rounded-xl border border-green-100">
                             <span class="text-[9px] font-semibold text-green-600/50 uppercase mb-1">Correct Answer</span>
                             <span class="font-bold text-green-700 text-sm"><?= htmlspecialchars($q->correct_answer) ?></span>
                         </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>
