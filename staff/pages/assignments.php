<?php
require '../../connections/db.php';
require '../../auth/check.php';
/** @var stdClass|false $user */
if ($user->role !== 'staff') {
    exit('Unauthorized');
}

$teacher_id = $user->id;

// Fetch unique subjects/classes taught by this teacher from teacher_assignments or class
$classes_stmt = $conn->prepare("SELECT class FROM class WHERE teacher_id = ?");
$classes_stmt->execute([$teacher_id]);
$classes = $classes_stmt->fetchAll(PDO::FETCH_COLUMN);

$active_session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch Assignments
$ass_stmt = $conn->prepare("
    SELECT a.*, 
        (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) as sub_count 
    FROM assignments a 
    WHERE a.teacher_id = ? AND a.session_id = ?
    ORDER BY a.created_at DESC
");
$ass_stmt->execute([$teacher_id, $active_session_id]);
$assignments = $ass_stmt->fetchAll(PDO::FETCH_OBJ);

?>
<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="flex items-center gap-5">
            <div
                class="size-16 rounded-3xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white shadow-xl shadow-indigo-200 flex items-center justify-center">
                <i class="bx bx-list-ul text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">CBA Manager</h1>
                <p class="text-sm text-gray-500 font-medium">Computer-Based Assignments</p>
            </div>
        </div>
        <button id="btnNewAssignment"
            class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl hover:bg-indigo-700 transition flex items-center gap-2 font-bold text-sm shadow-md">
            <i class="bx bx-plus"></i> New Assignment
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="size-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xl"><i
                    class="bx bx-list-ul"></i></div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Total Posted</p>
                <p class="text-2xl font-semibold text-gray-800"><?= count($assignments) ?></p>
            </div>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php foreach ($assignments as $a): ?>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg transition-all group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($a->title) ?></h3>
                        <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-widest mt-1">Class:
                            <?= htmlspecialchars($a->class) ?>
                        </p>
                    </div>
                    <span
                        class="px-3 py-1 bg-gray-50 text-gray-500 border border-gray-100 rounded-lg text-xs font-bold whitespace-nowrap">
                        <i class="bx bx-time"></i> Due: <?= date('M d, H:i', strtotime($a->due_date)) ?>
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-6 line-clamp-2"><?= htmlspecialchars($a->description) ?></p>
                <div class="flex items-center justify-between pt-4 border-t border-gray-50 mt-auto">
                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400 cursor-pointer hover:text-indigo-600 transition-colors"
                        onclick="viewSubmissions(<?= $a->id ?>, '<?= htmlspecialchars(addslashes($a->title)) ?>', '<?= htmlspecialchars($a->class) ?>')">
                        <i class="bx bx-check-square text-lg"></i> <?= $a->sub_count ?> Submissions
                    </div>
                    <?php if ($a->attachment): ?>
                        <a href="../uploads/assignments/<?= htmlspecialchars($a->attachment) ?>" target="_blank"
                        class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest hover:underline flex items-center gap-1">
                        <i class="bx bx-paperclip"></i> Attachment
                    </a>
                    <?php endif; ?>
                    <div class="flex gap-2">
                        <button onclick="deleteAssignment(<?= $a->id ?>)"
                            class="px-3 py-2 bg-red-50 text-red-500 hover:text-white hover:bg-red-500 rounded-xl transition cursor-pointer">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($assignments)): ?>
            <div class="col-span-full py-16 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                <i class="bx bx-list-ul text-4xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500 font-bold">No assignments posted yet</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: New Assignment -->
<div class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 fade-in"
    id="modalAssignment">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden scale-in flex flex-col max-h-[90vh]">
        <div class="p-5 md:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
            <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2"><i
                    class="bx bx-plus-circle text-indigo-500"></i> New Assignment</h3>
            <button class="text-gray-400 hover:text-gray-700 transition"
                onclick="$('#modalAssignment').addClass('hidden')"><i class="bx bx-x text-2xl"></i></button>
        </div>
        <div class="overflow-y-auto p-5 md:p-6">
            <form id="formAssignment">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="md:col-span-2">
                        <label
                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-1.5">Assignment
                            Title</label>
                        <input type="text" name="title" required
                            class="w-full px-4 py-2.5 border border-gray-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none text-sm font-bold text-gray-700 transition-all" placeholder="Subject name">
                    </div>
                    <div class="md:col-span-1">
                        <label
                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-1.5">Target
                            Class</label>
                        <select name="class" required
                            class="w-full px-4 py-2.5 border border-gray-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none text-sm font-bold text-gray-700 transition-all">
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                            </select>
                            </div>
                    <div class="md:col-span-2">
                        <label
                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-1.5">Attach
                            File <span class="text-gray-400 font-normal">(Optional, max 5MB)</span></label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.png"
                            class="w-full px-4 py-1.5 bg-gray-50 border border-gray-200 rounded-xl outline-none text-sm text-gray-600 file:mr-4 file:py-1.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:uppercase file:tracking-wider file:font-bold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 transition-all cursor-pointer">
                    </div>

                    <div class="md:col-span-1">
                        <label
                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-1.5">Due
                            Date & Time</label>
                        <input type="datetime-local" name="due_date" required
                            class="w-full px-4 py-2.5 border border-gray-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none text-sm font-bold text-gray-700 transition-all">
                    </div>

                    <div class="md:col-span-3">
                        <label
                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-1.5">Instructions
                            / Description</label>
                        <textarea name="description" rows="3" required
                            class="w-full px-4 py-2.5 border border-gray-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-400 outline-none text-sm text-gray-700 transition-all"></textarea>
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-100 hover:-translate-y-1 hover:shadow-xl hover:bg-indigo-700 transition-all mt-6 uppercase tracking-widest text-xs flex items-center justify-center gap-2">
                    <i class="bx bx-paper-plane text-lg text-indigo-200"></i> Publish Assignment
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Submissions -->
<div class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 fade-in"
    id="modalSubmissions">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden scale-in flex flex-col max-h-[90vh]">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
            <div>
                <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2"><i
                        class="bx bx-check-double text-indigo-500"></i> <span id="subsTitle">Submissions</span></h3>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1" id="subsClass">Class</p>
            </div>
            <button class="text-gray-400 hover:text-gray-700 transition cursor-pointer"
                onclick="$('#modalSubmissions').addClass('hidden')"><i class="bx bx-x text-2xl"></i></button>
        </div>

        <div class="flex-1 overflow-y-auto p-0 bg-gray-50/50" id="subsContainer">
            <!-- Dynamic Submissions Content -->
        </div>
    </div>
