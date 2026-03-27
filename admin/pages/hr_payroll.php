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
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-amber-700 hover:border-amber-200 hover:bg-amber-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0 shadow-sm border border-amber-200">
                    <i class="bx bx-wallet-alt text-amber-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 tracking-tight">Staff Payroll</h3>
                    <p class="text-sm text-gray-400 font-medium">Generate, review and settle staff salary payments.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <select id="prMonth" class="px-3 py-2 border border-gray-200 rounded-lg bg-white shadow-sm text-sm font-semibold text-gray-700 focus:outline-none">
                <?php for($m=1; $m<=12; ++$m): $mstr = str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= $mstr ?>" <?= $mstr == date('m') ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
            <select id="prYear" class="px-3 py-2 border border-gray-200 rounded-lg bg-white shadow-sm text-sm font-semibold text-gray-700 focus:outline-none">
                <?php for($y=date('Y'); $y>=date('Y')-2; --$y): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button onclick="loadPayroll()" id="loadPrBtn" class="px-4 py-2 bg-gray-900 text-white rounded-lg font-bold text-sm shadow-md hover:bg-black transition-all cursor-pointer">View</button>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4 px-2">
        <div>
            <button id="generateBtn" onclick="generatePayroll()" class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-md shadow-amber-200 flex items-center gap-2 text-sm cursor-pointer">
                <i class="bx bx-refresh text-lg"></i> Compile Payroll
            </button>
        </div>
        <button id="markAllPaidBtn" onclick="markAllPaid()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-md shadow-emerald-200 flex items-center gap-2 text-sm cursor-pointer hidden">
            <i class="bx bx-check-double text-lg"></i> Mark All as Paid
        </button>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-[2rem] p-6 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-10 overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="payrollTable">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-4 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest whitespace-nowrap">Staff Member</th>
                        <th class="px-4 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right whitespace-nowrap">Base</th>
                        <th class="px-4 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right whitespace-nowrap text-emerald-600">Bonuses</th>
                        <th class="px-4 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right whitespace-nowrap text-red-600">Deductions</th>
                        <th class="px-4 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right whitespace-nowrap font-bold text-gray-900">Net Pay</th>
                        <th class="px-4 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center whitespace-nowrap">Status</th>
                        <th class="px-4 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody id="payrollBody" class="divide-y divide-gray-50 hidden">
                    <!-- Loaded via Ajax -->
                </tbody>
            </table>
            
            <div id="prLoader" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <i class="bx bx-loader-alt bx-spin text-4xl mb-2 text-amber-600"></i>
                <p class="text-sm font-semibold">Loading Extracted Data...</p>
            </div>
            
            <div id="prEmpty" class="hidden flex flex-col items-center justify-center py-20 text-gray-400">
                <div class="size-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i class="bx bx-receipt text-3xl text-gray-300"></i>
                </div>
                <h4 class="font-bold text-gray-700">No Payroll Found</h4>
                <p class="text-xs mt-1">Click "Compile Payroll" to build records for this month based on base salaries.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Salary Record -->
