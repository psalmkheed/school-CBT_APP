<?php
session_start();
require '../connections/db.php';

$id = $_SESSION['user_id'];

$stmt = $conn->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $id]);
$result = $stmt->fetch(PDO::FETCH_OBJ);

// Build the profile photo src
$photoSrc = '../src/img_icon.png'; // default
if (!empty($result->profile_photo)) {
      $photoSrc = '../uploads/profile_photos/' . $result->profile_photo;
}

$initials = strtoupper(substr($result->first_name ?? 'U', 0, 1) . substr($result->last_name ?? 'N', 0, 1));
$fullName = ucfirst($result->first_name ?? '') . ' ' . ucfirst($result->last_name ?? '');
$colors = ['#0d6efd', '#198754', '#6f42c1', '#dc3545'];
$avatarBg = $colors[$result->id % count($colors)];

$roleLabel = ucfirst($result->role ?? 'User');

// --- Dynamic Theme Colors ---
$themeColor = ($result->role === 'admin') ? 'green' : 'blue';
$themeBg = "bg-{$themeColor}-100";
$themeBgHover = "hover:bg-{$themeColor}-50";
$themeText = "text-{$themeColor}-700";
$themeTextHover = "hover:text-{$themeColor}-700";
$themeBorderHover = "hover:border-{$themeColor}-400";
$themeIcon = "text-{$themeColor}-600";
$themeIconSmall = "text-{$themeColor}-500";
$themeBtn = "bg-{$themeColor}-600 hover:bg-{$themeColor}-500";
$themeRing = "ring-{$themeColor}-100";
$themeFocus = "focus:ring-{$themeColor}-400";


// Get class name for staff
$class_name = '';
if ($result->role === 'staff') {
      $class_stmt = $conn->prepare("SELECT class FROM class WHERE teacher_id = :id LIMIT 1");
      $class_stmt->execute([':id' => $id]);
      $class_name = $class_stmt->fetchColumn() ?: 'Unassigned';
}
?>


