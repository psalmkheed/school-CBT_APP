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
                  class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all cursor-pointer"
                  data-tippy-content="Go Back">
                  <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div>
                <h3 class="text-lg md:text-2xl font-bold text-gray-800">Set Questions</h3>
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
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Question Text</label>

                        <!-- ── Formatting Toolbar ─────────────────────────────── -->
                        <div class="border border-gray-100 rounded-t-2xl overflow-hidden border-b-0">

                            <!-- Row 1: Always-visible format buttons + tab switchers -->
                            <div class="flex items-center gap-0 bg-gray-50 border-b border-gray-100">

                                <!-- Format buttons (always visible) -->
                                <div class="flex items-center gap-1 px-3 py-2 border-r border-gray-200 shrink-0">
                                    <button type="button" data-fmt="bold"
                                        class="fmt-btn size-8 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm text-gray-500 font-black text-sm transition-all cursor-pointer flex items-center justify-center"
                                        title="Bold"><b>B</b></button>
                                    <button type="button" data-fmt="italic"
                                        class="fmt-btn size-8 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm text-gray-500 text-sm transition-all cursor-pointer flex items-center justify-center italic font-semibold"
                                        title="Italic">I</button>
                                    <button type="button" data-fmt="sup"
                                        class="fmt-btn px-2.5 h-8 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm text-gray-500 text-xs font-bold transition-all cursor-pointer flex items-center justify-center"
                                        title="Superscript — wrap selected text in &lt;sup&gt;">x<sup class="text-[8px] font-black">2</sup></button>
                                    <button type="button" data-fmt="sub"
                                        class="fmt-btn px-2.5 h-8 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm text-gray-500 text-xs font-bold transition-all cursor-pointer flex items-center justify-center"
                                        title="Subscript — wrap selected text in &lt;sub&gt;">x<sub class="text-[8px] font-black">2</sub></button>
                                </div>

                                <!-- Category tabs -->
                                <div class="flex items-center overflow-x-auto" id="symTabs">
                                    <?php
                                    $tabs = ['Math','Sup','Sub','Frac','Sets','Greek','Chem'];
                                    $tabColors = [
                                        'Math' => 'blue','Sup' => 'violet','Sub' => 'green',
                                        'Frac' => 'orange','Sets' => 'indigo','Greek' => 'purple','Chem' => 'teal'
                                    ];
                                    foreach($tabs as $i => $tab): ?>
                                        <button type="button"
                                            class="sym-tab shrink-0 px-3 py-2.5 text-[11px] font-black uppercase tracking-wider transition-all cursor-pointer border-b-2 <?= $i === 0 ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-400 hover:text-gray-600 hover:bg-gray-100/60' ?>"
                                            data-tab="<?= $tab ?>">
                                            <?= $tab ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Row 2: Symbol grid (switches per tab) -->
                            <div class="bg-white px-3 py-2.5 min-h-[52px]" id="symGrid">
                                <?php
                                $allSymbols = [
                                    'Math' => [
                                        '×'=>'Multiply','÷'=>'Divide','±'=>'Plus-Minus','√'=>'Square Root',
                                        '∛'=>'Cube Root','∜'=>'4th Root','π'=>'Pi','∞'=>'Infinity','°'=>'Degree',
                                        '≤'=>'Less or Equal','≥'=>'Greater or Equal','≠'=>'Not Equal',
                                        '≈'=>'Approximately','≡'=>'Identical','∝'=>'Proportional To',
                                        '∴'=>'Therefore','∵'=>'Because','∑'=>'Summation',
                                        '∫'=>'Integral','∂'=>'Partial Derivative','∇'=>'Nabla/Del',
                                        '⊥'=>'Perpendicular','∥'=>'Parallel','∠'=>'Angle',
                                        '%'=>'Percent','‰'=>'Per Mille',
                                    ],
                                    'Sup' => [
                                        '⁰'=>'⁰','¹'=>'¹','²'=>'²','³'=>'³','⁴'=>'⁴',
                                        '⁵'=>'⁵','⁶'=>'⁶','⁷'=>'⁷','⁸'=>'⁸','⁹'=>'⁹',
                                        'ⁿ'=>'ⁿ','ˣ'=>'ˣ','⁺'=>'Sup +','⁻'=>'Sup −',
                                        '⁼'=>'Sup =','⁽'=>'Sup (','⁾'=>'Sup )',
                                    ],
                                    'Sub' => [
                                        '₀'=>'₀','₁'=>'₁','₂'=>'₂','₃'=>'₃','₄'=>'₄',
                                        '₅'=>'₅','₆'=>'₆','₇'=>'₇','₈'=>'₈','₉'=>'₉',
                                        '₊'=>'Sub +','₋'=>'Sub −','₌'=>'Sub =',
                                        '₍'=>'Sub (','₎'=>'Sub )','ₙ'=>'Sub n',
                                    ],
                                    'Frac' => [
                                        '½'=>'Half','⅓'=>'⅓','⅔'=>'⅔','¼'=>'¼','¾'=>'¾',
                                        '⅕'=>'⅕','⅖'=>'⅖','⅗'=>'⅗','⅘'=>'⅘',
                                        '⅙'=>'⅙','⅚'=>'⅚','⅛'=>'⅛','⅜'=>'⅜','⅝'=>'⅝','⅞'=>'⅞',
                                    ],
                                    'Sets' => [
                                        '∈'=>'Element of','∉'=>'Not element of','∪'=>'Union','∩'=>'Intersection',
                                        '⊂'=>'Subset','⊃'=>'Superset','⊆'=>'Subset or Equal','⊇'=>'Superset or Equal',
                                        '∅'=>'Empty Set','ℝ'=>'Real ℝ','ℤ'=>'Integers ℤ','ℕ'=>'Natural ℕ',
                                        'ℚ'=>'Rational ℚ','ℂ'=>'Complex ℂ','∧'=>'AND','∨'=>'OR',
                                        '¬'=>'NOT','⇒'=>'Implies','⇔'=>'Iff',
                                    ],
                                    'Greek' => [
                                        'α'=>'alpha','β'=>'beta','γ'=>'gamma','δ'=>'delta','ε'=>'epsilon',
                                        'ζ'=>'zeta','η'=>'eta','θ'=>'theta','ι'=>'iota','κ'=>'kappa',
                                        'λ'=>'lambda','μ'=>'mu','ν'=>'nu','ξ'=>'xi','ο'=>'omicron',
                                        'π'=>'pi','ρ'=>'rho','σ'=>'sigma','τ'=>'tau','υ'=>'upsilon',
                                        'φ'=>'phi','χ'=>'chi','ψ'=>'psi','ω'=>'omega',
                                        'Γ'=>'Gamma','Δ'=>'Delta','Θ'=>'Theta','Λ'=>'Lambda','Ξ'=>'Xi',
                                        'Π'=>'Pi','Σ'=>'Sigma','Υ'=>'Upsilon','Φ'=>'Phi','Ψ'=>'Psi','Ω'=>'Omega',
                                    ],
                                    'Chem' => [
                                        '→'=>'Forward','←'=>'Reverse','⇌'=>'Equilibrium','⇒'=>'Produces',
                                        '↑'=>'Gas (↑)','↓'=>'Precipitate (↓)','∆'=>'Heat Δ','⊕'=>'⊕',
                                        '⁺'=>'⁺ charge','⁻'=>'⁻ charge','²⁺'=>'2+','³⁺'=>'3+',
                                        '²⁻'=>'2−','³⁻'=>'3−','°C'=>'°C','°F'=>'°F',
                                        'ℏ'=>'ℏ h-bar','Å'=>'Å Angstrom',
                                    ],
                                ];
                                foreach($allSymbols as $tabName => $symbols): ?>
                                    <div class="sym-panel <?= $tabName !== 'Math' ? 'hidden' : '' ?> flex flex-wrap gap-1" data-panel="<?= $tabName ?>">
                                        <?php foreach($symbols as $sym => $title): ?>
                                            <button type="button" data-sym="<?= htmlspecialchars($sym) ?>"
                                                class="sym-btn h-8 min-w-[2rem] px-1.5 rounded-lg border border-gray-100 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 bg-gray-50 text-gray-700 text-sm font-bold transition-all cursor-pointer flex items-center justify-center"
                                                title="<?= htmlspecialchars($title) ?>"><?= $sym ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Preview toggle -->
                        <div class="flex items-center gap-2 bg-gray-50/50 border border-gray-100 border-t-0 border-b-0 px-4 py-1.5">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" id="previewToggle" class="rounded accent-blue-600">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Live Preview</span>
                            </label>
                        </div>

                        <!-- Question Textarea -->
                        <textarea name="question_text" id="question_text" required
                            class="w-full bg-gray-50 border border-gray-100 rounded-b-2xl rounded-t-none p-5 text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-400 focus:bg-white transition-all min-h-[120px] placeholder:text-gray-300 font-mono text-sm"
                            placeholder="Type question here... select text then click B/I/x²/x₂, or click a symbol tab to insert characters."></textarea>

                        <!-- ── Diagram / Shape Attachment ─────────────────── -->
                        <div class="mt-4 p-4 border-2 border-dashed border-gray-100 rounded-2xl bg-gray-50/50">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <i class="bx bx-shape-square text-blue-500 text-lg"></i>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Diagram / Shape (Optional)</span>
                                </div>
                                <button type="button" onclick="$('#question_image_input').click()"
                                    class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:text-blue-700 cursor-pointer flex items-center gap-1 bg-white px-3 py-1.5 rounded-lg shadow-sm border border-blue-50">
                                    <i class="bx bx-cloud-upload"></i> Upload Image
                                </button>
                                <input type="file" name="question_image" id="question_image_input" class="hidden" accept="image/*" onchange="previewDiagram(this)">
                            </div>

                            <!-- Image Preview Area -->
                            <div id="diagramPreview" class="hidden relative rounded-xl overflow-hidden border border-gray-200 bg-white inline-block">
                                <img src="" id="diagram_img" class="max-h-[200px] w-auto block">
                                <button type="button" onclick="removeDiagram()"
                                    class="absolute top-2 right-2 size-8 bg-red-500/80 hover:bg-red-600 text-white rounded-lg flex items-center justify-center transition-all cursor-pointer backdrop-blur-sm shadow-lg">
                                    <i class="bx bx-trash"></i>
                                </button>
                                <input type="hidden" name="remove_existing_image" id="remove_existing_image" value="0">
                            </div>
                            
                            <p id="diagramHint" class="text-[10px] text-gray-400 italic">For geometry, circuits, structures or graph. Supports JPG, PNG, WEBP.</p>
                        </div>

                        <!-- Live Preview -->
                        <div id="questionPreview" class="hidden mt-2 p-4 bg-blue-50 border border-blue-100 rounded-2xl text-gray-800 font-medium text-sm leading-relaxed">
                            <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-2">Preview</p>
                            <div id="previewContent"></div>
                        </div>
                    </div>

                    <!-- Question Type Toggle -->
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest shrink-0">Question Type</span>
                        <div class="flex gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="question_type" value="mcq" id="type_mcq" class="hidden peer" checked>
                                <div class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wide border-2 transition-all
                                    peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600
                                    bg-white text-gray-400 border-gray-200 hover:border-blue-300">
                                    <i class="bx bx-list-ul mr-1"></i>MCQ
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="question_type" value="fill_blank" id="type_fill" class="hidden peer">
                                <div class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wide border-2 transition-all
                                    peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500
                                    bg-white text-gray-400 border-gray-200 hover:border-amber-300">
                                    <i class="bx bx-pencil mr-1"></i>Fill in Blank
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- MCQ Options Grid -->
                    <div id="mcqOptions" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach(['A', 'B', 'C', 'D'] as $opt): ?>
                            <div class="relative">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Option <?= $opt ?></label>
                                <div class="flex items-center gap-3">
                                    <input type="text" name="option_<?= strtolower($opt) ?>" id="option_<?= strtolower($opt) ?>"
                                        class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:bg-white transition-all"
                                        placeholder="Enter option <?= $opt ?>">
                                    <label class="cursor-pointer group flex-shrink-0">
                                        <input type="radio" name="correct_answer" value="<?= $opt ?>" class="hidden peer">
                                        <div class="size-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 peer-checked:bg-green-600 peer-checked:text-white transition-all group-hover:bg-gray-200" data-tippy-content="Mark as correct">
                                            <i class="bx bx-check font-bold"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Fill in Blank Answer Field (hidden by default) -->
                    <div id="fillBlankOptions" class="hidden">
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="size-8 rounded-lg bg-amber-500 flex items-center justify-center text-white">
                                    <i class="bx bx-pencil text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-black text-amber-700 uppercase tracking-widest">Fill in the Blank</p>
                                    <p class="text-[10px] text-amber-500">Student will type their answer. Graded case-insensitively.</p>
                                </div>
                            </div>
                            <label class="text-[10px] font-bold text-amber-600 uppercase tracking-widest block mb-2">Correct Answer</label>
                            <input type="text" id="fill_correct_answer" name="fill_correct_answer"
                                class="w-full bg-white border border-amber-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all"
                                placeholder="e.g. Weathering">
                            <p class="text-[10px] text-amber-400 mt-2">💡 Tip: Use the blank symbol <strong>___</strong> in the question text, e.g. <em>___ is the breaking down of rock into smaller particles.</em></p>
                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-8 border-t border-gray-50 flex items-center justify-between">
                    <button type="button" id="prevBtn" onclick="navigateQ(-1)"
                        class="flex items-center gap-2 text-sm px-2 md:px-6 py-2.5 rounded-xl text-gray-500 font-bold hover:bg-gray-100 transition-all cursor-pointer">
                        <i class="bx bx-arrow-left text-xl"></i>
                        Previous
                    </button>
                    
                    <div class="flex gap-3">
                        <button type="button" id="aiGenBtn"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm bg-violet-50 text-violet-600 border border-violet-100 font-bold hover:bg-violet-100 transition-all cursor-pointer"
                            onclick="openAiModal()">
                            <i class="bx bx-bot text-lg"></i>
                            <span class="hidden md:inline">AI Generate</span>
                        </button>
                        <button type="button" id="deleteBtn" onclick="deleteQuestion()"
                            class="size-12 rounded-xl border border-red-50 text-red-400 hover:bg-red-50 hover:text-red-600 transition-all cursor-pointer flex items-center justify-center hidden"
                            title="Delete this question">
                            <i class="bx bx-trash text-xl"></i>
                        </button>
                        <button type="submit" id="saveBtn"
                            class="px-8 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all cursor-pointer">
                            Save
                        </button>
                        <button type="button" id="nextBtn" onclick="navigateQ(1, true)"
                            class="flex items-center gap-2 px-2 md:px-6 py-2.5 rounded-xl text-sm bg-gray-900 text-white font-bold hover:bg-black transition-all cursor-pointer">
                            Next
                            <i class="bx bx-arrow-right text-xl"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── AI Generate Questions Modal ──────────────────────────────────────── -->
