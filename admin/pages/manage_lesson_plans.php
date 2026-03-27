<?php
require __DIR__ . '/../../auth/check.php';

// Only admins can access
if (!in_array($user->role, ['super', 'admin'])) {
    exit('Unauthorized');
}

$active_session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch all pending plans
$stmt = $conn->prepare("
    SELECT lp.*, u.first_name, u.surname, s.subject 
    FROM lesson_plans lp
    JOIN users u ON lp.teacher_id = u.id
    JOIN subjects s ON lp.subject_id = s.id
    WHERE lp.status = 'pending' AND lp.session_id = ?
    ORDER BY lp.created_at ASC
");
$stmt->execute([$active_session_id]);
$pending = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch recent history
$stmt = $conn->prepare("
    SELECT lp.*, u.first_name, u.surname, s.subject 
    FROM lesson_plans lp
    JOIN users u ON lp.teacher_id = u.id
    JOIN subjects s ON lp.subject_id = s.id
    WHERE lp.status != 'pending' AND lp.session_id = ?
    ORDER BY lp.created_at DESC
    LIMIT 10
");
$stmt->execute([$active_session_id]);
$history = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="fadeIn w-full md:p-8 p-4 bg-gray-50/30">
    <div class="flex items-center gap-5 mb-10">
        <div class="size-16 rounded-3xl bg-indigo-600 text-white shadow-xl shadow-indigo-100 flex items-center justify-center">
            <i class="bx bx-check-shield text-4xl"></i>
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">Academic Oversight</h1>
            <p class="text-sm text-gray-400 font-medium italic">Review and approve teacher curriculum plans</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8">
        <!-- Pending Review -->
        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl overflow-hidden">
            <div class="p-8 border-b border-gray-50 bg-gray-50/30 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">Pending Approval</h3>
                <span class="px-4 py-1.5 bg-indigo-100 text-indigo-600 rounded-full text-[10px] font-semibold uppercase tracking-widest active-pulse">
                    <?= count($pending) ?> Submissions
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Teacher</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Subject / Class</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Topic / Week</th>
                            <th class="px-8 py-5 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Review</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($pending)): ?>
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center text-gray-400 italic">Excellent! All lesson plans have been reviewed.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending as $p): ?>
                                <tr class="hover:bg-indigo-50/30 transition-all">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <div class="size-10 rounded-full bg-gray-100 flex items-center justify-center font-bold text-xs text-gray-500">
                                                <?= $p->first_name[0] ?>
                                            </div>
                                            <span class="text-sm font-bold text-gray-800"><?= $p->first_name . ' ' . $p->surname ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-semibold text-gray-700 uppercase"><?= $p->subject ?></p>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mt-0.5"><?= $p->class_name ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-bold text-gray-600 line-clamp-1"><?= $p->topic ?></p>
                                        <p class="text-[10px] font-semibold text-indigo-500 uppercase tracking-widest mt-0.5">Week <?= $p->week_number ?></p>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                            <button onclick="reviewPlan(<?= $p->id ?>)" class="px-5 py-2.5 bg-gray-900 text-white rounded-xl text-[10px] font-semibold uppercase tracking-widest hover:bg-black transition-all cursor-pointer shadow-md">Review Plan</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History -->
        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl overflow-hidden opacity-80">
            <div class="p-8 border-b border-gray-50 bg-gray-50/30">
                <h3 class="text-lg font-bold text-gray-800">Review History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($history as $h): ?>
                            <tr class="hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-4">
                                    <span class="text-xs font-bold text-gray-700"><?= $h->first_name ?></span>
                                </td>
                                <td class="px-8 py-4">
                                    <span class="text-xs font-bold text-gray-500"><?= $h->subject ?></span>
                                </td>
                                <td class="px-8 py-4">
                                    <span class="px-2 py-1 bg-<?= $h->status === 'approved' ? 'emerald' : 'red' ?>-100 text-<?= $h->status === 'approved' ? 'emerald' : 'red' ?>-600 rounded-lg text-[8px] font-semibold uppercase tracking-widest">
                                        <?= $h->status ?>
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <span class="text-[10px] text-gray-400 font-bold"><?= date('M j, Y', strtotime($h->created_at)) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
window.pendingPlans = <?= json_encode($pending) ?>;

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

window.reviewPlan = function(id) {
    const plan = window.pendingPlans.find(p => p.id == id);
    if (!plan) return;

    let contentHtml = `
        <div style="text-align: left;">
            <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Plan Content</div>
            <div style="max-height: 400px; overflow-y: auto; padding: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 20px; font-size: 14px; color: #374151; white-space: pre-wrap; font-family: system-ui, -apple-system, sans-serif;">${escapeHtml(plan.content) || '<span style="color:#9ca3af; font-style:italic;">No text content provided.</span>'}</div>
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

    Swal.fire({
        title: 'Review: ' + plan.topic,
        html: contentHtml,
        input: 'textarea',
        inputPlaceholder: 'Add feedback for the teacher (optional)...',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: '<i class="bx bx-check-circle"></i> Approve',
        denyButtonText: '<i class="bx bx-x-circle"></i> Reject',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981',
        denyButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        width: '700px',
        customClass: {
            title: 'text-lg font-semibold text-gray-800 tracking-tight',
            input: 'w-full !p-4 !bg-gray-50 !border !border-gray-200 !rounded-xl !focus:ring-2 !focus:ring-indigo-400 !text-sm !resize-none',
            popup: '!rounded-[2rem]'
        }
    }).then((result) => {
        if (result.isConfirmed || result.isDenied) {
            let action = result.isConfirmed ? 'approve' : 'reject';
            let feedback = result.value || '';
            
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we update the status.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: BASE_URL + 'staff/auth/lesson_plan_api.php?action=' + action,
                type: 'POST',
                data: { id: id, feedback: feedback },
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({
                            title: 'Status Updated!',
                            text: 'Lesson plan has been ' + action + 'd successfully.',
                            icon: 'success',
                            confirmButtonColor: '#4f46e5'
                        }).then(() => loadPage(BASE_URL + 'admin/pages/manage_lesson_plans.php'));
                    } else {
                        Swal.fire('Error', res.message || 'Operation failed', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Network Error', 'error');
                }
            });
        }
    });
}
</script>
