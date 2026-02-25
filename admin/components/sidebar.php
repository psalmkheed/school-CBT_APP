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
                  <h1 class="text-shadow-md text-2xl font-black" style="color: <?php echo $primary ?>">
                        <?= strtoupper(explode(' ', $result->school_name)[0]) ?>
                  </h1>
                  <h5 class="text-sm dark:!text-white" style="color: <?php echo $secondary ?>">
                        <?= $result->school_tagline ?>
                  </h5>
            </div>
      </div>

      <!-- sideBar Items -->
      <div class="grid grid-rows-[auto_1fr_auto] my-2 md:mt-0 h-[calc(100vh-140px)]">
            <ul class="sidebar">

                  <li class="li active flex items-center gap-3 group" id="homepage" data-tippy-content="Admin Overview">
                        <i
                              class="bxs-home-alt-2 text-2xl  group-hover:text-transparent group-hover:bg-linear-to-b from-green-700 via-green-500 to-orange-500 bg-clip-text"></i>
                        <p class="">Home</p>
                  </li>

                  <li class="li flex items-center gap-3 group" id="createAccount" data-tippy-content="Register New User">
                        <i
                              class="bx bx-user-plus bx-flip-horizontal text-2xl  group-hover:text-transparent group-hover:bg-linear-to-b from-green-700 via-green-500 to-orange-500 bg-clip-text"></i>
                        <p class="">Create Account</p>
                  </li>

                  <li class="li flex items-center gap-3 group" id="usersRecord" data-tippy-content="User and Staff Records">
                        <i
                              class="bx bx-database text-2xl  group-hover:text-transparent group-hover:bg-linear-to-b from-green-700 via-green-500 to-orange-500 bg-clip-text"></i>
                        <p class="">Users Record</p>
                  </li>

                  <li class="li flex items-center gap-3 group" id="classes" data-tippy-content="Manage Classes and Teachers">
                        <i
                              class="bx-door-open text-2xl  group-hover:text-transparent group-hover:bg-linear-to-b from-green-700 via-green-500 to-orange-500 bg-clip-text"></i>
                        <p class="">Classes</p>
                  </li>

                  <li class="li flex items-center gap-3 group" id="createExam" data-tippy-content="Manage Examinations">
                        <i
                              class="bx-book-open text-2xl  group-hover:text-transparent group-hover:bg-linear-to-b from-green-700 via-green-500 to-orange-500 bg-clip-text"></i>
                        <p class="">Examination</p>
                  </li>

                  <div class="border-b border-gray-200/50 my-3"></div>
                  <li class="li flex items-center gap-3 group" id="broadcast" data-tippy-content="Send Announcements">
                        <i class="bx-envelope text-2xl group-hover:text-transparent group-hover:bg-linear-to-b from-green-700 via-green-500 to-orange-500 bg-clip-text"></i>
                        <p>Message</p>
                  </li>
                  <li class="li flex items-center gap-3 group" id="createBlog" data-tippy-content="Manage Blog Posts">
                        <i class="bx-edit text-2xl group-hover:text-transparent group-hover:bg-linear-to-b from-green-700 via-green-500 to-orange-500 bg-clip-text"></i>
                        <p>Blog</p>
                  </li>
            </ul>

            <!-- Current Session Status Card -->
<div class="mt-auto px-2 mb-6 pt-4 border-t border-gray-100 self-end">
      <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-4 border border-green-100 shadow-sm transition-all duration-300">
            <div class="flex items-center gap-2 mb-2">
                  <div class="w-8 h-8 rounded-lg bg-green-600 flex items-center justify-center text-white shadow-sm">
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
                                          <span class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                          <p class="text-xs font-semibold text-green-700">
                                                <?= $_SESSION['active_term'] ?>
                                          </p>
                                    </div>
                              </div>
                        <?php else: ?>
                              <div class="space-y-2">
                                    <p class="text-[11px] font-medium text-red-500 italic leading-tight">No active academic session found
                                          in the system.</p>
                                    <button onclick="document.getElementById('createSession').click()"
                                          class="text-[10px] font-bold text-green-600 uppercase tracking-widest hover:text-green-700 transition flex items-center gap-1 cursor-pointer">
                                          Create New Session <i class="bx bx-right-arrow-alt"></i>
                                    </button>
                              </div>
                        <?php endif; ?>
                  </div>
            </div>
      </div>


</aside>