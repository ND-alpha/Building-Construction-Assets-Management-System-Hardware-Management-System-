<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 Authentication Check
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 🔌 Database Connection
$conn = new mysqli("localhost", "root", "", "hardware");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Default Worker Rate
$worker_rate = 10.0;   

$settings_query = "SELECT worker_rate FROM system_settings WHERE id = 1 LIMIT 1";
$settings_result = $conn->query($settings_query);
if ($settings_result && $settings_result->num_rows > 0) {
    $row = $settings_result->fetch_assoc();
    $worker_rate = floatval($row['worker_rate']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_rates'])) {
    $_SESSION['worker_rate'] = floatval($_POST['worker_rate']);
}

if (isset($_SESSION['worker_rate'])) $worker_rate = $_SESSION['worker_rate'];

// Selected Month & Year Filter
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// 1. Total Monthly Revenue
$query = $conn->prepare("
    SELECT p.*, c.customer_name 
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.order_id
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    WHERE MONTH(p.payment_date) = ? AND YEAR(p.payment_date) = ?
    ORDER BY p.payment_id DESC
");
$query->bind_param("ii", $selected_month, $selected_year);
$query->execute();
$report_data = $query->get_result();

$total_revenue = 0;
$records = [];

while ($row = $report_data->fetch_assoc()) {
    $total_revenue += floatval($row['total_amount']);
    $records[] = $row;
}

// 2. 🚚 Supplier Wise Breakdown (DYNAMIC TIERED RATES FROM DB)
$supplier_query = $conn->prepare("
    SELECT 
        IFNULL(s.supplier_id, 0) as supplier_id, 
        IFNULL(s.supplier_name, 'Local / No Supplier') as supplier_name, 
        IFNULL(s.commission_rate, 18.00) as individual_rate,
        SUM(oi.quantity * oi.price) as supplier_total_revenue
    FROM payments p
    INNER JOIN order_items oi ON p.order_id = oi.order_id
    LEFT JOIN inventory i ON oi.item_id = i.item_id
    LEFT JOIN supplier s ON i.supplier_id = s.supplier_id
    WHERE MONTH(p.payment_date) = ? AND YEAR(p.payment_date) = ?
    GROUP BY s.supplier_id, s.supplier_name, s.commission_rate
");
$supplier_query->bind_param("ii", $selected_month, $selected_year);
$supplier_query->execute();
$supplier_data = $supplier_query->get_result();

$supplier_records = [];
$total_calculated_supplier_share = 0;

while ($s_row = $supplier_data->fetch_assoc()) {
    if ($s_row['supplier_id'] > 0) {
        $rate = floatval($s_row['individual_rate']);
        $s_share = (floatval($s_row['supplier_total_revenue']) * $rate) / 100;
        $s_row['calculated_share'] = $s_share;
        $total_calculated_supplier_share += $s_share;
    } else {
        $s_row['calculated_share'] = 0;
        $s_row['individual_rate'] = 0;
    }
    $supplier_records[] = $s_row;
}

// 3. Category-wise Expenses
$expense_query = $conn->prepare("
    SELECT expense_type, SUM(amount) as total_type_amount, COUNT(*) as total_count
    FROM expenses
    WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?
    GROUP BY expense_type
");
$expense_query->bind_param("ii", $selected_month, $selected_year);
$expense_query->execute();
$expense_data = $expense_query->get_result();

$expense_records = [];
$total_other_expenses = 0;

while ($e_row = $expense_data->fetch_assoc()) {
    $total_other_expenses += floatval($e_row['total_type_amount']);
    $expense_records[] = $e_row;
}

// Financial Calculations
$grand_worker_share = ($total_revenue * $worker_rate) / 100;
$grand_supplier_share = $total_calculated_supplier_share; 
$total_all_expenses = $grand_worker_share + $grand_supplier_share + $total_other_expenses;
$grand_business_profit = $total_revenue - $total_all_expenses;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Financial Statement - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-input: #f8fafc;
            --bg-hover: #f8fafc;
            --text-title: #0f172a;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #f1f5f9;
            --border-outer: #edf2f7;
            --border-input: #e2e8f0;
            --th-bg: #f8fafc;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
            --badge-sup-bg: #fef3c7;
            --badge-sup-text: #92400e;
            --badge-loc-bg: #e2e8f0;
            --badge-loc-text: #475569;
            --total-sup-text: #b45309;
            --btn-pdf-bg: #0f172a;
            --btn-pdf-hover: #1e293b;
        }

        body.dark-theme {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #0f172a;
            --bg-hover: #334155;
            --text-title: #f8fafc;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --border-outer: #334155;
            --border-input: #334155;
            --th-bg: #0f172a;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            --badge-sup-bg: rgba(245, 158, 11, 0.2);
            --badge-sup-text: #fbbf24;
            --badge-loc-bg: rgba(148, 163, 184, 0.2);
            --badge-loc-text: #cbd5e1;
            --total-sup-text: #f59e0b;
            --btn-pdf-bg: #38bdf8;
            --btn-pdf-hover: #0284c7;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; transition: background-color 0.25s, color 0.25s; }
        body { background: var(--bg-body); display: flex; min-height: 100vh; color: var(--text-main); }
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 40px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 26px; font-weight: 700; color: var(--text-title); }
        
        .btn-action { padding: 12px 20px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; }
        .btn-pdf { background: var(--btn-pdf-bg); color: white; }
        body.dark-theme .btn-pdf { color: #0f172a; }

        .analytics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .analytic-card { background: var(--bg-card); padding: 22px; border-radius: 18px; border: 1px solid var(--border-outer); box-shadow: var(--shadow); }
        .analytic-card span { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
        .analytic-card h2 { font-size: 20px; font-weight: 800; color: var(--text-title); margin-top: 5px; }
        .lbl-rate { font-size: 11px; background: var(--border-color); padding: 2px 6px; border-radius: 6px; margin-left: 5px; color: var(--text-muted); }

        .section-title { font-size: 18px; font-weight: 700; color: var(--text-title); margin: 35px 0 15px 0; display: flex; align-items: center; gap: 10px; }
        .table-card { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; padding: 10px 0; box-shadow: var(--shadow); margin-bottom: 30px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { background: var(--th-bg); color: var(--text-muted); padding: 16px 24px; font-weight: 600; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid var(--border-outer); text-align: left; }
        td { padding: 16px 24px; color: var(--text-main); font-size: 14px; border-bottom: 1px solid var(--border-color); }

        .badge-supplier { background: var(--badge-sup-bg); color: var(--badge-sup-text); padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 12px; }
        .badge-local { background: var(--badge-loc-bg); color: var(--badge-loc-text); padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 12px; }

        @media (max-width: 1024px) { .main-content { margin-left: 0; width: 100%; padding: 20px; } .analytics-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php if (file_exists('sidebar.php')) include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Statement of Monthly Income</h1>
            <button onclick="window.print()" class="btn-action btn-pdf">
                <i class="fa-solid fa-file-pdf"></i> Export & Download PDF
            </button>
        </div>

        <!-- Analytics Cards -->
        <div class="analytics-grid">
            <div class="analytic-card" style="border-top: 4px solid #0284c7;">
                <span>Gross Revenue</span>
                <h2>Rs. <?php echo number_format($total_revenue, 2); ?></h2>
            </div>
            <div class="analytic-card" style="border-top: 4px solid #7c3aed;">
                <span>Workers Allocation <span class="lbl-rate"><?php echo $worker_rate; ?>%</span></span>
                <h2>Rs. <?php echo number_format($grand_worker_share, 2); ?></h2>
            </div>
            <div class="analytic-card" style="border-top: 4px solid #d97706;">
                <span>Suppliers Allocation <span class="lbl-rate">Tiered Rates</span></span>
                <h2>Rs. <?php echo number_format($grand_supplier_share, 2); ?></h2>
            </div>
            <div class="analytic-card" style="border-top: 4px solid <?php echo ($grand_business_profit < 0) ? '#ef4444' : '#059669'; ?>;">
                <span>Net Business Profit</span>
                <h2 style="color: <?php echo ($grand_business_profit < 0) ? '#ef4444' : '#059669'; ?>;">
                    Rs. <?php echo number_format($grand_business_profit, 2); ?>
                </h2>
            </div>
        </div>

        <!-- Supplier Table with Tiered Rates Display -->
        <div class="section-title">
            <i class="fa-solid fa-truck-ramp-box" style="color:#d97706;"></i> Individual Supplier Share Breakdown (Tiered Rates)
        </div>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Supplier ID</th>
                        <th>Supplier Name</th>
                        <th style="text-align: right;">Total Item Sales Vol.</th>
                        <th style="text-align: right;">Individual Rate (%)</th>
                        <th style="text-align: right;">Net Payout Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($supplier_records) > 0): ?>
                        <?php foreach ($supplier_records as $sr): ?>
                        <tr>
                            <td>
                                <?php if ($sr['supplier_id'] > 0): ?>
                                    <span class="badge-supplier">#SUP-<?php echo htmlspecialchars($sr['supplier_id']); ?></span>
                                <?php else: ?>
                                    <span class="badge-local">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($sr['supplier_name']); ?></strong></td>
                            <td style="text-align: right; font-weight: 500;">Rs. <?php echo number_format($sr['supplier_total_revenue'], 2); ?></td>
                            
                            <!-- INDIVIDUAL RATE DISPLAY -->
                            <td style="text-align: right;">
                                <span class="lbl-rate" style="font-weight: 700; color: #d97706;">
                                    <?php echo number_format($sr['individual_rate'], 2); ?>%
                                </span>
                            </td>
                            
                            <td style="text-align: right; font-weight: 700; color: var(--total-sup-text);">Rs. <?php echo number_format($sr['calculated_share'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-subtle); padding: 25px;">No supplier breakdown available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
        }
    </script>
</body>
</html>