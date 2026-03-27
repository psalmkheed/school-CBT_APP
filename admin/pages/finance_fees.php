<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

// Fetch basic parameters
$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Fetch all fee categories
$stmt = $conn->prepare("SELECT * FROM finance_fee_categories WHERE academic_session = :session AND term = :term ORDER BY created_at DESC");
$stmt->execute([':session' => $active_session, ':term' => $active_term]);
$categories = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get total students
$student_count_stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $student_count_stmt->fetchColumn();

// Fetch student balances (Aggregation)
// Note: We sum amount_due and amount_paid to get total outstanding.
$balances_stmt = $conn->prepare("
    SELECT 
        users.id,
        users.first_name, 
        users.surname, 
        users.user_id as student_id,
        users.class,
        SUM(sf.amount_due) as total_due,
        SUM(sf.amount_paid) as total_paid
    FROM users 
    JOIN finance_student_fees sf ON sf.student_id = users.user_id
    JOIN finance_fee_categories fc ON sf.fee_category_id = fc.id
    WHERE users.role = 'student' AND fc.academic_session = :session AND fc.term = :term
    GROUP BY users.id
    ORDER BY total_due DESC
");
$balances_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$student_balances = $balances_stmt->fetchAll(PDO::FETCH_OBJ);

// Calculate Summary stats
$total_expected = 0;
$total_collected = 0;
$pending_students = 0;

foreach ($student_balances as $b) {
    if ($b->total_due > 0) {
        $total_expected += $b->total_due;
        $total_collected += $b->total_paid;
        if ($b->total_due > $b->total_paid) {
            $pending_students++;
        }
    }
}
$outstanding_fees = $total_expected - $total_collected;
$collection_rate = $total_expected > 0 ? round(($total_collected / $total_expected) * 100) : 0;

// Fetch Payment history
$history_stmt = $conn->prepare('
    SELECT p.*, f.student_id, c.name as fee_name, u.first_name, u.surname, u.class
    FROM finance_payments p
    JOIN finance_student_fees f ON p.student_fee_id = f.id
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    JOIN users u ON f.student_id = u.user_id
    WHERE c.academic_session = :session AND c.term = :term
    ORDER BY p.created_at DESC
    LIMIT 50
');
$history_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$payment_history = $history_stmt->fetchAll(PDO::FETCH_OBJ);

$class_stmt = $conn->prepare('SELECT * FROM class ORDER BY class ASC');

$class_stmt->execute();

$class_result = $class_stmt->fetchAll(PDO:: FETCH_OBJ);

?>

<div class="fadeIn w-full p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Manage Fees</h1>
            <p class="text-sm text-gray-500 mt-1">Configure and monitor student tuition and payments</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('issueInvoiceModal').classList.remove('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl font-bold shadow-sm transition-all flex items-center gap-2">
                <i class="bx bx-receipt text-xl"></i> Issue Invoice
            </button>
            <button onclick="document.getElementById('addFeeCategoryModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-200 transition-all flex items-center gap-2">
                <i class="bx bx-plus-circle text-xl"></i> Add Fee Category
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-3xl p-6 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 size-24 bg-blue-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <div class="size-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl flex-shrink-0 z-10 shadow-inner">
                <i class="bx bx-wallet"></i>
            </div>
            <div class="z-10">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Expected Revenue</p>
                <h3 class="text-2xl font-semibold text-gray-800">₦<?= number_format($total_expected, 2) ?></h3>
            </div>
        </div>
        <div class="bg-white rounded-3xl p-6 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 size-24 bg-emerald-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <div class="size-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl flex-shrink-0 z-10 shadow-inner">
                <i class="bx bx-badge-check"></i>
            </div>
            <div class="z-10 w-full pr-4">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Collected</p>
                <h3 class="text-2xl font-semibold text-gray-800">₦<?= number_format($total_collected, 2) ?></h3>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?= $collection_rate ?>%"></div>
                </div>
                <p class="text-[10px] font-semibold text-gray-400 mt-1"><?= $collection_rate ?>% Collection Rate</p>
            </div>
        </div>
        <div class="bg-white rounded-3xl p-6 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex items-center gap-5 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 size-24 bg-red-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <div class="size-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl flex-shrink-0 z-10 shadow-inner">
                <i class="bx bx-shield-circle"></i>
            </div>
            <div class="z-10">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Outstanding Fees</p>
                <h3 class="text-2xl font-semibold text-gray-800">₦<?= number_format($outstanding_fees, 2) ?></h3>
                 <p class="text-[10px] font-bold text-red-500 mt-1 bg-red-50 inline-block px-2 py-0.5 rounded-md"><?= number_format($pending_students) ?> Students pending</p>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden">
        <!-- Tabs -->
        <div class="flex border-b border-gray-100 bg-gray-50/50 px-6 pt-4">
            <button onclick="switchTab('balances')" id="tab-balances" class="tab-btn px-6 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600 flex items-center gap-2">
                <i class="bx bx-group text-lg"></i> Student Balances
            </button>
            <button onclick="switchTab('categories')" id="tab-categories" class="tab-btn px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 transition flex items-center gap-2 border-b-2 border-transparent">
                <i class="bx bx-categories text-lg"></i> Fee Categories
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-btn px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 transition flex items-center gap-2 border-b-2 border-transparent">
                <i class="bx bx-history text-lg"></i> Payment History
            </button>
        </div>

        <!-- TAB CONTENT: Student Balances -->
        <div id="panel-balances" class="tab-panel flex flex-col">

        <!-- Table Header & Filters -->
        <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative w-full md:w-96">
                <i class="bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" id="filterSearch" placeholder="Search by student name or ID..." class="w-full pl-11 pr-4 py-3 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-100 transition-all font-medium">
            </div>
            <div class="flex gap-3">
                <select id="filterClass" class="bg-gray-50 border-none px-4 py-3 rounded-2xl text-sm font-bold text-gray-600 focus:ring-2 focus:ring-blue-100">
                    <option value="">All Classes</option>
                    <?php 
                        $classes_stmt = $conn->query("SELECT DISTINCT class FROM users WHERE role = 'student' AND class IS NOT NULL AND class != '' ORDER BY class");
                        while($c = $classes_stmt->fetchColumn()) {
                            echo "<option value=\"".htmlspecialchars($c)."\">".htmlspecialchars($c)."</option>";
                        }
                    ?>
                </select>
                <select id="filterStatus" class="bg-gray-50 border-none px-4 py-3 rounded-2xl text-sm font-bold text-gray-600 focus:ring-2 focus:ring-blue-100">
                    <option value="">All Statuses</option>
                    <option value="paid">Paid Fully</option>
                    <option value="partial">Partial Payment</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </div>
        </div>

        <!-- Table (Mock Data) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Student</th>
                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Class</th>
                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Total Due</th>
                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Amount Paid</th>
                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-semibold tracking-widest text-gray-400 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(empty($student_balances)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400 font-medium">No fee allocations found for any student yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($student_balances as $bal): 
                            $initials = strtoupper(substr($bal->first_name, 0, 1) . substr($bal->surname, 0, 1));
                            $colors = ['bg-blue-100 text-blue-600', 'bg-green-100 text-green-600', 'bg-purple-100 text-purple-600', 'bg-orange-100 text-orange-600', 'bg-pink-100 text-pink-600'];
                            $randomColor = $colors[$bal->id % count($colors)];

                            $status = 'unpaid';
                            $status_class = 'bg-red-50 text-red-600 border-red-100';
                            $status_icon = 'bx-x-circle';
                            $status_text = 'Unpaid';

                            if ($bal->total_paid >= $bal->total_due) {
                                $status = 'paid';
                                $status_class = 'bg-emerald-50 text-emerald-600 border-emerald-100';
                                $status_icon = 'bx-check-circle';
                                $status_text = 'Paid';
                            } elseif ($bal->total_paid > 0) {
                                $status = 'partial';
                                $status_class = 'bg-amber-50 text-amber-600 border-amber-100';
                                $status_icon = 'bx-loader-circle';
                                $status_text = 'Partial';
                            }
                        ?>
                        <tr class="balance-row hover:bg-gray-50/50 transition-colors" 
                            data-name="<?= htmlspecialchars(strtolower($bal->first_name . ' ' . $bal->surname)) ?>"
                            data-studentid="<?= htmlspecialchars(strtolower($bal->student_id)) ?>"
                            data-class="<?= htmlspecialchars($bal->class) ?>"
                            data-status="<?= $status ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full <?= $randomColor ?> flex items-center justify-center font-bold shrink-0 text-sm"><?= $initials ?></div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($bal->first_name . ' ' . $bal->surname) ?></p>
                                        <p class="text-[11px] text-gray-500 font-medium"><?= htmlspecialchars($bal->student_id) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-600"><?= htmlspecialchars($bal->class) ?></td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-800">₦<?= number_format($bal->total_due, 2) ?></td>
                            <td class="px-6 py-4 text-sm font-bold <?= $status === 'paid' ? 'text-emerald-600' : ($status === 'partial' ? 'text-amber-500' : 'text-gray-400') ?>">
                                ₦<?= number_format($bal->total_paid, 2) ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold <?= $status_class ?> border">
                                    <i class="bx <?= $status_icon ?>"></i> <?= $status_text ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if($status !== 'paid'): ?>
                                <button onclick="sendReminder('<?= htmlspecialchars($bal->student_id) ?>')" class="text-gray-400 hover:text-red-600 transition p-2 bg-gray-50 hover:bg-red-50 rounded-lg" data-tippy-content="Send Reminder">
                                    <i class="bx bx-bell text-lg"></i>
                                </button>
                                <?php endif; ?>
                                <button onclick="window.open(BASE_URL + 'admin/pages/finance_invoice.php?student_id=<?= htmlspecialchars($bal->student_id) ?>', '_blank', 'width=800,height=1000')" class="text-gray-400 hover:text-indigo-600 transition p-2 bg-gray-50 hover:bg-indigo-50 rounded-lg" data-tippy-content="Print Invoice">
                                    <i class="bx bx-printer text-lg"></i>
                                </button>
                                <button onclick="openPaymentModal('<?= htmlspecialchars($bal->student_id) ?>', '<?= addslashes(htmlspecialchars($bal->first_name . ' ' . $bal->surname)) ?>')" class="text-gray-400 hover:text-blue-600 transition p-2 bg-gray-50 hover:bg-blue-50 rounded-lg" data-tippy-content="Log Payment">
                                    <i class="bx bx-wallet-alt text-lg"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        </div> <!-- End Panel Balances -->

        <!-- TAB CONTENT: Fee Categories -->
        <div id="panel-categories" class="tab-panel hidden flex-col">
            <div class="overflow-x-auto p-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 rounded-xl">
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Category Name</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Amount</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Target</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Session/Term</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if(empty($categories)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-400 font-medium">No fee categories created for this term.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($categories as $cat): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-4 py-3 font-bold text-gray-700"><?= $cat->name ?></td>
                                <td class="px-4 py-3 font-semibold text-gray-900">₦<?= number_format($cat->amount, 2) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-xs font-bold"><?= $cat->assigned_class ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500"><?= $cat->academic_session ?> <span class="text-gray-300 mx-1">|</span> <?= $cat->term ?></td>
                                <td class="px-4 py-3 text-right">
                                    <button onclick="editFeeCategory(<?= htmlspecialchars(json_encode($cat)) ?>)" class="text-blue-400 hover:text-blue-600 transition bg-blue-50 p-2 rounded-lg mr-2" data-tippy-content="Edit"><i class="bx bx-edit"></i></button>
                                    <button class="text-red-400 hover:text-red-600 transition bg-red-50 p-2 rounded-lg" data-tippy-content="Delete"><i class="bx bx-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div> <!-- End Panel Categories -->
        
        <!-- TAB CONTENT: Payment History -->
        <div id="panel-history" class="tab-panel hidden flex-col">
            <div class="overflow-x-auto p-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 rounded-xl">
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Date & Ref</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Student</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Fee Allocation</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Method</th>
                            <th class="px-4 py-3 text-[10px] font-semibold tracking-widest text-gray-400 uppercase text-right">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if(empty($payment_history)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-400 font-medium">No payment history yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($payment_history as $ph): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-4 py-3 border-b border-gray-50">
                                    <p class="text-sm font-bold text-gray-800"><?= date('M d, Y', strtotime($ph->created_at)) ?></p>
                                    <p class="text-[10px] text-gray-500 font-mono mt-0.5"><?= htmlspecialchars($ph->reference_no ? $ph->reference_no : 'NO-REF') ?></p>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-50">
                                    <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($ph->first_name . ' ' . $ph->surname) ?></p>
                                    <p class="text-[11px] text-gray-500"><?= htmlspecialchars($ph->student_id) ?> &bull; <?= htmlspecialchars($ph->class) ?></p>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-50">
                                    <span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-[10px] font-bold"><?= htmlspecialchars($ph->fee_name) ?></span>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-50 text-sm font-semibold text-gray-600">
                                    <?= htmlspecialchars($ph->payment_method) ?>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-50 text-right">
                                    <p class="text-sm font-semibold text-emerald-600">₦<?= number_format($ph->amount, 2) ?></p>
                                    <button class="mt-1 text-[10px] font-bold text-gray-400 hover:text-blue-600 transition flex items-center justify-end gap-1 w-full" onclick="window.open(BASE_URL + 'admin/pages/finance_receipt.php?id=<?= $ph->id ?>', '_blank', 'width=800,height=1000')">
                                        <i class="bx bx-receipt"></i> Print Receipt
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Add Fee Category -->
<div id="addFeeCategoryModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center overflow-y-auto p-4 transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden scale-100 transition-transform">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800">Add New Fee Category</h3>
            <button onclick="document.getElementById('addFeeCategoryModal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-500 transition">
                <i class="bx bx-x text-xl"></i>
            </button>
        </div>
        <form id="createFeeCategoryForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Fee Name / Title</label>
                    <input type="text" name="name" required placeholder="e.g. 1st Term Tuition Fee" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Amount (₦)</label>
                    <input type="number" name="amount" required min="1" placeholder="e.g. 45000" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Target Class</label>
                        <select name="assigned_class" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                            <option value="" disabled selected>-- Select Option --</option>
                            <option value="All">ALL</option>
                            <?php foreach($class_result as $cr) : ?>
                            
                            <option value="<?= $cr->class ?>"><?= strtoupper($cr->class) ?></option>
                            <?php endforeach ?>
                            
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Term</label>
                        <input type="text" name="term" required readonly value="<?= $active_term ?>" class="w-full px-4 py-3 bg-gray-200 border border-gray-200 rounded-xl text-sm font-bold text-gray-500 cursor-not-allowed">
                    </div>
                </div>
                <input type="hidden" name="academic_session" value="<?= $active_session ?>">
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addFeeCategoryModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" id="btnSubmitCategory" class="px-6 py-2.5 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Fee Category -->
<div id="editFeeCategoryModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center overflow-y-auto p-4 transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden scale-100 transition-transform">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800">Edit Fee Category</h3>
            <button onclick="document.getElementById('editFeeCategoryModal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-500 transition">
                <i class="bx bx-x text-xl"></i>
            </button>
        </div>
        <form id="editFeeCategoryForm" class="p-6">
            <input type="hidden" name="id" id="edit_category_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Fee Name / Title</label>
                    <input type="text" name="name" id="edit_category_name" required placeholder="e.g. 1st Term Tuition Fee" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Amount (₦)</label>
                    <input type="number" name="amount" id="edit_category_amount" required min="1" placeholder="e.g. 45000" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Target Class</label>
                    <select name="assigned_class" id="edit_category_class" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                        <option value="" disabled selected>-- Select Option --</option>
                        <option value="All" >ALL</option>
                        <?php foreach ($class_result as $cr): ?>
                        
                            <option value="<?= $cr->class ?>">
                                <?= strtoupper($cr->class) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editFeeCategoryModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" id="btnUpdateCategory" class="px-6 py-2.5 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Log Payment -->
<div id="logPaymentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center overflow-y-auto p-4 transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden scale-100 transition-transform">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800">Log Payment</h3>
            <button onclick="document.getElementById('logPaymentModal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-500 transition">
                <i class="bx bx-x text-xl"></i>
            </button>
        </div>
        <form id="logPaymentForm" class="p-6">
            <input type="hidden" name="student_id" id="payment_student_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Student Name</label>
                    <input type="text" id="payment_student_name" readonly class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Amount Paid (₦)</label>
                    <input type="number" name="amount" required min="1" placeholder="e.g. 25000" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Payment Method</label>
                    <select name="payment_method" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="POS">POS Terminal</option>
                        <option value="Online Gatewy">Online Gateway</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Reference / Teller No. (Optional)</label>
                    <input type="text" name="reference_no" placeholder="e.g. TR-2983749" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all font-medium">
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('logPaymentModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" id="btnSubmitPayment" class="px-6 py-2.5 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Log Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Issue Invoice -->
<div id="issueInvoiceModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[999] hidden flex items-center justify-center overflow-y-auto p-4 transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden scale-100 transition-transform">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800">Issue Invoice</h3>
            <button onclick="document.getElementById('issueInvoiceModal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-500 transition">
                <i class="bx bx-x text-xl"></i>
            </button>
        </div>
        <form id="issueInvoiceForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5">Select Student</label>
                    <select name="student_id" id="invoice_selected_student" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                        <option value="">-- Choose a Student --</option>
                        <?php foreach($student_balances as $stu): ?>
                            <option value="<?= htmlspecialchars($stu->student_id) ?>"><?= htmlspecialchars($stu->first_name . ' ' . $stu->surname . ' (' . $stu->student_id . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('issueInvoiceModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="button" onclick="generateGlobalInvoice()" class="px-6 py-2.5 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition flex items-center gap-2">
                    <i class="bx bx-printer"></i> Print Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function generateGlobalInvoice() {
        const student = document.getElementById('invoice_selected_student').value;
        if (!student) {
            Swal.fire('Ooops', 'Please select a student first.', 'info');
            return;
        }
        document.getElementById('issueInvoiceModal').classList.add('hidden');
        window.open(BASE_URL + 'admin/pages/finance_invoice.php?student_id=' + student, '_blank', 'width=800,height=1000');
    }

    function sendReminder(studentId) {
        Swal.fire({
            title: "Send Reminder?",
            text: "This will push a notification to this student's dashboard regarding their outstanding balance.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#10b981",
            confirmButtonText: "Yes, send reminder"
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/auth/finance_api.php?action=send_reminder', { student_id: studentId }, function(res) {
                    if (res.status == 'success') {
                        showAlert('success', res.message);
                    } else {
                        showAlert('error', res.message);
                    }
                });
            }
        });
    }

    function editFeeCategory(cat) {
        document.getElementById('edit_category_id').value = cat.id;
        document.getElementById('edit_category_name').value = cat.name;
        document.getElementById('edit_category_amount').value = cat.amount;
        document.getElementById('edit_category_class').value = cat.assigned_class;
        document.getElementById('editFeeCategoryModal').classList.remove('hidden');
    }

    function openPaymentModal(studentId, studentName) {
        document.getElementById('payment_student_id').value = studentId;
        document.getElementById('payment_student_name').value = studentName;
        document.getElementById('logPaymentModal').classList.remove('hidden');
    }

    // Tab Switching Logic
    function switchTab(tabId) {
        // Hide all panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.add('hidden');
            panel.classList.remove('flex');
        });
        
        // Reset all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('text-blue-600', 'border-blue-600');
            btn.classList.add('text-gray-500', 'border-transparent');
        });

        // Show active panel
        document.getElementById('panel-' + tabId).classList.remove('hidden');
        document.getElementById('panel-' + tabId).classList.add('flex');
        
        // Activate button
        const activeBtn = document.getElementById('tab-' + tabId);
        activeBtn.classList.remove('text-gray-500', 'border-transparent');
        activeBtn.classList.add('text-blue-600', 'border-blue-600');
    }

    // Modal Form Submission for Fee Category
    $("#createFeeCategoryForm").on("submit", function(e) {
        e.preventDefault();
        
        let fd = new FormData(this);
        let btn = $("#btnSubmitCategory");
        let ogText = btn.html();
        btn.html('<i class="bx bxs-loader-dots bx-spin"></i> Creating...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/finance_api.php?action=create_fee_category',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    document.getElementById('addFeeCategoryModal').classList.add('hidden');
                    // Reload the page to refresh the table
                    setTimeout(() => loadPage(BASE_URL + "admin/pages/finance_fees.php"), 800);
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

    // Modal Form Submission for Editing Fee Category
    $("#editFeeCategoryForm").on("submit", function(e) {
        e.preventDefault();
        
        let fd = new FormData(this);
        let btn = $("#btnUpdateCategory");
        let ogText = btn.html();
        btn.html('<i class="bx bxs-loader-dots bx-spin"></i> Saving...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/finance_api.php?action=edit_fee_category',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    document.getElementById('editFeeCategoryModal').classList.add('hidden');
                    setTimeout(() => loadPage(BASE_URL + "admin/pages/finance_fees.php"), 800);
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

    // Modal Form Submission for Logging Payment
    $("#logPaymentForm").on("submit", function(e) {
        e.preventDefault();
        
        let fd = new FormData(this);
        let btn = $("#btnSubmitPayment");
        let ogText = btn.html();
        btn.html('<i class="bx bxs-loader-dots bx-spin"></i> Processing...').prop('disabled', true);

        $.ajax({
            url: BASE_URL + 'admin/auth/finance_api.php?action=log_payment',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if(res.status === 'success') {
                    showAlert('success', res.message);
                    document.getElementById('logPaymentModal').classList.add('hidden');
                    setTimeout(() => loadPage(BASE_URL + "admin/pages/finance_fees.php"), 800);
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

    // Initialize tooltips if tippy is loaded globally
    if(typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]');
    }
    // Filtering Logic for Balances
    function filterBalances() {
        const query = document.getElementById('filterSearch').value.toLowerCase();
        const classFilter = document.getElementById('filterClass').value;
        const statusFilter = document.getElementById('filterStatus').value;

        document.querySelectorAll('.balance-row').forEach(row => {
            const name = row.dataset.name;
            const studentId = row.dataset.studentid;
            const rowClass = row.dataset.class;
            const rowStatus = row.dataset.status;

            const matchesSearch = name.includes(query) || studentId.includes(query);
            const matchesClass = (classFilter === "" || rowClass === classFilter);
            const matchesStatus = (statusFilter === "" || rowStatus === statusFilter);

            if (matchesSearch && matchesClass && matchesStatus) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }

    document.getElementById('filterSearch').addEventListener('input', filterBalances);
    document.getElementById('filterClass').addEventListener('change', filterBalances);
    document.getElementById('filterStatus').addEventListener('change', filterBalances);
</script>
