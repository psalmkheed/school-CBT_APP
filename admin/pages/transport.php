<?php
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

// Fetch basic data for the page
$stmt = $conn->query("SELECT * FROM transport_routes ORDER BY id DESC");
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("
    SELECT v.*, r.route_name 
    FROM transport_vehicles v 
    LEFT JOIN transport_routes r ON v.route_id = r.id 
    ORDER BY v.id DESC
");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("
    SELECT a.*, u.first_name, u.surname, u.class, v.vehicle_number, v.driver_name, r.route_name 
    FROM transport_allocations a
    JOIN users u ON a.student_id = u.id
    JOIN transport_vehicles v ON a.vehicle_id = v.id
    LEFT JOIN transport_routes r ON v.route_id = r.id
    ORDER BY a.id DESC
");
$allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students safely
$stmt = $conn->query("SELECT id, user_id, first_name, surname, class FROM users WHERE role = 'student' ORDER BY class ASC, first_name ASC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-indigo-700 hover:border-indigo-200 hover:bg-indigo-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 shadow-sm border border-indigo-200">
                    <i class="bx bx-bus text-indigo-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">Transport Management</h3>
                    <p class="text-sm text-gray-400 font-medium">Manage routes, vehicles, and student allocations</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-white rounded-[2rem] shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-2 mb-8 inline-flex overflow-x-auto custom-scrollbar max-w-full">
        <button onclick="switchTab('routes')" id="tabBtn_routes" class="relative overflow-hidden px-8 py-3 rounded-2xl font-bold text-sm bg-indigo-600 text-white shadow-lg shadow-indigo-200 transition-all whitespace-nowrap">
            <span class="relative z-10 flex items-center gap-2"><i class="bx bx-map-alt text-lg"></i> Routes mapping</span>
        </button>
        <button onclick="switchTab('vehicles')" id="tabBtn_vehicles" class="relative overflow-hidden px-8 py-3 rounded-2xl font-bold text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-all whitespace-nowrap">
            <span class="relative z-10 flex items-center gap-2"><i class="bx bx-car text-lg"></i> Vehicles fleet</span>
        </button>
        <button onclick="switchTab('allocations')" id="tabBtn_allocations" class="relative overflow-hidden px-8 py-3 rounded-2xl font-bold text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-all whitespace-nowrap">
            <span class="relative z-10 flex items-center gap-2"><i class="bx bx-user-pin text-lg"></i> Student Allocations</span>
        </button>
    </div>

    <!-- TABS CONTENT SECTION -->
    
    <!-- Tab 1: Routes -->
    <div id="tab_routes" class="tab-pane">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Route Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 space-y-6">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="bx bx-plus-circle text-indigo-500 mr-1"></i> New Route</h4>
                    <form id="routeForm" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Route Title</label>
                            <input type="text" name="route_name" required placeholder="e.g. Downtown Route" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Stops (Comma separated)</label>
                            <textarea name="stops" rows="2" placeholder="Central Park, Main St, Oak Ave..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Fare / Term</label>
                            <input type="number" step="0.01" name="fare" placeholder="0.00" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                        </div>
                        <button type="submit" class="w-full py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-sm shadow-xl transition-all">
                            Save Route
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Routes List -->
            <div class="lg:col-span-2 space-y-4">
                <?php if(empty($routes)): ?>
                    <div class="py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                        <i class="bx bx-map-pin text-4xl text-gray-300 mb-2"></i>
                        <h5 class="text-gray-500 font-bold">No Routes Defined</h5>
                    </div>
                <?php else: ?>
                    <?php foreach($routes as $r): ?>
                        <div class="bg-white p-6 rounded-3xl border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:shadow-lg transition-all">
                            <div class="flex items-center gap-4">
                                <div class="size-12 rounded-full bg-indigo-50 flex items-center justify-center shrink-0">
                                    <i class="bx bx-map-alt text-xl text-indigo-500"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($r['route_name']) ?></h4>
                                    <p class="text-xs font-semibold text-gray-500 line-clamp-1"><span class="text-indigo-400">Stops:</span> <?= htmlspecialchars($r['stops']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Term Fare</p>
                                    <p class="text-sm font-bold text-gray-800">$<?= number_format($r['fare'], 2) ?></p>
                                </div>
                                <button onclick="deleteResource('route', <?= $r['id'] ?>)" class="size-10 rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Delete Route">
                                    <i class="bx bx-trash text-lg"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tab 2: Vehicles -->
    <div id="tab_vehicles" class="tab-pane hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Vehicle -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 space-y-6">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="bx bx-plus-circle text-indigo-500 mr-1"></i> Add Vehicle</h4>
                    <form id="vehicleForm" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Assign Route</label>
                            <select name="route_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                                <option value="">Select Route</option>
                                <?php foreach($routes as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['route_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Vehicle Number / Plate</label>
                            <input type="text" name="vehicle_number" required placeholder="e.g. AB-123-CD" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Driver Name</label>
                                <input type="text" name="driver_name" required placeholder="John Doe" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Driver Contact</label>
                                <input type="text" name="driver_phone" placeholder="Phone" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Seat Capacity</label>
                                <input type="number" name="capacity" required placeholder="e.g. 50" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                            </div>
                        </div>
                        <button type="submit" class="w-full mt-4 py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-sm shadow-xl transition-all">
                            Register Vehicle
                        </button>
                    </form>
                </div>
            </div>

            <!-- Vehicles List -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if(empty($vehicles)): ?>
                        <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                            <i class="bx bx-bus text-4xl text-gray-300 mb-2"></i>
                            <h5 class="text-gray-500 font-bold">No Vehicles Registered</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach($vehicles as $v): ?>
                            <div class="bg-white p-6 rounded-3xl border border-gray-100 hover:shadow-lg transition-all relative">
                                <div class="absolute top-4 right-4">
                                    <button onclick="deleteResource('vehicle', <?= $v['id'] ?>)" class="size-8 rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors cursor-pointer" title="Delete Vehicle">
                                        <i class="bx bx-trash text-sm"></i>
                                    </button>
                                </div>
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="px-3 py-1 bg-gray-900 text-white rounded-lg text-xs font-semibold uppercase tracking-widest border border-gray-700 shadow-sm inline-block">
                                        <?= htmlspecialchars($v['vehicle_number']) ?>
                                    </div>
                                    <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md">
                                        <i class="bx bx-map"></i> <?= htmlspecialchars($v['route_name'] ?? 'Unassigned') ?>
                                    </span>
                                </div>
                                <div class="space-y-2 mt-4">
                                    <div class="flex justify-between items-center text-sm font-medium">
                                        <span class="text-gray-500"><i class="bx bx-user-circle mr-1"></i> Driver</span>
                                        <span class="text-gray-800"><?= htmlspecialchars($v['driver_name']) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm font-medium">
                                        <span class="text-gray-500"><i class="bx bx-phone mr-1"></i> Contact</span>
                                        <span class="text-gray-800"><?= htmlspecialchars($v['driver_phone']) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm font-medium">
                                        <span class="text-gray-500"><i class="bx bx-chair mr-1"></i> Capacity</span>
                                        <span class="text-gray-800"><?= intval($v['capacity']) ?> Seats</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Allocations -->
    <div id="tab_allocations" class="tab-pane hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Allocation -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 space-y-6 sticky top-24">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="bx bx-plus-circle text-indigo-500 mr-1"></i> Assign Transport</h4>
                    <form id="allocationForm" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Student</label>
                            <select name="student_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold max-h-48 overflow-y-auto">
                                <option value="">Select a student...</option>
                                <?php 
                                $current_class = '';
                                foreach($students as $s): 
                                    if ($s['class'] !== $current_class) {
                                        if ($current_class !== '') { echo "</optgroup>"; }
                                        $current_class = $s['class'];
                                        echo "<optgroup label='" . htmlspecialchars($current_class) . "'>";
                                    }
                                ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?> (<?= $s['user_id'] ?>)</option>
                                <?php endforeach; if ($current_class !== '') echo "</optgroup>"; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Assign Vehicle</label>
                            <select name="vehicle_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                                <option value="">Select Vehicle</option>
                                <?php foreach($vehicles as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['vehicle_number'] . ' - ' . $v['route_name']) ?> (Max: <?= $v['capacity'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Specific Pickup Point / Landmark</label>
                            <input type="text" name="pickup_point" required placeholder="e.g. Opposite City Mall" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all text-sm font-semibold">
                        </div>
                        
                        <button type="submit" class="w-full mt-2 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-xl shadow-emerald-200 transition-all">
                            Complete Assignment
                        </button>
                    </form>
                </div>
            </div>

            <!-- Allocations List Table -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-800">Assigned Commuters</h4>
                        <span class="text-xs font-bold text-gray-400 bg-white px-3 py-1 rounded-full border border-gray-200"><?= count($allocations) ?> Assigned</span>
                    </div>
                    
                    <?php if(empty($allocations)): ?>
                        <div class="py-16 text-center">
                            <i class="bx bx-user-x text-4xl text-gray-300 mb-2"></i>
                            <h5 class="text-gray-500 font-bold">No Allocations Made</h5>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 text-[10px] uppercase tracking-widest font-semibold text-gray-400 border-b border-gray-100">
                                        <th class="p-4 pl-6">Student</th>
                                        <th class="p-4">Route & Vehicle</th>
                                        <th class="p-4">Pickup Point</th>
                                        <th class="p-4 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach($allocations as $a): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-4 pl-6">
                                            <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($a['first_name'] . ' ' . $a['surname']) ?></p>
                                            <p class="text-[10px] font-bold text-indigo-500 uppercase"><?= htmlspecialchars($a['class']) ?></p>
                                        </td>
                                        <td class="p-4">
                                            <p class="text-xs font-bold text-gray-700 bg-gray-100 inline-block px-2 py-0.5 rounded border border-gray-200 mb-1"><?= htmlspecialchars($a['vehicle_number']) ?></p>
                                            <p class="text-[11px] font-medium text-gray-500"><?= htmlspecialchars($a['route_name'] ?? 'N/A') ?></p>
                                        </td>
                                        <td class="p-4">
                                            <p class="text-xs font-medium text-gray-600 line-clamp-2"><?= htmlspecialchars($a['pickup_point']) ?></p>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button onclick="deleteResource('allocation', <?= $a['id'] ?>)" class="p-2 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');

    // UI Tab Switcher
    function switchTab(tabId) {
        $('.tab-pane').addClass('hidden');
        $('#tab_' + tabId).removeClass('hidden');

        // Reset all buttons
        $('[id^="tabBtn_"]').removeClass('bg-indigo-600 text-white shadow-lg shadow-indigo-200').addClass('text-gray-500 hover:text-indigo-600 hover:bg-gray-50');
        
        // Active button
        $('#tabBtn_' + tabId).removeClass('text-gray-500 hover:text-indigo-600 hover:bg-gray-50').addClass('bg-indigo-600 text-white shadow-lg shadow-indigo-200');
    }

    // Default Tab
    switchTab('routes');

    // Forms Handlers
    $('#routeForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), 'create_route', 'Saving Route...');
    });

    $('#vehicleForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), 'create_vehicle', 'Registering...');
    });

    $('#allocationForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), 'assign_student', 'Assigning...');
    });

    function submitForm(form, action, loadingText) {
        const btn = form.find('button[type="submit"]');
        const ogText = btn.html();
        btn.html(`<i class="bx bx-loader-alt bx-spin"></i> ${loadingText}`).prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/transport_api.php?action=' + action,
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    form[0].reset();
                    // Reload the component to reflect changes
                    $('#transport').trigger('click'); 
                } else {
                    showAlert('error', res.message || 'An error occurred.');
                }
            },
            error: function() {
                showAlert('error', 'Network communication failed.');
            },
            complete: function() {
                btn.html(ogText).prop('disabled', false);
            }
        });
    }

    window.deleteResource = function(type, id) {
        Swal.fire({
            title: 'Delete ' + type.charAt(0).toUpperCase() + type.slice(1) + '?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'admin/auth/transport_api.php?action=delete_' + type,
                    type: 'POST',
                    data: { id: id },
                    success: function(res) {
                        if(res.status === 'success') {
                            showAlert('success', res.message);
                            $('#transport').trigger('click');
                        } else {
                            showAlert('error', res.message || 'Deletion failed.');
                        }
                    }
                });
            }
        });
    };
</script>
