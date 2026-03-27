<?php
require __DIR__ . '/../auth/check.php';
require __DIR__ . '/../auth/fee_check.php';

if ($_SESSION['role'] !== 'guardian') {
    header("Location: {$base}auth/login.php");
    exit();
}

// Fetch all wards linked to this guardian
$stmt = $conn->prepare("
    SELECT u.*, gw.relationship 
    FROM users u
    JOIN guardian_wards gw ON u.id = gw.student_id
    WHERE gw.guardian_id = ?
");
$stmt->execute([$user->id]);
$wards = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<?php require '../components/header.php'; ?>
<body class="bg-gray-50/50">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h1 class="text-4xl font-semibold text-gray-900 tracking-tight mb-2">Guardian Watchtower</h1>
                <p class="text-gray-500 font-medium italic">Monitoring academic progress and campus safety for your dependents</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-3">
                    <div class="size-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="bx bx-user text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Logged in As</p>
                        <p class="text-sm font-bold text-gray-800"><?= $user->first_name . ' ' . $user->surname ?></p>
                    </div>
                </div>
                <a href="events.php" class="p-4 bg-indigo-50 text-indigo-600 rounded-2xl border border-indigo-100 hover:bg-indigo-500 hover:text-white transition-all shadow-sm" title="School Events">
                    <i class="bx bx-calendar-event text-2xl"></i>
                </a>
                <a href="../auth/logout.php" class="p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Log Out">
                    <i class="bx bx-log-out text-2xl"></i>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($wards as $w): 
                $is_cleared = isFeeCleared($conn, $w->id);
                
                // Attendance flags (Recent absence)
                $att_stmt = $conn->prepare("SELECT status FROM attendance WHERE student_id = ? ORDER BY id DESC LIMIT 5");
                $att_stmt->execute([$w->id]);
                $recent_att = $att_stmt->fetchAll(PDO::FETCH_COLUMN);
                $absences = count(array_filter($recent_att, fn($s) => $s == 'absent'));
            ?>
                <div class="bg-white rounded-[3rem] p-8 border border-gray-100 shadow-xl shadow-gray-200/50 group hover:-translate-y-2 transition-all duration-500 overflow-hidden relative">
                    <!-- Status Indicator -->
                    <div class="absolute top-0 right-0 p-8">
                        <span class="px-4 py-1.5 <?= $is_cleared ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' ?> rounded-full text-[10px] font-semibold uppercase tracking-widest border border-<?= $is_cleared ? 'emerald' : 'red' ?>-100">
                            ACCOUNT: <?= $is_cleared ? 'CLEARED' : 'OVERDUE' ?>
                        </span>
                    </div>

                    <div class="flex flex-col items-center text-center gap-4 mb-8">
                        <div class="size-24 rounded-full bg-gray-50 border-4 border-white shadow-xl flex items-center justify-center overflow-hidden">
                            <img src="<?= $w->profile_photo ? '../uploads/profile/'.$w->profile_photo : 'https://ui-avatars.com/api/?name='.urlencode($w->first_name).'&background=random' ?>" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800"><?= $w->first_name ?> <?= $w->surname ?></h3>
                            <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mt-1"><?= $w->class ?> | <?= $w->relationship ?></p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Fee Card -->
                        <div class="p-4 rounded-2xl <?= $is_cleared ? 'bg-gray-50 text-gray-500' : 'bg-red-50 text-red-700' ?> border border-transparent transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold uppercase tracking-wider flex items-center gap-2">
                                    <i class="bx bx-credit-card text-lg"></i> Financial Status
                                </span>
                                <?php if(!$is_cleared): ?>
                                    <span class="animate-pulse"><i class="bx bx-error-circle"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Attendance Card -->
                        <div class="p-4 rounded-2xl <?= $absences >= 2 ? 'bg-orange-50 text-orange-700' : 'bg-emerald-50 text-emerald-700' ?> border border-transparent transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold uppercase tracking-wider flex items-center gap-2">
                                    <i class="bx bx-calendar text-lg"></i> Attendance Flow
                                </span>
                                <span class="text-xs font-bold"><?= $absences > 0 ? $absences.' Absence Flag' : 'Steady' ?></span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-3 mt-8">
                            <a href="../auth/generate_report_card.php?student_id=<?= $w->id ?>" target="_blank" class="py-4 bg-gray-900 text-white rounded-2xl text-[10px] font-semibold uppercase tracking-widest text-center shadow-lg hover:bg-black transition-all">
                                Report Card
                            </a>
                            <button class="py-4 bg-indigo-600 text-white rounded-2xl text-[10px] font-semibold uppercase tracking-widest text-center shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                                Message School
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($wards)): ?>
                <div class="col-span-full py-20 text-center bg-white rounded-[3rem] border border-dashed border-gray-200">
                    <i class="bx bx-user-plus text-6xl text-gray-200 mb-4 font-normal"></i>
                    <p class="text-gray-400 font-bold uppercase tracking-widest">No wards linked to this account</p>
                    <p class="text-xs text-gray-300 mt-1">Please contact the administrator to link your child's profile.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
