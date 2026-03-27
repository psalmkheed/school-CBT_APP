<?php
require '../../connections/db.php';

if (!in_array($_SESSION['role'], ['admin', 'super'])) {
    echo "Unauthorized Access.";
    exit;
}
?>

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-indigo-700 hover:border-indigo-200 hover:bg-indigo-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 shadow-sm border border-indigo-200">
                    <i class="bx bx-user-id-card text-indigo-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 tracking-tight">Staff Directory</h3>
                    <p class="text-sm text-gray-400 font-medium">Manage all teaching and non-teaching staff financial profiles.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-[2rem] p-6 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-10">
        <div class="flex items-center justify-between mb-6">
            <div class="relative w-full max-w-sm">
                <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" id="directorySearch" placeholder="Search staff name or ID..." class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="staffDirectoryTable">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Staff Member</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Staff ID</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Base Salary (₦)</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Bank Details</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="staffDirectoryBody" class="divide-y divide-gray-50 hidden">
                    <!-- Loaded via Ajax -->
                </tbody>
            </table>
            
            <div id="directoryLoader" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <i class="bx bx-loader-alt bx-spin text-4xl mb-2 text-indigo-600"></i>
                <p class="text-sm font-semibold">Loading Directory...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Finance Profile -->
<div id="financeModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl scale-100 transform transition-all">
        <div class="px-6 py-5 flex items-center justify-between border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2"><i class="bx bx-wallet text-indigo-600"></i> Edit Finance Profile</h3>
            <button onclick="document.getElementById('financeModal').classList.add('hidden')" class="w-8 h-8 rounded-full hover:bg-gray-200 flex items-center justify-center text-gray-500 transition"><i class="bx bx-x text-xl"></i></button>
        </div>
        <form id="financeForm" class="p-6 space-y-4">
            <input type="hidden" name="staff_id" id="f_staff_id">
            
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Base Salary (₦)</label>
                <input type="number" step="0.01" name="basic_salary" id="f_basic_salary" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-mono">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Bank Name</label>
                    <input type="text" name="bank_name" id="f_bank_name" placeholder="E.g. Access Bank" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Account No.</label>
                    <input type="text" name="account_number" id="f_account_number" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Account Name</label>
                <input type="text" name="account_name" id="f_account_name" placeholder="E.g. John Doe" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all">
            </div>
            
            <div class="pt-4 mt-6 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('financeModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">Cancel</button>
                <button type="submit" id="f_submitBtn" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition shadow-lg shadow-indigo-200 flex items-center gap-2">
                    <i class="bx bx-save text-lg"></i> Save Profile
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let staffData = [];

    function loadDirectory() {
        $('#directoryLoader').removeClass('hidden');
        $('#staffDirectoryBody').addClass('hidden');
        
        $.get(BASE_URL + 'admin/auth/hr_api.php?action=get_staff_directory', function(res) {
            if(res.status === 'success') {
                staffData = res.data;
                renderDirectory(staffData);
            }
            $('#directoryLoader').addClass('hidden');
            $('#staffDirectoryBody').removeClass('hidden');
        });
    }

    function renderDirectory(data) {
        let html = '';
        if(data.length === 0) {
            html = `<tr><td colspan="5" class="px-6 py-10 text-center"><p class="text-gray-400 font-semibold text-sm">No staff records found.</p></td></tr>`;
        } else {
            data.forEach(s => {
                const avatar = s.profile_photo ? '../uploads/profile/' + s.profile_photo : `https://ui-avatars.com/api/?name=${s.first_name}+${s.surname}&background=random`;
                const basicSalary = parseFloat(s.basic_salary).toLocaleString('en-US', {minimumFractionDigits: 2});
                const accNo = s.account_number || '<span class="text-red-400 italic">No Account</span>';
                const bank = s.bank_name || '<span class="text-red-400 hover:text-red-600 italic tooltip" title="Bank Name Note">No Bank</span>';
                const accName = s.account_name ? `<p class="text-[10px] text-gray-500 mt-1 uppercase tracking-widest">${s.account_name}</p>` : '';
                
                // Keep data object encoded in the button for easy editing
                const sJson = JSON.stringify(s).replace(/'/g, "&#39;");
                
                html += `
                <tr class="hover:bg-indigo-50/20 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="${avatar}" class="w-10 h-10 rounded-full object-cover shadow-sm bg-white shrink-0">
                            <div>
                                <h5 class="text-sm font-bold text-gray-800">${s.first_name} ${s.surname}</h5>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-[11px] font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded border border-gray-200">${s.user_id}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold text-indigo-600">₦${basicSalary}</span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-xs font-bold text-gray-700 whitespace-nowrap">${bank} &bull; ${accNo}</p>
                        ${accName}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick='window.editFinance(${sJson})' class="p-2 border border-gray-200 rounded-xl text-gray-400 hover:text-indigo-600 hover:border-indigo-200 hover:bg-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <i class="bx bx-edit text-lg"></i>
                        </button>
                    </td>
                </tr>
                `;
            });
        }
        $('#staffDirectoryBody').html(html);
    }

    $('#directorySearch').on('input', function() {
        const term = $(this).val().toLowerCase();
        const filtered = staffData.filter(s => 
            s.first_name.toLowerCase().includes(term) || 
            s.surname.toLowerCase().includes(term) || 
            s.user_id.toLowerCase().includes(term)
        );
        renderDirectory(filtered);
    });

    window.editFinance = function(staff) {
        $('#f_staff_id').val(staff.id);
        $('#f_basic_salary').val(staff.basic_salary);
        $('#f_bank_name').val(staff.bank_name);
        $('#f_account_number').val(staff.account_number);
        $('#f_account_name').val(staff.account_name);
        $('#financeModal').removeClass('hidden');
    }

    $('#financeForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#f_submitBtn');
        const ogHtml = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin text-lg"></i> Saving...').prop('disabled', true);

        $.post(BASE_URL + 'admin/auth/hr_api.php?action=update_staff_finance', $(this).serialize(), function(res) {
            btn.html(ogHtml).prop('disabled', false);
            if(res.status === 'success') {
                $('#financeModal').addClass('hidden');
                showAlert('success', res.message);
                loadDirectory();
            } else {
                showAlert('error', res.message);
            }
        });
    });

    // Init
    loadDirectory();
</script>
