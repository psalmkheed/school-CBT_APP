<?php
require '../../connections/db.php';

// Fetch all classes
$class_stmt = $conn->prepare("SELECT * FROM class ORDER BY class ASC");
$class_stmt->execute();
$all_classes = $class_stmt->fetchAll(PDO::FETCH_OBJ);

// Fetch all staff
$staff_stmt = $conn->prepare("SELECT id, first_name, surname FROM users WHERE role = 'staff' ORDER BY first_name ASC");
$staff_stmt->execute();
$all_staff = $staff_stmt->fetchAll(PDO::FETCH_OBJ);

// Function to deduce educational level
function getEducationalLevel($className) {
    if (preg_match('/JSS|JUNIOR|BASIC\s*[789]/i', $className)) {
        return 'junior';
    } elseif (preg_match('/SS|SENIOR/i', $className)) {
        if (preg_match('/JSS/i', $className)) return 'junior';
        return 'senior';
    } else {
        return 'primary';
    }
}
?>

<div class="fadeIn w-full md:p-8 p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h3 class="text-2xl font-bold text-gray-800">Timetable Builder</h3>
            <p class="text-sm text-gray-500">Manage class schedules and teacher assignments.</p>
        </div>
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
            <select id="levelSelector" class="w-full md:w-48 bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="all">All Levels</option>
                <option value="primary">Primary</option>
                <option value="junior">Junior Secondary</option>
                <option value="senior">Senior Secondary</option>
            </select>
            <select id="classSelector" class="w-full md:w-64 bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                <option value="">-- Select Class --</option>
                <?php foreach ($all_classes as $cls): 
                    $lvl = getEducationalLevel($cls->class);
                ?>
                    <option value="<?= htmlspecialchars($cls->class) ?>" class="class-opt" data-level="<?= $lvl ?>">
                        <?= htmlspecialchars($cls->class) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button id="addSlotBtn" class="bg-teal-600 text-white px-5 py-2.5 rounded-xl hover:bg-teal-700 transition-all font-bold text-sm shadow-md flex items-center gap-2 hidden">
                <i class="bx bx-plus text-lg"></i> Add
            </button>
        </div>
    </div>

    <div id="timetableBlock" class="hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="timetableGrid">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Day</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Time</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Subject</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Teacher</th>
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="timetableBody">
                        <!-- Slots will be injected here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="emptyState" class="flex flex-col items-center justify-center p-12 py-20 bg-white rounded-2xl border border-dashed border-gray-200 text-center">
        <div class="w-20 h-20 rounded-full bg-teal-50 flex items-center justify-center mb-4">
            <i class="bx bx-table text-4xl text-teal-500"></i>
        </div>
        <h4 class="text-xl font-bold text-gray-700 mb-2">Select a Class</h4>
        <p class="text-sm text-gray-400 max-w-sm">Choose a class from the dropdown above to view or start building its weekly timetable.</p>
    </div>
</div>

