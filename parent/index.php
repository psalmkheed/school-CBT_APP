<?php
require '../connections/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guardian') {
    header("Location: {$base}auth/login.php");
    exit();
}

$guardian_id = $_SESSION['user_id'];
$guardian_name = $_SESSION['first_name'] ?? 'Guardian';
$guardian_username = $_SESSION['username'] ?? '';

// Expire old hall passes system-wide
$conn->exec("UPDATE hall_passes SET status = 'expired' WHERE status = 'active' AND expires_at < CURRENT_TIMESTAMP");

$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';
$session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch Linked Wards
$wards_stmt = $conn->prepare("
    SELECT w.id as link_id, w.created_at as linked_on,
           u.id as student_internal_id, u.user_id as admission_no, u.first_name, u.surname, u.class, u.profile_photo,
           (SELECT COUNT(*) FROM attendance WHERE student_id = u.id AND session_id = ?) as total_days,
           (SELECT COUNT(*) FROM attendance WHERE student_id = u.id AND status = 'present' AND session_id = ?) as present_days
    FROM guardian_wards w
    JOIN users u ON w.student_id = u.id
    WHERE w.guardian_id = ?
");
$wards_stmt->execute([$session_id, $session_id, $guardian_id]);
$wards = $wards_stmt->fetchAll(PDO::FETCH_OBJ);

foreach($wards as $w) {
    $hist_stmt = $conn->prepare("
        SELECT r.percentage 
        FROM exam_results r
        JOIN exams e ON r.exam_id = e.id
        WHERE r.user_id = ? AND e.session = ? AND e.term = ?
        ORDER BY r.taken_at DESC LIMIT 5
    ");
    $hist_stmt->execute([$w->student_internal_id, $active_session, $active_term]);
    $w->trends = array_reverse($hist_stmt->fetchAll(PDO::FETCH_COLUMN));

    // Check for active Hall Pass
    $pass_stmt = $conn->prepare("
        SELECT p.*, u.first_name as staff_fname, u.surname as staff_lname 
        FROM hall_passes p
        JOIN users u ON p.issued_by = u.id
        WHERE p.student_id = ? AND p.status = 'active' AND p.expires_at > CURRENT_TIMESTAMP
        ORDER BY p.id DESC LIMIT 1
    ");
    $pass_stmt->execute([$w->student_internal_id]);
    $w->active_pass = $pass_stmt->fetch(PDO::FETCH_OBJ);
}

// Calculate Total Outstanding Fees for all wards combined
$total_outstanding_stmt = $conn->prepare("
    SELECT SUM(f.amount_due - f.amount_paid) as outstanding
    FROM guardian_wards w
    JOIN users u ON w.student_id = u.id
    JOIN finance_student_fees f ON u.user_id = f.student_id
    WHERE w.guardian_id = ? AND f.status != 'paid'
");
$total_outstanding_stmt->execute([$guardian_id]);
$total_family_outstanding = $total_outstanding_stmt->fetchColumn() ?: 0;

$config_stmt = $conn->prepare('SELECT school_name, school_logo, school_tagline, school_primary, account_details FROM school_config');
$config_stmt->execute();
$config = $config_stmt->fetch(PDO::FETCH_OBJ);

// Fetch Notifications for Guardian
$notif_stmt = $conn->prepare("SELECT * FROM broadcast WHERE recipient = ? ORDER BY created_at DESC LIMIT 10");
$notif_stmt->execute([$guardian_username]);
$notifications = $notif_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch Recent Payments for all linked wards
$recent_pays_stmt = $conn->prepare("
    SELECT p.*, c.name as fee_name, u.first_name, u.surname
    FROM finance_payments p
    JOIN finance_student_fees f ON p.student_fee_id = f.id
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    JOIN users u ON f.student_id = u.user_id
    JOIN guardian_wards w ON u.id = w.student_id
    WHERE w.guardian_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recent_pays_stmt->execute([$guardian_id]);
$recent_pays = $recent_pays_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch General Announcements
$ann_stmt = $conn->prepare("SELECT * FROM announcements WHERE recipient IN ('all', 'parent') AND status = 'active' ORDER BY created_at DESC LIMIT 5");
$ann_stmt->execute();
$announcements_list = $ann_stmt->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= strtoupper($config->school_name) ?> Parent Portal - Dashboard</title>
    <meta name="theme-color" content="<?= htmlspecialchars($config->school_primary ?? '#4f46e5') ?>">
          <link rel="icon" type="image" href="<?= $base ?>../uploads/school_logo/">
    <link href="../src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="../src/input.css" rel="stylesheet">
    <link href="../src/boxicons.css" rel="stylesheet">
    <script src="../src/jquery.js"></script>
    <script>window.APP_URL = "<?= $base ?>";</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar-link.active {
            background-color: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-50 flex font-sans select-none min-h-screen">



    <!-- Sidebar -->
    <aside id="sideBar" class="bg-white w-72 h-screen fixed -left-72 md:left-0 z-40 border-r border-gray-100 flex flex-col justify-between transition-all duration-300">
        <div>
            <div class="invisible h-20 px-4 flex md:visible items-center border-b border-gray-100 gap-3 pt-12 md:pt-0">
                <?php if(!empty($config->school_logo)): ?>
                    <img src="<?= $base . ltrim($config->school_logo ?? '', '/') ?>" class="h-10 object-contain">
                <?php else: ?>
                    <i class="bx bxs-school text-indigo-600 text-3xl"></i>
                <?php endif; ?>
                <h2 class="font-black text-gray-800 text-xl tracking-tight leading-none"><?= htmlspecialchars(explode(' ', strtoupper($config->school_name ?? 'School'))[0]) ?><br><span class="text-sm text-gray-600 font-medium"><?= $config->school_tagline ?? 'Portal'?></span></h2>
            </div>
            
            <nav class="p-4 space-y-2 md:mt-4 mt-0">
                <a href="index.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-xl transition-all">
                    <i class="bx bx-home-alt text-xl"></i> Dashboard
                </a>
            </nav>
        </div>
        
        <div class="p-4 border-t border-gray-100">
            <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 font-bold transition-all">
                <i class="bx bx-arrow-out-right-square-half text-xl"></i> Sign Out
            </a>
            <div class="mt-4 flex items-center gap-3 px-4">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold">
                    <?= strtoupper(substr($guardian_name, 0, 1)) ?>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($guardian_name) ?></p>
                    <p class="text-xs text-gray-500">Parent</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden"></div>
    <div id="dropdownOverlay"></div>

    <!-- Main Content -->
    <main class="w-full md:ml-72 min-h-screen">
        <?php require 'components/navbar.php'; ?>
        <div id="mainContent" class="w-full pt-5 md:pt-0">
            <div class="p-4 md:p-10 max-w-7xl mx-auto pb-20 md:pb-10">
            
            <!-- Banner -->
            <div class="bg-gradient-to-r from-indigo-600 to-blue-500 rounded-3xl p-8 mb-8 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
                <i class="bx bxs-face absolute -right-4 -bottom-4 text-9xl text-white opacity-10 transform -rotate-12"></i>
                <h1 class="text-3xl font-semibold mb-2 relative z-10">Welcome, <?= htmlspecialchars($guardian_name) ?>!</h1>
                <p class="text-indigo-100 font-medium relative z-10">Monitor your ward's academic records and financial obligations securely.</p>
                
                <?php if($total_family_outstanding > 0): ?>
                <div class="mt-8 bg-black/20 backdrop-blur-md rounded-2xl p-4 inline-flex items-center gap-4 relative z-10 border border-white/10">
                    <div class="bg-red-500 w-12 h-12 rounded-xl flex items-center justify-center shadow-lg"><i class="bx bx-receipt pr-0 text-white text-2xl"></i></div>
                    <div>
                        <p class="text-xs uppercase font-bold tracking-widest text-indigo-200">Total Family Arrears</p>
                        <p class="text-xl font-semibold">₦<?= number_format($total_family_outstanding, 2) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mt-12">
                <!-- Left Column -->
                <div class="lg:col-span-8 space-y-12">
                    <section>
                        <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-800">My Linked Wards</h2>
                <button onclick="document.getElementById('linkChildModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold transition flex items-center gap-2 text-sm shadow-md shadow-indigo-200">
                    <i class="bx bx-link"></i> Link a Child
                </button>
            </div>

            <?php if (!empty($wards) && $total_family_outstanding > 0): ?>
                <div class="mb-10 bg-gradient-to-br from-red-600 to-rose-700 rounded-3xl p-6 md:p-8 text-white shadow-xl shadow-rose-200 flex flex-col md:flex-row items-center justify-between gap-6 overflow-hidden relative">
                    <!-- Decorative Circle -->
                    <div class="absolute -top-10 -right-10 size-40 bg-white/10 rounded-full blur-3xl"></div>
                    
                    <div class="flex items-center gap-6 relative z-10">
                        <div class="size-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 shadow-inner">
                            <i class="bx bx-receipt text-3xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold">Family Outstanding Balance</h2>
                            <p class="text-white/80 font-medium text-sm mt-1">Total pending fees for all linked wards.</p>
                        </div>
                    </div>

                    <div class="flex flex-col md:items-end relative z-10">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-white/60 mb-1">Due Amount</p>
                        <p class="text-3xl font-semibold mb-4">₦<?= number_format($total_family_outstanding, 2) ?></p>
                        <button onclick="showHowToPay()"
                            class="bg-white text-rose-700 hover:bg-rose-50 px-8 py-3 rounded-xl font-semibold shadow-lg transition-all flex items-center gap-2 transform active:scale-95 cursor-pointer">
                            <i class="bx bx-wallet text-xl"></i> How to Pay
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(empty($wards)): ?>
                <div class="bg-white border border-gray-100 rounded-3xl p-12 text-center shadow-sm">
                    <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="bx bx-user-plus text-4xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Wards Linked Yet</h3>
                    <p class="text-gray-500 font-medium mb-6 max-w-sm mx-auto">Link your child using their Admission Number / Registration ID to unlock their academic and financial records.</p>
                    <button onclick="document.getElementById('linkChildModal').classList.remove('hidden')" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-6 py-3 rounded-xl font-bold transition">
                        Link Child Now
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-3">
                    <?php foreach($wards as $ward): ?>
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] hover:shadow-lg transition-transform hover:-translate-y-1">
                            <div class="flex items-center gap-4 border-b border-gray-50 pb-5 mb-5">
                                <?php if(!empty($ward->profile_photo)): ?>
                                    <img src="<?= $base ?>uploads/profile_photos/<?= htmlspecialchars($ward->profile_photo) ?>" class="w-16 h-16 rounded-2xl object-cover shadow-sm">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex flex-col items-center justify-center shadow-inner">
                                        <i class="bx bx-user text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($ward->first_name . ' ' . $ward->surname) ?></h3>
                                    <p class="text-sm font-semibold text-gray-500 mt-0.5"><?= htmlspecialchars($ward->class) ?> &bull; <?= htmlspecialchars($ward->admission_no) ?></p>
                                </div>
                                
                                <!-- Attendance Ring -->
                                <div class="ml-auto flex items-center gap-3">
                                    <?php 
                                    $total_a = $ward->total_days ?: 0;
                                    $present_a = $ward->present_days ?: 0;
                                    $percent_a = $total_a > 0 ? round(($present_a / $total_a) * 100) : 0;
                                    $ringColor = "text-emerald-500";
                                    if($percent_a < 70) $ringColor = "text-red-500";
                                    elseif($percent_a < 90) $ringColor = "text-amber-500";
                                    ?>
                                    <div class="relative size-11" data-tippy-content="Attendance: <?= $percent_a ?>% (<?= $present_a ?>/<?= $total_a ?> days)">
                                        <svg class="size-full -rotate-90" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="18" cy="18" r="16" fill="none" class="stroke-current text-gray-100" stroke-width="3"></circle>
                                            <circle cx="18" cy="18" r="16" fill="none" class="stroke-current <?= $ringColor ?>" stroke-width="3" stroke-dasharray="100" stroke-dashoffset="<?= 100 - $percent_a ?>" stroke-linecap="round" style="transition: stroke-dashoffset 1s ease-out;"></circle>
                                        </svg>
                                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                            <span class="text-[9px] font-semibold text-gray-800"><?= $percent_a ?>%</span>
                                        </div>
                                    </div>

                                    <button onclick="unlinkChild(<?= $ward->link_id ?>, '<?= addslashes(htmlspecialchars($ward->first_name)) ?>')" class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition" title="Unlink Child">
                                        <i class="bx bx-unlink"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Performance Trend Sparkline -->
                            <?php if(!empty($ward->trends) && count($ward->trends) > 1): ?>
                            <div class="mb-5 bg-gray-50/50 rounded-2xl p-3 border border-gray-100/50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Exam Performance Trend</span>
                                    <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">Last 5 Exams</span>
                                </div>
                                <div class="h-12 w-full">
                                    <canvas id="trend_<?= $ward->student_internal_id ?>"></canvas>
                                </div>
                                <script>
                                    new Chart(document.getElementById('trend_<?= $ward->student_internal_id ?>'), {
                                        type: 'line',
                                        data: {
                                            labels: <?= json_encode(array_fill(0, count($ward->trends), '')) ?>,
                                            datasets: [{
                                                data: <?= json_encode($ward->trends) ?>,
                                                borderColor: '#6366f1',
                                                borderWidth: 2,
                                                pointRadius: 0,
                                                tension: 0.4,
                                                fill: true,
                                                backgroundColor: 'rgba(99, 102, 241, 0.05)'
                                            }]
                                        },
                                        options: {
                                            maintainAspectRatio: false,
                                            plugins: { legend: { display: false }, tooltip: { enabled: false } },
                                            scales: { x: { display: false }, y: { display: false, min: 0, max: 100 } }
                                        }
                                    });
                                </script>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($ward->active_pass)): ?>
                            <div class="mb-5 bg-rose-50 border border-rose-100 rounded-2xl p-4 flex items-start gap-3 shadow-inner relative overflow-hidden group">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-rose-100/50 rounded-bl-full animate-pulse opacity-50"></div>
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 text-white flex items-center justify-center shrink-0 shadow-md">
                                    <i class="bx bx-badge-check text-2xl"></i>
                                </div>
                                <div class="flex-1 relative z-10 w-full min-w-0">
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-1 gap-1">
                                        <h4 class="text-xs font-semibold text-rose-800 tracking-tight leading-none uppercase">Active Gate Pass</h4>
                                        <span class="text-[9px] font-semibold text-rose-600 bg-white px-2 py-0.5 rounded-full border border-rose-100 shadow-sm animate-pulse whitespace-nowrap">
                                            Expires <?= date('h:i A', strtotime($ward->active_pass->expires_at)) ?>
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-rose-700 font-bold mb-2 leading-tight">
                                        Authorized by <?= htmlspecialchars($ward->active_pass->staff_fname . ' ' . $ward->active_pass->staff_lname) ?>
                                    </p>
                                    <div class="bg-white/60 p-2 rounded-xl border border-rose-100/50 text-xs">
                                        <p class="font-bold text-rose-900 truncate"><span class="text-rose-400 font-medium mr-1 uppercase text-[9px] tracking-widest">To:</span> <?= htmlspecialchars($ward->active_pass->destination) ?></p>
                                        <p class="font-bold text-rose-900 truncate mt-0.5"><span class="text-rose-400 font-medium mr-1 uppercase text-[9px] tracking-widest">For:</span> <?= htmlspecialchars($ward->active_pass->reason) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-3 gap-2">
                                <a data-url="pages/academics.php?id=<?= $ward->student_internal_id ?>" class="ajax-card flex flex-col items-center justify-center p-3 rounded-xl bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 transition text-gray-600 group cursor-pointer">
                                    <i class="bx bx-book-bookmark text-2xl mb-1 group-hover:scale-110 transition-transform"></i>
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Results</span>
                                </a>
                                <a data-url="pages/attendance.php?id=<?= $ward->student_internal_id ?>" class="ajax-card flex flex-col items-center justify-center p-3 rounded-xl bg-gray-50 hover:bg-emerald-50 hover:text-emerald-600 transition text-gray-600 group cursor-pointer">
                                    <i class="bx bx-calendar-check text-2xl mb-1 group-hover:scale-110 transition-transform"></i>
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Attendance</span>
                                </a>
                                <a data-url="pages/finance.php?id=<?= $ward->student_internal_id ?>" class="ajax-card flex flex-col items-center justify-center p-3 rounded-xl bg-gray-50 hover:bg-amber-50 hover:text-amber-600 transition text-gray-600 group cursor-pointer">
                                    <i class="bx bx-wallet text-2xl mb-1 group-hover:scale-110 transition-transform"></i>
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Finance</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
                    </section>

                    <!-- Recent Payments Section -->
                    <section>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Recent Payment History</h2>
                </div>
                
                <?php if(empty($recent_pays)): ?>
                    <div class="bg-white border border-gray-100 rounded-3xl p-10 text-center shadow-sm">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="bx bx-receipt text-3xl text-gray-300"></i>
                        </div>
                        <p class="text-gray-400 font-bold">No payments recorded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest uppercase text-gray-400">Student & Fee Description</th>
                                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest uppercase text-gray-400">Date & Ref</th>
                                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest uppercase text-gray-400 text-right">Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach($recent_pays as $pay): ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($pay->first_name) ?></p>
                                            <p class="text-[10px] font-semibold text-gray-500 mt-0.5"><?= htmlspecialchars($pay->fee_name) ?></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-xs font-bold text-gray-700"><?= date('M d, Y', strtotime($pay->created_at)) ?></p>
                                            <p class="text-[10px] font-mono text-gray-400"><?= htmlspecialchars($pay->reference_no ?: 'CASH_PMT') ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <p class="text-sm font-semibold text-emerald-600">₦<?= number_format($pay->amount, 2) ?></p>
                                            <div class="flex items-center justify-end gap-2 mt-1">
                                                <button onclick="window.open('../admin/pages/finance_receipt.php?id=<?= $pay->id ?>', '_blank', 'width=800,height=1000')" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 hover:underline">Receipt</button>
                                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">&bull; Success</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
                    </section>
                </div>

                <!-- Right Column: Noticeboard -->
                <div class="lg:col-span-4 space-y-8">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full sticky top-5">
                        <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800 tracking-tight">Noticeboard</h2>
                            <i class="bx bxs-megaphone text-orange-500 text-2xl"></i>
                        </div>
                        
                        <div class="p-6 space-y-8 overflow-y-auto max-h-[calc(100vh-300px)] custom-scrollbar">
                            <!-- Official Updates -->
                            <section>
                                <h3 class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-4">Official Updates</h3>
                                <?php if(empty($announcements_list)): ?>
                                    <p class="text-xs text-gray-400 font-medium italic">No new school updates.</p>
                                <?php else: ?>
                                    <div class="space-y-6">
                                        <?php foreach($announcements_list as $ann): ?>
                                            <div class="flex gap-4 group">
                                                <div class="flex flex-col items-center">
                                                    <div class="size-2.5 rounded-full bg-indigo-500 ring-4 ring-indigo-50"></div>
                                                    <div class="w-px h-full bg-gray-100 group-last:bg-transparent mt-2"></div>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-xs font-semibold text-gray-800 mb-1 leading-snug"><?= htmlspecialchars($ann->title) ?></p>
                                                    <p class="text-[11px] text-gray-500 font-medium leading-relaxed mb-2"><?= nl2br(htmlspecialchars($ann->message)) ?></p>
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">
                                                        <i class="bx bx-calendar mr-1"></i><?= date('M j, Y', strtotime($ann->created_at)) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>

                            </section>
                        </div>
                    </div>
                </div>
            </div> <!-- End Main Grid -->

        </div>
        </div>
    </main>

    <!-- Global Notification Screen -->
    <div id="notification_screen" class="fixed inset-0 bg-black/60 h-screen z-[999] opacity-0 translate-x-[-50%] pointer-events-none transition-all duration-300 backdrop-blur-sm">
        <div class="lg:w-[400px] w-full h-screen bg-white overflow-y-auto float-right shadow-2xl" id="notification_area">
            <div class="bg-white py-4 px-6 sticky top-0 z-50 flex items-center justify-between border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
                <button id="notification_closeBtn" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">
                    <i class="bx bx-x text-2xl"></i>
                </button>
            </div>
            <div class="p-6">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-20">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="bx bx-bell-off text-3xl text-gray-300"></i>
                        </div>
                        <p class="text-gray-400 font-bold">No notifications yet</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($notifications as $msg): ?>
                            <div class="notification p-5 rounded-2xl border <?= $msg->is_read ? 'border-gray-100 bg-white opacity-70' : 'border-indigo-100 bg-indigo-50/30' ?> transition-all relative">
                                <div class="flex items-start gap-4 mb-3">
                                    <div class="w-10 h-10 rounded-xl <?= $msg->is_read ? 'bg-gray-100' : 'bg-indigo-100' ?> flex items-center justify-center shrink-0">
                                        <i class="bx <?= $msg->is_read ? 'bx-bell' : 'bx-bell-plus animate-swing' ?> text-xl <?= $msg->is_read ? 'text-gray-400' : 'text-indigo-600' ?>"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 leading-tight mb-1"><?= htmlspecialchars($msg->subject) ?></h4>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= date('M j, Y • h:i A', strtotime($msg->created_at)) ?></p>
                                    </div>
                                </div>
                                <div class="text-sm font-medium text-gray-600 mb-4 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($msg->message)) ?>
                                </div>
                                <?php if (!$msg->is_read): ?>
                                                <button
                                                    class="mark-read-btn text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 cursor-pointer"
                                                    data-id="<?= $msg->id ?>">
                                        <i class="bx bx-check-double text-lg"></i> Mark as Read
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Link Child -->
    <div id="linkChildModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-100 transform transition-all">
            <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-semibold text-gray-800 text-lg">Link a Child</h3>
                <button onclick="document.getElementById('linkChildModal').classList.add('hidden')" class="w-8 h-8 rounded-full hover:bg-gray-200 flex items-center justify-center text-gray-500 transition"><i class="bx bx-x text-xl"></i></button>
            </div>
            <form id="linkChildForm" class="p-6">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-widest mb-2">Student ID / Admission No.</label>
                    <input type="text" name="student_id" required placeholder="e.g. STU-2023-001" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-center uppercase tracking-widest placeholder-gray-300">
                </div>
                <div class="mt-8">
                    <button type="submit" id="btnLinkChild" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2">
                        <i class="bx bx-link text-lg"></i> Link Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: How to Pay -->
    <div id="howToPayModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-100 transform transition-all">
            <div class="px-6 py-5 bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-2xl bg-white/20 flex items-center justify-center border border-white/30">
                        <i class="bx bx-wallet text-white text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-white text-lg tracking-tight">How to Pay</h3>
                </div>
                <button onclick="document.getElementById('howToPayModal').classList.add('hidden')" class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center text-white/70 hover:text-white transition cursor-pointer"><i class="bx bx-x text-2xl"></i></button>
            </div>
            
            <div class="p-6">
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 mb-6 flex gap-3 items-start">
                    <i class="bx bx-info-circle text-amber-500 text-xl mt-0.5"></i>
                    <p class="text-[11px] font-bold text-amber-700 italic leading-relaxed">Please make payments using the details below and bring the physical bank teller or screenshot of the transfer to the school bursary for confirmation.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Bank Account Details</label>
                        <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 text-sm font-semibold text-gray-700 leading-relaxed whitespace-pre-line">
                            <?= !empty($config->account_details) ? htmlspecialchars($config->account_details) : "Account details not yet provided by the school.\n\nPlease contact the school administrator." ?>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button onclick="document.getElementById('howToPayModal').classList.add('hidden')" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3.5 rounded-xl shadow-lg shadow-gray-200 transition flex items-center justify-center gap-2">
                            <i class="bx bx-check-circle"></i> I Understand
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../src/scripts.js"></script>
    <script>
        function showHowToPay() {
            document.getElementById('howToPayModal').classList.remove('hidden');
        }

        // Standardized cleanup: Global scripts handle toggling and notifications now.
        // We only maintain parent-specific logic here.

        $(document).on('click', '.ajax-card', function(e) {
            const url = $(this).data('url');
            if (url) {
                // If on mobile, force close sidebar on mobile card click
                if (window.innerWidth < 1024) {
                    if (typeof closeSidebar === 'function') closeSidebar();
                }
            }
        });

        $('#linkChildForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#btnLinkChild');
            const ogHtml = btn.html();
            btn.html('<i class="bx bxs-loader-dots bx-spin text-lg"></i> Linking...').prop('disabled', true);

            $.post('auth/parent_api.php?action=link_child', $(this).serialize(), function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: res.message,
                        icon: 'success',
                        confirmButtonColor: '#4f46e5'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message, 'error');
                    btn.html(ogHtml).prop('disabled', false);
                }
            }).fail(function() {
                Swal.fire('Error', 'Network request failed. Try again.', 'error');
                btn.html(ogHtml).prop('disabled', false);
            });
        });

        function unlinkChild(linkId, childName) {
            Swal.fire({
                title: "Unlink " + childName + "?",
                text: "You will no longer be able to see their records.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, unlink",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('auth/parent_api.php?action=unlink_child', { link_id: linkId }, function(res) {
                        if(res.status === 'success') {
                            location.reload();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
