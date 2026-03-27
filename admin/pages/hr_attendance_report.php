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
                    <i class="bx bxs-bar-chart text-indigo-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 tracking-tight">Attendance Report</h3>
                    <p class="text-sm text-gray-400 font-medium">Monthly staff attendance overview and trends.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <select id="reportMonth" class="px-4 py-2 border border-gray-200 rounded-xl bg-white shadow-sm text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <?php for($m=1; $m<=12; ++$m): $mstr = str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= $mstr ?>" <?= $mstr == date('m') ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
            <select id="reportYear" class="px-4 py-2 border border-gray-200 rounded-xl bg-white shadow-sm text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <?php for($y=date('Y'); $y>=date('Y')-5; --$y): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button onclick="loadReport()" class="px-4 py-2 bg-gray-900 text-white rounded-xl font-bold text-sm shadow-md hover:bg-black transition-all">Generate</button>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] p-6 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="repTable">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Staff Member</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center text-emerald-600">Present</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center text-red-600">Absent</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center text-amber-600">Late</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center text-blue-600">Half Day</th>
                        <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Score</th>
                    </tr>
                </thead>
                <tbody id="repBody" class="divide-y divide-gray-50 hidden">
                    <!-- Loaded via Ajax -->
                </tbody>
            </table>
            
            <div id="repLoader" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <i class="bx bx-loader-alt bx-spin text-4xl mb-2 text-indigo-600"></i>
                <p class="text-sm font-semibold">Compiling Report...</p>
            </div>
        </div>
    </div>
</div>

<script>
    function loadReport() {
        const m = $('#reportMonth').val();
        const y = $('#reportYear').val();

        $('#repLoader').removeClass('hidden');
        $('#repBody').addClass('hidden');
        
        $.get(BASE_URL + 'admin/auth/hr_api.php?action=get_attendance_report&month=' + m + '&year=' + y, function(res) {
            if(res.status === 'success') {
                renderReport(res.data);
            }
            $('#repLoader').addClass('hidden');
            $('#repBody').removeClass('hidden');
        });
    }

    function renderReport(data) {
        let html = '';
        if(data.length === 0) {
            html = `<tr><td colspan="6" class="px-6 py-10 text-center"><p class="text-gray-400 font-semibold text-sm">No data available.</p></td></tr>`;
        } else {
            data.forEach(s => {
                const total = parseInt(s.total_days_logged) || 1; // avoid divide by zero for score
                const present = parseInt(s.present_days) || 0;
                let score = Math.round((present / total) * 100);
                if (parseInt(s.total_days_logged) === 0) score = 0; // if zero logs, 0% score

                let bgClass = "bg-emerald-50 text-emerald-600";
                if(score < 50) bgClass = "bg-red-50 text-red-600";
                else if(score < 80) bgClass = "bg-amber-50 text-amber-600";

                html += `
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <h5 class="text-sm font-bold text-gray-800">${s.first_name} ${s.surname}</h5>
                        <p class="text-[10px] font-mono text-gray-400 tracking-widest uppercase mt-0.5">${s.user_id}</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-bold text-gray-700">${present}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-bold text-gray-700">${s.absent_days || 0}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-bold text-gray-700">${s.late_days || 0}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-bold text-gray-700">${s.half_days || 0}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-lg text-xs font-bold ${bgClass}">${score}%</span>
                    </td>
                </tr>
                `;
            });
        }
        $('#repBody').html(html);
    }

    // Init
    loadReport();
</script>
