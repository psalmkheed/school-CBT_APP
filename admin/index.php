<?php
require __DIR__ . '/../auth/check.php';

// redirect to configuration page if school settings is not yet configured
// -------------------------------------------------------------//
$check_config = $conn->prepare('SELECT * FROM school_config');
$check_config->execute();

if ($check_config->rowCount() < 1) {
      session_unset();
      session_destroy();
      header("Location: /school_app/index.php");
}
;
// -------------------------------------------------------------//

// Only admins can access this page
if ($user->role !== 'admin') {
      header("Location: /school_app/auth/login.php");
      exit();
}

$stmt = $conn->prepare("
    SELECT * FROM broadcast
    WHERE recipient = :recipient OR recipient = 'ADMIN_SUPPORT'
    ORDER BY created_at DESC
");

$stmt->execute([
      ':recipient' => $_SESSION['username']
]);

$results = $stmt->fetchAll(PDO::FETCH_OBJ);

// Core Counts
$count_student_result = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?: 0;
$count_staff_result = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn() ?: 0;

// Get Active Session/Term dates for calculations
$active_session_query = $conn->prepare("SELECT session_start_date, session_end_date FROM sch_session WHERE status = 1 LIMIT 1");
$active_session_query->execute();
$current_session = $active_session_query->fetch(PDO::FETCH_ASSOC);

$prev_session_query = $conn->prepare("SELECT session_start_date, session_end_date FROM sch_session WHERE status = 0 ORDER BY id DESC LIMIT 1");
$prev_session_query->execute();
$prev_session = $prev_session_query->fetch(PDO::FETCH_ASSOC);

function calculateGrowth($conn, $role, $current_session, $prev_session)
{
      if (!$current_session || !$prev_session)
            return ['percent' => 0, 'type' => 'neutral'];

      // Count current session arrivals
      $curr_count = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = ? AND created_at >= ? AND created_at <= ?");
      $curr_count->execute([$role, $current_session['session_start_date'], $current_session['session_end_date']]);
      $curr = $curr_count->fetchColumn() ?: 0;

      // Count previous session arrivals
      $prev_count = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = ? AND created_at >= ? AND created_at <= ?");
      $prev_count->execute([$role, $prev_session['session_start_date'], $prev_session['session_end_date']]);
      $prev = $prev_count->fetchColumn() ?: 0;

      if ($prev == 0)
            return ['percent' => $curr > 0 ? 100 : 0, 'type' => 'increment'];

      $diff = (($curr - $prev) / $prev) * 100;
      return [
            'percent' => round(abs($diff), 1),
            'type' => $diff >= 0 ? 'increment' : 'decrement'
      ];
}

$student_growth = calculateGrowth($conn, 'student', $current_session, $prev_session);
$staff_growth = calculateGrowth($conn, 'staff', $current_session, $prev_session);

// --- 6-Month Chart Data ---
$months = [];
$student_month_data = [];
$staff_month_data = [];
for ($i = 5; $i >= 0; $i--) {
      $date = date('Y-m', strtotime("-$i months"));
      $month_name = date('M', strtotime("-$i months"));
      $months[] = $month_name;

      // Student count for this month
      $stmt_st = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
      $stmt_st->execute([$date]);
      $student_month_data[] = $stmt_st->fetchColumn() ?: 0;

      // Staff count for this month
      $stmt_sf = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'staff' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
      $stmt_sf->execute([$date]);
      $staff_month_data[] = $stmt_sf->fetchColumn() ?: 0;
}
?>


<?php
require '../connections/db.php';
// class populate query
$class_populate = $conn->prepare("SELECT class FROM class");
$class_populate->execute();
$rows = $class_populate->fetchAll(PDO::FETCH_OBJ);

// subject populate query
$subject_populate = $conn->prepare("SELECT subject FROM subjects");
$subject_populate->execute();
$subjects = $subject_populate->fetchAll(PDO::FETCH_OBJ);

// teacher populate query
$teacher_populate = $conn->prepare('SELECT * FROM users WHERE role = "staff"');
$teacher_populate->execute();
$all_staff = $teacher_populate->fetchAll(PDO::FETCH_OBJ);

?>

<?php require __DIR__ . '/components/header.php'; ?>
<?php require __DIR__ . '/components/sidebar.php'; ?>

<!-- right nav -->
<main class="ml-0 md:ml-72">
      <!-- top Nav -->
      <?php require '../components/navbar.php'; ?>

      <!-- Main Content -->
      <div class="flex w-full" id="mainContent">
            <div class="fadeIn w-full md:p-8 p-4">
                  <!-- Welcome Banner -->
                  <div
                        class="relative overflow-hidden bg-gradient-to-br from-green-600 via-green-500 to-green-300 rounded-2xl p-6 md:p-8 mb-6 shadow-lg shadow-green-100">
                        <!-- Decorative circles -->
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full"></div>
                        <div class="absolute top-1/2 right-1/4 w-20 h-20 bg-white/5 rounded-full"></div>

                        <div class="relative z-10 md:flex justify-between items-center">
                              <div>
                                    <p class="text-emerald-50 text-sm font-medium mb-1">
                                          <?= date('l, F j, Y') ?>
                                    </p>
                                    <h3 class="text-2xl md:text-3xl font-bold text-white mb-1">
                                          Welcome back, <?= ucfirst($user->role) ?> 👋
                                    </h3>
                                    <p class="text-emerald-50 text-sm opacity-90">
                                          Welcome back to your administrative command center.
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
                                          <?php else: ?>
                                                <div
                                                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-500 backdrop-blur-md border border-red-400/20 text-red-100 text-xs font-semibold animate-pulse">
                                                      <i class="bx-error-circle text-sm"></i>
                                                      Create a new Session
                                                </div>
                                          <?php endif; ?>
                                    </div>
                              </div>

                              <button
                                    class="mt-4 md:mt-0 rounded-xl px-5 py-2.5 flex gap-2 items-center text-sm font-semibold border transition-all duration-300 <?= $is_session_active ? 'bg-gray-400/20 text-gray-300 cursor-not-allowed border-gray-400/30' : 'bg-white/20 backdrop-blur-sm text-white cursor-pointer hover:bg-white/30 hover:shadow-md border-white/30' ?>"
                                    id="<?= $is_session_active ? 'sessionDisabled' : 'createSession' ?>"
                                    <?= $is_session_active ? 'data-tippy-content="A term is currently active. You can create a new one after the current one expires."' : '' ?>>
                                    <i class="<?= $is_session_active ? 'bx-lock' : 'bxs-calendar-plus' ?> text-lg"></i>
                                    New Session
                              </button>
                        </div>
                  </div>

                  <!-- Stats Cards -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">

                        <!-- Students Card -->
                        <div
                              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-200 flex-shrink-0">
                                    <i class="bx-group text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total
                                          Students</p>
                                    <h3 class="text-3xl md:text-3xl font-extrabold text-gray-800 leading-tight">
                                          <?= $count_student_result ?: 0 ?>
                                    </h3>
                                    <p class="text-[10px] text-gray-400">
                                          <?php if ($student_growth['type'] == 'increment'): ?>
                                                <span
                                                      class="text-emerald-700 bg-emerald-100/80 rounded-full px-1.5 py-0.5 font-bold">+<?= $student_growth['percent'] ?>%</span>
                                                than last term
                                          <?php else: ?>
                                                <span
                                                      class="text-red-700 bg-red-100/80 rounded-full px-1.5 py-0.5 font-bold">-<?= $student_growth['percent'] ?>%</span>
                                                than last term
                                          <?php endif; ?>
                                    </p>
                              </div>
                        </div>

                        <!-- Staff Card -->
                        <div
                              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-200 flex-shrink-0">
                                    <i class="bx-briefcase text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Staff
                                    </p>
                                    <h3 class="text-3xl md:text-3xl font-extrabold text-gray-800 leading-tight">
                                          <?= $count_staff_result ?: 0 ?>
                                    </h3>
                                    <p class="text-[10px] text-gray-400">
                                          <?php if ($staff_growth['type'] == 'increment'): ?>
                                                <span
                                                      class="text-emerald-700 bg-emerald-100/80 rounded-full px-1.5 py-0.5 font-bold">+<?= $staff_growth['percent'] ?>%</span>
                                                than last term
                                          <?php else: ?>
                                                <span
                                                      class="text-red-700 bg-red-100/80 rounded-full px-1.5 py-0.5 font-bold">-<?= $staff_growth['percent'] ?>%</span>
                                                than last term
                                          <?php endif; ?>
                                    </p>
                              </div>
                        </div>

                        <!-- Quick Action Card -->
                        <div
                              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-200 flex-shrink-0">
                                    <i class="bx-book-open text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Users
                                    </p>
                                    <h3 class="text-3xl md:text-4xl font-extrabold text-gray-800 leading-tight">
                                          <?= ($count_student_result ?: 0) + ($count_staff_result ?: 0) ?>
                                    </h3>
                              </div>
                        </div>
                  </div>

                  <!-- Charts & Support Row -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Registration Trends Chart -->
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                              <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                          <div
                                                class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                                <i class="bx bx-trending-up text-xl"></i>
                                          </div>
                                          <div>
                                                <h4 class="text-sm font-bold text-gray-800">Registration Trends</h4>
                                                <p
                                                      class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">
                                                      6-Month
                                                      Growth Analysis</p>
                                          </div>
                                    </div>
                              </div>
                              <div class="h-64">
                                    <canvas id="growthChart"></canvas>
                                    <script>
                                          (function() {
                                                window.initGrowthChart = function () {
                                                      const canvas = document.getElementById('growthChart');
                                                      if (!canvas) return;
                                                      if (window.theGrowthChart instanceof Chart) window.theGrowthChart.destroy();
                                                      const ctx = canvas.getContext('2d');
                                                      window.theGrowthChart = new Chart(ctx, {
                                                            type: 'bar', 
                                                            data: {
                                                                  labels: <?= json_encode($months) ?>,
                                                                  datasets: [{
                                                                        label: 'Students',
                                                                        data: <?= json_encode($student_month_data) ?>,
                                                                        backgroundColor: '#10b981',
                                                                        borderRadius: 6,
                                                                        barThickness: 16,
                                                                  }, {
                                                                        label: 'Staff',
                                                                        data: <?= json_encode($staff_month_data) ?>,
                                                                        backgroundColor: '#6366f1',
                                                                        borderRadius: 6,
                                                                        barThickness: 16,
                                                                  }]
                                                            },
                                                            options: {
                                                                  responsive: true,
                                                                  maintainAspectRatio: false,
                                                                  plugins: {
                                                                        legend: {
                                                                              display: true,
                                                                              position: 'bottom',
                                                                              labels: { usePointStyle: true, font: { size: 10, weight: '700' } }
                                                                        }
                                                                  },
                                                                  scales: {
                                                                        y: { beginAtZero: true, grid: { display: true, color: 'rgba(0,0,0,0.03)' }, ticks: { stepSize: 1 } },
                                                                        x: { grid: { display: false } }
                                                                  }
                                                            }
                                                      });
                                                };
                                                window.initGrowthChart();
                                          })();
                                    </script>
                              </div>
                        </div>

                        <!-- Admin Support & Chat Inbox -->
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                              <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                          <div
                                                class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                                <i class="bx bx-send text-xl"></i>
                                          </div>
                                          <div>
                                                <h4 class="text-sm font-bold text-gray-800">Support & Chat Inbox</h4>
                                                <p
                                                      class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">
                                                      Direct
                                                      Inquiries</p>
                                          </div>
                                    </div>
                                    <button onclick="openChatSupport()"
                                          class="px-4 py-1.5 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-bold uppercase tracking-widest hover:bg-emerald-100 transition cursor-pointer">
                                          Launch Support <i class="bx bx-right-top-arrow-circle align-middle ml-1"></i>
                                    </button>
                              </div>
                              <?php if (count($results) > 0): ?>
                                                <div class="space-y-4 max-h-64 overflow-y-auto pr-1">
                                                <?php foreach (array_slice($results, 0, 4) as $msg): ?>
                                                      <div onclick='openChatSupport(<?= json_encode($msg->username) ?>, <?= json_encode($msg->subject) ?>, <?= json_encode($msg->message) ?>, <?= json_encode(date("h:i A", strtotime($msg->created_at))) ?>)'
                                                      class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-emerald-50/50 transition-all duration-300 border border-transparent hover:border-emerald-100 group cursor-pointer">
                                                      <div class="relative">
                                                            <div
                                                      class="w-10 h-10 rounded-full bg-gray-100 border-2 border-white shadow-sm flex items-center justify-center overflow-hidden">
                                                      <i class="bx bx-user text-lg text-gray-400"></i>
                                                </div>
                                                <span
                                                      class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full"></span>
                                          </div>
                                          <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-center mb-0.5">
                                                      <p class="text-xs font-bold text-gray-800 truncate">
                                                            <?= htmlspecialchars($msg->username ?? 'Sender') ?>
                                                            </p>
                                                            <span
                                                                  class="text-[9px] font-semibold text-gray-400"><?= date('M j, h:i A', strtotime($msg->created_at ?? 'now')) ?></span>
                                                      </div>
                                                      <p class="text-[11px] text-gray-500 truncate font-semibold">
                                                            <?= htmlspecialchars($msg->subject ?? 'New Inquiry Message') ?>
                                                      </p>
                                          </div>
                                          <div
                                                class="w-1.5 h-1.5 rounded-full bg-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                          </div>
                                    </div>
                                          <?php endforeach ?>
                                          </div>
                                          <?php else: ?>
                                    <div
                                          class="text-center py-10 bg-gray-50/50 rounded-3xl border border-dashed border-gray-200">
                                          <div
                                                class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center mx-auto mb-4">
                                                <i class="bx bx-message text-gray-300 text-2xl"></i>
                                          </div>
                                          <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Your Inbox is
                                                Clear</p>
                                          <p class="text-[10px] text-gray-400 mt-1">No pending support inquiries at the
                                                moment.</p>
                                    </div>
                              <?php endif ?>
                                          </div>
                  </div>

            </div>
      </div>

</main>
<!-- Create Session form modal  -->
<div class="fixed inset-0 bg-black/90 z-[99999] flex items-center justify-center hidden backdrop-blur-md"
      id="sessionModal">
      <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden fadeIn max-h-[90vh] flex flex-col">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 shrink-0">
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                              <i class="bx-calendar text-emerald-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800"> Academic Session Configuration ⚙️</h3>
                  </div>
                  <button type="button" class="text-gray-400 hover:text-gray-600 transition cursor-pointer"
                        onclick="document.getElementById('sessionModal').classList.add('hidden')">
                        <i class="bx-x text-2xl"></i>
                  </button>
            </div>

            <!-- Modal Body -->
            <form class="p-6 flex flex-col gap-6 overflow-y-auto" id="sessionForm" tabindex="-1">

                  <!-- Session Name Section -->
                  <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                        <label for="session"
                              class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">School
                              Session Year</label>
                        <select id="session" name="session" required
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent transition bg-white shadow-sm">
                              <option value="" disabled selected>Select Academic Session</option>
                              <?php
                              $currentYear = date('Y') - 1;
                              for ($i = 0; $i < 5; $i++) {
                                    $year = ($currentYear + $i) . "/" . ($currentYear + $i + 1);
                                    echo "<option value='$year'>$year</option>";
                              }
                              ?>
                        </select>
                  </div>

                  <!-- Dynamic Terms Container -->
                  <div id="terms-container" class="flex flex-col gap-6 font-primary">
                        <!-- Term Block 1 (Always Visible) -->
                        <div
                              class="term-block relative bg-white p-5 rounded-2xl border-2 border-gray-100 transition-all duration-300 hover:border-emerald-100 group">
                              <div class="flex items-center gap-2 mb-4">
                                    <span
                                          class="w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">1</span>
                                    <h4 class="text-sm font-bold text-gray-700">Initial Academic Term</h4>
                              </div>

                              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                          <label
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Term
                                                Name</label>
                                          <select name="terms[0][term]" required
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition bg-white">
                                                <option value="First Term">First Term</option>
                                                <option value="Second Term">Second Term</option>
                                                <option value="Third Term">Third Term</option>
                                          </select>
                                    </div>
                                    <div>
                                          <label
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Starts
                                                On</label>
                                          <input type="date" name="terms[0][start_date]" required
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition">
                                    </div>
                                    <div>
                                          <label
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Ends
                                                On</label>
                                          <input type="date" name="terms[0][end_date]" required
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition">
                                    </div>
                              </div>
                        </div>
                  </div>

                  <!-- Actions Section -->
                  <div class="flex flex-col gap-3 py-2 shrink-0">
                        <button type="button" id="add-term-btn"
                              class="w-full py-3 border-2 border-dashed border-gray-200 rounded-xl text-gray-400 hover:text-emerald-600 hover:border-emerald-400 hover:bg-emerald-50/50 transition-all duration-300 text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 group cursor-pointer">
                              <i class="bx bx-plus-circle text-lg group-hover:scale-110 transition-transform"></i>
                              Add Another Term to this Session
                        </button>

                        <button type="submit"
                              class="w-full bg-emerald-600 text-white py-4 rounded-xl font-bold text-sm shadow-lg shadow-emerald-100 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer"
                              id="sessionBtn">
                              Save & Activate Session
                        </button>
                  </div>
            </form>
      </div>
</div>


<!-- ---------------------------------------------------------------------- -->


<!-- greetings Modal -->
<?php if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true): ?>
      <div class="w-full h-screen bg-black/90 flex items-center justify-center fixed top-0 left-0 z-[99999] backdrop-blur-md"
            id="greetingsModal">
            <!-- msg container -->
            <div class="relative rounded-2xl bg-white p-6 shadow-md border-gray-200/80 w-[450px] max-w-[95%] backdrop-blur-sm fade-in-bottom"
                  id="greetingMsgContainer">
                  <!-- close button -->
                  <button type="button"
                        class="bg-red-500 hover:bg-red-600 transition cursor-pointer flex items-center justify-center rounded-full p-1.5 hover:shadow-md top-4 right-4 absolute"
                        onclick="document.getElementById('greetingsModal').classList.add('hidden')">
                        <i class="bx-x text-2xl text-white"></i>
                  </button>
                  <!-- msg box -->
                  <div class="flex flex-col gap-4">
                        <div class="flex flex-col items-center gap-2">
                              <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-2">
                                    <i class="bx-shield-quarter text-4xl text-emerald-600"></i>
                              </div>
                              <h3 class="text-2xl font-bold text-gray-800 text-center">Admin Portal</h3>
                              <p class="text-sm font-semibold text-gray-400 uppercase tracking-widest">
                                    <?= strtoupper(explode(' ', $result->school_name ?? 'SCHOOL')[0]) ?> Management System
                              </p>
                              </div>
                              
                              <p class="text-lg text-gray-500 text-center leading-relaxed">
                              Welcome, <span
                                    class="text-emerald-600 font-bold"><?= ucfirst($_SESSION['first_name']) ?></span>. <br>
                              Ready to manage your academic ecosystem?
                              </p>

                        <div class="grid grid-cols-2 gap-3">
                              <div
                                    class="bg-blue-50/50 p-3 rounded-xl border border-blue-100 flex flex-col items-center gap-1">
                                    <i class="bx-user-plus text-blue-600 text-xl"></i>
                                    <span class="text-xs font-bold text-gray-700">Add Users</span>
                              </div>
                              <div
                                    class="bg-green-50/50 p-3 rounded-xl border border-green-100 flex flex-col items-center gap-1">
                                    <i class="bx-calendar text-green-600 text-xl"></i>
                                    <span class="text-xs font-bold text-gray-700">Sessions</span>
                              </div>
                              <div
                                    class="bg-orange-50/50 p-3 rounded-xl border border-orange-100 flex flex-col items-center gap-1">
                                    <i class="bx-envelope text-orange-600 text-xl"></i>
                                    <span class="text-xs font-bold text-gray-700">Broadcast</span>
                              </div>
                              <div
                                    class="bg-purple-50/50 p-3 rounded-xl border border-purple-100 flex flex-col items-center gap-1">
                                    <i class="bx-book-add text-purple-600 text-xl"></i>
                                    <span class="text-xs font-bold text-gray-700">Exams</span>
                              </div>
                        </div>

                        <button type="button"
                              class="w-full bg-emerald-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-emerald-100 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all cursor-pointer"
                              onclick="enterAppMode(); document.getElementById('greetingsModal').classList.add('hidden')">
                              Launch Dashboard
                        </button>
                  </div>
            </div>
      </div>
      <?php unset($_SESSION['show_welcome']); ?>
