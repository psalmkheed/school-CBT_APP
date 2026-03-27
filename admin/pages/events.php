<?php
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

$stmt = $conn->query("SELECT * FROM school_events ORDER BY start_date ASC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatDateRange($start, $end) {
    $s = new DateTime($start);
    $e = new DateTime($end);
    if ($s->format('Y-m-d') === $e->format('Y-m-d')) {
        return $s->format('M j, Y') . ' (' . $s->format('g:i A') . ' - ' . $e->format('g:i A') . ')';
    }
    return $s->format('M j, Y (g:i A)') . ' - ' . $e->format('M j, Y (g:i A)');
}
?>

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()"
                class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-indigo-700 hover:border-indigo-200 hover:bg-indigo-50 transition-all cursor-pointer"
                title="Go back" data-tippy-content="Back to Dashboard">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 shadow-sm border border-indigo-200">
                    <i class="bx bx-calendar-event text-indigo-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">School Event Calendar</h3>
                    <p class="text-sm text-gray-400 font-medium">Create and manage upcoming school events</p>
                </div>
            </div>
        </div>

        <button onclick="$('#composePanel').slideToggle();" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-indigo-200 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group whitespace-nowrap">
            <i class="bx bx-plus-circle text-lg group-hover:scale-110 transition-transform"></i> Add Event
        </button>
    </div>

    <!-- Create Event Form -->
    <div id="composePanel" class="hidden mb-8">
        <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-6 md:p-8">
            <h4 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="bx bx-calendar-plus text-indigo-500"></i> New Event</h4>
            <form id="eventForm" class="space-y-6" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Event Title</label>
                        <input type="text" name="title" required placeholder="e.g. PTF Meeting" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Visibility</label>
                        <select name="visibility" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-medium text-sm">
                            <option value="all">Every Portal (Staff, Parent, Student)</option>
                            <option value="students">Students Portal Only</option>
                            <option value="parents">Parents Portal Only</option>
                            <option value="staff">Staff Portal Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Start Date & Time</label>
                        <input type="datetime-local" name="start_date" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">End Date & Time</label>
                        <input type="datetime-local" name="end_date" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-medium text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Description</label>
                        <textarea name="description" rows="3" placeholder="Optional details about the event..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-medium text-sm resize-none"></textarea>
                    </div>
                    <div class="md:col-span-2 hidden">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Event Type</label>
                        <select name="type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all font-medium text-sm">
                            <option value="event">General Event</option>
                            <option value="exam">Examination</option>
                            <option value="holiday">Holiday</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" onclick="$('#composePanel').slideUp();" class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 transition-all font-bold text-sm">Cancel</button>
                    <button type="submit" id="submitBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                        <i class="bx bx-save text-lg"></i> Save Event
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Events Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
        <?php if(empty($events)): ?>
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                <div class="mb-4 inline-flex items-center justify-center size-16 rounded-full bg-gray-50 text-gray-400">
                    <i class="bx bx-calendar-x text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">No Events Scheduled</h3>
                <p class="text-sm text-gray-500 mt-2">Click Add Event to start building the school calendar.</p>
            </div>
        <?php else: ?>
            <?php foreach($events as $ev): 
                $now = new DateTime();
                $end = new DateTime($ev['end_date']);
                $is_past = $end < $now;
                
                $bg_color = $is_past ? 'bg-gray-50 border-gray-200' : 'bg-white border-indigo-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)]';
                $icon_color = $is_past ? 'text-gray-400 bg-gray-100' : 'text-indigo-500 bg-indigo-100';
            ?>
            <div class="rounded-3xl border relative group p-6 flex flex-col h-full <?= $bg_color ?>">
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex flex-col">
                        <span class="text-xs font-bold text-gray-400">Date & Time</span>
                        <span class="text-sm font-semibold text-gray-700">
                            <i class="bx bx-time mr-1"></i> <?= formatDateRange($ev['start_date'], $ev['end_date']) ?>
                        </span>
                    </span>
                    <?php if($is_past): ?>
                        <span class="text-[10px] uppercase font-semibold tracking-widest rounded-full bg-gray-200 text-gray-600 px-2 py-1">Past</span>
                    <?php else: ?>
                        <span class="text-[10px] uppercase font-semibold tracking-widest rounded-full bg-green-100 text-green-700 px-2 py-1">Upcoming</span>
                    <?php endif; ?>
                </div>

                <div class="flex gap-4">
                    <div class="size-10 rounded-xl <?= $icon_color ?> flex items-center justify-center shrink-0">
                        <i class="bx bx-party text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 mb-1 leading-tight line-clamp-2"><?= htmlspecialchars($ev['title']) ?></h4>
                        <p class="text-xs text-gray-500 font-semibold mb-3">Visible to: <?= ucfirst(htmlspecialchars($ev['visibility'])) ?></p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mt-2 mb-6 line-clamp-4 leading-relaxed font-medium">
                    <?= nl2br(htmlspecialchars($ev['description'])) ?>
                </p>

                <div class="mt-auto pt-4 border-t border-gray-100 flex justify-end relative z-10">
                    <button class="delete-event p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors" data-id="<?= $ev['id'] ?>" data-tippy-content="Delete Event">
                        <i class="bx bx-trash text-lg"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');

    $('#eventForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#submitBtn');
        let ogHtml = btn.html();
        btn.html('<i class="bx bxs-loader-dots bx-spin text-lg"></i> Saving...').attr('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/event_api.php?action=create',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if(res.status == 'success') {
                    showAlert('success', res.message);
                    setTimeout(() => loadPage(BASE_URL + 'admin/pages/events.php'), 1000);
                } else {
                    showAlert('error', res.message);
                    btn.html(ogHtml).attr('disabled', false);
                }
            },
            error: function() {
                showAlert('error', 'Network error occurred.');
                btn.html(ogHtml).attr('disabled', false);
            }
        });
    });

    $('.delete-event').on('click', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: "Delete Event?",
            text: "This action cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            confirmButtonText: "Yes, delete it"
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/auth/event_api.php?action=delete', { id: id }, function(res) {
                    if (res.status == 'success') {
                        showAlert('success', res.message);
                        setTimeout(() => loadPage(BASE_URL + 'admin/pages/events.php'), 1000);
                    } else {
                        showAlert('error', res.message);
                    }
                });
            }
        });
    });
</script>
