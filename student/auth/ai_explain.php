<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';
require_once __DIR__ . '/../../connections/ai_config.php';

// Only students can call this
if ($user->role !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$question  = trim($_POST['question']  ?? '');
$option_a  = trim($_POST['option_a']  ?? '');
$option_b  = trim($_POST['option_b']  ?? '');
$option_c  = trim($_POST['option_c']  ?? '');
$option_d  = trim($_POST['option_d']  ?? '');
$correct   = strtoupper(trim($_POST['correct'] ?? ''));
$subject   = trim($_POST['subject']   ?? 'the subject');

if (!$question || !$correct) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$options_map  = ['A' => $option_a, 'B' => $option_b, 'C' => $option_c, 'D' => $option_d];
$correct_text = $options_map[$correct] ?? $correct;

$student_class = htmlspecialchars($user->class ?? 'school');
$system = "You are a helpful Nigerian school teacher for {$subject} ({$student_class} level). Give clear, concise explanations in simple English appropriate for Nigerian school students. Keep responses to 3–5 sentences max. Do NOT repeat the question or list the options again.";

$user_msg = "A student answered this exam question:\n\nQuestion: {$question}\n\nOptions:\nA) {$option_a}\nB) {$option_b}\nC) {$option_c}\nD) {$option_d}\n\nThe correct answer is: {$correct}) {$correct_text}\n\nExplain why this is correct and give a tip to remember it.";

$explanation = groq_ask($system, $user_msg, 400);

if (!$explanation) {
    echo json_encode(['success' => false, 'message' => 'AI could not generate an explanation. Please try again.']);
    exit;
}

echo json_encode(['success' => true, 'explanation' => trim($explanation)]);
