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
                  <div class="flex items-center gap-2">
                        <div class="size-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                              <i class="bx-envelope text-blue-600"></i>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-800">Send Message</h3>
                  </div>
            </div>
      </div>

      <div class="w-full fadeIn">
            <div class="flex items-center gap-2 mb-4 text-start">
                  <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <i class="bx-send text-green-600"></i>
                  </div>
                  <h3 class="text-lg font-bold text-gray-800">Compose New Broadcast</h3>
            </div>

            <form id="broadcastMessage">
                  <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 flex flex-col gap-6">

                        <div class="flex flex-col gap-2">
                              <label for="recipient" class="text-xs font-bold text-gray-500 uppercase tracking-widest">Recipient</label>
                              <select id="recipient"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition bg-white cursor-pointer">
                                    <option disabled selected>Select Recipient</option>
                                    <?php foreach ($result as $res): ?>
                                          <option value="<?= $res->user_id ?>"><?= $res->user_id ?> - <?= $res->first_name ?> <?= $res->last_name ?></option>
                                    <?php endforeach; ?>
                              </select>
                        </div>

                        <div class="flex flex-col gap-2">
                              <label for="subject" class="text-xs font-bold text-gray-500 uppercase tracking-widest">Subject</label>
                              <input type="text" id="subject"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent transition"
                                    placeholder="Enter message subject">
                        </div>

                        <div class="flex flex-col gap-2">
                              <label for="message" class="text-xs font-bold text-gray-500 uppercase tracking-widest">Message</label>
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
</div>
</div>


<script>


      $("#broadcastMessage").on("submit", function (e) {
            e.preventDefault();

            $.ajax({
                  url: '/school_app/admin/auth/broadcastAuth.php',
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
            })
      })
</script>