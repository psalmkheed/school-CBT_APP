<?php
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

// Fetch basic data
$stmt = $conn->query("SELECT * FROM inventory_items ORDER BY category ASC, item_name ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("
    SELECT i.*, it.item_name, it.category, s.first_name, s.surname, s.role
    FROM inventory_issues i
    JOIN inventory_items it ON i.item_id = it.id
    JOIN users s ON i.issued_to_user_id = s.id
    ORDER BY i.id DESC
");
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch members (usually staff) for the issue dropdown
$stmt = $conn->query("SELECT id, first_name, surname, role FROM users WHERE role IN ('super', 'admin', 'staff') ORDER BY first_name ASC");
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="fadeIn w-full p-4 md:p-8 min-h-screen">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <button onclick="goHome()" class="md:hidden size-10 shrink-0 rounded-full flex items-center justify-center text-gray-500 hover:text-orange-700 hover:border-orange-200 hover:bg-orange-50 transition-all cursor-pointer">
                <i class="bx bx-arrow-left-stroke text-4xl"></i>
            </button>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-orange-100 flex items-center justify-center shrink-0 shadow-sm border border-orange-200">
                    <i class="bx bx-box text-orange-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl md:text-2xl font-semibold text-gray-800 tracking-tight">Inventory Management</h3>
                    <p class="text-sm text-gray-400 font-medium">Track school assets and equipment distribution</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-white rounded-[2rem] shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-2 mb-8 inline-flex overflow-x-auto custom-scrollbar max-w-full">
        <button onclick="switchInvTab('assets')" id="tabBtnInv_assets" class="relative overflow-hidden px-8 py-3 rounded-2xl font-bold text-sm bg-orange-500 text-white shadow-lg shadow-orange-200 transition-all whitespace-nowrap">
            <span class="relative z-10 flex items-center gap-2"><i class="bx bx-sitemap text-lg"></i> Asset Registry</span>
        </button>
        <button onclick="switchInvTab('distribution')" id="tabBtnInv_distribution" class="relative overflow-hidden px-8 py-3 rounded-2xl font-bold text-sm text-gray-500 hover:text-orange-600 hover:bg-orange-50 transition-all whitespace-nowrap">
            <span class="relative z-10 flex items-center gap-2"><i class="bx bx-transfer text-lg"></i> Dispatch Log</span>
        </button>
    </div>

    <!-- TABS CONTENT SECTION -->
    
    <!-- Tab 1: Assest Registry -->
    <div id="tabInv_assets" class="tab-pane-inv">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Add Item Form -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 space-y-6">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="bx bx-plus-circle text-orange-500 mr-1"></i> Add New Asset</h4>
                    <form id="itemForm" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Item Name</label>
                            <input type="text" name="item_name" required placeholder="e.g. Ergonomic Chair" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-all text-sm font-semibold">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Category</label>
                                <select name="category" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-all text-sm font-semibold">
                                    <option value="Furniture">Furniture</option>
                                    <option value="Electronics">Electronics</option>
                                    <option value="Stationery">Stationery</option>
                                    <option value="Lab Equipment">Lab Equipment</option>
                                    <option value="Sports Gear">Sports Gear</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Initial Quantity</label>
                                <input type="number" name="stock_quantity" required value="1" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-all text-sm font-semibold">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Storage Location</label>
                            <input type="text" name="location" placeholder="e.g. Block A Storage" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-all text-sm font-semibold">
                        </div>
                        <button type="submit" class="w-full py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-sm shadow-xl transition-all">
                            Register Asset
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Items Grid -->
            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php if(empty($items)): ?>
                        <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                            <i class="bx bx-package text-4xl text-gray-300 mb-2"></i>
                            <h5 class="text-gray-500 font-bold">Inventory is Empty</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach($items as $it): 
                            $bgStatus = $it['stock_quantity'] > 5 ? 'bg-emerald-50 text-emerald-600' : ($it['stock_quantity'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600');
                        ?>
                            <div class="bg-white p-5 rounded-3xl border border-gray-100 relative group hover:shadow-[0_4px_20px_rgb(0,0,0,0.06)] transition-all flex flex-col justify-between">
                                <div class="absolute top-4 right-4 flex gap-1">
                                    <button onclick="addStock(<?= $it['id'] ?>, '<?= htmlspecialchars(addslashes($it['item_name'])) ?>')" class="size-8 rounded-full bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Add Stock">
                                        <i class="bx bx-plus text-sm"></i>
                                    </button>
                                    <button onclick="deleteResourceInv('item', <?= $it['id'] ?>)" class="size-8 rounded-full bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Delete Format">
                                        <i class="bx bx-trash text-sm"></i>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <div class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-[10px] font-semibold uppercase tracking-widest border border-gray-200 inline-block mb-3">
                                        <?= htmlspecialchars($it['category']) ?>
                                    </div>
                                    <h4 class="text-base font-semibold text-gray-800 leading-tight mb-1 pr-14 line-clamp-2" title="<?= htmlspecialchars($it['item_name']) ?>"><?= htmlspecialchars($it['item_name']) ?></h4>
                                    
                                    <?php if($it['location']): ?>
                                        <p class="text-[10px] text-gray-400 font-semibold mb-4 uppercase tracking-widest"><i class="bx bx-map-pin"></i> <?= htmlspecialchars($it['location']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">In Stock</div>
                                    <div class="text-sm px-2 py-1 rounded font-semibold <?= $bgStatus ?>"><?= $it['stock_quantity'] ?> Units</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Distribution/Issues -->
    <div id="tabInv_distribution" class="tab-pane-inv hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Issue Form -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-8 space-y-6 sticky top-24">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="bx bx-transfer-alt text-orange-500 mr-1"></i> Dispatch Asset</h4>
                    <form id="issueInvForm" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select Item</label>
                            <select name="item_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-all text-sm font-semibold max-h-48 overflow-y-auto">
                                <option value="">Choose asset...</option>
                                <?php foreach($items as $it): 
                                    if ($it['stock_quantity'] > 0): ?>
                                    <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['item_name']) ?> (<?= $it['stock_quantity'] ?> left)</option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Staff Recipient</label>
                            <select name="issued_to_user_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-all text-sm font-semibold max-h-48 overflow-y-auto">
                                <option value="">Select recipient...</option>
                                <?php foreach($staff_list as $sl): ?>
                                    <option value="<?= $sl['id'] ?>"><?= htmlspecialchars($sl['first_name'] . ' ' . $sl['surname']) ?> (<?= htmlspecialchars($sl['role']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Quantity Dispatched</label>
                            <input type="number" name="quantity" required value="1" min="1" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-100 focus:border-orange-400 transition-all text-sm font-semibold">
                        </div>
                        <button type="submit" class="w-full mt-2 py-3 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-bold text-sm shadow-xl shadow-orange-200 transition-all">
                            Dispatch
                        </button>
                    </form>
                </div>
            </div>

            <!-- Issues Table -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-800">Dispatch Log</h4>
                        <span class="text-xs font-bold text-gray-400 bg-white px-3 py-1 rounded-full border border-gray-200"><?= count($issues) ?> Records</span>
                    </div>
                    
                    <?php if(empty($issues)): ?>
                        <div class="py-16 text-center">
                            <i class="bx bx-receipt text-4xl text-gray-300 mb-2"></i>
                            <h5 class="text-gray-500 font-bold">No Dispatches Logged</h5>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 text-[10px] uppercase tracking-widest font-semibold text-gray-400 border-b border-gray-100">
                                        <th class="p-4 pl-6">Asset</th>
                                        <th class="p-4">Staff Member</th>
                                        <th class="p-4">Date</th>
                                        <th class="p-4 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach($issues as $i): 
                                        $is_returned = $i['status'] === 'returned';
                                    ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="p-4 pl-6">
                                            <p class="text-sm font-bold text-gray-800 line-clamp-1 truncate block w-40" title="<?= htmlspecialchars($i['item_name']) ?>"><?= htmlspecialchars($i['item_name']) ?></p>
                                            <p class="text-[10px] font-bold text-orange-500 uppercase"><?= $i['quantity'] ?> Delivered</p>
                                        </td>
                                        <td class="p-4">
                                            <p class="text-sm font-bold text-gray-700"><?= htmlspecialchars($i['first_name'] . ' ' . $i['surname']) ?></p>
                                            <p class="text-[10px] font-bold text-indigo-500 uppercase"><?= htmlspecialchars($i['role']) ?></p>
                                        </td>
                                        <td class="p-4">
                                            <p class="text-xs font-semibold text-gray-500"><span class="text-gray-800"><?= date('M j, Y', strtotime($i['issue_date'])) ?></span></p>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                <?php if(!$is_returned): ?>
                                                    <span class="text-[9px] font-semibold uppercase tracking-widest px-2 py-1 rounded bg-orange-100 text-orange-700">IN USE</span>
                                                    <button onclick="returnItem(<?= $i['id'] ?>)" class="text-[10px] font-bold px-3 py-1.5 rounded-lg bg-gray-900 text-white hover:bg-black transition-all">
                                                        Recover Item
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-[9px] font-semibold uppercase tracking-widest px-2 py-1 rounded bg-emerald-100 text-emerald-700">RETURNED</span>
                                                    <p class="text-[10px] text-gray-400 font-bold"><?= date('M j, Y', strtotime($i['return_date'])) ?></p>
                                                <?php endif; ?>
                                            </div>
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
    function switchInvTab(tabId) {
        $('.tab-pane-inv').addClass('hidden');
        $('#tabInv_' + tabId).removeClass('hidden');

        // Reset all buttons
        $('[id^="tabBtnInv_"]').removeClass('bg-orange-500 text-white shadow-lg shadow-orange-200').addClass('text-gray-500 hover:text-orange-600 hover:bg-orange-50');
        
        // Active button
        $('#tabBtnInv_' + tabId).removeClass('text-gray-500 hover:text-orange-600 hover:bg-orange-50').addClass('bg-orange-500 text-white shadow-lg shadow-orange-200');
    }

    // Default Tab
    switchInvTab('assets');

    // Forms Handlers
    $('#itemForm').on('submit', function(e) {
        e.preventDefault();
        submitInvForm($(this), 'add_item', 'Saving...');
    });

    $('#issueInvForm').on('submit', function(e) {
        e.preventDefault();
        submitInvForm($(this), 'issue_item', 'Dispatching...');
    });

    function submitInvForm(form, action, loadingText) {
        const btn = form.find('button[type="submit"]');
        const ogText = btn.html();
        btn.html(`<i class="bx bx-loader-alt bx-spin"></i> ${loadingText}`).prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/inventory_api.php?action=' + action,
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    form[0].reset();
                    $('#inventory').trigger('click'); 
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

    window.addStock = function(id, name) {
        Swal.fire({
            title: 'Add Stock to ' + name,
            input: 'number',
            inputLabel: 'Quantity',
            inputPlaceholder: 'Enter number of units',
            inputAttributes: { min: 1, step: 1 },
            showCancelButton: true,
            confirmButtonText: 'Add to Inventory'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $.ajax({
                    url: BASE_URL + 'admin/auth/inventory_api.php?action=add_stock',
                    type: 'POST',
                    data: { id: id, quantity: result.value },
                    success: function(res) {
                        if(res.status === 'success') {
                            showAlert('success', res.message);
                            $('#inventory').trigger('click');
                        } else {
                            showAlert('error', res.message || 'Action failed.');
                        }
                    }
                });
            }
        });
    }

    window.deleteResourceInv = function(type, id) {
        Swal.fire({
            title: 'Delete Asset?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'admin/auth/inventory_api.php?action=delete_' + type,
                    type: 'POST',
                    data: { id: id },
                    success: function(res) {
                        if(res.status === 'success') {
                            showAlert('success', res.message);
                            $('#inventory').trigger('click');
                        } else {
                            showAlert('error', res.message || 'Deletion failed.');
                        }
                    }
                });
            }
        });
    };

    window.returnItem = function(id) {
        Swal.fire({
            title: 'Confirm Recovery',
            text: "Mark this asset as returned by the staff?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            confirmButtonText: 'Yes, Recovered'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + 'admin/auth/inventory_api.php?action=return_item',
                    type: 'POST',
                    data: { id: id },
                    success: function(res) {
                        if(res.status === 'success') {
                            showAlert('success', res.message);
                            $('#inventory').trigger('click');
                        } else {
                            showAlert('error', res.message || 'Action failed.');
                        }
                    }
                });
            }
        });
    };
</script
