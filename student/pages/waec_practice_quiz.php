<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

$subject = $_POST['subject'] ?? '';
$topic   = $_POST['topic']   ?? '';
$count   = max(5, min(100, (int)($_POST['count'] ?? 25)));

if (!$subject) { echo '<p class="p-8 text-red-500 font-bold">No subject specified.</p>'; exit; }

// Build query
$sql = "SELECT * FROM waec_questions WHERE subject = :subject";
$params = [':subject' => $subject];

if ($topic) {
    $sql .= " AND topic = :topic";
    $params[':topic'] = $topic;
}

$sql .= " ORDER BY RAND() LIMIT :limit";
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', $count, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    echo '<div class="p-8 flex flex-col items-center justify-center text-center min-h-[60vh]">
        <div class="size-20 bg-gray-50 rounded-full flex items-center justify-center mb-6"><i class="bx bx-search-alt text-4xl text-gray-300"></i></div>
        <h4 class="text-xl font-bold text-gray-800 mb-2">No Questions Found</h4>
        <p class="text-gray-500 max-w-sm mb-6">We couldn\'t find any questions for <strong>' . htmlspecialchars($subject) . '</strong>' . ($topic ? ' — <strong>' . htmlspecialchars($topic) . '</strong>' : '') . '. Try a different topic.</p>
        <button onclick="loadPage(\'<?= APP_URL ?>student/pages/waec_practice.php\')" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold text-sm hover:bg-indigo-700 transition-all cursor-pointer">← Back to Subjects</button>
    </div>';
    exit;
}
?>

