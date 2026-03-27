<?php
$stmt = $conn->prepare('SELECT * FROM school_config');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_OBJ);
$primary = $result->school_primary;
$secondary = $result->school_secondary;
?>

<!-- Mobile overlay backdrop -->
<div class="fixed inset-0 bg-black/50 z-[499] hidden md:hidden transition-opacity duration-300" id="sidebarOverlay">
</div>



<!-- sidebar -->
<aside class="dark:bg-gray-900 dark:text-white dark:border-0 w-72 bg-white h-screen border-r-[1.5px] border-gray-200/50 shrink-0 fixed top-0 left-0 z-[500] transition-transform duration-300 ease-in-out md:translate-x-0 -translate-x-full"
      id="sideBar">
      <div id="sideBarTogglerContainer" class="invisible lg:visible">
            <i class="fa-solid fa-chevron-left text-xs transition-transform duration-500" id="sideBarToggler"
                  data-tippy-content="Toggle Sidebar"></i>
      </div>



      <!-- Mobile close button -->
      <div class="flex items-center justify-between p-3 md:hidden">
            <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Menu</span>
            <button
                  class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 hover:text-gray-700 transition cursor-pointer"
                  id="sidebarCloseBtn">
                  <i class="bx-x text-xl"></i>
            </button>
      </div>

      <!-- school logo -->
      <div class="py-2 px-4 mb-5 hidden md:flex items-center gap-2">
            <img src='<?= $base . ltrim($result->school_logo ?? '', '/') ?>' class="size-10 object-contain" />
            <div class="flex flex-col justify-center items-start">
                  <h4 class="text-2xl font-black text-gray-700">
                        <?= strtoupper(explode(' ', $result->school_name)[0]) ?>
                  </h4>
                  <h5 class="text-sm dark:text-gray-400"><?= $result->school_tagline ?>
                  </h5>
            </div>
      </div>
      <div class="h-full overflow-y-auto px-4 custom-scrollbar pb-24">
            <!-- sideBar Items -->
            <div class="grid grid-rows-[auto_1fr_auto] my-2 md:mt-0 min-h-[calc(100vh-120px)]">
                  <ul class="sidebar">

                        <li class="li flex items-center gap-3 group" id="sideHome" data-page="index.php"
                              data-tippy-content="Go to Dashboard">
                              <i class="bxs-home-alt-2 text-2xl side-icon"></i>
                              <p class="">Home</p>
                        </li>
                        <li class="li flex items-center gap-3 group" id="sideEvents" data-page="events.php"
                              data-tippy-content="School Event Calendar">
                              <i class="bx-calendar-event text-2xl side-icon"></i>
                              <p class="">Events</p>
                        </li>
                        <?php if ($_SESSION['role'] === 'student'): ?>
                              <li class="li flex items-center gap-3 group relative" id="sideChat" data-page="chat.php"
                                    data-tippy-content="Messages &amp; Chat">
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
                        <li class="li flex items-center gap-3 group" id="sideTest" data-page="exam.php" data-tippy-content="Take an Exam">
                              <i class="bx-pencil text-2xl side-icon"></i>
                              <p>Take Exam</p>
                        </li>
                        <li class="li flex items-center gap-3 group" id="sideExamHistory" data-page="exam_history.php"
                              data-tippy-content="My Exam History & Results">
                              <i class="bx-history text-2xl side-icon"></i>
                              <p>Exam History</p>
                        </li>
                              <li class="li flex items-center gap-3 group" id="sideAssignmentsStudent"
                                    data-page="assignments.php" data-tippy-content="Pending Assignments">
                                    <i class="bxs-list-ul text-2xl side-icon"></i>
                                    <p>Assignments</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideLibrary" data-page="library.php"
                                    data-tippy-content="Digital Material Library">
                                    <i class="bx bx-book-content text-2xl side-icon"></i>
                                    <p>Library</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideStudy" data-page="study.php"
                                    data-tippy-content="AI Personalized Study Path">
                                    <i class="bx bx-sparkles text-2xl side-icon"></i>
                                    <p>Study Path</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideTimetableStudent" data-page="timetable.php"
                                    data-tippy-content="Class Timetable">
                                    <i class="bx bx-calendar text-2xl side-icon"></i>
                                    <p>Timetable</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideGamification" data-page="gamification.php"
                                    data-tippy-content="Points & Badges">
                                    <i class="bx bx-trophy text-2xl side-icon"></i>
                                    <p>Achievements</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideMyQr" data-page="my_qr.php"
                                    data-tippy-content="My QR Code">
                                    <i class="bx bx-qr text-2xl side-icon"></i>
                                    <p>My QR Code</p>
                              </li>
                              <?php
                                    $userClass = strtoupper(trim($_SESSION['class'] ?? $user->class ?? ''));
                                    $isSenior = (strpos($userClass, 'SS') !== false && strpos($userClass, 'JSS') === false) || (strpos($userClass, 'SSS') !== false);
                                    if ($isSenior): ?>
                                    <li class="li flex items-center gap-3 group" id="sideWaecPractice" onclick="window.loadPage('pages/waec_practice.php')"
                                          data-tippy-content="WAEC Practice Questions">
                                          <i class="bx bx-book-open text-2xl side-icon"></i>
                                          <p>WAEC Practice</p>
                                    </li>
                              <?php endif; ?>
                              <?php elseif ($_SESSION['role'] === 'staff'): ?>
                              <!-- Staff Specific Items -->
                              <li class="li flex items-center gap-3 group" id="sideStudents" data-page="students.php"
                                    data-tippy-content="My Students">
                                    <i class="bx-group text-2xl side-icon"></i>
                                    <p>My Students</p>
                              </li>
                              <?php if (isset($_SESSION['active_term']) && $_SESSION['active_term'] === 'First Term'): ?>
                                    <li class="li flex items-center gap-3 group" id="sidePromote" data-page="promote_students.php"
                                          data-tippy-content="Promote Students">
                                          <i class="fas fa-exchange-alt text-2xl side-icon"></i>
                                          <p>Promote Students</p>
                                    </li>
                              <?php endif; ?>
                              <li class="li flex items-center gap-3 group" id="sideStaffExams" data-page="exams.php"
                                    data-tippy-content="Set Exam Questions">
                                    <i class="bx-pencil text-2xl side-icon"></i>
                                    <p>Manage Exams</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideAttendance" data-page="attendance.php"
                                    data-tippy-content="Class Attendance">
                                    <i class="bx bx-calendar-check text-2xl side-icon"></i>
                                    <p>Attendance</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideCA" data-page="ca_records.php"
                                    data-tippy-content="Weekly & Monthly Assessments">
                                    <i class="bx bx-edit text-2xl side-icon"></i>
                                    <p>CA Records</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideStaffPasses" data-page="hall_passes.php"
                                    data-tippy-content="Issue Temporary Passes">
                                    <i class="bx bx-badge-check text-2xl side-icon"></i>
                                    <p>Pass Manager</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideResults" data-page="results.php"
                                    data-tippy-content="Exam Results & Grading">
                                    <i class="bx-bar-chart text-2xl side-icon"></i>
                                    <p>Results</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideAssignments" data-page="assignments.php"
                                    data-tippy-content="Class Assignments">
                                    <i class="bxs-list-ul text-2xl side-icon"></i>
                                    <p>Assignments</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideStaffLibrary" data-page="library.php"
                                    data-tippy-content="Manage Study Materials">
                                    <i class="bx bx-book-content text-2xl side-icon"></i>
                                    <p>Library</p>
                              </li>

                              <!-- Study Material Group -->
                              <li class="sidebar-dropdown-wrapper !p-0">
                                    <div class="flex items-center justify-between gap-3 group px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn"
                                          data-target="studyMenuStaff">
                                          <div class="flex items-center gap-3">
                                                <i
                                                      class="bx bx-book-bookmark text-2xl side-icon group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-600 to-purple-500 bg-clip-text"></i>
                                                <p>Study Hub</p>
                                          </div>
                                          <i
                                                class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow text-gray-400"></i>
                                    </div>
                                    <ul class="hidden space-y-1 mt-1 bg-gray-50/50 rounded-xl mx-2" id="studyMenuStaff">
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="staffStudyUpload" data-page="study_materials.php">
                                                <i class="fas fa-file-upload text-xl group-hover:text-blue-500"></i>
                                                <p class="text-xs font-semibold">Upload Content</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="staffStudyAssignment" data-page="study_materials.php">
                                                <i class="bxs-list-ul text-xl group-hover:text-amber-500"></i>
                                                <p class="text-xs font-semibold">Assignment</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="staffStudySyllabus" data-page="study_materials.php">
                                                <i class="bx bx-book-bookmark text-xl group-hover:text-teal-500"></i>
                                                <p class="text-xs font-semibold">Syllabus</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="staffStudyOther" data-page="study_materials.php">
                                                <i class="bx bx-caret-down text-xl group-hover:text-rose-500"></i>
                                                <p class="text-xs font-semibold">Other Download</p>
                                          </li>
                                    </ul>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideAiGen" data-page="ai_generator.php"
                                    data-tippy-content="AI Powered Tools">
                                    <i class="bx bx-robot text-2xl side-icon"></i>
                                    <p>AI Assistant</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideLessonPlans" data-page="lesson_plans.php"
                                    data-tippy-content="Weekly Lesson Planning">
                                    <i class="bx bx-book-bookmark text-2xl side-icon"></i>
                                    <p>Lesson Plans</p>
                              </li>
                              <li class="li flex items-center gap-3 group" id="sideTimetableStaff" data-page="timetable.php"
                                    data-tippy-content="My Timetable">
                                    <i class="bx bx-calendar text-2xl side-icon"></i>
                                    <p>My Timetable</p>
                              </li>
                        <?php endif; ?>
                                          </ul>

                  <!-- Sidebar Footer: Session & Logout -->
                  <div class="mt-auto px-2 mb-6 pt-4 border-t border-gray-100 space-y-3">
                        <!-- Session Status Card -->
                        <div
                              class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-4 border border-blue-100 shadow-sm transition-all duration-300">
                              <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                          <div
                                                class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm">
                                                <i class="bx bx-clock-5 text-lg"></i>
                                          </div>
                                          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Portal
                                                Timeline</span>
                                    </div>
                                    <button type="button"
                                          onclick="document.getElementById('scopeModal').classList.toggle('hidden')"
                                          class="text-blue-500 hover:text-blue-700 bg-white shadow-sm hover:bg-blue-200 p-1.5 rounded-lg transition"
                                          data-tippy-content="Change Session/Term">
                                          <i class="bx bx-slider-alt text-lg"></i>
                                    </button>
                              </div>

                              <?php if (isset($_SESSION['active_session']) && !empty($_SESSION['active_session'])): ?>
                              <div class="space-y-1">
                                          <h4 class="text-sm font-bold text-gray-800 tracking-tight">
                                                <?= $_SESSION['active_session'] ?>
                                          </h4>
                                          <div class="flex items-center gap-1.5">
                                                <span
                                                      class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                <p class="text-xs font-semibold text-blue-700"><?= $_SESSION['active_term'] ?>
                                                </p>
                                                </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="space-y-1">
                                                      <p class="text-xs font-medium text-gray-500 italic">No active session found.</p>
                                                </div>
                                                <?php endif; ?>
                                                </div>

                        <!-- Permanent Logout Button (Desktop Fix) -->
                        <a href="<?= $base ?>auth/logout.php"
                              class="flex items-center gap-3 px-5 py-3.5 rounded-2xl bg-red-50 text-red-600 hover:bg-red-500 hover:text-white transition-all duration-300 group shadow-sm border border-red-100/50">
                              <i
                                    class="bx bx-arrow-out-left-square-half text-xl transition-transform group-hover:-translate-x-1"></i>
                              <span class="text-xs font-bold uppercase tracking-widest">Logout Portal</span>
                        </a>
                  </div>
            </div>
      </div>

      <?php
      // Fetch available terms and sessions to populate scope modal
      $scope_sessions_stmt = $conn->query("SELECT DISTINCT session FROM sch_session ORDER BY session DESC");
      $scope_sessions = $scope_sessions_stmt->fetchAll(PDO::FETCH_COLUMN);
      // Read real term names from DB — must match exactly how records are stored
      $scope_terms_stmt = $conn->query("SELECT DISTINCT term FROM sch_session ORDER BY FIELD(term,'First Term','Second Term','Third Term')");
      $scope_terms = $scope_terms_stmt->fetchAll(PDO::FETCH_COLUMN);
      ?>
      <!-- Portal Scope Modal -->
      <div id="scopeModal"
            class="hidden fixed inset-0 z-[99999] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden fadeIn">
                  <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-blue-50/50">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                              <i class="bx bx-time text-blue-600 text-2xl"></i> Time Machine
                        </h3>
                        <button onclick="document.getElementById('scopeModal').classList.add('hidden')"
                              class="text-gray-400 hover:text-red-500 transition">
                              <i class="bx bx-x text-2xl"></i>
                        </button>
                  </div>
                  <div class="p-6 space-y-5">
                        <div class="space-y-1.5">
                              <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Academic
                                    Session</label>
                              <select id="scopeSession"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <?php foreach ($scope_sessions as $ss): ?>
                                          <option value="<?= $ss ?>" <?= (isset($_SESSION['active_session']) && $_SESSION['active_session'] === $ss) ? 'selected' : '' ?>><?= $ss ?></option>
                                    <?php endforeach; ?>
                              </select>
                              </div>
                              <div class="space-y-1.5">
                              <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Academic
                                    Term</label>
                              <select id="scopeTerm"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <?php foreach ($scope_terms as $tt): ?>
                                          <option value="<?= $tt ?>" <?= (isset($_SESSION['active_term']) && $_SESSION['active_term'] === $tt) ? 'selected' : '' ?>><?= $tt ?></option>
                                    <?php endforeach; ?>
                              </select>
                              </div>
                        <button id="applyScopeBtn"
                              class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md transition-all">
                              Access Timeline
                        </button>
                  </div>
            </div>
      </div>


      <style>
            .dropdown-arrow.rotate-90 {
                  transform: rotate(5deg) !important;
                  transition: transform 0.3s ease;
            }

            .sidebar ul ul {
                  display: none;
            }
      </style>
      <script>
            $(document).ready(function () {
                  // Scope Modal Logics
                  $('#applyScopeBtn').on('click', function () {
                        const s = $('#scopeSession').val();
                        const t = $('#scopeTerm').val();
                        const btn = $(this);

                        if (!s || !t) return;

                        btn.html('<i class="bx bx-loader-alt bx-spin"></i> Switching...').prop('disabled', true);

                        $.ajax({
                              url: BASE_URL + 'auth/change_scope.php',
                              type: 'POST',
                              data: { session: s, term: t },
                              dataType: 'json',
                              success: function (res) {
                                    if (res.success) {
                                          window.showToast(res.message, 'success');
                                          setTimeout(() => window.location.reload(), 1000);
                                    } else {
                                          window.showToast(res.message, 'error');
                                          btn.html('Access Timeline').prop('disabled', false);
                                    }
                              },
                              error: function () {
                                    window.showToast('Network error', 'error');
                                    btn.html('Access Timeline').prop('disabled', false);
                              }
                        });
                  });

                  $('.sidebar-dropdown-btn').on('click', function (e) {
                        e.stopPropagation();
                        const targetId = $(this).data('target');
                        const $submenu = $('#' + targetId);
                        const $arrow = $(this).find('.dropdown-arrow');

                        // Close other submenus
                        $('.sidebar-dropdown-btn').not(this).each(function () {
                              const oId = $(this).data('target');
                              if ($('#' + oId).is(':visible')) {
                                    $('#' + oId).slideUp(200);
                                    $(this).find('.dropdown-arrow').removeClass('rotate-90');
                              }
                        });

                        $submenu.slideToggle(300);
                        $arrow.toggleClass('rotate-90');
                  });

                  // Ensure active link parent starts open
                  $('.li.active-link').closest('ul').show();
                  $('.li.active-link').closest('ul').prev('.sidebar-dropdown-btn').find('.dropdown-arrow').addClass('rotate-90');
            });
      </script>
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