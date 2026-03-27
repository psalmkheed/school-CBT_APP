<?php
require __DIR__ . '/../auth/check.php';

// Only staff can access this page
if ($user->role !== 'staff') {
      header("Location: {$base}auth/login.php");
      exit();
}

// redirect to configuration page if school settings is not yet configured
// -------------------------------------------------------------//
$check_config = $conn->prepare('SELECT * FROM school_config');
$check_config->execute();

if ($check_config->rowCount() < 1) {
      session_unset();
      session_destroy();
      header("Location: {$base}index.php");
}
;
// -------------------------------------------------------------//

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

// Count unread messages
$unread_stmt = $conn->prepare("SELECT COUNT(*) FROM broadcast WHERE recipient = :recipient AND is_read = 0");
$unread_stmt->execute([':recipient' => $_SESSION['username']]);
$unread_messages = (int) $unread_stmt->fetchColumn();

// Count total students across all assigned classes
$count_students = $conn->prepare("
    SELECT COUNT(id) FROM users 
    WHERE role = 'student' 
    AND class IN (
        SELECT c.class 
        FROM class c 
        WHERE c.teacher_id = :tid
    )
");
$count_students->execute([':tid' => $user->id]);
$total_students = (int) $count_students->fetchColumn();

$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Count total exams
$count_exams = $conn->prepare("
    SELECT COUNT(*) 
    FROM exams e 
    WHERE e.subject_teacher = :subject_teacher 
    AND e.session = :session AND e.term = :term
    AND e.class IN (
        SELECT c.class 
        FROM teacher_assignments ta 
        JOIN class c ON ta.class_id = c.id 
        WHERE ta.teacher_id = :tid
    )
"); 
$count_exams->execute([
    ':subject_teacher' => $_SESSION['first_name'] . ' ' . $_SESSION['surname'],
    ':tid' => $user->id,
    ':session' => $active_session,
    ':term' => $active_term
]);
$total_exams = (int) $count_exams->fetchColumn();

// --- Performance Analytics Data ---
$perf_stmt = $conn->prepare("
    SELECT e.subject, AVG(r.percentage) as avg_score, COUNT(r.id) as attempts
    FROM exams e
    JOIN exam_results r ON e.id = r.exam_id
    WHERE e.subject_teacher = :teacher
    AND e.session = :session AND e.term = :term
    AND e.class IN (
        SELECT c.class 
        FROM teacher_assignments ta 
        JOIN class c ON ta.class_id = c.id 
        WHERE ta.teacher_id = :tid
    )
    GROUP BY e.id, e.subject
    ORDER BY MAX(r.taken_at) DESC
    LIMIT 10
");
$perf_stmt->execute([
    ':teacher' => $_SESSION['first_name'] . ' ' . $_SESSION['surname'],
    ':tid' => $user->id,
    ':session' => $active_session,
    ':term' => $active_term
]);
$performance_data = $perf_stmt->fetchAll(PDO::FETCH_OBJ);

$chart_labels = [];
$chart_values = [];
foreach ($performance_data as $p) {
      $chart_labels[] = $p->subject;
      $chart_values[] = round($p->avg_score, 1);
}
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

                  <!-- Welcome Banner -->
                  <div class="relative overflow-hidden bg-gradient-to-br from-sky-600 via-blue-500 to-blue-400 rounded-2xl p-6 md:p-8 mb-6 shadow-lg">
                        <!-- Decorative circles -->
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full"></div>
                        <div class="absolute top-1/2 right-1/4 w-20 h-20 bg-white/5 rounded-full"></div>

                        <div class="relative z-10">
                              <p class="text-green-100 text-sm font-medium mb-1">
                                    <?= date('l, F j, Y') ?>
                              </p>
                              <h3 class="text-2xl md:text-3xl font-semibold text-white mb-1">
                                    Welcome back, <?= ucfirst($user->first_name) ?> 👋
                              </h3>
                              <p class="text-green-100 text-sm">
                                    Here's your staff dashboard overview.
                              </p>
                        </div>
                  </div>

                  <!-- Stats Cards -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">

                        <!-- Students Card -->
                        <div data-url="<?= $base ?>staff/pages/students.php" 
                              class="ajax-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-200 flex-shrink-0">
                                    <i class="bx-group text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Students</p>
                                    <h3 class="text-3xl md:text-4xl font-bold text-gray-800 leading-tight">
                                          <?= $total_students ?>
                                    </h3>
                              </div>
                        </div>

                        <!-- Exams Card -->
                        <div data-url="<?= $base ?>staff/pages/exams.php" 
                              class="ajax-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-200 flex-shrink-0">
                                    <i class="bx-book-open text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Exams</p>
                                    <h3 class="text-3xl md:text-4xl font-bold text-gray-800 leading-tight">
                                          <?= $total_exams ?>
                                    </h3>
                              </div>
                        </div>

                        <!-- Messages Card -->
                        <div id="notification_toggler" 
                              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-200 flex-shrink-0">
                                    <i class="bx-envelope text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Messages</p>
                                    <h3 class="text-3xl md:text-4xl font-bold text-gray-800 leading-tight">
                                          <?= $unread_messages ?>
                                    </h3>
                              </div>
                        </div>
                  </div>

                  <!-- Quick Action Cards -->
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">

                        <!-- Manage Students Card -->
                        <div class="ajax-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer"
                              data-url="<?= $base ?>staff/pages/students.php">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-lg shadow-red-200 flex-shrink-0">
                                    <i class="bx-group text-2xl text-white"></i>
                              </div>
                              <div>
                                    <h3 class="text-base font-semibold text-gray-800 group-hover:text-red-600 transition">Manage Students</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">View and update student records for your class</p>
                              </div>
                        </div>

                        <!-- Manage Exams Card -->
                        <div class="ajax-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer"
                              data-url="<?= $base ?>staff/pages/exams.php">
                              <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-200 flex-shrink-0">
                                    <i class="bx bxs-file-detail text-2xl text-white"></i>
                              </div>
                              <div>
                                    <h3 class="text-base font-semibold text-gray-800 group-hover:text-blue-600 transition">Manage Exams</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Set questions and view exam status</p>
                              </div>
                        </div>

                        <div class="ajax-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 cursor-pointer"
                              data-url="<?= $base ?>staff/pages/study_materials.php?tab=All">
      <div
            class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-200 flex-shrink-0">
            <i class="bx bx-book-content text-2xl text-white"></i>
      </div>
      <div>
            <h3 class="text-base font-semibold text-gray-800 group-hover:text-indigo-600 transition">Study Materials
            </h3>
            <p class="text-xs text-gray-400 mt-0.5">Upload notes and resources for students</p>
      </div>
</div>
                  </div>

                  <!-- Quick Actions Row -->
                  <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Quick Actions</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                              <button onclick="$('#sideStudents').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center group-hover:bg-orange-100 transition">
                                          <i class="bx-group text-lg text-orange-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Students</span>
                              </button>
                              <button onclick="$('#sideStaffExams').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition">
                                          <i class="bx-pencil text-lg text-blue-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Exams</span>
                              </button>
                              <button onclick="$('#sideResults').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center group-hover:bg-indigo-100 transition">
                                          <i class="bx-bar-chart text-lg text-indigo-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Results</span>
                              </button>
                               <button onclick="$('#staffStudyUpload').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100 transition">
                                          <i class="bx bx-book-content text-lg text-emerald-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Library</span>
                              </button>
                              <button onclick="$('#sideProfile').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition">
                                          <i class="bx-user text-lg text-purple-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Profile</span>
                              </button>
                        </div>
                  </div>

                  <!-- Charts & Messages Row -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Performance Analytics Chart -->
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                              <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                <i class="bx bx bx-trending-up text-xl"></i>
                                          </div>
                                          <div>
                                                <h4 class="text-sm font-bold text-gray-800">Performance Analytics</h4>
                                                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Average Scores per Exam</p>
                                          </div>
                                    </div>
                              </div>
                              <div class="h-64">
                                    <canvas id="performanceChart"></canvas>
                                    <script>
                                          (function() {
                                                const ctx = document.getElementById('performanceChart').getContext('2d');
                                                new Chart(ctx, {
                                                      type: 'line',
                                                      data: {
                                                            labels: <?= json_encode(array_reverse($chart_labels)) ?>,
                                                            datasets: [{
                                                                  label: 'Avg Score (%)',
                                                                  data: <?= json_encode(array_reverse($chart_values)) ?>,
                                                                  borderColor: '#6366f1',
                                                                  backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                                                  borderWidth: 3,
                                                                  fill: true,
                                                                  tension: 0.4,
                                                                  pointRadius: 4,
                                                                  pointBackgroundColor: '#6366f1'
                                                            }]
                                                      },
                                                      options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            plugins: { legend: { display: false } },
                                                            scales: {
                                                                  y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.03)' } },
                                                                  x: { grid: { display: false } }
                                                            }
                                                      }
                                                });
                                          })();
                                    </script>
                              </div>
                        </div>

                        <!-- Recent Messages -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                              <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="bx-bell text-blue-600"></i>
                                          </div>
                                          <h4 class="text-sm font-semibold text-gray-800">Recent Messages</h4>
                                    </div>
                                    <?php if ($unread_messages > 0): ?>
                                                                        <span class="text-xs bg-red-100 text-red-600 font-bold px-2 py-0.5 rounded-full"><?= $unread_messages ?>
                                                                        unread</span>
                                                                  <?php else: ?>
                                                                  <span class="text-xs text-gray-400"><?= count($results) ?> messages</span>
                                                                  <?php endif ?>
                                                                  </div>
                                                                  <?php if (count($results) > 0): ?>
                                                                  <div class="space-y-3 max-h-64 overflow-y-auto">
                                                                        <?php foreach (array_slice($results, 0, 5) as $msg): ?>
                                                                        <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                                                                              <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                                                    <i class="bx-envelope text-green-600 text-sm"></i>
                                                                              </div>
                                                                              <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-gray-700 truncate">
                                                                  <?= htmlspecialchars($msg->subject ?? 'No Subject') ?>
                                                            </p>
                                                            <p class="text-xs text-gray-400 mt-0.5">
                                                                  <?= htmlspecialchars($msg->username ?? '') ?> <?php if (!empty($msg->created_at)): ?> •
                                                                        <?= date('M j', strtotime($msg->created_at)) ?> <?php endif ?>
                                                            </p>
                                                            </div>
                                                            </div>
                                                            <?php endforeach ?>
                                                            </div>
                                                            <?php else: ?>
                                                            <div class="text-center py-8">
                                                                  <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                                                        <i class="bx-envelope text-gray-400 text-xl"></i>
                                                                  </div>
                                                                  <p class="text-sm text-gray-400">No messages yet</p>
                                                            </div>
                                                            <?php endif ?>
                        </div>
                  </div>

            </div>
      </div>
</main>

<?php require '../components/notification.php'; ?>
<?php require '../components/support_modal.php'; ?>
<?php require '../components/footer.php';?>

<!-- greetings Modal -->
<?php if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true): ?>
<div class="w-full h-screen bg-black/90 flex items-center justify-center fixed top-0 left-0 z-[99999] backdrop-blur-md"
      id="greetingsModal">
      <!-- msg container -->
      <div class="relative rounded-2xl bg-white p-6 shadow-md border-gray-200/80 w-[450px] max-w-[95%] backdrop-blur-sm fade-in-bottom" id="greetingMsgContainer">
            <!-- close button -->
             <button type="button" class="bg-red-500 hover:bg-red-600 transition cursor-pointer flex items-center justify-center rounded-full p-1.5 hover:shadow-md top-4 right-4 absolute" onclick="document.getElementById('greetingsModal').classList.add('hidden')">
                        <i class="bx-x text-2xl text-white"></i>
                  </button>
            <!-- msg box -->
            <div class="flex flex-col gap-4">
                  <div class="flex flex-col items-center gap-2">
                        <div class="w-16 h-16 rounded-full bg-sky-100 flex items-center justify-center mb-2">
                              <i class="bx-briefcase text-4xl text-sky-600"></i>
                        </div>
                        <h3 class="text-2xl font-semibold text-gray-800 text-center">Hello, Educator! 🎓</h3>
                        <p class="text-sm font-semibold text-gray-400 uppercase tracking-widest"><?= strtoupper(explode(' ', $result->school_name ?? 'School')[0])?> Staff Portal</p>
                  </div>
                  
                  <p class="text-lg text-gray-500 text-center leading-relaxed">
                        Welcome back, <span class="text-sky-600 font-bold"><?= ucfirst($_SESSION['first_name']) ?></span>. <br>
                        Ready to manage your classroom and exams for today?
                  </p>

                  <div class="grid grid-cols-2 gap-3">
                        <div class="bg-sky-50/50 p-3 rounded-xl border border-sky-100 flex flex-col items-center gap-1 cursor-pointer hover:bg-sky-100 transition-all"
                              onclick="document.getElementById('greetingsModal').classList.add('hidden'); $('#sideStaffExams').click();">
                              <i class="bx-pencil text-green-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Set Exams</span>
                        </div>
                        <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100 flex flex-col items-center gap-1">
                              <i class="bx-group text-blue-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Students</span>
                        </div>
                        <div class="bg-orange-50/50 p-3 rounded-xl border border-orange-100 flex flex-col items-center gap-1">
                              <i class="bx-news text-orange-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Broadcast</span>
                        </div>
                        <div class="bg-purple-50/50 p-3 rounded-xl border border-purple-100 flex flex-col items-center gap-1">
                              <i class="bx-cog text-purple-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Settings</span>
                        </div>
                  </div>

                  <button type="button" 
                        class="w-full bg-sky-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-sky-700 hover:shadow-sky-200 transition-all cursor-pointer"
                        onclick="enterAppMode(); document.getElementById('greetingsModal').classList.add('hidden')">
                        Enter Dashboard
                  </button>
                  
                  <p class="text-[11px] text-gray-400 text-center italic">Manage your tools and stay updated with school broadcasts!</p>
            </div>
      </div>
</div>
<?php unset($_SESSION['show_welcome']); ?>
<?php endif; ?>
