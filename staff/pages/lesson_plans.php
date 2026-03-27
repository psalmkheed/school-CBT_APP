<?php
require __DIR__ . '/../../auth/check.php';

$teacher_id = $user->id;

// Fetch teacher's subjects & classes
$stmt = $conn->prepare("
    SELECT DISTINCT s.id as subject_id, s.subject, c.class as class_name 
    FROM teacher_assignments ta
    JOIN subjects s ON ta.subject_id = s.id
    JOIN class c ON ta.class_id = c.id
    WHERE ta.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_OBJ);

$active_session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch existing lesson plans
$stmt = $conn->prepare("
    SELECT lp.*, s.subject 
    FROM lesson_plans lp
    JOIN subjects s ON lp.subject_id = s.id
    WHERE lp.teacher_id = ? AND lp.session_id = ?
    ORDER BY lp.week_number DESC, lp.created_at DESC
");
$stmt->execute([$teacher_id, $active_session_id]);
$plans = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-indigo-600 text-white shadow-xl shadow-indigo-100 flex items-center justify-center">
                <i class="bx bx-book-bookmark text-4xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">Lesson Planning</h1>
                <p class="text-sm text-gray-400 font-medium italic">Organize your curriculum and track syllabus coverage</p>
            </div>
        </div>
        
        <button onclick="$('#uploadPlanModal').removeClass('hidden')" class="px-8 py-4 bg-indigo-600 text-white rounded-[1.25rem] font-semibold text-xs uppercase tracking-widest hover:shadow-2xl hover:shadow-indigo-200 hover:-translate-y-1 transition-all cursor-pointer flex items-center gap-3">
            <i class="bx bx-plus-circle text-xl"></i> Create New Plan
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Stats Summary -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-[2rem] p-8 border border-gray-100 shadow-xl shadow-gray-200/50">
                <h3 class="text-lg font-bold text-gray-800 mb-6">Syllabus Overview</h3>
                <div class="space-y-6">
                    <?php 
                    $processed = [];
                    foreach($assignments as $a): 
                        if(isset($processed[$a->subject_id.$a->class_name])) continue;
                        $processed[$a->subject_id.$a->class_name] = true;
                        
                        // Count approved weeks
                        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM lesson_plans WHERE subject_id = ? AND class_name = ? AND status = 'approved' AND teacher_id = ? AND session_id = ?");
                        $count_stmt->execute([$a->subject_id, $a->class_name, $teacher_id, $active_session_id]);
                        $approved_weeks = $count_stmt->fetchColumn();
                        $progress = min(($approved_weeks / 12) * 100, 100); // Assuming 12-week term
                    ?>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-gray-700 uppercase"><?= $a->subject ?> (<?= $a->class_name ?>)</span>
                                <span class="text-[10px] font-semibold text-indigo-600"><?= round($progress) ?>%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-500 h-full transition-all duration-500" style="width: <?= $progress ?>%"></div>
                            </div>
                            <p class="text-[9px] text-gray-400 font-bold mt-1.5 uppercase tracking-widest"><?= $approved_weeks ?>/12 Weeks Covered</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Guidelines -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-[2rem] p-8 text-white shadow-xl">
                <i class="bx bx-info-circle text-4xl mb-4 text-white/40"></i>
                <h4 class="text-lg font-bold mb-2">Admin Guidelines</h4>
                <ul class="text-xs space-y-3 font-medium text-white/80">
                    <li class="flex gap-2"><i class="bx bx-check-circle"></i> Submit plans 48h before the week starts.</li>
                    <li class="flex gap-2"><i class="bx bx-check-circle"></i> Include specific learning objectives.</li>
                    <li class="flex gap-2"><i class="bx bx-check-circle"></i> Attached PDFs/Docs are preferred.</li>
                </ul>
            </div>
        </div>

        <!-- Plans Table -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
                    <h3 class="text-lg font-bold text-gray-800">Recent Submissions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Subject / Week</th>
                                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Topic</th>
                                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (empty($plans)): ?>
                                <tr>
                                    <td colspan="4" class="px-8 py-20 text-center text-gray-400 italic">No lesson plans submitted yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($plans as $p): 
                                    $statusColor = $p->status === 'approved' ? 'emerald' : ($p->status === 'rejected' ? 'red' : 'amber');
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition-all group">
                                        <td class="px-8 py-6">
                                            <p class="text-sm font-semibold text-gray-800"><?= $p->subject ?></p>
                                            <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">Week <?= $p->week_number ?></p>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="text-xs font-bold text-gray-600 line-clamp-1"><?= $p->topic ?></p>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="px-3 py-1.5 bg-<?= $statusColor ?>-50 text-<?= $statusColor ?>-600 rounded-xl text-[9px] font-semibold uppercase tracking-widest border border-<?= $statusColor ?>-100">
                                                <?= $p->status ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-right flex justify-end gap-2">
                                            <button onclick="viewPlanDetails(<?= $p->id ?>)" class="p-2.5 bg-white border border-gray-100 rounded-xl text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition-all">
                                                <i class="bx bx-eye text-xl"></i>
                                            </button>
                                            <button onclick="deletePlan(<?= $p->id ?>)" class="p-2.5 bg-white border border-gray-100 rounded-xl text-gray-400 hover:text-red-600 hover:border-red-200 transition-all">
                                                <i class="bx bx-trash text-xl"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadPlanModal" class="hidden fixed inset-0 z-[1000] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="$(this).parent().addClass('hidden')"></div>
    <div class="relative bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">New Lesson Plan</h3>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Submission Form</p>
            </div>
            <button onclick="$('#uploadPlanModal').addClass('hidden')" class="size-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-red-500 transition-all"><i class="bx bx-x text-2xl"></i></button>
        </div>
        
        <form id="lessonPlanForm" class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">Subject & Class</label>
                    <select name="assignment" required class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500/20 transition-all cursor-pointer">
                        <?php foreach($assignments as $a): ?>
                            <option value="<?= $a->subject_id ?>|<?= $a->class_name ?>"><?= $a->subject ?> - <?= $a->class_name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">Week Number</label>
                    <input type="number" name="week_number" min="1" max="15" required class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">Main Topic</label>
                <input type="text" name="topic" required placeholder="e.g. Introduction to Calculus" class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500/20 transition-all">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">Core Content / Notes</label>
                <textarea name="content" rows="4" class="w-full bg-gray-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500/20 transition-all resize-none" placeholder="Paste your lesson summary here..."></textarea>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest ml-1">Attachment (Optional)</label>
                <input type="file" name="plan_file" class="w-full text-xs font-bold text-gray-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-semibold file:uppercase file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 transition-all cursor-pointer">
            </div>

            <button type="submit" class="w-full py-5 bg-indigo-600 text-white rounded-2xl font-semibold text-xs uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:-translate-y-1 transition-all">
                Submit for Approval
            </button>
        </form>
    </div>
</div>

<script>
$('#lessonPlanForm').on('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    
    $.ajax({
        url: BASE_URL + 'staff/auth/lesson_plan_api.php?action=create',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: 'Lesson plan submitted for review.',
                    icon: 'success',
                    confirmButtonColor: '#4f46e5'
                }).then(() => loadPage(BASE_URL + 'staff/pages/lesson_plans.php'));
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});

