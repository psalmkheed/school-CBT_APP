<?php
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'student') {
    exit('Unauthorized access.');
}

$class = $user->class;
$session_id = $_SESSION['active_session_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT t.*, CONCAT(u.first_name, ' ', u.surname) AS teacher_name
    FROM timetables t
    LEFT JOIN users u ON t.teacher_id = u.id
    WHERE t.class = :class AND t.session_id = :session_id
    ORDER BY 
        FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), 
        t.start_time ASC
");
$stmt->execute([':class' => $class, ':session_id' => $session_id]);
$timetable = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatTime($time24) {
    return date("g:i A", strtotime($time24));
}
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div class="flex items-center gap-5 w-full">
            <div class="size-16 rounded-3xl bg-indigo-600 text-white shadow-xl flex items-center justify-center">
                <i class="bx bx-calendar text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Class Timetable</h1>
                <p class="text-sm text-gray-500 font-medium">Your weekly schedule for <?= htmlspecialchars($class) ?></p>
            </div>
        </div>
        <select id="daySelector" class="w-full md:w-[150px] bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-400 shadow-sm">
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="All">All Days</option>
        </select>
    </div>

    <?php if (empty($timetable)): ?>
        <div class="flex flex-col items-center justify-center p-12 py-20 bg-white rounded-[2rem] border border-dashed border-gray-200 text-center">
            <div class="w-20 h-20 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                <i class="bx bx-calendar-x text-4xl text-indigo-500"></i>
            </div>
            <h4 class="text-xl font-bold text-gray-700 mb-2">No Timetable Available</h4>
            <p class="text-sm text-gray-400 max-w-sm">Your class timetable has not been set up yet. Check back later.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="p-6 text-xs font-semibold text-gray-400 uppercase tracking-widest">Day</th>
                            <th class="p-6 text-xs font-semibold text-gray-400 uppercase tracking-widest">Time</th>
                            <th class="p-6 text-xs font-semibold text-gray-400 uppercase tracking-widest">Subject</th>
                            <th class="p-6 text-xs font-semibold text-gray-400 uppercase tracking-widest">Teacher</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($timetable as $slot): ?>
                            <tr class="hover:bg-gray-50/30 transition-all group timetable-row" data-day="<?= htmlspecialchars($slot['day']) ?>">
                                <td class="p-6">
                                    <span class="text-sm font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100">
                                        <?= htmlspecialchars($slot['day']) ?>
                                    </span>
                                </td>
                                <td class="p-6 whitespace-nowrap">
                                    <div class="flex items-center gap-2 text-sm font-bold text-gray-600">
                                        <i class="bx bx-time text-gray-400 text-lg"></i>
                                        <?= formatTime($slot['start_time']) ?> - <?= formatTime($slot['end_time']) ?>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($slot['subject']) ?></p>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center gap-2">
                                        <div class="size-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                                            <i class="bx bx-user"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-600 truncate"><?= htmlspecialchars($slot['teacher_name']) ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    $('#daySelector').on('change', function() {
        const selectedDay = $(this).val();
        if (selectedDay === 'All') {
            $('.timetable-row').fadeIn(200);
        } else {
            $('.timetable-row').hide();
            $('.timetable-row[data-day="' + selectedDay + '"]').fadeIn(200);
        }
    });

    const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });
    const validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    const defaultDay = validDays.includes(today) ? today : 'Monday';
    $('#daySelector').val(defaultDay).trigger('change');
</script>
