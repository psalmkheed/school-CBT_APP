<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';
require_once __DIR__ . '/../../connections/ai_config.php';

if (!in_array($user->role, ['admin', 'staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$subject = trim($_POST['subject'] ?? '');
$class   = trim($_POST['class']   ?? '');
$count   = (int)($_POST['count']  ?? 5);
$topic   = trim($_POST['topic']   ?? '');

if (!$subject || !$class || !$topic) {
    echo json_encode(['success' => false, 'message' => 'Subject, class and topic are required.']);
    exit;
}

$count = max(1, min($count, 20));

$system = "You are an expert Nigerian secondary school question setter for {$subject} ({$class} level). You only respond with valid JSON arrays — no markdown, no explanation, no code fences. Just raw JSON.";

$user_msg = "Generate exactly {$count} multiple-choice questions on the topic: \"{$topic}\".\n\nRequirements:\n- Each question has exactly 4 options: A, B, C, D\n- One correct answer per question\n- Appropriate for {$class} WAEC/NECO level\n- Clear, unambiguous language\n\nRespond ONLY with a JSON array in this exact format:\n[\n  {\n    \"question\": \"Question text here?\",\n    \"option_a\": \"First option\",\n    \"option_b\": \"Second option\",\n    \"option_c\": \"Third option\",\n    \"option_d\": \"Fourth option\",\n    \"correct_answer\": \"A\"\n  }\n]";

$raw = groq_ask($system, $user_msg, 2000);

if (!$raw) {
    echo json_encode(['success' => false, 'message' => 'AI did not return content. Please try again.']);
    exit;
}

// Strip any accidental markdown fences
$raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
$raw = preg_replace('/\s*```$/', '', $raw);

$questions = json_decode(trim($raw), true);

if (!is_array($questions) || empty($questions)) {
    echo json_encode(['success' => false, 'message' => 'AI returned invalid format. Please try again.']);
    exit;
}

$clean = [];
foreach ($questions as $q) {
    if (isset($q['question'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct_answer'])) {
        $clean[] = [
            'question'       => trim($q['question']),
            'option_a'       => trim($q['option_a']),
            'option_b'       => trim($q['option_b']),
            'option_c'       => trim($q['option_c']),
            'option_d'       => trim($q['option_d']),
            'correct_answer' => strtoupper(trim($q['correct_answer'])),
        ];
    }
}

if (empty($clean)) {
    echo json_encode(['success' => false, 'message' => 'No valid questions found in AI response.']);
    exit;
}

echo json_encode(['success' => true, 'questions' => $clean]);
