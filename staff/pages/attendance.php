<?php
require __DIR__ . '/../../auth/check.php';
/** @var stdClass|false $user */
// Only staff can access
if ($user->role !== 'staff') {
    exit('Unauthorized');
}

$class = $_SESSION['class'] ?? '';
$teacher_id = $user->id;
$session_id = $_SESSION['active_session_id'] ?? 0;
$today = date('Y-m-d');
$selected_date = $_GET['date'] ?? $today;

// Fetch students in this class
$stmt = $conn->prepare("SELECT id, first_name, surname, user_id FROM users WHERE role = 'student' AND class = :class ORDER BY first_name ASC");
$stmt->execute([':class' => $class]);
$students = $stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch existing attendance for the selected date
$at_stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE class = :class AND attendance_date = :date AND session_id = :sess_id");
$at_stmt->execute([':class' => $class, ':date' => $selected_date, ':sess_id' => $session_id]);
$existing = $at_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [student_id => status]

?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-xl shadow-blue-200 flex items-center justify-center">
                <i class="bx bx-calendar-check text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Class Attendance</h1>
                <p class="text-sm text-gray-500 font-medium">Marking for <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-lg border border-blue-100"><?= htmlspecialchars($class) ?></span></p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-white p-2 rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50">
            <div class="flex items-center gap-2 px-3 border-r border-gray-100">
                <i class="bx bx-calendar text-blue-500 text-xl"></i>
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Select Date</label>
            </div>
            <input type="date" id="attendanceDatePicker" value="<?= $selected_date ?>" max="<?= $today ?>"
                class="border-0 focus:ring-0 text-sm font-bold text-gray-700 cursor-pointer bg-transparent py-2">
            <button onclick="$('#attendanceDatePicker').val('<?= $today ?>').trigger('change')" 
                class="px-4 py-2 bg-gray-50 text-gray-500 rounded-xl text-[10px] font-semibold uppercase tracking-widest hover:bg-blue-50 hover:text-blue-600 transition-all cursor-pointer">
                Today
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-2xl shadow-gray-200/50 overflow-hidden">
        <form id="attendanceForm">
            <input type="hidden" name="attendance_date" value="<?= $selected_date ?>">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-8 py-6 text-[11px] font-semibold text-gray-400 uppercase tracking-[0.2em]">Student Information</th>
                            <th class="px-8 py-6 text-[11px] font-semibold text-gray-400 uppercase tracking-[0.2em] text-center">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): 
                                $status = $existing[$student->id] ?? 'none';
                            ?>
                                <tr class="hover:bg-gray-50/30 transition-all group">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="size-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-500 font-bold text-xs ring-4 ring-white shadow-sm group-hover:from-blue-100 group-hover:to-blue-200 group-hover:text-blue-600 transition-all">
                                                <?= strtoupper(substr($student->first_name, 0, 1) . substr($student->surname, 0, 1)) ?>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors">
                                                    <?= htmlspecialchars($student->first_name . ' ' . $student->surname) ?>
                                                </h4>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= $student->user_id ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center justify-center gap-3">
                                            <!-- Present -->
                                            <label class="relative cursor-pointer group/item">
                                                <input type="radio" name="status[<?= $student->id ?>]" value="present" 
                                                    <?= ($status === 'present' || $status === 'none') ? 'checked' : '' ?> class="hidden peer">
                                                <div class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gray-50/50 text-gray-400 border-2 border-transparent peer-checked:bg-green-50 peer-checked:text-green-600 peer-checked:border-green-200 peer-checked:shadow-lg peer-checked:shadow-green-100 transition-all duration-300">
                                                    <i class="bx bx-check-circle text-lg group-hover/item:scale-110 transition-transform"></i>
                                                    <span class="text-[10px] font-semibold uppercase tracking-widest">Present</span>
                                                </div>
                                            </label>

                                            <!-- Late -->
                                            <label class="relative cursor-pointer group/item">
                                                <input type="radio" name="status[<?= $student->id ?>]" value="late" 
                                                    <?= $status === 'late' ? 'checked' : '' ?> class="hidden peer">
                                                <div class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gray-50/50 text-gray-400 border-2 border-transparent peer-checked:bg-amber-50 peer-checked:text-amber-600 peer-checked:border-amber-200 peer-checked:shadow-lg peer-checked:shadow-amber-100 transition-all duration-300">
                                                    <i class="bx bx-clock-5 text-lg group-hover/item:scale-110 transition-transform"></i>
                                                    <span class="text-[10px] font-semibold uppercase tracking-widest">Late</span>
                                                </div>
                                            </label>

                                            <!-- Absent -->
                                            <label class="relative cursor-pointer group/item">
                                                <input type="radio" name="status[<?= $student->id ?>]" value="absent" 
                                                    <?= $status === 'absent' ? 'checked' : '' ?> class="hidden peer">
                                                <div class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gray-50/50 text-gray-400 border-2 border-transparent peer-checked:bg-red-50 peer-checked:text-red-600 peer-checked:border-red-200 peer-checked:shadow-lg peer-checked:shadow-red-100 transition-all duration-300">
                                                    <i class="bx bx-x-circle text-lg group-hover/item:scale-110 transition-transform"></i>
                                                    <span class="text-[10px] font-semibold uppercase tracking-widest">Absent</span>
                                                </div>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="px-8 py-32 text-center text-gray-400 italic">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="size-20 rounded-full bg-gray-50 flex items-center justify-center text-gray-200">
                                            <i class="bx bx-group text-5xl"></i>
                                        </div>
                                        <p class="text-sm font-bold tracking-tight">No students found in your class.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($students) > 0): ?>
                <div class="p-8 bg-gray-50/30 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="px-12 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-[1.25rem] font-semibold text-xs uppercase tracking-widest hover:shadow-2xl hover:shadow-blue-200 hover:-translate-y-1 active:translate-y-0 transition-all duration-300 cursor-pointer flex items-center gap-3">
                        <i class="bx bx-save text-xl"></i> Complete Attendance
                    </button>
                </div>
            <?php endif ?>
        </form>
    </div>
</div>

<script>
    $('#attendanceDatePicker').on('change', function() {
        const date = $(this).val();
        window.loadPage('pages/attendance.php?date=' + date);
    });

    $('#attendanceForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="bx bxs-loader-dots animate-spin text-lg"></i> Saving...');

        $.ajax({
            url: 'auth/attendanceAuth.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                try {
                    const data = typeof res === 'string' ? JSON.parse(res) : res;
                    if(data.status === 'success') {
                        Swal.fire({
                            title: 'Attendance Saved',
                            text: 'Class attendance has been recorded successfully.',
                            icon: 'success',
                            confirmButtonColor: '#2563eb'
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to save', 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'Invalid server response', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Connection failed', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
</script>