<div id="aiModal" class="hidden fixed inset-0 z-[99999] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAiModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden fadeIn">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-violet-50 shrink-0">
            <div class="flex items-center gap-3">
                <div class="size-9 rounded-xl bg-violet-600 flex items-center justify-center text-white">
                    <i class="bx bx-robot text-xl"></i>
                </div>
                <div>
                    <h3 class="font-black text-gray-800">AI Question Generator</h3>
                    <p class="text-[10px] text-violet-500 font-bold uppercase tracking-wider">Powered by Groq</p>
                </div>
            </div>
            <button onclick="closeAiModal()" class="text-gray-400 hover:text-gray-600 transition cursor-pointer">
                <i class="bx bx-x text-2xl"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1">

            <!-- Topic Input -->
            <div id="aiInputArea">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Enter Topic / Prompt</label>
                <textarea id="aiTopic" rows="3"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-400 transition resize-none"
                    placeholder="E.g. Photosynthesis, Quadratic equations, French Revolution..."></textarea>

                <div class="flex items-center gap-3 mt-4">
                    <div class="flex flex-col gap-1 flex-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Number of Questions</label>
                        <select id="aiCount" class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 transition">
                            <option value="5">5 Questions</option>
                            <option value="10" selected>10 Questions</option>
                            <option value="15">15 Questions</option>
                            <option value="20">20 Questions</option>
                        </select>
                    </div>
                    <button type="button" id="aiGenerateBtn" onclick="runAiGenerate()"
                        class="mt-5 px-6 py-2.5 bg-violet-600 text-white rounded-xl font-bold text-sm hover:bg-violet-700 transition-all cursor-pointer flex items-center gap-2 shadow-lg shadow-violet-100">
                        <i class="bx bx-sparkles"></i> Generate
                    </button>
                </div>
            </div>

            <!-- Results Area -->
            <div id="aiResultsArea" class="hidden mt-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-xs font-black text-gray-500 uppercase tracking-widest">Generated Questions</p>
                    <div class="flex items-center gap-2">
                        <span id="aiQCounter" class="text-xs font-bold text-violet-600"></span>
                        <button onclick="runAiGenerate()" class="text-xs text-gray-400 hover:text-violet-600 font-bold transition cursor-pointer">
                            <i class="bx bx-refresh"></i> Regenerate
                        </button>
                    </div>
                </div>

                <!-- Single question navigator -->
                <div id="aiQCard" class="bg-violet-50 border border-violet-100 rounded-2xl p-5">
                    <p class="text-[10px] font-black text-violet-400 uppercase tracking-widest mb-2" id="aiQLabel">Question 1</p>
                    <p class="text-sm font-semibold text-gray-800 leading-relaxed mb-4" id="aiQText"></p>
                    <div class="space-y-2" id="aiQOpts"></div>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between mt-4">
                    <button onclick="aiNavQ(-1)" class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-100 transition cursor-pointer flex items-center gap-1">
                        <i class="bx bx-chevron-left text-lg"></i> Prev
                    </button>
                    <button onclick="aiInsertCurrent()" class="px-6 py-2.5 bg-violet-600 text-white rounded-xl font-bold text-sm hover:bg-violet-700 transition cursor-pointer flex items-center gap-2 shadow-md shadow-violet-100">
                        <i class="bx bx-import"></i> Use This Question
                    </button>
                    <button onclick="aiNavQ(1)" class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-100 transition cursor-pointer flex items-center gap-1">
                        Next <i class="bx bx-chevron-right text-lg"></i>
                    </button>
                </div>
            </div>
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
            // Restore question type
            const qtype = data.question.question_type || 'mcq';
            $(`input[name="question_type"][value="${qtype}"]`).prop('checked', true).trigger('change');
            if (qtype === 'fill_blank') {
                $("#fill_correct_answer").val(data.question.correct_answer);
                $("#fill_correct_hidden").val(data.question.correct_answer);
            }

            // Handle Question Image
            if (data.question.question_image) {
                $("#diagram_img").attr('src', '/school_app/uploads/questions/' + data.question.question_image);
                $("#diagramPreview").removeClass("hidden");
                $("#diagramHint").addClass("hidden");
            } else {
                $("#diagramPreview").addClass("hidden");
                $("#diagramHint").removeClass("hidden");
            }
        } else {
            $("#deleteBtn").addClass("hidden");
            $("#diagramPreview").addClass("hidden");
            $("#diagramHint").removeClass("hidden");
        }
        $("#remove_existing_image").val("0");
        $("#question_image_input").val(""); // clear file input
        updateProgress();
        
        // Update Next vs Finish button on last question
        if (num === totalQs) {
            $("#nextBtn").html(`Finish Setup <i class="bx bx-check-double text-xl"></i>`)
                .removeClass('bg-gray-900').addClass('bg-green-600');
        } else {
            $("#nextBtn").html(`Next <i class="bx bx-arrow-right text-xl"></i>`)
                .addClass('bg-gray-900').removeClass('bg-green-600');
        }

        // Update nav buttons
        $(".question-number-btn").removeClass("ring-2 ring-blue-500 ring-offset-2");
        $(`#nav-${num}`).addClass("ring-2 ring-blue-500 ring-offset-2");
        
        // Scroll to button
        $(`#nav-${num}`)[0].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }, 'json');
}

