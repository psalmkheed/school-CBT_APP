<?php
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'staff') {
    die("Unauthorized");
}

$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
if (!$exam_id) {
    die("Invalid Exam ID");
}

// Fetch exam details
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = :id");
$stmt->execute([':id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_OBJ);

if (!$exam) {
    die("Exam not found");
}

// Fetch all questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = :id ORDER BY question_number ASC");
$stmt->execute([':id' => $exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4 bg-gray-50/50 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <button onclick="loadPage('/school_app/staff/pages/exams.php')"
                    class="size-10 shrink-0 rounded-full flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all cursor-pointer"
                    title="Go back">
                    <i class="bx bx-arrow-left text-2xl"></i>
                </button>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">Preview Exam</h3>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($exam->subject) ?> • <?= htmlspecialchars($exam->class) ?></p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="bg-blue-600 px-4 py-2 rounded-xl text-white shadow-lg shadow-blue-100 flex items-center gap-2">
                    <i class="bx bx-clock-5 font-bold text-lg"></i>
                    <span class="text-sm font-semibold"><?= $exam->time_allowed ?> Mins</span>
                </div>
                <div class="bg-white px-4 py-2 rounded-xl border border-gray-100 shadow-sm flex items-center gap-2">
                    <i class="bx bx-list-check font-bold text-lg text-blue-600"></i>
                    <span class="text-sm font-semibold"><?= count($questions) ?> / <?= $exam->num_quest ?> Questions</span>
                </div>
            </div>
        </div>

        <?php if (count($questions) === 0): ?>
            <div class="bg-white rounded-3xl p-12 text-center border border-dashed border-gray-200">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bx bx-pencil text-3xl text-gray-300"></i>
                </div>
                <h4 class="text-gray-800 font-bold mb-2">No questions set yet</h4>
                <p class="text-gray-500 text-sm mb-6">Go back to the question builder to add some questions.</p>
                <button onclick="loadSetQuestions(<?= $exam_id ?>)" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-bold text-sm">Set Questions</button>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($questions as $q): ?>
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden p-8 relative">
                        <div class="absolute top-8 right-8">
                            <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em]">Q<?= $q->question_number ?></span>
                        </div>
                        
                        <div class="mb-6">
                            <p class="text-lg font-bold text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($q->question_text)) ?></p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach (['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $key => $val): ?>
                                <div class="flex items-center gap-3 p-4 rounded-2xl border <?= $q->correct_answer === $key ? 'border-green-200 bg-green-50/50' : 'border-gray-50 bg-gray-50/30' ?>">
                                    <div class="size-8 shrink-0 rounded-lg flex items-center justify-center font-black text-sm <?= $q->correct_answer === $key ? 'bg-green-600 text-white shadow-lg shadow-green-100' : 'bg-white text-gray-400 border border-gray-100 shadow-sm' ?>">
                                        <?= $key ?>
                                    </div>
                                    <span class="text-sm font-bold <?= $q->correct_answer === $key ? 'text-green-800' : 'text-gray-600' ?>">
                                        <?= htmlspecialchars($val) ?>
                                    </span>
                                    <?php if ($q->correct_answer === $key): ?>
                                        <i class="bx bx-check-double text-green-600 ml-auto text-xl"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <!-- if number of question is lesser than numbers of questions fetched from the database display this div -->
                <?php if (count($questions) < $exam->num_quest): ?>
                    <div class="bg-orange-50 rounded-2xl p-6 border border-orange-100 flex items-center gap-4">
                        <i class="bx bx-info-circle text-2xl text-orange-400"></i>
                        <div>
                            <p class="text-sm font-bold text-orange-800">Incomplete Exam</p>
                            <p class="text-xs text-orange-600 font-medium">You have only set <?= count($questions) ?> out of <?= $exam->num_quest ?> required questions. This exam will not be available to students until all questions are set.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
