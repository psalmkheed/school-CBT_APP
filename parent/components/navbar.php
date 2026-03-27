<?php
$guardian_id = $_SESSION['user_id'];
$guardian_username = $_SESSION['username'] ?? '0';

$stmt = $conn->prepare("SELECT COUNT(*) FROM broadcast WHERE recipient = :user_id AND is_read = 0");
$stmt->execute([':user_id' => $guardian_username]);
$unreadCount = (int) $stmt->fetchColumn();

// Colors for avatar
$initial = strtoupper(substr($guardian_name, 0, 1));
$bg_color = '#4f46e5'; // Primary Indigo
?>

<nav class="md:px-8 py-4 px-4 flex items-center justify-between sticky top-0 z-[100] bg-white border-b border-gray-100 shadow-sm">

      <!-- Left Section -->
      <div class="flex items-center gap-4">
            <button id="sideBarToggler" class="p-2 -ml-2 rounded-xl text-gray-500 hover:bg-gray-50 transition-colors md:invisible">
                  <i class="bx bxs-list text-2xl"></i>
            </button>
            <h4 class="text-xl font-bold text-gray-800 tracking-tight block md:hidden uppercase">Dashboard</h4>
      </div>

      <!-- Right Section -->
      <div class="flex items-center gap-4">
            
            <!-- Notification Bell -->
            <div class="relative h-10 w-10 rounded-xl bg-gray-50 flex items-center justify-center cursor-pointer border border-gray-100 hover:bg-indigo-50 hover:border-indigo-100 transition-all"
                 id="notification_toggler" data-tippy-content="Notifications">
                  <span class="absolute -top-1 -right-1 flex h-4 w-4">
                        <?php if ($unreadCount > 0): ?>
                              <span id="unread_ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span id="unread_count" class="relative inline-flex items-center justify-center h-4 w-4 rounded-full bg-red-500 text-white text-[10px] font-bold"><?= $unreadCount ?></span>
                        <?php else: ?>
                              <span id="unread_ping"  style="display:none"></span>
                              <span id="unread_count" style="display:none">0</span>
                        <?php endif; ?>
                  </span>
                  <i class="bx bx-bell text-xl text-gray-600"></i>
            </div>

            <!-- Profile Trigger -->
            <div class="flex items-center gap-3 pl-4 border-l border-gray-100 cursor-pointer group">
                  <div class="text-right hidden sm:block">
                        <p class="text-xs font-semibold text-gray-900 leading-none mb-1"><?= htmlspecialchars($guardian_name) ?></p>
                        <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest leading-none">Parent</p>
                  </div>
                  <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-semibold shadow-lg shadow-indigo-100 group-hover:scale-105 transition-transform">
                        <?= $initial ?>
                  </div>
            </div>

      </div>
</nav>

<!-- Dropdown Items (Simplified for Parent) -->
<!-- <div id="dropDownItems" class="fixed bottom-0 left-0 right-0 z-[200] md:absolute md:bottom-auto md:left-auto md:right-8 md:top-20 md:w-64" style="display:none;">
      <div class="bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                  <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Active User</p>
                  <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($guardian_name) ?>
                  </p>
            </div>
            <ul class="p-2">
                  <li>
                        <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 font-bold transition-all">
                              <i class="bx bx-power-off text-xl"></i> Sign Out
                        </a>
                  </li>
            </ul>
      </div>
</div> -->
