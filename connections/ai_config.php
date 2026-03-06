<?php
// ── AI Configuration (Groq — Free & Fast) ───────────────────────────────
// Get your FREE API key from: https://console.groq.com → API Keys
// Paste your key below. That's it — all AI features use this one key.

// Load API Key from .env if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    if (isset($env['GROQ_API_KEY'])) {
        define('GROQ_API_KEY', $env['GROQ_API_KEY']);
    }
}

// Fallback to environment variable or empty string
if (!defined('GROQ_API_KEY')) {
    define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
}
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL',   'llama-3.3-70b-versatile'); // Free: 6000 tokens/min

// Sends a prompt to Groq and returns the response text (or null on failure)
function groq_ask(string $system_prompt, string $user_prompt, int $max_tokens = 500): ?string {
    $payload = json_encode([
        'model'    => GROQ_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user',   'content' => $user_prompt],
        ],
        'max_tokens'  => $max_tokens,
        'temperature' => 0.7,
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
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
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
        error_log("Groq API Error [{$http_code}]: {$curl_err} | Response: {$response}");
        return null;
    }

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}
