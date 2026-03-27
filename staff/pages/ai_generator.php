<?php
require '../../connections/db.php';
require '../../auth/check.php';
/** @var stdClass $user */
// Only staff can access this page
if ($user->role !== 'staff') {
    exit('Unauthorized access.');
}

// Fetch all staff exams that are in 'set up' status for the active term
$teacher_name = $user->first_name . ' ' . $user->surname;
$active_session = $_SESSION['active_session'] ?? '';
$active_term    = $_SESSION['active_term'] ?? '';
$stmt = $conn->prepare("SELECT id, subject, exam_type, class FROM exams WHERE subject_teacher = :teacher AND exam_status = 'set up' AND session = :session AND term = :term ORDER BY id DESC");
$stmt->execute([':teacher' => $teacher_name, ':session' => $active_session, ':term' => $active_term]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-xl shadow-purple-200 flex items-center justify-center">
                <i class="bx bx-robot text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">AI Assistant</h1>
                <p class="text-sm text-gray-500 font-medium">Harness the power of Artificial Intelligence to draft exams and lesson plans.</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
             <button id="navQuestionGen" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-purple-50 text-purple-700 border-2 border-purple-200 shadow-sm transition-all focus:outline-none">
                Question Generator
            </button>
            <button id="navLessonGen" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-white text-gray-500 border-2 border-transparent hover:bg-gray-50 transition-all focus:outline-none">
                Lesson Plan Idea
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8" id="questionGenSection">
        
        <!-- Sidebar Controls -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-6 flex flex-col gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <i class="bx bx-slider-alt text-purple-500"></i> Generator Settings
                    </h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Setup constraints</p>
                </div>

                <div class="space-y-4">
                    <!-- Exam Selection -->
                    <div class="flex flex-col gap-1.5 focus-within:text-purple-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest flex justify-between">
                            Target Exam <span class="text-red-500">*</span>
                        </label>
                        <select id="aiExamSelect" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition-all appearance-none cursor-pointer">
                            <option value="">-- Choose Exam (Set up mode) --</option>
                            <?php foreach($exams as $ex): ?>
                                <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['subject']) ?> (<?= $ex['class'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Number of Questions -->
                    <div class="flex flex-col gap-1.5 focus-within:text-purple-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest flex justify-between">
                            Number of Questions <span id="numQVal" class="text-purple-600">5</span>
                        </label>
                        <input type="range" id="numQuestions" min="1" max="50" value="5" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-purple-600">
                    </div>

                    <!-- Difficulty -->
                    <div class="flex flex-col gap-1.5 focus-within:text-purple-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest">Difficulty</label>
                        <select id="aiDifficulty" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition-all cursor-pointer">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>

                    <!-- Topic / Context -->
                    <div class="flex flex-col gap-1.5 focus-within:text-purple-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest">Topic or Text Context <span class="text-red-500">*</span></label>
                        <textarea id="aiTopic" rows="5" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition-all resize-none" placeholder="E.g. Paste a paragraph of text, or type 'Photosynthesis process, basic terms and equations'"></textarea>
                    </div>
                </div>

                <hr class="border-gray-100">

                <button id="generateAiBtn" class="w-full relative group overflow-hidden bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4 rounded-xl font-semibold text-xs uppercase tracking-widest shadow-lg shadow-purple-200 hover:-translate-y-1 active:translate-y-0 transition-all duration-300">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        <i class="bx bx-sparkles text-lg"></i> Generate Questions
                    </span>
                    <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                </button>
            </div>
        </div>

        <!-- Output / Preview Area -->
        <div class="lg:col-span-8 flex flex-col">
            <div class="flex-1 bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-6 md:p-8 flex flex-col relative min-h-[500px]">
                
                <!-- Empty State -->
                <div id="aiEmptyState" class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-gray-50/50 rounded-[2rem] border-2 border-dashed border-gray-200 m-4 z-10">
                    <div class="size-24 rounded-full bg-purple-100 mb-6 flex items-center justify-center text-purple-600 relative">
                        <i class="bx bx-brain text-5xl"></i>
                        <span class="absolute top-0 right-0 size-4 rounded-full bg-green-400 border-2 border-white animate-ping"></span>
                        <span class="absolute top-0 right-0 size-4 rounded-full bg-green-400 border-2 border-white"></span>
                    </div>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">AI Output Console</h2>
                    <p class="text-sm text-gray-500 max-w-sm">Configure your settings on the left pane and hit Generate to see AI-drafted Multiple Choice Questions here.</p>
                </div>

                <!-- Loading State -->
                <div id="aiLoadingState" class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-white/90 backdrop-blur-sm rounded-[2rem] z-20 hidden">
                    <div class="relative size-24 mb-6">
                        <div class="absolute inset-0 border-4 border-gray-100 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-purple-600 rounded-full border-t-transparent animate-spin"></div>
                        <i class="bx bx-bot absolute inset-0 flex items-center justify-center text-3xl text-purple-600 animate-pulse"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Generating Brilliance...</h2>
                    <p class="text-sm text-gray-500">Parsing context and crafting academic questions.</p>
                </div>

                <!-- Results Container -->
                <div id="aiResultsContainer" class="hidden flex-col h-full z-10">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800"><i class="bx bx-check-double text-green-500 mr-2"></i>Generated Drafts</h3>
                        <button id="saveAllAiBtn" class="bg-gray-900 text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-black transition-all shadow-md flex items-center gap-2">
                            <i class="bx bx-save"></i> Save All to Exam
                        </button>
                    </div>

                    <div id="questionsList" class="flex-1 overflow-y-auto pr-2 space-y-6 custom-scrollbar pb-10">
                        <!-- Ajax populated questions go here -->
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Lesson Gen Section -->
    <div id="lessonGenSection" class="hidden grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Controls -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-6 flex flex-col gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <i class="bx bx-book-bookmark text-amber-500"></i> Lesson Settings
                    </h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Setup constraints</p>
                </div>

                <div class="space-y-4">
                    <!-- Class / Grade Level -->
                    <div class="flex flex-col gap-1.5 focus-within:text-amber-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest flex justify-between">
                            Grade / Class Level <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="aiLessonClass" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all" placeholder="E.g. JSS2 or Grade 8">
                    </div>

                    <!-- Subject -->
                    <div class="flex flex-col gap-1.5 focus-within:text-amber-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest flex justify-between">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="aiLessonSubject" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all" placeholder="E.g. Mathematics">
                    </div>

                    <!-- Topic -->
                    <div class="flex flex-col gap-1.5 focus-within:text-amber-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest flex justify-between">
                            Topic <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="aiLessonTopic" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all" placeholder="E.g. Linear Equations">
                    </div>

                    <!-- Duration -->
                    <div class="flex flex-col gap-1.5 focus-within:text-amber-600 transition-colors">
                        <label class="text-xs font-semibold text-inherit uppercase tracking-widest flex justify-between">
                            Duration (Minutes)
                        </label>
                        <input type="number" id="aiLessonDuration" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all" value="45">
                    </div>
                </div>

                <hr class="border-gray-100">

                <button id="generateLessonBtn" class="w-full relative group overflow-hidden bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-4 rounded-xl font-semibold text-xs uppercase tracking-widest shadow-lg shadow-amber-200 hover:-translate-y-1 active:translate-y-0 transition-all duration-300">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        <i class="bx bx-sparkles text-lg"></i> Draft Lesson Plan
                    </span>
                    <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                </button>
            </div>
        </div>

        <!-- Output -->
        <div class="lg:col-span-8 flex flex-col">
            <div class="flex-1 bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-6 md:p-8 flex flex-col relative min-h-[500px]">
                
                <div id="aiLessonEmptyState" class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-gray-50/50 rounded-[2rem] border-2 border-dashed border-gray-200 m-4 z-10">
                    <div class="size-24 rounded-full bg-amber-100 mb-6 flex items-center justify-center text-amber-600 relative">
                        <i class="bx bx-book-content text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Lesson Draft Console</h2>
                    <p class="text-sm text-gray-500 max-w-sm">Configure your subject and topic and let AI draft a comprehensive lesson plan.</p>
                </div>

                <div id="aiLessonLoadingState" class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-white/90 backdrop-blur-sm rounded-[2rem] z-20 hidden">
                    <div class="relative size-24 mb-6">
                        <div class="absolute inset-0 border-4 border-gray-100 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-amber-500 rounded-full border-t-transparent animate-spin"></div>
                        <i class="bx bx-bot absolute inset-0 flex items-center justify-center text-3xl text-amber-500 animate-pulse"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Structuring Lesson Plan...</h2>
                    <p class="text-sm text-gray-500">Creating learning objectives, activities, and resources.</p>
                </div>

                <div id="aiLessonResultsContainer" class="hidden flex-col h-full z-10">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800"><i class="bx bx-check-double text-green-500 mr-2"></i>Generated Plan</h3>
                        <div class="flex gap-2">
                            <button onclick="copyLessonPlan()" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-xs font-bold transition-all hover:bg-gray-200 flex items-center gap-2">
                                <i class="bx bx-copy"></i>
                            </button>
                            <button onclick="downloadLessonPlan()" class="bg-gray-900 text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-black transition-all shadow-md flex items-center gap-2">
                                <i class="bx bx-download"></i> Download Txt
                            </button>
                        </div>
                    </div>

                    <div id="lessonPlanText" class="flex-1 p-6 bg-gray-50 rounded-2xl border border-gray-100 font-medium text-gray-700 text-sm whitespace-pre-wrap overflow-y-auto">
                        <!-- AI content here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab switching
    $('#navQuestionGen').on('click', function() {
        $(this).removeClass('bg-white text-gray-500 border-transparent hover:bg-gray-50').addClass('bg-purple-50 text-purple-700 border-purple-200');
        $('#navLessonGen').removeClass('bg-purple-50 text-purple-700 border-purple-200').addClass('bg-white text-gray-500 border-transparent hover:bg-gray-50');
        $('#questionGenSection').removeClass('hidden');
        $('#lessonGenSection').addClass('hidden');
    });
    $('#navLessonGen').on('click', function() {
        $(this).removeClass('bg-white text-gray-500 border-transparent hover:bg-gray-50').addClass('bg-purple-50 text-purple-700 border-purple-200');
        $('#navQuestionGen').removeClass('bg-purple-50 text-purple-700 border-purple-200').addClass('bg-white text-gray-500 border-transparent hover:bg-gray-50');
        $('#lessonGenSection').removeClass('hidden');
        $('#questionGenSection').addClass('hidden');
    });

    // Range slider value
    $('#numQuestions').on('input', function() {
        $('#numQVal').text($(this).val());
    });

    let generatedQuestionsData = [];

    // Form submission parsing
    $('#generateAiBtn').on('click', function() {
        const examId = $('#aiExamSelect').val();
        const numQ = $('#numQuestions').val();
        const diff = $('#aiDifficulty').val();
        const topic = $('#aiTopic').val().trim();

        if(!examId) return window.showToast('Please select a Target Exam first.', 'error');
        if(!topic) return window.showToast('Please define a Topic or Context.', 'error');

        $('#aiEmptyState').addClass('hidden');
        $('#aiResultsContainer').addClass('hidden');
        $('#aiLoadingState').removeClass('hidden');

        // Request generation
        $.ajax({
            url: BASE_URL + 'staff/auth/ai_generator_api.php',
            method: 'POST',
            data: {
                action: 'generate',
                num_questions: numQ,
                difficulty: diff,
                topic: topic,
                exam_id: examId
            },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    generatedQuestionsData = res.questions;
                    renderGeneratedQuestions(res.questions);
                    $('#aiLoadingState').addClass('hidden');
                    $('#aiResultsContainer').removeClass('hidden').addClass('flex');
                    window.triggerHaptic && window.triggerHaptic(50);
                } else {
                    window.showToast(res.message, 'error');
                    $('#aiLoadingState').addClass('hidden');
                    $('#aiEmptyState').removeClass('hidden');
                }
            },
            error: function() {
                window.showToast('Network timeout. Please retry.', 'error');
                $('#aiLoadingState').addClass('hidden');
                $('#aiEmptyState').removeClass('hidden');
            }
        });
    });

    function renderGeneratedQuestions(questions) {
        let html = '';
        questions.forEach((q, index) => {
            html += `
            <div class="p-6 bg-gray-50 rounded-2xl border border-gray-200 relative group">
                <div class="absolute top-4 right-4 bg-white text-xs font-bold px-3 py-1 rounded-full text-purple-600 border border-purple-100 shadow-sm shadow-purple-100">
                    Q${index + 1}
                </div>
                <h4 class="text-sm font-bold text-gray-800 mb-4 pr-12 leading-relaxed">${q.question_text}</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="flex items-start gap-3 p-3 rounded-xl border ${q.correct_answer === 'A' ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'}">
                        <span class="size-6 shrink-0 rounded bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-500">A</span>
                        <p class="text-sm text-gray-700 font-medium">${q.option_a}</p>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl border ${q.correct_answer === 'B' ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'}">
                        <span class="size-6 shrink-0 rounded bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-500">B</span>
                        <p class="text-sm text-gray-700 font-medium">${q.option_b}</p>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl border ${q.correct_answer === 'C' ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'}">
                        <span class="size-6 shrink-0 rounded bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-500">C</span>
                        <p class="text-sm text-gray-700 font-medium">${q.option_c}</p>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl border ${q.correct_answer === 'D' ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'}">
                        <span class="size-6 shrink-0 rounded bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-500">D</span>
                        <p class="text-sm text-gray-700 font-medium">${q.option_d}</p>
                    </div>
                </div>
            </div>`;
        });
        $('#questionsList').html(html);
    }

    // Save All to chosen Exam
    $('#saveAllAiBtn').on('click', function() {
        const examId = $('#aiExamSelect').val();
        if(!examId || generatedQuestionsData.length === 0) return;

        const btn = $(this);
        const ogText = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin"></i> Saving...').prop('disabled', true);

        // We will send the whole array to bulk insert endpoint
        $.ajax({
            url: BASE_URL + 'staff/auth/ai_generator_api.php',
            method: 'POST',
            data: {
                action: 'save_bulk',
                exam_id: examId,
                questions: JSON.stringify(generatedQuestionsData)
            },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    window.showToast(`Saved ${res.inserted} questions successfully!`, 'success');
                    // Reset UI
                    $('#aiResultsContainer').addClass('hidden');
                    $('#aiEmptyState').removeClass('hidden');
                    generatedQuestionsData = [];
                } else {
                    window.showToast(res.message, 'error');
                }
            },
            error: function() {
                window.showToast('Failed to save to database.', 'error');
            },
            complete: function() {
                btn.html(ogText).prop('disabled', false);
            }
        });
    });

    // ──── LESSON PLAN GENERATOR ────
    $('#generateLessonBtn').on('click', function() {
        const tClass = $('#aiLessonClass').val().trim();
        const tSubject = $('#aiLessonSubject').val().trim();
        const tTopic = $('#aiLessonTopic').val().trim();
        const tDuration = $('#aiLessonDuration').val().trim();

        if(!tClass || !tSubject || !tTopic) {
            return window.showToast('Please fill out Class, Subject, and Topic.', 'error');
        }

        $('#aiLessonEmptyState').addClass('hidden');
        $('#aiLessonResultsContainer').addClass('hidden');
        $('#aiLessonLoadingState').removeClass('hidden');

        $.ajax({
            url: BASE_URL + 'staff/auth/ai_generator_api.php',
            method: 'POST',
            data: {
                action: 'generate_lesson',
                class: tClass,
                subject: tSubject,
                topic: tTopic,
                duration: tDuration
            },
            dataType: 'json',
            success: function(res) {
                $('#aiLessonLoadingState').addClass('hidden');
                
                if(res.status === 'success') {
                    $('#lessonPlanText').text(res.content);
                    $('#aiLessonResultsContainer').removeClass('hidden').addClass('flex');
                    window.showToast('Lesson draft generated successfully!', 'success');
                } else {
                    $('#aiLessonEmptyState').removeClass('hidden');
                    window.showToast(res.message, 'error');
                }
            },
            error: function() {
                $('#aiLessonLoadingState').addClass('hidden');
                $('#aiLessonEmptyState').removeClass('hidden');
                window.showToast("Network Error.", 'error');
            }
        });
    });

    window.copyLessonPlan = function() {
        const txt = $('#lessonPlanText').text();
        navigator.clipboard.writeText(txt).then(() => {
            window.showToast("Copied to clipboard!", "success");
        });
    };

    window.downloadLessonPlan = function() {
        const txt = $('#lessonPlanText').text();
        const topic = $('#aiLessonTopic').val().trim().replace(/[^a-z0-9]/gi, '_').toLowerCase();
        const blob = new Blob([txt], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `LessonPlan_${topic}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

</script>