// ── Question Type Toggle ──────────────────────────────────────────────────
$('input[name="question_type"]').on('change', function() {
    const isFill = $(this).val() === 'fill_blank';
    $('#mcqOptions').toggleClass('hidden', isFill);
    $('#fillBlankOptions').toggleClass('hidden', !isFill);
});

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


function navigateQ(dir, saveFirst = false) {
    if (saveFirst) {
        // If it's a "Next" or "Finish" click, we save first
        // We'll set a temporary property so the submit handler knows what to do next
        $("#questionForm").data('nav-dir', dir).trigger("submit");
    } else {
        loadQuestion(currentQuestion + dir);
    }
}

$("#questionForm").on("submit", function(e) {
    e.preventDefault();
    const btn = $("#saveBtn");
    const formData = new FormData(this); // Use FormData for file uploads

    $.ajax({
        url: '/school_app/staff/auth/save_question.php',
        method: 'POST',
        data: formData,
        processData: false, // Required for FormData
        contentType: false, // Required for FormData
        dataType: 'json',
        beforeSend: function() {
            btn.prop('disabled', true).html(`<div class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full mx-auto"></div>`);
        },
        success: function(data) {
            if (data.success) {
                window.showToast("Question saved successfully!", "success");
                updateProgress();
                
                const navDir = $("#questionForm").data('nav-dir');
                $("#questionForm").data('nav-dir', null); // clear it

                if (navDir === 1 && currentQuestion === totalQs) {
                    // Clicking Finish on the last page
                    setTimeout(() => {
                        window.loadPage('/school_app/staff/pages/exams.php');
                    }, 800);
                } else if (navDir) {
                    // Navigate after save
                    loadQuestion(currentQuestion + navDir);
                } else if (currentQuestion < totalQs) {
                    // Simple "Save Question" click while not on last, auto-next anyway
                    setTimeout(() => loadQuestion(currentQuestion + 1), 500);
                } else {
                    // Just stay on current question if saved but no navigation
                    loadQuestion(currentQuestion);
                }
            } else {
                window.showToast(data.message, "error");
            }
        },
        error: function() {
            window.showToast("Network error. Try again", "error");
        },
        complete: function() {
            btn.prop('disabled', false).text('Save');
        }
    });
});