window.staffPlans = <?= json_encode($plans) ?>;

window.escapeHtml = function(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
};

window.viewPlanDetails = function(id) {
    const plan = window.staffPlans.find(p => p.id == id);
    if (!plan) return;

    let contentHtml = `
        <div style="text-align: left;">
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <span style="padding: 4px 12px; background: #eef2ff; color: #4f46e5; border-radius: 8px; font-weight: 800; font-size: 10px; text-transform: uppercase;">Week ${plan.week_number}</span>
                <span style="padding: 4px 12px; background: #f3f4f6; color: #4b5563; border-radius: 8px; font-weight: 800; font-size: 10px; text-transform: uppercase;">${escapeHtml(plan.subject)} - ${escapeHtml(plan.class_name)}</span>
            </div>

            <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Plan Content</div>
            <div style="max-height: 250px; overflow-y: auto; padding: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 20px; font-size: 14px; color: #374151; white-space: pre-wrap; font-family: system-ui, -apple-system, sans-serif;">${escapeHtml(plan.content) || '<span style="color:#9ca3af; font-style:italic;">No text content provided.</span>'}</div>
        </div>
    `;

    if (plan.file_path) {
        contentHtml += `
            <div style="text-align: left; margin-bottom: 20px;">
                <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Attachment</div>
                <a href="${BASE_URL}uploads/lesson_plans/${escapeHtml(plan.file_path)}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; background: #eef2ff; color: #4f46e5; border-radius: 10px; font-weight: 800; text-decoration: none; font-size: 12px; border: 1px solid #c7d2fe; transition: all 0.2s;">
                    <i class='bx bx-file' style="font-size: 16px;"></i> Download/View Attachment
                </a>
            </div>
        `;
    }

    if (plan.admin_feedback) {
        const statusColors = {
            'approved': 'background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;',
            'rejected': 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;',
            'pending': 'background: #fffbeb; color: #d97706; border: 1px solid #fde68a;'
        };

        contentHtml += `
            <div style="text-align: left; margin-top: 10px; padding-top: 20px; border-top: 2px dashed #f3f4f6;">
                <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Admin Feedback</div>
                <div style="padding: 16px; border-radius: 12px; font-size: 13px; font-weight: 500; white-space: pre-wrap; ${statusColors[plan.status]}">
                    ${escapeHtml(plan.admin_feedback)}
                </div>
            </div>
        `;
    }

    Swal.fire({
        title: escapeHtml(plan.topic),
        html: contentHtml,
        showConfirmButton: true,
        confirmButtonText: 'Close',
        confirmButtonColor: '#4f46e5',
        width: '600px',
        customClass: {
            title: 'text-lg font-semibold text-gray-800 tracking-tight',
            popup: '!rounded-[2rem]'
        }
    });
};

function deletePlan(id) {
    Swal.fire({
        title: 'Delete this Lesson Plan?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(BASE_URL + 'staff/auth/lesson_plan_api.php?action=delete', { id: id }, function(res) {
                if(res.status === 'success') {
                    loadPage(BASE_URL + 'staff/pages/lesson_plans.php');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>
