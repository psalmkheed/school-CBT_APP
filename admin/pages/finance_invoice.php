<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin', 'guardian'])) {
    echo "Access Denied.";
    exit;
}

$student_id = $_GET['student_id'] ?? '';

if (empty($student_id)) {
    echo "Invalid student ID.";
    exit;
}

// Fetch Student Profile
$stmt_stu = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'student'");
$stmt_stu->execute([$student_id]);
$student = $stmt_stu->fetch(PDO::FETCH_OBJ);

if (!$student) {
    echo "Student record not found.";
    exit;
}

// Fetch Unpaid / Outstanding Fees
$stmt_fees = $conn->prepare("
    SELECT f.*, c.name as fee_name, c.academic_session, c.term
    FROM finance_student_fees f
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    WHERE f.student_id = ? AND f.status != 'paid'
");
$stmt_fees->execute([$student_id]);
$outstanding_fees = $stmt_fees->fetchAll(PDO::FETCH_OBJ);

// Fetch school details from settings
$school_name = "Zenith High School";
$school_address = "123 Education Way, Academic District";
$school_contact = "0800 ZENITH HS | info@zenithhigh.edu";
$school_logo = "";
$signature_path = "";

$config_stmt = $conn->query("SELECT * FROM school_config LIMIT 1");
$config = $config_stmt->fetch(PDO::FETCH_ASSOC);

if ($config) {
    if (!empty($config['school_name'])) $school_name = $config['school_name'];
    if (!empty($config['school_address'])) $school_address = $config['school_address'];
    
    $contact_parts = [];
    if (!empty($config['school_phone_number'])) $contact_parts[] = $config['school_phone_number'];
    if (!empty($config['school_email'])) $contact_parts[] = $config['school_email'];
    if (!empty($contact_parts)) {
        $school_contact = implode(' | ', $contact_parts);
    }

    if (!empty($config['school_logo'])) $school_logo = $config['school_logo'];
    if (!empty($config['signature'])) $signature_path = $config['signature'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding Invoice - <?= htmlspecialchars($student->first_name . ' ' . $student->surname) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        @media print {
            body { background-color: #ffffff; }
            .no-print { display: none !important; }
            .print-container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; border: none !important; }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen py-12">

    <div class="print-container bg-white w-full max-w-2xl mx-auto p-10 bg-white rounded-none md:rounded-2xl shadow-2xl relative border border-gray-100">
        
        <!-- Header -->
        <div class="border-b-2 border-gray-800 pb-6 mb-8 text-center flex flex-col items-center">
            <?php 
                $logo_src = '';
                if(!empty($school_logo)) {
                    $path = $school_logo;
                    if(strpos($path, 'http') !== 0) {
                        // Legacy handling: old logos didn't store folder paths
                        if (strpos($path, '/') === false) {
                            $path = 'uploads/school_logo/' . $path;
                        } elseif(strpos($path, 'uploads/') !== 0) {
                            $path = 'uploads/' . ltrim($path, '/');
                        }
                        $logo_src = APP_URL . $path;
                    } else {
                        $logo_src = $path;
                    }
                }
            ?>
            <?php if (!empty($logo_src)): ?>
                <img src="<?= htmlspecialchars($logo_src) ?>" alt="School Logo" class="h-20 object-contain mb-4">
            <?php else: ?>
                <!-- Simulated Logo shape -->
                <div class="w-16 h-16 bg-blue-600 rounded-xl mb-4 flex items-center justify-center transform rotate-3">
                    <div class="w-8 h-8 rounded-full border-4 border-white border-t-transparent"></div>
                </div>
            <?php endif; ?>
            <h1 class="text-3xl font-semibold text-gray-900 tracking-tight uppercase"><?= $school_name ?></h1>
            <p class="text-sm text-gray-600 font-semibold mt-1"><?= $school_address ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= $school_contact ?></p>
        </div>

        <div class="flex items-center justify-between mb-8 pb-8 border-b border-dashed border-gray-300">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 uppercase tracking-widest">Fee Invoice</h2>
                <p class="text-sm font-semibold text-gray-500 mt-1">Status: <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded uppercase text-[10px] tracking-wider border border-red-100 font-semibold ml-1">UNPAID</span></p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-500">Date Issued:</p>
                <p class="text-md font-bold text-gray-800"><?= date('F d, Y') ?></p>
            </div>
        </div>

        <!-- Student Info -->
        <div class="grid grid-cols-2 gap-8 mb-8 bg-gray-50 p-6 rounded-xl border border-gray-100">
            <div>
                <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase mb-1">Invoice To</p>
                <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($student->first_name . ' ' . $student->surname) ?></p>
                <p class="text-sm font-semibold text-gray-600 mt-1">ID: <?= htmlspecialchars($student->user_id) ?></p>
                <p class="text-sm font-semibold text-gray-600">Class: <?= htmlspecialchars($student->class) ?></p>
            </div>
            <div class="text-right text-sm">
                 <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase mb-1">Please Note</p>
                 <p class="font-bold text-gray-600 text-[11px] leading-relaxed max-w-[180px] ml-auto">Present this invoice at the cashier's desk or use the student ID for online bank transfers.</p>
            </div>
        </div>

        <!-- Fee Details Table -->
        <table class="w-full mb-8">
            <thead>
                <tr>
                    <th class="py-3 px-2 text-left text-xs font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-200">Description / Term</th>
                    <th class="py-3 px-2 text-center text-xs font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-200">Total Due</th>
                    <th class="py-3 px-2 text-center text-xs font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-200">Paid so far</th>
                    <th class="py-3 px-2 text-right text-xs font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-200">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $grand_total_due = 0;
                    $grand_total_paid = 0;
                    $grand_balance = 0;
                ?>
                <?php if(empty($outstanding_fees)): ?>
                <tr>
                    <td colspan="4" class="py-12 text-center text-gray-500 font-bold">No outstanding fees found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($outstanding_fees as $fee): ?>
                        <?php 
                            $balance = $fee->amount_due - $fee->amount_paid;
                            $grand_total_due += $fee->amount_due;
                            $grand_total_paid += $fee->amount_paid;
                            $grand_balance += $balance;
                        ?>
                        <tr>
                            <td class="py-4 px-2 border-b border-gray-100">
                                <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($fee->fee_name) ?></p>
                                <p class="text-xs font-medium text-gray-500 mt-0.5"><?= htmlspecialchars($fee->term . ' / ' . $fee->academic_session) ?></p>
                            </td>
                            <td class="py-4 px-2 text-sm text-center text-gray-600 font-semibold border-b border-gray-100">₦<?= number_format($fee->amount_due, 2) ?></td>
                            <td class="py-4 px-2 text-sm text-center text-emerald-600 font-semibold border-b border-gray-100">
                                ₦<?= number_format($fee->amount_paid, 2) ?>
                            </td>
                            <td class="py-4 px-2 text-right text-md font-semibold text-red-600 border-b border-gray-100">₦<?= number_format($balance, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-between items-end mb-12 border-t border-gray-300 pt-6">
            <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">Summary</div>
            <div class="w-1/2">
                <div class="flex items-center justify-between py-2 text-sm">
                    <span class="font-bold text-gray-500 uppercase">Gross Fee</span>
                    <span class="font-bold text-gray-900">₦<?= number_format($grand_total_due, 2) ?></span>
                </div>
                <div class="flex items-center justify-between py-2 text-sm border-b border-gray-200 mb-2">
                    <span class="font-bold text-gray-500 uppercase">Amount Paid</span>
                    <span class="font-bold text-emerald-600">- ₦<?= number_format($grand_total_paid, 2) ?></span>
                </div>
                <div class="flex items-center justify-between py-2 border-b-4 border-gray-800">
                    <span class="text-md font-semibold text-gray-800 uppercase">Total Outstanding</span>
                    <span class="text-2xl font-semibold text-red-600">₦<?= number_format($grand_balance, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Print Buttons -->
        <div class="flex gap-4 justify-center no-print mt-12 mb-4">
            <button onclick="window.close()" class="px-8 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors shadow">Close Window</button>
            <button onclick="window.print()" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Invoice
            </button>
        </div>
    </div>
</body>
</html>
