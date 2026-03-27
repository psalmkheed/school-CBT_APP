<?php
require '../../connections/db.php';

// Fetch classes
$class_stmt = $conn->query("SELECT * FROM class ORDER BY class ASC");
$classes = $class_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch sessions
$session_stmt = $conn->query("SELECT DISTINCT session FROM sch_session ORDER BY session DESC");
$sessions = $session_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch terms
$term_stmt = $conn->query("SELECT DISTINCT term FROM sch_session ORDER BY term ASC");
$terms = $term_stmt->fetchAll(PDO::FETCH_OBJ);

// Default list (current active session/term)
$active_sess = $_SESSION['active_session'] ?? '';
$active_trm = $_SESSION['active_term'] ?? '';
?>

<div class="fadeIn w-full md:p-8 p-4">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-100">
                <i class="bx bx-file text-3xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">Report Card Center</h1>
                <p class="text-sm text-gray-400 font-medium">Automated PDF generation and performance monitoring</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
             <div class="bg-white p-1 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-2">
                <select id="filterClass" class="bg-transparent border-none text-xs font-bold text-gray-500 px-4 py-2 focus:ring-0">
                    <option value="">All Classes</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c->class ?>"><?= $c->class ?></option>
                    <?php endforeach ?>
                </select>
                <div class="w-px h-6 bg-gray-100"></div>
                <select id="filterSession" class="bg-transparent border-none text-xs font-bold text-gray-500 px-4 py-2 focus:ring-0">
                    <?php foreach($sessions as $s): ?>
                        <option value="<?= $s->session ?>" <?= $s->session == $active_sess ? 'selected' : '' ?>><?= $s->session ?></option>
                    <?php endforeach ?>
                </select>
                <div class="w-px h-6 bg-gray-100"></div>
                <select id="filterTerm" class="bg-transparent border-none text-xs font-bold text-gray-500 px-4 py-2 focus:ring-0">
                    <?php foreach($terms as $t): ?>
                        <option value="<?= $t->term ?>" <?= $t->term == $active_trm ? 'selected' : '' ?>><?= $t->term ?></option>
                    <?php endforeach ?>
                </select>
             </div>
             <button id="btnFetchStudents" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 cursor-pointer flex items-center gap-2">
                <i class="bx bx-refresh text-lg"></i> Sync Data
             </button>
        </div>
    </div>

    <!-- Student List Card -->
    <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl overflow-hidden transition-all duration-300">
        <div class="p-8 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Class Roll Call</h3>
            <div class="relative w-64">
                <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                <input type="text" id="studentSearch" placeholder="Search by name or ID..." class="w-full pl-11 pr-4 py-2.5 bg-gray-50/50 border-none rounded-2xl text-xs focus:ring-2 focus:ring-indigo-400 transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left" id="studentTable">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-8 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Student Information</th>
                        <th class="px-8 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Class / Group</th>
                        <th class="px-8 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Subjects Taken</th>
                        <th class="px-8 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Avg Score</th>
                        <th class="px-8 py-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentReportList" class="divide-y divide-gray-50">
                    <!-- Loaded via AJAX -->
                     <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center gap-4">
                                <i class="bx bx-loader-alt animate-spin text-4xl text-indigo-400"></i>
                                <p class="text-sm font-bold text-gray-400">Loading student result...</p>
                            </div>
                        </td>
                     </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manage Results Modal -->