<!-- ── Page wrapper ──────────────────────────────────────────────────────── -->
<div class="w-full p-4 md:p-8 fadeIn">

      <!-- Page Title -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                  <button onclick="goHome()"
                        class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 <?= $themeTextHover ?> hover:border-<?= $themeColor ?>-200 <?= $themeBgHover ?> transition-all cursor-pointer"
                        title="Go back" data-tippy-content="Back to Dashboard">
                        <i class="bx bx-arrow-left-stroke text-4xl"></i>
                  </button>
                  <div class="flex items-center gap-3 text-start">
                        <div class="p-2 rounded-lg <?= $themeBg ?> <?= $themeIcon ?> shrink-0">
                              <i class="bx-user-circle text-2xl"></i>
                        </div>
                        <div>
                              <h1 class="text-xl md:text-2xl font-bold text-gray-800">My Profile</h1>
                              <p class="text-xs md:text-sm text-gray-500">Manage your info</p>
                        </div>
                  </div>
            </div>
            <?php if ($_SESSION['role'] !== 'student'): ?>
            <button id="changePasswordBtn"
                  class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 <?= $themeBorderHover ?> <?= $themeTextHover ?> <?= $themeBgHover ?> transition-all duration-200 shadow-sm cursor-pointer"
                  data-tippy-content="Update your account password">
                  <i class="bx-lock-alt text-base"></i>
                  Change Password
            </button>
            <?php endif ?>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── LEFT CARD: Avatar + Photo Upload ─────────────────────────── -->
            <div class="lg:col-span-1">
                  <div
                        class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 flex flex-col items-center gap-5">

                        <!-- Avatar -->
                        <div class="relative group">
                              <img id="profilePreview" src="<?= htmlspecialchars($photoSrc) ?>" alt="Profile Photo"
                                    class="w-20 h-20 md:w-24 md:h-24 rounded-full object-cover ring-4 <?= $themeRing ?> shadow-md transition-all duration-300">

                              <!-- Camera overlay on hover -->
                              <label for="fileUpload"
                                    class="absolute inset-0 rounded-full bg-black/40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer"
                                    data-tippy-content="Click to update your profile picture">
                                    <i class="bx-camera text-white text-3xl"></i>
                                    <span class="text-white text-xs mt-1">Change</span>
                              </label>
                        </div>

                        <!-- Name & Role badge -->
                        <div class="text-center">
                              <h2 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($fullName) ?></h2>
                              <span class="mt-1 inline-block px-3 py-0.5 rounded-full text-xs font-semibold
                        <?= $result->role === 'admin' ? 'bg-purple-100 text-purple-700' :
                              ($result->role === 'staff' ? 'bg-sky-100 text-sky-700' :
                                    'bg-blue-100 text-blue-700') ?>">
                                    <?= htmlspecialchars($roleLabel) ?>
                              </span>
                        </div>

                        <!-- Upload Form -->
                        <form id="photoUploadForm" enctype="multipart/form-data"
                              class="w-full flex flex-col items-center gap-3">

                              <input id="fileUpload" name="profile_photo" type="file" accept="image/*" class="hidden">

                              <!-- "Choose Photo" button — always visible -->
                              <label for="fileUpload"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 <?= $themeBtn ?> text-white rounded-xl cursor-pointer transition-all duration-200 font-medium text-sm">
                                    <i class="bx-image-add text-lg"></i>
                                    Choose New Photo
                              </label>

                              <!-- Selected file name -->
                              <p id="fileName" class="text-xs text-gray-400 text-center truncate max-w-full">No file
                                    selected</p>

                              <!-- Save button — shown only after file chosen -->
                              <button id="submitBtn" type="submit"
                                    class="hidden w-full items-center justify-center gap-2 px-4 py-2.5 bg-gray-800 text-white rounded-xl hover:bg-gray-700 transition-all duration-200 font-medium text-sm">
                                    <span id="submitBtnText">Save Photo</span>
                                    <span id="submitSpinner" class="hidden">
                                          <svg class="animate-spin h-4 w-4 text-white inline"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                      stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z">
                                                </path>
                                          </svg>
                                    </span>
                              </button>

                              <p class="text-xs text-gray-400 text-center">JPG, PNG, GIF or WEBP · Max 2 MB</p>
                        </form>

                  </div>
            </div>

            <!-- ── RIGHT CARD: Info Details ─────────────────────────────────── -->
            <div class="lg:col-span-2 flex flex-col gap-5">

                  <!-- Personal Info Card -->
                  <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
                        <h3
                              class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-5 flex items-center gap-2">
                              <i class="bx-id-card <?= $themeIcon ?> text-lg"></i>
                              Personal Information
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                              <!-- Username -->
                              <div class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-400 uppercase tracking-wide">Username /
                                          ID</label>
                                    <div
                                          class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                          <i class="bx-at <?= $themeIconSmall ?> text-lg shrink-0"></i>
                                          <span
                                                class="text-gray-800 font-semibold text-sm"><?= htmlspecialchars($result->user_id ?? '—') ?></span>
                                    </div>
                              </div>

                              <!-- Role -->
                              <div class="flex flex-col gap-1">
                                    <label
                                          class="text-xs font-medium text-gray-400 uppercase tracking-wide">Role</label>
                                    <div
                                          class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                          <i class="bx-shield <?= $themeIconSmall ?> text-lg shrink-0"></i>
                                          <span
                                                class="text-gray-800 font-semibold text-sm"><?= htmlspecialchars($roleLabel) ?></span>
                                    </div>
                              </div>

                              <!-- First Name -->
                              <div class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-400 uppercase tracking-wide">First
                                          Name</label>
                                    <div
                                          class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                          <i class="bx-user <?= $themeIconSmall ?> text-lg shrink-0"></i>
                                          <span
                                                class="text-gray-800 font-semibold text-sm"><?= htmlspecialchars(ucfirst($result->first_name ?? '—')) ?></span>
                                    </div>
                              </div>

                              <!-- Last Name -->
                              <div class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-400 uppercase tracking-wide">Last
                                          Name</label>
                                    <div
                                          class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                          <i class="bx-user <?= $themeIconSmall ?> text-lg shrink-0"></i>
                                          <span
                                                class="text-gray-800 font-semibold text-sm"><?= htmlspecialchars(ucfirst($result->last_name ?? '—')) ?></span>
                                    </div>
                              </div>

                              <?php if (!empty($result->email)): ?>
                                    <!-- Email -->
                                    <div class="flex flex-col gap-1 sm:col-span-2">
                                          <label
                                                class="text-xs font-medium text-gray-400 uppercase tracking-wide">Email</label>
                                          <div
                                                class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                                <i class="bx-envelope <?= $themeIconSmall ?> text-lg shrink-0"></i>
                                                <span
                                                      class="text-gray-800 font-semibold text-sm"><?= htmlspecialchars($result->email) ?></span>
                                          </div>
                                    </div>
                              <?php endif; ?>

                              <?php if($_SESSION['role'] === 'student'): ?>
                                    <!-- Class -->
                                    <div class="flex flex-col gap-1">
                                          <label
                                                class="text-xs font-medium text-gray-400 uppercase tracking-wide">Class</label>
                                          <div
                                                class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                                <i class="bx-book-open <?= $themeIconSmall ?> text-lg shrink-0"></i>
                                                <span
                                                      class="text-gray-800 font-semibold text-sm"><?= htmlspecialchars($result->class) ?></span>
                                          </div>
                                    </div>
                              <?php endif; ?>

                              <?php if($_SESSION['role'] === 'staff' && !empty($class_name)): ?>
                                    <!-- Class -->
                                    <div class="flex flex-col gap-1">
                                          <label
                                                class="text-xs font-medium text-gray-400 uppercase tracking-wide">Class Assigned</label>
                                          <div
                                                class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                                <i class="bx-book-open <?= $themeIconSmall ?> text-lg shrink-0"></i>
                                                <span
                                                      class="text-gray-800 font-semibold text-sm"><?= htmlspecialchars($class_name) ?></span>
                                          </div>
                                    </div>
                              <?php endif; ?>

                        </div>
                  </div>

                  <!-- Account Security Card -->
                  <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
                        <h3
                              class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-5 flex items-center gap-2">
                              <i class="bx-lock-alt <?= $themeIcon ?> text-lg"></i>
                              Account Security
                        </h3>

                        <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-100 rounded-xl">
                              <div class="flex items-center gap-3">
                                    <div
                                          class="w-9 h-9 rounded-full <?= $themeBg ?> flex items-center justify-center shrink-0">
                                          <i class="bx-lock-keyhole <?= $themeIcon ?>"></i>
                                    </div>
                                    <div>
                                          <p class="text-sm font-semibold text-gray-700">Password</p>
                                          <p class="text-xs text-gray-400">Manage your account security</p>
                                    </div>
                              </div>
                              <?php if ($_SESSION['role'] !== 'student'): ?>
                              <button type="button"
                                    class="openPasswordModal text-xs <?= $themeBg ?> <?= $themeText ?> hover:bg-<?= $themeColor ?>-200 transition-colors font-bold px-4 py-1.5 rounded-full cursor-pointer">
                                    Change Password
                              </button>
                              <?php endif ?>
                        </div>
                  </div>

            </div>
      </div>
