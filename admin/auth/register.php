<?php

require '../../connections/db.php';

$class_populate = $conn->prepare("SELECT class FROM class ORDER BY class ASC");
$class_populate->execute();

$rows = $class_populate->fetchAll(PDO::FETCH_OBJ);

$states_query = $conn->query("SELECT state FROM state_of_origin ORDER BY state ASC");
$states = $states_query->fetchAll(PDO::FETCH_OBJ);

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
      <form class="fadeIn lg:w-[100%] w-full " id="regForm">
            <div class="bg-white rounded-2xl shadow-md border-t-4 border-green-500 p-6 flex flex-col gap-5">
                  
                  <!-- Top Section: User Role & Profile Photo -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- User Role -->
                        <div class="flex flex-col gap-1.5">
                              <label for="user_role" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">User Role</label>
                              <select name="user_role" id="user_role" required
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white">
                                    <option disabled selected value="">Select User Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">Staff</option>
                                    <option value="student">Student</option>
                              </select>
                        </div>
                        
                        <!-- Profile Photo (Always visible right under/next to Role) -->
                        <div id="profilePhotoContainer" class="flex flex-col gap-1.5 fadeIn">
                              <label for="profile_photo" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Profile Photo <span class="text-[10px] text-gray-400 lowercase">(Required for ID Card)</span>
                              </label>
                              <div class="relative w-full border-2 border-dashed border-gray-200 rounded-xl px-4 flex-1 min-h-[5rem] hover:border-green-400 hover:bg-green-50/30 transition-all flex flex-col items-center justify-center cursor-pointer overflow-hidden" id="photo_dropzone">
                                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    <div class="flex items-center gap-2 py-2" id="photo_icon_text">
                                          <i class="bx-cloud-upload text-2xl text-gray-300 group-hover:text-green-500" id="photo_icon"></i>
                                          <span class="text-sm font-semibold text-gray-600" id="photo_text">Click to upload</span>
                                    </div>
                                    <img id="photo_preview" src="" class="hidden absolute inset-0 w-full h-full object-cover z-0" alt="Preview">
                              </div>
                        </div>
                  </div>

                  <!-- First & Surname -->
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- surname -->
                        <div class="flex flex-col gap-1.5">
                              <label for="surname" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Surname</label>
                              <div class="relative">
                                    <i class="bx-user text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="Surname" name="surname" id="surname" required
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                        <!-- first name -->
                        <div class="flex flex-col gap-1.5">
                              <label for="first_name" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">First Name</label>
                              <div class="relative">
                                    <i class="bx-user text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="First name" name="first_name" id="first_name" required
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                        <!-- other name -->
                        <div class="flex flex-col gap-1.5">
                              <label for="other_name" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Other Name</label>
                              <div class="relative">
                                    <i class="bx-user text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="Other name (optional)" name="other_name" id="other_name"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                  </div>

                  <!-- Shared Fields: Admission & Passwords -->
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="admission_date" class="text-xs font-semibold text-gray-500 uppercase tracking-wide" id="dateLabel">Admission Date</label>
                              <div class="relative">
                                    <input type="date" name="admission_date" id="admission_date" required
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="user_id" class="text-xs font-semibold text-gray-500 uppercase tracking-wide" id="userIdLabel">Admission Number</label>
                              <div class="relative">
                                    <i class="bx-at text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="Enter Registration ID" name="user_id" id="user_id" required
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                              <label for="parent_phone" class="text-xs font-semibold text-gray-500 uppercase tracking-wide" id="phoneLabel">Phone Number</label>
                              <div class="relative">
                                    <i class="bx-phone text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="text" placeholder="080..." name="parent_phone" id="parent_phone" required
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                              </div>
                        </div>
                  </div>

                  <!-- Passwords -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                              <label for="password" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Password</label>
                              <div class="relative">
                                    <i class="bx-lock text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                    <input type="password" placeholder="Enter password" name="password" id="password" required
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
                                    <input type="password" placeholder="Confirm password" name="confirm_password" id="confirm_password" required
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                                    <button type="button" class="toggle-pw absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="confirm_password">
                                          <i class="bx-eye-closed text-lg"></i>
                                    </button>
                              </div>
                        </div>
                  </div>

                  <!-- ============================================== -->
                  <!-- STUDENT ONLY SECTION -->
                  <!-- ============================================== -->
                  <div id="studentOnlyFields" class="flex flex-col gap-5 hidden">
                        <!-- Demographics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                              <div class="flex flex-col gap-1.5">
                                    <label for="gender" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Gender</label>
                                    <select name="gender" id="gender" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white student-req">
                                          <option selected disabled value="">Select Gender</option>
                                          <option value="Male">Male</option>
                                          <option value="Female">Female</option>
                                    </select>
                              </div>
                              <div class="flex flex-col gap-1.5">
                                    <label for="date_of_birth" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Date of Birth</label>
                                    <div class="relative">
                                          <input type="date" name="date_of_birth" id="date_of_birth" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition student-req">
                                    </div>
                              </div>
                              <div class="flex flex-col gap-1.5">
                                    <label for="state_of_origin" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">State of Origin</label>
                                    <div class="relative">
                                          <select name="state_of_origin" id="state_of_origin" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white student-req">
                                                <option selected disabled value="">Select State</option>
                                                <?php foreach ($states as $state): ?>
                                                      <option value="<?= htmlspecialchars($state->state) ?>"><?= htmlspecialchars($state->state) ?></option>
                                                <?php endforeach; ?>
                                          </select>
                                    </div>
                                    </div>
                        </div>

                        <!-- Class & Home Address -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div class="flex flex-col gap-1.5">
                                    <label for="home_address" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Home Address</label>
                                    <div class="relative">
                                          <i class="bx-map text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                          <input type="text" placeholder="Full residential address" name="home_address" id="home_address"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition student-req">
                                    </div>
                              </div>
                              <div class="flex flex-col gap-1.5">
                                    <label for="class" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Class</label>
                                    <select name="class" id="class" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white student-req">
                                          <option selected value="">Select Class</option>
                                          <?php foreach ($rows as $row): ?>
                                                <option value="<?= $row->class ?>"><?= $row->class ?></option>
                                          <?php endforeach ?>
                                    </select>
                                    </div>
                        </div>

                        <!-- Parent Info Extra -->
                        <h4 class="text-xs font-bold text-gray-800 uppercase tracking-widest mt-2 border-b border-gray-100 pb-2">Parent / Guardian Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div class="flex flex-col gap-1.5">
                                    <label for="parent_name" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Guardian Name</label>
                                    <div class="relative">
                                          <i class="bx-user text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                          <input type="text" placeholder="Full name" name="parent_name" id="parent_name"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition student-req">
                                    </div>
                              </div>
                              <div class="flex flex-col gap-1.5">
                                    <label for="parent_email" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Parent Email</label>
                                    <div class="relative">
                                          <i class="bx-envelope text-lg absolute left-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                                          <input type="email" placeholder="Email (optional)" name="parent_email" id="parent_email"
                                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition">
                                    </div>
                              </div>
                        </div>
                  </div>
                  <!-- ============================================== -->

                  <button type="submit" name="register" id="regBtn"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 transition-all duration-200 font-semibold text-sm cursor-pointer mt-2">
                        Create Account
                  </button>
            </div>
      </form>
