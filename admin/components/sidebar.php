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
                  <h1 class="text-2xl font-black text-gray-700">
                        <?= strtoupper(explode(' ', $result->school_name)[0]) ?>
                  </h1>
                  <h5 class="text-sm dark:!text-white" style="color: <?php echo $secondary ?>">
                        <?= $result->school_tagline ?>
                  </h5>
            </div>
      </div>

      <!-- sideBar Items -->
      <div class="grid grid-rows-[auto_1fr_auto] my-2 md:mt-0 h-[calc(100vh-140px)]">
            <ul class="sidebar space-y-1">

                  <li class="li active flex items-center gap-3 group rounded-xl transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="homepage" data-tippy-content="Admin Overview">
                        <i class="bx bx-home-alt-2 text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-600 to-violet-500 bg-clip-text"></i>
                        <p class="text-sm font-medium">Home</p>
                  </li>

                  <!-- Account Group -->
                  <li class="sidebar-dropdown-wrapper !p-0">
                        <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn" data-target="accountMenu">
                              <div class="flex items-center gap-3">
                                    <i class="bx bx-user-circle text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-blue-600 to-sky-500 bg-clip-text"></i>
                                    <p class="text-sm">Account</p>
                              </div>
                              <i class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                        </div>
                        <ul class="hidden space-y-1 mt-1" id="accountMenu">
                              <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="createAccount" data-tippy-content="Register New User">
                                    <i class="bx bx-user-plus text-2xl group-hover:text-blue-500"></i>
                                    <p class="text-sm">Create Account</p>
                              </li>
                              <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="usersRecord" data-tippy-content="User and Staff Records">
                                    <i class="bx bx-user-id-card text-2xl group-hover:text-emerald-500"></i>
                                    <p class="text-sm">Users Record</p>
                              </li>
                        </ul>
                  </li>

                  <!-- Academy Group -->
                  <li class="sidebar-dropdown-wrapper !p-0">
                        <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn" data-target="academyMenu">
                              <div class="flex items-center gap-3">
                                    <i class="bx bx-school text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-orange-600 to-indigo-500 bg-clip-text"></i>
                                    <p class="text-sm">Academy</p>
                              </div>
                              <i class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                        </div>
                        <ul class="hidden space-y-1 mt-1" id="academyMenu">
                              <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="classes" data-tippy-content="Manage Classes and Teachers">
                                    <i class="bx-door-open text-2xl group-hover:text-orange-500"></i>
                                    <p class="text-sm">Classes</p>
                              </li>
                              <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="createExam" data-tippy-content="Manage Examinations">
                                    <i class="bx-book-open text-2xl group-hover:text-purple-500"></i>
                                    <p class="text-sm">Examination</p>
                              </li>
                        </ul>
                  </li>

                  <div class="border-b border-gray-200/50 my-3"></div>
                  <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="broadcast" data-tippy-content="Send Announcements">
                        <i class="bx-envelope text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-amber-600 to-yellow-500 bg-clip-text"></i>
                        <p class="text-sm">Message</p>
                  </li>
                  <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="createBlog" data-tippy-content="Manage Blog Posts">
                        <i class="bx-edit text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-pink-600 to-rose-500 bg-clip-text"></i>
                        <p class="text-sm">Blog</p>
                  </li>
                  <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm" id="viewLogs" data-tippy-content="System Activity Logs">
                        <i class="bx bx-checklist text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-600 to-violet-500 bg-clip-text"></i>
                        <p class="text-sm">Activity Logs</p>
                  </li>
            </ul>

            <!-- Current Session Status Card -->
            <div class="mt-auto px-2 mb-6 pt-4 border-t border-gray-100 self-end">
                  <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-2xl p-4 border border-emerald-100 shadow-sm transition-all duration-300">
                        <div class="flex items-center gap-2 mb-2">
                              <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-sm">
                                    <i class="bx bx-calendar-event text-lg"></i>
                              </div>
                              <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Period</span>
                        </div>
                        
                        <?php if (isset($_SESSION['active_session']) && !empty($_SESSION['active_session'])): ?>
                                    <div class="space-y-1">
                                          <h4 class="text-sm font-bold text-gray-800">
                                                <?= $_SESSION['active_session'] ?>
                                    </h4>
                                    <div class="flex items-center gap-1.5">
                                                      <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                      <p class="text-xs font-semibold text-emerald-700">
                                                            <?= $_SESSION['active_term'] ?>
                                                      </p>
                                                      </div>
                                                      </div>
                                                      <?php else: ?>
                                                      <div class="space-y-2">
                                                            <p class="text-[11px] font-medium text-red-500 italic leading-tight">No active academic session found
                                                                  in the system.</p>
                                                            <button onclick="document.getElementById('createSession').click()"
                                                      class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest hover:text-emerald-700 transition flex items-center gap-1 cursor-pointer">
                                                      Create New Session <i class="bx bx-right-arrow-alt"></i>
                                                </button>
                                          </div>
                                    <?php endif; ?>
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
      $(document).ready(function() {
            $('.sidebar-dropdown-btn').on('click', function(e) {
                  e.stopPropagation();
                  const targetId = $(this).data('target');
                  const $submenu = $('#' + targetId);
                  const $arrow = $(this).find('.dropdown-arrow');

                  // Close other submenus (Optional: comment out if you want multiple open)
                  $('.sidebar-dropdown-btn').not(this).each(function() {
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
            $('.sidebar .li.active').closest('ul').show();
            $('.sidebar .li.active').closest('ul').prev('.sidebar-dropdown-btn').find('.dropdown-arrow').addClass('rotate-90');
      });
</script>

</aside>