</div>

<!-- ── Change Password Modal ─────────────────────────────────────────────── -->
<div id="passwordModal" class="fixed inset-0 bg-black/90 z-[99999] flex items-center justify-center hidden backdrop-blur-md">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden fadeIn">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full <?= $themeBg ?> flex items-center justify-center">
                              <i class="bx-lock-keyhole <?= $themeIcon ?>"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Change Password</h3>
                  </div>
                  <button id="closePasswordModal" class="text-gray-400 hover:text-gray-600 transition cursor-pointer">
                        <i class="bx-x text-2xl"></i>
                  </button>
            </div>

            <!-- Modal Body -->
            <form id="changePasswordForm" class="p-6 flex flex-col gap-5">

                  <!-- Current Password -->
                  <div class="flex flex-col gap-1.5">
                        <label for="currentPassword" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Current Password</label>
                        <div class="relative">
                              <input type="password" id="currentPassword" name="current_password"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 <?= $themeFocus ?> focus:border-transparent transition"
                                    placeholder="Enter current password">
                              <button type="button" class="toggle-pw absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="currentPassword">
                                    <i class="bx-eye-closed text-lg"></i>
                              </button>
                        </div>
                  </div>

                  <!-- New Password -->
                  <div class="flex flex-col gap-1.5">
                        <label for="newPassword" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">New Password</label>
                        <div class="relative">
                              <input type="password" id="newPassword" name="new_password" minlength="6"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 <?= $themeFocus ?> focus:border-transparent transition"
                                    placeholder="Min. 6 characters">
                              <button type="button" class="toggle-pw absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="newPassword">
                                    <i class="bx-eye-closed text-lg"></i>
                              </button>
                        </div>
                  </div>

                  <!-- Confirm New Password -->
                  <div class="flex flex-col gap-1.5">
                        <label for="confirmPassword" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Confirm New Password</label>
                        <div class="relative">
                              <input type="password" id="confirmPassword" name="confirm_password" minlength="6"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 <?= $themeFocus ?> focus:border-transparent transition"
                                    placeholder="Re-enter new password">
                              <button type="button" class="toggle-pw absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="confirmPassword">
                                    <i class="bx-eye-closed text-lg"></i>
                              </button>
                        </div>
                  </div>

                  <!-- Submit -->
                  <button type="submit" id="pwSubmitBtn"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 <?= $themeBtn ?> text-white rounded-xl transition-all duration-200 font-semibold text-sm cursor-pointer">
                        <span id="pwBtnText">Update Password</span>
                        <span id="pwSpinner" class="hidden">
                              <svg class="animate-spin h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                              </svg>
                        </span>
                  </button>
            </form>
      </div>
