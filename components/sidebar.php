<?php
$stmt = $conn->prepare('SELECT * FROM school_config');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$primary = $result->school_primary;
$secondary = $result->school_secondary;
?>

<!-- Mobile overlay backdrop -->
<div class="fixed inset-0 bg-black/50 z-[499] hidden md:hidden transition-opacity duration-300" id="sidebarOverlay"></div>

<!-- sidebar -->
<aside class="dark:bg-gray-900 dark:text-white dark:border-0 w-72 bg-white h-screen border-r-[1.5px] border-gray-200/50 px-4 shrink-0 fixed top-0 left-0 z-[500] overflow-y-auto transition-transform duration-300 ease-in-out md:translate-x-0 -translate-x-full"
      id="sideBar">

      <!-- Mobile close button -->
      <div class="flex items-center justify-between p-3 md:hidden">
            <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Menu</span>
            <button class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 hover:text-gray-700 transition cursor-pointer" id="sidebarCloseBtn">
                  <i class="bx-x text-xl"></i>
            </button>
      </div>

      <!-- school logo -->
      <div class="p-2 mb-4 hidden md:flex items-center gap-2">
            <img src='<?= $base ?>uploads/school_logo/<?= $result->school_logo ?>' class="size-10" />
            <div class="flex flex-col justify-center items-start">
                  <h4 class="text-2xl font-black text-gray-700">
                        <?= strtoupper(explode(' ', $result->school_name)[0]) ?>
                  </h4>
                  <h5 class="text-sm dark:text-gray-400"><?= $result->school_tagline ?>
                  </h5>
            </div>
      </div>
      <!-- sideBar Items -->
      <div class="grid grid-rows-[auto_1fr_auto] my-2 md:mt-0 h-[calc(100vh-140px)]">
            <ul class="sidebar">

                  <li class="li flex items-center gap-3 group" id="sideHome" data-tippy-content="Go to Dashboard">
                        <i class="bxs-home-alt-2 text-2xl side-icon"></i>
                        <p class="">Home</p>
                  </li>
                  <?php if ($_SESSION['role'] === 'student'): ?>
                  <li class="li flex items-center gap-3 group relative" id="sideChat" data-tippy-content="Messages &amp; Chat">
                        <i class="bx-message-circle-detail text-2xl side-icon"></i>
                        <p class="">Chat</p>
                        <span id="chatNotifDot" class="hidden absolute top-1 left-5 flex size-2.5">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                              <span class="relative inline-flex rounded-full size-2.5 bg-red-500"></span>
                        </span>
                  </li>
                  <?php endif ?>
                  <div class="border-b border-gray-200/50 my-3"></div>

                  <?php if ($_SESSION['role'] === 'student'): ?>
                        <!-- Student Specific Items -->
                        <li class="li flex items-center gap-3 group" id="sideTest" data-tippy-content="Take an Exam">
                              <i class="bx-pencil text-2xl side-icon"></i>
                              <p>Take Exam</p>
                        </li>
                        <li class="li flex items-center gap-3 group" id="sideExamHistory" data-tippy-content="My Exam History & Results">
                              <i class="bx-history text-2xl side-icon"></i>
                              <p>Exam History</p>
                        </li>
                        <?php
                        $userClass = strtoupper(trim($_SESSION['class'] ?? $user->class ?? ''));
                        $isSenior = (strpos($userClass, 'SS') !== false && strpos($userClass, 'JSS') === false) || (strpos($userClass, 'SSS') !== false);
                        if ($isSenior): ?>
                              <li class="li flex items-center gap-3 group" id="sideWaecPractice"
                                    onclick="window.loadPage('/school_app/student/pages/waec_practice.php')"
                                    data-tippy-content="WAEC Practice Questions">
                                    <i class="bx bx-book-open text-2xl side-icon"></i>
                                    <p>WAEC Practice</p>
                              </li>
                        <?php endif; ?>
                  <?php elseif ($_SESSION['role'] === 'staff'): ?>
                        <!-- Staff Specific Items -->
                        <li class="li flex items-center gap-3 group" id="sideStudents" data-tippy-content="My Students">
                              <i class="bx-group text-2xl side-icon"></i>
                              <p>My Students</p>
                        </li>
                        <li class="li flex items-center gap-3 group" id="sideStaffExams" data-tippy-content="Set Exam Questions">
                              <i class="bx-pencil text-2xl side-icon"></i>
                              <p>Manage Exams</p>
                        </li>
                        <li class="li flex items-center gap-3 group" id="sideResults" data-tippy-content="Exam Results & Grading">
                              <i class="bx-bar-chart text-2xl side-icon"></i>
                              <p>Results</p>
                        </li>
                  <?php endif; ?>
            </ul>

            <!-- Current Session Status Card -->
            <div class="mt-auto px-2 mb-6 pt-4 border-t border-gray-100 self-end">
                  <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-4 border border-blue-100 shadow-sm transition-all duration-300">
                        <div class="flex items-center gap-2 mb-2">
                              <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm">
                                    <i class="bx bx-calendar-event text-lg"></i>
                              </div>
                              <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Academic Term</span>
                        </div>

                        <?php if (isset($_SESSION['active_session']) && !empty($_SESSION['active_session'])): ?>
                              <div class="space-y-1">
                                    <h4 class="text-sm font-bold text-gray-800"><?= $_SESSION['active_session'] ?></h4>
                                    <div class="flex items-center gap-1.5">
                                          <span class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                          <p class="text-xs font-semibold text-blue-700"><?= $_SESSION['active_term'] ?></p>
                                    </div>
                              </div>
                        <?php else: ?>
                              <div class="space-y-1">
                                    <p class="text-xs font-medium text-gray-500 italic">No active session found.</p>
                              </div>
                        <?php endif; ?>
                  </div>
            </div>
      </div>
</aside>

<?php if ($_SESSION['role'] === 'student'):
      // Initialize the last seen ID so the background poll doesn't alert old messages on refresh
      $latest_chat_stmt = $conn->prepare("SELECT MAX(id) FROM class_messages WHERE class = :class");
      $latest_chat_stmt->execute([':class' => $_SESSION['class'] ?? '']);
      $initialLastId = (int) $latest_chat_stmt->fetchColumn();
      ?>
      <script>
            window._chatLastSeenId = <?= $initialLastId ?>;
      </script>
<?php endif; ?>