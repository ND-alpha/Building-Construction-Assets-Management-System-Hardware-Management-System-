<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "hardware");
    if($conn->connect_error){
        die("Database Connection Failed: " . $conn->connect_error);
    }
}

// ----------------------------------------------------------------
// Dynamic Shop Settings Fetch
// ----------------------------------------------------------------
$sys_shop_name = "FixIt Hardware";
$sys_shop_logo = "";
$sys_shop_address = "";
$sys_shop_phone = "";
$sys_shop_email = "";

$settings_query = $conn->query("SELECT * FROM system_settings WHERE id = 1");
if ($settings_query && $settings_query->num_rows > 0) {
    $setting_data = $settings_query->fetch_assoc();
    $sys_shop_name = !empty($setting_data['shop_name']) ? $setting_data['shop_name'] : $sys_shop_name;
    $sys_shop_logo = !empty($setting_data['logo']) ? $setting_data['logo'] : "";
    $sys_shop_address = !empty($setting_data['address']) ? $setting_data['address'] : "";
    $sys_shop_phone = !empty($setting_data['phone']) ? $setting_data['phone'] : "";
    $sys_shop_email = !empty($setting_data['email']) ? $setting_data['email'] : "";
}

// ----------------------------------------------------------------
// PENDING PAYMENT එකක් UPDATE කිරීම සහ AUTO RECEIPT POPUP LOGIC
// ----------------------------------------------------------------
$update_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    $additional_paid = floatval($_POST['additional_paid']);
    $new_method = $conn->real_escape_string($_POST['payment_method']);

    $stmt = $conn->prepare("SELECT net_amount, paid_amount, balance_amount FROM payments WHERE payment_id = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $current_pay = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($current_pay) {
        $total_paid_now = $current_pay['paid_amount'] + $additional_paid;
        $net_amount = $current_pay['net_amount'];
        
        $new_balance = 0;
        $new_change = 0;
        $new_status = 'Pending';

        if ($total_paid_now >= $net_amount) {
            $new_change = $total_paid_now - $net_amount;
            $new_balance = 0;
            $new_status = 'Paid';
        } else {
            $new_balance = $net_amount - $total_paid_now;
            $new_status = 'Pending';
        }

        $update_stmt = $conn->prepare("
            UPDATE payments 
            SET paid_amount = ?, balance_amount = ?, change_amount = ?, payment_status = ?, payment_method = ? 
            WHERE payment_id = ?
        ");
        $update_stmt->bind_param("dddssi", $total_paid_now, $new_balance, $new_change, $new_status, $new_method, $payment_id);
        
        if ($update_stmt->execute()) {
            $update_message = "<script>
                alert('Payment updated successfully!');
                window.open('view_receipt.php?payment_id=$payment_id', '_blank');
                window.location.href='manage_payment.php';
            </script>";
        } else {
            $update_message = "<script>alert('Failed to update payment.');</script>";
        }
        $update_stmt->close();
    }
}

