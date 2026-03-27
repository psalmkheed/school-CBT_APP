<?php
require_once __DIR__ . '/../connections/db.php';
require_once __DIR__ . '/../auth/check.php';

// Fetch published exams for the student's class scoped to the active session/term
$active_session = $_SESSION['active_session'] ?? '';
$active_term    = $_SESSION['active_term']    ?? '';

$stmt = $conn->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM exam_results r WHERE r.exam_id = e.id AND r.user_id = :user_id) as taken_count
    FROM exams e
    WHERE e.class = :class 
      AND e.exam_status = 'published'
      AND e.session = :session
      AND e.term = :term
    ORDER BY e.id DESC
");
$stmt->execute([
    ':class'   => $user->class,
    ':user_id' => $user->id,
    ':session' => $active_session,
    ':term'    => $active_term,
]);
$exams = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="p-4 md:p-8 min-h-screen bg-gray-50/30">
    <div class="flex items-center gap-4 mb-8">
        <button onclick="goHome()"
            class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-green-700 hover:border-green-200 hover:bg-green-50 transition-all cursor-pointer"
            title="Go back" data-tippy-content="Back to Dashboard">
            <i class="bx bx-arrow-left-stroke text-4xl"></i>
        </button>
        <div>
            <h3 class="text-2xl font-bold text-gray-800">Available Exams</h3>
            <p class="text-sm text-gray-500">Select an exam to start your assessment</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="examsList">
        <?php if (count($exams) > 0): ?>
                <?php foreach ($exams as $exam):
                    $isTaken = $exam->taken_count > 0;
                    $isExpired = false;
                    if (!empty($exam->due_date)) {
                        if (strtotime($exam->due_date) < time()) {
                            $isExpired = true;
                        }
                    }
                    ?>
                    <div
                        class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden group relative">
                        <!-- Decor -->
                        <div
                            class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-green-500/5 to-transparent rounded-bl-full -mr-16 -mt-16 transition-transform group-hover:scale-110">
                        </div>
        
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div
                                    class="size-12 rounded-2xl bg-green-50 flex items-center justify-center text-green-600 group-hover:bg-green-600 group-hover:text-white transition-all duration-500 shadow-sm">
                                    <i class="bx bx-pencil text-2xl"></i>
                                </div>
                                <?php if ($isTaken): ?>
                                    <span
                                        class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider">Completed</span>
                                <?php elseif ($isExpired): ?>
                                    <span
                                        class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase tracking-wider">Expired</span>
                                <?php else: ?>
                                    <span
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold uppercase tracking-wider">Pending</span>
                                <?php endif; ?>
                            </div>
        
                            <h4 class="text-xl font-semibold text-gray-800 mb-1 group-hover:text-green-600 transition-colors">
                                <?= htmlspecialchars($exam->subject) ?></h4>
                            <p class="text-sm text-gray-400 font-medium mb-6"><?= htmlspecialchars($exam->exam_type) ?> •
                                <?= htmlspecialchars($exam->paper_type) ?></p>
        
                            <div class="grid grid-cols-2 gap-4 mb-8">
                                <div class="bg-gray-50/50 p-3 rounded-2xl border border-gray-100/50">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mb-1">Questions</p>
                                    <p class="text-sm font-semibold text-gray-700"><?= $exam->num_quest ?> Items</p>
                                </div>
                                <div class="bg-gray-50/50 p-3 rounded-2xl border border-gray-100/50">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mb-1">Duration</p>
                                    <p class="text-sm font-semibold text-gray-700"><?= $exam->time_allowed ?> Mins</p>
                                </div>
                            </div>
        
                            <?php if ($isExpired && !$isTaken): ?>
                                <button disabled
                                    class="w-full py-4 rounded-2xl font-bold text-sm transition-all duration-300 shadow-lg bg-green-600 text-white opacity-60 cursor-not-allowed">
                                    Expired
                                </button>
                            <?php else: ?>
                                <button
                                    onclick="<?= $isTaken ? "viewResult({$exam->id})" : "confirmStartExam({$exam->id}, '" . addslashes($exam->subject) . "', {$exam->num_quest}, {$exam->time_allowed})" ?>"
                                    class="w-full py-4 rounded-2xl font-bold text-sm transition-all duration-300 shadow-lg cursor-pointer
                                        <?= $isTaken ? 'bg-white text-green-600 border-2 border-green-600 hover:bg-green-50' : 'bg-green-600 text-white hover:bg-green-700 hover:shadow-green-200' ?>">
                                    <?= $isTaken ? 'View Result' : 'Start Examination' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div
                    class="col-span-full py-20 bg-white rounded-3xl border border-dashed border-gray-200 flex flex-col items-center justify-center text-center px-6">
                    <div class="size-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                        <i class="bx bx-calendar-x text-4xl text-gray-300"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-800 mb-2">No Exams Scheduled</h4>
                    <p class="text-gray-500 max-w-sm">You've cleared all your assessments! New exams will appear here when assigned
                        by your teachers.</p>
                </div>
            <?php endif; ?>
        </div>
        </div>
        
        <!-- Exam Instruction Modal -->
        <div id="instructionModal"
            class="hidden fixed inset-0 z-[900] flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeInstruction()"></div>
        
            <div
                class="relative bg-white w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-[2.5rem] shadow-2xl overflow-hidden fadeIn">
        
                <!-- Drag Handle (mobile) -->
                <div class="flex justify-center pt-4 pb-1 sm:hidden">
                    <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
                </div>
        
                <!-- Header -->
                <div class="px-8 pt-6 pb-4 flex items-center gap-4">
                    <div
                        class="size-14 shrink-0 rounded-2xl bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-500">
                        <i class="bx bx-info-circle text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800 leading-tight" id="modalExamSubject">Examination</h3>
                        <p class="text-xs text-gray-400 font-medium mt-0.5">Read these instructions before you begin</p>
                    </div>
                </div>
        
                <!-- Divider -->
                <div class="mx-8 border-t border-gray-100"></div>
        
                <!-- Instructions -->
                <div class="px-8 py-6 space-y-3">
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-blue-50 border border-blue-100">
                        <div class="size-9 shrink-0 rounded-xl bg-blue-500 flex items-center justify-center text-white mt-0.5">
                            <i class="bx bx-clock-5 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">Timed Assessment</p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">The countdown starts immediately when you
                                click <strong>Begin</strong>. Manage your time wisely.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-red-50 border border-red-100">
                        <div class="size-9 shrink-0 rounded-xl bg-red-500 flex items-center justify-center text-white mt-0.5">
                            <i class="bx bx-alert-shield text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">Anti-Cheat Active</p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Do not switch tabs or leave this page. Your
                                exam may be auto-submitted.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-4 rounded-2xl bg-green-50 border border-green-100">
                        <div class="size-9 shrink-0 rounded-xl bg-green-500 flex items-center justify-center text-white mt-0.5">
                            <i class="bx bx-save text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">Answers are Auto-Saved</p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Your selections are saved locally. You can
                                navigate back and forth between questions.</p>
                        </div>
                    </div>
                </div>
        
                <!-- Action Buttons -->
                <div class="px-8 pb-8 grid grid-cols-2 gap-3">
                    <button onclick="closeInstruction()"
                        class="w-full py-4 bg-gray-100 text-gray-600 rounded-2xl font-semibold text-sm hover:bg-gray-200 transition-all cursor-pointer">
                        Not Now
            </button>
            <button id="startBtn"
                class="w-full py-4 bg-green-600 text-white rounded-2xl font-semibold text-sm hover:bg-green-700 shadow-lg shadow-green-100 transition-all cursor-pointer flex items-center justify-center gap-2">
                <i class="bx bx-play-circle text-lg"></i> Begin Exam
            </button>
        </div>
    </div>
