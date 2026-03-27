<!-- notification full screen -->
<div class="fixed inset-0 bg-black/90 h-screen z-[99999] translate-x-[-50%] opacity-0 pointer-events-none transition-all duration-700 backdrop-blur-md hidden"
      id="notification_screen">
      <!-- notification area -->
      <div class="lg:w-[33%] md:w-[50%] w-[100%] h-screen glass border-l border-white/20 overflow-y-auto float-right shadow-2xl relative"
            id="notification_area">
            <!-- Header -->
            <div
                  class="glass py-4 px-6 w-full h-20 flex items-center sticky top-0 z-50 justify-between border-b border-white/10">
                  <div>
                        <p class="text-xl font-bold text-gray-700  ">Notifications</p>
                  </div>
                  <button
                        class="size-10 rounded-2xl bg-red-500/10 text-red-600 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all duration-300 hover:rotate-90 active-shrink cursor-pointer"
                        id="notification_closeBtn">
                        <i class="bx bx-x text-2xl"></i>
                  </button>
            </div>

            <div class="p-6">
                  <?php if (empty($results)): ?>
                        <div class="flex flex-col items-center justify-center py-20 text-center">
                              <div class="size-20 rounded-[2rem] bg-gray-50 flex items-center justify-center mb-4">
                                    <i class="bx bx-bell-slash text-4xl text-gray-200"></i>
                              </div>
                              <h4 class="font-bold text-gray-800">All caught up!</h4>
                              <p class="text-xs text-gray-700 mt-1">You have no new notifications at the moment.</p>
                        </div>

                  <?php else: ?>
                        <?php foreach ($results as $msg): ?>
                              <div
                                    class="notification p-5 rounded-xl scroll-smooth shadow-sm mb-4 hover-lift group relative overflow-hidden transition-all duration-300 bg-white">
                                    <div class="flex items-start gap-4 mb-3">
                                          <div class="size-10 rounded-2xl bg-blue-500/10 flex items-center justify-center shrink-0">
                                                <i class="bx bxs-bell-ring text-xl text-blue-600"></i>
                                          </div>
                                          <div class="min-w-0">
                                                <h3 class="font-semibold text-gray-800 tracking-tight truncate leading-tight mt-1">
                                                      <?= htmlspecialchars(ucfirst($msg->subject)) ?>
                                                </h3>
                                                <p class="text-[9px] font-semibold text-gray-400">
                                                      <?= date('d M, Y • h:i A', strtotime($msg->created_at)) ?>
                                                </p>
                                          </div>
                                    </div>

                                    <div
                                          class="bg-white/50 dark:bg-black/5 p-3 rounded-2xl text-gray-600 text-sm leading-relaxed font-medium mb-4">
                                          <?php
                                                $message = $msg->message;
                                                $replacements = [
                                                      '{firstname}' => $user->first_name ?? 'User',
                                                '{lastname}' => $user->surname ?? '',
                                                '{fullname}' => (ucfirst($user->surname ?? '')) . ' ' . (ucfirst($user->first_name ?? 'User')),
                                                '{role}' => ucfirst($_SESSION['role'] ?? 'User'),
                                                '{school_name}' => strtoupper($result->school_name) ?? '',
                                                '{sender}' => ucfirst($msg->username) ?? '',
                                          ];
                                          $processed_msg = str_ireplace(array_keys($replacements), array_values($replacements), $message);
                                          echo nl2br(htmlspecialchars($processed_msg));
                                          ?>
                                    </div>

                                    <div class="flex items-center justify-between">
                                          <span class="text-[10px] font-bold text-gray-400">
                                                From: <span class="text-blue-500"><?= htmlspecialchars($msg->username) ?></span>
                                          </span>

                                          <?php if (!$msg->is_read): ?>
                                                <button type="button"
                                                      class="mark-read-btn flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-[10px] font-semibold uppercase tracking-widest rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 active-shrink transition-all transition-all cursor-pointer"
                                                      data-id="<?= $msg->id ?>">
                                                      <i class="bx bx-check-double text-sm"></i> Done
                                                </button>
                                          <?php else: ?>
                                                <span class="flex items-center gap-1 text-gray-400 text-[10px] font-semibold uppercase tracking-widest">
                                                      <i class="bx-check-circle text-sm text-green-500"></i> Viewed
                                                </span>
                                          <?php endif; ?>
                                    </div>
                              </div>
                        <?php endforeach; ?>

                  <?php endif; ?>
            </div><!-- /.p-6 -->
      </div><!-- /#notification_area -->
</div><!-- /#notification_screen -->