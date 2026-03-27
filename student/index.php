<?php
require __DIR__ . '/../auth/check.php';
require __DIR__ . '/../auth/fee_check.php';

$is_cleared = isFeeCleared($conn, $user->id);
$outstanding_fees = $is_cleared ? [] : getOutstandingFees($conn, $user->id);

// redirect to configuration page if school settings is not yet configured
// -------------------------------------------------------------//
$check_config = $conn->prepare('SELECT * FROM school_config');
$check_config->execute();
$sch_config = $check_config->fetch(PDO::FETCH_OBJ);

if (!$sch_config) {
      session_unset();
      session_destroy();
      header("Location: {$base}index.php");
      exit();
}
// -------------------------------------------------------------//

// Only students can access this page
if ($user->role !== 'student') {
      header("Location: {$base}auth/login.php");
      exit();
}

$userClass = strtoupper(trim($_SESSION['class'] ?? $user->class ?? ''));
$isSenior = (strpos($userClass, 'SS') !== false && strpos($userClass, 'JSS') === false) || (strpos($userClass, 'SSS') !== false);

$stmt = $conn->prepare("
    SELECT * FROM broadcast
    WHERE recipient = :recipient
    ORDER BY created_at DESC
");

$stmt->execute([
      ':recipient' => $_SESSION['username']
]);

/** @var array $results */
$results = $stmt->fetchAll(PDO::FETCH_OBJ);

$blog_stmt = $conn->prepare('SELECT * FROM blog ORDER BY id DESC LIMIT 6');
$blog_stmt->execute();
/** @var array $blogs_list */
$blogs_list = $blog_stmt->fetchAll(PDO::FETCH_OBJ);

// Count unread messages
$unread_stmt = $conn->prepare("SELECT COUNT(*) FROM broadcast WHERE recipient = :recipient AND is_read = 0");
$unread_stmt->execute([':recipient' => $_SESSION['username']]);
$unread_messages = (int) $unread_stmt->fetchColumn();

$session_id = $_SESSION['active_session_id'] ?? 0;
$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Attendance Stats
$att_count_stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present FROM attendance WHERE student_id = :sid AND session_id = :sess_id");
$att_count_stmt->execute([':sid' => $user->id, ':sess_id' => $session_id]);
$att_stats = $att_count_stmt->fetch(PDO::FETCH_OBJ);
$total_days = (int) ($att_stats->total ?? 0);
$present_days = (int) ($att_stats->present ?? 0);
$attendance_percent = $total_days > 0 ? round(($present_days / $total_days) * 100) : 100;

// Core Student Stats
$exam_count_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id 
    WHERE r.user_id = :sid AND e.session = :sess AND e.term = :term
");
$exam_count_stmt->execute([':sid' => $user->id, ':sess' => $active_session, ':term' => $active_term]);
$total_exams = $exam_count_stmt->fetchColumn() ?: 0;

$avg_score_stmt = $conn->prepare("
    SELECT AVG(r.percentage) 
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id 
    WHERE r.user_id = :sid AND e.session = :sess AND e.term = :term
");
$avg_score_stmt->execute([':sid' => $user->id, ':sess' => $active_session, ':term' => $active_term]);
$avg_score = round($avg_score_stmt->fetchColumn() ?: 0);

$lib_count_stmt = $conn->prepare("SELECT COUNT(*) FROM materials WHERE TRIM(LOWER(class)) = TRIM(LOWER(?))");
$lib_count_stmt->execute([$userClass]);
$lib_count = $lib_count_stmt->fetchColumn() ?: 0;

// Check for active Hall Pass
$conn->exec("UPDATE hall_passes SET status = 'expired' WHERE status = 'active' AND expires_at < CURRENT_TIMESTAMP");
$pass_stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.surname 
    FROM hall_passes p
    JOIN users u ON p.issued_by = u.id
    WHERE p.student_id = ? AND p.status = 'active'
    ORDER BY p.id DESC LIMIT 1
");
$pass_stmt->execute([$user->id]);
$active_pass = $pass_stmt->fetch(PDO::FETCH_OBJ);
?>

<?php require '../components/header.php'; ?>
<?php require '../components/sidebar.php'; ?>

<!-- right nav -->
<main class="ml-0 md:ml-72">
      <!-- top Nav -->
      <?php require '../components/navbar.php'; ?>

      <!-- Main Content -->
      <div class="flex flex-col w-full" id="mainContent">
            <div class="fadeIn w-full md:p-8 p-4">
                  
                  <!-- Welcome Banner (Admin Style) -->
                  <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-500 to-green-400 rounded-2xl p-6 md:p-8 mb-6 shadow-lg shadow-blue-100">
                        <!-- Decorative circles -->
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full"></div>
                        <div class="absolute top-1/2 right-1/4 w-20 h-20 bg-white/5 rounded-full"></div>

                        <div class="relative z-10 md:flex justify-between items-center">
                              <div>
                                    <p class="text-blue-50 text-sm font-medium mb-1">
                                          <?= date('l, F j, Y') ?>
                                    </p>
                                    <h3 class="text-2xl md:text-3xl font-bold text-white mb-1">
                                          Welcome back, <?= ucfirst($user->surname) ?> 👋
                                    </h3>
                                    <p class="text-blue-50 text-sm opacity-90 font-medium">
                                          Ready to achieve your academic goals today?
                                    </p>

                                    <div class="mt-3 flex items-center gap-2">
                         <?php if (isset($_SESSION['active_session']) && !empty($_SESSION['active_session'])): ?>
            <div
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md border border-white/20 text-white text-xs font-semibold shadow-sm">
                  <i class="bx-calendar text-sm"></i>
                  <?= $_SESSION['active_session'] ?>
            </div>
            <div
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-xs font-semibold shadow-sm">
                  <i class="bx-clock-5 text-sm"></i>
                  <?= $_SESSION['active_term'] ?>
            </div>
      <?php endif; ?>
            <?php if ($is_cleared): ?>
                  <div
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-400/20 backdrop-blur-md border border-emerald-400/30 text-emerald-100 text-xs font-bold shadow-sm">
                        <i class="bx bx-check-shield text-sm"></i>
                        ACCOUNT CLEARED
                  </div>
            <?php else: ?>
                  <div
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-400/20 backdrop-blur-md border border-red-400/30 text-red-100 text-xs font-bold shadow-sm animate-pulse">
                        <i class="bx bx-error-circle text-sm"></i>
                        OUTSTANDING DEBT
                  </div>
            <?php endif; ?>
                                    </div>
                                    </div>

                              <button onclick="$('#sideTest').click()" class="mt-4 md:mt-0 bg-white/20 backdrop-blur-sm text-white px-5 py-2.5 rounded-xl border border-white/30 text-sm font-bold shadow-lg shadow-blue-900/10 transition-all hover:bg-white/30 hover:-translate-y-0.5 cursor-pointer flex items-center gap-2">
                                    <i class="bx bx-pencil text-lg"></i>
                                    Take Exam
                              </button>
                        </div>
                  </div>
                  
            <?php if ($active_pass): ?>
                  <div class="bg-rose-50 border border-rose-100 rounded-3xl p-6 mb-6 flex flex-col md:flex-row items-start gap-5 shadow-sm relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-rose-100 to-rose-50 rounded-bl-full flex items-start justify-end p-4 animate-pulse opacity-50 pointer-events-none">
                              <i class="bx bx-run text-rose-300 text-5xl"></i>
                        </div>
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 text-white flex items-center justify-center shrink-0 shadow-xl shadow-rose-200">
                              <i class="bx bx-badge-check text-4xl"></i>
                        </div>
                        <div class="flex-1 relative z-10 w-full">
                              <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-2 gap-3">
                                    <h4 class="text-xl font-semibold text-rose-800 tracking-tight">ACTIVE DIGITAL PASS</h4>
                                    <span class="px-3 py-1.5 bg-white text-rose-600 rounded-xl text-xs font-semibold uppercase tracking-widest border border-rose-100 shadow-sm animate-pulse flex items-center gap-1">
                                          <i class="bx bx-time-five text-base"></i> Expires <?= date('h:i A', strtotime($active_pass->expires_at)) ?>
                                    </span>
                              </div>
                              <p class="text-sm text-rose-700 font-medium mb-4">
                                    You have been granted permission by <strong>Teacher <?= htmlspecialchars($active_pass->first_name . ' ' . $active_pass->surname) ?></strong>. 
                                    Show this digital ticket to the security staff or patrol on duty.
                              </p>
                              
                              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white/60 p-4 rounded-xl border border-rose-100/50 w-full lg:w-2/3">
                                    <div>
                                          <span class="text-[10px] font-semibold uppercase tracking-widest text-rose-400 block mb-0.5">Destination</span>
                                          <span class="text-sm font-bold text-rose-900"><?= htmlspecialchars($active_pass->destination) ?></span>
                                    </div>
                                    <div>
                                          <span class="text-[10px] font-semibold uppercase tracking-widest text-rose-400 block mb-0.5">Authorized Reason</span>
                                          <span class="text-sm font-bold text-rose-900"><?= htmlspecialchars($active_pass->reason) ?></span>
                                    </div>
                              </div>
                        </div>
                  </div>
            <?php endif; ?>

            <?php if (!$is_cleared): ?>
                  <div class="bg-red-50 border border-red-100 rounded-2xl p-5 mb-6 flex items-start gap-4 shadow-sm animate-pulse">
                        <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                              <i class="bx bx-block text-2xl"></i>
                        </div>
                        <div class="flex-1">
                              <h4 class="text-sm font-semibold text-red-800 uppercase tracking-tight">Financial Hold: Academic Restrictions
                                    Active</h4>
                              <p class="text-xs text-red-600 font-medium leading-relaxed mt-1">
                                    Our records indicate unpaid fees. Your access to <strong>Report Cards</strong> and <strong>AI Study
                                          Insights</strong> has been temporarily restricted.
                                    Please settle your balance of <span
                                          class="font-bold">₦<?= number_format(array_sum(array_column($outstanding_fees, 'amount_due')) - array_sum(array_column($outstanding_fees, 'amount_paid')), 2) ?></span>
                                    to restore full access.
                              </p>
                        </div>
                        <button
                              class="bg-red-600 text-white px-4 py-2 rounded-xl text-[10px] font-semibold uppercase tracking-widest shadow-lg shadow-red-200 hover:bg-red-700 transition-all cursor-pointer">
                              Pay Now
                        </button>
                  </div>
            <?php endif; ?>

            <!-- Quick Stats Grid (Admin Style) -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                        <!-- Exam Count -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-100 flex-shrink-0">
                                    <i class="bx-pencil text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Exams Taken</p>
                                    <h3 class="text-3xl font-extrabold text-gray-800 leading-tight"><?= $total_exams ?></h3>
                              </div>
                        </div>

                        <!-- Average Score -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-100 flex-shrink-0">
                                    <i class="bxs-bar-chart text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Avg. Score</p>
                                    <h3 class="text-3xl font-extrabold text-gray-800 leading-tight"><?= $avg_score ?>%</h3>
                              </div>
                        </div>

                        <!-- Attendance -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-100 flex-shrink-0">
                                    <i class="bx-calendar-check text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Attendance</p>
                                    <h3 class="text-3xl font-extrabold text-gray-800 leading-tight"><?= $attendance_percent ?>%</h3>
                              </div>
                        </div>

                        <!-- Library Items -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer" onclick="$('#sideLibrary').click()">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-100 flex-shrink-0">
                                    <i class="bx-book-content text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Resources</p>
                                    <h3 class="text-3xl font-extrabold text-gray-800 leading-tight tabular-nums"><?= $lib_count ?></h3>
                              </div>
                              </div>
                  </div>

                  <!-- Charts Section (Balanced) -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Growth Trend -->
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                              <div class="flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                          <i class="bx bx-chart-line text-xl"></i>
                                    </div>
                                    <div>
                                          <h4 class="text-sm font-bold text-gray-800">Academic Trend</h4>
                                          <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Performance over time</p>
                                    </div>
                              </div>
                              <div class="h-64">
                                    <canvas id="growthChart"></canvas>
                              </div>
                        </div>

                        <!-- Subject Mastery -->
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                              <div class="flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                          <i class="bx bx-bar-chart text-xl"></i>
                                    </div>
                                    <div>
                                          <h4 class="text-sm font-bold text-gray-800">Subject Mastery</h4>
                                          <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Category analysis</p>
                                    </div>
                              </div>
                              <div class="h-64">
                                    <canvas id="masteryChart"></canvas>
                              </div>
                        </div>
                  </div>

                  <!-- Content: News + Notifications Row -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Latest Stories -->
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                              <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                          <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                                <i class="bx bx-news text-xl"></i>
                                          </div>
                                          <h4 class="text-sm font-bold text-gray-800">Latest Stories</h4>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= count($blogs_list) ?> Posts</span>
                              </div>
                              <?php if (count($blogs_list) > 0): ?>
                                    <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                          <?php foreach (array_slice($blogs_list, 0, 5) as $blog): ?>
                                                                                                            <div class="blog_post flex items-center gap-4 p-3 rounded-2xl hover:bg-gray-50 transition-all cursor-pointer group border border-transparent hover:border-gray-100"
                                                                                                                  data-url="blog/post.php?post_id=<?= $blog->id ?>">
                                                                                                      <div class="flex-1 min-w-0">
                                                                                                            <p class="text-[9px] font-bold text-blue-500 uppercase tracking-widest mb-0.5">
                                                                                                                  <?= htmlspecialchars($blog->blog_category ?? 'STUDENT LIFE') ?>
                                                                                                            </p>
                                                                                                            <p class="text-xs font-bold text-gray-700 truncate group-hover:text-blue-600 transition">
                                                                                                                  <?= htmlspecialchars($blog->blog_title) ?>
                                                                                                            </p>
                                                      </div>
                                                      <i class="bx bx-chevron-right text-gray-300 group-hover:text-blue-600 transition"></i>
                                                </div>
                                          <?php endforeach; ?>
                                    </div>
                              <?php else: ?>
                                    <div class="text-center py-10 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">No stories yet</p>
                                    </div>
                              <?php endif ?>
                        </div>

                        <!-- Notifications -->
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                              <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                <i class="bx bx-bell text-xl"></i>
                                          </div>
                                          <h4 class="text-sm font-bold text-gray-800">Notifications</h4>
                                    </div>
                                    <?php if ($unread_messages > 0): ?>
                                                                        <span
                                                                              class="text-[10px] font-bold bg-red-100 text-red-600 px-3 py-1 rounded-full uppercase tracking-widest animate-pulse"><?= $unread_messages ?>
                                                                        New</span>
                                    <?php endif ?>
                              </div>
                              <?php if (count($results) > 0): ?>
                                    <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                          <?php foreach (array_slice($results, 0, 5) as $msg): ?>
                                                                                                            <div
                                                                                                                  class="flex items-start gap-4 p-3 rounded-2xl hover:bg-gray-50 transition-all group overflow-hidden border border-transparent hover:border-gray-100">
                                                                                                                  <div
                                                                                                                        class="w-2 h-2 rounded-full mt-1.5 <?= $msg->is_read ? 'bg-gray-200' : 'bg-blue-600 shadow-[0_0_10px_rgba(37,99,235,0.5)]' ?> shrink-0">
                                                      </div>
                                                      <div class="flex-1 min-w-0">
                                                            <h5 class="text-xs font-bold text-gray-800 truncate"><?= htmlspecialchars($msg->subject) ?></h5>
                                                            <p class="text-[11px] text-gray-500 line-clamp-1 font-medium mt-0.5">
                                                                  <?= htmlspecialchars(strip_tags($msg->message)) ?>
                                                            </p>
                                                            <div class="flex items-center gap-2 mt-2">
                                                                  <span
                                                                        class="text-[9px] font-bold text-gray-400 uppercase tracking-widest"><?= date('d M, h:i A', strtotime($msg->created_at)) ?></span>
                                                            </div>
                                                      </div>
                                                </div>
                                          <?php endforeach; ?>
                                    </div>
                              <?php else: ?>
                                    <div class="text-center py-10 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">No updates today</p>
                                    </div>
                              <?php endif ?>
                        </div>
                  </div>
            </div>
      </div>
</main>

<script>
      $(document).on('click', '.blog_post', function (e) {
            e.preventDefault();
            const url = $(this).data('url');
            const postId = new URL(url, window.location.origin).searchParams.get('post_id');

            $('#mainContent').fadeOut(200, function () {
                  $.ajax({
                        url: 'blog/post.php',
                        type: 'GET',
                        data: { post_id: postId },
                        success: function (response) {
                              $('#mainContent').html(response).fadeIn(300);
                        },
                        error: function () {
                               $('#mainContent').html('<p class="p-8 text-red-500 font-semibold uppercase text-center">Failed to load content.</p>').fadeIn(300);
                        }
                   });
            });
      });

      $(document).ready(function() {
            if (typeof window.initGrowthChart === 'function') {
                  window.initGrowthChart();
            }
      });
</script>

<?php require '../components/support_modal.php' ?>
<?php require '../components/notification.php'; ?>
<?php require '../components/footer.php'; ?>

<!-- greetings Modal (Admin Style) -->
<?php if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true): ?>
<div class="w-full h-screen bg-black/60 flex items-center justify-center fixed top-0 left-0 z-[99999] backdrop-blur-md"
      id="greetingsModal">
      <div class="relative rounded-2xl bg-white p-6 md:p-8 shadow-2xl border border-gray-100 w-[450px] max-w-[95%] backdrop-blur-sm fade-in-bottom"
            id="greetingMsgContainer">
            <button type="button"
                  class="bg-red-500 hover:bg-red-600 transition flex items-center justify-center rounded-full p-1.5 absolute top-4 right-4 z-20 cursor-pointer"
                  onclick="document.getElementById('greetingsModal').classList.add('hidden')">
                  
                  <i class="bx-x text-2xl text-white"></i>
            </button>
            <div class="flex flex-col gap-6">
                  <div class="flex flex-col items-center gap-3">
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-1">
                              <i class="bx bx-book-open text-4xl text-blue-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 text-center">Student Portal</h3>
                        <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest text-center">
                              <?= strtoupper(explode(' ', $sch_config->school_name ?? 'SCHOOL')[0]) ?> LEARNING HUB
                        </p>
                  </div>
                  <p class="text-lg text-gray-500 text-center leading-relaxed">
                        Welcome, <span class="text-blue-600 font-bold"><?= ucfirst($_SESSION['first_name']) ?></span>. <br>
                        Ready to begin your academic mission?
                  </p>
                  <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 flex flex-col items-center gap-1 hover:bg-blue-100/50 transition cursor-pointer" onclick="$('#sideTest').click(); document.getElementById('greetingsModal').classList.add('hidden')">
                              <i class="bx bx-pencil text-blue-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Take Exam</span>
                        </div>
                        <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 flex flex-col items-center gap-1 hover:bg-indigo-100/50 transition cursor-pointer" onclick="$('#sideLibrary').click(); document.getElementById('greetingsModal').classList.add('hidden')">
                              <i class="bx bx-book-open text-indigo-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Library</span>
                        </div>
                        <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100 flex flex-col items-center gap-1 hover:bg-emerald-100/50 transition cursor-pointer" onclick="$('#sideExamHistory').click(); document.getElementById('greetingsModal').classList.add('hidden')">
                              <i class="bx bx-bar-chart-alt-2 text-emerald-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Analytics</span>
                        </div>
                        <div class="bg-purple-50/50 p-4 rounded-xl border border-purple-100 flex flex-col items-center gap-1 hover:bg-purple-100/50 transition cursor-pointer" onclick="$('#sideChat').click(); document.getElementById('greetingsModal').classList.add('hidden')">
                              <i class="bx bx-message-square-detail text-purple-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Messages</span>
                        </div>
                  </div>
                  <button type="button" class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-100 hover:bg-blue-700 hover:-translate-y-0.5 transition cursor-pointer" onclick="document.getElementById('greetingsModal').classList.add('hidden')">
                        Launch Dashboard
                  </button>
            </div>
      </div>
</div>
<?php unset($_SESSION['show_welcome']); ?>
<?php endif; ?>
