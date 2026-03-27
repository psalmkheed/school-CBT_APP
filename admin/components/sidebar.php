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
            <img src='<?= $base . ltrim($result->school_logo ?? '', '/') ?>' class="size-10 object-contain bg-white rounded" />
            <div class="flex flex-col justify-center items-start">
                  <h1 class="text-2xl font-black text-gray-700">
                        <?= strtoupper(explode(' ', $result->school_name)[0]) ?>
                  </h1>
                  <h5 class="text-sm dark:!text-white" style="color: <?php echo $secondary ?>">
                        <?= $result->school_tagline ?>
                  </h5>
            </div>
      </div>
      <div class="h-full overflow-y-auto px-4 custom-scrollbar pb-24">
            <!-- sideBar Items -->
            <div class="grid grid-rows-[auto_1fr_auto] my-2 md:mt-0 min-h-[calc(100vh-120px)]">
                  <ul class="sidebar space-y-1">

                        <li class="li active flex items-center gap-3 group rounded-xl transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                              id="homepage" data-tippy-content="Admin Overview">
                              <i
                                    class="bx bx-home-alt-2 text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-600 to-violet-500 bg-clip-text"></i>
                              <p class="text-sm font-medium">Home</p>
                        </li>

                        <!-- Account Group -->
                        <li class="sidebar-dropdown-wrapper !p-0">
                              <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn"
                                    data-target="accountMenu">
                                    <div class="flex items-center gap-3">
                                          <i
                                                class="bx bx-user-circle text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-blue-600 to-sky-500 bg-clip-text"></i>
                                          <p class="text-sm">Account Management</p>
                                    </div>
                                    <i
                                          class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                              </div>
                              <ul class="hidden space-y-1 mt-1" id="accountMenu">
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="createAccount" data-tippy-content="Register New User">
                                          <i class="bx bx-user-plus text-2xl group-hover:text-blue-500"></i>
                                          <p class="text-sm">Create New Account</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="usersRecord" data-tippy-content="User and Staff Records">
                                          <i class="bx bx-user text-2xl group-hover:text-emerald-500"></i>
                                          <p class="text-sm">View Users Record</p>
                                    </li>
                              </ul>
                        </li>

                        <!-- Academy Group -->
                        <li class="sidebar-dropdown-wrapper !p-0">
                              <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn"
                                    data-target="academyMenu">
                                    <div class="flex items-center gap-3">
                                          <i
                                                class="bx bx-school text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-orange-600 to-indigo-500 bg-clip-text"></i>
                                          <p class="text-sm">Academy</p>
                                    </div>
                                    <i
                                          class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                              </div>
                              <ul class="hidden space-y-1 mt-1" id="academyMenu">
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="classes" data-tippy-content="Manage Classes and Teachers">
                                          <i class="bx-door-open text-2xl group-hover:text-orange-500"></i>
                                          <p class="text-sm">Classes</p>
                                    </li>
                                    <?php if (isset($_SESSION['active_term']) && $_SESSION['active_term'] === 'First Term'): ?>
                                                                                                      <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                                                                            id="promoteStudents" data-tippy-content="Batch Promote Students">
                                                                                                            <i class="fas fa-exchange-alt text-2xl group-hover:text-emerald-500"></i>
                                                                                                            <p class="text-sm">Promote Students</p>
                                                                                                      </li>
                                                                                                <?php endif; ?>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="createExam" data-tippy-content="Manage Examinations">
                                          <i class="bx-book-open text-2xl group-hover:text-purple-500"></i>
                                          <p class="text-sm">Examination</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="importData" data-tippy-content="Bulk Data Import">
                                          <i class="bxs-arrow-up-square text-2xl group-hover:text-sky-500"></i>
                                          <p class="text-sm">Bulk Import</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="reportCards" data-tippy-content="Manage Report Cards">
                                          <i class="bx-file text-2xl group-hover:text-amber-500"></i>
                                          <p class="text-sm">Report Cards</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="proctoring" data-tippy-content="AI Integrity Check">
                                          <i class="bx bx-shield-quarter text-2xl group-hover:text-red-500"></i>
                                          <p class="text-sm">Proctoring</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="attTrends" data-tippy-content="Attendance Analytics">
                                          <i class="bx bx-bar-chart-square text-2xl group-hover:text-cyan-500"></i>
                                          <p class="text-sm">Attendance Trends</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="manageLessonPlans" data-tippy-content="Review Lesson Plans">
                                          <i class="bx bx-book-bookmark text-2xl group-hover:text-indigo-500"></i>
                                          <p class="text-sm">Lesson Plans</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="adminLibrary" data-tippy-content="Digital Library Manager">
                                          <i class="bx bx-card-view-large text-2xl group-hover:text-amber-500"></i>
                                          <p class="text-sm">Digital Library</p>
                                    </li>
                              </ul>
                        </li>

                        <!-- Study Material Group -->
                        <li class="sidebar-dropdown-wrapper !p-0">
                              <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn"
                                    data-target="studyMenu">
                                    <div class="flex items-center gap-3">
                                          <i
                                                class="bx bx-book-content text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-600 to-purple-500 bg-clip-text"></i>
                                          <p class="text-sm">Study Material</p>
                                    </div>
                                    <i
                                          class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                              </div>
                              <ul class="hidden space-y-1 mt-1" id="studyMenu">
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="studyUpload" data-tippy-content="Upload Content">
                                          <i class="fas fa-file-upload text-2xl group-hover:text-blue-500"></i>
                                          <p class="text-sm">Upload Content</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="studyAssignment" data-tippy-content="Manage Assignments">
                                          <i class="bxs-list-ul text-2xl group-hover:text-amber-500"></i>
                                          <p class="text-sm">Assignment</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="studySyllabus" data-tippy-content="Manage Syllabus">
                                          <i class="bx bx-book-bookmark text-2xl group-hover:text-teal-500"></i>
                                          <p class="text-sm">Syllabus</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="studyOther" data-tippy-content="Other Downloads">
                                          <i class="bx bx-caret-down text-2xl group-hover:text-rose-500"></i>
                                          <p class="text-sm">Other Download</p>
                                    </li>
                              </ul>
                        </li>

                        <!-- Operations Group (Timetables, IDs, Attendance) -->
                        <li class="sidebar-dropdown-wrapper !p-0">
                              <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn"
                                    data-target="operationsMenu">
                                    <div class="flex items-center gap-3">
                                          <i
                                                class="bx bx-briefcase text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-teal-600 to-emerald-500 bg-clip-text"></i>
                                          <p class="text-sm">Operations</p>
                                    </div>
                                    <i
                                          class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                              </div>
                              <ul class="hidden space-y-1 mt-1" id="operationsMenu">
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="manageTimetable" data-tippy-content="Dynamic Timetable Builder">
                                          <i class="bx bx-table text-2xl group-hover:text-teal-500"></i>
                                          <p class="text-sm">Timetables</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="idCards" data-tippy-content="Generate QR ID Cards">
                                          <i class="bx bx-user-id-card text-2xl group-hover:text-blue-500"></i>
                                          <p class="text-sm">ID Cards Generator</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="qrScanner" data-tippy-content="Fast Attendance Scanner">
                                          <i class="bx bx-qr-scan text-2xl group-hover:text-purple-500"></i>
                                          <p class="text-sm">QR Attendance</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="hostelManager" data-tippy-content="Manage Dorms and Beds">
                                          <i class="bx bx-building-house text-2xl group-hover:text-amber-500"></i>
                                          <p class="text-sm">Hostel Management</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="passManager" data-tippy-content="Digital Campus Passes">
                                          <i class="bx bx-badge-check text-2xl group-hover:text-rose-500"></i>
                                          <p class="text-sm">Pass Manager</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="behaviorLog" data-tippy-content="Student Disciplinary Log">
                                          <i class="bx bx-street-view text-2xl group-hover:text-fuchsia-500"></i>
                                          <p class="text-sm">Behavior Log</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="transport" data-tippy-content="Manage School Transport">
                                          <i class="bx bx-bus text-2xl group-hover:text-blue-500"></i>
                                          <p class="text-sm">Transport</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="inventory" data-tippy-content="Manage School Assets">
                                          <i class="bx bx-box text-2xl group-hover:text-orange-500"></i>
                                          <p class="text-sm">Inventory</p>
                                    </li>
                                    <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                          id="physicalLibrary" data-tippy-content="Book Issues & Returns">
                                          <i class="bx-book-library text-2xl group-hover:text-amber-600"></i>
                                          <p class="text-sm">Physical Library</p>
                                    </li>
                              </ul>
                        </li>
                        <?php if ($_SESSION['role'] == 'super'): ?>
                              <!-- Human Resource Group -->
                              <li class="sidebar-dropdown-wrapper !p-0">
                                    <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn"
                                          data-target="hrMenu">
                                          <div class="flex items-center gap-3">
                                                <i
                                                      class="bx bx-group text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-600 to-blue-500 bg-clip-text"></i>
                                                <p class="text-sm">Human Resource</p>
                                          </div>
                                          <i
                                                class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                                    </div>
                                    <ul class="hidden space-y-1 mt-1" id="hrMenu">
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="hrStaffDirectory" data-tippy-content="Staff Profiles">
                                                <i class="bx bx-user-id-card text-2xl group-hover:text-blue-500"></i>
                                                <p class="text-sm">Staff Directory</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="hrAttendance" data-tippy-content="Daily Attendance">
                                                <i class="bx bx-calendar-check text-2xl group-hover:text-emerald-500"></i>
                                                <p class="text-sm">Attendance</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="hrAttendanceReport" data-tippy-content="View Attendance Reports">
                                                <i class="bxs-bar-chart text-2xl group-hover:text-indigo-500"></i>
                                                <p class="text-sm">Attendance Report</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="hrPayroll" data-tippy-content="Manage Salary Processing">
                                                <i class="bx bx-wallet-alt text-2xl group-hover:text-amber-500"></i>
                                                <p class="text-sm">Payroll</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="hrPayrollReport" data-tippy-content="Salary Overviews">
                                                <i class="bx bx-file text-2xl group-hover:text-rose-500"></i>
                                                <p class="text-sm">Payroll Report</p>
                                          </li>
                                    </ul>
                              </li>
                        <?php endif ?>
                        <!-- Finance Group (Super Role Only) -->
                        <?php if ($_SESSION['role'] == 'super'): ?>
                        <li class="sidebar-dropdown-wrapper !p-0">
                              <div class="flex items-center justify-between gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 cursor-pointer sidebar-dropdown-btn"
                                    data-target="financeMenu">
                                    <div class="flex items-center gap-3">
                                          <i
                                                class="bx bx-dollar-circle text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-green-600 to-emerald-500 bg-clip-text"></i>
                                          <p class="text-sm">Finance</p>
                                    </div>
                                          <i
                                                class="bx bx-chevron-right text-lg transition-transform duration-300 dropdown-arrow"></i>
                                    </div>
                                    <ul class="hidden space-y-1 mt-1" id="financeMenu">
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="manageFees" data-tippy-content="Manage School Fees">
                                                <i class="bx-credit-card text-2xl group-hover:text-green-500"></i>
                                                <p class="text-sm">Manage Fees</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="expenseTracker" data-tippy-content="Track Expenses">
                                                <i class="bx-currency-notes text-2xl group-hover:text-red-500"></i>
                                                <p class="text-sm">Expenses</p>
                                          </li>
                                          <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                                                id="financialReports" data-tippy-content="Financial Reports">
                                                <i class="bx-file text-2xl group-hover:text-blue-500"></i>
                                                <p class="text-sm">Reports</p>
                                          </li>
                                    </ul>
                              </li>
                        <?php endif; ?>

                        <div class="border-b border-gray-200/50 my-3"></div>
                        <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                              id="broadcast" data-tippy-content="Send Messages">
                              <i
                                    class="bx-envelope text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-amber-600 to-yellow-500 bg-clip-text"></i>
                              <p class="text-sm">Message</p>
                        </li>
                        <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                              id="events" data-tippy-content="School Events">
                              <i
                                    class="bx-calendar-event text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-500 to-purple-500 bg-clip-text"></i>
                              <p class="text-sm">Events</p>
                        </li>
                        <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                              id="announcements" data-tippy-content="System Announcements (Popups)">
                              <i
                                    class="bx-bell text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-red-500 to-pink-500 bg-clip-text"></i>
                              <p class="text-sm">Announcements</p>
                        </li>
                        <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                              id="createBlog" data-tippy-content="Manage Blog Posts">
                              <i
                                    class="bx-edit text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-pink-600 to-rose-500 bg-clip-text"></i>
                              <p class="text-sm">Blog</p>
                        </li>
                        <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                              id="viewLogs" data-tippy-content="System Activity Logs">
                              <i
                                    class="bx bx-checklist text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-indigo-600 to-violet-500 bg-clip-text"></i>
                              <p class="text-sm">Activity Logs</p>
                        </li>
                        <li class="li flex items-center gap-3 group rounded-xl px-4 py-3 transition-all duration-200 hover:bg-emerald-50/30 [&.active]:bg-emerald-50 [&.active]:text-emerald-700 [&.active]:font-bold [&.active]:shadow-sm"
                              id="schoolSettings" data-tippy-content="Configure School Details">
                              <i
                                    class="bx-cog text-2xl group-hover:text-transparent group-hover:bg-gradient-to-br from-slate-600 to-gray-500 bg-clip-text"></i>
                              <p class="text-sm">School Settings</p>
                        </li>
                  </ul>

                  <!-- Current Session Status Card -->
                  <div class="mt-auto px-2 mb-6 pt-4 border-t border-gray-100 self-end">
                        <div
                              class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-2xl p-4 border border-emerald-100 shadow-sm transition-all duration-300">
                              <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                          <div
                                                class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-sm">
                                                <i class="bx bx-clock-5 text-lg"></i>
                                          </div>
                                          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Portal
                                                Timeline</span>
                                    </div>
                                    <button type="button"
                                          onclick="document.getElementById('scopeModal').classList.toggle('hidden')"
                                          class="text-emerald-500 hover:text-emerald-700 bg-white shadow-sm hover:bg-emerald-200 p-1.5 rounded-lg transition"
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
                                                      class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <p class="text-[11px] font-bold text-emerald-700">
                                                      <?= $_SESSION['active_term'] ?>
                                                </p>
                                                </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="space-y-2">
                                          <p class="text-[11px] font-medium text-red-500 italic leading-tight">No active
                                                academic session found in the system.</p>
                                          <button onclick="document.getElementById('createSession').click()"
                                                class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest hover:text-emerald-700 transition flex items-center gap-1 cursor-pointer">
                                                Create New Session <i class="bx bx-right-arrow-alt"></i>
                                          </button>
                                    </div>
                              <?php endif; ?>
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
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-emerald-50/50">
                              <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <i class="bx bx-time text-emerald-600 text-2xl"></i> Time Machine
                              </h3>
                              <button onclick="document.getElementById('scopeModal').classList.add('hidden')"
                                    class="text-gray-400 hover:text-red-500 transition cursor-pointer">
                                    <i class="bx bx-x text-2xl"></i>
                              </button>
                        </div>
                        <div class="p-6 space-y-5">
                              <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Academic
                                          Session</label>
                                    <select id="scopeSession"
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                          <?php foreach ($scope_sessions as $ss): ?>
                                                <option value="<?= $ss ?>" <?= (isset($_SESSION['active_session']) && $_SESSION['active_session'] === $ss) ? 'selected' : '' ?>><?= $ss ?>
                                                </option>
                                          <?php endforeach; ?>
                                    </select>
                                    </div>
                                    <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Academic
                                          Term</label>
                                    <select id="scopeTerm"
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                          <?php foreach ($scope_terms as $tt): ?>
                                                <option value="<?= $tt ?>" <?= (isset($_SESSION['active_term']) && $_SESSION['active_term'] === $tt) ? 'selected' : '' ?>><?= $tt ?></option>
                                          <?php endforeach; ?>
                                    </select>
                                    </div>
                              <button id="applyScopeBtn"
                                    class="cursor-pointer w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-md transition-all">
                                    Access Timeline
                              </button>
                        </div>
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

                        // Close other submenus (Optional: comment out if you want multiple open)
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
                  $('.sidebar .li.active').closest('ul').show();
                  $('.sidebar .li.active').closest('ul').prev('.sidebar-dropdown-btn').find('.dropdown-arrow').addClass('rotate-90');
            });
      </script>

</aside>