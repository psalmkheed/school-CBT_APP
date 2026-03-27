<?php
require '../../connections/db.php';
$stmt = $conn->prepare('SELECT * FROM users ORDER BY first_name ASC');
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
<div class="w-full p-4 md:p-8 min-h-screen">
      <!-- Page Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                  <button onclick="goHome()"
                        class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-green-700 hover:border-green-200 hover:bg-green-50 transition-all cursor-pointer"
                        title="Go back" data-tippy-content="Back to Dashboard">
                        <i class="bx bx-arrow-left-stroke text-4xl"></i>
                  </button>
                  <div class="flex items-center gap-3">
                        <div
                              class="size-10 rounded-2xl bg-blue-100 flex items-center justify-center shrink-0 shadow-sm border border-blue-200">
                              <i class="bx bx-broadcast text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                              <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">Broadcast System
                              </h3>
                              <p class="text-sm text-gray-400 font-medium">Send notifications and manage sent messages
                              </p>
                        </div>
                  </div>
            </div>

            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                  <div
                        class="flex p-1.5 bg-gray-100/50 rounded-2xl border border-gray-100 w-full md:w-auto overflow-x-auto gap-2">
                        <button
                              class="flex-1 md:flex-none px-6 py-2.5 rounded-xl transition-all duration-300 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group bg-white shadow-sm border border-gray-100"
                              id="composeBtn">
                              <i
                                    class="bx bx-edit text-lg text-blue-500 group-hover:scale-110 transition-transform"></i>
                              <span class="text-blue-600">Compose</span>
                        </button>
                        <button
                              class="flex-1 md:flex-none px-6 py-2.5 rounded-xl transition-all duration-300 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group border border-transparent hover:bg-white/50"
                              id="manageBtn">
                              <i
                                    class="bx bx-history text-lg text-orange-500 group-hover:scale-110 transition-transform"></i>
                              <span class="text-gray-600">History</span>
                        </button>
                  </div>
            </div>
      </div>

      <div id="composeSection" class="w-full fadeIn">
            <div class="flex items-center gap-2 mb-4 text-start">
                  <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <i class="bx-send text-green-600"></i>
                  </div>
                  <h3 class="text-lg font-bold text-gray-800">Compose New Broadcast</h3>
            </div>

            <form id="broadcastMessage" method="POST">
                  <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col gap-6">

                        <div class="flex flex-col gap-2">
                              <label for="recipient"
                                    class="text-xs font-bold text-gray-500 uppercase tracking-widest">Recipient</label>
                              <select id="recipient"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white cursor-pointer">
                                    <option value="" disabled selected>Select Recipient</option>
                                    <option value="all">All Users (Students, Staff & Parents)</option>
                                    <option value="students">All Students</option>
                                    <option value="staff">All Staff</option>
                                    <option value="parents">All Parents</option>
                                    <optgroup label="Specific Users">
                                    <?php foreach ($result as $res):
                                          if ($res->user_id !== 'admin001' && $res->first_name !== 'Super Admin') {

                                                $userID = $res->user_id;
                                                $first_name = $res->first_name;
                                                $surname = $res->surname;
                                                $full_name = "$first_name $surname";
                                          } ?>
                                          <option value="<?= $userID ?>"><?= $userID ?> - <?= $full_name ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                              </select>
                        </div>

                        <div class="flex flex-col gap-2">
                              <label for="subject"
                                    class="text-xs font-bold text-gray-500 uppercase tracking-widest">Subject</label>
                              <input type="text" id="subject"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition"
                                    placeholder="Enter message subject">
                        </div>

                        <div class="flex flex-col gap-2">
                              <label for="message"
                                    class="text-xs font-bold text-gray-500 uppercase tracking-widest">Message</label>
                              <textarea rows="6" placeholder="Compose your message..."
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition resize-none"
                                    id="message"></textarea>
                        </div>

                        <button type="submit"
                              class="w-full md:w-max px-8 py-3 bg-green-600 text-white rounded-xl hover:bg-green-500 hover:shadow-lg transition-all duration-300 font-bold text-sm cursor-pointer ml-auto"
                              name="send_message" id="msgBtn">
                              <i class="bx-send mr-2"></i>
                              Send Message
                        </button>
                  </div>
            </form>
      </div>


      <div id="manageSection" class="w-full fadeIn hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                  <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                              <i class="bx bx-history text-orange-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Broadcast History</h3>
                  </div>
                  <div class="flex items-center gap-3">
                        <div class="relative w-full md:w-64 group">
                              <i
                                    class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                              <input type="text" id="broadcastSearch"
                                    class="w-full pl-11 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition shadow-sm"
                                    placeholder="Search broadcasts...">
                        </div>
                        <button id="broadcastCSV"
                              class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-50 transition shadow-sm flex items-center gap-2 cursor-pointer whitespace-nowrap">
                              <i class="bx bx-arrow-big-down-line"></i> CSV
                        </button>
                  </div>
            </div>

            <div class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
                  <div class="overflow-x-auto">
                        <table id="broadcastTable" class="w-full text-left border-collapse">
                              <thead class="bg-gray-50/80 border-b border-gray-100">
                                    <tr>
                                          <th
                                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                Recipient</th>
                                          <th
                                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                Subject</th>
                                          <th
                                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                Date
                                                Sent</th>
                                          <th
                                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                Status</th>
                                          <th
                                                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">
                                                Actions</th>
                                    </tr>
                              </thead>
                              <tbody id="broadcastBody" class="divide-y divide-gray-50">
                                    <!-- Loaded via AJAX -->
                              </tbody>
                        </table>
                  </div>
            </div>
      </div>
