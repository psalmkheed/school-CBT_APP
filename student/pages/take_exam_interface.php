<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

if (!$exam_id) {
    exit('<div class="p-8 text-center text-red-500 font-bold">Invalid Exam Requested.</div>');
}

// Fetch exam details
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = :id AND exam_status = 'published'");
$stmt->execute([':id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_OBJ);

if (!$exam) {
    exit('<div class="p-8 text-center text-red-500 font-bold">Exam not found or not yet published.</div>');
}

// Check if already taken
$stmt = $conn->prepare("SELECT id FROM exam_results WHERE exam_id = :id AND user_id = :user_id");
$stmt->execute([':id' => $exam_id, ':user_id' => $user->id]);
if ($stmt->fetch()) {
    exit('<div class="p-8 text-center text-blue-600 font-bold">You have already completed this examination.</div>');
}

// Fetch questions
$stmt = $conn->prepare("SELECT id, question_number, question_text, option_a, option_b, option_c, option_d, question_type, question_image FROM questions WHERE exam_id = :id ORDER BY question_number ASC");
$stmt->execute([':id' => $exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($questions) < $exam->num_quest) {
    exit('<div class="p-8 text-center text-orange-500 font-bold">This exam is currently undergoing maintenance. Please try again later.</div>');
}

// ── Randomize question order per student ────────────────────────────────
// Seed is deterministic per (user, exam) pair so the same student always
// gets the same shuffled order on refresh, but different from other students.
$seed = (int)$user->id * 9973 + (int)$exam_id * 6271; // two primes for spread
mt_srand($seed);
// Fisher-Yates shuffle using mt_rand
$n = count($questions);
for ($i = $n - 1; $i > 0; $i--) {
    $j = mt_rand(0, $i);
    [$questions[$i], $questions[$j]] = [$questions[$j], $questions[$i]];
}
mt_srand(); // reset seed to random so other mt_rand calls aren't affected
// ────────────────────────────────────────────────────────────────────────
// ────────────────────────────────────────────────────────────────────────

recordActivity($conn, 'EXAM_START', "Student started exam: '{$exam->subject}' (ID: $exam_id)");
?>

<div class="fixed inset-0 bg-white z-[900] flex flex-col fadeIn">
    <!-- Header / Status Bar -->
    <div class="h-16 bg-white border-b border-gray-50 px-4 md:px-8 flex items-center justify-between shrink-0 shadow-sm relative z-20">
        <div class="flex items-center gap-3">
            <div class="hidden md:flex flex-col">
                <h4 class="text-xs font-semibold text-gray-800 uppercase tracking-tighter"><?= htmlspecialchars($exam->subject) ?></h4>
                <p class="text-[9px] text-gray-400 font-bold uppercase"><?= htmlspecialchars($exam->exam_type) ?></p>
            </div>
            <div class="h-8 w-[1px] bg-gray-100 hidden md:block"></div>
            <div class="flex items-center gap-2 bg-blue-50/50 px-3 py-1.5 rounded-xl border border-blue-100/50">
                <i class="bx bx-list-ol text-blue-600 text-xs"></i>
                <span class="text-[11px] font-semibold text-blue-600 uppercase" id="progressText">Q1 of <?= count($questions) ?></span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-red-50/50 px-3 py-2 rounded-xl border border-red-100/30" id="timerBadge">
                <i class="bx bx-timer text-red-600 font-bold text-base animate-pulse"></i>
                <span class="text-base font-semibold text-red-600 tabular-nums tracking-tighter" id="examTimer">--:--</span>
            </div>
            <!-- Calculator Toggle -->
            <button id="calcToggleBtn" onclick="toggleCalculator()"
                class="size-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all cursor-pointer shadow-sm"
                title="Calculator">
                <i class="bx bx-calculator text-lg"></i>
            </button>
            <button onclick="confirmSubmit()" class="hidden md:flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white rounded-xl font-bold text-xs hover:bg-green-700 transition-all shadow-md shadow-green-100 cursor-pointer uppercase tracking-wider">
                <i class="bx bx-check-circle"></i> Submit
            </button>
        </div>
    </div>

    <!-- Progress Top Bar -->
    <div class="h-1.5 w-full bg-gray-100 shrink-0">
        <div id="progressBar" class="h-full bg-green-500 transition-all duration-500 shadow-[0_0_10px_rgba(34,197,94,0.3)]" style="width: 0%"></div>
    </div>

    <!-- Main Question Area -->
    <div class="flex-1 flex overflow-hidden bg-gray-50/30">
        
        <!-- Left: Current Question Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 flex justify-center relative scrollbar-thin scrollbar-thumb-gray-200">
            <div class="max-w-3xl w-full h-fit py-4" id="questionContainer">
                <!-- Question content injected by JS -->
                <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-2xl shadow-gray-200/50 border border-white relative overflow-hidden flex flex-col fadeIn">
                    <!-- Watermark -->
                    <div class="absolute -bottom-10 -right-10 text-[120px] font-semibold text-gray-50/50 select-none rotate-[-15deg] z-0 pointer-events-none" id="qNumWatermark">01</div>
                    
                    <div class="relative z-10 flex flex-col">
                        <div class="mb-10" id="questionTextContainer">
                            <p class="text-[11px] font-semibold text-blue-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <span class="size-2 rounded-full bg-blue-500 animate-pulse"></span>
                                Question <span id="displayQN">1</span>
                            </p>
                            <!-- Question Text and Image Injected here -->
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="optionsGrid">
                            <!-- Options injected by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Question Navigator (Desktop only) -->
        <div class="hidden lg:flex w-80 bg-white border-l border-gray-100 flex-col shrink-0 relative z-20">
            <div class="p-6 border-b border-gray-50 bg-gray-50/30">
                <h5 class="text-xs font-semibold text-gray-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="bx bx-grid-alt text-blue-600"></i>
                    Question Navigator
                </h5>
                <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Jump to any question</p>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6 scrollbar-thin">
                <div class="grid grid-cols-5 gap-2" id="navigatorGrid">
                    <!-- Numbers injected by JS -->
                </div>
            </div>

            <div class="p-6 border-t border-gray-50 bg-gray-50/30">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="size-3 rounded shadow-sm bg-green-500"></div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase">Answered</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-3 rounded shadow-sm bg-blue-600"></div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase">Current</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-3 rounded shadow-sm bg-gray-200"></div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase">Not Attempted</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigator Toggle (Small screen only) -->
    <button onclick="toggleMobileNav()" class="lg:hidden fixed right-6 bottom-24 size-14 rounded-full bg-blue-600 text-white shadow-xl shadow-blue-200 z-[950] flex items-center justify-center animate-bounce">
        <i class="bx bx-grid-alt text-2xl"></i>
    </button>

    <!-- Mobile Navigator Overlay -->
    <div id="mobileNavOverlay" class="hidden fixed inset-0 z-[1000] p-6 flex flex-col">
        <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" onclick="toggleMobileNav()"></div>
        <div class="relative bg-white w-full max-h-[80vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col fadeIn mt-auto">
            <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                <h5 class="text-xs font-semibold text-gray-800 uppercase tracking-widest">Jump to Question</h5>
                <button onclick="toggleMobileNav()" class="size-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400"><i class="bx bx-x text-xl"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 scrollbar-thin">
                <div class="grid grid-cols-5 gap-3" id="mobileNavigatorGrid"></div>
            </div>
        </div>
    </div>

    <!-- ── Floating Calculator ──────────────────────────────────────── -->
    <div id="calcPanel"
        class="hidden fixed z-[500] shadow-2xl shadow-gray-400/20 rounded-[2rem] overflow-hidden select-none"
        style="bottom: 100px; right: 20px; width: 256px;">

        <!-- Header (drag handle) -->
        <div id="calcHeader"
            class="flex items-center justify-between bg-gray-900 px-4 py-2.5 cursor-grab active:cursor-grabbing">
            <div class="flex items-center gap-2">
                <i class="bx bx-calculator text-white text-lg"></i>
                <span class="text-white font-semibold text-sm tracking-wide">Calculator</span>
            </div>
            <button onclick="toggleCalculator()"
                class="size-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors cursor-pointer">
                <i class="bx bx-x text-base"></i>
            </button>
        </div>

        <!-- Display -->
        <div class="bg-gray-800 px-4 pt-3 pb-2">
            <!-- History / expression -->
            <div id="calcHistory" class="text-right text-gray-400 text-xs font-medium min-h-[18px] mb-1 tracking-wider overflow-hidden text-ellipsis whitespace-nowrap"></div>
            <!-- Main display -->
            <div id="calcDisplay"
                class="text-right text-white font-semibold text-3xl tracking-tight leading-none overflow-hidden text-ellipsis whitespace-nowrap">0</div>
        </div>

        <!-- Buttons -->
        <div class="bg-gray-900 p-2 grid grid-cols-4 gap-1.5">
            <?php
            $calcBtns = [
                // Row 1 – utility
                ['MC','calc-mem','Memory Clear'],['MR','calc-mem','Memory Recall'],
                ['M+','calc-mem','Memory Add'],['M-','calc-mem','Memory Sub'],
                // Row 2 – functions
                ['C','calc-clear','Clear All'],['±','calc-fn','Toggle Sign'],
                ['%','calc-fn','Percent'],['÷','calc-op','Divide'],
                // Row 3 – scientific
                ['√','calc-fn','Square Root'],['x²','calc-fn','Square'],
                ['1/x','calc-fn','Reciprocal'],['×','calc-op','Multiply'],
                // Row 4
                ['7','calc-num',''],['8','calc-num',''],['9','calc-num',''],['−','calc-op','Subtract'],
                // Row 5
                ['4','calc-num',''],['5','calc-num',''],['6','calc-num',''],  ['+','calc-op','Add'],
                // Row 6
                ['1','calc-num',''],['2','calc-num',''],['3','calc-num',''],
                ['=','calc-eq calc-span-row-2','Equals'],
                // Row 7
                ['0','calc-num calc-span-col-2',''],  ['.','calc-num','Decimal'],
            ];
            $colorMap = [
                'calc-num'   => 'bg-gray-700 hover:bg-gray-600 text-white',
                'calc-op'    => 'bg-blue-600 hover:bg-blue-500 text-white',
                'calc-fn'    => 'bg-gray-600 hover:bg-gray-500 text-white',
                'calc-mem'   => 'bg-gray-700 hover:bg-gray-600 text-blue-300 text-xs',
                'calc-clear' => 'bg-red-500 hover:bg-red-400 text-white',
                'calc-eq'    => 'bg-green-500 hover:bg-green-400 text-white font-semibold text-xl',
            ];
            foreach($calcBtns as [$label, $classes, $title]):
                // Determine colour from primary class
                $primaryCls = explode(' ', $classes)[0];
                $color = $colorMap[$primaryCls] ?? 'bg-gray-700 text-white';
                $span = strpos($classes, 'calc-span-col-2') !== false ? 'col-span-2' : '';
                $rowSpan = strpos($classes, 'calc-span-row-2') !== false ? 'row-span-2' : '';
            ?>
                <button type="button"
                    class="calc-btn <?= $color ?> <?= $span ?> <?= $rowSpan ?> rounded-xl font-bold text-sm py-2.5 transition-all cursor-pointer flex items-center justify-center active:scale-95"
                    data-val="<?= htmlspecialchars($label) ?>"
                    <?= $title ? 'title="'.$title.'"' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- ── End Calculator ────────────────────────────────────────────── -->


    <div class="h-20 bg-white border-t border-gray-50 px-6 md:px-12 flex items-center justify-between shrink-0 shadow-lg relative z-20">
        <button id="prevBtn" onclick="prevQuestion()" class="flex items-center gap-2 px-6 py-3.5 bg-gray-50 text-gray-400 rounded-2xl font-bold text-xs transition-all border border-gray-100 hover:bg-white hover:text-blue-600 hover:border-blue-200 disabled:opacity-50 disabled:cursor-not-allowed group uppercase tracking-widest">
            <i class="bx bx-chevron-left text-xl group-hover:-translate-x-1 transition-transform"></i> Prev
        </button>
        
        <div class="hidden lg:flex items-center gap-1.5 overflow-x-auto max-w-[40%] px-4" id="paginationDots">
            <!-- Dots added by JS -->
        </div>

        <button id="nextBtn" onclick="nextQuestion()" class="flex items-center gap-2 px-8 py-3.5 bg-blue-600 text-white rounded-2xl font-bold text-xs transition-all hover:bg-blue-700 hover:translate-y-[-2px] shadow-lg shadow-blue-100 group uppercase tracking-widest">
            Next <i class="bx bx-chevron-right text-xl group-hover:translate-x-1 transition-transform"></i>
        </button>
        <button id="finalSubmitBtn" onclick="confirmSubmit()" class="hidden flex items-center gap-2 px-8 py-3.5 bg-green-600 text-white rounded-2xl font-bold text-xs transition-all hover:bg-green-700 hover:translate-y-[-2px] shadow-lg shadow-green-100 group uppercase tracking-widest">
            Submit Exam <i class="bx bx-check-circle text-xl"></i>
        </button>
    </div>
</div>

<script>
(function() {
    const TIMER_KEY = 'exam_timer_' + <?= $exam_id ?>;
    const questions = <?= json_encode($questions) ?>;
    const examDuration = <?= $exam->time_allowed ?> * 60; // in seconds
    let currentIndex = 0;
    let answers = {};
    // Restore timer from localStorage so it survives refresh
    let saved = null;
    try {
        saved = parseInt(localStorage.getItem(TIMER_KEY));
    } catch (e) {
        console.warn('Storage blocked by tracking prevention.');
    }
    let timeLeft = (!isNaN(saved) && saved !== null && saved > 0 && saved <= examDuration) ? saved : examDuration;
    let timerInterval;

    function init() {
        if (questions.length === 0) return;
        renderQuestion();
        startTimer();
        createNavigator();
        updateProgress();
    }

    function renderQuestion() {
        const q = questions[currentIndex];
        $('#displayQN').text(currentIndex + 1);
        $('#qNumWatermark').text((currentIndex + 1).toString().padStart(2, '0'));
        
        let questionHtml = `<h2 class="text-lg md:text-xl font-bold text-gray-800 leading-relaxed mb-6" id="questionText">${q.question_text}</h2>`;
        
        // Show Diagram if exists
        if (q.question_image) {
            questionHtml += `
                <div class="mb-8 bg-gray-50/50 p-2 rounded-3xl border border-gray-100/50 inline-block max-w-full overflow-hidden">
                    <img src="../uploads/questions/${q.question_image}" 
                         class="max-h-[350px] md:max-h-[450px] w-auto rounded-2xl shadow-sm hover:scale-[1.02] cursor-zoom-in transition-transform" 
                         alt="Question Diagram"
                         onclick="window.open(this.src, '_blank')">
                </div>
            `;
        }
        
        $('#questionTextContainer').html(questionHtml);

        const isFill = q.question_type === 'fill_blank';
        let html = '';

        if (isFill) {
            // ── Fill in the Blank input ──────────────────────────────────
            const currentVal = answers[q.id] || '';
            html = `
                <div class="col-span-2 mt-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="px-3 py-1.5 bg-amber-100 rounded-xl">
                            <span class="text-[10px] font-semibold text-amber-600 uppercase tracking-widest">✏️ Fill in the Blank</span>
                        </div>
                    </div>
                    <input type="text" id="fillInput"
                        value="${currentVal.replace(/"/g, '&quot;')}"
                        placeholder="Type your answer here..."
                        class="w-full text-lg font-bold text-gray-800 bg-white border-2 border-gray-200 rounded-2xl px-6 py-5 focus:outline-none focus:border-amber-400 focus:ring-4 focus:ring-amber-50 transition-all placeholder:text-gray-300"
                        oninput="selectFillAnswer('${q.id}', this.value)"
                        autocomplete="off" autocorrect="off" spellcheck="false">
                    <p class="text-xs text-gray-400 font-medium mt-3 text-center">Type your answer in the box above</p>
                </div>`;
            $('#optionsGrid').removeClass('md:grid-cols-2').addClass('grid-cols-1');
        } else {
            // ── MCQ Options ──────────────────────────────────────────────
            const options = [
                { key: 'A', text: q.option_a },
                { key: 'B', text: q.option_b },
                { key: 'C', text: q.option_c },
                { key: 'D', text: q.option_d }
            ];
            options.forEach(opt => {
                const isSelected = answers[q.id] === opt.key;
                html += `
                    <div class="option-item flex items-center gap-4 p-5 rounded-[2rem] border-2 cursor-pointer transition-all duration-300 group
                        ${isSelected ? 'border-green-500 bg-green-50 shadow-lg shadow-green-100' : 'border-gray-50 bg-gray-50/50 hover:border-blue-200 hover:bg-blue-50/30'}"
                        onclick="selectAnswer('${q.id}', '${opt.key}')">
                        <div class="size-10 shrink-0 rounded-2xl flex items-center justify-center font-semibold text-sm transition-all
                            ${isSelected ? 'bg-green-600 text-white shadow-md' : 'bg-white text-gray-400 border border-gray-100 group-hover:border-blue-300 group-hover:text-blue-500 shadow-sm'}">
                            ${opt.key}
                        </div>
                        <span class="text-xs md:text-sm font-bold ${isSelected ? 'text-green-800' : 'text-gray-600'}">
                            ${opt.text}
                        </span>
                        ${isSelected ? '<i class="bx bx-check-circle text-green-600 ml-auto text-2xl fade-in-scale"></i>' : ''}
                    </div>
                `;
            });
            $('#optionsGrid').addClass('md:grid-cols-2').removeClass('grid-cols-1');
        }

        $('#optionsGrid').html(html);

        // Focus fill input automatically
        if (isFill) setTimeout(() => document.getElementById('fillInput')?.focus(), 100);

        // Control buttons
        $('#prevBtn').prop('disabled', currentIndex === 0);
        if (currentIndex === questions.length - 1) {
            $('#nextBtn').addClass('hidden');
            $('#finalSubmitBtn').removeClass('hidden');
        } else {
            $('#nextBtn').removeClass('hidden');
            $('#finalSubmitBtn').addClass('hidden');
        }
        updateNavigator();
    }

    window.selectAnswer = function(qId, option) {
        answers[qId] = option;
        renderQuestion();
        updateProgress();
    };

    window.selectFillAnswer = function(qId, text) {
        // Store the typed text directly (trimming done at submit)
        if (text.trim()) {
            answers[qId] = text;
        } else {
            delete answers[qId];
        }
        updateProgress();
    };

    window.nextQuestion = function() {
        if (currentIndex < questions.length - 1) {
            currentIndex++;
            renderQuestion();
            updateProgress();
        }
    };

    window.prevQuestion = function() {
        if (currentIndex > 0) {
            currentIndex--;
            renderQuestion();
            updateProgress();
        }
    };

    window.jumpToQuestion = function(idx) {
        if (idx >= 0 && idx < questions.length) {
            currentIndex = idx;
            renderQuestion();
            updateProgress();
            $('#mobileNavOverlay').addClass('hidden');
        }
    };

    window.toggleMobileNav = function() {
        $('#mobileNavOverlay').toggleClass('hidden');
    };

    function startTimer() {
        updateTimerDisplay();
        timerInterval = setInterval(() => {
            timeLeft--;
            try {
                localStorage.setItem(TIMER_KEY, timeLeft); // persist across refresh
            } catch (e) {}
            
            updateTimerDisplay();
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                try {
                    localStorage.removeItem(TIMER_KEY);
                } catch (e) {}
                autoSubmit();
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        $('#examTimer').text(`${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`);
        
        if (timeLeft < 300) { // Less than 5 mins
            $('#timerBadge').addClass('bg-red-100 border-red-200').removeClass('bg-red-50');
            $('#examTimer').addClass('scale-110');
        }
    }

    function updateProgress() {
        const answeredCount = Object.keys(answers).length;
        const progress = (answeredCount / questions.length) * 100;
        $('#progressBar').css('width', progress + '%');
        $('#progressText').text(`Q${currentIndex + 1} of ${questions.length}`);
    }

    function createNavigator() {
        let html = '';
        questions.forEach((_, i) => {
            html += `<button onclick="jumpToQuestion(${i})" class="size-10 rounded-xl flex items-center justify-center font-semibold text-xs transition-all border-2 cursor-pointer q-nav-item" id="nav-${i}">${i + 1}</button>`;
        });
        $('#navigatorGrid, #mobileNavigatorGrid').html(html);
        updateNavigator();
    }

    function updateNavigator() {
        questions.forEach((q, i) => {
            const hasAnswer = answers[q.id];
            const isCurrent = currentIndex === i;
            
            // Desktop Navigator
            const btn = $(`#nav-${i}, #mobileNavigatorGrid #nav-${i}`);
            btn.removeClass('bg-gray-50 border-gray-100 text-gray-400 bg-blue-600 border-blue-600 text-white bg-green-500 border-green-500 text-white shadow-lg');
            
            if (isCurrent) {
                btn.addClass('bg-blue-600 border-blue-600 text-white shadow-lg scale-110 relative z-10');
            } else if (hasAnswer) {
                btn.addClass('bg-green-500 border-green-500 text-white');
            } else {
                btn.addClass('bg-gray-50 border-gray-100 text-gray-400 hover:border-blue-200 hover:text-blue-500');
            }
        });

        // Sync original dots hidden under footer
        let dotsHtml = '';
        questions.forEach((_, i) => {
            const hasAns = answers[questions[i].id];
            const isCurr = currentIndex === i;
            dotsHtml += `<div class="size-2 rounded-full transition-all duration-300 ${isCurr ? 'bg-blue-600 scale-150' : (hasAns ? 'bg-green-500' : 'bg-gray-200')}"></div>`;
        });
        $('#paginationDots').html(dotsHtml);
    }

    function autoSubmit() {
        Swal.fire({
            title: 'Time is up!',
            text: 'Your exam is being submitted automatically.',
            icon: 'warning',
            timer: 3000,
            showConfirmButton: false,
            allowOutsideClick: false
        }).then(() => {
            finalSubmitAction();
        });
    }

    window.confirmSubmit = function() {
        const answered = Object.keys(answers).length;
        const total = questions.length;
        
        let msg = 'Are you sure you want to submit your exam now?';
        if (answered < total) {
            msg = `You have only answered ${answered} out of ${total} questions. Are you sure you want to submit?`;
        }

        Swal.fire({
            title: 'Finish Exam?',
            text: msg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#4b5563',
            confirmButtonText: 'Yes, Submit Now',
            cancelButtonText: 'Keep Going'
        }).then((result) => {
            if (result.isConfirmed) {
                finalSubmitAction();
            }
        });
    };

    window.finalSubmitAction = function() {
        clearInterval(timerInterval);
        // Clear persistence
        try {
            localStorage.removeItem(TIMER_KEY);
        } catch (e) {}
        
        $('#mainContent').fadeOut(300, function() {
            $(this).html('<div class="h-screen flex flex-col items-center justify-center p-8 text-center">' +
                '<div class="size-24 border-8 border-green-100 border-t-green-600 rounded-full animate-spin mb-8"></div>' +
                '<h2 class="text-3xl font-semibold text-gray-800 mb-2">Submitting Exam...</h2>' +
                '<p class="text-gray-500 font-medium">Please wait while we calculate your result.</p>' +
                '</div>').fadeIn(300);

            $.ajax({
                url: 'auth/submit_exam.php',
                type: 'POST',
                data: { 
                    exam_id: <?= $exam_id ?>,
                    answers: JSON.stringify(answers)
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        disableProctoring(); // ← Kill ALL anti-cheat listeners now that exam is done
                        showSuccessScreen(res);
                    } else {
                        Swal.fire('Error', res.message || 'Submission failed.', 'error').then(() => {
                            location.reload();
                        });
                    }
                },
                error: function() {
                    Swal.fire('Connection Error', 'Failed to reach the server. Please check your connection.', 'error');
                }
            });
        });
    }

    function showSuccessScreen(res) {
        // Redirect to result view or show a nice summary
        viewResult(<?= $exam_id ?>);
    }

    init();
})();

// \u2500\u2500 Calculator \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
(function() {
    let display    = '0';
    let expression = '';
    let operand    = null;
    let operator   = null;
    let justCalc   = false;
    let memory     = 0;

    const $disp = document.getElementById('calcDisplay');
    const $hist = document.getElementById('calcHistory');

    function updateDisplay() {
        // Shorten very long numbers
        const num = parseFloat(display);
        $disp.textContent = isNaN(num) ? display : (Math.abs(num) > 1e12 ? num.toExponential(4) : display);
        $hist.textContent = expression;
    }

    function calcInput(val) {
        switch(val) {
            /* \u2500 Clear \u2500 */
            case 'C':
                display = '0'; expression = ''; operand = null; operator = null; justCalc = false;
                break;

            /* \u2500 Digits \u2500 */
            case '0': case '1': case '2': case '3': case '4':
            case '5': case '6': case '7': case '8': case '9':
                if (justCalc) { display = val; expression = ''; justCalc = false; }
                else display = (display === '0' ? val : display + val);
                break;

            /* \u2500 Decimal \u2500 */
            case '.':
                if (justCalc) { display = '0.'; justCalc = false; break; }
                if (!display.includes('.')) display += '.';
                break;

            /* \u2500 Operators \u2500 */
            case '+': case '\u2212': case '\u00d7': case '\u00f7':
                if (operator && !justCalc) {
                    display = String(calculate(parseFloat(operand), parseFloat(display), operator));
                    expression = display + ' ' + val;
                } else {
                    expression = display + ' ' + val;
                }
                operand  = display;
                operator = val;
                justCalc = false;
                display  = '0';
                break;

            /* \u2500 Equals \u2500 */
            case '=':
                if (operator === null) break;
                const result = calculate(parseFloat(operand), parseFloat(display), operator);
                expression = operand + ' ' + operator + ' ' + display + ' =';
                display    = String(result);
                operand    = null; operator = null; justCalc = true;
                break;

            /* \u2500 Scientific \u2500 */
            case '\u00b1':
                display = String(parseFloat(display) * -1);
                break;
            case '%':
                display = String(parseFloat(display) / 100);
                break;
            case '\u221a':
                expression = '\u221a(' + display + ')';
                display = String(Math.sqrt(parseFloat(display)));
                justCalc = true;
                break;
            case 'x\u00b2':
                expression = '(' + display + ')\u00b2';
                display = String(Math.pow(parseFloat(display), 2));
                justCalc = true;
                break;
            case '1/x':
                expression = '1/(' + display + ')';
                display = String(1 / parseFloat(display));
                justCalc = true;
                break;

            /* \u2500 Memory \u2500 */
            case 'MC': memory = 0; break;
            case 'MR': display = String(memory); break;
            case 'M+': memory += parseFloat(display); break;
            case 'M-': memory -= parseFloat(display); break;
        }
        updateDisplay();
    }

    function calculate(a, b, op) {
        if (op === '+') return parseFloat((a + b).toPrecision(12));
        if (op === '\u2212') return parseFloat((a - b).toPrecision(12));
        if (op === '\u00d7') return parseFloat((a * b).toPrecision(12));
        if (op === '\u00f7') return b === 0 ? 'Error' : parseFloat((a / b).toPrecision(12));
        return b;
    }

    // Button clicks
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.calc-btn');
        if (btn) { calcInput(btn.dataset.val); e.preventDefault(); }
    });

    // Keyboard support (only when calc is open)
    document.addEventListener('keydown', function(e) {
        const panel = document.getElementById('calcPanel');
        if (panel.classList.contains('hidden')) return;
        const keyMap = {
            '0':'0','1':'1','2':'2','3':'3','4':'4','5':'5','6':'6','7':'7','8':'8','9':'9',
            '.':'.','Enter':'=','=':'=','Escape':'C','Backspace':'C',
            '+':'+','-':'\u2212','*':'\u00d7','/':'\u00f7','%':'%'
        };
        if (keyMap[e.key]) { calcInput(keyMap[e.key]); e.preventDefault(); }
    });

    // Toggle show/hide
    window.toggleCalculator = function() {
        const panel = document.getElementById('calcPanel');
        panel.classList.toggle('hidden');
        // Active state on button
        const btn = document.getElementById('calcToggleBtn');
        btn.classList.toggle('bg-blue-600');
        btn.classList.toggle('text-white');
        btn.classList.toggle('border-blue-600');
    };

    // \u2500 Drag to move \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
    const panel  = document.getElementById('calcPanel');
    const handle = document.getElementById('calcHeader');
    let dragging = false, ox = 0, oy = 0;

    handle.addEventListener('mousedown', function(e) {
        dragging = true;
        const rect = panel.getBoundingClientRect();
        ox = e.clientX - rect.left;
        oy = e.clientY - rect.top;
        // Switch from bottom/right anchoring to top/left so we can move freely
        panel.style.bottom = 'auto';
        panel.style.right  = 'auto';
        panel.style.top    = rect.top + 'px';
        panel.style.left   = rect.left + 'px';
        handle.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        panel.style.left = (e.clientX - ox) + 'px';
        panel.style.top  = (e.clientY - oy) + 'px';
    });

    document.addEventListener('mouseup', function() {
        dragging = false;
        handle.style.cursor = 'grab';
    });
})();
// \u2500\u2500 End Calculator \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
// ── End Calculator ──────────────────────────────────────────

// ── Live Proctoring (Anti-Cheat) ──────────────────────────
let cheatingWarnings = 0;
const MAX_WARNINGS = 3;
const currentExamId = <?= $exam_id ?>;
let isSubmittingExam = false;
let isAlertOpen = false;
let examDone = false; // Set to true after successful submission — stops all proctoring

// Call this once exam is successfully submitted to kill ALL proctoring
function disableProctoring() {
    examDone = true;
    document.removeEventListener('visibilitychange', _visibilityHandler);
    window.removeEventListener('blur', _blurHandler);
    document.removeEventListener('contextmenu', _ctxHandler);
    document.removeEventListener('copy',  _copyHandler);
    document.removeEventListener('cut',   _cutHandler);
    document.removeEventListener('paste', _pasteHandler);
    document.removeEventListener('keydown', _keydownHandler);
}

function logProctorAlert(reason) {
    if (isSubmittingExam || isAlertOpen) return;
    isAlertOpen = true;
    cheatingWarnings++;
    
    // Log secretly to backend
    $.post('../student/auth/proctoring_api.php', { 
        action: 'log_alert', 
        exam_id: currentExamId,
        reason: reason
    });

    if (cheatingWarnings >= MAX_WARNINGS) {
        isSubmittingExam = true;
        Swal.fire({
            title: 'EXAM TERMINATED',
            text: 'Multiple proctoring violations detected. Your exam has been automatically submitted.',
            icon: 'error',
            allowOutsideClick: false,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Exit'
        }).then(() => {
            if (typeof window.finalSubmitAction === 'function') {
                  window.finalSubmitAction(); // Force submit directly
            }
        });
    } else {
        Swal.fire({
            title: 'Proctoring Alert!',
            text: `Violation: ${reason}. Please remain on this screen. Attempt ${cheatingWarnings} of ${MAX_WARNINGS}.`,
            icon: 'warning',
            allowOutsideClick: false,
            confirmButtonColor: '#f59e0b'
        }).then(() => {
            setTimeout(() => { isAlertOpen = false; }, 500);
        });
    }
}

const _visibilityHandler = function() {
    if (document.hidden && !isSubmittingExam && !isAlertOpen && !examDone) {
        logProctorAlert("Switched browser tab or minimized window");
    }
};
document.addEventListener("visibilitychange", _visibilityHandler);

const _blurHandler = function() {
    if (!isSubmittingExam && !isAlertOpen && !examDone) {
        logProctorAlert("Clicked outside exam window or used another application");
    }
};
window.addEventListener("blur", _blurHandler);

window.addEventListener("focus", function() {
    // Just re-gaining focus, ignore
});

// Disable Right Click
const _ctxHandler  = event => event.preventDefault();
const _copyHandler  = event => event.preventDefault();
const _cutHandler   = event => event.preventDefault();
const _pasteHandler = event => event.preventDefault();
document.addEventListener('contextmenu', _ctxHandler);

// Disable Copy, Cut, Paste
document.addEventListener('copy',  _copyHandler);
document.addEventListener('cut',   _cutHandler);
document.addEventListener('paste', _pasteHandler);

// Disable specific keyboard shortcuts (F12, Ctrl+Shift+I, Ctrl+C, Ctrl+V, etc.)
const _keydownHandler = function(e) {
    if (examDone) return; // Exam finished — allow normal keyboard usage
    if (
        e.keyCode === 123 || // F12
        (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) || // Ctrl+Shift+I/J
        (e.ctrlKey && e.keyCode === 85) || // Ctrl+U
        (e.ctrlKey && (e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88)) // Ctrl+C/V/X
    ) {
        e.preventDefault();
        logProctorAlert("Attempted to use prohibited keyboard shortcut");
    }
};
document.addEventListener('keydown', _keydownHandler);

// ── End Proctoring ──────────────────────────────────────────

</script>
