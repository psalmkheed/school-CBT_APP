<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if ($user->role !== 'staff') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'generate') {
    $num = (int)($_POST['num_questions'] ?? 5);
    $diff = $_POST['difficulty'] ?? 'medium';
    $topic = trim($_POST['topic'] ?? '');
    $exam_id = (int)($_POST['exam_id'] ?? 0);

    if (empty($topic) || empty($exam_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Topic/Context or Target Exam is empty.']);
        exit;
    }

    // Fetch Exam Details to get the Class context
    $chk_stmt = $conn->prepare("SELECT class, subject FROM exams WHERE id = ?");
    $chk_stmt->execute([$exam_id]);
    $examData = $chk_stmt->fetch(PDO::FETCH_ASSOC);
    $examClass = $examData['class'] ?? 'General';
    $examSubject = $examData['subject'] ?? 'General';

    // Fetch Groq API Key
    $config_stmt = $conn->query("SELECT groq_api_key FROM school_config LIMIT 1");
    $config = $config_stmt->fetch(PDO::FETCH_ASSOC);
    $api_key = $config['groq_api_key'] ?? '';

    if (empty($api_key)) {
        echo json_encode(['status' => 'error', 'message' => 'AI System not configured. Admin must set Groq API key in Settings.']);
        exit;
    }

    // Prepare prompt for Groq
    $system_prompt = "You are a stringent academic examiner. Generate exactly $num multiple-choice questions on the topic '$topic' for the subject '$examSubject'.
    CRITICAL CONSTRAINT: You must perfectly scale the vocabulary, concepts, and depth strictly for students in: $examClass. 
    Note: Classes like 'Primary' are elementary. 'JSS1-JSS3' are Junior High (Grades 7-9). 'SS1-SS3' (e.g. SS2) are SENIOR Secondary High School (Grades 10-12 / Ages 15-18) and therefore require advanced, rigorous examination standards reminiscent of college-prep or WAEC standards!
    The explicitly requested difficulty tier is: '$diff'. (Hard means very challenging for this specific class level).
    Format the output as pure JSON containing an array of objects. Each object must have the following keys: 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer' (which must be 'A', 'B', 'C', or 'D' mapping to the EXACT key letter string). Do not include any other text, markdown blocks, or explanations. Just the JSON array.";

    $data = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            [
                "role" => "system",
                "content" => $system_prompt
            ]
        ],
        "temperature" => 0.7,
        "response_format" => ["type" => "json_object"]
    ];
    // Notice: Groq API doesn't fully support 'json_object' on all models, adding strong instructions to ensure JSON output.
    $data['messages'][0]['content'] .= " Reply with ONLY a valid JSON object with a single key 'questions' holding the array.";

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ]);
    
    // Ignore SSL locally for dev
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        // Fallback to mock if API fails
        $error_msg = json_decode($response, true)['error']['message'] ?? 'Network Error';
        echo json_encode(['status' => 'error', 'message' => 'Groq API Error: ' . $error_msg]);
        exit;
    }

    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    
    // Extract JSON from the content
    $parsed = json_decode($content, true);
    if (!$parsed || !isset($parsed['questions'])) {
        // Try to strip potential markdown blocks
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $parsed = json_decode(trim($content), true);
    }

    if ($parsed && isset($parsed['questions']) && is_array($parsed['questions'])) {
        echo json_encode([
            'status' => 'success',
            'questions' => $parsed['questions']
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to parse AI response. It did not return valid JSON.', 'raw' => $content]);
        exit;
    }
}

if ($action === 'save_bulk') {
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $questions_json = $_POST['questions'] ?? '[]';
    
    $questions = json_decode($questions_json, true);

    if (empty($exam_id) || empty($questions) || !is_array($questions)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data. Exam ID or Questions missing.']);
        exit;
    }

    // Verify ownership of the exam
    $teacher_name = $user->first_name . ' ' . $user->surname;
    $chk = $conn->prepare("SELECT id FROM exams WHERE id = :id AND subject_teacher = :teacher");
    $chk->execute([':id' => $exam_id, ':teacher' => $teacher_name]);
    if ($chk->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Exam not found or you do not have permission.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Get the current highest question_number for this exam
        $stmt_num = $conn->prepare("SELECT MAX(CAST(question_number AS UNSIGNED)) FROM questions WHERE exam_id = :exam_id");
        $stmt_num->execute([':exam_id' => $exam_id]);
        $currentMax = (int)$stmt_num->fetchColumn();

        $inserted = 0;
        $insert_stmt = $conn->prepare("INSERT INTO questions (exam_id, question_number, question_text, option_a, option_b, option_c, option_d, correct_answer, question_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'mcq')");

        foreach ($questions as $q) {
            $currentMax++;
            $insert_stmt->execute([
                $exam_id,
                (string)$currentMax,
                $q['question_text'],
                $q['option_a'],
                $q['option_b'],
                $q['option_c'],
                $q['option_d'],
                $q['correct_answer']
            ]);
            $inserted++;
        }

        // Update exam setup status if necessary (check total set)
        $num_q_stmt = $conn->prepare("SELECT num_quest FROM exams WHERE id = :id");
        $num_q_stmt->execute([':id' => $exam_id]);
        $total_needed = (int)$num_q_stmt->fetchColumn();

        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE exam_id = :id");
        $count_stmt->execute([':id' => $exam_id]);
        $total_set = (int)$count_stmt->fetchColumn();

        if ($total_set >= $total_needed) {
            $upd = $conn->prepare("UPDATE exams SET exam_status = 'ready' WHERE id = :id AND exam_status = 'set up'");
            $upd->execute([':id' => $exam_id]);
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'inserted' => $inserted]);

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
} elseif ($action === 'generate_lesson') {
    $class = trim($_POST['class'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $duration = trim($_POST['duration'] ?? '45');

    if (empty($class) || empty($subject) || empty($topic)) {
        echo json_encode(['status' => 'error', 'message' => 'Class, Subject, and Topic are required.']);
        exit;
    }

    // Fetch Groq API Key
    $config_stmt = $conn->query("SELECT groq_api_key FROM school_config LIMIT 1");
    $config = $config_stmt->fetch(PDO::FETCH_ASSOC);
    $api_key = $config['groq_api_key'] ?? '';

    if (empty($api_key)) {
        echo json_encode(['status' => 'error', 'message' => 'AI System not configured. Admin must set Groq API key in Settings.']);
        exit;
    }

    $system_prompt = "You are a master educator and curriculum expert. Generate a highly structured, engaging, and professional Lesson Plan for the following:
Subject: $subject
Grade/Class Level: $class
Topic: $topic
Duration: $duration minutes

The lesson plan should strictly follow this format (use clear Markdown headings):
1. Lesson Objectives (SMART goals)
2. Materials / Resources Needed
3. Introduction / Hook (approx. 5-10 mins)
4. Main Activities / Instruction (breakdown of the lesson)
5. Differentiation (how to support struggling vs advanced students)
6. Assessment / Checking for Understanding
7. Conclusion / Wrap-up
8. Homework / Extension Activity

Provide only the text content of the lesson plan. No casual conversational intro or outro. Be thorough and practical.";

    $data = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            [
                "role" => "system",
                "content" => $system_prompt
            ]
        ],
        "temperature" => 0.7
    ];

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ]);
    
    // Ignore SSL locally for dev
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        $error_msg = json_decode($response, true)['error']['message'] ?? 'Network Error';
        echo json_encode(['status' => 'error', 'message' => 'Groq API Error: ' . $error_msg]);
        exit;
    }

    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';

    if ($content) {
        echo json_encode([
            'status' => 'success',
            'content' => trim($content)
        ]);
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Failed to generate output from AI.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
