<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

// Fetch cumulative points
$points_stmt = $conn->prepare("SELECT SUM(points) as total FROM student_points WHERE student_id = :id");
$points_stmt->execute([':id' => $user->id]);
$total_points = (int) $points_stmt->fetchColumn();

// Fetch badge timeline
$badges_stmt = $conn->prepare("
    SELECT b.name, b.description, b.icon, sb.earned_at
    FROM student_badges sb
    JOIN badges b ON sb.badge_id = b.id
    WHERE sb.student_id = :id
    ORDER BY sb.earned_at DESC
");
$badges_stmt->execute([':id' => $user->id]);
$earned_badges = $badges_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unearned badges to show locked ones
$all_badges_stmt = $conn->prepare("
    SELECT id, name, description, icon, points_required
    FROM badges
    WHERE id NOT IN (SELECT badge_id FROM student_badges WHERE student_id = :id)
    ORDER BY points_required ASC
");
$all_badges_stmt->execute([':id' => $user->id]);
$locked_badges = $all_badges_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Point Activity History
$history_stmt = $conn->prepare("SELECT points, activity, created_at FROM student_points WHERE student_id = :id ORDER BY created_at DESC LIMIT 15");
$history_stmt->execute([':id' => $user->id]);
$history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate next level/milestone
$next_badge = $locked_badges[0] ?? null;
$progress_pct = 100;
if ($next_badge && $next_badge['points_required'] > 0) {
    if ($total_points < $next_badge['points_required']) {
        $progress_pct = min(100, ($total_points / $next_badge['points_required']) * 100);
    }
}
$progress_pct = round($progress_pct);

?>

<div class="fadeIn p-4 md:p-10">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-yellow-400 to-orange-500 text-white shadow-xl shadow-orange-200 flex items-center justify-center">
                <i class="bx bx-trophy text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Achievements</h1>
                <p class="text-sm text-gray-500 font-medium">Track your academic points, timeline, and earned badges.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left Pane: Stats & History -->
        <div class="lg:col-span-8 flex flex-col gap-8">
            <!-- Level Banner -->
            <div class="bg-gradient-to-r from-gray-900 via-gray-800 to-black text-white p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">
                <!-- Particles Decoration -->
                <div class="absolute -top-10 -right-10 size-40 bg-orange-500 rounded-full blur-[60px] opacity-30"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <p class="text-orange-400 font-bold uppercase tracking-widest text-[10px] mb-2">Total Score</p>
                        <h2 class="text-5xl md:text-6xl font-semibold tabular-nums tracking-tighter">
                            <?= number_format($total_points) ?> <span class="text-xl text-gray-400 font-bold tracking-normal inline-block align-top mt-2 -ml-2">XP</span>
                        </h2>
                    </div>
                    <?php if ($next_badge): ?>
                    <div class="w-full md:w-1/2 p-4 bg-white/10 backdrop-blur-md rounded-3xl border border-white/10">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-gray-300 uppercase tracking-widest">Next Milestone</span>
                            <span class="text-xs font-semibold text-orange-400"><?= $total_points ?> / <?= $next_badge['points_required'] ?> XP</span>
                        </div>
                        <p class="text-sm font-bold truncate mb-3"><i class="bx <?= $next_badge['icon'] ?> text-yellow-400"></i> <?= $next_badge['name'] ?></p>
                        <div class="w-full bg-black/40 rounded-full h-2">
                            <div class="bg-gradient-to-r from-orange-500 to-yellow-400 h-2 rounded-full transition-all duration-1000" style="width: <?= $progress_pct ?>%"></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="w-full md:w-1/2 p-4 bg-orange-500/20 rounded-3xl border border-orange-500/20 text-center">
                        <i class="bx bxs-crown text-3xl text-yellow-400 mb-2"></i>
                        <p class="font-bold text-yellow-200">Maximum Level Reached!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-100/50 p-6 md:p-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="bx bx-list-ol text-blue-500"></i> Points History
                </h3>
                
                <?php if (empty($history)): ?>
                    <div class="py-12 text-center bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                        <i class="bx bx-ghost text-4xl text-gray-300 mb-2"></i>
                        <p class="text-gray-400 font-bold text-sm">No activity recorded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach($history as $item): 
                            $pts = (int)$item['points'];
                            $isPos = $pts > 0;
                        ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl group hover:bg-white hover:shadow-md transition-all">
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-xl <?= $isPos ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?> flex items-center justify-center shrink-0 shadow-inner">
                                    <i class="bx <?= $isPos ? 'bx-trending-up' : 'bx-trending-down' ?> text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800"><?= htmlspecialchars($item['activity']) ?></h4>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><?= date('M j, Y • g:i a', strtotime($item['created_at'])) ?></p>
                                </div>
                            </div>
                            <span class="font-semibold <?= $isPos ? 'text-green-500' : 'text-red-500' ?>">
                                <?= $isPos ? '+' : '' ?><?= $pts ?> XP
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Pane: Badges -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-100/50 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="bx bxs-badge-check text-yellow-500"></i> Earned Badges
                </h3>
                
                <?php if (empty($earned_badges)): ?>
                    <p class="text-xs text-gray-400 font-bold mb-4">Complete exams and activities to earn your first badge!</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach($earned_badges as $b): ?>
                        <div class="flex flex-col items-center justify-center p-4 bg-gradient-to-b from-yellow-50 to-white border border-yellow-100 rounded-2xl text-center group relative cursor-default" data-tippy-content="Earned on <?= date('M j, Y', strtotime($b['earned_at'])) ?>">
                            <div class="size-16 rounded-full bg-yellow-100 text-yellow-500 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform mb-3 border-4 border-white">
                                <i class="bx <?= $b['icon'] ?> text-3xl drop-shadow-sm"></i>
                            </div>
                            <h4 class="text-xs font-semibold text-gray-800 leading-tight mb-1"><?= htmlspecialchars($b['name']) ?></h4>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Locked Badges -->
            <div class="bg-gray-50 rounded-[2rem] border border-gray-100 p-6">
                <h3 class="text-sm font-semibold text-gray-500 mb-6 uppercase tracking-widest flex items-center gap-2">
                    <i class="bx bx-lock"></i> Locked Badges
                </h3>
                
                <div class="space-y-4">
                    <?php foreach($locked_badges as $bk): ?>
                    <div class="flex items-start gap-4 p-4 bg-white border border-gray-200 rounded-2xl opacity-60 grayscale hover:grayscale-0 hover:opacity-100 transition-all duration-500 cursor-default">
                        <div class="size-10 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center shrink-0">
                            <i class="bx <?= $bk['icon'] ?> text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-gray-800"><?= htmlspecialchars($bk['name']) ?></h4>
                            <p class="text-[10px] text-gray-400 font-medium mb-1 line-clamp-2"><?= htmlspecialchars($bk['description']) ?></p>
                            <span class="text-[9px] font-semibold uppercase tracking-widest text-orange-500 bg-orange-50 px-2 py-0.5 rounded-md border border-orange-100">
                                Requires <?= $bk['points_required'] ?> XP
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    if(typeof initTooltips === 'function') initTooltips();
</script>