// Initial load
$(document).ready(function() {
    loadQuestion(1);

    // ── Formatting Toolbar Logic ───────────────────────────────────────
    const $ta = $('#question_text')[0];

    // Wrap selected text with an HTML tag, or insert tag pair at cursor
    function wrapSelection(tag) {
        const start = $ta.selectionStart;
        const end   = $ta.selectionEnd;
        const val   = $ta.value;
        const selected = val.substring(start, end);
        const wrapped = `<${tag}>${selected}</${tag}>`;
        $ta.value = val.substring(0, start) + wrapped + val.substring(end);
        // Move cursor to just after the inserted block, or inside if nothing selected
        const cursor = selected.length > 0
            ? start + wrapped.length
            : start + tag.length + 2; // position inside the opening tag
        $ta.setSelectionRange(cursor, cursor);
        $ta.focus();
        updatePreview();
    }

    // Insert a plain symbol at cursor position
    function insertSymbol(sym) {
        const start = $ta.selectionStart;
        const val   = $ta.value;
        $ta.value = val.substring(0, start) + sym + val.substring(start);
        const pos = start + sym.length;
        $ta.setSelectionRange(pos, pos);
        $ta.focus();
        updatePreview();
    }

    // Format buttons (bold, italic, sup, sub)
    const formatMap = {
        bold: 'strong',
        italic: 'em',
        sup: 'sup',
        sub: 'sub'
    };

    $(document).on('click', '.fmt-btn', function() {
        const fmt = $(this).data('fmt');
        if (formatMap[fmt]) wrapSelection(formatMap[fmt]);
    });

    // Symbol buttons
    $(document).on('click', '.sym-btn', function() {
        insertSymbol($(this).data('sym'));
    });

    // ── Tab switching ─────────────────────────────────────────────────
    $(document).on('click', '.sym-tab', function() {
        const tab = $(this).data('tab');
        // Update tab active state
        $('.sym-tab')
            .removeClass('border-blue-500 text-blue-600 bg-white')
            .addClass('border-transparent text-gray-400');
        $(this)
            .removeClass('border-transparent text-gray-400')
            .addClass('border-blue-500 text-blue-600 bg-white');
        // Show only matching panel
        $('.sym-panel').addClass('hidden');
        $(`.sym-panel[data-panel="${tab}"]`).removeClass('hidden');
    });

    // Live preview toggle
    function updatePreview() {
        if ($('#previewToggle').is(':checked')) {
            $('#previewContent').html($ta.value || '<span class="text-gray-300">Nothing yet...</span>');
        }
    }

    $('#question_text').on('input', updatePreview);

    $('#previewToggle').on('change', function() {
        if ($(this).is(':checked')) {
            $('#questionPreview').removeClass('hidden');
            updatePreview();
        } else {
            $('#questionPreview').addClass('hidden');
        }
    });

    window.previewDiagram = function(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#diagram_img').attr('src', e.target.result);
                $('#diagramPreview').removeClass('hidden');
                $('#diagramHint').addClass('hidden');
            }
            reader.readAsDataURL(input.files[0]);
            $('#remove_existing_image').val('0');
        }
    }

    window.removeDiagram = function() {
        $('#diagramPreview').addClass('hidden');
        $('#diagramHint').removeClass('hidden');
        $('#question_image_input').val('');
        $('#remove_existing_image').val('1');
    }
    // ── End Formatting Toolbar Logic ───────────────────────────────────
});

