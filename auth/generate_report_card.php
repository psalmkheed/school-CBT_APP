<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../connections/db.php';
require __DIR__ . '/../auth/check.php';
require __DIR__ . '/../auth/fee_check.php';

use Mpdf\Mpdf;

if (!isset($_GET['student_id'])) {
    die("Student ID required");
}

$student_id = (int)$_GET['student_id'];

// Fee Check Restriction (Only for students and their guardians)
if (!in_array($_SESSION['role'], ['super', 'admin', 'staff'])) {
    if (!isFeeCleared($conn, $student_id)) {
        die("
            <div style='font-family: sans-serif; text-align: center; padding: 50px;'>
                <h1 style='color: #ef4444;'>Access Restricted</h1>
                <p style='color: #6b7280;'>Academic records for this student are withheld due to outstanding fee obligations.</p>
                <p style='color: #6b7280;'>Please contact the bursary department to clear all payments.</p>
                <button onclick='window.close()' style='padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;'>Close Window</button>
            </div>
        ");
    }
}
$session = $_GET['session'] ?? ($_SESSION['active_session'] ?? '');
$term = $_GET['term'] ?? ($_SESSION['active_term'] ?? '');

// Fetch Student Info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_OBJ);

if (!$student) {
    die("Student not found");
}

$sess = trim($session);
$trm = trim($term);

$term_variations = [$trm];
if (strpos($trm, 'First') !== false)
    $term_variations[] = str_replace('First', '1st', $trm);
if (strpos($trm, '1st') !== false)
    $term_variations[] = str_replace('1st', 'First', $trm);
if (strpos($trm, 'Second') !== false)
    $term_variations[] = str_replace('Second', '2nd', $trm);
if (strpos($trm, '2nd') !== false)
    $term_variations[] = str_replace('2nd', 'Second', $trm);
if (strpos($trm, 'Third') !== false)
    $term_variations[] = str_replace('Third', '3rd', $trm);
if (strpos($trm, '3rd') !== false)
    $term_variations[] = str_replace('3rd', 'Third', $trm);

$in_placeholders = implode(',', array_fill(0, count($term_variations), '?'));

// Fetch Results for this student in the requested session/term
$results_params = [$student_id, $sess];
foreach ($term_variations as $v)
    $results_params[] = $v;

$results_stmt = $conn->prepare("
    SELECT e.subject, e.exam_type, r.score, r.total_questions, r.percentage, r.taken_at,
           (SELECT ca_score FROM continuous_assessment ca 
            JOIN subjects s ON ca.subject_id = s.id 
            WHERE ca.student_id = r.user_id AND s.subject = e.subject AND TRIM(ca.term) IN ($in_placeholders) AND TRIM(ca.session_year) = ? LIMIT 1) as ca_score
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = ? AND TRIM(e.session) = ? AND TRIM(e.term) IN ($in_placeholders)
    ORDER BY e.subject ASC
");
// Prepare parameters for result_stmt
$ca_params = [];
foreach ($term_variations as $v)
    $ca_params[] = $v;
$ca_params[] = $sess;
$results_params = array_merge([$student_id, $sess], $term_variations, $ca_params);

// Wait, the subquery params order: ca.term IN (?), ca.session_year = ? => $ca_params
// Main query: WHERE r.user_id = ?, TRIM(e.session) = ?, TRIM(e.term) IN (?) => $student_id, $sess, $term_variations
// It is simpler to just inject the variations strings safely or bind properly.
$results_stmt = $conn->prepare("
    SELECT e.subject, e.exam_type, r.score, r.total_questions, r.percentage, r.taken_at,
           (SELECT ca_score FROM continuous_assessment ca 
            JOIN subjects s ON ca.subject_id = s.id 
            WHERE ca.student_id = r.user_id AND s.subject = e.subject AND TRIM(ca.term) IN ($in_placeholders) AND TRIM(ca.session_year) = TRIM(e.session) LIMIT 1) as ca_score
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = ? AND TRIM(e.session) = ? AND TRIM(e.term) IN ($in_placeholders)
    ORDER BY e.subject ASC
");
$results_params = array_merge($term_variations, [$student_id, $sess], $term_variations);
$results_stmt->execute($results_params);
$results = $results_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch Position in Class
$rank_stmt = $conn->prepare("
    SELECT r.user_id, AVG(percentage) as avg_p 
    FROM exam_results r 
    JOIN exams e ON r.exam_id = e.id 
    JOIN users u ON r.user_id = u.id
    WHERE TRIM(e.session) = ? AND TRIM(e.term) IN ($in_placeholders) AND u.class = ?
    GROUP BY r.user_id 
    ORDER BY avg_p DESC
");
$rank_params = array_merge([$sess], $term_variations, [$student->class]);
$rank_stmt->execute($rank_params);
$rankings = $rank_stmt->fetchAll(PDO::FETCH_ASSOC);

$position = 0;
foreach ($rankings as $index => $rank) {
    if ($rank['user_id'] == $student_id) {
        $position = $index + 1;
        break;
    }
}
$total_students = count($rankings);

function formatPosition($n)
{
    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    if ((($n % 100) >= 11) && (($n % 100) <= 13))
        return $n . 'th';
    else
        return $n . $ends[$n % 10];
}

// Attendance Stats
$att_stmt = $conn->prepare("SELECT status FROM attendance WHERE student_id = ? AND session_id = (SELECT id FROM sch_session WHERE session = ? AND term = ? LIMIT 1)");
$att_stmt->execute([$student_id, $session, $term]);
$attendance_records = $att_stmt->fetchAll(PDO::FETCH_OBJ);
$days_present = 0;
foreach ($attendance_records as $ar)
    if ($ar->status == 'present')
        $days_present++;
$total_days = count($attendance_records);
$attendance_pct = $total_days > 0 ? round(($days_present / $total_days) * 100) : 'N/A';

// Fetch School Config
$config = $conn->query("SELECT * FROM school_config LIMIT 1")->fetch(PDO::FETCH_OBJ);

// Calculate Totals
$total_weighted_percent = 0;
foreach($results as $r) {
    $ca_score = $r->ca_score ?: 0;
    $total_weighted_percent += ($ca_score + ($r->percentage * 0.6));
}
$avg_percent = count($results) > 0 ? ($total_weighted_percent / count($results)) : 0;

// Grade and Insight
function getGrade($p)
{
    if ($p >= 80)
        return ['A', 'Distinction', '#059669'];
    if ($p >= 70)
        return ['B', 'Very Good', '#10b981'];
    if ($p >= 60)
        return ['C', 'Credit', '#3b82f6'];
    if ($p >= 50)
        return ['D', 'Pass', '#f59e0b'];
    if ($p >= 40)
        return ['E', 'Fair', '#ef4444'];
    return ['F', 'Fail', '#b91c1c'];
}

$final_grade = getGrade($avg_percent);

function getInsight($p)
{
    if ($p >= 80)
        return "Outstanding performance! The student demonstrates exceptional mastery across all subjects.";
    if ($p >= 70)
        return "Great results. The student shows strong academic potential and steady progress.";
    if ($p >= 50)
        return "Satisfactory performance. Some areas need more focus to reach a higher potential.";
    return "Needs improvement. Recommend additional support and consistent study habits.";
}

$primary = $config->school_primary ?? '#10b981';
$secondary = $config->school_secondary ?? '#065f46';

$html = '
<style>
    @page { margin: 0; }
    body { font-family: "Helvetica", sans-serif; color: #1f2937; margin: 0; padding: 0; background: #fff; }
    .bg-stripe { height: 10px; background: linear-gradient(to right, ' . $primary . ', ' . $secondary . '); }
    .page-content { padding: 40px; }
    
    /* Header Section */
    .header-table { width: 100%; border-bottom: 2px solid #f3f4f6; padding-bottom: 20px; }
    .logo-container { width: 60px; }
    .school-info { padding-left: 20px; }
    .school-name { font-size: 26px; font-weight: 900; color: ' . $secondary . '; margin: 0; }
    .school-contact { font-size: 11px; color: #6b7280; font-weight: 600; margin-top: 4px; }
    
    .report-badge { 
        background: #f0fdf4; color: #166534; padding: 8px 15px; border-radius: 50px; 
        font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;
        display: inline-block; margin-top: 20px;
    }

    /* Student Info Grid */
    .info-grid { width: 100%; margin-top: 30px; }
    .info-card { background: #f9fafb; border: 1px solid #f3f4f6; padding: 15px; border-radius: 12px; }
    .label { font-size: 9px; font-weight: 800; color: #9ca3af; text-transform: uppercase; margin-bottom: 3px; }
    .value { font-size: 13px; font-weight: 700; color: #374151; }

    /* Results Table */
    .results-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
    .results-table th { 
        padding: 12px 15px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;
        text-align: left; font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;
    }
    .results-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 12px; font-weight: 600; }
    .subject-col { color: #1e293b; font-weight: 700; }
    
    /* Stats Footer */
    .stats-container { margin-top: 30px; width: 100%; }
    .stat-box { border: 1px solid #e5e7eb; border-radius: 15px; padding: 20px; text-align: center; }
    .stat-big { font-size: 24px; font-weight: 900; color: ' . $secondary . '; }
    .stat-sub { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; }

    .comment-area { margin-top: 30px; padding: 20px; background: #fefce8; border-left: 4px solid #facc15; border-radius: 0 12px 12px 0; }
    .comment-title { font-size: 11px; font-weight: 800; color: #854d0e; text-transform: uppercase; margin-bottom: 5px; }
    .comment-text { font-size: 13px; font-style: italic; line-height: 1.5; color: #713f12; }

    .footer { position: fixed; bottom: 40px; left: 40px; right: 40px; }
    .signature-row { width: 100%; }
    .signature-box { border-top: 1px solid #e5e7eb; padding-top: 10px; text-align: center; width: 200px; }
</style>

<div class="bg-stripe"></div>
<div class="page-content">
    <table class="header-table">
        <tr>
            <td class="logo-container">
                <img src="' . ($config->school_logo ? "../" . ltrim($config->school_logo, '/') : '') . '" width="60" />
            </td>
            <td class="school-info">
                <h1 class="school-name">' . htmlspecialchars(strtoupper($config->school_name)) . '</h1>
                <p class="school-contact">' . htmlspecialchars($config->school_address) . ' | ' . htmlspecialchars($config->school_email) . ' | ' . htmlspecialchars($config->school_phone_number) . '</p>
                <div class="report-badge">Academic Achievement Report - ' . $term . ' ' . $session . '</div>
            </td>
        </tr>
    </table>

    <table class="info-grid" cellspacing="10">
        <tr>
            <td class="info-card">
                <div class="label">Student Full Name</div>
                <div class="value">' . htmlspecialchars(strtoupper($student->first_name . " " . $student->surname)) . '</div>
            </td>
            <td class="info-card">
                <div class="label">Admission Number</div>
                <div class="value">' . $student->user_id . '</div>
            </td>
            <td class="info-card">
                <div class="label">Current Class</div>
                <div class="value">' . htmlspecialchars(strtoupper($student->class)) . '</div>
            </td>
        </tr>
    </table>

    <table class="results-table">
        <thead>
            <tr>
                <th>Subject Name</th>
                <th style="text-align: center;">CA (40)</th>
                <th style="text-align: center;">Exam (60)</th>
                <th style="text-align: center;">Total</th>
                <th style="text-align: center;">Grade</th>
            </tr>
        </thead>
        <tbody>';

foreach($results as $r) {
    $ca_score = $r->ca_score ?: 0;
    $exam_portion = round(($r->percentage * 0.6), 1);
    $final_total = round($ca_score + ($r->percentage * 0.6));
    $g = getGrade($final_total);

    $html .= '
            <tr>
                <td class="subject-col">' . htmlspecialchars($r->subject) . '</td>
                <td style="text-align: center; color: #64748b;">' . $ca_score . '</td>
                <td style="text-align: center; color: #64748b;">' . $exam_portion . '</td>
                <td style="text-align: center; font-weight: 800; color: #1e293b;">' . $final_total . '%</td>
                <td style="text-align: center;"><span style="color: ' . $g[2] . '; font-weight: 900;">' . $g[0] . '</span></td>
            </tr>';
}

if(empty($results)) {
    $html .= '<tr><td colspan="5" style="text-align:center; padding: 60px; color: #94a3b8; font-style: italic;">No academic records found for this period.</td></tr>';
}

$html .= '
        </tbody>
    </table>

    <table class="stats-container" cellspacing="10">
        <tr>
            <td width="25%" class="stat-box">
                <div class="stat-big">' . count($results) . '</div>
                <div class="stat-sub">Subjects</div>
            </td>
            <td width="25%" class="stat-box">
                <div class="stat-big">' . round($avg_percent) . '%</div>
                <div class="stat-sub">Average</div>
            </td>
            <td width="25%" class="stat-box">
                <div class="stat-big" style="color: ' . $final_grade[2] . '">' . $final_grade[0] . '</div>
                <div class="stat-sub">Final Grade</div>
            </td>
            <td width="25%" class="stat-box">
                <div class="stat-big">' . ($position ? formatPosition($position) : 'N/A') . '</div>
                <div class="stat-sub">Position Out of ' . $total_students . '</div>
            </td>
        </tr>
    </table>

    <div class="comment-area">
        <div class="comment-title">Performance Insight & Remark</div>
        <div class="comment-text">"' . getInsight($avg_percent) . '"</div>
    </div>

    <table class="info-grid" cellspacing="10" style="margin-top: 20px;">
        <tr>
             <td class="info-card" width="33%">
                <div class="label">Attendance %</div>
                <div class="value">' . $attendance_pct . '% (' . $days_present . '/' . $total_days . ' Days)</div>
            </td>
            <td class="info-card" width="67%">
                <div class="label">Academic Summary</div>
                <div class="value">' . $student->first_name . ' performed better than ' . max(0, round((($total_students - $position) / max(1, $total_students)) * 100)) . '% of class peers.</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <table class="signature-row">
            <tr>
                <td>
                    <div class="signature-box">
                        <div class="label">Class Teacher</div>
                        <div style="height: 40px;"></div>
                        <div class="value">Signature & Date</div>
                    </div>
                </td>
                <td style="text-align: right;">
                    <div class="signature-box" style="float: right;">
                        <div class="label">School Principal</div>
                        <img src="' . ($config->signature ? "../uploads/signature/" . $config->signature : '') . '" height="40" style="margin-top: 5px;"/>
                        <div class="value">' . htmlspecialchars($config->school_name) . '</div>
                    </div>
                </td>
            </tr>
        </table>
        <p style="text-align: center; font-size: 9px; color: #cbd5e1; margin-top: 30px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">
            Certified Academic Document • Generated on ' . date("F j, Y") . '
        </p>
    </div>
</div>';

try {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
    ]);

    $mpdf->SetTitle('Official Report Card - ' . $student->first_name . ' ' . $student->surname);
    $mpdf->WriteHTML($html);
    $mpdf->Output('Report_Card_'.$student->surname.'.pdf', 'I');
} catch (\Mpdf\MpdfException $e) {
    echo "PDF Generation Error: " . $e->getMessage();
}
