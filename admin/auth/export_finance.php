<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../connections/db.php';
require __DIR__ . '/../../auth/check.php';

use Mpdf\Mpdf;

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    die("Unauthorized Access");
}

$format = $_GET['format'] ?? 'csv';
$period = $_GET['period'] ?? 'this_term';

$where_payment = "1=1";
$where_expense = "status = 'approved'";

if ($period === 'this_year') {
    $where_payment .= " AND YEAR(created_at) = YEAR(CURRENT_DATE)";
    $where_expense .= " AND YEAR(expense_date) = YEAR(CURRENT_DATE)";
    $period_label = 'Year-to-Date (' . date('Y') . ')';
} elseif ($period === 'last_term') {
    $where_payment .= " AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 MONTH) AND created_at < DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)";
    $where_expense .= " AND expense_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 MONTH) AND expense_date < DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)";
    $period_label = 'Last Term';
} elseif ($period === 'all_time') {
    $period_label = 'All Time';
} else {
    $where_payment .= " AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 4 MONTH)";
    $where_expense .= " AND expense_date >= DATE_SUB(CURRENT_DATE, INTERVAL 4 MONTH)";
    $period_label = 'This Term';
}

// Fetch unified ledger
$query = "
    SELECT 'Revenue' as type, CONCAT('Fee Payment - Ref: ', reference_no) as description, amount, created_at as trans_date 
    FROM finance_payments 
    WHERE $where_payment

    UNION ALL

    SELECT 'Expense' as type, title as description, amount, expense_date as trans_date 
    FROM finance_expenses 
    WHERE $where_expense

    ORDER BY trans_date DESC
";

$stmt = $conn->query($query);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_revenue = 0;
$total_expense = 0;
foreach ($transactions as $t) {
    if ($t['type'] === 'Revenue') $total_revenue += $t['amount'];
    if ($t['type'] === 'Expense') $total_expense += $t['amount'];
}
$net_profit = $total_revenue - $total_expense;

if ($format === 'csv' || $format === 'excel') {
    $filename = "Financial_Report_{$period_label}_" . date('Ymd_His');
    
    if ($format === 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename.xls\"");
        
        echo "<table border='1'>";
        echo "<tr><th colspan='4'>Financial Report - $period_label</th></tr>";
        echo "<tr><th>Date</th><th>Type</th><th>Description</th><th>Amount (NGN)</th></tr>";
        foreach ($transactions as $row) {
            $date = date('M d, Y', strtotime($row['trans_date']));
            echo "<tr>";
            echo "<td>" . htmlspecialchars($date) . "</td>";
            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . number_format($row['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "<tr><th colspan='3' align='right'>Total Revenue</th><th>" . number_format($total_revenue, 2) . "</th></tr>";
        echo "<tr><th colspan='3' align='right'>Total Expense</th><th>" . number_format($total_expense, 2) . "</th></tr>";
        echo "<tr><th colspan='3' align='right'>Net Profit</th><th>" . number_format($net_profit, 2) . "</th></tr>";
        echo "</table>";
    } else {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"$filename.csv\"");
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ["Financial Report - $period_label"]);
        fputcsv($output, []);
        fputcsv($output, ['Date', 'Type', 'Description', 'Amount (NGN)']);
        
        foreach ($transactions as $row) {
            fputcsv($output, [
                date('M d, Y', strtotime($row['trans_date'])), 
                $row['type'], 
                $row['description'], 
                $row['amount']
            ]);
        }
        fputcsv($output, []);
        fputcsv($output, ['','Total Revenue', $total_revenue]);
        fputcsv($output, ['','Total Expense', $total_expense]);
        fputcsv($output, ['','Net Profit', $net_profit]);
        fclose($output);
    }
    exit;
} elseif ($format === 'pdf') {
    // Generate PDF
    $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_top' => 20]);
    $html = '
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #1e3a8a; margin: 0; }
        .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; color: #374151; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-box { background-color: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid #e2e8f0; }
        .summary-title { font-weight: bold; margin-bottom: 10px; font-size: 14px; }
        .revenue-text { color: #10b981; }
        .expense-text { color: #ef4444; }
        .profit-text { ' . ($net_profit >= 0 ? 'color: #10b981;' : 'color: #ef4444;') . ' font-weight: bold; }
    </style>
    
    <div class="header">
        <h1 class="title">Financial Report</h1>
        <p class="subtitle">Period: ' . htmlspecialchars($period_label) . ' | Generated on: ' . date('M d, Y h:i A') . '</p>
    </div>
    
    <div class="summary-box">
        <div class="summary-title">Financial Summary</div>
        <table style="margin-top:0;">
            <tr>
                <th>Gross Revenue</th>
                <td class="text-right revenue-text">NGN ' . number_format($total_revenue, 2) . '</td>
            </tr>
            <tr>
                <th>Total Expenses</th>
                <td class="text-right expense-text">NGN ' . number_format($total_expense, 2) . '</td>
            </tr>
            <tr>
                <th>Net Profit / Loss</th>
                <td class="text-right profit-text">NGN ' . number_format($net_profit, 2) . '</td>
            </tr>
        </table>
    </div>
    
    <h3 style="margin-top: 30px; font-size: 16px;">Ledger Transactions</h3>
    <table>
        <thead>
            <tr>
                <th width="15%">Date</th>
                <th width="15%">Type</th>
                <th width="50%">Description</th>
                <th width="20%" class="text-right">Amount (NGN)</th>
            </tr>
        </thead>
        <tbody>';
        
    if (empty($transactions)) {
        $html .= '<tr><td colspan="4" class="text-center" style="padding: 20px;">No transactions found for this period.</td></tr>';
    } else {
        foreach ($transactions as $t) {
            $html .= '<tr>
                <td>' . date('M d, Y', strtotime($t['trans_date'])) . '</td>
                <td><span class="' . ($t['type'] == 'Revenue' ? 'revenue-text' : 'expense-text') . '">' . htmlspecialchars($t['type']) . '</span></td>
                <td>' . htmlspecialchars($t['description']) . '</td>
                <td class="text-right">' . number_format($t['amount'], 2) . '</td>
            </tr>';
        }
    }
        
    $html .= '</tbody>
    </table>
    ';

    $mpdf->WriteHTML($html);
    $mpdf->Output('Financial_Report_'.$period_label.'.pdf', 'I');
    exit;
} else {
    echo "Invalid format specified.";
}
?>