// Summary Query
$summary = $conn->query("
    SELECT 
        SUM(gross_amount) AS grand_gross,
        SUM(discount_amount) AS grand_discount,
        SUM(net_amount) AS grand_revenue,
        SUM(business_profit_share) AS grand_profit
    FROM payments
    WHERE payment_status != 'Failed'
")->fetch_assoc();

$ledger = $conn->query("
    SELECT p.*, c.customer_name 
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.order_id
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    ORDER BY p.payment_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Management - <?php echo htmlspecialchars($sys_shop_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Modern CSS Variables for System Themes */
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;
            --text-title: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --border-color: #f1f5f9;
            --input-bg: #ffffff;
            --modal-overlay: rgba(15, 23, 42, 0.6);
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            --table-header-bg: #f8fafc;
        }

        [data-theme="dark"], body.dark-theme {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --text-title: #f8fafc;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #0f172a;
            --modal-overlay: rgba(0, 0, 0, 0.75);
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            --table-header-bg: #172131;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; transition: background-color 0.25s, color 0.25s, border-color 0.25s; }
        body { background: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }
        
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 40px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 26px; font-weight: 700; color: var(--text-title); }
        
        .btn-add-payment { padding: 12px 20px; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; text-decoration: none; border-radius: 12px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3); transition: 0.3s; cursor: pointer; border: none; }
        .btn-add-payment:hover { transform: translateY(-2px); }

        .btn-collect-due { padding: 6px 12px; background: #d97706; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; margin-top: 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s; }
        .btn-collect-due:hover { background: #b45309; }

        .btn-view-receipt { padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; transition: 0.2s; }
        .btn-view-receipt:hover { background: #dc2626; }

        /* Summary Cards - 4 Columns */
        .analytics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        @media (max-width: 1024px) { .analytics-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .analytics-grid { grid-template-columns: 1fr; } }
        
        .analytic-card { background: var(--bg-card); padding: 22px; border-radius: 18px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; box-shadow: var(--shadow); }
        .card-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .gross-icon { background: rgba(100, 116, 139, 0.15); color: #64748b; }
        .discount-icon { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .revenue-icon { background: rgba(2, 132, 199, 0.15); color: #0284c7; }
        .profit-icon { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        
        .analytic-card span { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .analytic-card h2 { font-size: 22px; font-weight: 800; color: var(--text-title); word-break: break-all; }

        /* Table Card Styling */
        .table-card { background: var(--bg-card); border-radius: 20px; box-shadow: var(--shadow); border: 1px solid var(--border-color); overflow-x: auto; padding: 10px 0; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1100px; }
        th { background: var(--table-header-bg); color: var(--text-muted); padding: 18px 20px; font-weight: 600; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid var(--border-color); text-align: left; }
        td { padding: 16px 20px; color: var(--text-main); font-size: 14px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:hover td { background-color: var(--bg-hover); }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; text-transform: uppercase; }
        .status-paid { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .status-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .status-failed { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .method-tag { background: var(--bg-hover); padding: 4px 8px; border-radius: 6px; font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .text-muted { color: var(--text-muted) !important; font-size: 12px; }

        /* Modal Dialog Styling */
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: var(--modal-overlay); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: var(--bg-card); padding: 30px; border-radius: 20px; width: 450px; border: 1px solid var(--border-color); box-shadow: var(--shadow); color: var(--text-main); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { font-size: 18px; font-weight: 700; color: var(--text-title); }
        .close-btn { font-size: 20px; color: var(--text-muted); cursor: pointer; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); background: var(--input-bg); border-radius: 8px; font-size: 14px; color: var(--text-title); outline: none; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-cancel { padding: 10px 16px; background: var(--bg-hover); color: var(--text-main); border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-submit { padding: 10px 16px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>
    <?php echo $update_message; ?>
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Revenue & Financial Ledger</h1>
            <a href="add_payment.php" class="btn-add-payment"><i class="fa-solid fa-plus-circle"></i> Collect New Payment</a>
        </div>

        <!-- 4 Summary Cards -->
        <div class="analytics-grid">
            <div class="analytic-card">
                <div class="card-icon gross-icon"><i class="fa-solid fa-calculator"></i></div>
                <span>Total Gross</span>
                <h2>Rs. <?php echo number_format($summary['grand_gross'] ?? 0, 2); ?></h2>
            </div>
            <div class="analytic-card">
                <div class="card-icon discount-icon"><i class="fa-solid fa-tags"></i></div>
                <span>Total Discount</span>
                <h2>Rs. <?php echo number_format($summary['grand_discount'] ?? 0, 2); ?></h2>
            </div>
            <div class="analytic-card">
                <div class="card-icon revenue-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <span>Net Revenue</span>
                <h2>Rs. <?php echo number_format($summary['grand_revenue'] ?? 0, 2); ?></h2>
            </div>
            <div class="analytic-card">
                <div class="card-icon profit-icon"><i class="fa-solid fa-chart-line"></i></div>
                <span>Net Profit</span>
                <h2>Rs. <?php echo number_format($summary['grand_profit'] ?? 0, 2); ?></h2>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Receipt ID</th>
                        <th>Order / Customer</th>
                        <th>Gross & Discount</th>
                        <th>Net Payable</th>
                        <th>Amount Paid</th>
                        <th>Due Bal / Change</th>
                        <th>Status & Method</th>
                        <th>Handled By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($ledger && $ledger->num_rows > 0): ?>
                        <?php while($l = $ledger->fetch_assoc()): ?>
                        <?php 
                            $status_class = 'status-paid';
                            if($l['payment_status'] == 'Pending') $status_class = 'status-pending';
                            if($l['payment_status'] == 'Failed') $status_class = 'status-failed';
                        ?>
                        <tr>
                            <td>
                                <strong>#REC-<?php echo $l['payment_id']; ?></strong><br>
                                <span class="text-muted"><?php echo date('M d, Y H:i', strtotime($l['payment_date'])); ?></span>
                            </td>

                            <td>
                                <span style="color:var(--text-muted); font-weight:600;">#ORD-<?php echo $l['order_id']; ?></span><br>
                                <strong style="color:var(--text-title);"><?php echo $l['customer_name'] ? htmlspecialchars($l['customer_name']) : 'Walk-in Customer'; ?></strong>
                            </td>

                            <td>
                                <span>G: Rs. <?php echo number_format($l['gross_amount'], 2); ?></span><br>
                                <span style="color: #ef4444; font-size:12px;">D: -Rs. <?php echo number_format($l['discount_amount'], 2); ?></span>
                            </td>

                            <td style="font-weight:700; color:#06b6d4;">
                                Rs. <?php echo number_format($l['net_amount'], 2); ?>
                            </td>

                            <td style="font-weight:700; color:var(--text-title);">
                                Rs. <?php echo number_format($l['paid_amount'], 2); ?>
                            </td>

                            <td>
                                <?php if($l['balance_amount'] > 0): ?>
                                    <span style="color:#d97706; font-weight:600;">Due: Rs. <?php echo number_format($l['balance_amount'], 2); ?></span><br>
                                    
                                    <?php if($l['payment_status'] == 'Pending'): ?>
                                        <button class="btn-collect-due" onclick="openPaymentModal(<?php echo $l['payment_id']; ?>, <?php echo $l['balance_amount']; ?>, '<?php echo $l['customer_name'] ? addslashes(htmlspecialchars($l['customer_name'])) : 'Walk-in Customer'; ?>')">
                                            <i class="fa-solid fa-money-bill-wave"></i> Collect Due
                                        </button>
                                    <?php endif; ?>

                                <?php elseif($l['change_amount'] > 0): ?>
                                    <span style="color:#10b981; font-weight:600;">Chg: Rs. <?php echo number_format($l['change_amount'], 2); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $l['payment_status']; ?></span><br>
                                <span class="method-tag" style="margin-top: 5px; display:inline-block;"><i class="fa-solid fa-wallet"></i> <?php echo $l['payment_method']; ?></span>
                            </td>

                            <td>
                                <strong><i class="fa-solid fa-user-tie" style="font-size: 12px; margin-right:4px;"></i> <?php echo htmlspecialchars($l['handled_by']); ?></strong>
                            </td>
                            
                            <td>
                                <a href="view_receipt.php?payment_id=<?php echo $l['payment_id']; ?>" target="_blank" class="btn-view-receipt">
                                    <i class="fa-solid fa-print"></i> Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 50px;">No payout distribution data generated inside the system.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- COLLECT DUE PAYMENT MODAL POPUP -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Complete Pending Payment</h3>
                <span class="close-btn" onclick="closePaymentModal()">&times;</span>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="payment_id" id="modal_payment_id">
                
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" id="modal_customer_name" readonly style="opacity: 0.8; font-weight: 600;">
                </div>

                <div class="form-group">
                    <label>Remaining Due Balance (Rs.)</label>
                    <input type="text" id="modal_due_balance" readonly style="background: rgba(239,68,68,0.1); color: #ef4444; font-weight: 700;">
                </div>

                <div class="form-group">
                    <label>Amount Paying Now (Rs.)</label>
                    <input type="number" step="0.01" name="additional_paid" id="modal_amount_paying" required min="0.01" placeholder="Enter amount collected">
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" name="complete_payment" class="btn-submit">Update & Print</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Logic & Light/Dark Theme Switcher JS -->
    <script>
        function openPaymentModal(paymentId, dueAmount, customerName) {
            document.getElementById('modal_payment_id').value = paymentId;
            document.getElementById('modal_customer_name').value = customerName;
            document.getElementById('modal_due_balance').value = dueAmount.toFixed(2);
            document.getElementById('modal_amount_paying').value = dueAmount.toFixed(2);
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('paymentModal');
            if (event.target == modal) { modal.style.display = "none"; }
        }

        // Theme Alignment with Sidebar
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.body.classList.add('dark-theme');
            }
        });
    </script>
</body>
</html>