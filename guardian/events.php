<?php
require '../connections/db.php';
require '../auth/check.php';

if ($_SESSION['role'] !== 'guardian') {
    header("Location: {$base}auth/login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM school_events WHERE visibility IN ('all', 'parents') ORDER BY start_date ASC");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatDateRange($start, $end) {
    if (!$start || !$end) return '';
    $s = new DateTime($start);
    $e = new DateTime($end);
    if ($s->format('Y-m-d') === $e->format('Y-m-d')) {
        return $s->format('M j, Y') . ' (' . $s->format('g:i A') . ' - ' . $e->format('g:i A') . ')';
    }
    return $s->format('M j, Y (g:i A)') . ' - ' . $e->format('M j, Y (g:i A)');
}
?>

<?php require '../components/header.php'; ?>
<body class="bg-gray-50/50">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div class="flex items-center gap-4">
                <a href="index.php" class="size-12 shrink-0 rounded-full bg-white flex items-center justify-center text-gray-500 hover:text-indigo-700 hover:border-indigo-200 border border-gray-100 shadow-sm transition-all cursor-pointer">
                    <i class="bx bx-arrow-left-stroke text-3xl"></i>
                </a>
                <div>
                    <h1 class="text-4xl font-semibold text-gray-900 tracking-tight mb-2">School Events</h1>
                    <p class="text-gray-500 font-medium italic">View upcoming and past events for parents.</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="../auth/logout.php" class="p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100 hover:bg-red-500 hover:text-white transition-all">
                    <i class="bx bx-log-out text-2xl"></i>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
            <?php if(empty($events)): ?>
                <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                    <div class="mb-4 inline-flex items-center justify-center size-16 rounded-full bg-gray-50 text-gray-400">
                        <i class="bx bx-calendar-x text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">No Events Found</h3>
                    <p class="text-sm text-gray-500 mt-2">There are no school events listed for you right now.</p>
                </div>
            <?php else: ?>
                <?php foreach($events as $ev): 
                    $now = new DateTime();
                    $end = new DateTime($ev['end_date']);
                    $is_past = $end < $now;
                    
                    $bg_color = $is_past ? 'bg-gray-50 border-gray-200' : 'bg-white border-indigo-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] hover:-translate-y-1 hover:shadow-lg transition-transform';
                    $icon_color = $is_past ? 'text-gray-400 bg-gray-100' : 'text-indigo-500 bg-indigo-100';
                ?>
                <div class="rounded-3xl border relative group p-6 flex flex-col h-full <?= $bg_color ?>">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex flex-col">
                            <span class="text-xs font-bold text-gray-400">Date & Time</span>
                            <span class="text-sm font-semibold text-gray-700">
                                <i class="bx bx-time mr-1"></i> <?= formatDateRange($ev['start_date'], $ev['end_date']) ?>
                            </span>
                        </span>
                        <?php if($is_past): ?>
                            <span class="text-[10px] uppercase font-semibold tracking-widest rounded-full bg-gray-200 text-gray-600 px-2 py-1">Past</span>
                        <?php else: ?>
                            <span class="text-[10px] uppercase font-semibold tracking-widest rounded-full bg-green-100 text-green-700 px-2 py-1 flex items-center gap-1">
                                <span class="relative flex size-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full size-2 bg-green-500"></span>
                                </span> Upcoming
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-4">
                        <div class="size-10 rounded-xl <?= $icon_color ?> flex items-center justify-center shrink-0">
                            <i class="bx bx-party text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-800 mb-1 leading-tight line-clamp-2"><?= htmlspecialchars($ev['title']) ?></h4>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 mt-2 line-clamp-4 leading-relaxed font-medium">
                        <?= nl2br(htmlspecialchars($ev['description'] ?? '')) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
