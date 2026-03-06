<?php
require '../../connections/db.php';
require '../auth/check.php'; // protect page

$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'student'");
$stmt->execute();
$count = $stmt->rowCount();
$result = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
<div class="w-full p-4 md:p-8">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
                  <button onclick="goHome()"
                        class="md:hidden w-12 h-12 shrink-0 rounded-2xl flex items-center justify-center text-gray-500 hover:text-sky-600 hover:bg-sky-50 transition-all cursor-pointer border border-gray-100"
                        title="Go back" data-tippy-content="Back to Dashboard">
                        <i class="bx bx-arrow-left-stroke text-4xl"></i>
                  </button>
                  <div class="flex items-center gap-4">
                        <div class="p-3.5 rounded-2xl bg-emerald-50 text-emerald-600 shadow-sm border border-emerald-100 shrink-0">
                              <i class="bx bx-group text-3xl"></i>
                        </div>
                        <div>
                              <h3 class="text-2xl font-black text-gray-800 tracking-tight">
                                    Users Record
                              </h3>
                              <p class="text-sm text-gray-400 font-medium">Manage all student and staff accounts</p>
                        </div>
                  </div>
            </div>
            <!-- Search & Filters -->
            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                <div class="relative w-full md:w-64 group">
                    <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                    <input type="text" id="userRecordSearch" 
                        class="w-full pl-11 pr-4 py-2.5 bg-gray-100/50 border border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:bg-white transition-all"
                        placeholder="Search records...">
                </div>

                <div class="flex p-1.5 bg-gray-100/50 rounded-2xl border border-gray-100 w-full md:w-auto overflow-x-auto gap-2">
                    <button
                            class="flex-1 md:flex-none px-6 py-2.5 rounded-xl transition-all duration-300 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group"
                            id="studentBtn">
                            <i class="bx-user text-lg text-sky-500 group-hover:scale-110 transition-transform"></i>
                            <span class="text-gray-600">Students</span>
                    </button>

                    <button
                            class="flex-1 md:flex-none px-6 py-2.5 rounded-xl transition-all duration-300 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group"
                            id="staffBtn">
                            <i class="bx-user text-lg text-orange-500 group-hover:scale-110 transition-transform"></i>
                            <span class="text-gray-600">Staff Members</span>
                    </button>
                </div>
            </div>

      </div>

      <div class="fadeIn w-full bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden" id="userRecordTable">
            <div class="overflow-x-auto" id="studentRecord">
                  <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50/80 border-b border-gray-100" id="userTableHead">
                              <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">User Profile</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">User ID</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Class / Group</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Account Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                              </tr>
                        </thead>

                        <tbody id="userTableBody">
                              <!-- Content loaded via AJAX -->
                        </tbody>
                  </table>
            </div>
      </div>
</div>

<script>
      $(document).ready(function () {
            // Function to load students
            function loadStudents(page = 1) {
                  $.ajax({
                        url: '/school_app/admin/auth/fetch_students.php',
                        method: 'POST',
                        data: { page: page },
                        success: function (data) {
                              $("#userTableBody").html(data);
                              registerStudentHandlers();
                              if (typeof initTooltips === 'function') initTooltips();
                        }
                  });
            }

            // Function to load staff
            function loadStaff(page = 1) {
                  const staffHead = `
                        <tr>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">User Profile</th>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">User ID</th>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Account Status</th>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                  `;
                  $("#userTableHead").html(staffHead);
                  
                  $.ajax({
                        url: '/school_app/admin/auth/fetch_staff.php',
                        method: 'POST',
                        data: { page: page },
                        success: function (data) {
                              $("#userTableBody").html(data);
                              registerStaffHandlers();
                              if (typeof initTooltips === 'function') initTooltips();
                        }
                  });
            }

            // Initial load
            $("#studentBtn").addClass("bg-white shadow-sm").find("span").addClass("text-emerald-600");
            loadStudents();
            window.initTableSearch('userRecordSearch', 'userTableBody');

            function initTooltips() {
                if (window.tippy) {
                   tippy('[data-tippy-content]', {
                        allowHTML: true,
                        animation: 'shift-away',
                        arrow: true,
                        theme: 'material'
                    });
                }
            }

            // Toggle Buttons
            $("#studentBtn").off("click").on("click", function() {
                  const studentHead = `
                        <tr>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">User Profile</th>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">User ID</th>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Class / Group</th>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Account Status</th>
                              <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                  `;
                  $(".flex.p-1.5.bg-gray-100\\/50 button").removeClass("bg-white shadow-sm").find("span").removeClass("text-emerald-600 text-amber-600");
                  $("#staffBtn").removeClass("bg-white shadow-sm").find("span").removeClass("text-amber-600");
                  $(this).addClass("bg-white shadow-sm").find("span").addClass("text-emerald-600");
                  $("#userTableHead").html(studentHead);
                  loadStudents(1);
            });

            $("#staffBtn").off("click").on("click", function() {
                  $(".flex.p-1.5.bg-gray-100\\/50 button").removeClass("bg-white shadow-sm").find("span").removeClass("text-emerald-600 text-amber-600");
                  $("#studentBtn").removeClass("bg-white shadow-sm").find("span").removeClass("text-emerald-600");
                  $(this).addClass("bg-white shadow-sm").find("span").addClass("text-amber-600");
                  loadStaff(1);
            });

            // Pagination Clicks (Delegated)
            $(document).on("click", ".student-page-btn", function() {
                  loadStudents($(this).data("page"));
            });

            $(document).on("click", ".staff-page-btn", function() {
                  loadStaff($(this).data("page"));
            });

            // Expose globally for fetch scripts
            window.initTooltips = initTooltips;
      });
