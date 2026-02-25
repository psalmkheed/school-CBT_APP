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
    WHERE recipient = :recipient
    ORDER BY created_at DESC
");

$stmt->execute([
      ':recipient' => $_SESSION['username']
]);

$results = $stmt->fetchAll(PDO::FETCH_OBJ);

// statement for counting numbers of students in the users table
$count_student = $conn->prepare("SELECT * FROM users WHERE role = 'student' ");
$count_student->execute();
$count_student_result = $count_student->rowCount();


// statement for counting numbers of staff in the users table
$count_staff = $conn->prepare("SELECT * FROM users WHERE role = 'staff' ");
$count_staff->execute();
$count_staff_result = $count_staff->rowCount();


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

<?php require 'components/header.php'; ?>
<?php require 'components/sidebar.php'; ?>

<!-- right nav -->
<main class="ml-0 md:ml-72">
      <!-- top Nav -->
      <?php require '../components/navbar.php'; ?>

      <!-- Main Content -->
      <div class="flex w-full" id="mainContent">
            <div class="fadeIn w-full md:p-8 p-4">
                  <!-- Welcome Banner -->
                  <div
                        class="relative overflow-hidden bg-gradient-to-br from-green-600 via-green-500 to-emerald-400 rounded-2xl p-6 md:p-8 mb-6 shadow-lg">
                        <!-- Decorative circles -->
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full"></div>
                        <div class="absolute top-1/2 right-1/4 w-20 h-20 bg-white/5 rounded-full"></div>

                        <div class="relative z-10 md:flex justify-between items-center">
                              <div>
                                    <p class="text-green-100 text-sm font-medium mb-1">
                                          <?= date('l, F j, Y') ?>
                                    </p>
                                    <h3 class="text-2xl md:text-3xl font-bold text-white mb-1">
                                          Welcome back, <?= ucfirst($user->first_name) ?> 👋
                                    </h3>
                                    <p class="text-green-100 text-sm">
                                          Here's what's happening at your school today.
                                    </p>

                                    <div class="mt-3 flex items-center gap-2">
                                          <?php if (isset($_SESSION['active_session']) && !empty($_SESSION['active_session'])): ?>
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md border border-white/20 text-white text-xs font-semibold shadow-sm">
                                                      <i class="bx-calendar text-sm"></i>
                                                      <?= $_SESSION['active_session'] ?>
                                                </div>
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-500/30 backdrop-blur-md border border-green-400/30 text-white text-xs font-semibold shadow-sm">
                                                      <i class="bx-clock-5 text-sm"></i>
                                                      <?= $_SESSION['active_term'] ?>
                                                </div>
                                          <?php else: ?>
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-500 backdrop-blur-md border border-red-400/20 text-red-100 text-xs font-semibold animate-pulse">
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
                                    <h3 class="text-3xl md:text-4xl font-extrabold text-gray-800 leading-tight">
                                          <?= $count_student_result ?: 0 ?>
                                    </h3>
                              </div>
                        </div>

                        <!-- Staff Card -->
                        <div
                              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg shadow-green-200 flex-shrink-0">
                                    <i class="bx-briefcase text-2xl text-white"></i>
                              </div>
                              <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Staff
                                    </p>
                                    <h3 class="text-3xl md:text-4xl font-extrabold text-gray-800 leading-tight">
                                          <?= $count_staff_result ?: 0 ?>
                                    </h3>
                              </div>
                        </div>

                        <!-- Quick Action Card -->
                        <div
                              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 group hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                              <div
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-200 flex-shrink-0">
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

                  <!-- Quick Actions Row -->
                  <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Quick Actions</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                              <button onclick="$('#createAccount').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div
                                          class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition">
                                          <i class="bx-user-plus text-lg text-blue-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Add User</span>
                              </button>
                              <button onclick="$('#createExam').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div
                                          class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center group-hover:bg-green-100 transition">
                                          <i class="bx-book-add text-lg text-green-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">New Exam</span>
                              </button>
                              <button onclick="$('#broadcast').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div
                                          class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition">
                                          <i class="bx-envelope text-lg text-amber-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">Message</span>
                              </button>
                              <button onclick="$('#createBlog').click()"
                                    class="bg-white border border-gray-100 rounded-xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer group">
                                    <div
                                          class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition">
                                          <i class="bx-edit text-lg text-purple-600"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600">New Blog</span>
                              </button>
                        </div>
                  </div>

                  <!-- Recent Messages Section -->
                  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                              <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                          <i class="bx-bell text-blue-600"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-800">Recent Messages</h4>
                              </div>
                              <span class="text-xs text-gray-400"><?= count($results) ?> messages</span>
                        </div>
                        <?php if (count($results) > 0): ?>
                              <div class="space-y-3 max-h-64 overflow-y-auto">
                                    <?php foreach (array_slice($results, 0, 5) as $msg): ?>
                                          <div
                                                class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                                                <div
                                                      class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                      <i class="bx-envelope text-green-600 text-sm"></i>
                                                </div>
                                                <div class="min-w-0">
                                                      <p class="text-sm font-semibold text-gray-700 truncate">
                                                            <?= htmlspecialchars($msg->subject ?? 'No Subject') ?>
                                                      </p>
                                                      <p class="text-xs text-gray-400 mt-0.5">
                                                            <?= htmlspecialchars($msg->username ?? '') ?>
                                                            <?php if (!empty($msg->created_at)): ?>
                                                                  • <?= date('M j', strtotime($msg->created_at)) ?><?php endif ?>
                                                      </p>
                                                </div>
                                          </div>
                                    <?php endforeach ?>
                              </div>
                        <?php else: ?>
                              <div class="text-center py-8">
                                    <div
                                          class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                          <i class="bx-envelope text-gray-400 text-xl"></i>
                                    </div>
                                    <p class="text-sm text-gray-400">No messages yet</p>
                              </div>
                        <?php endif ?>
                  </div>

            </div>
      </div>