<!-- ─── Practice Quiz Interface ──────────────────────────────────── -->
<div class="fixed inset-0 bg-white z-[900] flex flex-col fadeIn" id="practiceQuizContainer">

    <!-- Header -->
    <div class="h-14 bg-white border-b border-gray-50 px-4 md:px-8 flex items-center justify-between shrink-0 shadow-sm relative z-20">
        <div class="flex items-center gap-2">
            <button onclick="exitPractice()" class="size-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all cursor-pointer" title="Exit Practice">
                <i class="bx bx-x text-xl"></i>
            </button>
            <div class="hidden md:block">
                <h4 class="text-[10px] font-semibold text-gray-800 uppercase tracking-tighter"><?= htmlspecialchars($subject) ?> Practice</h4>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Calculator Toggle -->
            <button id="calcToggleBtn" onclick="toggleCalculator()"
                class="size-8 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all cursor-pointer shadow-sm"
                title="Calculator">
                <i class="bx bx-calculator text-base"></i>
            </button>
            <!-- Progress badge -->
            <div class="flex items-center gap-1.5 bg-indigo-50/50 px-3 py-1.5 rounded-xl border border-indigo-100/50">
                <i class="bx bx-list-ol text-indigo-600 font-bold text-xs"></i>
                <span class="text-[10px] font-semibold text-indigo-600 uppercase" id="pqProgress">Q1 of <?= count($questions) ?></span>
            </div>
            <!-- Score badge -->
            <div class="flex items-center gap-1.5 bg-green-50/50 px-3 py-1.5 rounded-xl border border-green-100/50">
                <i class="bx bx-check-circle text-green-600 text-xs"></i>
                <span class="text-[10px] font-semibold text-green-600" id="pqScore">0/0</span>
            </div>
        </div>
    </div>

    <!-- ── Floating Calculator ──────────────────────────────────────── -->
    <div id="calcPanel"
        class="hidden fixed z-[500] shadow-2xl shadow-gray-900/40 rounded-[1.5rem] overflow-hidden select-none outline-none border border-gray-700/50 transition-all duration-300 backdrop-blur-md"
        style="bottom: 100px; right: 20px; width: 270px;">

        <!-- Header (drag handle) -->
        <div id="calcHeader"
            class="flex items-center justify-between bg-gray-900/95 px-4 py-2 cursor-grab active:cursor-grabbing border-b border-gray-800">
            <div class="flex items-center gap-2">
                <i class="bx bx-calculator text-indigo-400 text-base"></i>
                <span class="text-white font-bold text-[9px] tracking-widest uppercase opacity-80">Scientific</span>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="toggleCalcBody()" id="toggleBodyBtn"
                    class="size-6 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/50 hover:text-white transition-all cursor-pointer" title="Minimize/Maximize">
                    <i class="bx bx-chevron-down text-sm" id="toggleBodyIcon"></i>
                </button>
                <button onclick="toggleCalculator()"
                    class="size-6 rounded-lg bg-red-500/10 hover:bg-red-500 flex items-center justify-center text-white/50 hover:text-white transition-all cursor-pointer">
                    <i class="bx bx-x text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Display Area (Always visible when panel is open) -->
        <div class="bg-gray-800/90 px-4 pt-3 pb-2 border-b border-gray-900/50">
            <div id="calcHistory" class="text-right text-gray-500 text-[9px] font-bold uppercase tracking-widest min-h-[12px] mb-0.5 overflow-hidden text-ellipsis whitespace-nowrap"></div>
            <div id="calcDisplay"
                class="text-right text-white font-bold text-2xl tracking-tight leading-none overflow-hidden text-ellipsis whitespace-nowrap">0</div>
        </div>

        <!-- Buttons Body (Togglable) -->
        <div id="calcBody" class="bg-gray-950/95 p-2 grid grid-cols-5 gap-1 transition-all duration-300">
            <?php
            $calcBtns = [
                // Row 1: Memory & Clear
                ['MC','calc-mem','MC'],['MR','calc-mem','MR'],['M+','calc-mem','M+'],['M-','calc-mem','M-'],['C','calc-clear','C'],
                // Row 2: Sci Functions
                ['sin','calc-sci','sin'],['cos','calc-sci','cos'],['tan','calc-sci','tan'],['log','calc-sci','log10'],['ln','calc-sci','ln'],
                // Row 3: Advanced Ops
                ['√','calc-sci','sqrt'],['∛','calc-sci','cbrt'],['x²','calc-sci','x²'],['xʸ','calc-op','pow'],['1/x','calc-sci','1/x'],
                // Row 4: Constants & Specials
                ['eˣ','calc-sci','exp'],['10ˣ','calc-sci','10^x'],['π','calc-val','pi'],['e','calc-val','e'],['%','calc-sci','%'],
                // Row 5: Numbers & Basic Ops
                ['7','calc-num',''],['8','calc-num',''],['9','calc-num',''],['÷','calc-op','÷'],['±','calc-fn','±'],
                ['4','calc-num',''],['5','calc-num',''],['6','calc-num',''],['×','calc-op','×'],['.','calc-num','.'],
                ['1','calc-num',''],['2','calc-num',''],['3','calc-num',''],['−','calc-op','−'],['=','calc-eq calc-span-row-2','='],
                ['0','calc-num calc-span-col-2',''],['+','calc-op','+'],
            ];
            $colorMap = [
                'calc-num'   => 'bg-gray-800 hover:bg-gray-700 text-white',
                'calc-op'    => 'bg-indigo-600 hover:bg-indigo-500 text-white',
                'calc-sci'   => 'bg-gray-900 border border-gray-800 hover:bg-gray-800 text-indigo-300 text-[9px] uppercase font-bold',
                'calc-fn'    => 'bg-gray-900 hover:bg-gray-800 text-gray-400 text-[10px] font-bold',
                'calc-val'   => 'bg-gray-900 hover:bg-gray-800 text-orange-400 font-serif italic text-xs',
                'calc-mem'   => 'bg-transparent text-gray-600 hover:text-gray-400 text-[8px] font-bold',
                'calc-clear' => 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/20',
                'calc-eq'    => 'bg-green-600 hover:bg-green-500 text-white font-bold text-lg shadow-lg shadow-green-900/20',
            ];
            foreach($calcBtns as [$label, $classes, $title]):
                $primaryCls = explode(' ', $classes)[0];
                $color = $colorMap[$primaryCls] ?? 'bg-gray-800 text-white';
                $colspan = strpos($classes, 'calc-span-col-2') !== false ? 'col-span-2' : '';
                $rowspan = strpos($classes, 'calc-span-row-2') !== false ? 'row-span-2' : '';
            ?>
                <button type="button"
                    class="calc-btn <?= $color ?> <?= $colspan ?> <?= $rowspan ?> rounded-lg py-2 transition-all cursor-pointer flex items-center justify-center active:scale-95 shadow-sm"
                    data-val="<?= htmlspecialchars($label) ?>">
                    <?= htmlspecialchars($label) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- ── End Calculator ────────────────────────────────────────────── -->

    <!-- Progress bar -->
    <div class="h-1.5 w-full bg-gray-100 shrink-0">
        <div id="pqBar" class="h-full bg-indigo-500 transition-all duration-500 shadow-[0_0_10px_rgba(99,102,241,0.3)]" style="width: 0%"></div>
    </div>

    <!-- Question Area -->
    <div class="flex-1 flex overflow-hidden bg-gray-50/30">
        
        <!-- Left: Question Content Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8 flex justify-center relative scrollbar-thin scrollbar-thumb-indigo-100" id="pqScrollContainer">
            <div class="max-w-3xl w-full h-fit py-4" id="pqQuestionArea">
                <!-- Injected by JS -->
            </div>
        </div>

        <!-- Right: Question Navigator (Desktop only) -->
        <div class="hidden lg:flex w-72 bg-white border-l border-gray-100 flex-col shrink-0 relative z-20">
            <div class="p-5 border-b border-gray-50 bg-gray-50/20">
                <h5 class="text-[10px] font-semibold text-gray-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="bx bx-grid-alt text-indigo-600"></i>
                    Practice Navigator
                </h5>
            </div>
            
            <div class="flex-1 overflow-y-auto p-5 scrollbar-thin">
                <div class="grid grid-cols-4 gap-2" id="pqNavigatorGrid">
                    <!-- Numbers injected by JS -->
                </div>
            </div>

            <div class="p-5 border-t border-gray-50 bg-indigo-50/10">
                <div class="space-y-2.5">
                    <div class="flex items-center gap-3">
                        <div class="size-2.5 rounded shadow-sm bg-green-500"></div>
                        <span class="text-[9px] font-semibold text-gray-500 uppercase">Answered</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-2.5 rounded shadow-sm bg-indigo-600"></div>
                        <span class="text-[9px] font-semibold text-gray-500 uppercase">Current</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-2.5 rounded shadow-sm bg-gray-200"></div>
                        <span class="text-[9px] font-semibold text-gray-500 uppercase">Todo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigator Toggle -->
    <button onclick="toggleMobilePQNav()" class="lg:hidden fixed right-6 bottom-24 size-14 rounded-full bg-indigo-600 text-white shadow-xl shadow-indigo-200 z-[950] flex items-center justify-center animate-bounce">
        <i class="bx bx-grid-alt text-2xl"></i>
    </button>

    <!-- Mobile Navigator Overlay -->
    <div id="pqMobileNavOverlay" class="hidden fixed inset-0 z-[1000] p-6 flex flex-col">
        <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md" onclick="toggleMobilePQNav()"></div>
        <div class="relative bg-white w-full max-h-[80vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col fadeIn mt-auto">
            <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                <h5 class="text-xs font-semibold text-gray-800 uppercase tracking-widest">Jump to Question</h5>
                <button onclick="toggleMobilePQNav()" class="size-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400"><i class="bx bx-x text-xl"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 scrollbar-thin">
                <div class="grid grid-cols-5 gap-3" id="pqMobileNavigatorGrid"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="h-16 bg-white border-t border-gray-50 px-6 md:px-12 flex items-center justify-between shrink-0 shadow-lg relative z-20">
        <button onclick="pqPrev()" id="pqPrevBtn" disabled
            class="px-5 py-2.5 bg-gray-50 text-gray-400 rounded-xl font-bold text-[10px] uppercase tracking-widest border border-gray-100 hover:bg-white hover:text-indigo-600 disabled:opacity-40 transition-all cursor-pointer flex items-center gap-1.5">
            <i class="bx bx-chevron-left text-lg"></i> Prev
        </button>

        <div class="flex gap-1 overflow-x-auto max-w-[40%] px-2 scrollbar-none" id="pqDots"></div>

        <button onclick="pqNext()" id="pqNextBtn"
            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-indigo-700 shadow-md transition-all cursor-pointer flex items-center gap-1.5 hover:translate-y-[-1px]">
            Next <i class="bx bx-chevron-right text-lg"></i>
        </button>
    </div>