</div>

<script>
      (function () {
            const form = document.getElementById('photoUploadForm');
            const input = document.getElementById('fileUpload');
            const fileName = document.getElementById('fileName');
            const preview = document.getElementById('profilePreview');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('submitBtnText');
            const spinner = document.getElementById('submitSpinner');

            // ── Live preview & reveal Save button ─────────────────────────────────
            input.addEventListener('change', () => {
                  if (!input.files.length) return;

                  const file = input.files[0];
                  fileName.textContent = file.name;

                  // Instant local preview
                  const reader = new FileReader();
                  reader.onload = (e) => { preview.src = e.target.result; };
                  reader.readAsDataURL(file);

                  // Show Save button
                  submitBtn.classList.remove('hidden');
                  submitBtn.classList.add('inline-flex');
            });

            // ── AJAX submit ───────────────────────────────────────────────────────
            form.addEventListener('submit', (e) => {
                  e.preventDefault();

                  if (!input.files.length) {
                        showToast('Please select a file first.', 'error');
                        return;
                  }

                  const formData = new FormData();
                  formData.append('profile_photo', input.files[0]);

                  btnText.textContent = 'Saving…';
                  spinner.classList.remove('hidden');
                  submitBtn.disabled = true;

                  fetch('../auth/profile_photo.php', {
                        method: 'POST',
                        body: formData
                  })
                        .then(res => res.json())
                        .then(data => {
                              if (data.success) {
                                    showToast(data.message, 'success');
                                    if (data.photo_url) {
                                          // Cache-bust so the browser actually fetches the new image
                                          const newSrc = data.photo_url + '?t=' + Date.now();
                                          preview.src = newSrc;

                                          // ── Also update the navbar avatar ──────────
                                          const navAvatar = document.getElementById('dropDownMenu');
                                          if (navAvatar) {
                                                if (navAvatar.tagName === 'IMG') {
                                                      navAvatar.src = newSrc;
                                                } else {
                                                      const img = document.createElement('img');
                                                      img.src = newSrc;
                                                      img.alt = navAvatar.title || 'Profile';
                                                      img.id = 'dropDownMenu';
                                                      img.className = 'rounded-full md:h-12 md:w-12 h-8 w-8 object-cover ring-2 ring-<?= $themeColor ?>-200 cursor-pointer';
                                                      img.title = navAvatar.title || '';
                                                      navAvatar.parentNode.replaceChild(img, navAvatar);
                                                }
                                          }
                                    }
                                    form.reset();
                                    fileName.textContent = 'No file selected';
                                    submitBtn.classList.add('hidden');
                                    submitBtn.classList.remove('inline-flex');
                              } else {
                                    showToast(data.message, 'error');
                              }
                        })
                        .catch(() => {
                              showToast('Something went wrong. Please try again.', 'error');
                        })
                        .finally(() => {
                              btnText.textContent = 'Save Photo';
                              spinner.classList.add('hidden');
                              submitBtn.disabled = false;
                        });
            });

            // ══════════════════════════════════════════════════════════════════════
            // ── Change Password Modal ─────────────────────────────────────────────
            // ══════════════════════════════════════════════════════════════════════

            const pwModal   = document.getElementById('passwordModal');
            const pwForm    = document.getElementById('changePasswordForm');
            const pwBtn     = document.getElementById('pwSubmitBtn');
            const pwBtnText = document.getElementById('pwBtnText');
            const pwSpinner = document.getElementById('pwSpinner');

            // Open modal (both header button and security card button)
            document.querySelectorAll('#changePasswordBtn, .openPasswordModal').forEach(btn => {
                  btn.addEventListener('click', () => {
                        pwModal.classList.remove('hidden');
                  });
            });

            // Close modal — X button
            document.getElementById('closePasswordModal').addEventListener('click', () => {
                  pwModal.classList.add('hidden');
                  pwForm.reset();
            });

            // Close modal — click backdrop
            pwModal.addEventListener('click', (e) => {
                  if (e.target === pwModal) {
                        pwModal.classList.add('hidden');
                        pwForm.reset();
                  }
            });

            // Close modal — ESC key
            document.addEventListener('keydown', (e) => {
                  if (e.key === 'Escape' && !pwModal.classList.contains('hidden')) {
                        pwModal.classList.add('hidden');
                        pwForm.reset();
                  }
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

            // AJAX submit password change
            pwForm.addEventListener('submit', (e) => {
                  e.preventDefault();

                  const current = document.getElementById('currentPassword').value.trim();
                  const newPw   = document.getElementById('newPassword').value.trim();
                  const confirm = document.getElementById('confirmPassword').value.trim();

                  if (!current || !newPw || !confirm) {
                        showToast('All fields are required.', 'error');
                        return;
                  }

                  if (newPw !== confirm) {
                        showToast('New passwords do not match.', 'error');
                        return;
                  }

                  if (newPw.length < 6) {
                        showToast('Password must be at least 6 characters.', 'error');
                        return;
                  }

                  pwBtnText.textContent = 'Updating…';
                  pwSpinner.classList.remove('hidden');
                  pwBtn.disabled = true;

                  const formData = new FormData();
                  formData.append('current_password', current);
                  formData.append('new_password', newPw);
                  formData.append('confirm_password', confirm);

                  fetch('../auth/change_password.php', {
                        method: 'POST',
                        body: formData
                  })
                        .then(res => res.json())
                        .then(data => {
                              if (data.success) {
                                    showToast(data.message, 'success');
                                    pwForm.reset();
                                    setTimeout(() => pwModal.classList.add('hidden'), 1500);
                              } else {
                                    showToast(data.message, 'error');
                              }
                        })
                        .catch(() => {
                              showToast('Something went wrong. Please try again.', 'error');
                        })
                        .finally(() => {
                              pwBtnText.textContent = 'Update Password';
                              pwSpinner.classList.add('hidden');
                              pwBtn.disabled = false;
                        });
            });

      })();
</script>