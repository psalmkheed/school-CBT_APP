<?php
require '../../auth/check.php';

if (!in_array($user->role, ['super', 'admin'])) {
    exit('Unauthorized');
}

$session_id = $_SESSION['active_session_id'] ?? 0;

// Fetch all logs for the active session
$stmt = $conn->prepare("
    SELECT b.*, s.first_name as s_first, s.surname as s_last, s.class, u.first_name as t_first, u.surname as t_last 
    FROM behavior_logs b 
    JOIN users s ON b.student_id = s.id 
    JOIN users u ON b.logged_by = u.id 
    WHERE b.session_id = :session_id
    ORDER BY b.created_at DESC
");
$stmt->execute([':session_id' => $session_id]);
$logs = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch all students using distinct inner join if needed, or simple query
$std_stmt = $conn->prepare("SELECT id, first_name, surname, class FROM users WHERE role = 'student' ORDER BY class, first_name");
$std_stmt->execute();
$students = $std_stmt->fetchAll(PDO::FETCH_OBJ);

?>
<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-fuchsia-500 to-purple-600 text-white shadow-xl shadow-fuchsia-200 flex items-center justify-center">
                <i class="bx bx-street-view text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Behavior & Discipline Log</h1>
                <p class="text-sm text-gray-500 font-medium">Track Commendations and Infractions</p>
            </div>
        </div>
        <button onclick="$('#modalBehavior').removeClass('hidden')" class="bg-fuchsia-600 text-white px-5 py-2.5 rounded-xl hover:bg-fuchsia-700 transition flex items-center gap-2 font-bold text-sm shadow-md">
            <i class="bx bx-plus"></i> Log Incident
        </button>
    </div>

    <!-- Logs List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php foreach($logs as $b): 
            $is_bad = ($b->type === 'infraction');
            $bg = $is_bad ? 'rose' : 'emerald';
            $icon = $is_bad ? 'bx-error-circle' : 'bxs-star';
        ?>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg transition-all group flex gap-4">
                <div class="size-12 rounded-full bg-<?= $bg ?>-50 text-<?= $bg ?>-500 flex items-center justify-center text-xl shrink-0"><i class="bx <?= $icon ?>"></i></div>
                <div class="flex-1 text-sm text-gray-600">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 leading-tight"><?= htmlspecialchars($b->s_first . ' ' . $b->s_last) ?></h3>
                            <p class="text-[10px] text-fuchsia-500 font-bold uppercase tracking-widest mt-1">Class: <?= htmlspecialchars($b->class) ?></p>
                        </div>
                        <span class="px-2 py-1 bg-gray-50 text-gray-500 border border-gray-100 rounded-lg text-[10px] uppercase font-bold whitespace-nowrap">
                            <?= date('M d, y', strtotime($b->created_at)) ?>
                        </span>
                    </div>
                    <p class="mb-4 italic">"<?= htmlspecialchars($b->description) ?>"</p>
                    <div class="flex items-center justify-between border-t border-gray-50 pt-3">
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Logged by: <?= htmlspecialchars($b->t_first . ' ' . $b->t_last) ?></span>
                        <button onclick="deleteLog(<?= $b->id ?>)" class="text-red-400 hover:text-red-600 transition p-1"><i class="bx bx-trash text-lg"></i></button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($logs)): ?>
            <div class="col-span-full py-16 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                <i class="bx bx-street-view text-4xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500 font-bold">No behaviors logged yet</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 fade-in" id="modalBehavior">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden scale-in animate-slideUp">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2"><i class="bx bx-plus-circle text-fuchsia-500"></i> New Behavior Log</h3>
            <button class="text-gray-400 hover:text-gray-700 transition" onclick="$('#modalBehavior').addClass('hidden')"><i class="bx bx-x text-2xl"></i></button>
        </div>
        <form id="formBehavior" class="p-6 space-y-5">
            <input type="hidden" name="action" value="log_incident">
            <div>
                <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Student</label>
                <select name="student_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-fuchsia-400 outline-none text-sm font-bold text-gray-700">
                    <option value="">-- Choose Student --</option>
                    <?php foreach($students as $s): ?>
                        <option value="<?= $s->id ?>"><?= htmlspecialchars($s->class) ?> - <?= htmlspecialchars($s->first_name . ' ' . $s->surname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Incident Type</label>
                <select name="type" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-fuchsia-400 outline-none text-sm font-bold text-gray-700">
                    <option value="infraction">Infraction (Misbehavior)</option>
                    <option value="commendation">Commendation (Good Behavior)</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Incident Details</label>
                <textarea name="description" rows="3" placeholder="Describe what happened..." required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-fuchsia-400 outline-none text-sm text-gray-700"></textarea>
            </div>
            
            <button type="submit" class="w-full py-4 bg-fuchsia-600 text-white rounded-xl font-semibold shadow-lg shadow-fuchsia-100 hover:-translate-y-1 hover:shadow-xl hover:bg-fuchsia-700 transition-all mt-6 uppercase tracking-widest text-[10px]">Save Log</button>
        </form>
    </div>
</div>

<script>
    $('#formBehavior').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type=submit]');
        const og = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin"></i>').prop('disabled', true);
        
        $.post(BASE_URL + 'admin/auth/behavior_api.php', $(this).serialize(), function(res) {
            btn.html(og).prop('disabled', false);
            if(res.status === 'success') {
                window.showToast(res.message, 'success');
                $('#modalBehavior').addClass('hidden');
                setTimeout(() => window.loadPage('pages/behavior.php'), 500);
            } else {
                window.showToast(res.message, 'error');
            }
        }, 'json').fail(() => {
            window.showToast('Network error', 'error');
            btn.html(og).prop('disabled', false);
        });
    });

    function deleteLog(id){
        Swal.fire({
            title: 'Delete Log?',
            text: "This cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it'
        }).then(result => {
            if(result.isConfirmed) {
                $.post(BASE_URL + 'admin/auth/behavior_api.php', { action: 'delete', id: id }, function(res) {
                    if(res.status === 'success') {
                        window.showToast(res.message, 'success');
                        window.loadPage('pages/behavior.php');
                    } else {
                        window.showToast(res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>
