<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

// Only SS1 – SS3 students (Senior School) may access
$userClass = strtoupper(trim($_SESSION['class'] ?? $user->class ?? ''));
$isAllowed = (strpos($userClass, 'SS') !== false && strpos($userClass, 'JSS') === false) || (strpos($userClass, 'SSS') !== false);

// Fetch distinct subjects from waec_questions
$subjects = $conn->query("SELECT DISTINCT subject FROM waec_questions ORDER BY subject ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch distinct topics per subject
$topicsBySubject = [];
foreach ($subjects as $subj) {
    $ts = $conn->prepare("SELECT DISTINCT topic FROM waec_questions WHERE subject = ? ORDER BY topic ASC");
    $ts->execute([$subj]);
    $topicsBySubject[$subj] = $ts->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!-- ─── WAEC Practice – Subject Selection ──────────────────────────── -->
<div class="p-4 md:p-8 min-h-screen bg-gray-50/30" id="waecPracticeHub">
    <div class="flex items-center gap-4 mb-8">
        <button onclick="goHome()"
            class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-indigo-700 hover:border-indigo-200 hover:bg-indigo-50 transition-all cursor-pointer"
            title="Go back" data-tippy-content="Back to Dashboard">
            <i class="bx bx-arrow-left-stroke text-4xl"></i>
        </button>
        <div>
            <h3 class="text-2xl font-bold text-gray-800">WAEC Practice Mode</h3>
            <p class="text-sm text-gray-500">Select a subject to start practising — questions are randomised each time</p>
        </div>
    </div>

    <?php if (!$isAllowed): ?>
        <div class="col-span-full py-20 bg-white rounded-3xl border border-dashed border-orange-200 flex flex-col items-center justify-center text-center px-6">
            <div class="size-20 bg-orange-50 rounded-full flex items-center justify-center mb-6">
                <i class="bx bx-lock-alt text-4xl text-orange-400"></i>
            </div>
            <h4 class="text-xl font-semibold text-gray-800 mb-2">Access Restricted</h4>
            <p class="text-gray-500 max-w-sm">WAEC Practice is available for SS1 – SS3 students only. Your current class (<strong><?= htmlspecialchars($userClass ?: 'N/A') ?></strong>) doesn't have access.</p>
        </div>
    <?php else: ?>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="size-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600"><i class="bx bx-book-open text-xl"></i></div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= count($subjects) ?></p>
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Subjects</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="size-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600"><i class="bx bx-list-check text-xl"></i></div>
            </div>
            <?php $totalQ = $conn->query("SELECT COUNT(*) FROM waec_questions")->fetchColumn(); ?>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($totalQ) ?></p>
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Questions</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="size-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600"><i class="bx bx-timer text-xl"></i></div>
            </div>
            <p class="text-2xl font-bold text-gray-800">No Limit</p>
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Time</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="size-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600"><i class="bx bx-infinite text-xl"></i></div>
            </div>
            <p class="text-2xl font-bold text-gray-800">Unlimited</p>
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Attempts</p>
        </div>
    </div>

    <!-- Subject Cards -->
    <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Choose a Subject</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="subjectGrid">
        <?php
        $icons = [
            'Mathematics'      => ['bx-calculator', 'from-indigo-500 to-blue-600', 'bg-indigo-50 text-indigo-600'],
            'English Language' => ['bx-book-open', 'from-green-500 to-emerald-600', 'bg-green-50 text-green-600'],
            'Physics'          => ['bx-atom', 'from-orange-500 to-red-500', 'bg-orange-50 text-orange-600'],
            'Chemistry'        => ['bx-vial', 'from-purple-500 to-violet-600', 'bg-purple-50 text-purple-600'],
            'Biology'          => ['bx-dna', 'from-teal-500 to-green-600', 'bg-teal-50 text-teal-600'],
            'Economics'        => ['bx-line-chart', 'from-sky-500 to-cyan-600', 'bg-sky-50 text-sky-600'],
            'Government'       => ['bx-landmark', 'from-rose-500 to-pink-600', 'bg-rose-50 text-rose-600'],
        ];

        foreach ($subjects as $subject):
            $meta  = $icons[$subject] ?? ['bx-book', 'from-gray-500 to-gray-600', 'bg-gray-100 text-gray-600'];
            $icon  = $meta[0];
            $grad  = $meta[1];
            $badge = $meta[2];
            $topics = $topicsBySubject[$subject] ?? [];
            $topicCount = count($topics);
            $qCount = $conn->prepare("SELECT COUNT(*) FROM waec_questions WHERE subject = ?");
            $qCount->execute([$subject]);
            $numQ = $qCount->fetchColumn();
        ?>
            <div class="waec-subject-card bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden group relative cursor-pointer"
                 onclick="openWaecPracticeConfig('<?= htmlspecialchars($subject) ?>')">
                <!-- Gradient top strip -->
                <div class="h-1.5 bg-gradient-to-r <?= $grad ?>"></div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div class="size-12 rounded-2xl <?= $badge ?> flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="bx <?= $icon ?> text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-500">
                            <?= number_format($numQ) ?> Qs
                        </span>
                    </div>

                    <h4 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-indigo-600 transition-colors"><?= htmlspecialchars($subject) ?></h4>
                    <p class="text-xs text-gray-400 font-medium"><?= $topicCount ?> topics available</p>

                    <div class="mt-5 flex items-center gap-2 text-indigo-500 text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span>Start Practising</span>
                        <i class="bx bx-right-arrow-alt text-lg"></i>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ─── Practice Configuration Modal ──────────────────────────────── -->
<div id="waecConfigModal" class="hidden fixed inset-0 z-[300] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeWaecConfig()"></div>

    <div class="relative bg-white w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-[2.5rem] shadow-2xl overflow-hidden fadeIn">
        <!-- Drag Handle (mobile) -->
        <div class="flex justify-center pt-4 pb-1 sm:hidden">
            <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
        </div>

        <!-- Header -->
        <div class="px-8 pt-6 pb-4 flex items-center gap-4">
            <div class="size-14 shrink-0 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-500">
                <i class="bx bx-cog text-3xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800 leading-tight" id="waecConfigSubject">Mathematics</h3>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Configure your practice session</p>
            </div>
        </div>

        <div class="mx-8 border-t border-gray-100"></div>

        <!-- Config Form -->
        <div class="px-8 py-6 space-y-5">
            <!-- Number of questions -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Number of Questions</label>
                <div class="grid grid-cols-4 gap-2" id="qCountBtns">
                    <button type="button" data-count="10" class="qcount-btn py-3 rounded-xl text-sm font-bold border border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:bg-indigo-50 transition-all cursor-pointer">10</button>
                    <button type="button" data-count="25" class="qcount-btn py-3 rounded-xl text-sm font-bold border-2 border-indigo-500 bg-indigo-50 text-indigo-700 transition-all cursor-pointer active">25</button>
                    <button type="button" data-count="50" class="qcount-btn py-3 rounded-xl text-sm font-bold border border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:bg-indigo-50 transition-all cursor-pointer">50</button>
                    <button type="button" data-count="100" class="qcount-btn py-3 rounded-xl text-sm font-bold border border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:bg-indigo-50 transition-all cursor-pointer">100</button>
                </div>
            </div>

            <!-- Topic filter -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Topic (Optional)</label>
                <select id="waecTopicSelect" class="w-full py-3 px-4 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
                    <option value="">All Topics (Mixed)</option>
                </select>
            </div>

            <!-- Info -->
            <div class="flex items-start gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100">
                <i class="bx bx-bulb text-amber-500 text-lg mt-0.5"></i>
                <p class="text-xs text-amber-700 leading-relaxed">Practice mode has <strong>no timer</strong> and <strong>no score tracking</strong>. You'll see explanations for each question after answering.</p>
            </div>
        </div>

        <!-- Buttons -->
        <div class="px-8 pb-8 grid grid-cols-2 gap-3">
            <button onclick="closeWaecConfig()" class="w-full py-4 bg-gray-100 text-gray-600 rounded-2xl font-bold text-sm hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
            <button onclick="startWaecPractice()" id="startPracticeBtn"
                class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-sm hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all cursor-pointer flex items-center justify-center gap-2">
                <i class="bx bx-play-circle text-lg"></i> Start
            </button>
        </div>
    </div>
</div>

<!-- ─── Topics data (for JS) ──────────────────────────────────── -->
<script>
const waecTopics = <?= json_encode($topicsBySubject) ?>;
let selectedSubject = '';
let selectedCount = 25;

// Subject card click → open config
function openWaecPracticeConfig(subject) {
    selectedSubject = subject;
    document.getElementById('waecConfigSubject').textContent = subject;

    // Populate topics dropdown
    const sel = document.getElementById('waecTopicSelect');
    sel.innerHTML = '<option value="">All Topics (Mixed)</option>';
    (waecTopics[subject] || []).forEach(t => {
        sel.innerHTML += `<option value="${t}">${t}</option>`;
    });

    document.getElementById('waecConfigModal').classList.remove('hidden');
}

function closeWaecConfig() {
    document.getElementById('waecConfigModal').classList.add('hidden');
}

// Question count buttons
document.querySelectorAll('.qcount-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.qcount-btn').forEach(b => {
            b.classList.remove('border-indigo-500', 'border-2', 'bg-indigo-50', 'text-indigo-700', 'active');
            b.classList.add('border-gray-200', 'bg-white', 'text-gray-600');
        });
        this.classList.remove('border-gray-200', 'bg-white', 'text-gray-600');
        this.classList.add('border-indigo-500', 'border-2', 'bg-indigo-50', 'text-indigo-700', 'active');
        selectedCount = parseInt(this.dataset.count);
    });
});

function startWaecPractice() {
    const topic = document.getElementById('waecTopicSelect').value;
    closeWaecConfig();

    // Load practice quiz via AJAX
    $('#mainContent').fadeOut(300, function() {
        $.ajax({
            url: '/school_app/student/pages/waec_practice_quiz.php',
            type: 'POST',
            data: { subject: selectedSubject, topic: topic, count: selectedCount },
            success: function(response) {
                $('#mainContent').html(response).fadeIn(300);
            },
            error: function() {
                showToast('Failed to load practice quiz. Try again.', 'error');
                $('#mainContent').fadeIn(300);
            }
        });
    });
}
</script>