</div>


<script>
      // File Preview
      document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                  const reader = new FileReader();
                  reader.onload = function(e) {
                        $('#photo_icon_text').addClass('hidden');
                        $('#photo_preview').attr('src', e.target.result).removeClass('hidden');
                  }
                  reader.readAsDataURL(file);
            }
      });

      // Role Change Observer
      $("#user_role").on("change", function() {
            if ($(this).val() === 'student') {
                  $("#studentOnlyFields").removeClass('hidden');
                  $(".student-req").prop('required', true);
                  $("#userIdLabel").text("Admission Number");
                  $("#phoneLabel").text("Guardian Phone Number");
                  $("#dateLabel").text("Admission Date");
            } else {
                  $("#studentOnlyFields").addClass('hidden');
                  $(".student-req").prop('required', false);
                  $("#userIdLabel").text($(this).val() === 'staff' ? "Staff Number" : "Admin ID");
                  $("#phoneLabel").text("Your Phone Number");
                  $("#dateLabel").text("Join Date");
            }
      });

      // Register AJAX
      $("#regForm").on("submit", function (e) {
            e.preventDefault();

            let formData = new FormData(this);

            $.ajax({
                  url: "auth/register_auth.php",
                  method: "POST",
                  dataType: "json",
                  data: formData,
                  processData: false,
                  contentType: false,
                  beforeSend: function () {
                        $("#regBtn").prop("disabled", true).html(`<div class="animate-spin h-6 w-6 border-2 border-gray-300 border-t-transparent rounded-full mx-auto"></div>`);
                  },
                  success: function (data) {
                        if (data.status === "success") {
                              showAlert('success', data.message);
                              $("#regForm")[0].reset();
                              // Reset Image Preview as well
                              $('#photo_icon_text').removeClass('hidden');
                              $('#photo_preview').addClass('hidden').attr('src', '');
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
                  const icon = btn.querySelector('i');
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