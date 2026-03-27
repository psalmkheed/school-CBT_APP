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

$session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch Attendance
$att_stmt = $conn->prepare("
    SELECT *
    FROM attendance
    WHERE student_id = ? AND session_id = ?
    ORDER BY attendance_date DESC
");
$att_stmt->execute([$student_internal_id, $session_id]);
$attendance = $att_stmt->fetchAll(PDO::FETCH_OBJ);

$config_stmt = $conn->query("SELECT school_name, school_logo, school_primary FROM school_config LIMIT 1");
$config = $config_stmt->fetch(PDO::FETCH_OBJ);

// Calculate Stats
$total_days = count($attendance);
$present = 0;
$absent = 0;
$late = 0;

foreach ($attendance as $record) {
    if ($record->status === 'present') $present++;
    if ($record->status === 'absent') $absent++;
    if ($record->status === 'late') $late++;
}

$attendance_rate = $total_days > 0 ? round(($present / $total_days) * 100) : 100;
?>
<div class="fadeIn w-full max-w-5xl mx-auto p-4 md:p-8 pb-20">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6 mb-12">
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
            
            <div class="flex items-center justify-between md:justify-start gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex-wrap">
                <div class="text-center px-4 border-r border-gray-100 flex-1 md:flex-none">
                    <p class="text-[10px] font-semibold uppercase text-gray-400">Total</p>
                    <p class="text-2xl font-semibold text-gray-800"><?= $total_days ?></p>
                </div>
                <div class="text-center px-4 border-r border-gray-100 flex-1 md:flex-none">
                    <p class="text-[10px] font-semibold uppercase text-gray-400">Present</p>
                    <p class="text-2xl font-semibold text-emerald-600"><?= $present ?></p>
                </div>
                <div class="text-center px-4 border-r border-gray-100 flex-1 md:flex-none">
                    <p class="text-[10px] font-semibold uppercase text-gray-400">Absent</p>
                    <p class="text-2xl font-semibold text-red-600"><?= $absent ?></p>
                </div>
                <div class="text-center px-4 flex-1 md:flex-none">
                    <p class="text-[10px] font-semibold uppercase text-gray-400">Late</p>
                    <p class="text-2xl font-semibold text-amber-500"><?= $late ?></p>
                </div>
            </div>
        </div>

        <h2 class="text-lg font-semibold tracking-widest text-gray-400 uppercase mb-4">Daily Logs</h2>
        
        <?php if(empty($attendance)): ?>
            <div class="bg-white border border-gray-100 rounded-2xl p-8 text-center shadow-sm">
                <i class="bx bx-calendar-x text-5xl text-gray-200 mb-3 block"></i>
                <h3 class="text-lg font-bold text-gray-800">No Attendance Records Yet</h3>
                <p class="text-sm font-medium text-gray-500 max-w-sm mx-auto">Once the class teacher marks the register, it will show up here.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach($attendance as $log): 
                    $bg = 'bg-gray-50';
                    $txt = 'text-gray-500';
                    $icon = 'bx-calendar';
                    
                    if ($log->status === 'present') {
                        $bg = 'bg-emerald-50';
                        $txt = 'text-emerald-600';
                        $icon = 'bx-check-circle';
                    } elseif ($log->status === 'absent') {
                        $bg = 'bg-red-50';
                        $txt = 'text-red-500';
                        $icon = 'bx-x-circle';
                    } elseif ($log->status === 'late') {
                        $bg = 'bg-amber-50';
                        $txt = 'text-amber-500';
                        $icon = 'bx-time-five';
                    }
                ?>
                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] flex items-center justify-between group hover:shadow-md transition">
                    <div>
                        <p class="text-sm font-bold text-gray-800"><?= date('D, M d, Y', strtotime($log->attendance_date)) ?></p>
                        <p class="text-[10px] uppercase font-bold tracking-widest <?= $txt ?> mt-0.5"><?= htmlspecialchars($log->status) ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $bg ?> <?= $txt ?>">
                        <i class="bx <?= $icon ?> text-xl"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
