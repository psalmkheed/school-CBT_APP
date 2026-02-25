<?php
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'staff') {
    die("Unauthorized");
}

$teacher_name = $user->first_name . ' ' . $user->last_name;

$stmt = $conn->prepare("SELECT * FROM exams WHERE subject_teacher = :teacher ORDER BY id DESC");
$stmt->execute([':teacher' => $teacher_name]);
$exams = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-800">Exam Management</h3>
            <p class="text-sm text-gray-500">Manage questions for your assigned exams.</p>
        </div>
    </div>

    <!-- Exams Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (count($exams) > 0): ?>
            <?php foreach ($exams as $exam): ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden group">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white">
                                <i class="bx-book-open text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest 
                                <?= $exam->exam_status === 'published' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600' ?>">
                                <?= htmlspecialchars($exam->exam_status) ?>
                            </span>
                        </div>

                        <h4 class="text-xl font-black text-gray-800 mb-1"><?= htmlspecialchars($exam->subject) ?></h4>
                        <p class="text-sm text-gray-400 mb-4"><?= htmlspecialchars($exam->class) ?> • <?= htmlspecialchars($exam->exam_type) ?></p>
                        
                        <div class="flex items-center gap-4 mb-6">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Questions</span>
                                <span class="text-sm font-bold text-gray-700"><?= $exam->num_quest ?></span>
                            </div>
                            <div class="flex flex-col border-l border-gray-100 pl-4">
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Duration</span>
                                <span class="text-sm font-bold text-gray-700"><?= $exam->time_allowed ?> mins</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <button 
                                onclick="loadSetQuestions(<?= $exam->id ?>)"
                                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 text-gray-700 rounded-xl hover:bg-blue-600 hover:text-white transition-all duration-300 font-bold text-sm cursor-pointer border border-gray-100 hover:border-blue-600 shadow-sm">
                                <i class="bx bx-pencil"></i>
                                Set Questions
                            </button>
                            <button 
                                onclick="previewExam(<?= $exam->id ?>)"
                                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white text-blue-600 rounded-xl hover:bg-blue-50 transition-all duration-300 font-bold text-sm cursor-pointer border border-blue-100 shadow-sm">
                                <i class="bx bx-show"></i>
                                Preview Exam
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-20 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bx bx-folder-open text-4xl text-gray-300"></i>
                </div>
                <h4 class="text-gray-500 font-bold">No exams assigned to you yet.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function loadSetQuestions(examId) {
    $("#mainContent").load("/school_app/staff/pages/set_questions.php", { exam_id: examId });
}

function previewExam(examId) {
    $("#mainContent").load("/school_app/staff/pages/preview_exam.php", { exam_id: examId });
}
</script>