</div>

<script>
(function() {
    const questions = <?= json_encode($questions) ?>;
    const total = questions.length;
    let current = 0;
    let answers = {};
    let revealed = {};
    let correct = 0;
    let answered = 0;

    function init() {
        createNavigator();
        render();
        initCalculator();
    }

    function render() {
        const q = questions[current];
        const opts = ['A', 'B', 'C', 'D'];
        const optKeys = ['option_a', 'option_b', 'option_c', 'option_d'];
        const userAns = answers[current] || null;
        const isRevealed = !!revealed[current];

        let html = `
            <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-2xl shadow-indigo-100/50 border border-white relative overflow-hidden flex flex-col fadeIn">
                <!-- Watermark -->
                <div class="absolute -bottom-10 -right-10 text-[120px] font-semibold text-gray-50/10 select-none rotate-[-15deg] z-0 pointer-events-none">${(current+1).toString().padStart(2, '0')}</div>

                <div class="relative z-10 flex flex-col">
                    <div class="shrink-0 mb-6 flex items-center justify-between">
                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-semibold uppercase tracking-widest
                            ${q.difficulty === 'easy' ? 'bg-green-100 text-green-700' : q.difficulty === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">
                            ${q.difficulty} • ${q.topic}
                        </span>
                    </div>

                    <div class="mb-10">
                        <h2 class="text-lg md:text-xl font-bold text-gray-800 leading-relaxed mb-6">${q.question}</h2>
                        
                        ${q.image ? `
                            <div class="mb-8 bg-gray-50/50 p-2 rounded-3xl border border-gray-100/50 inline-block max-w-full overflow-hidden">
                                <img src="../uploads/questions/${q.image}" 
                                     class="max-h-[400px] w-auto rounded-2xl shadow-sm" 
                                     alt="Diagram">
                            </div>
                        ` : ''}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;

        opts.forEach((letter, i) => {
            const val = q[optKeys[i]];
            const isSelected = userAns === letter;
            const isCorrect = q.correct_answer === letter;
            let cls = 'border-gray-50 bg-gray-50/50 hover:border-indigo-200 hover:bg-indigo-50/30';
            let iconHtml = '';

            if (isRevealed) {
                if (isCorrect) {
                    cls = 'border-green-500 bg-green-50 shadow-md shadow-green-100';
                    iconHtml = '<i class="bx bx-check-circle text-green-600 text-2xl fade-in-scale"></i>';
                } else if (isSelected && !isCorrect) {
                    cls = 'border-red-500 bg-red-50 shadow-md shadow-red-100';
                    iconHtml = '<i class="bx bx-x-circle text-red-600 text-2xl fade-in-scale"></i>';
                } else {
                    cls = 'border-gray-50 bg-gray-50/10 opacity-60';
                }
            } else if (isSelected) {
                cls = 'border-indigo-500 bg-indigo-50 shadow-lg shadow-indigo-100/50';
            }

            html += `
                <button onclick="pqSelect(${current}, '${letter}')"
                    class="w-full flex items-center gap-4 p-5 rounded-[2rem] border-2 transition-all duration-300 text-left cursor-pointer group ${cls}"
                    ${isRevealed ? 'disabled' : ''}>
                    <span class="size-10 shrink-0 rounded-2xl ${isSelected && !isRevealed ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-gray-400 border border-gray-100 group-hover:border-indigo-300 group-hover:text-indigo-500 shadow-sm'} flex items-center justify-center font-bold text-xs transition-all">${letter}</span>
                    <span class="text-xs md:text-sm font-bold ${isSelected ? (isRevealed && !isCorrect ? 'text-red-800' : 'text-indigo-800') : 'text-gray-600'} flex-1 leading-tight">${val}</span>
                    ${iconHtml}
                </button>`;
        });

        html += '</div>';

        // Action/Explanation Area
        if (userAns && !isRevealed) {
            html += `
                <div class="mt-10">
                    <button onclick="pqReveal(${current})"
                        class="w-full py-5 bg-indigo-600 text-white rounded-[2rem] font-semibold text-sm hover:bg-indigo-700 shadow-xl shadow-indigo-100/50 transition-all cursor-pointer flex items-center justify-center gap-2 uppercase tracking-widest">
                        <i class="bx bx-check-double text-xl"></i> Check Answer
                    </button>
                </div>`;
        }

        if (isRevealed && q.explanation) {
            html += `
                <div class="mt-8 p-6 rounded-[2rem] bg-indigo-50/80 border border-indigo-100 fadeIn">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="size-8 rounded-full bg-indigo-600 flex items-center justify-center text-white">
                            <i class="bx bx-bulb text-lg"></i>
                        </div>
                        <span class="text-xs font-semibold text-indigo-600 uppercase tracking-widest">Expert Explanation</span>
                    </div>
                    <p class="text-sm font-bold text-indigo-800 leading-relaxed">${q.explanation}</p>
                </div>`;
        }

        html += '</div></div>';
        document.getElementById('pqQuestionArea').innerHTML = html;
        document.getElementById('pqScrollContainer').scrollTo({ top: 0, behavior: 'smooth' });
        updateUI();
    }

    // ── Calculator Engine ─────────────────────────────────────────
    function initCalculator() {
        let display = '0';
        let expression = '';
        let operand = null;
        let operator = null;
        let justCalc = false;
        let memory = 0;

        const $disp = document.getElementById('calcDisplay');
        const $hist = document.getElementById('calcHistory');

        function updateDisplay() {
            const num = parseFloat(display);
            $disp.textContent = isNaN(num) ? display : (Math.abs(num) > 1e12 ? num.toExponential(4) : display);
            $hist.textContent = expression;
        }

        function calcInput(val) {
            const currentNum = parseFloat(display);
            switch(val) {
                case 'C': display = '0'; expression = ''; operand = null; operator = null; justCalc = false; break;
                case '0': case '1': case '2': case '3': case '4': case '5': case '6': case '7': case '8': case '9':
                    if (justCalc) { display = val; expression = ''; justCalc = false; }
                    else display = (display === '0' ? val : display + val);
                    break;
                case '.':
                    if (justCalc) { display = '0.'; justCalc = false; break; }
                    if (!display.includes('.')) display += '.';
                    break;
                case '+': case '−': case '×': case '÷': case 'xʸ':
                    let opLabel = val === 'xʸ' ? '^' : val;
                    if (operator && !justCalc) {
                        display = String(calculate(parseFloat(operand), currentNum, operator));
                        expression = display + ' ' + opLabel;
                    } else { expression = display + ' ' + opLabel; }
                    operand = display; operator = val; justCalc = false; display = '0';
                    break;
                case '=':
                    if (operator === null) break;
                    const result = calculate(parseFloat(operand), currentNum, operator);
                    let eqOpLabel = operator === 'xʸ' ? '^' : operator;
                    expression = operand + ' ' + eqOpLabel + ' ' + (display === '0' ? currentNum : display) + ' =';
                    display = String(result); operand = null; operator = null; justCalc = true;
                    break;
                case '±': display = String(currentNum * -1); break;
                case '%': display = String(currentNum / 100); break;
                case '√': expression = '√(' + display + ')'; display = String(Math.sqrt(currentNum)); justCalc = true; break;
                case '∛': expression = '∛(' + display + ')'; display = String(Math.cbrt(currentNum)); justCalc = true; break;
                case 'x²': expression = '(' + display + ')²'; display = String(Math.pow(currentNum, 2)); justCalc = true; break;
                case '1/x': expression = '1/(' + display + ')'; display = String(1 / currentNum); justCalc = true; break;
                case 'π': display = String(Math.PI); justCalc = false; break;
                case 'e': display = String(Math.E); justCalc = false; break;
                case 'sin': expression = 'sin(' + display + '°)'; display = String(parseFloat(Math.sin(currentNum * Math.PI / 180).toPrecision(10))); justCalc = true; break;
                case 'cos': expression = 'cos(' + display + '°)'; display = String(parseFloat(Math.cos(currentNum * Math.PI / 180).toPrecision(10))); justCalc = true; break;
                case 'tan': expression = 'tan(' + display + '°)'; display = String(parseFloat(Math.tan(currentNum * Math.PI / 180).toPrecision(10))); justCalc = true; break;
                case 'log': expression = 'log(' + display + ')'; display = String(Math.log10(currentNum)); justCalc = true; break;
                case 'ln': expression = 'ln(' + display + ')'; display = String(Math.log(currentNum)); justCalc = true; break;
                case 'eˣ': expression = 'e^(' + display + ')'; display = String(Math.exp(currentNum)); justCalc = true; break;
                case '10ˣ': expression = '10^(' + display + ')'; display = String(Math.pow(10, currentNum)); justCalc = true; break;
                case 'MC': memory = 0; break;
                case 'MR': display = String(memory); break;
                case 'M+': memory += currentNum; break;
                case 'M-': memory -= currentNum; break;
            }
            updateDisplay();
        }

        function calculate(a, b, op) {
            if (op === '+') return parseFloat((a + b).toPrecision(12));
            if (op === '−') return parseFloat((a - b).toPrecision(12));
            if (op === '×') return parseFloat((a * b).toPrecision(12));
            if (op === '÷') return b === 0 ? 'Error' : parseFloat((a / b).toPrecision(12));
            if (op === 'xʸ') return parseFloat(Math.pow(a, b).toPrecision(12));
            return b;
        }

        document.querySelectorAll('.calc-btn').forEach(btn => {
            btn.onclick = () => calcInput(btn.dataset.val);
        });

        // Dragging
        const panel = document.getElementById('calcPanel');
        const handle = document.getElementById('calcHeader');
        let dragging = false, ox = 0, oy = 0;

        handle.onmousedown = (e) => {
            dragging = true;
            const rect = panel.getBoundingClientRect();
            ox = e.clientX - rect.left; oy = e.clientY - rect.top;
            panel.style.bottom = 'auto'; panel.style.right = 'auto';
            panel.style.top = rect.top + 'px'; panel.style.left = rect.left + 'px';
        };
        document.onmousemove = (e) => {
            if (!dragging) return;
            panel.style.left = (e.clientX - ox) + 'px'; panel.style.top = (e.clientY - oy) + 'px';
        };
        document.onmouseup = () => { dragging = false; };

        window.toggleCalculator = () => {
            panel.classList.toggle('hidden');
            const btn = document.getElementById('calcToggleBtn');
            btn.classList.toggle('bg-indigo-600');
            btn.classList.toggle('text-white');
            btn.classList.toggle('border-indigo-600');
        };

        window.toggleCalcBody = () => {
            const body = document.getElementById('calcBody');
            const icon = document.getElementById('toggleBodyIcon');
            body.classList.toggle('hidden');
            icon.classList.toggle('bx-chevron-down');
            icon.classList.toggle('bx-chevron-up');
        };
    }

    // Standard logic
    window.pqSelect = function(idx, letter) { if (revealed[idx]) return; answers[idx] = letter; render(); };
    window.pqReveal = function(idx) {
        if (revealed[idx]) return;
        revealed[idx] = true; answered++;
        if (answers[idx] === questions[idx].correct_answer) correct++;
        render();
    };
    window.pqPrev = function() { if (current > 0) { current--; render(); } };
    window.pqNext = function() { if (current < total - 1) { current++; render(); } };

    window.jumpToPQ = function(idx) {
        if (idx >= 0 && idx < total) {
            current = idx;
            render();
            $('#pqMobileNavOverlay').addClass('hidden');
        }
    };

    window.toggleMobilePQNav = function() {
        $('#pqMobileNavOverlay').toggleClass('hidden');
    };

    function createNavigator() {
        const grid = document.getElementById('pqNavigatorGrid');
        const mobileGrid = document.getElementById('pqMobileNavigatorGrid');
        grid.innerHTML = ''; mobileGrid.innerHTML = '';
        
        for (let i = 0; i < total; i++) {
            const btn = `
                <button onclick="jumpToPQ(${i})" class="pq-nav-item size-10 rounded-xl border border-gray-100 font-bold text-xs flex items-center justify-center transition-all cursor-pointer hover:bg-indigo-50 hover:text-indigo-600">
                    ${i + 1}
                </button>`;
            grid.insertAdjacentHTML('beforeend', btn);
            mobileGrid.insertAdjacentHTML('beforeend', btn);
        }
        createDots();
    }

    function updateNavigator() {
        const items = document.querySelectorAll('.pq-nav-item');
        items.forEach((item, i) => {
            const isCurrent = i === current;
            const isRevealed = !!revealed[i];
            const isAnswered = !!answers[i];
            
            item.className = 'pq-nav-item size-10 rounded-xl font-bold text-xs flex items-center justify-center transition-all cursor-pointer ';
            
            if (isCurrent) item.className += 'bg-indigo-600 text-white shadow-lg shadow-indigo-100 ring-4 ring-indigo-50 scale-110 z-10';
            else if (isRevealed) {
                const isCorrect = answers[i] === questions[i].correct_answer;
                item.className += (isCorrect ? 'bg-green-500 text-white border-green-500' : 'bg-red-500 text-white border-red-500');
            } else if (isAnswered) item.className += 'bg-amber-100 text-amber-700 border-amber-200';
            else item.className += 'bg-gray-50 text-gray-400 border-gray-100 hover:bg-gray-100';
        });
    }

    function updateUI() {
        document.getElementById('pqProgress').textContent = `Q${current + 1} of ${total}`;
        document.getElementById('pqBar').style.width = ((current + 1) / total * 100) + '%';
        document.getElementById('pqScore').textContent = `${correct}/${answered}`;
        document.getElementById('pqPrevBtn').disabled = current === 0;
        const nextBtn = document.getElementById('pqNextBtn');
        if (current === total - 1) { nextBtn.innerHTML = '<i class="bx bx-check text-lg"></i> Finish'; nextBtn.onclick = finishPractice; }
        else { nextBtn.innerHTML = 'Next <i class="bx bx-right-arrow-alt text-lg"></i>'; nextBtn.onclick = pqNext; }
        updateNavigator();
        document.querySelectorAll('.pq-dot').forEach((d, i) => {
            d.className = 'pq-dot size-3 rounded-full transition-all duration-300 cursor-pointer shrink-0 ';
            if (i === current) d.className += 'bg-indigo-600 scale-125';
            else if (revealed[i]) d.className += (answers[i] === questions[i].correct_answer ? 'bg-green-400' : 'bg-red-400');
            else if (answers[i]) d.className += 'bg-indigo-300';
            else d.className += 'bg-gray-300';
        });
    }

    function createDots() {
        const container = document.getElementById('pqDots'); container.innerHTML = '';
        for (let i = 0; i < total; i++) {
            const dot = document.createElement('div');
            dot.className = 'pq-dot size-3 rounded-full bg-gray-300 cursor-pointer shrink-0 transition-all';
            dot.onclick = () => { current = i; render(); };
            container.appendChild(dot);
        }
    }

    function finishPractice() {
        const pct = answered > 0 ? Math.round((correct / answered) * 100) : 0;
        const unanswered = total - answered;
        let emoji = pct >= 80 ? '🎉' : pct >= 50 ? '👍' : '📚';
        let msg = pct >= 80 ? 'Excellent work!' : pct >= 50 ? 'Good effort, keep practising!' : 'Keep going, practice makes perfect!';

        document.getElementById('pqQuestionArea').innerHTML = `
            <div class="fadeIn text-center py-8">
                <div class="text-6xl mb-4">${emoji}</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Practice Complete!</h2>
                <p class="text-gray-500 mb-8">${msg}</p>
                <div class="grid grid-cols-3 gap-4 max-w-md mx-auto mb-8">
                    <div class="bg-green-50 rounded-2xl p-4 border border-green-100"><p class="text-2xl font-bold text-green-600">${correct}</p><p class="text-[10px] font-bold text-green-500 uppercase">Correct</p></div>
                    <div class="bg-red-50 rounded-2xl p-4 border border-red-100"><p class="text-2xl font-bold text-red-600">${answered - correct}</p><p class="text-[10px] font-bold text-red-500 uppercase">Wrong</p></div>
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200"><p class="text-2xl font-bold text-gray-600">${unanswered}</p><p class="text-[10px] font-bold text-gray-400 uppercase">Skipped</p></div>
                </div>
                <div class="bg-indigo-50 rounded-2xl p-6 border border-indigo-100 max-w-sm mx-auto mb-8"><p class="text-4xl font-bold text-indigo-600">${pct}%</p><p class="text-xs font-bold text-indigo-400 uppercase">Score</p></div>
                <div class="flex gap-3 justify-center flex-wrap">
                    <button onclick="window.loadPage('pages/waec_practice.php')" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-2xl font-bold text-sm hover:bg-gray-200 transition-all cursor-pointer">← Back to Subjects</button>
                    <button onclick="location.reload()" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold text-sm hover:bg-indigo-700 shadow-md transition-all cursor-pointer">🔄 Try Again</button>
                </div>
            </div>`;
        document.querySelector('#practiceQuizContainer > div:last-child').style.display = 'none';
        document.getElementById('pqBar').style.width = '100%';
    }

    window.exitPractice = function() {
        if (answered > 0 && answered < total) { if (!confirm('You have unanswered questions. Exit practice?')) return; }
        window.loadPage('pages/waec_practice.php');
    };

    init();
})();
</script>
