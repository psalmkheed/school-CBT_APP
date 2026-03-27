<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

$stmt = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $weeks = floor($diff->d / 7);
        $days = $diff->d - ($weeks * 7);

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        $values = array(
            'y' => $diff->y,
            'm' => $diff->m,
            'w' => $weeks,
            'd' => $days,
            'h' => $diff->h,
            'i' => $diff->i,
            's' => $diff->s,
        );

        foreach ($string as $k => &$v) {
            if ($values[$k]) {
                $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

if (!function_exists('getTimeAgo')) {
    function getTimeAgo($timeString) {
        return time_elapsed_string($timeString);
    }
}
?>

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()"
                class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-red-700 hover:border-red-200 hover:bg-red-50 transition-all cursor-pointer"
                title="Go back" data-tippy-content="Back to Dashboard">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-red-100 flex items-center justify-center shrink-0 shadow-sm border border-red-200">
                    <i class="bx bx-bell text-red-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">System Announcements</h3>
                    <p class="text-sm text-gray-400 font-medium">Create and manage global flash messages</p>
                </div>
            </div>
        </div>

        <button onclick="$('#composePanel').slideToggle();" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-red-200 font-bold text-sm cursor-pointer flex gap-2 items-center justify-center group whitespace-nowrap">
            <i class="bx bx-plus-circle text-lg group-hover:scale-110 transition-transform"></i> Create Announcement
        </button>
    </div>

    <!-- Create Announcement Form -->
    <div id="composePanel" class="hidden mb-8">
        <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-6 md:p-8">
            <h4 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="bx bx-edit text-red-500"></i> Compose Announcement</h4>
            <form id="announcementForm" class="space-y-6" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Title / Subject</label>
                        <input type="text" name="title" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-100 focus:border-red-400 transition-all font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Recipient Category</label>
                        <select name="recipient" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-100 focus:border-red-400 transition-all font-medium text-sm">
                            <option value="all">Everyone (All Students & Staff)</option>
                            <option value="students">Students Only</option>
                            <option value="staff">Staff Only</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Message Body</label>
                        <textarea name="message" rows="3" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-100 focus:border-red-400 transition-all font-medium text-sm resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Notice Type</label>
                        <select name="type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-100 focus:border-red-400 transition-all font-medium text-sm">
                            <option value="info">Info (Blue Background)</option>
                            <option value="warning">Warning (Orange Background)</option>
                            <option value="success">Success (Green Background)</option>
                            <option value="danger">Danger / Urgent (Red Background)</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" onclick="$('#composePanel').slideUp();" class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 transition-all font-bold text-sm">Cancel</button>
                    <button type="submit" id="submitBtn" class="bg-red-600 hover:bg-red-700 text-white px-8 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-red-200 transition-all flex items-center gap-2">
                        <i class="bx bx-send text-lg"></i> Publish
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Active/History Announcements Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
        <?php if(empty($announcements)): ?>
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                <div class="mb-4 inline-flex items-center justify-center size-16 rounded-full bg-gray-50 text-gray-400">
                    <i class="bx bx-bell-slash text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">No Announcements</h3>
                <p class="text-sm text-gray-500 mt-2">Publish an announcement to notify students or staff across the portal.</p>
            </div>
        <?php else: ?>
            <?php foreach($announcements as $a): 
                $bg_color = 'bg-blue-50 border-blue-100 text-blue-800';
                $icon_color = 'text-blue-500 bg-blue-100';
                $icon = 'bx-info-circle';
                
                if($a['type'] == 'warning') {
                    $bg_color = 'bg-orange-50 border-orange-100 text-orange-800';
                    $icon_color = 'text-orange-500 bg-orange-100';
                    $icon = 'bx-alert-triangle';
                } elseif($a['type'] == 'danger') {
                    $bg_color = 'bg-red-50 border-red-100 text-red-800';
                    $icon_color = 'text-red-500 bg-red-100';
                    $icon = 'bx-shield-circle';
                } elseif($a['type'] == 'success') {
                    $bg_color = 'bg-emerald-50 border-emerald-100 text-emerald-800';
                    $icon_color = 'text-emerald-500 bg-emerald-100';
                    $icon = 'bxs-check-circle';
                }
            ?>
            <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden relative group p-6 flex flex-col h-full <?= $a['status'] == 'inactive' ? 'opacity-60 saturate-50' : '' ?>">
                
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex py-1 px-3 text-[10px] uppercase font-semibold tracking-widest rounded-full <?= $bg_color ?>">
                        <?= htmlspecialchars($a['type']) ?>
                    </span>
                    <span class="text-xs font-bold text-gray-400 relative z-10">
                        <i class="bx bx-time mr-1"></i> <?= getTimeAgo($a['created_at']) ?>
                    </span>
                </div>

                <div class="flex gap-4">
                    <div class="size-10 rounded-xl <?= $icon_color ?> flex items-center justify-center shrink-0">
                        <i class="bx <?= $icon ?> text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 mb-1 leading-tight line-clamp-2"><?= htmlspecialchars($a['title']) ?></h4>
                        <p class="text-xs text-gray-500 font-semibold mb-3">To: <?= ucfirst(htmlspecialchars($a['recipient'])) ?></p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mt-2 mb-6 line-clamp-4 leading-relaxed font-medium">
                    <?= nl2br(htmlspecialchars($a['message'])) ?>
                </p>

                <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between relative z-10 transition-opacity">
                    <label class="flex items-center cursor-pointer gap-2">
                        <div class="relative">
                            <input type="checkbox" class="sr-only toggle-status" data-id="<?= $a['id'] ?>" <?= $a['status'] == 'active' ? 'checked' : '' ?>>
                            <div class="block bg-gray-200 w-10 h-6 rounded-full transition-colors peer-checked:bg-green-500"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4 shadow"></div>
                        </div>
                        <span class="text-xs font-bold text-gray-500 select-none">Active</span>
                    </label>

                    <button class="delete-announcement p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors" data-id="<?= $a['id'] ?>" data-tippy-content="Delete permanently">
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

    $('#announcementForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#submitBtn');
        let ogHtml = btn.html();
        btn.html('<i class="bx bxs-loader-dots bx-spin text-lg"></i> Sending...').attr('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/announcement_api.php?action=create',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if(res.status == 'success') {
                    showAlert('success', res.message);
                    setTimeout(() => loadPage(BASE_URL + 'admin/pages/announcements.php'), 1000);
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

    $('.toggle-status').on('change', function() {
        let id = $(this).data('id');
        let status = $(this).is(':checked') ? 'active' : 'inactive';
        
        // Optimistic UI toggle handled by CSS, we just make the request
        $.post(BASE_URL + 'admin/auth/announcement_api.php?action=toggle_status', { id: id, status: status }, function(res) {
            if(res.status == 'success') {
                if(status == 'active') {
                    // Update appearance immediately if we un-faded it
                    $(`input[data-id="${id}"]`).closest('.bg-white').removeClass('opacity-60 saturate-50');
                } else {
                    $(`input[data-id="${id}"]`).closest('.bg-white').addClass('opacity-60 saturate-50');
                }
            } else {
                showAlert('error', res.message);
            }
        });
    });

    $('.delete-announcement').on('click', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: "Delete Announcement?",
            text: "This action cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            confirmButtonText: "Yes, delete it"
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/auth/announcement_api.php?action=delete', { id: id }, function(res) {
                    if (res.status == 'success') {
                        showAlert('success', res.message);
                        setTimeout(() => loadPage(BASE_URL + 'admin/pages/announcements.php'), 1000);
                    } else {
                        showAlert('error', res.message);
                    }
                });
            }
        });
    });

    // We need to inject the CSS for the toggle dynamically
    $("<style>")
    .prop("type", "text/css")
    .html(`
        .toggle-status:checked ~ .dot { transform: translateX(100%); }
        .toggle-status:checked ~ .block { background-color: #10b981; }
    `)
    .appendTo("head");
</script>
