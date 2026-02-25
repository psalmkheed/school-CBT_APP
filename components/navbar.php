<?php
$id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT *, CONCAT(UPPER(LEFT(first_name, 1)), UPPER(LEFT(last_name, 1))) AS initials FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

$initial = $user->initials;
$colors = ['#0d6efd', '#198754', '#6f42c1', '#dc3545'];
$bg = $colors[$user->id % count($colors)];

$stmt = $conn->prepare("SELECT COUNT(*) FROM broadcast WHERE recipient = :user_id AND is_read = 0");
$userId = $_SESSION['username'] ?? '0';
$stmt->execute([':user_id' => $userId]);
$unreadCount = (int) $stmt->fetchColumn();

// Dynamic Theme Colors
$themeColor   = ($user->role === 'admin') ? 'green' : (($user->role === 'staff') ? 'sky' : 'blue');
$themeText    = "text-{$themeColor}-600";
$themeHoverBg = "hover:bg-{$themeColor}-50";
$themeHoverText = "hover:text-{$themeColor}-700";
$themeRing    = "ring-{$themeColor}-200";
$themeGradient = ($user->role === 'admin') ? 'from-green-50 to-emerald-50/30' : 'from-blue-50 to-indigo-50/30';

$navBg = match($user->role) {
    'admin'   => 'bg-green-500 text-green-100',
    'student' => 'bg-blue-500 text-blue-100',
    'staff'   => 'bg-sky-500 text-sky-100',
    default   => 'bg-gray-500 text-gray-100',
};
?>

<!-- ─── Navbar ──────────────────────────────────────────── -->
<nav class="md:px-10 py-3 px-2 flex items-center justify-between sticky top-0 z-[100] shadow-sm <?= $navBg ?> md:bg-white">

      <!-- Left: Hamburger + Title -->
      <div class="flex items-center gap-2 md:invisible">
            <i class="fa-solid fa-bars text-2xl cursor-pointer md:hidden"
               id="sideBarToggler" data-tippy-content="Toggle Sidebar Menu"></i>
            <h4 class="text-lg font-bold md:hidden">Dashboard</h4>
      </div>

      <!-- Right: Fullscreen + Bell + Avatar -->
      <div class="flex gap-4 items-center">

            <!-- Fullscreen (desktop only) -->
            <button id="fullscreenToggler"
                    class="hidden md:block bg-gray-100 px-3 py-1.5 rounded-lg text-gray-500 text-sm cursor-pointer"
                    data-tippy-content="Maximize View">
                  Go Fullscreen
            </button>

            <!-- Notification Bell -->
            <div class="relative h-9 w-9 md:h-11 md:w-11 rounded-full border border-red-400/50 bg-red-50 flex items-center justify-center cursor-pointer"
                 id="notification_toggler" data-tippy-content="View Notifications">
                  <span class="absolute -top-1 -right-1 flex h-4 w-4">
                        <?php if ($unreadCount > 0): ?>
                              <span id="unread_ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span id="unread_count" class="relative inline-flex items-center justify-center h-4 w-4 rounded-full bg-red-500 text-white text-[10px] font-bold"><?= $unreadCount ?></span>
                        <?php else: ?>
                              <span id="unread_ping"  style="display:none"></span>
                              <span id="unread_count" style="display:none">0</span>
                        <?php endif; ?>
                  </span>
                  <i class="bxs-bell text-xl text-red-600"></i>
            </div>

            <!-- Account Avatar (trigger) -->
            <div class="cursor-pointer flex items-center" id="dropDownMenu"
                 data-tippy-content="Account Menu (<?= htmlspecialchars($user->first_name) ?>)">
                  <?php if (!empty($user->profile_photo)): ?>
                        <img src="<?= $base ?>uploads/profile_photos/<?= htmlspecialchars($user->profile_photo) ?>"
                             alt="<?= htmlspecialchars($user->first_name) ?>"
                             class="rounded-full h-9 w-9 md:h-11 md:w-11 object-cover ring-2 <?= $themeRing ?>">
                  <?php else: ?>
                        <div class="flex items-center justify-center text-white font-bold text-sm md:text-lg rounded-full h-9 w-9 md:h-11 md:w-11"
                             style="background-color:<?= $bg ?>">
                              <?= $initial ?>
                        </div>
                  <?php endif; ?>
            </div>

      </div>
