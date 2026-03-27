<?php
require '../../connections/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guardian') {
    header("Location: {$base}auth/login.php");
    exit();
}

$guardian_id = $_SESSION['user_id'];
$student_internal_id = intval($_GET['id'] ?? 0);

// Verify this ward belongs to guardian
$check = $conn->prepare("SELECT id FROM guardian_wards WHERE guardian_id = ? AND student_id = ?");
$check->execute([$guardian_id, $student_internal_id]);

if ($check->rowCount() === 0) {
    echo "<h2 class='p-8 text-center text-red-500 font-bold'>Unauthorized Access to this student's records.</h2>";
    exit;
}

// Fetch Student Data
$stu_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stu_stmt->execute([$student_internal_id]);
$student = $stu_stmt->fetch(PDO::FETCH_OBJ);

$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Fetch Exams Results
$res_stmt = $conn->prepare("
    SELECT r.*, e.exam_type, e.subject
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = ? AND e.session = ? AND e.term = ?
    ORDER BY r.taken_at DESC
");
$res_stmt->execute([$student_internal_id, $active_session, $active_term]);
$results = $res_stmt->fetchAll(PDO::FETCH_OBJ);

$config_stmt = $conn->query("SELECT school_name, school_logo, school_primary FROM school_config LIMIT 1");
$config = $config_stmt->fetch(PDO::FETCH_OBJ);
?>
<div class="fadeIn w-full max-w-5xl mx-auto p-4 md:p-8 pb-20">
        
        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-8">
            <div class="flex items-center gap-4 w-full md:w-auto">
                <button onclick="goHome()" class="md:hidden w-12 h-12 shrink-0 rounded-2xl flex items-center justify-center text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all cursor-pointer border border-gray-100">
                    <i class="bx bx-arrow-left-stroke text-3xl"></i>
                </button>
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-2xl shadow-inner border-2 border-white shrink-0">
                    <?= strtoupper(substr($student->first_name, 0, 1) . substr($student->surname, 0, 1)) ?>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 leading-tight"><?= htmlspecialchars($student->first_name . ' ' . $student->surname) ?></h1>
                    <p class="font-semibold text-gray-500 text-sm md:text-base">Class: <?= htmlspecialchars($student->class) ?></p>
                </div>
            </div>
            <div class="md:ml-auto w-full md:w-auto">
                <button onclick="window.open('<?= $base ?>auth/generate_report_card.php?student_id=<?= $student->id ?>', '_blank', 'width=800,height=1000')" class="w-full md:w-auto bg-gray-900 hover:bg-black text-white px-5 py-3 md:py-2.5 rounded-xl font-bold shadow-lg shadow-gray-200 transition flex items-center justify-center gap-2">
                    <i class="bx bx-food-menu"></i> Generate Report Card
                </button>
            </div>
        </div>

        <h2 class="text-lg font-semibold tracking-widest text-gray-400 uppercase mb-4">Exam History</h2>
        
        <?php if(empty($results)): ?>
            <div class="bg-white border border-gray-100 rounded-2xl p-8 text-center shadow-sm">
                <i class="bx bx-book-content text-5xl text-gray-200 mb-3 block"></i>
                <h3 class="text-lg font-bold text-gray-800">No Exams Taken</h3>
                <p class="text-sm font-medium text-gray-500 max-w-sm mx-auto">Results will populate here once your ward completes CBT assessments.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($results as $res): 
                    $pct = ($res->score / max(1, $res->total_questions)) * 100;
                    $status = $pct >= 50 ? 'passed' : 'failed';
                ?>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-500"><?= htmlspecialchars($res->exam_type) ?></span>
                            <?php if($status === 'passed'): ?>
                                <span class="bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-widest border border-emerald-100">Passed</span>
                            <?php else: ?>
                                <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-widest border border-red-100">Failed</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($res->subject) ?></h3>
                    </div>
                    <div class="mt-6 flex items-end justify-between border-t border-gray-50 pt-4">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Score</p>
                            <p class="text-2xl font-semibold <?= $status === 'passed' ? 'text-emerald-500' : 'text-red-500' ?>"><?= $res->score ?> <span class="text-sm text-gray-400 font-bold">/ <?= $res->total_questions ?></span></p>
                        </div>
                        <p class="text-xs font-semibold text-gray-400"><?= date('M d, Y', strtotime($res->taken_at)) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
