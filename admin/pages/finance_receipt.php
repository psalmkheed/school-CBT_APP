<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin', 'guardian'])) {
    echo "Access Denied.";
    exit;
}

$payment_id = intval($_GET['id'] ?? 0);

if ($payment_id <= 0) {
    echo "Invalid payment ID.";
    exit;
}

$stmt = $conn->prepare("
    SELECT p.*, f.student_id as student_reg_no, c.name as fee_name, c.academic_session, c.term, 
           u.first_name, u.surname, u.class
    FROM finance_payments p
    JOIN finance_student_fees f ON p.student_fee_id = f.id
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    JOIN users u ON f.student_id = u.user_id
    WHERE p.id = ?
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_OBJ);

if (!$payment) {
    echo "Payment record not found.";
    exit;
}

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
    <title>Payment Receipt - #<?= $payment->id ?></title>
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
            .print-container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
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
                <h2 class="text-xl font-semibold text-gray-800 uppercase tracking-widest">Official Receipt</h2>
                <p class="text-sm font-semibold text-gray-500 mt-1">Ref: <span class="text-gray-800"><?= htmlspecialchars($payment->reference_no ? $payment->reference_no : 'N/A') ?></span></p>
                <p class="text-sm font-semibold text-gray-500">Date: <span class="text-gray-800"><?= date('F d, Y - h:i A', strtotime($payment->created_at)) ?></span></p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase font-bold text-gray-400 mb-1">Receipt No.</p>
                <p class="text-2xl font-semibold text-blue-600">#<?= str_pad($payment->id, 5, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <!-- Student Info -->
        <div class="grid grid-cols-2 gap-8 mb-8 bg-gray-50 p-6 rounded-xl border border-gray-100">
            <div>
                <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase mb-1">Received From</p>
                <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($payment->first_name . ' ' . $payment->surname) ?></p>
                <p class="text-sm font-semibold text-gray-600 mt-1">ID: <?= htmlspecialchars($payment->student_reg_no) ?></p>
                <p class="text-sm font-semibold text-gray-600">Class: <?= htmlspecialchars($payment->class) ?></p>
            </div>
            <div class="text-right text-sm">
                 <p class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase mb-1">Payment Method</p>
                 <p class="font-bold text-gray-800 bg-white inline-block px-3 py-1 rounded shadow-sm border border-gray-100"><?= htmlspecialchars($payment->payment_method) ?></p>
            </div>
        </div>

        <!-- Payment Details Table -->
        <table class="w-full mb-8">
            <thead>
                <tr>
                    <th class="py-3 px-2 text-left text-xs font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-200">Description</th>
                    <th class="py-3 px-2 text-center text-xs font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-200">Term/Session</th>
                    <th class="py-3 px-2 text-right text-xs font-semibold tracking-widest text-gray-400 uppercase border-b border-gray-200">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-4 px-2 text-sm font-bold text-gray-800 border-b border-gray-100"><?= htmlspecialchars($payment->fee_name) ?></td>
                    <td class="py-4 px-2 text-sm text-center text-gray-600 font-semibold border-b border-gray-100"><?= htmlspecialchars($payment->term . ' / ' . $payment->academic_session) ?></td>
                    <td class="py-4 px-2 text-right text-lg font-semibold text-gray-800 border-b border-gray-100">₦<?= number_format($payment->amount, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end mb-12">
            <div class="w-1/2">
                <div class="flex items-center justify-between py-2 border-b-2 border-gray-800">
                    <span class="text-sm font-bold text-gray-600 uppercase">Total Paid</span>
                    <span class="text-2xl font-semibold text-gray-900">₦<?= number_format($payment->amount, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="flex justify-between items-end pt-12">
            <div class="text-center w-48">
                <div class="border-b border-gray-800 mb-2 mt-8"></div>
                <p class="text-xs font-bold text-gray-500 uppercase">Parent/Guardian Sign</p>
            </div>
            
            <!-- Cashier / School Sign -->
            <div class="text-center w-48 relative">
                <?php 
                    $sig_src = '';
                    if(!empty($signature_path)) {
                        $path = $signature_path;
                        if(strpos($path, 'http') !== 0) {
                            // Legacy handling
                            if (strpos($path, '/') === false) {
                                $path = 'uploads/signatures/' . $path; // Guessing legacy signature path
                            } elseif(strpos($path, 'uploads/') !== 0) {
                                $path = 'uploads/' . ltrim($path, '/');
                            }
                            $sig_src = APP_URL . $path;
                        } else {
                            $sig_src = $path;
                        }
                    }
                ?>
                <?php if (!empty($sig_src)): ?>
                    <img src="<?= htmlspecialchars($sig_src) ?>" class="h-16 object-contain absolute -top-12 left-1/2 transform -translate-x-1/2 z-0 mix-blend-multiply opacity-80" alt="Signature">
                <?php else: ?>
                    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2 text-blue-100 opacity-50 z-0">
                        <svg width="80" height="80" viewBox="0 0 100 100" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4" />
                            <text x="50" y="55" font-family="sans-serif" font-size="16" font-weight="bold" text-anchor="middle">PAID</text>
                        </svg>
                    </div>
                <?php endif; ?>
                <div class="border-b border-gray-800 mb-2 z-10 relative"></div>
                <p class="text-xs font-bold text-gray-500 uppercase z-10 relative">Bursar / Cashier</p>
            </div>
        </div>
        
        <p class="text-center text-[10px] text-gray-400 font-semibold mt-12 mb-0">Note: Fees are non-refundable. Please keep this receipt safe.</p>

        <!-- No Print Action Buttons -->
        <div class="no-print absolute top-4 right-4 flex gap-2">
            <button onclick="window.close()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-2 rounded-lg font-bold flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold shadow-lg shadow-blue-200 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-printer"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Print Receipt
            </button>
        </div>
    </div>
    <script>
        // Optional auto-print on load for ultimate speed
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
