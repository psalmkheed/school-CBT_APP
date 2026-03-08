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
$questions = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<style>
    @media print {
        body { background: white !important; }
        .no-print { display: none !important; }
        .fadeIn { padding: 0 !important; margin: 0 !important; background: white !important; }
        .max-w-4xl { max-width: 100% !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
        .bg-white { border: none !important; box-shadow: none !important; padding: 10px 0 !important; border-bottom: 1px solid #eee !important; border-radius: 0 !important; }
        .rounded-3xl, .rounded-2xl { border-radius: 0 !important; }
        .shadow-sm, .shadow-lg { box-shadow: none !important; }
        
        /* Marking Scheme Toggle */
        body.printing-question-paper .correct-answer-indicator { display: none !important; }
        body.printing-question-paper .bg-green-50\/50 { background: white !important; border: 1px solid #eee !important; }
        body.printing-question-paper .text-green-800 { color: #374151 !important; }
        body.printing-question-paper .bg-green-600 { background: white !important; color: #9ca3af !important; border: 1px solid #d1d5db !important; }
        body.printing-question-paper .fill-blank-answer { display: none !important; }

        .question-image { max-height: 200px !important; }
        @page { margin: 2cm; }
    }
</style>

<div class="fadeIn w-full md:p-8 p-4 bg-gray-50/50 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <button onclick="loadPage('/school_app/staff/pages/exams.php')"
                    class="no-print size-10 shrink-0 rounded-full flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all cursor-pointer"
                    title="Go back">
                    <i class="bx bx-left-arrow-alt text-3xl"></i>
                </button>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">Preview Exam</h3>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($exam->subject) ?> • <?= htmlspecialchars($exam->class) ?></p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Print Tool -->
                <div class="relative group no-print">
                    <button class="bg-gray-800 text-white px-4 py-2 rounded-xl shadow-lg flex items-center gap-2 hover:bg-gray-900 transition-all cursor-pointer">
                        <i class="bx bx-printer font-bold text-lg"></i>
                        <span class="text-sm font-semibold">Print</span>
                        <i class="bx bx-chevron-down"></i>
                    </button>
                    <!-- Print Dropdown -->
                    <div class="absolute right-0 top-full mt-2 w-48 bg-white border border-gray-100 rounded-2xl shadow-xl z-50 py-2 hidden group-hover:block animate-in fade-in slide-in-from-top-2">
                        <button onclick="printExam('question')" class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                            <i class="bx bx-file text-base"></i> Question Paper
                        </button>
                        <button onclick="printExam('scheme')" class="w-full text-left px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 flex items-center gap-2 border-t border-gray-50">
                            <i class="bx bx-check-circle text-base text-green-600"></i> Marking Scheme
                        </button>
                    </div>
                </div>

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
                            <p class="text-lg font-bold text-gray-800 leading-relaxed"><?= $q->question_text ?></p>
                            <?php if ($q->question_image): ?>
                                <div class="mt-4 bg-gray-50 p-2 rounded-2xl border border-gray-100 inline-block max-w-full overflow-hidden shadow-sm">
                                    <img src="/school_app/uploads/questions/<?= $q->question_image ?>" class="max-h-[250px] w-auto rounded-xl">
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (($q->question_type ?? 'mcq') === 'fill_blank'): ?>
                            <!-- Fill in the Blank preview -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="px-3 py-1 bg-amber-100 rounded-lg">
                                        <span class="text-[9px] font-black text-amber-600 uppercase tracking-widest">✏️ Fill in the Blank</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-4 rounded-2xl border border-green-200 bg-green-50/50 correct-answer-indicator">
                                    <div
                                        class="size-8 shrink-0 rounded-lg flex items-center justify-center font-black text-sm bg-green-600 text-white shadow-lg shadow-green-100">
                                        <i class="bx bx-check text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-green-500">Correct Answer</span>
                                        <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($q->correct_answer) ?></p>
                                    </div>
                                    <i class="bx bx-check-double text-green-600 text-xl"></i>
                                </div>
                                <div class="hidden print:block h-10 border-b border-dashed border-gray-300 w-full mb-4 fill-blank-answer"></div>
                            </div>
                        <?php else: ?>
                        <!-- MCQ Options -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach (['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $key => $val): ?>
                                    <div
                                        class="flex items-center gap-3 p-4 rounded-2xl border <?= $q->correct_answer === $key ? 'border-green-200 bg-green-50/50' : 'border-gray-50 bg-gray-50/30' ?>">
                                        <div
                                            class="size-8 shrink-0 rounded-lg flex items-center justify-center font-black text-sm <?= $q->correct_answer === $key ? 'bg-green-600 text-white shadow-lg shadow-green-100' : 'bg-white text-gray-400 border border-gray-100 shadow-sm' ?>">
                                            <?= $key ?>
                                    </div>
                                        <span class="text-sm font-semibold <?= $q->correct_answer === $key ? 'text-green-800' : 'text-gray-600' ?>"><?= $val ?></span>
                                        <?php if ($q->correct_answer === $key): ?>
                                            <i class="bx bx-check-double text-green-600 ml-auto text-xl correct-answer-indicator"></i>
                                        <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                        </div>
                        <?php endif; ?>
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
<script>
    function printExam(mode) {
        // Toggle printing class
        if (mode === 'question') {
            document.body.classList.add('printing-question-paper');
        } else {
            document.body.classList.remove('printing-question-paper');
        }

        // Hide UI elements not caught by CSS (just in case)
        const sidebar = document.getElementById('sideBar');
        const navbar = document.querySelector('nav');
        if(sidebar) sidebar.classList.add('no-print');
        if(navbar) navbar.classList.add('no-print');

        // Trigger print
        window.print();

        // Restore
        document.body.classList.remove('printing-question-paper');
    }
</script>