</script>

<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>


<script>
      function registerStudentHandlers() {
            $(document).off('click', '#userTableBody .delete-btn').on('click', '#userTableBody .delete-btn', function (e) {
                  e.preventDefault();
                  const btn = $(this);
                  const userId = btn.data('id');

                  Swal.fire({
                        title: 'Are you sure?',
                        text: 'This student will be permanently deleted.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                  }).then((result) => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                              url: 'auth/delete.php',
                              type: 'POST',
                              dataType: 'json',
                              data: { del_id: userId },
                              beforeSend: function () {
                                    btn.prop('disabled', true).text('Deleting...');
                              },
                              success: function (res) {
                                    if (res.success) {
                                          btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                                          Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Student record deleted.', timer: 1500, showConfirmButton: false });
                                    } else {
                                          btn.prop('disabled', false).text('Delete');
                                          Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Delete failed' });
                                    }
                              }
                        });
                  });
            });

            $(document).off('click', '#userTableBody .edit-btn').on('click', '#userTableBody .edit-btn', function () {
                  const btn = $(this);
                  const userId = btn.data('id');
                  const row = btn.closest('tr');

                  $.ajax({
                        url: 'auth/get_user.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { id: userId },
                        success: function (res) {
                              if (!res.success) { Swal.fire('Error', 'Unable to fetch data', 'error'); return; }
                              Swal.fire({
                                    title: 'Edit Student',
                                    html: `
                                           <div class="flex flex-col gap-3">
                                                <input id="swal_first" class="swal2-input" style="margin: 10px auto;" placeholder="First Name" value="${res.data.first_name}">
                                                <input id="swal_last" class="swal2-input" style="margin: 10px auto;" placeholder="Last Name" value="${res.data.last_name}">
                                                <input id="swal_class" class="swal2-input" style="margin: 10px auto;" placeholder="Class" value="${res.data.class}">
                                                <div class="flex items-center justify-center gap-2 mt-2">
                                                    <span class="text-xs font-bold text-gray-400 uppercase">Status:</span>
                                                    <select id="swal_status" class="swal2-select" style="margin: 0; display: flex;">
                                                        <option value="1" ${res.data.status == 1 ? 'selected' : ''}>Active</option>
                                                        <option value="0" ${res.data.status == 0 ? 'selected' : ''}>Inactive</option>
                                                    </select>
                                                </div>
                                           </div>
                                     `,
                                     confirmButtonColor: '#10b981',
                                    showCancelButton: true,
                                    confirmButtonText: 'Save',
                                    preConfirm: () => {
                                          return { 
                                              id: userId, 
                                              first_name: $('#swal_first').val(), 
                                              last_name: $('#swal_last').val(), 
                                              class: $('#swal_class').val(),
                                              status: $('#swal_status').val()
                                          };
                                    }
                              }).then((result) => {
                                    if (!result.isConfirmed) return;
                                    $.ajax({
                                          url: 'auth/update_student.php',
                                          type: 'POST',
                                          dataType: 'json',
                                          data: result.value,
                                          success: function (update) {
                                                if (!update.success) { Swal.fire('Failed', update.message, 'error'); return; }
                                                row.find('.student-fullname').text(result.value.first_name + ' ' + result.value.last_name);
                                                row.find('.student-class').text(result.value.class);
                                                
                                                // Update status badge
                                                const statusHtml = result.value.status == 1 
                                                     ? `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-pulse"></span>
                                                            Active
                                                        </span>`
                                                     : `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-100">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                                            Inactive
                                                        </span>`;
                                                 row.find('.user-status-cell').html(statusHtml);

                                                Swal.fire({ icon: 'success', title: 'Updated', text: 'Student record updated', timer: 1300, showConfirmButton: false });
                                          }
                                    });
                              });
                        }
                  });
            });
      }

      function registerStaffHandlers() {
            $(document).off('click', '#userTableBody .delete-btn').on('click', '#userTableBody .delete-btn', function (e) {
                  e.preventDefault();
                  const btn = $(this);
                  const userId = btn.data('id');

                  Swal.fire({
                        title: 'Are you sure?',
                        text: 'This staff member will be permanently deleted.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                  }).then((result) => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                              url: 'auth/delete.php',
                              type: 'POST',
                              dataType: 'json',
                              data: { del_id: userId },
                              beforeSend: function () {
                                    btn.prop('disabled', true).text('Deleting...');
                              },
                              success: function (res) {
                                    if (res.success) {
                                          btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                                          Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Staff record deleted.', timer: 1500, showConfirmButton: false });
                                    } else {
                                          btn.prop('disabled', false).text('Delete');
                                          Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Delete failed' });
                                    }
                              }
                        });
                  });
            });

            $(document).off('click', '#userTableBody .edit-btn').on('click', '#userTableBody .edit-btn', function () {
                  const btn = $(this);
                  const userId = btn.data('id');
                  const row = btn.closest('tr');

                  $.ajax({
                        url: 'auth/get_staff.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { id: userId },
                        success: function (res) {
                              if (!res.success) { Swal.fire('Error', 'Unable to fetch data', 'error'); return; }
                              Swal.fire({
                                    title: 'Edit Staff',
                                    html: `
                                           <div class="flex flex-col gap-3">
                                                <input id="swal_first" class="swal2-input" style="margin: 10px auto;" placeholder="First Name" value="${res.data.first_name}">
                                                <input id="swal_last" class="swal2-input" style="margin: 10px auto;" placeholder="Last Name" value="${res.data.last_name}">
                                                <div class="flex items-center justify-center gap-2 mt-2">
                                                    <span class="text-xs font-bold text-gray-400 uppercase">Status:</span>
                                                    <select id="swal_status" class="swal2-select" style="margin: 0; display: flex;">
                                                        <option value="1" ${res.data.status == 1 ? 'selected' : ''}>Active</option>
                                                        <option value="0" ${res.data.status == 0 ? 'selected' : ''}>Inactive</option>
                                                    </select>
                                                </div>
                                           </div>
                                     `,
                                    confirmButtonColor: '#FF6900',
                                    showCancelButton: true,
                                    confirmButtonText: 'Save',
                                    preConfirm: () => {
                                          return { 
                                              id: userId, 
                                              first_name: $('#swal_first').val(), 
                                              last_name: $('#swal_last').val(),
                                              status: $('#swal_status').val()
                                          };
                                    }
                              }).then((result) => {
                                    if (!result.isConfirmed) return;
                                    $.ajax({
                                          url: 'auth/update_staff.php',
                                          type: 'POST',
                                          dataType: 'json',
                                          data: result.value,
                                          success: function (update) {
                                                if (!update.success) { Swal.fire('Failed', update.message, 'error'); return; }
                                                row.find('.staff-fullname').text(result.value.first_name + ' ' + result.value.last_name);
                                                
                                                // Update status badge
                                                const statusHtml = result.value.status == 1 
                                                     ? `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-pulse"></span>
                                                            Active
                                                        </span>`
                                                     : `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-100">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                                            Inactive
                                                        </span>`;
                                                 row.find('.staff-status-cell').html(statusHtml);

                                                Swal.fire({ icon: 'success', title: 'Updated', text: 'Staff record updated', timer: 1300, showConfirmButton: false });
                                          }
                                    });
                              });
                        }
                  });
            });
      }
</script>