</div>

<script>
    $('#btnNewAssignment').on('click', () => $('#modalAssignment').removeClass('hidden'));

    $('#formAssignment').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('button[type=submit]');
        const og = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin"></i>').prop('disabled', true);

        const formData = new FormData(this);

        $.ajax({
            url: '../staff/auth/assignment_api.php?action=create',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                btn.html(og).prop('disabled', false);
                if (res.status === 'success') {
                    window.showToast(res.message, "success");
                    $('#modalAssignment').addClass('hidden');
                    setTimeout(() => window.loadPage('pages/assignments.php'), 500);
                } else {
                    window.showToast(res.message, "error");
                }
            },
            error: function () {
                btn.html(og).prop('disabled', false);
                window.showToast("Network Error", "error");
            }
        });
    });

    function deleteAssignment(id) {
        Swal.fire({
            title: 'Delete Assignment?',
            text: "This will remove the assignment and all student submissions.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it'
        }).then(result => {
            if (result.isConfirmed) {
                $.post('../staff/auth/assignment_api.php?action=delete', { id: id }, function (res) {
                    if (res.status === 'success') {
                        window.showToast(res.message, 'success');
                        window.loadPage('pages/assignments.php');
                    } else {
                        window.showToast(res.message, 'error');
                    }
                }, 'json');
            }
        });
    }

    function viewSubmissions(id, title, cls) {
        $('#subsTitle').text(title);
        $('#subsClass').text('Class: ' + cls);
        $('#subsContainer').html('<div class="p-16 text-center"><i class="bx bx-loader-alt bx-spin text-4xl text-indigo-400"></i></div>');
        $('#modalSubmissions').removeClass('hidden');

        $.get('../staff/auth/assignment_api.php', { action: 'submissions', id: id }, function (res) {
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    $('#subsContainer').html(`
                        <div class="py-16 text-center bg-white m-6 rounded-3xl border border-dashed border-gray-200">
                            <i class="bx bx-shield-x text-4xl text-gray-300 mb-3 block"></i>
                            <p class="text-gray-500 font-bold">No submissions yet.</p>
                        </div>
                    `);
                    return;
                }

                let html = '<div class="divide-y divide-gray-100">';
                res.data.forEach(s => {
                    let scoreBadge = s.grade !== null
                        ? `<span class="px-3 py-1 bg-green-50 text-green-600 border border-green-100 rounded-lg text-xs font-semibold">${s.grade} / 100</span>`
                        : `<span class="px-3 py-1 bg-orange-50 text-orange-600 border border-orange-100 rounded-lg text-xs font-semibold">Not Graded</span>`;

                    let attachHtml = s.attachment
                        ? `<a href="../uploads/assignments/${encodeURIComponent(s.attachment)}" target="_blank" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:underline flex items-center gap-1 w-max"><i class="bx bx-paperclip"></i> View Attached Work</a>`
                        : '';

                    html += `
                        <div class="p-6 bg-white hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="font-semibold text-gray-800">${s.first_name} ${s.surname}</h4>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Submitted: ${new Date(s.submitted_at).toLocaleString()}</p>
                                </div>
                                ${scoreBadge}
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 mb-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap font-medium">${s.text_answer || '<i class="text-gray-400">No text answer provided.</i>'}</p>
                            </div>
                            
                            <div class="flex items-center justify-between gap-4">
                                <div>${attachHtml}</div>
                                <div class="flex items-center gap-2">
                                    <input type="number" id="grade_${s.id}" placeholder="Score /100" class="w-24 px-3 py-2 border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-400 outline-none" max="100" min="0" value="${s.grade !== null ? s.grade : ''}">
                                    <button onclick="saveGrade(${s.id}, ${id})" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] uppercase tracking-widest rounded-xl transition shadow-md whitespace-nowrap">Save Grade</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $('#subsContainer').html(html);
            } else {
                $('#subsContainer').html('<div class="p-6 text-red-500 font-bold">' + res.message + '</div>');
            }
        }, 'json');
    }

    window.saveGrade = function (submissionId, assignmentId) {
        const score = $(`#grade_${submissionId}`).val();
        if (score === '') {
            window.showToast("Enter a valid score", "error");
            return;
        }

        $.post('../staff/auth/assignment_api.php?action=grade', {
            submission_id: submissionId,
            grade: score,
            assignment_id: assignmentId
        }, function (res) {
            if (res.status === 'success') {
                window.showToast("Grade saved!", "success");
                viewSubmissions(assignmentId, $('#subsTitle').text(), $('#subsClass').text().replace('Class: ', '')); // refresh
            } else {
                window.showToast(res.message, "error");
            }
        }, 'json').fail(() => window.showToast("Network Error", "error"));
    };
</script>