// ── AI Generate Modal Logic ───────────────────────────────────────────────
let aiQuestions = [];
let aiQIndex    = 0;

function openAiModal() {
    $('#aiModal').removeClass('hidden');
    $('#aiResultsArea').addClass('hidden');
    setTimeout(() => $('#aiTopic').focus(), 100);
}

function closeAiModal() {
    $('#aiModal').addClass('hidden');
}

function runAiGenerate() {
    const topic   = $('#aiTopic').val().trim();
    const count   = $('#aiCount').val();

    if (!topic) {
        window.showToast('Please enter a topic first.', 'error');
        return;
    }

    const btn = $('#aiGenerateBtn');
    btn.prop('disabled', true).html('<i class="bx bx-loader-dots animate-spin bx-spin"></i> Generating...');
    $('#aiResultsArea').addClass('hidden');

    $.ajax({
        url: '/school_app/admin/auth/ai_generate.php',
        type: 'POST',
        data: {
            subject: '<?= addslashes($exam->subject) ?>',
                class: '<?= addslashes($exam->class) ?>',
                topic: topic,
                count: count,
            },
            dataType: 'json',
            success: function (res) {
                if (res.success && res.questions.length) {
                    aiQuestions = res.questions;
                    aiQIndex = 0;
                    renderAiQuestion();
                    $('#aiResultsArea').removeClass('hidden');
                } else {
                    window.showToast(res.message || 'AI returned no questions. Try again.', 'error');
                }
            },
            error: function () {
                window.showToast('Network error. Check connection and try again.', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="bx bx-sparkles"></i> Generate');
            }
        });
    }

    function renderAiQuestion() {
        const q = aiQuestions[aiQIndex];
        const badgeColors = { A: 'bg-blue-600', B: 'bg-green-600', C: 'bg-orange-500', D: 'bg-purple-600' };

        $('#aiQLabel').text(`Question ${aiQIndex + 1} of ${aiQuestions.length}`);
        $('#aiQCounter').text(`${aiQIndex + 1} / ${aiQuestions.length}`);
        $('#aiQText').text(q.question);

        const opts = [
            { key: 'A', text: q.option_a },
            { key: 'B', text: q.option_b },
            { key: 'C', text: q.option_c },
            { key: 'D', text: q.option_d },
        ];

        $('#aiQOpts').html(opts.map(o => {
            const isCorrect = o.key === q.correct_answer;
            return `<div class="flex items-center gap-3 p-2.5 rounded-xl border ${isCorrect ? 'bg-green-50 border-green-200' : 'bg-white border-gray-100'}">
            <div class="size-7 shrink-0 rounded-lg ${badgeColors[o.key] || 'bg-gray-500'} ${isCorrect ? '' : 'opacity-60'} flex items-center justify-center text-white text-xs font-black">${o.key}</div>
            <span class="text-sm ${isCorrect ? 'text-green-800 font-bold' : 'text-gray-600'}">${o.text}</span>
            ${isCorrect ? '<i class="bx bx-check-circle text-green-500 ml-auto"></i>' : ''}
        </div>`;
        }).join(''));
    }

    function aiNavQ(dir) {
        aiQIndex = Math.max(0, Math.min(aiQuestions.length - 1, aiQIndex + dir));
        renderAiQuestion();
    }

    function aiInsertCurrent() {
        const q = aiQuestions[aiQIndex];
        if (!q) return;

        $('#question_text').val(q.question);
        $('#option_a').val(q.option_a);
        $('#option_b').val(q.option_b);
        $('#option_c').val(q.option_c);
        $('#option_d').val(q.option_d);
        $(`input[name="correct_answer"][value="${q.correct_answer}"]`).prop('checked', true);

        closeAiModal();
        window.showToast(`Question ${aiQIndex + 1} inserted! Review and save.`, 'success');
    }
</script>