</nav>

<!-- ─── Dropdown Overlay (mobile backdrop) ─────────────── -->
<div id="dropdownOverlay"
     class="fixed inset-0 z-[190] bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0 pointer-events-none md:hidden">
</div>

<!-- ─── Account Dropdown Panel ─────────────────────────── -->
<div id="dropDownItems"
     class="fixed bottom-0 left-0 right-0 z-[200]
            md:absolute md:bottom-auto md:left-auto md:right-4 md:top-16 md:w-72"
     style="display:none;">

      <!-- Inner card -->
      <div class="bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-gray-100 ring-1 ring-black/5 overflow-hidden w-full">

            <!-- Mobile drag handle -->
            <div class="flex justify-center pt-3 pb-1 md:hidden">
                  <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>

            <!-- User header -->
            <div class="px-5 py-4 bg-gradient-to-br <?= $themeGradient ?> border-b border-gray-100">
                  <div class="flex items-center gap-3">
                        <?php if (!empty($user->profile_photo)): ?>
                              <img src="<?= $base ?>uploads/profile_photos/<?= htmlspecialchars($user->profile_photo) ?>"
                                   class="w-11 h-11 rounded-full object-cover ring-2 ring-white shadow-sm">
                        <?php else: ?>
                              <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-sm"
                                   style="background-color:<?= $bg ?>">
                                    <?= $initial ?>
                              </div>
                        <?php endif; ?>
                        <div class="min-w-0">
                              <h4 class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?></h4>
                              <p class="text-[11px] font-semibold <?= $themeText ?> uppercase tracking-wider"><?= htmlspecialchars($_SESSION['role']) ?></p>
                        </div>
                  </div>
            </div>

            <!-- Menu items -->
            <ul class="p-2 space-y-0.5 pb-[100px] md:pb-2">

                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                        <li>
                              <div class="flex items-center gap-3 px-4 py-3 rounded-xl <?= $themeHoverBg ?> <?= $themeHoverText ?> text-gray-700 transition group cursor-pointer">
                                    <div class="w-9 h-9 rounded-lg bg-orange-50 flex items-center justify-center group-hover:bg-orange-100 transition">
                                          <i class="bx-book-bookmark text-xl text-orange-600"></i>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-600 group-hover:<?= $themeHoverText ?>">Exam History</span>
                              </div>
                        </li>
                        <li>
                              <div class="flex items-center gap-3 px-4 py-3 rounded-xl <?= $themeHoverBg ?> <?= $themeHoverText ?> text-gray-700 transition group cursor-pointer">
                                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center group-hover:bg-blue-100 transition">
                                          <i class="bx-book-library text-xl text-blue-600"></i>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-600 group-hover:<?= $themeHoverText ?>">Saved Questions</span>
                              </div>
                        </li>
                  <?php endif; ?>

                  <li>
                        <div id="profile" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= $themeHoverBg ?> text-gray-700 transition group cursor-pointer">
                              <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100 transition">
                                    <i class="bx-cog text-xl text-purple-600"></i>
                              </div>
                              <span class="text-sm font-semibold text-gray-600 group-hover:<?= $themeHoverText ?>">Profile Settings</span>
                        </div>
                  </li>

                  <div class="mx-3 my-1 border-t border-gray-100"></div>

                  <li>
                        <a href="<?= $base ?>auth/logout.php"
                           class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 text-gray-700 transition group">
                              <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center group-hover:bg-red-100 transition">
                                    <i class="bx-arrow-out-left-square-half text-xl text-red-600"></i>
                              </div>
                              <span class="text-sm font-semibold text-gray-600 group-hover:text-red-700">Logout</span>
                        </a>
                  </li>

            </ul>
      </div>
</div>