</div>


<script>
      // Tab switching
      function switchTab(showId, hideId, activeBtn, inactiveBtn, activeClasses, inactiveClasses) {
            $("#" + hideId).addClass("hidden");
            $("#" + showId).removeClass("hidden");

            $(inactiveBtn).removeClass(activeClasses).addClass(inactiveClasses)
                  .find("span").removeClass("text-blue-600 text-orange-600").addClass("text-gray-600");
            $(activeBtn).removeClass(inactiveClasses).addClass(activeClasses);
      }

      $("#composeBtn").on("click", function () {
            switchTab("composeSection", "manageSection", this, "#manageBtn", "bg-white shadow-sm border-gray-100", "border-transparent hover:bg-white/50");
            $(this).find("span").removeClass("text-gray-600").addClass("text-blue-600");
      });

      $("#manageBtn").on("click", function () {
            switchTab("manageSection", "composeSection", this, "#composeBtn", "bg-white shadow-sm border-gray-100", "border-transparent hover:bg-white/50");
            $(this).find("span").removeClass("text-gray-600").addClass("text-orange-600");
            loadBroadcasts();
      });

      function loadBroadcasts() {
            $("#broadcastBody").html('<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400"><i class="bx bxs-loader-dots bx-spin text-2xl"></i></td></tr>');
            $.ajax({
                  url: 'auth/fetch_broadcasts.php',
                  type: 'GET',
                  success: function (html) {
                        $("#broadcastBody").html(html);
                        window.initTableToolkit({
                              searchId: 'broadcastSearch',
                              tableId: 'broadcastTable',
                              bodyId: 'broadcastBody',
                              csvBtnId: 'broadcastCSV',
                              csvName: 'broadcast_history'
                        });
                        bindBroadcastActions();
                  }
            });
      }

      function bindBroadcastActions() {
            $('.delete-broadcast-btn').off('click').on('click', function (e) {
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = $(this).closest('tr');

                  Swal.fire({
                        title: 'Delete Message?',
                        text: "Wait, this action is permanent!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                  }).then((result) => {
                        if (result.isConfirmed) {
                              $.post('auth/delete_broadcast.php', { id: id }, function (res) {
                                    if (res.success) {
                                          row.fadeOut(300, function () { $(this).remove(); });
                                          Swal.fire('Deleted!', 'Message deleted.', 'success');
                                    } else {
                                          Swal.fire('Error', res.message, 'error');
                                    }
                              });
                        }
                  });
            });

            $('.edit-broadcast-btn').off('click').on('click', function (e) {
                  e.preventDefault();
                  const id = $(this).data('id');

                  $.post('auth/get_broadcast.php', { id: id }, function (res) {
                        if (res.success) {
                              const b = res.data;
                              Swal.fire({
                                    title: 'Edit Broadcast',
                                    html: `
                                        <div class="flex flex-col gap-3 text-left w-full mt-4">
                                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">Recipient</label>
                                            <input id="swal_b_recipient" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" value="${b.recipient.replace(/"/g, '&quot;')}">
                                            
                                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-2">Subject</label>
                                            <input id="swal_b_subject" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" value="${b.subject.replace(/"/g, '&quot;')}">
                                            
                                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wide mt-2">Message</label>
                                            <textarea id="swal_b_message" rows="5" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm resize-none whitespace-pre-wrap">${b.message}</textarea>
                                        </div>
                                  `,
                                    showCancelButton: true,
                                    confirmButtonText: 'Save Changes',
                                    confirmButtonColor: '#f97316',
                                    preConfirm: () => {
                                          return {
                                                id: id,
                                                recipient: $('#swal_b_recipient').val(),
                                                subject: $('#swal_b_subject').val(),
                                                message: $('#swal_b_message').val()
                                          };
                                    }
                              }).then((result) => {
                                    if (result.isConfirmed) {
                                          $.post('auth/update_broadcast.php', result.value, function (upd) {
                                                if (upd.success) {
                                                      Swal.fire('Saved!', 'Message updated.', 'success');
                                                      loadBroadcasts();
                                                } else {
                                                      Swal.fire('Error', upd.message, 'error');
                                                }
                                          });
                                    }
                              });
                        } else {
                              Swal.fire('Error', res.message || 'Failed to load details.', 'error');
                        }
                  });
            });
      }

      $("#broadcastMessage").on("submit", function (e) {
            e.preventDefault();

            $.ajax({
                  url: 'auth/broadcastAuth.php',
                  method: 'POST',
                  dataType: 'json',
                  data: {
                        recipient: $("#recipient").val(),
                        subject: $("#subject").val(),
                        message: $("#message").val()
                  },
                  beforeSend: function () {
                        $("#msgBtn").prop("disabled", true).text("Sending...");
                  },
                  success: function (res) {
                        if (res.status === "success") {
                              showAlert('success', res.message);
                              $("#broadcastMessage")[0].reset();
                        } else {
                              showAlert('error', res.message);
                        }
                  },
                  error: function () {
                        showAlert('error', "Network error. Try again.");
                  },
                  complete: function () {
                        $("#msgBtn").prop("disabled", false).html('<i class="bx-send mr-2"></i> Send Message');
                  }
            });
      });
</script>