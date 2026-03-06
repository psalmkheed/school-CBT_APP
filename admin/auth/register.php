<?php

require '../../connections/db.php';

$class_populate = $conn->prepare("SELECT class FROM class ORDER BY class ASC");
$class_populate->execute();

$rows = $class_populate->fetchAll(PDO::FETCH_OBJ);

?>

<div class="w-full flex justify-center flex-col p-4 md:p-6">
      <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="bx-user-plus text-green-600"></i>
                  </div>
                  <h3 class="text-lg font-bold text-gray-800">Create Account</h3>
            </div>
      </div>

      <hr class="border-b border-gray-100 my-4">


      <!-- registration form -->
      <form class="fadeIn lg:w-[60%] w-full" id="regForm">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col gap-5">

                  <!-- First & Last Name -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="first_name" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">First Name</label>
                              <div class="relative">
                                    <i class="bx-user text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="Enter first name" name="first_name" id="first_name"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="last_name" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Name</label>
                              <div class="relative">
                                    <i class="bx-user text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="Enter last name" name="last_name" id="last_name"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                  </div>

                  <!-- User ID & Class -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="user_id" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">User ID</label>
                              <div class="relative">
                                    <i class="bx-at text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="Enter user ID" name="user_id" id="user_id"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="class" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Class</label>
                              <select name="class" id="class"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option selected value=" ">Select Class</option>
                                    <?php foreach ($rows as $row): ?>
                                          <option value="<?= $row->class ?>"><?= $row->class ?></option>
                                    <?php endforeach ?>
                              </select>
                        </div>
                  </div>

                  <!-- Password & Confirm -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="password" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Password</label>
                              <div class="relative">
                                    <i class="bx-lock text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="password" placeholder="Enter password" name="password" id="password"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                                    <button type="button" class="toggle-pw absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="password">
                                          <i class="bx-eye-closed text-lg"></i>
                                    </button>
                              </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="confirm_password" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Confirm Password</label>
                              <div class="relative">
                                    <i class="bx-lock text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="password" placeholder="Confirm password" name="confirm_password" id="confirm_password"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                                    <button type="button" class="toggle-pw absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="confirm_password">
                                          <i class="bx-eye-closed text-lg"></i>
                                    </button>
                              </div>
                        </div>
                  </div>

                  <!-- User Role -->
                  <div class="flex flex-col gap-1.5">
                        <label for="user_role" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">User Role</label>
                        <select name="user_role" id="user_role"
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                              <option disabled selected value="">Select User Role</option>
                              <option value="admin">Admin</option>
                              <option value="staff">Staff</option>
                              <option value="student">Student</option>
                        </select>
                  </div>

                  <button type="submit" name="register" id="regBtn"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 transition-all duration-200 font-semibold text-sm cursor-pointer">
                        Create Account
                  </button>
            </div>
      </form>
</div>


<script>
  // Register AJAX
      $("#regForm").on("submit", function (e) {
            e.preventDefault();

            $.ajax({
                  url: "/school_app/admin/auth/register_auth.php",
                  method: "POST",
                  dataType: "json",
                  data: $(this).serialize(),
                  beforeSend: function () {
                        $("#regBtn").prop("disabled", true).html(`<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>
                        `);
                  },
                  success: function (data) {
                        if (data.status === "success") {
                              showAlert('success', data.message);
                              $("#regForm")[0].reset();
                        } else {
                              showAlert('error', data.message);
                        }
                  },
                  error: function () {
                        showAlert('error', 'Network error. Try again.');
                  },
                  complete: function () {
                        $("#regBtn").prop("disabled", false).text("Create Account");
                  }
            });
      });


      // Toggle password visibility
      document.querySelectorAll('.toggle-pw').forEach(btn => {
            btn.addEventListener('click', () => {
                  const input = document.getElementById(btn.dataset.target);
                  const icon  = btn.querySelector('i');
                  if (input.type === 'password') {
                        input.type = 'text';
                        icon.className = 'bx-eye text-lg';
                  } else {
                        input.type = 'password';
                        icon.className = 'bx-eye-closed text-lg';
                  }
            });
      });
</script>