<!-- Add/Edit Slot Modal -->
<div class="hidden fixed inset-0 bg-black/90 flex items-center justify-center p-2 z-[99999] backdrop-blur-md" id="slotModal">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden fadeIn">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center">
                    <i class="bx-time text-teal-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800" id="slotModalTitle">Add Timetable Slot</h3>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600 transition cursor-pointer" onclick="closeSlotModal()">
                <i class="bx-x text-2xl"></i>
            </button>
        </div>
        <form id="slotForm" class="p-6 flex flex-col gap-4">
            <input type="hidden" id="slot_id" name="id">
            <input type="hidden" id="slot_class" name="class">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Day</label>
                    <select name="day" id="slot_day" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Subject</label>
                    <input type="text" name="subject" id="slot_subject" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400" placeholder="e.g. Mathematics">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Start Time</label>
                    <input type="time" name="start_time" id="slot_start" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-500 uppercase">End Time</label>
                    <input type="time" name="end_time" id="slot_end" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-500 uppercase">Teacher</label>
                <select name="teacher_id" id="slot_teacher" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                    <option value="">-- Assign Teacher --</option>
                    <?php foreach ($all_staff as $staff): ?>
                        <option value="<?= $staff->id ?>"><?= htmlspecialchars($staff->first_name . ' ' . $staff->surname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" id="saveSlotBtn" class="w-full mt-2 flex items-center justify-center px-4 py-3 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-all cursor-pointer font-bold text-sm shadow-md">
                Save Slot
            </button>
        </form>
    </div>
</div>

<script>
    let currentClass = '';

    // Cache original options for true cross-browser hiding
    const classOptions = $('#classSelector option.class-opt').clone();

    $('#levelSelector').on('change', function() {
        const selectedLevel = $(this).val();
        const $classSelect = $('#classSelector');
        
        $classSelect.empty().append('<option value="">-- Select Class --</option>');
        $classSelect.val('');
        currentClass = '';
        
        classOptions.each(function() {
            if (selectedLevel === 'all' || $(this).data('level') === selectedLevel) {
                $classSelect.append($(this).clone());
            }
        });
        
        $classSelect.trigger('change');
    });

    $('#classSelector').on('change', function() {
        currentClass = $(this).val();
        if(currentClass) {
            $('#emptyState').addClass('hidden');
            $('#timetableBlock').removeClass('hidden');
            $('#addSlotBtn').removeClass('hidden');
            loadTimetable();
        } else {
            $('#emptyState').removeClass('hidden');
            $('#timetableBlock').addClass('hidden');
            $('#addSlotBtn').addClass('hidden');
        }
    });

    function loadTimetable() {
        $('#timetableBody').html(`<tr><td colspan="5" class="p-8 text-center"><i class="bx bx-loader-alt bx-spin text-2xl text-teal-500"></i></td></tr>`);
        $.get(BASE_URL + 'admin/auth/timetable_api.php?action=get&class=' + encodeURIComponent(currentClass), function(res) {
            if(res.status === 'success') {
                if(res.data.length === 0) {
                    $('#timetableBody').html(`<tr><td colspan="5" class="p-8 text-center text-gray-500 font-medium">No slots scheduled for this class yet.</td></tr>`);
                    return;
                }
                
                let html = '';
                const sorted = res.data; // Server can sort by day and then start_time
                sorted.forEach(slot => {
                    const timeRange = formatTime(slot.start_time) + ' - ' + formatTime(slot.end_time);
                    html += `
                    <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition group">
                        <td class="p-4"><span class="font-bold text-gray-700 bg-gray-100 px-3 py-1 rounded-lg text-xs">${slot.day}</span></td>
                        <td class="p-4 text-sm text-gray-600 font-medium whitespace-nowrap"><i class="bx bx-time text-gray-400 mr-1"></i> ${timeRange}</td>
                        <td class="p-4 text-sm font-bold text-gray-800">${slot.subject}</td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                    <i class="bx bx-user"></i>
                                </div>
                                <span class="text-sm text-gray-600">${slot.teacher_name}</span>
                            </div>
                        </td>
                        <td class="p-4 text-right whitespace-nowrap opacity-0 group-hover:opacity-100 transition">
                            <button onclick='editSlot(${JSON.stringify(slot)})' class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition inline-flex items-center justify-center cursor-pointer">
                                <i class="bx bx-edit text-lg"></i>
                            </button>
                            <button onclick='deleteSlot(${slot.id})' class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition inline-flex items-center justify-center cursor-pointer ml-1">
                                <i class="bx bx-trash text-lg"></i>
                            </button>
                        </td>
                    </tr>`;
                });
                $('#timetableBody').html(html);
            }
        });
    }

    function formatTime(time24) {
        let [h, m] = time24.split(':');
        let suffix = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${m} ${suffix}`;
    }

    $('#addSlotBtn').on('click', () => {
        $('#slotForm')[0].reset();
        $('#slot_id').val('');
        $('#slot_class').val(currentClass);
        $('#slotModalTitle').text('Add Schedule Slot');
        $('#slotModal').removeClass('hidden');
    });

    function closeSlotModal() {
        $('#slotModal').addClass('hidden');
    }

    function editSlot(slot) {
        $('#slot_id').val(slot.id);
        $('#slot_class').val(slot.class);
        $('#slot_day').val(slot.day);
        $('#slot_start').val(slot.start_time);
        $('#slot_end').val(slot.end_time);
        $('#slot_subject').val(slot.subject);
        $('#slot_teacher').val(slot.teacher_id);
        $('#slotModalTitle').text('Edit Schedule Slot');
        $('#slotModal').removeClass('hidden');
    }

    $('#slotForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveSlotBtn');
        const ogText = btn.text();
        btn.html('<i class="bx bx-loader-alt bx-spin"></i> Saving...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/timetable_api.php?action=save',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    window.showToast(res.message, 'success');
                    closeSlotModal();
                    loadTimetable();
                } else {
                    window.showToast(res.message, 'error');
                }
            },
            error: function() {
                window.showToast('Network error saving slot.', 'error');
            },
            complete: function() {
                btn.html(ogText).prop('disabled', false);
            }
        });
    });

    function deleteSlot(id) {
        Swal.fire({
            title: 'Delete this slot?',
            text: "This removes the subject from the class schedule.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/auth/timetable_api.php?action=delete', { id: id }, function(res) {
                    if(res.status === 'success') {
                        window.showToast('Slot deleted', 'success');
                        loadTimetable();
                    } else {
                        window.showToast(res.message, 'error');
                    }
                }, 'json');
            }
        });
    }

</script>
