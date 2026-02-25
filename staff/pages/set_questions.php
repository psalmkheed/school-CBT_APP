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

$total_questions = (int)$exam->num_quest;
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="loadPage('/school_app/staff/pages/exams.php')"
                  class="size-10 shrink-0 rounded-full flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all cursor-pointer"
                  data-tippy-content="Go Back">
                  <i class="bx bx-arrow-left text-2xl"></i>
            </button>
            <div>
                <h3 class="text-2xl font-bold text-gray-800">Set Questions</h3>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($exam->subject) ?> • <?= htmlspecialchars($exam->class) ?></p>
            </div>
        </div>
        
        <div class="bg-blue-50 px-4 py-2 rounded-xl border border-blue-100">
            <span class="text-xs font-bold text-blue-400 uppercase tracking-widest block">Progress</span>
            <span class="text-sm font-black text-blue-700" id="progressText">Checking...</span>
        </div>
    </div>

    <!-- Question Builder UI -->
    <div class="max-w-4xl mx-auto">
        <!-- Question Pagination -->
        <div class="flex flex-wrap gap-2 mb-8 justify-center" id="questionNav">
            <?php for ($i = 1; $i <= $total_questions; $i++): ?>
                <button 
                    onclick="loadQuestion(<?= $i ?>)"
                    class="question-number-btn size-10 rounded-xl border border-gray-200 flex items-center justify-center font-bold text-sm transition-all hover:border-blue-400 hover:text-blue-600 cursor-pointer"
                    id="nav-<?= $i ?>"
                    data-qnum="<?= $i ?>">
                    <?= $i ?>
                </button>
            <?php endfor; ?>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 shadow-xl shadow-gray-100/50 overflow-hidden relative">
            <div id="loadingOverlay" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex items-center justify-center hidden">
                <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
            </div>

            <form id="questionForm" class="p-8">
                <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                <input type="hidden" name="question_number" id="currentQNum" value="1">
                <input type="hidden" name="existing_id" id="existing_id" value="">

                <div class="space-y-6">
                    <!-- Question Text -->
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-3">Question Text</label>
                        <textarea name="question_text" id="question_text" required
                            class="w-full bg-gray-50 border border-gray-100 rounded-2xl p-5 text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-400 focus:bg-white transition-all min-h-[120px] placeholder:text-gray-300"
                            placeholder="Type your question here..."></textarea>
                    </div>

                    <!-- Options Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach(['A', 'B', 'C', 'D'] as $opt): ?>
                            <div class="relative">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Option <?= $opt ?></label>
                                <div class="flex items-center gap-3">
                                    <input type="text" name="option_<?= strtolower($opt) ?>" id="option_<?= strtolower($opt) ?>" required
                                        class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:bg-white transition-all"
                                        placeholder="Enter option <?= $opt ?>">
                                    <label class="cursor-pointer group flex-shrink-0">
                                        <input type="radio" name="correct_answer" value="<?= $opt ?>" required class="hidden peer">
                                        <div class="size-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 peer-checked:bg-green-600 peer-checked:text-white transition-all group-hover:bg-gray-200" data-tippy-content="Mark as correct">
                                            <i class="bx bx-check font-bold"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-10 pt-8 border-t border-gray-50 flex items-center justify-between">
                    <button type="button" id="prevBtn" onclick="navigateQ(-1)"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-gray-500 font-bold hover:bg-gray-100 transition-all cursor-pointer">
                        <i class="bx bx-left-arrow-alt text-xl"></i>
                        Previous
                    </button>
                    
                    <div class="flex gap-3">
                        <button type="button" id="deleteBtn" onclick="deleteQuestion()"
                            class="size-12 rounded-xl border border-red-50 text-red-400 hover:bg-red-50 hover:text-red-600 transition-all cursor-pointer flex items-center justify-center hidden"
                            title="Delete this question">
                            <i class="bx bx-trash text-xl"></i>
                        </button>
                        <button type="submit" id="saveBtn"
                            class="px-8 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all cursor-pointer">
                            Save Question
                        </button>
                        <button type="button" id="nextBtn" onclick="navigateQ(1)"
                            class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gray-900 text-white font-bold hover:bg-black transition-all cursor-pointer">
                            Next
                            <i class="bx bx-right-arrow-alt text-xl"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentQuestion = 1;
