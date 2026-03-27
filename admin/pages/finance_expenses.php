<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// We previously had an aggressive auto-update string here. I've removed it to avoid accidental mass reassignment of legacy expenses.

$scope_sql = " AND academic_session = :session AND term = :term";

// Fetch basic expense stats
$ytd_stmt = $conn->prepare("SELECT SUM(amount) FROM finance_expenses WHERE status = 'approved' $scope_sql");
$ytd_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$ytd_expenses = $ytd_stmt->fetchColumn() ?: 0;

$max_stmt = $conn->prepare("SELECT MAX(amount) FROM finance_expenses WHERE status = 'approved' $scope_sql");
$max_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$max_expenses = $max_stmt->fetchColumn() ?: 0;

$pending_stmt = $conn->prepare("SELECT SUM(amount) FROM finance_expenses WHERE status = 'pending' $scope_sql");
$pending_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$pending_expenses = $pending_stmt->fetchColumn() ?: 0;

// Fetch highest category this term
$highest_cat_stmt = $conn->prepare("
    SELECT c.name, SUM(e.amount) as total
    FROM finance_expenses e
    JOIN finance_expense_categories c ON e.category_id = c.id
    WHERE e.status = 'approved' $scope_sql
    GROUP BY c.id
    ORDER BY total DESC LIMIT 1
");
$highest_cat_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$highest_category = $highest_cat_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch categories for chart and modal
$cats_stmt = $conn->query("SELECT * FROM finance_expense_categories ORDER BY name ASC");
$categories = $cats_stmt->fetchAll(PDO::FETCH_OBJ);

// Build Filter SQL
$filter_status = $_GET['status'] ?? '';
$filter_category = $_GET['category_id'] ?? '';

$filter_sql = "";
$params = [':session' => $active_session, ':term' => $active_term];

if (!empty($filter_status)) {
    $filter_sql .= " AND e.status = :status";
    $params[':status'] = $filter_status;
}
if (!empty($filter_category)) {
    $filter_sql .= " AND e.category_id = :cat";
    $params[':cat'] = $filter_category;
}

// Fetch recent expenses
$recent_stmt = $conn->prepare("
    SELECT e.*, c.name as category_name
    FROM finance_expenses e
    JOIN finance_expense_categories c ON e.category_id = c.id
    WHERE 1=1 $scope_sql $filter_sql
    ORDER BY e.created_at DESC " . ($filter_sql ? "" : "LIMIT 50") . "
");
$recent_stmt->execute($params);
$recent_expenses = $recent_stmt->fetchAll(PDO::FETCH_OBJ);

// Build Chart Data
$chartData = [];
$chartLabels = [];
$colors = ['#3b82f6', '#ef4444', '#a855f7', '#10b981', '#f59e0b', '#6366f1', '#ec4899'];
foreach ($categories as $idx => $cat) {
    $sum_stmt = $conn->prepare("SELECT SUM(amount) FROM finance_expenses WHERE category_id = ? AND status = 'approved'");
    $sum_stmt->execute([$cat->id]);
    $cat_total = $sum_stmt->fetchColumn() ?: 0;
    
    // Only map categories with expenses to the chart
    if ($cat_total > 0) {
        $chartLabels[] = $cat->name;
        $chartData[] = $cat_total;
    }
}
?>

<div class="fadeIn w-full p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Expense Tracker</h1>
            <p class="text-sm text-gray-500 mt-1">Log, categorize, and monitor institutional spending</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('addExpenseCategoryModal').classList.remove('hidden')" class="bg-white hover:bg-gray-50 text-gray-700 px-5 py-2.5 rounded-xl font-bold shadow-sm transition-all flex items-center gap-2 border border-gray-200">
                <i class="bx bx-copy-plus text-xl text-blue-500"></i> Create Category
            </button>
            <button onclick="document.getElementById('logExpenseModal').classList.remove('hidden')" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-red-200 transition-all flex items-center gap-2">
                <i class="bx bx-minus-circle text-xl"></i> Record Expense
            </button>
        </div>
    </div>

    <!-- Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-[0_2px_15px_rgb(0,0,0,0.03)] border border-gray-100 flex items-center gap-4">
            <div class="size-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl shrink-0">
                <i class="bx bx-trending-down"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Largest Expense</p>
                <h4 class="text-xl font-semibold text-gray-800">₦<?= number_format($max_expenses, 2) ?></h4>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-[0_2px_15px_rgb(0,0,0,0.03)] border border-gray-100 flex items-center gap-4">
            <div class="size-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl shrink-0">
                <i class="bx bx-clock-5"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Pending</p>
                <h4 class="text-xl font-semibold text-gray-800">₦<?= number_format($pending_expenses, 2) ?></h4>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-[0_2px_15px_rgb(0,0,0,0.03)] border border-gray-100 flex items-center gap-4">
            <div class="size-12 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-xl shrink-0">
                <i class="bx bx-building-house"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Top Config</p>
                <?php if ($highest_category): ?>
                    <h4 class="text-sm font-semibold text-gray-800 mt-1 truncate"><?= htmlspecialchars($highest_category['name']) ?></h4>
                    <p class="text-[10px] text-gray-500 font-bold">₦<?= number_format($highest_category['total']) ?></p>
                <?php else: ?>
                    <h4 class="text-sm font-semibold text-gray-800 mt-1">None</h4>
                <?php endif; ?>
            </div>
        </div>
        <div class="bg-gradient-to-br from-gray-800 to-black rounded-2xl p-5 shadow-lg flex flex-col justify-center relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 text-gray-700/50 group-hover:scale-125 transition-transform duration-500">
                <i class="bx bx-pie-chart-alt opacity-20 text-8xl"></i>
            </div>
            <div class="z-10 relative">
                <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Total Term Expenses</p>
                <h4 class="text-xl font-semibold text-white">₦<?= number_format($ytd_expenses, 2) ?></h4>
                <a href="finance_reports.php" class="text-[10px] text-blue-400 font-bold hover:text-blue-300 mt-1 inline-flex items-center gap-1">View Full Report <i class="bx bx-right-arrow-alt"></i></a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Expenses Table -->
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex items-center justify-between relative">
                <h3 class="text-lg font-bold text-gray-800">Recent Expenditure</h3>
                <button onclick="document.getElementById('expenseFilterMenu').classList.toggle('hidden')" class="text-sm font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 relative z-30">
                    Filter <i class="bx bx-filter"></i>
                </button>
                
                <!-- Filter Dropdown -->
                <div id="expenseFilterMenu" class="absolute right-6 top-16 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 p-5 hidden z-20">
                    <form id="expenseFilterForm" class="space-y-4" onsubmit="event.preventDefault(); loadPage(BASE_URL + 'admin/pages/finance_expenses.php?' + $(this).serialize());">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                            <select name="status" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:border-blue-400 focus:outline-none">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= ($filter_status === 'pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= ($filter_status === 'approved') ? 'selected' : '' ?>>Approved</option>
                                <option value="rejected" <?= ($filter_status === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
                            <select name="category_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:border-blue-400 focus:outline-none">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c->id ?>" <?= ($filter_category == $c->id) ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="pt-2 border-t border-gray-50 flex gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-xl text-xs font-bold transition">Apply</button>
                            <button type="button" onclick="loadPage(BASE_URL + 'admin/pages/finance_expenses.php')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-600 py-2 rounded-xl text-xs font-bold text-center transition">Clear</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-100">Date</th>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-100">Detail</th>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-100">Category</th>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-100">Amount</th>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-100 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($recent_expenses)): ?>
                            <tr><td colspan="5" class="p-8 text-center text-gray-400 font-bold">No expenses logged yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_expenses as $exp): 
                                $date_fmt = date("M d, Y h:i A", strtotime($exp->created_at));
                                $status_html = "";
                                if ($exp->status == 'pending') {
                                    $status_html = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-100" data-tippy-content="Awaiting Approval"><i class="bx bx-time"></i> Pending</span>';
                                } elseif ($exp->status == 'approved') {
                                    $status_html = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100"><i class="bx bx-check-double"></i> Approved</span>';
                                } else {
                                    $status_html = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-red-50 text-red-600 border border-red-100"><i class="bx bx-x"></i> Rejected</span>';
                                }

                                // Match categories to background colors randomly based on hash
                                $cat_hash = crc32($exp->category_name);
                                $cat_colors = ['bg-blue-50 text-blue-600', 'bg-purple-50 text-purple-600', 'bg-gray-100 text-gray-600', 'bg-orange-50 text-orange-600', 'bg-cyan-50 text-cyan-600'];
                                $cat_style = $cat_colors[$cat_hash % count($cat_colors)];
                            ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-xs font-semibold text-gray-500 whitespace-nowrap"><?= $date_fmt ?></td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-800 break-words"><?= htmlspecialchars($exp->title) ?></p>
                                    <p class="text-[11px] text-gray-500 truncate max-w-[200px]"><?= htmlspecialchars($exp->description ?? 'No description') ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="<?= $cat_style ?> text-[10px] font-bold px-2 py-1 rounded-md whitespace-nowrap"><?= htmlspecialchars($exp->category_name) ?></span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-800">₦<?= number_format($exp->amount, 2) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($exp->status == 'pending'): ?>
                                        <div class="flex flex-col items-end gap-1.5">
                                            <?= $status_html ?>
                                            <div class="flex items-center gap-1">
                                                <button onclick="updateExpenseStatus(<?= $exp->id ?>, 'approved')" class="size-6 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-md flex items-center justify-center transition border border-emerald-100" data-tippy-content="Approve Expense"><i class="bx bx-check text-sm font-bold"></i></button>
                                                <button onclick="updateExpenseStatus(<?= $exp->id ?>, 'rejected')" class="size-6 bg-red-50 text-red-600 hover:bg-red-100 rounded-md flex items-center justify-center transition border border-red-100" data-tippy-content="Reject Expense"><i class="bx bx-x text-sm font-bold"></i></button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?= $status_html ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-50 text-center">
                <button class="text-[11px] font-semibold tracking-widest text-gray-400 hover:text-blue-600 uppercase transition">View All Expenses <i class="bx bx-right-arrow-alt"></i></button>
            </div>
        </div>

        <!-- Breakdown Chart Widget -->
        <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-6 flex flex-col">
            <h3 class="text-lg font-bold text-gray-800 mb-6 border-b border-gray-50 pb-4">Spending by Category</h3>
            <?php if(count($chartData) > 0): ?>
                <div class="relative w-full aspect-square max-h-[250px] mx-auto mb-6 flex-1 flex items-center justify-center">
                    <canvas id="expenseCategoryChart"></canvas>
                </div>
                <div class="space-y-3 mt-auto">
                    <?php 
                    $total_c = array_sum($chartData);
                    foreach($chartLabels as $idx => $label): 
                        $pct = round(($chartData[$idx] / $total_c) * 100);
                        $col = $colors[$idx % count($colors)];
                    ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: <?= $col ?>;"></div>
                            <span class="text-xs font-bold text-gray-600"><?= htmlspecialchars($label) ?></span>
                        </div>
                        <span class="text-xs font-semibold text-gray-800"><?= $pct ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex-1 flex flex-col items-center justify-center text-center opacity-50 py-12">
                     <i class="bx bx-pie-chart text-6xl text-gray-300 mb-2"></i>
                     <p class="font-bold text-gray-500">No chart data</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Add Expense -->
<div id="logExpenseModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center overflow-y-auto p-4 transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden scale-100 transition-transform">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800">Record New Expense</h3>
            <button onclick="document.getElementById('logExpenseModal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-500 transition">
                <i class="bx bx-x text-xl"></i>
            </button>
        </div>
        <form id="recordExpenseForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Expense Title</label>
                    <input type="text" name="title" required placeholder="e.g. Printer Ink Replacement" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Description (Optional)</label>
                    <textarea name="description" rows="2" placeholder="Brief details about this expense..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
                        <select name="category_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                            <option value="">Select...</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Amount (₦)</label>
                        <input type="number" name="amount" required min="1" placeholder="e.g. 5000" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-red-600 focus:ring-2 focus:ring-red-100 focus:border-red-400 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Date Incurred</label>
                    <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('logExpenseModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" id="btnSubmitExpense" class="px-6 py-2.5 rounded-xl font-bold text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-200 transition">Submit for Approval</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Expense Category -->
<div id="addExpenseCategoryModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center overflow-y-auto p-4 transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden scale-100 transition-transform">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800">New Category</h3>
            <button onclick="document.getElementById('addExpenseCategoryModal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-500 transition">
                <i class="bx bx-x text-xl"></i>
            </button>
        </div>
        <form id="addExpenseCategoryForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Office Supplies" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addExpenseCategoryModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" id="btnSubmitCategory" class="px-6 py-2.5 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');

    // UI Create Category Submission
    $("#addExpenseCategoryForm").on("submit", function(e) {
        e.preventDefault();
        
        let fd = new FormData(this);
        let btn = $("#btnSubmitCategory");
        let ogText = btn.html();
        btn.html('<i class="bx bxs-loader-dots bx-spin"></i> Saving...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/finance_api.php?action=create_expense_category',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    document.getElementById('addExpenseCategoryModal').classList.add('hidden');
                    // Add new category directly to the UI dynamically instead of full reload if you want,
                    // but since a reload is cleaner and matches convention:
                    setTimeout(() => loadPage(BASE_URL + "admin/pages/finance_expenses.php"), 800);
                } else {
                    showAlert('error', res.message || 'An error occurred');
                    btn.html(ogText).prop('disabled', false);
                }
            },
            error: function() {
                showAlert('error', 'Network error occurred');
                btn.html(ogText).prop('disabled', false);
            }
        });
    });

    // UI Expense Submission
    $("#recordExpenseForm").on("submit", function(e) {
        e.preventDefault();
        
        let fd = new FormData(this);
        let btn = $("#btnSubmitExpense");
        let ogText = btn.html();
        btn.html('<i class="bx bxs-loader-dots bx-spin"></i> Submitting...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/finance_api.php?action=create_expense',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    document.getElementById('logExpenseModal').classList.add('hidden');
                    setTimeout(() => loadPage(BASE_URL + "admin/pages/finance_expenses.php"), 800);
                } else {
                    showAlert('error', res.message || 'An error occurred');
                    btn.html(ogText).prop('disabled', false);
                }
            },
            error: function() {
                showAlert('error', 'Network error occurred');
                btn.html(ogText).prop('disabled', false);
            }
        });
    });

    // Quick Action to update expense status
    window.updateExpenseStatus = function(expenseId, updatedStatus) {
        if(!confirm(`Are you sure you want to mark this expense as ${updatedStatus}?`)) return;

        $.ajax({
            url: BASE_URL + 'admin/auth/finance_api.php?action=update_expense_status',
            method: 'POST',
            data: { id: expenseId, status: updatedStatus },
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    setTimeout(() => loadPage(BASE_URL + "admin/pages/finance_expenses.php"), 800);
                } else {
                    showAlert('error', res.message || 'An error occurred');
                }
            },
            error: function() {
                showAlert('error', 'Network error occurred');
            }
        });
    };

    // Chart Rendering mapped directly to PHP JSON output
    <?php if(count($chartData) > 0): ?>
    if(typeof Chart !== 'undefined' && document.getElementById('expenseCategoryChart')) {
        const ctx = document.getElementById('expenseCategoryChart').getContext('2d');
        const cLabels = <?= json_encode($chartLabels) ?>;
        const cData = <?= json_encode($chartData) ?>;
        const cColors = <?= json_encode(array_slice($colors, 0, count($chartData))) ?>;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: cLabels,
                datasets: [{
                    data: cData,
                    backgroundColor: cColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
    <?php endif; ?>
</script>
