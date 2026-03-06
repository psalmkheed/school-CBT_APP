<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

if (!$exam_id) {
    exit('<div class="p-8 text-center text-red-500 font-bold">Invalid Result Requested.</div>');
}

// Fetch exam details and result
$stmt = $conn->prepare("
    SELECT e.subject, e.exam_type, e.class, r.* 
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.exam_id = :exam_id AND r.user_id = :user_id
");
$stmt->execute([':exam_id' => $exam_id, ':user_id' => $user->id]);
$result = $stmt->fetch(PDO::FETCH_OBJ);

if (!$result) {
    exit('<div class="p-8 text-center text-red-500 font-bold">Result not found.</div>');
}

$percentage = (float)$result->percentage;
$isPassed = $percentage >= 50;

if ($percentage >= 90) {
    $msg = "Exceptional! You've mastered this subject.";
    $color = "green";
    $icon = "party";
    $gradientFrom = "#16a34a";
    $gradientTo = "#4ade80";
} elseif ($percentage >= 70) {
    $msg = "Great Job! You have a strong understanding.";
    $color = "blue";
    $icon = "medal";
    $gradientFrom = "#2563eb";
    $gradientTo = "#60a5fa";
} elseif ($percentage >= 50) {
    $msg = "Good Effort! Keep practicing to improve.";
    $color = "orange";
    $icon = "cool";
    $gradientFrom = "#ea580c";
    $gradientTo = "#fb923c";
} elseif ($percentage >= 40) {
    $msg = "You can do better next time.";
    $color = "yellow";
    $icon = "badge-check";
    $gradientFrom = "#ca8a04";
    $gradientTo = "#facc15";
} else {
    $msg = "Don't Give Up! Focus on weak areas and try again.";
    $color = "red";
    $icon = "sad";
    $gradientFrom = "#dc2626";
    $gradientTo = "#f87171";
}

// Dashoffset for SVG circle (circumference = 2 * pi * 54 ≈ 339.3)
$circumference = 339.3;
$dashOffset = $circumference - ($percentage / 100) * $circumference;
?>

<div class="fadeIn p-4 md:p-10 min-h-screen bg-gray-50/30">

    <!-- Back Button -->
    <div class="flex items-center gap-4 mb-8">
        <button onclick="
                $('#mainContent').fadeOut(200, function(){
                    $.ajax({
                        url: '/school_app/student/pages/exam_history.php',
                        type: 'POST',
                        success: function(r){ $('#mainContent').html(r).fadeIn(200); }
                    });
                })"
            class="bg-white border border-gray-100 md:hidden size-10 shrink-0 rounded-md flex items-center justify-center text-gray-300 hover:text-gray-600 hover:border-gray-200 hover:bg-gray-50 transition-all cursor-pointer sticky inset-x-6 top-6 z-50"
            title="Go back">
            <i class="fa fa-chevron-left text-xl"></i>
        </button>

        <button onclick="
                $('#mainContent').fadeOut(200, function(){
                    $.ajax({
                        url: '/school_app/student/pages/exam_history.php',
                        type: 'POST',
                        success: function(r){ $('#mainContent').html(r).fadeIn(200); }
                    });
                })"
            class="hidden md:flex items-center text-gray-400 hover:text-blue-600 font-bold text-sm transition-colors cursor-pointer group">
            <i class="fa fa-chevron-left text-xl group-hover:-translate-x-1 transition-transform"></i>
            Back to History
        </button>
    </div>

    <div class="max-w-2xl mx-auto">

        <!-- Result Card -->
        <div class="bg-white rounded-[3rem] shadow-2xl shadow-gray-200/60 overflow-hidden border border-gray-100/80 mb-6">

            <!-- Hero Header -->
            <div class="relative p-10 text-center text-white overflow-hidden" style="background: linear-gradient(135deg, <?= $gradientFrom ?>, <?= $gradientTo ?>)">
                <!-- Decorative Bubbles -->
                <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-bl-full -mr-10 -mt-10"></div>
                <div class="absolute bottom-0 left-0 w-28 h-28 bg-black/5 rounded-tr-full -ml-10 -mb-10"></div>

                <div class="relative z-10">
                    <div class="size-20 rounded-[1.75rem] bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center mx-auto mb-5 shadow-xl">
                        <i class="bx bx-<?= $icon ?> text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-black mb-1 tracking-tight"><?= $isPassed ? 'Congratulations!' : 'Heads Up!' ?></h2>
                    <p class="text-white/80 font-medium text-sm"><?= $msg ?></p>
                </div>
            </div>

            <!-- Score Ring -->
            <div class="flex flex-col items-center py-10 px-6 bg-white">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-6">Final Score</p>
                <div class="relative size-44 mb-6">
                    <svg class="size-44 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#f3f4f6" stroke-width="10"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="<?= $gradientFrom ?>" stroke-width="10"
                            stroke-dasharray="<?= $circumference ?>"
                            stroke-dashoffset="<?= $dashOffset ?>"
                            stroke-linecap="round"
                            style="transition: stroke-dashoffset 1.2s cubic-bezier(.4,0,.2,1)"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-gray-800 tabular-nums leading-none"><?= round($percentage) ?><span class="text-xl text-gray-300">%</span></span>
                        <span class="text-xs font-bold text-gray-400 mt-1"><?= $result->score ?> / <?= $result->total_questions ?></span>
                    </div>
                </div>

                <!-- Stat Pills -->
                <div class="grid grid-cols-2 gap-3 w-full mb-8">
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 text-center">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Subject</p>
                        <p class="text-sm font-black text-gray-800 leading-tight"><?= htmlspecialchars($result->subject) ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 text-center">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Exam Type</p>
                        <p class="text-sm font-black text-gray-800 leading-tight"><?= htmlspecialchars($result->exam_type) ?></p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="w-full flex flex-col gap-3">
                    <?php if (!empty($result->student_answers)): ?>
                        <button id="toggleReviewBtn"
                            onclick="document.getElementById('reviewSection').classList.toggle('hidden'); const h = document.getElementById('reviewSection').classList.contains('hidden'); this.innerHTML = h ? '<i class=\'bx bx-eye\'></i>&nbsp; View Performance Review' : '<i class=\'bx bx-eye-slash\'></i>&nbsp; Hide Review'"
                            class="w-full py-4 bg-white border-2 border-<?= $color ?>-500 text-<?= $color ?>-600 rounded-2xl font-black text-sm hover:bg-<?= $color ?>-50 transition-all cursor-pointer flex items-center justify-center gap-2">
                            <i class="bx bx-eye"></i>&nbsp; View Performance Review
                        </button>
                    <?php endif; ?>

                    <button onclick="goHome()"
                        class="w-full py-4 bg-gray-800 text-white rounded-2xl font-black text-sm hover:bg-black transition-all shadow-lg cursor-pointer">
                        Back to Dashboard
                    </button>
                </div>

                <p class="text-[10px] text-gray-300 font-bold uppercase tracking-widest mt-6">
                    Taken <?= date('M j, Y • g:i A', strtotime($result->taken_at)) ?>
                </p>
            </div>
        </div>

        <!-- Performance Review Section -->
        <div id="reviewSection" class="hidden space-y-4">
            <div class="flex items-center gap-3 mb-6 px-2">
                <div class="size-10 rounded-2xl bg-<?= $color ?>-50 flex items-center justify-center text-<?= $color ?>-600">
                    <i class="bx bx-check-circle text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-gray-800">Performance Review</h3>
                    <p class="text-xs text-gray-400 font-medium">Question-by-question breakdown</p>
                </div>
            </div>

            <?php
            $student_picks = json_decode($result->student_answers, true) ?: [];
            $q_stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = :eid ORDER BY question_number ASC");
            $q_stmt->execute([':eid' => $result->exam_id]);
            $all_qs = $q_stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($all_qs as $index => $q):
                $student_ans = $student_picks[$q->id] ?? null;
                $qtype = $q->question_type ?? 'mcq';

                if ($qtype === 'fill_blank') {
                    $isCorrect = $student_ans && strtolower(trim($student_ans)) === strtolower(trim($q->correct_answer));
                } else {
                    $isCorrect = $student_ans && strtoupper($student_ans) === strtoupper($q->correct_answer);
                }

                // Escape values for use in JS data attributes
                $js_question = htmlspecialchars($q->question_text, ENT_QUOTES);
                $js_a = htmlspecialchars($q->option_a ?? '', ENT_QUOTES);
                $js_b = htmlspecialchars($q->option_b ?? '', ENT_QUOTES);
                $js_c = htmlspecialchars($q->option_c ?? '', ENT_QUOTES);
                $js_d = htmlspecialchars($q->option_d ?? '', ENT_QUOTES);
                $js_correct = htmlspecialchars($q->correct_answer, ENT_QUOTES);
                $js_subject = htmlspecialchars($result->subject, ENT_QUOTES);
            ?>
                <div class="bg-white rounded-[2rem] p-6 border border-gray-100 shadow-sm">
                    <!-- Question Header -->
                    <div class="flex items-center justify-between mb-5">
                        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest">Q<?= $index + 1 ?></span>
                        <div class="flex items-center gap-2">
                            <?php if ($student_ans): ?>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wide
                                                                                                                            <?= $isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
                                    <i class="bx <?= $isCorrect ? 'bx-check' : 'bx-x' ?> text-sm"></i>
                                    <?= $isCorrect ? 'Correct' : 'Incorrect' ?>
                                </span>
                                <?php else: ?>
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wide bg-gray-100 text-gray-400">
                                                    <i class="bx bx-minus text-sm"></i> Skipped
                                                </span>
                                        <?php endif; ?>
            <!-- AI Explain Button -->
            <button
                class="ai-explain-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wide bg-violet-50 text-violet-600 hover:bg-violet-100 transition-all cursor-pointer border border-violet-100"
                data-question="<?= $js_question ?>" data-a="<?= $js_a ?>" data-b="<?= $js_b ?>" data-c="<?= $js_c ?>"
            data-d="<?= $js_d ?>" data-correct="<?= $js_correct ?>" data-subject="<?= $js_subject ?>"
            data-index="<?= $index ?>">
            <i class="bx bx-sparkles-alt text-sm"></i> Ask AI
        </button>
        </div>
                    </div>

                    <!-- Question Text -->
                    <p class="text-sm font-semibold text-gray-800 leading-relaxed mb-5"><?= $q->question_text ?></p>

                    <!-- Options / Fill-in Display -->
                    <?php if ($qtype === 'fill_blank'): ?>
                        <!-- Fill in the Blank review -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="px-3 py-1 bg-amber-100 rounded-lg">
                                    <span class="text-[9px] font-black text-amber-600 uppercase tracking-widest">✏️ Fill in the Blank</span>
                                </div>
                            </div>
                            <!-- Student's Answer -->
                            <div
                                class="flex items-center gap-3 p-3 rounded-2xl border <?= $student_ans ? ($isCorrect ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') : 'bg-gray-50 border-gray-200' ?>">
                                <div
                                    class="size-8 shrink-0 rounded-xl flex items-center justify-center font-black text-xs <?= $student_ans ? ($isCorrect ? 'bg-green-600 text-white' : 'bg-red-500 text-white') : 'bg-gray-200 text-gray-400' ?>">
                                    <i class="bx <?= $student_ans ? ($isCorrect ? 'bx-check' : 'bx-x') : 'bx-minus' ?> text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <span
                                        class="text-[9px] font-bold uppercase tracking-widest <?= $student_ans ? ($isCorrect ? 'text-green-500' : 'text-red-400') : 'text-gray-400' ?>">Your
                                        Answer</span>
                                    <p
                                        class="text-sm font-bold <?= $student_ans ? ($isCorrect ? 'text-green-800' : 'text-red-700') : 'text-gray-400 italic' ?>">
                                        <?= $student_ans ? htmlspecialchars($student_ans) : 'Skipped' ?>
                                    </p>
                                </div>
                                <?php if ($student_ans && $isCorrect): ?>
                                    <i class="bx bx-check-circle text-green-500 text-lg shrink-0"></i>
                                <?php elseif ($student_ans): ?>
                                    <i class="bx bx-x-circle text-red-400 text-lg shrink-0"></i>
                                <?php endif; ?>
                            </div>
                            <!-- Correct Answer (shown if wrong or skipped) -->
                            <?php if (!$isCorrect): ?>
                                <div class="flex items-center gap-3 p-3 rounded-2xl border bg-green-50 border-green-200">
                                    <div
                                        class="size-8 shrink-0 rounded-xl flex items-center justify-center font-black text-xs bg-green-600 text-white">
                                        <i class="bx bx-check text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-green-500">Correct Answer</span>
                                        <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($q->correct_answer) ?></p>
                                    </div>
                                    <i class="bx bx-check-circle text-green-500 text-lg shrink-0"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- MCQ Options -->
                        <div class="grid grid-cols-1 gap-2">
                            <?php
                            $opts = ['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d];
                            foreach ($opts as $key => $val):
                                $isCorrectOpt = strtoupper($q->correct_answer) === $key;
                                $isStudentOpt = $student_ans && strtoupper($student_ans) === $key;

                                if ($isCorrectOpt) {
                                    $optStyle = "bg-green-50 border-green-200";
                                    $badgeStyle = "bg-green-600 text-white";
                                    $textStyle = "text-green-800 font-bold";
                                    $icon2 = '<i class="bx bx-check-circle text-green-500 text-lg ml-auto shrink-0"></i>';
                                } elseif ($isStudentOpt) {
                                    $optStyle = "bg-red-50 border-red-200";
                                    $badgeStyle = "bg-red-500 text-white";
                                    $textStyle = "text-red-700 font-bold";
                                    $icon2 = '<i class="bx bx-x-circle text-red-400 text-lg ml-auto shrink-0"></i>';
                                } else {
                                    $optStyle = "bg-gray-50 border-gray-100";
                                    $badgeStyle = "bg-white text-gray-300 border border-gray-200";
                                    $textStyle = "text-gray-500";
                                    $icon2 = '';
                                }
                                ?>
                                <div class="flex items-center gap-3 p-3 rounded-2xl border <?= $optStyle ?>">
                                    <div class="size-8 shrink-0 rounded-xl flex items-center justify-center font-black text-xs <?= $badgeStyle ?>">
                                        <?= $key ?>
                                    </div>
                                    <span class="text-sm <?= $textStyle ?> leading-snug"><?= $val ?></span>
                                    <?= $icon2 ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- AI Explanation Panel (hidden by default) -->
                    <div id="ai-panel-<?= $index ?>" class="hidden mt-5 p-4 bg-violet-50 border border-violet-100 rounded-2xl">
    <div class="flex items-center gap-2 mb-3">
        <div class="size-7 rounded-lg bg-violet-600 flex items-center justify-center text-white shrink-0">
            <i class="bx bx-robot text-sm"></i>
        </div>
        <span class="text-[10px] font-black text-violet-500 uppercase tracking-widest">AI Explanation</span>
    </div>
    <p id="ai-text-<?= $index ?>" class="text-sm text-violet-900 leading-relaxed font-medium"></p>
</div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<script>
// ── AI Explain Handler ────────────────────────────────────────────────────
$(document).on('click', '.ai-explain-btn', function() {
    const btn    = $(this);
    const idx    = btn.data('index');
    const panel  = $(`#ai-panel-${idx}`);
    const textEl = $(`#ai-text-${idx}`);

    // Toggle off if already shown and filled
    if (!panel.hasClass('hidden') && textEl.text().trim()) {
        panel.slideUp(200, function(){ panel.addClass('hidden'); });
        return;
    }

    // Already fetched — just show again
    if (textEl.data('loaded')) {
        panel.removeClass('hidden').hide().slideDown(250);
        return;
    }

    // Show loading state
    btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin text-sm"></i> Thinking...');
    panel.removeClass('hidden').hide().slideDown(250);
    textEl.html('<span class="text-violet-400 italic text-xs">Generating explanation...</span>');

    $.ajax({
        url: '/school_app/student/auth/ai_explain.php',
        type: 'POST',
        data: {
            question  : btn.data('question'),
            option_a  : btn.data('a'),
            option_b  : btn.data('b'),
            option_c  : btn.data('c'),
            option_d  : btn.data('d'),
            correct   : btn.data('correct'),
            subject   : btn.data('subject'),
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                // Typewriter animation
                const text = res.explanation;
                textEl.text('');
                let i = 0;
                const interval = setInterval(() => {
                    textEl.text(text.slice(0, ++i));
                    if (i >= text.length) {
                        clearInterval(interval);
                        textEl.data('loaded', true);
                    }
                }, 12);
                btn.html('<i class="bx bx-robot text-sm"></i> Hide');
            } else {
                textEl.html(`<span class="text-red-500 text-xs font-bold">${res.message}</span>`);
                btn.html('<i class="bx bx-bot text-sm"></i> Ask AI');
            }
        },
        error: function() {
            textEl.html('<span class="text-red-500 text-xs font-bold">Network error. Please try again.</span>');
            btn.html('<i class="bx bx-bot text-sm"></i> Ask AI');
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
});
</script>
