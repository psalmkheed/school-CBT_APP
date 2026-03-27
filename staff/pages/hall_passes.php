<?php
require '../../auth/check.php';
/** @var stdClass|false $user */
if ($user->role !== 'staff') {
    exit('Unauthorized');
}

$session_id = $_SESSION['active_session_id'] ?? 0;

// Auto expire old passes before fetching
$conn->exec("UPDATE hall_passes SET status = 'expired' WHERE status = 'active' AND expires_at < CURRENT_TIMESTAMP");

// Fetch active passes issued by this teacher
$stmt = $conn->prepare("
    SELECT p.*, s.first_name as s_first, s.surname as s_last, s.class, u.first_name as i_first, u.surname as i_last 
    FROM hall_passes p 
    JOIN users s ON p.student_id = s.id 
    JOIN users u ON p.issued_by = u.id 
    WHERE p.issued_by = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user->id]);
$passes = $stmt->fetchAll(PDO::FETCH_OBJ);


// Fetch unique classes for this teacher
$class_stmt = $conn->prepare("
    SELECT DISTINCT c.id, c.class 
    FROM class c 
    WHERE c.teacher_id = :tid 
    ORDER BY c.class ASC
");
$class_stmt->execute([':tid' => $user->id]);
$assigned_classes = $class_stmt->fetchAll(PDO::FETCH_OBJ);
$assigned_class_names = array_map(function ($c) {
    return $c->class;
}, $assigned_classes);

if (empty($assigned_class_names))
    $assigned_class_names = ['__NONE__'];

$placeholders = implode(',', array_fill(0, count($assigned_class_names), '?'));

// Fetch all students in teacher's classes
$std_stmt = $conn->prepare("SELECT id, first_name, surname, class FROM users WHERE role = 'student' AND class IN ($placeholders) ORDER BY class, first_name");
$std_stmt->execute($assigned_class_names);
$students = $std_stmt->fetchAll(PDO::FETCH_OBJ);

?>
<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-xl shadow-rose-200 flex items-center justify-center">
                <i class="bx bx-badge-check text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Class Pass Manager</h1>
                <p class="text-sm text-gray-500 font-medium">Issue Temporary Passes to Your Students</p>
            </div>
        </div>
        <button onclick="$('#modalPass').removeClass('hidden')" class="bg-rose-600 text-white px-5 py-2.5 rounded-xl hover:bg-rose-700 transition flex items-center gap-2 font-bold text-sm shadow-md">
            <i class="bx bx-plus"></i> Issue New Pass
        </button>
    </div>

    <!-- Passes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($passes as $p): 
            $isActive = ($p->status === 'active');
            $statusColor = $isActive ? 'rose' : ($p->status === 'returned' ? 'emerald' : 'gray');
            $statusText = strtoupper($p->status);
        ?>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm transition-all group relative overflow-hidden">
                <?php if($isActive): ?>
                    <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-rose-100 to-rose-50 rounded-bl-full flex items-start justify-end p-3 animate-pulse">
                        <i class="bx bx-run text-rose-500 text-xl"></i>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <span class="px-2 py-1 rounded-md text-[10px] font-semibold tracking-widest bg-<?= $statusColor ?>-50 text-<?= $statusColor ?>-600 border border-<?= $statusColor ?>-100">
                        <?= $statusText ?>
                    </span>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 leading-tight"><?= htmlspecialchars($p->s_first . ' ' . $p->s_last) ?></h3>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1 mb-4">Class: <?= htmlspecialchars($p->class) ?></p>
                
                <div class="space-y-2 mb-6 border-l-2 border-<?= $statusColor ?>-400 pl-3">
                    <p class="text-sm text-gray-600"><strong>Destination:</strong> <?= htmlspecialchars($p->destination) ?></p>
                    <p class="text-sm text-gray-600"><strong>Reason:</strong> <?= htmlspecialchars($p->reason) ?></p>
                </div>

                <div class="border-t border-gray-50 pt-4 space-y-2">
                    <div class="flex justify-between items-center text-[10px] uppercase font-bold text-gray-400">
                        <span>Issued: <?= date('h:i A', strtotime($p->created_at)) ?></span>
                        <span>Expires: <?= date('h:i A', strtotime($p->expires_at)) ?></span>
                    </div>
                    <?php if($isActive): ?>
                        <button onclick="returnPass(<?= $p->id ?>)" class="w-full py-2 bg-<?= $statusColor ?>-50 text-<?= $statusColor ?>-600 font-bold text-xs uppercase tracking-widest rounded-xl hover:bg-<?= $statusColor ?>-100 transition-colors cursor-pointer">
                            Mark Returned
                        </button>
                    <?php else: ?>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 py-2 bg-gray-50 text-gray-400 text-center font-bold text-xs uppercase tracking-widest rounded-xl">
                                <?= $p->status === 'returned' ? 'Returned at ' . date('h:i A', strtotime($p->returned_at)) : 'Expired' ?>
                            </div>
                            <button onclick="deletePass(<?= $p->id ?>)" title="Delete Pass Record" class="px-3 py-2 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-colors cursor-pointer">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if(empty($passes)): ?>
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border-2 border-dashed border-gray-200">
                <i class="bx bx-badge-check text-4xl text-gray-300 mb-3 block"></i>
                <p class="text-gray-500 font-bold">No active passes issued by you</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 fade-in" id="modalPass">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden scale-in animate-slideUp">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-semibold text-gray-800 text-lg flex items-center gap-2"><i class="bx bx-plus-circle text-rose-500"></i> Issue New Pass</h3>
            <button class="text-gray-400 hover:text-gray-700 transition cursor-pointer" onclick="$('#modalPass').addClass('hidden')"><i class="bx bx-x text-2xl"></i></button>
        </div>
        <form id="formPass" class="p-6 space-y-5">
            <input type="hidden" name="action" value="issue">
            <div>
                <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">My Student</label>
                <select name="student_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-400 outline-none text-sm font-bold text-gray-700">
                    <option value="">-- Choose Student --</option>
                    <?php foreach($students as $s): ?>
                        <option value="<?= $s->id ?>"><?= htmlspecialchars($s->class) ?> - <?= htmlspecialchars($s->first_name . ' ' . $s->surname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Destination</label>
                <input type="text" name="destination" placeholder="e.g. Toilet, Library, Clinic" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-400 outline-none text-sm text-gray-700">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Reason</label>
                    <input type="text" name="reason" placeholder="e.g. Bathroom break" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-400 outline-none text-sm text-gray-700">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest block mb-2">Duration (Minutes)</label>
                    <input type="number" name="duration" min="5" max="60" value="10" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-400 outline-none text-sm font-bold text-gray-700">
                </div>
            </div>
            
            <button type="submit" class="w-full py-4 bg-rose-600 text-white rounded-xl font-semibold shadow-lg shadow-rose-100 hover:-translate-y-1 hover:shadow-xl hover:bg-rose-700 transition-all mt-6 uppercase tracking-widest text-[10px] cursor-pointer">Issue Pass</button>
        </form>
    </div>
</div>

<script>
    $('#formPass').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type=submit]');
        const og = btn.html();
        btn.html('<i class="bx bx-loader-alt bx-spin"></i>').prop('disabled', true);
        
        $.post(BASE_URL + 'staff/auth/pass_api.php', $(this).serialize(), function(res) {
            btn.html(og).prop('disabled', false);
            if(res.status === 'success') {
                window.showToast(res.message, 'success');
                $('#modalPass').addClass('hidden');
                setTimeout(() => window.loadPage('pages/hall_passes.php'), 500);
            } else {
                window.showToast(res.message, 'error');
            }
        }, 'json').fail(() => {
            window.showToast('Network error', 'error');
            btn.html(og).prop('disabled', false);
        });
    });

    function returnPass(id){
        Swal.fire({
            title: 'Mark as Returned?',
            text: "Confirm the student has returned to class.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Yes, returned'
        }).then(result => {
            if(result.isConfirmed) {
                $.post(BASE_URL + 'staff/auth/pass_api.php', { action: 'revoke', id: id }, function(res) {
                    if(res.status === 'success') {
                        window.showToast(res.message, 'success');
                        window.loadPage('pages/hall_passes.php');
                    } else {
                        window.showToast(res.message, 'error');
                    }
                }, 'json');
            }
        });
    }

    function deletePass(id){
        Swal.fire({
            title: 'Delete Pass Record?',
            text: "This will permanently remove this hall pass from your records.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it'
        }).then(result => {
            if(result.isConfirmed) {
                $.post(BASE_URL + 'staff/auth/pass_api.php', { action: 'delete', id: id }, function(res) {
                    if(res.status === 'success') {
                        window.showToast(res.message, 'success');
                        window.loadPage('pages/hall_passes.php');
                    } else {
                        window.showToast(res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>
