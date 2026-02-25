<?php
$stmt = $conn->prepare('SELECT * FROM school_config');

$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_OBJ);

$primary = $result->school_primary;
$secondary = $result->school_secondary;

?>

<!-- Mobile overlay backdrop -->
<div class="fixed inset-0 bg-black/50 z-[199] hidden md:hidden transition-opacity duration-300" id="sidebarOverlay"></div>

<!-- sidebar -->
<aside class="dark:bg-gray-900 dark:text-white dark:border-0 w-72 bg-white h-screen border-r-[1.5px] border-gray-200/50 px-4 shrink-0 fixed top-0 left-0 z-[200] overflow-y-auto transition-transform duration-300 ease-in-out md:translate-x-0 -translate-x-full"
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
                  <h4 class="text-shadow-md text-2xl font-black" style="color: <?php echo $primary ?>">
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
                  <li class="li flex items-center gap-3 group" id="sideChat" data-tippy-content="Messages & Chat">
                        <i class="bx-message-circle-detail text-2xl side-icon"></i>
                        <p class="">Chat</p>
                  </li>
                  <div class="border-b border-gray-200/50 my-3"></div>

                  <?php if ($_SESSION['role'] === 'student'): ?>
                        <!-- Student Specific Items -->
                        <li class="li flex items-center gap-3 group" id="sideTest" data-tippy-content="Practice and Exams">
                              <i class="bx-book-open text-2xl side-icon"></i>
                              <p>Test</p>
                        </li>
                        <li class="li flex items-center gap-3 group" id="sideStudy" data-tippy-content="Study Materials">
                              <i class="bx-book-library text-2xl side-icon"></i>
                              <p>Study</p>
                        </li>
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

                  <li class="li flex items-center gap-3 group" id="sideMore" data-tippy-content="More Options">
                        <i class="bx-dots-horizontal text-2xl side-icon"></i>
                        <p>More</p>
                  </li>
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