</main>
<!-- Create Session form modal  -->
<div class="fixed inset-0 bg-black/90 z-[99999] flex items-center justify-center hidden backdrop-blur-md" id="sessionModal">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden fadeIn max-h-[90vh] flex flex-col">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 shrink-0">
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                              <i class="bx-calendar text-green-600"></i>
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
                        <label for="session" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">School Session Year</label>
                        <select id="session" name="session" required
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white shadow-sm">
                              <option value="" disabled selected>Select Academic Session</option>
                              <?php 
                                    $currentYear = date('Y') - 1;
                                    for($i = 0; $i < 5; $i++) {
                                          $year = ($currentYear + $i) . "/" . ($currentYear + $i + 1);
                                          echo "<option value='$year'>$year</option>";
                                    }
                              ?>
                        </select>
                  </div>

                  <!-- Dynamic Terms Container -->
                  <div id="terms-container" class="flex flex-col gap-6 font-primary">
                        <!-- Term Block 1 (Always Visible) -->
                        <div class="term-block relative bg-white p-5 rounded-2xl border-2 border-gray-100 transition-all duration-300 hover:border-green-100 group">
                              <div class="flex items-center gap-2 mb-4">
                                    <span class="w-6 h-6 rounded-full bg-green-600 text-white text-[10px] flex items-center justify-center font-bold">1</span>
                                    <h4 class="text-sm font-bold text-gray-700">Initial Academic Term</h4>
                              </div>
                              
                              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                          <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Term Name</label>
                                          <select name="terms[0][term]" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition bg-white">
                                                <option value="First Term">First Term</option>
                                                <option value="Second Term">Second Term</option>
                                                <option value="Third Term">Third Term</option>
                                          </select>
                                    </div>
                                    <div>
                                          <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Starts On</label>
                                          <input type="date" name="terms[0][start_date]" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
                                    </div>
                                    <div>
                                          <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Ends On</label>
                                          <input type="date" name="terms[0][end_date]" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
                                    </div>
                              </div>
                        </div>
                  </div>

                  <!-- Actions Section -->
                  <div class="flex flex-col gap-3 py-2 shrink-0">
                        <button type="button" id="add-term-btn" 
                              class="w-full py-3 border-2 border-dashed border-gray-200 rounded-xl text-gray-400 hover:text-green-600 hover:border-green-400 hover:bg-green-50/50 transition-all duration-300 text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 group">
                              <i class="bx bx-plus-circle text-lg group-hover:scale-110 transition-transform"></i>
                              Add Another Term to this Session
                        </button>

                        <button type="submit"
                              class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-sm shadow-lg shadow-green-200 hover:bg-green-700 hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2"
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
      <div class="relative rounded-2xl bg-white p-6 shadow-md border-gray-200/80 w-[450px] max-w-[95%] backdrop-blur-sm fade-in-bottom" id="greetingMsgContainer">
            <!-- close button -->
             <button type="button" class="bg-red-500 hover:bg-red-600 transition cursor-pointer flex items-center justify-center rounded-full p-1.5 hover:shadow-md top-4 right-4 absolute" onclick="document.getElementById('greetingsModal').classList.add('hidden')">
                        <i class="bx-x text-2xl text-white"></i>
                  </button>
            <!-- msg box -->
            <div class="flex flex-col gap-4">
                  <div class="flex flex-col items-center gap-2">
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-2">
                              <i class="bx-shield-quarter text-4xl text-green-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 text-center">Admin Dashboard 🔐</h3>
                        <p class="text-sm font-semibold text-gray-400 uppercase tracking-widest"><?= strtoupper(explode(' ', $result->school_name ?? 'SCHOOL')[0])?> Management System</p>
                  </div>
                  
                  <p class="text-lg text-gray-500 text-center leading-relaxed">
                        Welcome, <span class="text-green-600 font-bold"><?= ucfirst($_SESSION['first_name']) ?></span>. <br>
                        The portal is ready. What would you like to manage today?
                  </p>

                  <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100 flex flex-col items-center gap-1">
                              <i class="bx-user-plus text-blue-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Add Users</span>
                        </div>
                        <div class="bg-green-50/50 p-3 rounded-xl border border-green-100 flex flex-col items-center gap-1">
                              <i class="bx-calendar text-green-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Sessions</span>
                        </div>
                        <div class="bg-orange-50/50 p-3 rounded-xl border border-orange-100 flex flex-col items-center gap-1">
                              <i class="bx-envelope text-orange-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Broadcast</span>
                        </div>
                        <div class="bg-purple-50/50 p-3 rounded-xl border border-purple-100 flex flex-col items-center gap-1">
                              <i class="bx-book-add text-purple-600 text-xl"></i>
                              <span class="text-xs font-bold text-gray-700">Exams</span>
                        </div>
                  </div>

                  <button type="button" 
                        class="w-full bg-green-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-green-700 hover:shadow-green-200 transition-all cursor-pointer"
                        onclick="enterAppMode(); document.getElementById('greetingsModal').classList.add('hidden')">
                        Proceed to Dashboard
                  </button>
            </div>
      </div>
</div>
<?php unset($_SESSION['show_welcome']); ?>
<?php endif; ?>
<?php require 'components/footer.php'; ?>

<?php require '../components/notification.php'; ?>

<script>

</script>