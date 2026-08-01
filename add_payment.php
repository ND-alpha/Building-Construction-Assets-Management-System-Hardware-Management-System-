<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ලොග් වී නැත්නම් ලොගින් පිටුවට හරවා යැවීම
if(!isset($_SESSION['employee_id'])){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "hardware");

if($conn->connect_error){
    die("Database Connection Failed: " . $conn->connect_error);
}

$status = "";

// Pending orders වගේම partial payments තියෙන ඒවා ගන්න query එක
$orders_query = "
    SELECT o.order_id, c.customer_name, SUM(oi.quantity * oi.price) AS order_total
    FROM orders o
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.order_id NOT IN (SELECT order_id FROM payments WHERE payment_status = 'Paid')
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
";
$orders_result = $conn->query($orders_query);

$orders_array = [];
while($row = $orders_result->fetch_assoc()) {
    $orders_array[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = intval($_POST['order_id']);
    $gross_amount = floatval($_POST['gross_amount']);
    $discount_amount = floatval($_POST['discount_amount']);
    $net_amount = floatval($_POST['net_amount']);
    $paid_amount = floatval($_POST['paid_amount']);
    $balance_amount = floatval($_POST['balance_amount']); // Remaining to pay
    $change_amount = floatval($_POST['change_amount']);   // Return to customer
    $payment_method = $_POST['payment_method'];
    $handled_by = $_POST['handled_by'];
    $payment_date = date("Y-m-d H:i:s");

    // Payment Status තීරණය කිරීම
    if ($gross_amount == 0) {
        $payment_status = "Failed";
    } elseif ($paid_amount < $net_amount) {
        $payment_status = "Pending";
    } else {
        $payment_status = "Paid";
    }

    if ($order_id > 0 && $payment_status != "Failed") {
        
        // AUTOMATIC FINANCIAL DISTRIBUTION LOGIC
        $shares_query = $conn->prepare("
            SELECT SUM(oi.quantity * i.supplier_cost) AS total_supplier,
                   SUM(oi.quantity * i.worker_cost) AS total_worker
            FROM order_items oi
            INNER JOIN inventory i ON oi.item_id = i.item_id
            WHERE oi.order_id = ?
        ");
        $shares_query->bind_param("i", $order_id);
        $shares_query->execute();
        $shares_res = $shares_query->get_result()->fetch_assoc();
        
        $total_supplier_share = floatval($shares_res['total_supplier']);
        $total_worker_share = floatval($shares_res['total_worker']);
        
        // Net amount එකෙන් ලාභය ගණනය කිරීම
        $business_profit_share = $net_amount - ($total_supplier_share + $total_worker_share);
        $shares_query->close();

        // ඩේටාබේස් එකට ඇතුළත් කිරීම
        $stmt = $conn->prepare("INSERT INTO payments (order_id, total_amount, gross_amount, discount_amount, net_amount, paid_amount, balance_amount, change_amount, total_supplier_share, total_worker_share, business_profit_share, payment_method, payment_status, handled_by, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("iddddddddddssss", $order_id, $net_amount, $gross_amount, $discount_amount, $net_amount, $paid_amount, $balance_amount, $change_amount, $total_supplier_share, $total_worker_share, $business_profit_share, $payment_method, $payment_status, $handled_by, $payment_date);
        
        if($stmt->execute()) {
            $final_order_status = ($payment_status == 'Paid') ? 'Completed' : 'Partially Paid';
            $update_order = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $update_order->bind_param("si", $final_order_status, $order_id);
            $update_order->execute();
            $status = "success";
        } else {
            $status = "error";
        }
        $stmt->close();
    } else {
        $status = "failed_status";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Payment - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Light & Dark Theme CSS Variables */
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;
            --bg-box: #f8fafc;
            --text-title: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #f8fafc;
            --input-focus-bg: #ffffff;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            
            /* Dynamic Alert & Badge Colors */
            --badge-default-bg: #e2e8f0;
            --badge-default-text: #0f172a;
            --alert-success-bg: #e2f8e9;
            --alert-success-text: #157337;
            --alert-error-bg: #fde8e8;
            --alert-error-text: #c81e1e;
        }

        body.dark-theme {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --bg-box: #0f172a;
            --text-title: #f8fafc;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #0f172a;
            --input-focus-bg: #1e293b;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.3);

            --badge-default-bg: #334155;
            --badge-default-text: #f8fafc;
            --alert-success-bg: rgba(34, 197, 94, 0.15);
            --alert-success-text: #4ade80;
            --alert-error-bg: rgba(239, 68, 68, 0.15);
            --alert-error-text: #f87171;
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            transition: background-color 0.25s, color 0.25s, border-color 0.25s;
        }

        body { 
            background: var(--bg-body); 
            color: var(--text-main);
            display: flex; 
            min-height: 100vh; 
        }

        .main-content { 
            margin-left: 280px; 
            width: calc(100% - 280px); 
            padding: 40px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }

        .page-header { 
            width: 100%; 
            max-width: 700px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }

        .page-header h1 { 
            font-size: 26px; 
            font-weight: 700; 
            color: var(--text-title); 
        }

        .btn-back { 
            padding: 10px 18px; 
            background: var(--bg-card); 
            border: 1px solid var(--border-color); 
            border-radius: 10px; 
            color: var(--text-main); 
            text-decoration: none; 
            font-size: 14px; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            transition: 0.3s; 
            box-shadow: var(--shadow);
        }

        .btn-back:hover {
            background: var(--bg-hover);
            transform: translateY(-2px);
        }

        .form-card { 
            background: var(--bg-card); 
            width: 100%; 
            max-width: 700px; 
            padding: 40px; 
            border-radius: 20px; 
            border: 1px solid var(--border-color); 
            box-shadow: var(--shadow); 
        }

        .form-group { 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            margin-bottom: 20px; 
        }

        .form-group label { 
            font-size: 13px; 
            font-weight: 600; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }

        select, input { 
            width: 100%; 
            padding: 14px 16px; 
            border: 2px solid var(--border-color); 
            border-radius: 12px; 
            outline: none; 
            font-size: 14px; 
            background: var(--input-bg); 
            color: var(--text-title);
        }

        select:focus, input:focus { 
            border-color: #3b82f6; 
            background: var(--input-focus-bg); 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); 
        }

        /* Grid Setup */
        .row-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
        }

        @media (max-width: 650px) {
            .row-grid { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
        }

        .calc-box { 
            background: var(--bg-box); 
            border: 2px solid var(--border-color); 
            padding: 20px; 
            border-radius: 14px; 
            margin-bottom: 25px; 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
        }

        .calc-title { 
            font-size: 13px; 
            font-weight: 700; 
            color: var(--text-muted); 
            margin-bottom: 5px; 
            text-transform: uppercase; 
        }

        .calc-value { 
            font-size: 20px; 
            font-weight: 800; 
            color: var(--text-title); 
        }

        .calc-value.net { color: #3b82f6; }
        .calc-value.balance { color: #ef4444; }
        .calc-value.change { color: #10b981; }
        .calc-value.status-badge { 
            font-size: 15px; 
            display: inline-block; 
            padding: 6px 14px; 
            border-radius: 20px; 
            text-align: center; 
            background: var(--badge-default-bg);
            color: var(--badge-default-text);
            font-weight: 700;
        }

        .btn-submit { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); 
            border: none; 
            color: white; 
            font-size: 16px; 
            font-weight: 600; 
            border-radius: 12px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px; 
            transition: 0.3s; 
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-submit:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        .alert { 
            width: 100%; 
            max-width: 700px; 
            padding: 14px 20px; 
            border-radius: 12px; 
            font-weight: 500; 
            font-size: 14px; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }

        .alert.success { 
            background: var(--alert-success-bg); 
            color: var(--alert-success-text); 
            border-left: 5px solid #22c55e; 
        }

        .alert.error { 
            background: var(--alert-error-bg); 
            color: var(--alert-error-text); 
            border-left: 5px solid #ef4444; 
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Collect & Distribute Payment</h1>
            <a href="manage_expenses.php" class="btn-back"><i class="fa-solid fa-chart-pie"></i> Expense Dashboard</a>
        </div>

        <?php if(!empty($status)): ?>
            <?php if($status == 'success'): ?>
                <div class="alert success"><i class="fa-solid fa-circle-check"></i> Success! Payment processed and Status updated successfully.</div>
            <?php elseif($status == 'failed_status'): ?>
                <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> Error: Gross amount cannot be 0 (Failed Transaction).</div>
            <?php else: ?>
                <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> Check database connectivity or inputs.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="add_payment.php">
                
                <div class="row-grid">
                    <div class="form-group">
                        <label>Handled By (System User)</label>
                        <select name="handled_by" required>
                            <option value="">-- Select Employee --</option>
                            <option value="Admin">Admin</option>
                            <option value="Manager">Manager</option>
                            <option value="Cashier 01">Cashier 01</option>
                            <option value="Cashier 02">Cashier 02</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select Pending Order</label>
                        <select name="order_id" id="orderSelect" onchange="loadOrderAmount()" required>
                            <option value="">-- Choose Order ID --</option>
                            <?php foreach($orders_array as $ord){ ?>
                                <option value="<?php echo $ord['order_id']; ?>">#ORD-<?php echo $ord['order_id']; ?> - <?php echo $ord['customer_name'] ? htmlspecialchars($ord['customer_name']) : 'Walk-in'; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="row-grid">
                    <div class="form-group">
                        <label>Discount Value (Rs.)</label>
                        <input type="number" step="0.01" id="discountAmount" name="discount_amount" value="0.00" onkeyup="calculateAmounts()">
                    </div>
                    <div class="form-group">
                        <label>Amount Received From Customer (Rs.)</label>
                        <input type="number" step="0.01" name="paid_amount" id="paidAmount" onkeyup="calculateAmounts()" required placeholder="0.00">
                    </div>
                </div>

                <div class="calc-box">
                    <div>
                        <div class="calc-title">Gross Amount</div>
                        <div class="calc-value" id="dispGross">Rs. 0.00</div>
                        <input type="hidden" name="gross_amount" id="inputGross" value="0">
                    </div>
                    <div>
                        <div class="calc-title">Net Amount</div>
                        <div class="calc-value net" id="dispNet">Rs. 0.00</div>
                        <input type="hidden" name="net_amount" id="inputNet" value="0">
                    </div>
                    <div>
                        <div class="calc-title">Pending Balance (Due)</div>
                        <div class="calc-value balance" id="dispBalance">Rs. 0.00</div>
                        <input type="hidden" name="balance_amount" id="inputBalance" value="0">
                    </div>
                    <div>
                        <div class="calc-title">Total Change (Return)</div>
                        <div class="calc-value change" id="dispChange">Rs. 0.00</div>
                        <input type="hidden" name="change_amount" id="inputChange" value="0">
                    </div>
                </div>

                <div class="row-grid" style="margin-bottom: 20px; align-items: center;">
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" required>
                            <option value="Cash">💵 Cash Money</option>
                            <option value="Bank Transfer">🏦 Direct Bank Transfer</option>
                            <option value="Cheque">✍️ Cheque Payment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Expected Payment Status</label>
                        <div id="statusBadge" class="calc-value status-badge">None</div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-cash-register"></i> Collect & Split Funds
                </button>
            </form>
        </div>
    </div>

    <!-- Calculation & Dynamic Dark/Light Theme Handler Script -->
    <script>
        const pendingOrders = <?php echo json_encode($orders_array); ?>;
        
        function loadOrderAmount() {
            const orderId = document.getElementById('orderSelect').value;
            if(!orderId) { resetFields(); return; }
            const activeOrder = pendingOrders.find(o => o.order_id == orderId);
            if(activeOrder) {
                const total = parseFloat(activeOrder.order_total);
                document.getElementById('dispGross').innerText = "Rs. " + total.toFixed(2);
                document.getElementById('inputGross').value = total;
                calculateAmounts();
            }
        }

        function calculateAmounts() {
            const gross = parseFloat(document.getElementById('inputGross').value) || 0;
            const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
            
            // Net Amount ගණනය කිරීම
            let net = gross - discount;
            if(net < 0) net = 0;
            
            document.getElementById('dispNet').innerText = "Rs. " + net.toFixed(2);
            document.getElementById('inputNet').value = net;

            let balance = 0;
            let change = 0;
            let statusText = "None";
            
            const isDark = document.body.classList.contains('dark-theme');
            let statusColor = isDark ? "#334155" : "#e2e8f0";
            let textColor = isDark ? "#f8fafc" : "#0f172a";

            if (gross === 0) {
                statusText = "Failed";
                statusColor = isDark ? "rgba(239, 68, 68, 0.25)" : "#fde8e8";
                textColor = isDark ? "#f87171" : "#c81e1e";
            } else if (paid < net) {
                balance = net - paid;
                statusText = "Pending";
                statusColor = isDark ? "rgba(245, 158, 11, 0.25)" : "#fef3c7";
                textColor = isDark ? "#fbbf24" : "#d97706";
            } else {
                change = paid - net;
                statusText = "Paid";
                statusColor = isDark ? "rgba(34, 197, 94, 0.25)" : "#dcfce7";
                textColor = isDark ? "#4ade80" : "#15803d";
            }

            // අගයන් පෙන්වීම
            document.getElementById('dispBalance').innerText = "Rs. " + balance.toFixed(2);
            document.getElementById('inputBalance').value = balance;
            
            document.getElementById('dispChange').innerText = "Rs. " + change.toFixed(2);
            document.getElementById('inputChange').value = change;

            // Status Badge එක Dynamic ලෙස Update කිරීම
            const badge = document.getElementById('statusBadge');
            badge.innerText = statusText;
            badge.style.backgroundColor = statusColor;
            badge.style.color = textColor;
        }

        function resetFields() {
            document.getElementById('dispGross').innerText = "Rs. 0.00"; document.getElementById('inputGross').value = 0;
            document.getElementById('dispNet').innerText = "Rs. 0.00"; document.getElementById('inputNet').value = 0;
            document.getElementById('dispBalance').innerText = "Rs. 0.00"; document.getElementById('inputBalance').value = 0;
            document.getElementById('dispChange').innerText = "Rs. 0.00"; document.getElementById('inputChange').value = 0;
            document.getElementById('paidAmount').value = "";
            document.getElementById('discountAmount').value = "0.00";
            
            const isDark = document.body.classList.contains('dark-theme');
            const badge = document.getElementById('statusBadge');
            badge.innerText = "None"; 
            badge.style.backgroundColor = isDark ? "#334155" : "#e2e8f0"; 
            badge.style.color = isDark ? "#f8fafc" : "#0f172a";
        }

        // Light / Dark Theme Syncing Logic
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
            }

            // Status Badge එක UI Mode එකට අනුව සකස් කිරීම
            calculateAmounts();

            // Sidebar Switcher එකට සම්බන්ධ කිරීම
            const themeToggle = document.querySelector('.theme-toggle input, #themeToggle, input[type="checkbox"]');
            if (themeToggle) {
                if (document.body.classList.contains('dark-theme')) {
                    themeToggle.checked = true;
                }
                themeToggle.addEventListener('change', () => {
                    if (themeToggle.checked) {
                        document.body.classList.add('dark-theme');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.body.classList.remove('dark-theme');
                        localStorage.setItem('theme', 'light');
                    }
                    calculateAmounts(); // Theme එක මාරු වන විට Badge Colors Refresh කිරීම
                });
            }
        });
    </script>
</body>
</html>