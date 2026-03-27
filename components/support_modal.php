<!-- Help & Support Modal -->
<div id="supportModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[900] hidden flex items-center justify-center p-4">
      <div id="supportModalContent" class="bg-white rounded-3xl w-[450px] max-w-full shadow-2xl fade-in-bottom overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-emerald-50/30">
                  <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
                              <i class="bx bx-headphone-mic text-xl"></i>
                        </div>
                        <div>
                              <h3 class="text-sm font-bold text-gray-800">Contact Admin Support</h3>
                              <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Direct Helpdesk</p>
                        </div>
                  </div>
                  <button onclick="document.getElementById('supportModal').classList.add('hidden')" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-500 transition-all flex items-center justify-center cursor-pointer">
                        <i class="bx bx-x text-xl"></i>
                  </button>
            </div>

            <!-- Body -->
            <form id="supportForm" class="p-6 space-y-4">
                  <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Subject / Topic</label>
                        <input type="text" id="supportSubject" required placeholder="e.g. Exam result inquiry" 
                              class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:bg-white transition-all">
                  </div>
                  <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Detailed Message</label>
                        <textarea id="supportMessage" rows="4" required placeholder="Describe your issue or question in detail..." 
                              class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:bg-white transition-all shadow-sm resize-none"></textarea>
                  </div>
                  <button type="submit" class="w-full bg-emerald-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-emerald-100 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all cursor-pointer flex items-center justify-center gap-2">
                        <i class="bx bx-send text-xl"></i>
                        Send Message to Admin
                  </button>
            </form>
      </div>
</div>

<script>
$(document).ready(function() {
    $('#supportForm').on('submit', function(e) {
        e.preventDefault();
        
        const subject = $('#supportSubject').val();
        const message = $('#supportMessage').val();
        const submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="bx bxs-loader-dots animate-spin text-xl"></i> Sending...');
        
        $.ajax({
            url: '../auth/send_support.php', // I will create this file
            type: 'POST',
            data: {
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sent!',
                        text: 'Your support request has been sent to the Admin office.',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        $('#supportModal').addClass('hidden');
                        $('#supportForm')[0].reset();
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to send message', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'An error occurred while sending your request.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="bx bx-send text-xl"></i> Send Message to Admin');
            }
        });
    });

    // Close on outside click
    window.onclick = function(event) {
        if (event.target == document.getElementById('supportModal')) {
            document.getElementById('supportModal').classList.add('hidden');
        }
    }
});

function openSupportModal() {
    document.getElementById('supportModal').classList.remove('hidden');
}
</script>