<?php endif; ?>
<!-- Admin Support Chat Modal -->
<div id="chatSupportModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-md z-[900] hidden flex items-center justify-center md:p-4">
      <div id="chatModalContent"
            class="bg-white md:rounded-3xl w-full h-full md:w-[35%] md:max-w-6xl md:h-[85vh] flex flex-col shadow-2xl fade-in-bottom overflow-hidden">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-emerald-50/30">
                  <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
                              <i class="bx bx-headphone-mic text-xl"></i>
                        </div>
                        <div>
                              <h3 class="text-sm font-bold text-gray-800">Support Helper</h3>
                              <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest"
                                          id="activeChatUser">Active Helpdesk</span>
                              </div>
                        </div>
                  </div>
                  <button onclick="document.getElementById('chatSupportModal').classList.add('hidden')"
                        class="w-8 h-8 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-500 transition-all flex items-center justify-center cursor-pointer">
                        <i class="bx bx-x text-xl"></i>
                  </button>
            </div>

            <!-- Chat Messages Area -->
            <div class="flex-1 p-6 overflow-y-auto space-y-6 bg-gray-50/50" id="chatMessageArea">
                  <div class="flex flex-col items-center justify-center h-full text-center py-20">
                        <div
                              class="w-20 h-20 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                              <i class="bx bx-envelope text-4xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800">Start a Conversation</h4>
                        <p class="text-xs text-gray-400 mt-1 max-w-[250px] mx-auto font-medium">Select a user from your
                              inbox to begin supporting their needs.</p>
                  </div>
            </div>

            <!-- Chat Input Area -->
            <div class="p-6 border-t border-gray-100 bg-white">
                  <div class="relative">
                        <textarea id="chatReplyText" rows="1" placeholder="Type your support message..."
                              class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:bg-white transition-all shadow-sm pr-12 resize-none"></textarea>
                        <button onclick="sendReply()" id="sendReplyBtn"
                              class="absolute right-2 top-1.5 w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center hover:bg-emerald-700 transition shadow-lg shadow-emerald-100 cursor-pointer">
                              <i class="bx bx-send text-xl mb-1"></i>
                        </button>
                  </div>
            </div>
      </div>
