<!-- notification full screen -->
<div class="fixed inset-0 bg-black/90 h-screen z-[99999] translate-x-[-50%] opacity-0 pointer-events-none transition-all duration-700 backdrop-blur-md"
      id="notification_screen">
      <!-- notification area -->
      <div class="lg:w-[35%] w-full h-screen bg-white overflow-y-auto overflow-x-visible float-right" id="notification_area">
            <!-- close Icon -->
            <div class="bg-white py-2 px-4 w-full h-12 flex items-center sticky inset-0 z-50 justify-between">
                  <h3 class="text-xl font-bold">Notifications</h3>
                  <div class="animate-pulse rounded-full h-8 flex items-center justify-center w-8 bg-red-600 text-sm cursor-pointer"
                        id="notification_closeBtn"><i class="fas fa-times text-white"></i></div>
            </div>
            <div class="p-6">
                  <?php if (empty($results)): ?>

                        <p class="w-full p-4 text-gray-500 text-center text-xl">
                              You have no unread messages
                        </p>

                   <?php else: ?>
                        <?php foreach ($results as $msg): ?>
                              <div class="flex flex-col gap-4 justify-center border border-gray-100 rounded-md shadow-sm p-4 mb-4">

                                    <div class="flex gap-5 items-center justify-between">
                                          <div class="flex gap-5 items-center">
                                                <i class="bxs-bell text-xl text-red-700"></i>
                                                <h3 class="text-lg font-medium">
                                                      <?= htmlspecialchars($msg->subject) ?>
                                                </h3>
                                          </div>


                                    </div>

                                    <div class="bg-gray-50 p-2 rounded-md text-gray-700 text-sm">
                                          <?php 
                                                // Dynamic Variable Magic
                                                $message = $msg->message;
                                                $replacements = [
                                                      '{firstname}' => $user->first_name ?? 'User',
                                                      '{lastname}'  => $user->last_name ?? '',
                                                      '{fullname}'  => ($user->first_name ?? 'User') . ' ' . ($user->last_name ?? ''),
                                                      '{role}'      => ucfirst($_SESSION['role'] ?? 'User'),
                                                      '{school_name}' => $result->school_name ?? '',
                                                ];
                                                
                                                // Replace case-insensitively
                                                $processed_msg = str_ireplace(array_keys($replacements), array_values($replacements), $message);
                                                echo nl2br(htmlspecialchars($processed_msg)); 
                                          ?>
                                    </div>

                                    <div class="flex justify-between items-center">
                                          <div class="flex gap-5 items-center">
                                                <small class="hidden md:block text-orange-500">From
                                                      <?= htmlspecialchars($msg->username) ?>
                                                </small>
                                                <p class="text-orange-700 text-sm">
                                                      <?= date('d F, Y', strtotime($msg->created_at)) ?>
                                                </p>
                                          </div>

                                          <!-- Mark as Read Button -->
                                          <?php if (!$msg->is_read): ?>
                                                <button type="button"
                                                      class="mark-read-btn  text-blue-600 rounded transition flex items-center gap-1 cursor-pointer"
                                                      data-id="<?= $msg->id ?>"><i class="bx-checks text-lg"></i> Mark as Read</button>
                                          <?php else: ?>
                                                <span class="flex items-center gap-1 text-gray-500 text-sm"><i class="bx-check-circle text-lg"></i> <p class="m-0 p-0">Read</p></span>
                                          <?php endif; ?>
                                    </div>

                              </div>
                        <?php endforeach; ?>

                  <?php endif; ?>
            </div><!-- /.p-6 -->
      </div><!-- /#notification_area -->
</div><!-- /#notification_screen -->