<div id="salaryModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-100 transform transition-all">
        <div class="px-6 py-5 flex items-center justify-between border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2"><i class="bx bx-edit text-amber-600"></i> Adjust Salary</h3>
            <button onclick="document.getElementById('salaryModal').classList.add('hidden')" class="w-8 h-8 rounded-full hover:bg-gray-200 flex items-center justify-center text-gray-500 transition"><i class="bx bx-x text-xl"></i></button>
        </div>
        <form id="salaryForm" class="p-6 space-y-4">
            <input type="hidden" name="record_id" id="s_record_id">
            
            <div class="bg-amber-50 rounded-xl p-3 border border-amber-100 flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-amber-800 uppercase tracking-widest">Base</span>
                <span class="text-sm font-bold text-amber-800" id="s_base_salary_display">₦0.00</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Bonuses/Allowances</label>
                    <input type="number" step="0.01" name="allowances" id="s_allowances" required class="w-full px-3 py-2.5 bg-gray-50 border border-emerald-200 rounded-xl text-sm font-semibold text-emerald-700 outline-none focus:ring-2 focus:ring-emerald-100 focus:bg-white transition-all font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1">Deductions (Lates/Misses)</label>
                    <input type="number" step="0.01" name="deductions" id="s_deductions" required class="w-full px-3 py-2.5 bg-gray-50 border border-red-200 rounded-xl text-sm font-semibold text-red-700 outline-none focus:ring-2 focus:ring-red-100 focus:bg-white transition-all font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-1 mt-2">Status</label>
                <select name="status" id="s_status" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
                    <option value="Pending">Pending</option>
                    <option value="Paid">Mark as Paid</option>
                </select>
            </div>
            
            <div class="pt-4 mt-6 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('salaryModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer">Cancel</button>
                <button type="submit" id="s_submitBtn" class="px-5 py-2.5 bg-gray-900 hover:bg-black text-white rounded-xl text-sm font-bold transition shadow-lg flex items-center gap-2 cursor-pointer">
                    <i class="bx bx-save text-lg"></i> Update Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.loadPayroll = function() {
        const m = $('#prMonth').val();
        const y = $('#prYear').val();

        $('#prLoader').removeClass('hidden');
        $('#payrollBody').addClass('hidden');
        $('#prEmpty').addClass('hidden');
        $('#markAllPaidBtn').addClass('hidden');
        
        $.get(BASE_URL + 'admin/auth/hr_api.php?action=get_payroll_records&month=' + m + '&year=' + y, function(res) {
            if(res.status === 'success') {
                renderPayroll(res.data);
            }
            $('#prLoader').addClass('hidden');
        });
    }

    function renderPayroll(data) {
        let html = '';
        if(data.length === 0) {
            $('#prEmpty').removeClass('hidden');
        } else {
            let allPaid = true;

            data.forEach(s => {
                if(s.status !== 'Paid') allPaid = false;

                const base = parseFloat(s.basic_salary).toLocaleString('en-US', {minimumFractionDigits: 2});
                const allow = parseFloat(s.allowances).toLocaleString('en-US', {minimumFractionDigits: 2});
                const ded = parseFloat(s.deductions).toLocaleString('en-US', {minimumFractionDigits: 2});
                const net = parseFloat(s.net_salary).toLocaleString('en-US', {minimumFractionDigits: 2});
                
                let badgeClass = s.status === 'Paid' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100';
                
                // For editing
                const sJson = JSON.stringify(s).replace(/'/g, "&#39;");

                html += `
                <tr class="hover:bg-gray-50/50 transition-colors group">
                    <td class="px-4 py-4">
                        <h5 class="text-sm font-bold text-gray-800 whitespace-nowrap">${s.first_name} ${s.surname}</h5>
                        <div class="flex flex-col mt-0.5 whitespace-nowrap">
                            <span class="text-[10px] font-mono text-gray-400">${s.user_id}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <span class="text-sm font-semibold text-gray-600">₦${base}</span>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">₦${allow}</span>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <span class="text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded">₦${ded}</span>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <span class="text-sm font-black text-gray-900">₦${net}</span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-widest font-black ${badgeClass}">${s.status}</span>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <button onclick='editPayroll(${sJson})' class="p-2 border border-gray-200 rounded-xl text-gray-400 hover:text-amber-600 hover:border-amber-200 hover:bg-white shadow-sm transition-all cursor-pointer">
                            <i class="bx bx-edit text-lg"></i>
                        </button>
                    </td>
                </tr>
                `;
            });
            $('#payrollBody').html(html).removeClass('hidden');
            
            if(!allPaid && data.length > 0) {
                $('#markAllPaidBtn').removeClass('hidden');
            }
        }
    }

    window.generatePayroll = function() {
        const m = $('#prMonth').val();
        const y = $('#prYear').val();
        
        Swal.fire({
            title: 'Compile Payroll?',
            text: 'This will pull all active staff members and their base salaries to instantiate this month\'s payroll ledgers.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, compile it'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#generateBtn');
                const og = btn.html();
                btn.html('<i class="bx bx-loader-alt bx-spin text-lg"></i> Compiling...').prop('disabled', true);
                
                $.post(BASE_URL + 'admin/auth/hr_api.php?action=generate_payroll', { month: m, year: y }, function(res) {
                    btn.html(og).prop('disabled', false);
                    if(res.status === 'success') {
                        showAlert('success', res.message);
                        loadPayroll();
                    } else {
                        showAlert('error', res.message);
                    }
                });
            }
        });
    }

    window.markAllPaid = function() {
        const m = $('#prMonth').val();
        const y = $('#prYear').val();
        
        Swal.fire({
            title: 'Clear Settled Payments',
            text: `Mark ALL pending salaries for ${m}/${y} as PAID? This will stamp them with today's date.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            confirmButtonText: 'Yes, Mark All Paid'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#markAllPaidBtn');
                const og = btn.html();
                btn.html('<i class="bx bx-loader-alt bx-spin px-2"></i>').prop('disabled', true);
                
                $.post(BASE_URL + 'admin/auth/hr_api.php?action=mark_all_paid', { month: m, year: y }, function(res) {
                    btn.html(og).prop('disabled', false);
                    if(res.status === 'success') {
                        showAlert('success', res.message);
                        loadPayroll();
                    } else {
                        showAlert('error', res.message);
                    }
                });
            }
        });
    }

    window.editPayroll = function(s) {
        $('#s_record_id').val(s.id);
        $('#s_base_salary_display').text('₦' + parseFloat(s.basic_salary).toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#s_allowances').val(s.allowances);
        $('#s_deductions').val(s.deductions);
        $('#s_status').val(s.status);
        
        $('#salaryModal').removeClass('hidden');
    }

    $('#salaryForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#s_submitBtn');
        const og = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin text-lg"></i> Saving...').prop('disabled', true);

        $.post(BASE_URL + 'admin/auth/hr_api.php?action=update_payroll_record', $(this).serialize(), function(res) {
            btn.html(og).prop('disabled', false);
            if(res.status === 'success') {
                $('#salaryModal').addClass('hidden');
                showAlert('success', res.message);
                loadPayroll();
            } else {
                showAlert('error', res.message);
            }
        });
    });

    // Init
    window.loadPayroll();
</script>