</div>

<script>
      // Support Chat Logic & Close Handler
      const chatModal = document.getElementById('chatSupportModal');
      const chatContent = document.getElementById('chatModalContent');

      let currentChatUser = '';
      let currentChatSubject = '';

      function openChatSupport(username = 'Support Desk', subject = '', message = '', time = '') {
            currentChatUser = username;
            currentChatSubject = subject;
            document.getElementById('activeChatUser').innerText = username;
            chatModal.classList.remove('hidden');

            // Reset Chat area with the actual received message
            if (username !== 'Support Desk') {
                  document.getElementById('chatMessageArea').innerHTML = `
              <div class="flex flex-col gap-4">
                  <div class="flex flex-col items-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-white shadow-sm border-4 border-emerald-50 overflow-hidden flex items-center justify-center">
                              <i class="bx bx-user text-3xl text-gray-400"></i>
                        </div>
                        <h4 class="text-sm font-bold text-gray-800 mt-2">${username}</h4>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Inquiry Ticket #Live</span>
                  </div>
                  
                  <div class="bg-white p-4 rounded-2xl rounded-tl-none border border-gray-100 shadow-sm max-w-[85%]">
                        <div class="text-[9px] font-bold text-emerald-600 mb-1 uppercase tracking-wider">Subject: ${subject}</div>
                        <p class="text-sm text-gray-600 leading-relaxed font-semibold">${message}</p>
                        <span class="text-[9px] text-gray-400 mt-2 block font-bold">${time}</span>
                  </div>
                  <div id="replyContainer" class="flex flex-col gap-4"></div>
              </div>
          `;
            }
      }

      function sendReply() {
            const message = document.getElementById('chatReplyText').value.trim();
            if (!message || !currentChatUser || currentChatUser === 'Support Desk') return;

            const btn = document.getElementById('sendReplyBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt animate-spin text-xl"></i>';

            $.ajax({
                  url: 'auth/broadcastAuth.php',
                  type: 'POST',
                  data: {
                        recipient: currentChatUser,
                        subject: 'Support Reply: ' + currentChatSubject,
                        message: message
                  },
                  success: function (response) {
                        if (response.status === 'success') {
                              const replyHtml = `
                    <div class="bg-emerald-600 p-4 rounded-2xl rounded-tr-none text-white shadow-md max-w-[85%] ml-auto animate-in fade-in slide-in-from-right-2">
                        <p class="text-sm leading-relaxed font-bold">${message}</p>
                        <span class="text-[9px] text-emerald-100 mt-2 block font-bold">Just Now • SENT</span>
                    </div>
                `;
                              document.getElementById('replyContainer').innerHTML += replyHtml;
                              document.getElementById('chatReplyText').value = '';

                              // Scroll to bottom
                              const area = document.getElementById('chatMessageArea');
                              area.scrollTop = area.scrollHeight;
                        } else {
                              Swal.fire('Error', response.message, 'error');
                        }
                  },
                  error: function () {
                        Swal.fire('Error', 'Failed to send reply', 'error');
                  },
                  complete: function () {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bx bx-send text-xl mb-1"></i>';
                  }
            });
      }

      // Click outside to close
      window.onclick = function (event) {
            if (event.target == chatModal) {
                  chatModal.classList.add('hidden');
            }
            // Also handle greetings and session modals if they use overlay IDs
            const greetingsModal = document.getElementById('greetingsModal');
            if (event.target == greetingsModal) {
                  greetingsModal.classList.add('hidden');
            }
      }

      document.addEventListener('DOMContentLoaded', () => {
            if(typeof window.initGrowthChart === 'function') window.initGrowthChart();
      });
</script>
<?php require 'components/footer.php'; ?>

<?php require '../components/notification.php'; ?>

<script>

</script>