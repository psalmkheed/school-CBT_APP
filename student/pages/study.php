<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Fetch weak subjects (lowest scores)
$stmt = $conn->prepare("
    SELECT e.subject, AVG(r.percentage) as avg_score
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = :user_id AND e.session = :sess AND e.term = :term
    GROUP BY e.subject
    ORDER BY avg_score ASC
    LIMIT 3
");
$stmt->execute([':user_id' => $user->id, ':sess' => $active_session, ':term' => $active_term]);
$weak_subjects = $stmt->fetchAll(PDO::FETCH_OBJ);

$firstName = explode(' ', $user->first_name)[0];

// Fallback if they haven't taken exams
$mockData = [
    (object)['subject' => 'Mathematics', 'avg_score' => 45.5],
    (object)['subject' => 'Physics', 'avg_score' => 52.0],
];
if(empty($weak_subjects)) {
    $weak_subjects = $mockData;
    $is_mock = true;
} else {
    $is_mock = false;
}

// Fetch Groq API Key
$config_stmt = $conn->query("SELECT groq_api_key FROM school_config LIMIT 1");
$config_key = $config_stmt->fetch(PDO::FETCH_ASSOC);
$api_key = $config_key['groq_api_key'] ?? '';

// Build Study Plan List
$study_plan = [];

if (!empty($api_key)) {
    // We have an API key, let's ask Groq to generate dynamic advice for each weak subject
    $subjects_list = implode(', ', array_map(function($s) { return $s->subject; }, $weak_subjects));
    
    $system_prompt = "You are an AI academic advisor. The student '$firstName' is currently weak in the following subjects: $subjects_list. Generate a personalized 3-step study roadmap focusing sequentially on their weakest subject first, then the next. Return a JSON array of 3 objects, each representing a roadmap step. Each object MUST have: 'title' (string, e.g., 'Master Algebra'), 'description' (string, 1-2 short sentences of specific advice), 'subject' (string, identifying the target subject). Only return the JSON array.";

    $data = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            ["role" => "system", "content" => $system_prompt]
        ],
        "temperature" => 0.6,
        "response_format" => ["type" => "json_object"]
    ];
    $data['messages'][0]['content'] .= " Reply with ONLY a valid JSON object with a single key 'steps' holding the array.";

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $api_key,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? '';
        
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $parsed = json_decode(trim($content), true);
        
        if ($parsed && isset($parsed['steps']) && is_array($parsed['steps']) && count($parsed['steps']) > 0) {
            $study_plan = $parsed['steps'];
        }
    }
}

// Fallback if API fails or is not configured
if (empty($study_plan)) {
    foreach($weak_subjects as $index => $weakness) {
        $study_plan[] = [
            'title' => htmlspecialchars($weakness->subject) . ' Focus',
            'description' => "To improve your " . htmlspecialchars($weakness->subject) . " scores, focus on foundational theories. The AI has queued up practice modules for you.",
            'subject' => htmlspecialchars($weakness->subject),
            'avg_score' => (int)$weakness->avg_score
        ];
    }
}

?>

