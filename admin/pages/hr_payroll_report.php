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
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-rose-700 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-rose-100 flex items-center justify-center shrink-0 shadow-sm border border-rose-200">
                    <i class="bx bx-pie-chart-alt-2 text-rose-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 tracking-tight">Payroll Report</h3>
                    <p class="text-sm text-gray-400 font-medium">Monthly staff expenditure breakdown & insights.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <select id="prrMonth" class="px-3 py-2 border border-gray-200 rounded-lg bg-white shadow-sm text-sm font-semibold text-gray-700 focus:outline-none">
                <?php for($m=1; $m<=12; ++$m): $mstr = str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= $mstr ?>" <?= $mstr == date('m') ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
            <select id="prrYear" class="px-3 py-2 border border-gray-200 rounded-lg bg-white shadow-sm text-sm font-semibold text-gray-700 focus:outline-none">
                <?php for($y=date('Y'); $y>=date('Y')-2; --$y): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button onclick="loadPayrollReport()" class="px-4 py-2 bg-gray-900 text-white rounded-lg font-bold text-sm shadow-md hover:bg-black transition-all cursor-pointer">Generate</button>
        </div>
    </div>

    <!-- Stats Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl p-6 shadow-xl relative overflow-hidden text-white">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <p class="text-xs uppercase tracking-widest font-bold text-gray-400 mb-1">Total Payroll Expenditure</p>
            <h3 class="text-3xl font-black font-mono tracking-tight" id="rpTotalPay">₦0.00</h3>
        </div>
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 relative">
            <i class="bx bx-check-circle absolute right-6 top-6 text-3xl text-emerald-100"></i>
            <p class="text-xs uppercase tracking-widest font-bold text-gray-400 mb-1">Cleared / Paid</p>
            <h3 class="text-2xl font-black text-gray-800 font-mono" id="rpTotalPaid">₦0.00</h3>
        </div>
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 relative">
            <i class="bx bx-time-five absolute right-6 top-6 text-3xl text-amber-100"></i>
            <p class="text-xs uppercase tracking-widest font-bold text-gray-400 mb-1">Pending Balance</p>
            <h3 class="text-2xl font-black text-amber-600 font-mono" id="rpTotalPending">₦0.00</h3>
        </div>
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 relative">
            <i class="bx bx-cut absolute right-6 top-6 text-3xl text-red-100"></i>
            <p class="text-xs uppercase tracking-widest font-bold text-gray-400 mb-1">Total Deductions</p>
            <h3 class="text-2xl font-black text-red-500 font-mono" id="rpTotalDeductions">₦0.00</h3>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-[2rem] p-6 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-10 overflow-hidden relative">
        <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest mb-4 flex items-center gap-2">
            <i class="bx bx-list-ul text-rose-500"></i> Detailed Ledger
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="prrTable">
                <thead>
                    <tr class="bg-gray-50/50 border-y border-gray-100">
                        <th class="px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-widest whitespace-nowrap">Staff Member</th>
                        <th class="px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right whitespace-nowrap">Final Pay</th>
                        <th class="px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right whitespace-nowrap">Bank Info</th>
                        <th class="px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody id="prrBody" class="divide-y divide-gray-50 hidden">
                    <!-- Loaded via Ajax -->
                </tbody>
            </table>
            
            <div id="prrLoader" class="flex flex-col items-center justify-center py-20 text-gray-400 hidden">
                <i class="bx bx-loader-alt bx-spin text-4xl mb-2 text-rose-600"></i>
                <p class="text-sm font-semibold">Analyzing Records...</p>
            </div>
            
            <div id="prrEmpty" class="hidden flex flex-col items-center justify-center py-20 text-gray-400">
                <div class="size-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i class="bx bx-receipt text-3xl text-gray-300"></i>
                </div>
                <h4 class="font-bold text-gray-700">No Records Found</h4>
                <p class="text-xs mt-1 text-center max-w-sm">There is no compiled payroll ledger for this month. Go to the Payroll tab to generate one.</p>
            </div>
        </div>
    </div>
</div>

<script>
    window.loadPayrollReport = function() {
        const m = $('#prrMonth').val();
        const y = $('#prrYear').val();

        $('#prrLoader').removeClass('hidden');
        $('#prrBody').addClass('hidden');
        $('#prrEmpty').addClass('hidden');
        
        $.get(BASE_URL + 'admin/auth/hr_api.php?action=get_payroll_records&month=' + m + '&year=' + y, function(res) {
            if(res.status === 'success') {
                processReport(res.data);
            }
            $('#prrLoader').addClass('hidden');
        });
    }

    function processReport(data) {
        if(data.length === 0) {
            $('#prrEmpty').removeClass('hidden');
            $('#rpTotalPay, #rpTotalPaid, #rpTotalPending, #rpTotalDeductions').text('₦0.00');
            return;
        }

        let totalPay = 0, totalPaid = 0, totalPending = 0, totalDeductions = 0;
        let html = '';

        data.forEach(s => {
            let net = parseFloat(s.net_salary);
            let ded = parseFloat(s.deductions);

            totalPay += net;
            totalDeductions += ded;

            if(s.status === 'Paid') {
                totalPaid += net;
            } else {
                totalPending += net;
            }

            const netFmt = net.toLocaleString('en-US', {minimumFractionDigits: 2});
            let badgeClass = s.status === 'Paid' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100';
            
            html += `
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-4 py-4">
                    <h5 class="text-sm font-bold text-gray-800 whitespace-nowrap">${s.first_name} ${s.surname}</h5>
                    <div class="flex flex-col mt-0.5 whitespace-nowrap">
                        <span class="text-[10px] font-mono text-gray-400">${s.user_id}</span>
                    </div>
                </td>
                <td class="px-4 py-4 text-right">
                    <span class="text-sm font-black text-gray-900">₦${netFmt}</span>
                </td>
                <td class="px-4 py-4 text-right">
                    <span class="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-1 rounded">${s.bank_name || 'No Bank'}</span>
                    <p class="text-[10px] font-mono text-gray-500 mt-1">${s.account_number || '-'}</p>
                    ${s.account_name ? `<p class="text-[9px] uppercase tracking-widest text-gray-400 mt-0.5">${s.account_name}</p>` : ''}
                </td>
                <td class="px-4 py-4 text-center">
                    <span class="px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-widest font-black ${badgeClass}">${s.status}</span>
                </td>
            </tr>
            `;
        });

        $('#prrBody').html(html).removeClass('hidden');

        $('#rpTotalPay').text('₦' + totalPay.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#rpTotalPaid').text('₦' + totalPaid.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#rpTotalPending').text('₦' + totalPending.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#rpTotalDeductions').text('₦' + totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2}));
    }

    // Init
    window.loadPayrollReport();
</script>
