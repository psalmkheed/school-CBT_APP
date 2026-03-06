<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../connections/db.php';  // also starts session
require_once __DIR__ . '/check.php';               // auth check (already in /auth/)
require_once __DIR__ . '/../connections/ai_config.php';

$message = trim($_POST['message'] ?? '');
if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Empty message']);
    exit;
}

// Clear history command
if ($message === '__clear__') {
    $_SESSION['ai_support_history'] = [];
    echo json_encode(['success' => true]);
    exit;
}

// Build conversation history from session for context
$history = $_SESSION['ai_support_history'] ?? [];

// Keep last 6 exchanges (12 messages) to stay within token limits
if (count($history) > 12) {
    $history = array_slice($history, -12);
}

// Add the new user message
$history[] = ['role' => 'user', 'content' => $message];

$role      = ucfirst($user->role ?? 'user');
$name      = htmlspecialchars($user->first_name ?? 'there');
$class     = htmlspecialchars($user->class ?? '');
$subject   = $user->role === 'staff' ? htmlspecialchars($user->subject ?? '') : '';

$system = "You are EduBot, a friendly and knowledgeable AI support assistant for a Nigerian school web portal called SchoolApp. You help students, teachers (staff), and admins with any questions about using the platform.

Current user info:
- Name: {$name}
- Role: {$role}" . ($class ? "\n- Class: {$class}" : "") . ($subject ? "\n- Subject taught: {$subject}" : "") . "

The platform features include:
- Students: Take exams, view results & performance reviews, check exam history, chat with admin, WAEC practice
- Staff: Set exam questions (with AI generation), view their students, check exam scores/results
- Admin: Create user accounts, manage classes, create/manage exams, broadcast notifications, manage staff

Guidelines:
- Be warm, concise, and helpful. Use simple English.
- If you don't know something specific about THIS school's data (e.g. specific schedules), say you can't access live data but suggest where to look.
- Keep responses under 4 sentences unless a step-by-step answer is needed.
- You are NOT a general-purpose AI — always bring answers back to the school platform context.
- Never make up specific data like exam dates, scores, or user info.";

// Build messages array for Groq
$payload = json_encode([
    'model'    => GROQ_MODEL,
    'messages' => array_merge(
        [['role' => 'system', 'content' => $system]],
        $history
    ),
    'max_tokens'  => 350,
    'temperature' => 0.6,
]);

$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SchoolApp/1.0)',
    CURLOPT_FOLLOWLOCATION => true,
]);

$response  = curl_exec($ch);
$curl_err  = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_err || $http_code !== 200) {
    echo json_encode(['success' => false, 'message' => 'EduBot is unavailable right now. Please try again.']);
    exit;
}

$data  = json_decode($response, true);
$reply = trim($data['choices'][0]['message']['content'] ?? '');

if (!$reply) {
    echo json_encode(['success' => false, 'message' => 'No response from EduBot.']);
    exit;
}

// Save assistant reply to history
$history[] = ['role' => 'assistant', 'content' => $reply];
$_SESSION['ai_support_history'] = $history;

echo json_encode(['success' => true, 'reply' => $reply]);