<div class="fadeIn p-4 md:p-10 min-h-screen">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-xl shadow-purple-200 flex items-center justify-center relative overflow-hidden">
                <i class="bx bx-brain text-3xl relative z-10"></i>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white/20 to-transparent"></div>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">AI Study Path</h1>
                <p class="text-sm text-gray-500 font-medium">Personalized curriculum based on your performance.</p>
            </div>
        </div>
        <button id="refreshAiBtn" class="flex flex-col items-center justify-center size-12 bg-white rounded-2xl hover:bg-gray-50 transition shadow-sm border border-gray-100 cursor-pointer group" data-tippy-content="Recalculate Path">
            <i class="bx bx-refresh-cw text-2xl text-purple-500 group-hover:rotate-180 transition-transform duration-500"></i>
        </button>
    </div>

    <?php if(empty($api_key)): ?>
    <div class="mb-8 p-6 bg-orange-50 border border-orange-100 rounded-3xl flex items-start gap-4">
        <div class="size-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
            <i class="bx bx-error text-xl"></i>
        </div>
        <div>
            <h4 class="text-orange-800 font-bold mb-1">AI Not Configured</h4>
            <p class="text-sm text-orange-600">The Administrator needs to configure the Groq API key in system settings to unlock dynamic, personalized AI guidance.</p>
        </div>
    </div>
    <?php elseif($is_mock): ?>
    <div class="mb-8 p-6 bg-blue-50 border border-blue-100 rounded-3xl flex items-start gap-4">
        <div class="size-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
            <i class="bx bx-info-circle text-xl"></i>
        </div>
        <div>
            <h4 class="text-blue-800 font-bold mb-1">Not enough exam data</h4>
            <p class="text-sm text-blue-600">You haven't taken enough exams yet. The below study path is a demonstration. Take some exams to get your personalized AI roadmap!</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- AI Overview -->
    <div class="bg-gray-900 rounded-[2.5rem] p-8 md:p-12 text-white relative overflow-hidden mb-12 shadow-2xl shadow-indigo-900/20">
        <!-- Decoration -->
        <div class="absolute -top-40 -right-40 size-96 bg-purple-600 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 size-96 bg-blue-600 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
        
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-[10px] font-semibold uppercase tracking-widest text-purple-300 mb-6 border border-white/10 backdrop-blur-md">
                <span class="size-1.5 rounded-full bg-purple-400 animate-pulse"></span> Analyzing records
            </span>
            <h2 class="text-3xl md:text-4xl font-semibold mb-4 leading-tight">
                Hey <?= htmlspecialchars($firstName) ?>, let's turn those <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">weaknesses into strengths.</span>
            </h2>
            <p class="text-gray-300 font-medium leading-relaxed mb-8">
                Based on your recent exam results, I noticed you're struggling slightly with <strong class="text-white"><?= htmlspecialchars($weak_subjects[0]->subject) ?></strong> and <strong class="text-white"><?= htmlspecialchars($weak_subjects[1]->subject ?? 'some other topics') ?></strong>. I've compiled a specialized 3-step module for you below.
            </p>
        </div>
    </div>

    <!-- The Roadmap -->
    <div class="space-y-6 relative before:absolute before:inset-0 before:ml-8 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-purple-200 before:via-indigo-100 before:to-transparent">

        <?php foreach($study_plan as $index => $step): 
            $icons = ['bx-target', 'bx-book-open', 'bx-rocket'];
            $colors = ['text-purple-600', 'text-blue-600', 'text-pink-600'];
            $bgColors = ['bg-purple-100', 'bg-blue-100', 'bg-pink-100'];
            $borderColors = ['border-purple-200', 'border-blue-200', 'border-pink-200'];
            
            $icon = $icons[$index % 3];
            $tColor = $colors[$index % 3];
            $bgColor = $bgColors[$index % 3];
            $bColor = $borderColors[$index % 3];
            $subjName = $step['subject'] ?? 'General';
        ?>
        <!-- Roadmap Item -->
        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group select-none">
            <!-- Icon -->
            <div class="flex items-center justify-center size-16 rounded-full border-4 border-white bg-white shadow-xl shadow-gray-200/50 absolute left-0 md:left-1/2 -translate-x-1/2 z-10">
                <div class="size-12 rounded-full <?= $bgColor ?> <?= $tColor ?> flex items-center justify-center">
                    <i class="bx <?= $icon ?> text-2xl group-hover:scale-110 transition-transform"></i>
                </div>
            </div>
            
            <!-- Card -->
            <div class="w-full md:w-[calc(50%-4rem)] pl-20 md:pl-0">
                <div class="bg-white p-6 md:p-8 rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-100/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <span class="px-3 py-1 rounded-full text-[10px] font-semibold uppercase tracking-widest <?= $bgColor ?> <?= $tColor ?> border <?= $bColor ?>">Step <?= $index + 1 ?></span>
                        <span class="text-xs font-bold text-gray-400"><?= htmlspecialchars($subjName) ?></span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?= htmlspecialchars($step['title'] ?? 'Focus Module') ?></h3>
                    <p class="text-sm text-gray-500 font-medium mb-6 leading-relaxed">
                        <?= htmlspecialchars($step['description'] ?? 'Review foundational concepts related to this subject to build strength.') ?>
                    </p>
                    
                    <div class="space-y-3">
                        <a href="https://www.youtube.com/results?search_query=<?= urlencode(htmlspecialchars($subjName) . ' ' . htmlspecialchars($step['title'] ?? '')) ?>+tutorial+for+<?= $_SESSION['class'] ?>+in+nigeria" target="_blank" class="w-full relative group/btn overflow-hidden bg-gray-50 hover:bg-white border border-gray-200 px-4 py-3 rounded-xl flex items-center justify-between transition-all">
                            <div class="flex items-center gap-3 relative z-10">
                                <div class="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                    <i class="bx bx-play-circle text-lg"></i>
                                </div>
                                <span class="text-sm font-bold text-gray-700 group-hover/btn:text-indigo-600 transition-colors text-left">Conceptual Video Review</span>
                            </div>
                            <i class="bx bx-chevron-right text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

</div>

<script>
    $('#refreshAiBtn').on('click', function() {
        const btn = $(this);
        btn.addClass('animate-spin'); // Use standard tailwind spin
        setTimeout(() => {
            window.loadPage('pages/study.php');
        }, 100);
    });
</script>
