<?php
require '../../connections/db.php';

// Fetch Hostels and stats
$stmt = $conn->query("
    SELECT h.*, 
        (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id) as room_count,
        (SELECT SUM(capacity) FROM rooms r WHERE r.hostel_id = h.id) as total_room_capacity,
        (SELECT COUNT(*) FROM bed_allocations ba JOIN rooms r ON ba.room_id = r.id WHERE r.hostel_id = h.id AND ba.status = 'active') as taken_beds
    FROM hostels h 
    ORDER BY h.name ASC
");
$hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Students who don't have an active bed yet
$stud_stmt = $conn->query("
    SELECT id, user_id, first_name, surname, class 
    FROM users 
    WHERE role = 'student' 
    AND id NOT IN (SELECT student_id FROM bed_allocations WHERE status = 'active')
    ORDER BY first_name ASC
");
$unassigned_students = $stud_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all rooms (for assignments)
$rooms_stmt = $conn->query("
    SELECT r.id, r.room_number, r.capacity, h.name as hostel_name, h.type,
        (SELECT COUNT(*) FROM bed_allocations ba WHERE ba.room_id = r.id AND ba.status = 'active') as taken
    FROM rooms r
    JOIN hostels h ON r.hostel_id = h.id
    ORDER BY h.name, r.room_number
");
$rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch allocations for the list
$alloc_stmt = $conn->query("
    SELECT ba.id, u.first_name, u.surname, u.user_id, u.class, r.room_number, h.name as hostel_name, ba.allocated_at
    FROM bed_allocations ba
    JOIN users u ON ba.student_id = u.id
    JOIN rooms r ON ba.room_id = r.id
    JOIN hostels h ON r.hostel_id = h.id
    WHERE ba.status = 'active'
    ORDER BY h.name, r.room_number, u.first_name
");
$allocations = $alloc_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fadeIn w-full md:p-8 p-4">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="flex items-center gap-5">
            <div class="size-16 rounded-3xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-xl shadow-amber-200 flex items-center justify-center">
                <i class="bx bx-building-house text-3xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Hostel & Dormitory</h1>
                <p class="text-sm text-gray-500 font-medium">Manage blocks, rooms, and student bed allocations.</p>
            </div>
        </div>
        <div class="flex bg-gray-100 p-1.5 rounded-2xl border border-gray-200 shadow-inner">
            <button id="tabHostels" class="px-6 py-2.5 rounded-xl text-sm font-bold bg-white text-amber-600 shadow-sm transition-all focus:outline-none flex gap-2 items-center">
                <i class="bx bx-buildings"></i> Hostels
            </button>
            <button id="tabAllocations" class="px-6 py-2.5 rounded-xl text-sm font-bold text-gray-500 hover:text-gray-800 transition-all focus:outline-none flex gap-2 items-center">
                <i class="bx bx-bed"></i> Allocations
            </button>
        </div>
    </div>

    <!-- HOSTELS VIEW -->
    <div id="viewHostels" class="space-y-6">
        <div class="flex justify-end">
            <button id="btnAddHostel" class="bg-amber-600 text-white px-5 py-2.5 rounded-xl hover:bg-amber-700 transition flex items-center gap-2 font-bold text-sm shadow-md">
                <i class="bx bx-plus"></i> New Hostel Block
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($hostels as $h): 
                $total_cap = (int)$h['total_room_capacity'];
                $taken = (int)$h['taken_beds'];
                $avail = $total_cap > 0 ? $total_cap - $taken : 0;
                $pct = $total_cap > 0 ? ($taken / $total_cap) * 100 : 0;
            ?>
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-lg transition-all flex flex-col group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-amber-50 rounded-full blur-[40px] opacity-0 group-hover:opacity-100 transition duration-500 -z-0"></div>
                
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($h['name']) ?></h3>
                    <span class="px-3 py-1 bg-gray-100 text-gray-500 text-xs font-bold uppercase tracking-widest rounded-full border border-gray-200"><?= $h['type'] ?></span>
                </div>
                
                <div class="flex items-center gap-6 mb-6 mt-2 relative z-10">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Total Rooms</p>
                        <p class="text-2xl font-semibold text-gray-700 leading-none mt-1"><?= $h['room_count'] ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Beds Free</p>
                        <p class="text-2xl font-semibold text-emerald-500 leading-none mt-1"><?= $avail ?></p>
                    </div>
                </div>

                <div class="mb-6 relative z-10">
                    <div class="flex justify-between text-xs font-bold text-gray-500 mb-2">
                        <span>Capacity: <?= $taken ?> / <?= $total_cap ?></span>
                        <span><?= round($pct) ?>%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full transition-all duration-1000" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>

                <div class="mt-auto relative z-10">
                    <button class="w-full py-3 bg-gray-50 text-amber-600 rounded-xl font-semibold text-xs uppercase tracking-widest hover:bg-amber-100 transition border border-amber-100" onclick="addRoomModal(<?= $h['id'] ?>)">
                        <i class="bx bx-plus-circle mr-1"></i> Add Room
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($hostels)): ?>
                <div class="col-span-full p-12 text-center bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                    <i class="bx bx-buildings text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-bold">No hostels set up yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ALLOCATIONS VIEW -->
    <div id="viewAllocations" class="hidden space-y-6">
        
        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 lg:p-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center gap-2"><i class="bx bx-user-plus text-amber-500"></i> Allocate Bed</h3>
            
            <form id="allocateForm" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex flex-col gap-1.5 focus-within:text-amber-600 transition-colors">
                    <label class="text-xs font-semibold text-inherit uppercase tracking-widest">Select Student</label>
                    <select name="student_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all cursor-pointer">
                        <option value="">-- Choose Unassigned Student --</option>
                        <?php foreach($unassigned_students as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= $st['first_name'] ?> <?= $st['surname'] ?> (<?= $st['user_id'] ?>) - <?= $st['class'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex flex-col gap-1.5 focus-within:text-amber-600 transition-colors">
                    <label class="text-xs font-semibold text-inherit uppercase tracking-widest">Select Room</label>
                    <select name="room_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all cursor-pointer">
                        <option value="">-- Choose Available Room --</option>
                        <?php foreach($rooms as $rm): 
                            $avail = $rm['capacity'] - $rm['taken'];
                            if($avail > 0):
                        ?>
                            <option value="<?= $rm['id'] ?>"><?= $rm['hostel_name'] ?> (<?= $rm['type'] ?>) - Room <?= $rm['room_number'] ?> (<?= $avail ?> free)</option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full h-[46px] bg-amber-600 text-white rounded-xl font-bold text-sm shadow-md hover:bg-amber-700 transition-all flex items-center justify-center gap-2">
                        <i class="bx bx-check"></i> Assign Room
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="p-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Student</th>
                            <th class="p-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Class</th>
                            <th class="p-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Hostel & Room</th>
                            <th class="p-4 text-[10px] font-semibold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($allocations as $al): ?>
                        <tr class="hover:bg-amber-50/20 transition group">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-xs shrink-0">
                                        <?= substr($al['first_name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800"><?= $al['first_name'] ?> <?= $al['surname'] ?></p>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest"><?= $al['user_id'] ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-sm text-gray-600 font-bold"><?= $al['class'] ?></td>
                            <td class="p-4">
                                <span class="text-sm font-semibold text-amber-700 bg-amber-50 px-3 py-1 rounded-lg border border-amber-100">
                                    <?= $al['hostel_name'] ?> - <?= $al['room_number'] ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <button onclick="revokeBed(<?= $al['id'] ?>)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" data-tippy-content="Revoke Bed">
                                    <i class="bx bx-user-minus text-xl"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($allocations)): ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-400 font-bold italic">No active bed allocations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- Modal: New Hostel -->
<div class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 fade-in" id="modalHostel">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden scale-in">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800">New Hostel Block</h3>
            <button class="text-gray-400 hover:text-gray-700 transition" onclick="$('#modalHostel').addClass('hidden')"><i class="bx bx-x text-2xl"></i></button>
        </div>
        <form id="formHostel" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_hostel">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Hostel Name</label>
                <input type="text" name="name" required class="w-full mt-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-400 outline-none text-sm" placeholder="e.g. Block C">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Type</label>
                <select name="type" class="w-full mt-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-400 outline-none text-sm">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Mixed">Mixed</option>
                </select>
            </div>
            <button type="submit" class="w-full py-3 bg-amber-600 text-white rounded-xl font-bold shadow-md hover:bg-amber-700 transition mt-4">Save Hostel</button>
        </form>
    </div>
</div>

<!-- Modal: New Room -->
<div class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 fade-in" id="modalRoom">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden scale-in">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800">Add New Room</h3>
            <button class="text-gray-400 hover:text-gray-700 transition" onclick="$('#modalRoom').addClass('hidden')"><i class="bx bx-x text-2xl"></i></button>
        </div>
        <form id="formRoom" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_room">
            <input type="hidden" name="hostel_id" id="room_hostel_id">
            
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Room Number / Name</label>
                <input type="text" name="room_number" required class="w-full mt-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-400 outline-none text-sm" placeholder="e.g. A105">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Bed Capacity</label>
                <input type="number" name="capacity" value="4" min="1" max="50" required class="w-full mt-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-400 outline-none text-sm">
            </div>
            <button type="submit" class="w-full py-3 bg-amber-600 text-white rounded-xl font-bold shadow-md hover:bg-amber-700 transition mt-4">Add Room</button>
        </form>
    </div>
</div>


<script>
    // Tab Switching
    $('#tabHostels').on('click', function() {
        $(this).removeClass('text-gray-500 hover:text-gray-800 bg-transparent').addClass('bg-white text-amber-600 shadow-sm');
        $('#tabAllocations').removeClass('bg-white text-amber-600 shadow-sm').addClass('text-gray-500 hover:text-gray-800 bg-transparent');
        $('#viewHostels').removeClass('hidden');
        $('#viewAllocations').addClass('hidden');
    });

    $('#tabAllocations').on('click', function() {
        $(this).removeClass('text-gray-500 hover:text-gray-800 bg-transparent').addClass('bg-white text-amber-600 shadow-sm');
        $('#tabHostels').removeClass('bg-white text-amber-600 shadow-sm').addClass('text-gray-500 hover:text-gray-800 bg-transparent');
        $('#viewAllocations').removeClass('hidden');
        $('#viewHostels').addClass('hidden');
    });

    // Modals
    $('#btnAddHostel').on('click', () => $('#modalHostel').removeClass('hidden'));
    function addRoomModal(hostel_id) {
        $('#room_hostel_id').val(hostel_id);
        $('#modalRoom').removeClass('hidden');
    }

    // Handlers
    function handleForm(formId) {
        $(formId).on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type=submit]');
            const og = btn.html();
            btn.html('<i class="bx bx-loader-alt bx-spin"></i>').prop('disabled', true);

            $.post(BASE_URL + 'admin/auth/hostels_api.php', $(this).serialize(), function(res) {
                if(res.status === 'success') {
                    window.showToast(res.message, 'success');
                    setTimeout(() => window.loadPage('pages/hostels.php'), 500);
                } else {
                    window.showToast(res.message, 'error');
                    btn.html(og).prop('disabled', false);
                }
            }, 'json').fail(() => {
                window.showToast('Network error', 'error');
                btn.html(og).prop('disabled', false);
            });
        });
    }

    handleForm('#formHostel');
    handleForm('#formRoom');
    handleForm('#allocateForm');

    // Revoke
    function revokeBed(id) {
        Swal.fire({
            title: 'Revoke Allocation?',
            text: "This will remove the student from this bed space.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, revoke it'
        }).then(result => {
            if(result.isConfirmed) {
                $.post(BASE_URL + 'admin/auth/hostels_api.php', { action: 'revoke_bed', id: id }, function(res) {
                    if(res.status === 'success') {
                        $('#tabAllocations').click(); // Pre-select tab on reload
                        window.loadPage('pages/hostels.php');
                    } else {
                        window.showToast(res.message, 'error');
                    }
                }, 'json');
            }
        });
    }

    // Auto-init tooltips
    if(typeof initTooltips === 'function') initTooltips();

</script>
