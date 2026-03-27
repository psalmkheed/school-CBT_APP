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
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-700 hover:border-emerald-200 hover:bg-emerald-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0 shadow-sm border border-emerald-200">
                    <i class="bx bx-calendar-check text-emerald-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 tracking-tight">Staff Attendance</h3>
                    <p class="text-sm text-gray-400 font-medium">Record daily presence and absences for employees.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="date" id="attDate" value="<?= date('Y-m-d') ?>" class="px-4 py-2 border border-gray-200 rounded-xl bg-white shadow-sm text-sm font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-200">
            <button onclick="loadAttendance()" class="px-4 py-2 bg-gray-900 text-white rounded-xl font-bold text-sm shadow-md hover:bg-black transition-all">Load</button>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] p-6 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-10">
        <form id="attendanceForm">
            <input type="hidden" id="formDate" name="attendance_date" value="<?= date('Y-m-d') ?>">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="attTable">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Staff Member</th>
                            <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest w-48 text-center">Status</th>
                            <th class="px-6 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="attBody" class="divide-y divide-gray-50 hidden">
                        <!-- Loaded via Ajax -->
                    </tbody>
                </table>
                
                <div id="attLoader" class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <i class="bx bx-loader-alt bx-spin text-4xl mb-2 text-emerald-600"></i>
                    <p class="text-sm font-semibold">Loading Directory...</p>
                </div>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-50 flex justify-end hidden" id="attActions">
                <button type="submit" id="saveBtn" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition shadow-lg shadow-emerald-200 flex items-center gap-2">
                    <i class="bx bx-save text-lg"></i> Save Attendance
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function loadAttendance() {
        const date = $('#attDate').val();
        if(!date) return;

        $('#formDate').val(date);
        $('#attLoader').removeClass('hidden');
        $('#attBody, #attActions').addClass('hidden');
        
        $.get(BASE_URL + 'admin/auth/hr_api.php?action=get_attendance&date=' + date, function(res) {
            if(res.status === 'success') {
                renderAttendance(res.data);
            }
            $('#attLoader').addClass('hidden');
            $('#attBody, #attActions').removeClass('hidden');
        });
    }

    function renderAttendance(data) {
        let html = '';
        if(data.length === 0) {
            html = `<tr><td colspan="3" class="px-6 py-10 text-center"><p class="text-gray-400 font-semibold text-sm">No active staff found.</p></td></tr>`;
        } else {
            data.forEach(s => {
                const isPresent = s.status === 'Present' ? 'checked' : '';
                const isAbsent = s.status === 'Absent' ? 'checked' : '';
                const isLate = s.status === 'Late' ? 'checked' : '';
                const isHalf = s.status === 'Half Day' ? 'checked' : '';
                
                html += `
                <tr class="hover:bg-emerald-50/20 transition-colors">
                    <td class="px-6 py-4">
                        <h5 class="text-sm font-bold text-gray-800">${s.first_name} ${s.surname}</h5>
                        <p class="text-[10px] font-mono text-gray-400 tracking-widest uppercase mt-0.5">${s.user_id}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-3">
                            <label class="flex flex-col items-center gap-1 cursor-pointer group">
                                <input type="radio" name="attendance[${s.staff_id}][status]" value="Present" class="text-emerald-500 focus:ring-emerald-500 w-4 h-4" ${isPresent}>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest group-hover:text-emerald-600">PRS</span>
                            </label>
                            <label class="flex flex-col items-center gap-1 cursor-pointer group">
                                <input type="radio" name="attendance[${s.staff_id}][status]" value="Absent" class="text-red-500 focus:ring-red-500 w-4 h-4" ${isAbsent}>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest group-hover:text-red-600">ABS</span>
                            </label>
                            <label class="flex flex-col items-center gap-1 cursor-pointer group">
                                <input type="radio" name="attendance[${s.staff_id}][status]" value="Late" class="text-amber-500 focus:ring-amber-500 w-4 h-4" ${isLate}>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest group-hover:text-amber-600">LAT</span>
                            </label>
                            <label class="flex flex-col items-center gap-1 cursor-pointer group">
                                <input type="radio" name="attendance[${s.staff_id}][status]" value="Half Day" class="text-blue-500 focus:ring-blue-500 w-4 h-4" ${isHalf}>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest group-hover:text-blue-600">HLF</span>
                            </label>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <input type="text" name="attendance[${s.staff_id}][remarks]" value="${s.remarks || ''}" placeholder="Optional reason..." class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-lg text-xs focus:ring-2 focus:ring-emerald-100 focus:bg-white transition-colors">
                    </td>
                </tr>
                `;
            });
        }
        $('#attBody').html(html);
    }

    $('#attendanceForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveBtn');
        const ogHtml = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin text-lg"></i> Saving...').prop('disabled', true);

        $.post(BASE_URL + 'admin/auth/hr_api.php?action=save_attendance', $(this).serialize(), function(res) {
            btn.html(ogHtml).prop('disabled', false);
            if(res.status === 'success') {
                showAlert('success', res.message);
            } else {
                showAlert('error', res.message);
            }
        }).fail(() => {
            btn.html(ogHtml).prop('disabled', false);
            showAlert('error', 'Network error.');
        });
    });

    // Init
    loadAttendance();
</script>