<div id="resultsModal" class="hidden fixed inset-0 z-[1000] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="$(this).parent().addClass('hidden')"></div>
    <div class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">Exam Performance</h3>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1 student-name-display"></p>
            </div>
            <button onclick="$('#resultsModal').addClass('hidden')" class="size-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 transition-all cursor-pointer"><i class="bx bx-x text-2xl"></i></button>
        </div>
        
        <div class="p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-[9px] font-semibold text-gray-400 uppercase tracking-widest">Exam</th>
                            <th class="px-6 py-4 text-[9px] font-semibold text-gray-400 uppercase tracking-widest text-center">Score</th>
                            <th class="px-6 py-4 text-[9px] font-semibold text-gray-400 uppercase tracking-widest text-right">Reset</th>
                        </tr>
                    </thead>
                    <tbody id="studentResultsBody" class="divide-y divide-gray-50">
                        <!-- Results injected here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    function loadStudents() {
        const cls = $('#filterClass').val();
        const sess = $('#filterSession').val();
        const term = $('#filterTerm').val();

        $('#studentReportList').html(`
            <tr>
                <td colspan="5" class="px-8 py-20 text-center">
                    <div class="flex flex-col items-center gap-4">
                        <i class="bx bx-loader-alt animate-spin text-4xl text-indigo-400"></i>
                        <p class="text-sm font-bold text-gray-400">Synchronizing student records...</p>
                    </div>
                </td>
            </tr>
        `);

        $.ajax({
            url: 'auth/report_api.php?action=list_students',
            type: 'POST',
            data: { class: cls, session: sess, term: term },
            success: function(res) {
                if(res.status === 'success') {
                    let html = '';
                    if(res.data.length === 0) {
                        html = '<tr><td colspan="5" class="px-8 py-20 text-center text-gray-400 font-medium italic">No students found matching the criteria.</td></tr>';
                    } else {
                        res.data.forEach(s => {
                            let avgVal = parseFloat(s.avg_percentage);
                            if (isNaN(avgVal)) avgVal = 0;
                            const avgColor = avgVal >= 70 ? 'text-emerald-600' : (avgVal >= 50 ? 'text-orange-500' : 'text-red-500');
                            
                            html += `
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center shrink-0 group-hover:bg-indigo-50 transition-colors">
                                                <i class="bx bx-user text-xl text-gray-400 group-hover:text-indigo-500"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-800">${s.first_name} ${s.surname}</p>
                                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">${s.user_id}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-sm font-bold text-gray-600">${s.class}</td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="px-3 py-1 bg-gray-100 rounded-lg text-xs font-semibold text-gray-500">${s.subjects_count}</span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="text-sm font-semibold ${avgColor}">${Math.round(avgVal)}%</span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button onclick="manageResults(${s.id}, '${s.first_name} ${s.surname}')" 
                                                class="px-5 py-2 bg-red-50 text-red-600 rounded-xl text-xs font-bold tracking-wider hover:bg-red-600 hover:text-white transition-all duration-300 flex items-center gap-2">
                                                <i class="bx bx-reset"></i> Reset Exam
                                            </button>
                                            <button onclick="window.open('../auth/generate_report_card.php?student_id=${s.id}&session=${sess}&term=${term}', '_blank')" 
                                                class="px-5 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-bold tracking-wider hover:bg-indigo-600 hover:text-white transition-all duration-300 flex items-center gap-2">
                                                <i class="bx bx-download"></i> Report Card
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#studentReportList').html(html);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to fetch student data', 'error');
            }
        });
    }

    $('#btnFetchStudents').on('click', loadStudents);
    $('#filterClass, #filterSession, #filterTerm').on('change', loadStudents);

    // Initial load
    loadStudents();

    // Search logic
    $('#studentSearch').on('keyup', function() {
        const val = $(this).val().toLowerCase();
        $('#studentReportList tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(val) > -1);
        });
    });

    window.manageResults = function(studentId, name) {
        $('.student-name-display').text(name + ' - Managing All Script Attempts');
        $('#resultsModal').removeClass('hidden');
        $('#studentResultsBody').html('<tr><td colspan="3" class="px-6 py-10 text-center"><i class="bx bx-loader-alt animate-spin text-2xl text-indigo-400"></i></td></tr>');

        $.post('auth/report_api.php?action=get_student_results', {
            student_id: studentId,
            session: $('#filterSession').val(),
            term: $('#filterTerm').val()
        }, function(res) {
            if(res.status === 'success') {
                let html = '';
                if(res.data.length === 0) {
                    html = '<tr><td colspan="3" class="px-6 py-10 text-center text-gray-400 italic">No exams records found for this period.</td></tr>';
                } else {
                    res.data.forEach(r => {
                        html += `
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-700">${r.subject}</p>
                                    <p class="text-[9px] font-semibold text-indigo-400 uppercase tracking-widest">${r.exam_type}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-semibold text-gray-800">${r.score} / ${r.total_questions}</span>
                                    <p class="text-[9px] font-bold text-gray-400">${Math.round(r.percentage)}%</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="confirmReset(${r.id}, ${studentId}, '${name}')" class="p-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all cursor-pointer">
                                        <i class="bx bx-trash text-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#studentResultsBody').html(html);
            }
        });
    };

    window.confirmReset = function(resultId, studentId, name) {
        Swal.fire({
            title: 'Reset Exam Attempt?',
            text: "This will permanently delete this student's exam score, allowing them to retake it immediately. This action is logged.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Reset Now'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('auth/report_api.php?action=delete_result', { result_id: resultId }, function(res) {
                    if(res.status === 'success') {
                        showToast('Exam attempt reset successfully', 'success');
                        manageResults(studentId, name); // reload list
                        loadStudents(); // reload main list to update counts
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            }
        });
    };
});
</script>