</div>

<script>
let currentExamInfo = null;

function confirmStartExam(id, subject, questions, time) {
    currentExamInfo = { id, subject, questions, time };
    document.getElementById('modalExamSubject').innerText = subject;
    document.getElementById('instructionModal').classList.remove('hidden');
    document.getElementById('startBtn').onclick = function() {
        startExam(id);
    };
}

function closeInstruction() {
    document.getElementById('instructionModal').classList.add('hidden');
}

function startExam(id) {
    $('#mainContent').fadeOut(300, function() {
        $.ajax({
            url: BASE_URL + 'student/pages/take_exam_interface.php',
            type: 'POST',
            data: { exam_id: id },
            success: function(response) {
                $('#mainContent').html(response).fadeIn(300);
            },
            error: function() {
                Swal.fire('Error', 'Failed to load exam. Please try again.', 'error');
                $('#mainContent').fadeIn(300);
            }
        });
    });
}

function viewResult(id) {
    $('#mainContent').fadeOut(300, function() {
        $.ajax({
            url: BASE_URL + 'student/pages/exam_result_view.php',
            type: 'POST',
            data: { exam_id: id },
            success: function(response) {
                $('#mainContent').html(response).fadeIn(300);
            },
            error: function() {
                Swal.fire('Error', 'Failed to load result.', 'error');
                $('#mainContent').fadeIn(300);
            }
        });
    });
}
</script>