const totalQs = <?= $total_questions ?>;
const examId = <?= $exam_id ?>;

function updateProgress() {
    $.post('/school_app/staff/auth/get_exam_progress.php', { exam_id: examId }, function(data) {
        if (data.success) {
            $("#progressText").text(`${data.count} / ${totalQs} Set`);
            // Update nav colors
            const setQs = data.set_questions; // Array of numbers
            $(".question-number-btn").removeClass("bg-green-600 text-white border-green-600 bg-blue-50 border-blue-200 text-blue-600");
            
            setQs.forEach(num => {
                $(`#nav-${num}`).addClass("bg-green-50 border-green-200 text-green-600");
            });
            
            $(`#nav-${currentQuestion}`).addClass("bg-blue-600 text-white border-blue-600 RING RING-blue-100");
        }
    }, 'json');
}

function loadQuestion(num) {
    if (num < 1 || num > totalQs) return;
    currentQuestion = num;
    $("#currentQNum").val(num);
    $("#loadingOverlay").removeClass("hidden");
    $("#questionForm")[0].reset();
    $("#existing_id").val("");

    $.post('/school_app/staff/auth/get_question.php', { exam_id: examId, q_num: num }, function(data) {
        $("#loadingOverlay").addClass("hidden");
        if (data.success && data.question) {
            $("#question_text").val(data.question.question_text);
            $("#option_a").val(data.question.option_a);
            $("#option_b").val(data.question.option_b);
            $("#option_c").val(data.question.option_c);
            $("#option_d").val(data.question.option_d);
            $(`input[name="correct_answer"][value="${data.question.correct_answer}"]`).prop('checked', true);
            $("#existing_id").val(data.question.id);
            $("#deleteBtn").removeClass("hidden");
        } else {
            $("#deleteBtn").addClass("hidden");
        }
        updateProgress();
        
        // Update nav buttons
        $(".question-number-btn").removeClass("ring-2 ring-blue-500 ring-offset-2");
        $(`#nav-${num}`).addClass("ring-2 ring-blue-500 ring-offset-2");
        
        // Scroll to button
        $(`#nav-${num}`)[0].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }, 'json');
}

function deleteQuestion() {
    const id = $("#existing_id").val();
    if (!id) return;

    Swal.fire({
        title: 'Are you sure?',
        text: "You want to delete this question?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('/school_app/staff/auth/delete_question.php', { id: id, exam_id: examId }, function(data) {
                if (data.success) {
                    window.showToast("Question deleted", "success");
                    loadQuestion(currentQuestion);
                } else {
                    window.showToast(data.message, "error");
                }
            }, 'json');
        }
    });
}


function navigateQ(dir) {
    loadQuestion(currentQuestion + dir);
}

$("#questionForm").on("submit", function(e) {
    e.preventDefault();
    const btn = $("#saveBtn");
    const formData = $(this).serialize();

    $.ajax({
        url: '/school_app/staff/auth/save_question.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            btn.prop('disabled', true).html(`<div class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full mx-auto"></div>`);
        },
        success: function(data) {
            if (data.success) {
                window.showToast("Question saved successfully!", "success");
                updateProgress();
                // Optionally move to next
                if (currentQuestion < totalQs) {
                    setTimeout(() => navigateQ(1), 500);
                }
            } else {
                window.showToast(data.message, "error");
            }
        },
        error: function() {
            window.showToast("Network error. Try again", "error");
        },
        complete: function() {
            btn.prop('disabled', false).text('Save Question');
        }
    });
});

// Initial load
$(document).ready(function() {
    loadQuestion(1);
});
</script>
