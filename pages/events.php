<?php
require '../connections/db.php';
require '../auth/check.php';

$role = $_SESSION['role'] ?? '';

// Determine allowed visibilities based on role
$allowed_visibilities = ['all'];
if ($role === 'student' || $role === 'students') {
    $allowed_visibilities[] = 'students';
} elseif ($role === 'staff') {
    $allowed_visibilities[] = 'staff';
} elseif ($role === 'parent' || $role === 'parents' || $role === 'guardian') {
    $allowed_visibilities[] = 'parents';
} else {
    // maybe admin/super seeing but this page is typically for the rest
}

$in_query = implode(',', array_fill(0, count($allowed_visibilities), '?'));

$stmt = $conn->prepare("SELECT * FROM school_events WHERE visibility IN ($in_query) ORDER BY start_date ASC");
$stmt->execute($allowed_visibilities);
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

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()"
                class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-indigo-700 hover:border-indigo-200 hover:bg-indigo-50 transition-all cursor-pointer"
                title="Go back" data-tippy-content="Back to Dashboard">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 shadow-sm border border-indigo-200">
                    <i class="bx bx-calendar-event text-indigo-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">School Event Calendar</h3>
                    <p class="text-sm text-gray-400 font-medium">View all upcoming and past events.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Events Grid -->
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
                        <!-- <p class="text-xs text-gray-500 font-semibold mb-3">Visible to: <?= ucfirst(htmlspecialchars($ev['visibility'])) ?></p> -->
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

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');
</script>
