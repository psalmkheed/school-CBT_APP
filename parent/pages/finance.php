<?php
require '../../connections/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guardian') {
    header("Location: {$base}auth/login.php");
    exit();
}

$guardian_id = $_SESSION['user_id'];
$student_internal_id = intval($_GET['id'] ?? 0);

// Verify this ward belongs to guardian
$check = $conn->prepare("SELECT id FROM guardian_wards WHERE guardian_id = ? AND student_id = ?");
$check->execute([$guardian_id, $student_internal_id]);

if ($check->rowCount() === 0) {
    echo "<h2 class='p-8 text-center text-red-500 font-bold'>Unauthorized Access to this student's records.</h2>";
    exit;
}

// Fetch Student Data
$stu_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stu_stmt->execute([$student_internal_id]);
$student = $stu_stmt->fetch(PDO::FETCH_OBJ);

// Fetch Outstanding Invoices
$stmt_fees = $conn->prepare("
    SELECT f.*, c.name as fee_name, c.academic_session, c.term
    FROM finance_student_fees f
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    WHERE f.student_id = ? AND f.status != 'paid'
");
$stmt_fees->execute([$student->user_id]);
$outstanding_fees = $stmt_fees->fetchAll(PDO::FETCH_OBJ);

// Fetch Payments
$pay_stmt = $conn->prepare("
    SELECT p.*, c.name as fee_name, c.academic_session, c.term, f.amount_due
    FROM finance_payments p
    JOIN finance_student_fees f ON p.student_fee_id = f.id
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    WHERE f.student_id = ?
    ORDER BY p.created_at DESC
");
$pay_stmt->execute([$student->user_id]);
$recent_payments = $pay_stmt->fetchAll(PDO::FETCH_OBJ);

$config_stmt = $conn->query("SELECT school_name, school_logo, school_primary, account_details FROM school_config LIMIT 1");
$config = $config_stmt->fetch(PDO::FETCH_OBJ);
?>
<div class="fadeIn w-full max-w-5xl mx-auto p-4 md:p-8 pb-20">
        
        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-8">
            <div class="flex items-center gap-4 w-full md:w-auto">
                <button onclick="goHome()" class="md:hidden w-12 h-12 shrink-0 rounded-2xl flex items-center justify-center text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all cursor-pointer border border-gray-100">
                    <i class="bx bx-arrow-left-stroke text-3xl"></i>
                </button>
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-semibold text-2xl shadow-inner border-2 border-white shrink-0">
                    <?= strtoupper(substr($student->first_name, 0, 1) . substr($student->surname, 0, 1)) ?>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 leading-tight"><?= htmlspecialchars($student->first_name . ' ' . $student->surname) ?></h1>
                    <p class="font-semibold text-gray-500 text-sm md:text-base">Class: <?= htmlspecialchars($student->class) ?></p>
                </div>
            </div>
        </div>

        <!-- OUTSTANDING FEES -->
        <h2 class="text-lg font-semibold tracking-widest text-gray-400 uppercase mb-4">Pending Invoices</h2>
        <?php if(empty($outstanding_fees)): ?>
            <div class="bg-white border border-gray-100 rounded-2xl p-8 text-center shadow-sm mb-12">
                <i class="bx bx-check-circle text-5xl text-emerald-400 mb-3 block"></i>
                <h3 class="text-lg font-bold text-gray-800">No Pending Fees</h3>
                <p class="text-sm font-medium text-gray-500 max-w-sm mx-auto">This student currently has no outstanding financial obligations.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php 
                $total_arrears = 0;
                foreach($outstanding_fees as $fee): 
                    $balance = $fee->amount_due - $fee->amount_paid;
                    $total_arrears += $balance;
                ?>
                <div class="bg-white border flex flex-col justify-between border-red-100 rounded-3xl p-6 shadow-sm shadow-red-50 hover:shadow-md transition">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <span class="bg-red-50 text-red-600 px-2 py-1 rounded text-[10px] font-semibold tracking-widest uppercase border border-red-100">Unpaid</span>
                            <span class="text-xs font-bold text-gray-400"><?= htmlspecialchars($fee->academic_session) ?></span>
                        </div>
                        <h3 class="font-bold text-gray-800 mb-1 leading-tight"><?= htmlspecialchars($fee->fee_name) ?></h3>
                        <p class="text-xs font-semibold text-gray-500"><?= htmlspecialchars($fee->term) ?></p>
                    </div>
                    
                    <div class="mt-6 border-t border-gray-100 pt-4 flex items-end justify-between">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Balance Due</p>
                            <p class="text-xl font-semibold text-red-600 leading-none mt-1">₦<?= number_format($balance, 2) ?></p>
                        </div>
                        <!-- Provide an easy option for parent to 'Pay Online' -->
                        <button onclick="showHowToPay()" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2 font-bold text-xs shadow-md shadow-indigo-200 transition">Pay Now</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Print Global Invoice -->
            <div class="bg-blue-50 border border-blue-100 rounded-3xl p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6 mb-12 shadow-inner">
                <div class="flex items-center gap-6">
                    <div class="bg-white size-16 rounded-2xl flex items-center justify-center text-blue-600 text-3xl shadow-sm">
                        <i class="bx bx-receipt"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Issue Global Invoice</h2>
                        <p class="font-medium text-gray-500 text-sm mt-1 max-w-sm">Generate a consolidated PDF document containing all current outstanding arrears for this ward.</p>
                    </div>
                </div>
                <div class="flex-shrink-0 text-center md:text-right">
                    <p class="text-xs uppercase font-bold text-gray-400 mb-1 tracking-widest">Total Arrears</p>
                    <p class="text-2xl font-semibold text-gray-900 mb-4">₦<?= number_format($total_arrears, 2) ?></p>
                    <button onclick="window.open('<?= $base ?>admin/pages/finance_invoice.php?student_id=<?= htmlspecialchars($student->user_id) ?>', '_blank', 'width=800,height=1000')" class="w-full bg-gray-900 hover:bg-black text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-gray-300 transition flex items-center justify-center gap-2">
                        <i class="bx bx-printer"></i> Print Statement
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- PAYMENT HISTORY -->
        <h2 class="text-lg font-semibold tracking-widest text-gray-400 uppercase mb-4">Payment History</h2>
        <?php if(empty($recent_payments)): ?>
            <div class="bg-white border border-gray-100 rounded-2xl p-8 text-center shadow-sm">
                <i class="bx bx-receipt text-5xl text-gray-200 mb-3 block"></i>
                <h3 class="text-lg font-bold text-gray-800">No Payments Built Yet</h3>
                <p class="text-sm font-medium text-gray-500">Record payments will appear down below.</p>
            </div>
        <?php else: ?>
            <div class="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest uppercase text-gray-400">Date/Ref</th>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest uppercase text-gray-400">Description</th>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest uppercase text-gray-400">Method</th>
                            <th class="px-6 py-4 text-[10px] font-semibold tracking-widest uppercase text-gray-400 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($recent_payments as $pay): ?>
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-800"><?= date('M d, Y', strtotime($pay->created_at)) ?></p>
                                <p class="text-[10px] font-mono text-gray-400 mt-0.5"><?= htmlspecialchars($pay->reference_no ?: 'NO-REF') ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($pay->fee_name) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-bold border border-gray-200"><?= htmlspecialchars($pay->payment_method) ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="text-sm font-semibold text-emerald-600">₦<?= number_format($pay->amount, 2) ?></p>
                                <button onclick="window.open('../admin/pages/finance_receipt.php?id=<?= $pay->id ?>', '_blank', 'width=800,height=1000')" class="text-[10px] font-bold text-blue-600 hover:underline mt-1">Get Receipt</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <!-- Modal: How to Pay -->
    <div id="howToPayModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-100 transform transition-all">
            <div class="px-6 py-5 bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-2xl bg-white/20 flex items-center justify-center border border-white/30">
                        <i class="bx bx-wallet text-white text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-white text-lg tracking-tight">How to Pay</h3>
                </div>
                <button onclick="document.getElementById('howToPayModal').classList.add('hidden')" class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center text-white/70 hover:text-white transition cursor-pointer"><i class="bx bx-x text-2xl"></i></button>
            </div>
            
            <div class="p-6">
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 mb-6 flex gap-3 items-start">
                    <i class="bx bx-info-circle text-amber-500 text-xl mt-0.5"></i>
                    <p class="text-[11px] font-bold text-amber-700 italic leading-relaxed">Please make payments using the details below and bring the physical bank teller or screenshot of the transfer to the school bursary for confirmation.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Bank Account Details</label>
                        <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 text-sm font-semibold text-gray-700 leading-relaxed whitespace-pre-line">
                            <?= !empty($config->account_details) ? htmlspecialchars($config->account_details) : "Account details not yet provided by the school.\n\nPlease contact the school administrator." ?>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button onclick="document.getElementById('howToPayModal').classList.add('hidden')" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3.5 rounded-xl shadow-lg shadow-gray-200 transition flex items-center justify-center gap-2">
                            <i class="bx bx-check-circle"></i> I Understand
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showHowToPay() {
            document.getElementById('howToPayModal').classList.remove('hidden');
        }
    </script>
