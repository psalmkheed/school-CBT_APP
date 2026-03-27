<?php
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo "Access Denied.";
    exit;
}

// Fetch Session based Time Machine Scope
$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// Fetch Revenue
$ytd_revenue_stmt = $conn->prepare("
    SELECT SUM(p.amount) 
    FROM finance_payments p
    JOIN finance_student_fees f ON p.student_fee_id = f.id
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    WHERE c.academic_session = :session AND c.term = :term
");
$ytd_revenue_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$gross_revenue = $ytd_revenue_stmt->fetchColumn() ?: 0;

// Fetch Expenses
$ytd_expenses_stmt = $conn->prepare("
    SELECT SUM(amount) 
    FROM finance_expenses 
    WHERE status = 'approved' AND academic_session = :session AND term = :term
");
$ytd_expenses_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$total_expenses = $ytd_expenses_stmt->fetchColumn() ?: 0;

$net_profit = $gross_revenue - $total_expenses;

// Cashflow Line Chart Data (Scoped dynamically to months in the active term/session)
$chart_labels = [];
$revenue_data = [];
$expense_data = [];

// Determine months that have activity in the term to populate the chart's X-axis
$months_stmt = $conn->prepare("
    SELECT DISTINCT DATE_FORMAT(t.activity_date, '%b %Y') as m_label, YEAR(t.activity_date) as y, MONTH(t.activity_date) as m
    FROM (
        SELECT p.created_at as activity_date 
        FROM finance_payments p
        JOIN finance_student_fees f ON p.student_fee_id = f.id
        JOIN finance_fee_categories c ON f.fee_category_id = c.id
        WHERE c.academic_session = :session1 AND c.term = :term1
        UNION
        SELECT expense_date as activity_date 
        FROM finance_expenses
        WHERE status = 'approved' AND academic_session = :session2 AND term = :term2
    ) as t
    ORDER BY y ASC, m ASC
    LIMIT 12
");
$months_stmt->execute([
    ':session1' => $active_session, ':term1' => $active_term,
    ':session2' => $active_session, ':term2' => $active_term
]);
$active_months = $months_stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($active_months)) {
    // Fallback if completely empty term
    $active_months[] = ['m_label' => date('M Y'), 'y' => date('Y'), 'm' => date('n')];
}

foreach ($active_months as $am) {
    $m = $am['m'];
    $y = $am['y'];
    
    $chart_labels[] = $am['m_label'];
    
    // Revenue for this active term's month
    $r_stmt = $conn->prepare("
        SELECT SUM(p.amount) FROM finance_payments p
        JOIN finance_student_fees f ON p.student_fee_id = f.id
        JOIN finance_fee_categories c ON f.fee_category_id = c.id
        WHERE c.academic_session = :session AND c.term = :term AND MONTH(p.created_at) = :m AND YEAR(p.created_at) = :y
    ");
    $r_stmt->execute([':session' => $active_session, ':term' => $active_term, ':m' => $m, ':y' => $y]);
    $revenue_data[] = $r_stmt->fetchColumn() ?: 0;
    
    // Expenses for this active term's month
    $e_stmt = $conn->prepare("SELECT SUM(amount) FROM finance_expenses WHERE status = 'approved' AND academic_session = :session AND term = :term AND MONTH(expense_date) = :m AND YEAR(expense_date) = :y");
    $e_stmt->execute([':session' => $active_session, ':term' => $active_term, ':m' => $m, ':y' => $y]);
    $expense_data[] = $e_stmt->fetchColumn() ?: 0;
}

// Top Debtors by Class
$debtors_stmt = $conn->prepare("
    SELECT u.class, COUNT(DISTINCT u.user_id) as unpaid_students, SUM(f.amount_due - f.amount_paid) as total_debt
    FROM users u
    JOIN finance_student_fees f ON u.user_id = f.student_id
    JOIN finance_fee_categories c ON f.fee_category_id = c.id
    WHERE u.role = 'student' AND f.status != 'paid' AND c.academic_session = :session AND c.term = :term
    GROUP BY u.class
    HAVING total_debt > 0
    ORDER BY total_debt DESC
    LIMIT 5
");
$debtors_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$top_debtors = $debtors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Unpaid Invoices / Pending Expenses
$pending_invoices_stmt = $conn->prepare("SELECT title, amount, created_at FROM finance_expenses WHERE status = 'pending' AND academic_session = :session AND term = :term ORDER BY created_at ASC LIMIT 4");
$pending_invoices_stmt->execute([':session' => $active_session, ':term' => $active_term]);
$pending_invoices = $pending_invoices_stmt->fetchAll(PDO::FETCH_ASSOC);

$period_label = "$active_session - $active_term";
?>

<div class="fadeIn w-full p-4 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
           <h1 class="text-2xl font-semibold text-gray-800">Financial Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Analytics, ledgers, and institutional cashflow</p>
        </div>
        <div class="flex items-center gap-3 relative">
            
            <div class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl font-bold shadow-sm flex items-center gap-2">
                <i class="bx bx-slider-alt text-xl text-emerald-600"></i> Scope: <?= $period_label ?>
            </div>

            <!-- Export Options Dropdown -->
            <div class="relative">
                <button onclick="document.getElementById('exportDropdown').classList.toggle('hidden');" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-200 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="bx bx-download text-xl"></i> Export Options
                </button>
                <div id="exportDropdown" class="hidden absolute top-full right-0 mt-2 w-48 bg-white border border-gray-100 rounded-xl shadow-xl z-50 py-2">
                    <button onclick="window.print()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 font-bold flex items-center gap-2"><i class="bx bx-printer text-lg"></i> Print Report</button>
                    <a href="auth/export_finance.php?format=pdf" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 font-bold flex items-center gap-2"><i class="bx bxs-file-pdf text-lg"></i> Export as PDF</a>
                    <a href="auth/export_finance.php?format=csv" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 font-bold flex items-center gap-2"><i class="bx bx-spreadsheet text-lg"></i> Export as CSV</a>
                    <a href="auth/export_finance.php?format=excel" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 font-bold flex items-center gap-2"><i class="bx bx-table text-lg"></i> Export as Excel</a>
                </div>
            </div>
        </div>
    </div>

    <!-- P&L Macro Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 text-9xl transform translate-x-1/4 -translate-y-1/4 text-white">
                <i class="bx bx-line-chart"></i>
            </div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1 relative z-10">Net Profit (<?= $period_label ?>)</p>
            <h3 class="text-3xl font-semibold text-white relative z-10 border-b border-gray-700 pb-4 mb-4">₦<?= number_format($net_profit, 2) ?></h3>
            <div class="flex items-center justify-between relative z-10">
                <span class="text-xs text-gray-400 font-bold">Health Status:</span>
                <span class="text-sm font-semibold <?= $net_profit >= 0 ? 'text-green-400' : 'text-red-400' ?>"><?= $net_profit >= 0 ? 'Surplus' : 'Deficit' ?></span>
            </div>
        </div>
        
        <div class="bg-white rounded-3xl p-6 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex flex-col justify-center">
            <div class="flex items-center justify-between mb-4">
                <div class="size-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="bx bx-wallet"></i>
                </div>
                <span class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Gross Revenue (<?= $period_label ?>)</span>
            </div>
            <h3 class="text-2xl font-semibold text-gray-800">₦<?= number_format($gross_revenue, 2) ?></h3>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 flex flex-col justify-center">
            <div class="flex items-center justify-between mb-4">
                <div class="size-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg">
                    <i class="bx bx-minus-circle"></i>
                </div>
                <span class="text-[10px] font-semibold tracking-widest text-gray-400 uppercase">Total Expenses (<?= $period_label ?>)</span>
            </div>
            <h3 class="text-2xl font-semibold text-gray-800">₦<?= number_format($total_expenses, 2) ?></h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Main Chart -->
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-6">Cashflow Analysis</h3>
            <div class="relative w-full h-[300px]">
                <canvas id="cashflowChart"></canvas>
            </div>
        </div>
        
        <!-- Debtors & Creditors Overview -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="bx bx-user-x text-red-500 text-lg"></i> Top Debtors (By Class)
                </h3>
                <div class="space-y-4">
                    <?php if(empty($top_debtors)): ?>
                        <p class="text-xs text-center text-gray-400 font-bold py-2">No outstanding student fees.</p>
                    <?php else: ?>
                        <?php foreach($top_debtors as $idx => $debtor): ?>
                        <div class="flex items-center justify-between <?= $idx < count($top_debtors)-1 ? 'border-b border-gray-50 pb-4' : '' ?>">
                            <div>
                                <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($debtor['class'] ?? 'Unassigned') ?> Block</p>
                                <p class="text-[10px] font-semibold text-gray-500"><?= number_format($debtor['unpaid_students']) ?> Students Unpaid</p>
                            </div>
                            <span class="text-sm font-semibold text-red-500">₦<?= number_format($debtor['total_debt']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="bx bx-store text-amber-500 text-lg"></i> Unpaid Expenditures (Pending)
                </h3>
                <div class="space-y-4">
                    <?php if (empty($pending_invoices)): ?>
                        <p class="text-xs text-center text-gray-400 font-bold py-2">All expenses are approved.</p>
                    <?php else: ?>
                        <?php foreach($pending_invoices as $inv): 
                            $days_waiting = floor((time() - strtotime($inv['created_at'])) / 86400);
                        ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="size-8 rounded-lg bg-amber-100 text-amber-600 flex flex-shrink-0 items-center justify-center font-bold">
                                <i class="bx bx-time-five"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-800 truncate"><?= htmlspecialchars($inv['title']) ?></p>
                                <p class="text-[10px] font-semibold <?= $days_waiting > 3 ? 'text-red-500' : 'text-gray-500' ?>">Waiting <?= $days_waiting ?> days</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-800 shrink-0">₦<?= number_format($inv['amount']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if(typeof tippy !== 'undefined') tippy('[data-tippy-content]');

    // Render Line Chart
    if(typeof Chart !== 'undefined' && document.getElementById('cashflowChart')) {
        const ctx = document.getElementById('cashflowChart').getContext('2d');
        const cLabels = <?= json_encode($chart_labels) ?>;
        const rData = <?= json_encode($revenue_data) ?>;
        const eData = <?= json_encode($expense_data) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: cLabels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: rData,
                        borderColor: '#10b981', // emerald-500
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: eData,
                        borderColor: '#ef4444', // red-500
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.4,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, boxWidth: 8, font: {family: 'Inter', weight: 'bold'} }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4], color: '#f3f4f6' },
                        ticks: {
                            callback: function(value) {
                                return '₦' + (value / 1000000).toFixed(1) + 'm';
                            },
                            font: {family: 'Inter', size: 10}
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: {family: 'Inter', size: 11, weight: 'bold'}, color: '#9ca3af' }
                    }
                }
            }
        });
    }
</script>
