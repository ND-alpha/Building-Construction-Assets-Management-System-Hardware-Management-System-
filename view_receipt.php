<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['employee_id'])){
    die("Unauthorized access.");
}

if(!isset($_GET['payment_id'])){
    die("Invalid Request.");
}

$conn = new mysqli("localhost", "root", "", "hardware");
if($conn->connect_error){
    die("Database Connection Failed.");
}

$payment_id = intval($_GET['payment_id']);

// Payment සහ Customer විස්තර ලබා ගැනීම
$payment_query = $conn->query("
    SELECT p.*, o.order_date, c.customer_name, c.phone, c.address
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.order_id
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    WHERE p.payment_id = $payment_id
");
$pay = $payment_query->fetch_assoc();

if(!$pay){
    die("Receipt not found.");
}

// ⚠️ වැරැද්ද නිවැරදි කරන ලද කොටස: 'items' වෙනුවට 'inventory' table එක JOIN කර ඇත.
$order_id = $pay['order_id'];
$items_query = $conn->query("
    SELECT oi.*, i.item_name 
    FROM order_items oi
    LEFT JOIN inventory i ON oi.item_id = i.item_id
    WHERE oi.order_id = $order_id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #REC-<?php echo $payment_id; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; margin: 0; padding: 20px; background: #f5f5f5; }
        .receipt-container { max-width: 600px; background: white; margin: 0 auto; padding: 30px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; position: relative; }
        .logo { max-width: 120px; margin-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; color: #1e293b; margin-bottom: 5px; }
        .contact-details { font-size: 13px; color: #64748b; line-height: 1.5; }
        
        .receipt-meta { display: flex; justify-content: space-between; font-size: 13px; color: #475569; margin-bottom: 20px; line-height: 1.6; }
        
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .item-table th { background: #f8fafc; text-align: left; padding: 10px; font-size: 12px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #edf2f7; }
        .item-table td { padding: 12px 10px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
        
        .totals-section { width: 50%; margin-left: auto; font-size: 14px; line-height: 1.8; margin-bottom: 20px; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; }
        .grand-total { font-weight: 700; color: #1e293b; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 6px 0; font-size: 16px; }
        
        .payment-info { background: #f8fafc; padding: 12px; border-radius: 6px; font-size: 13px; color: #475569; margin-bottom: 20px; border-left: 4px solid #3b82f6; }
        
        .footer { text-align: center; font-size: 13px; color: #64748b; border-top: 2px dashed #e2e8f0; padding-top: 20px; margin-top: 25px; line-height: 1.6; }
        .thank-you { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 5px; }
        
        .actions { text-align: center; margin-top: 20px; }
        .btn-print { padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2); }
        .btn-print:hover { background: #1d4ed8; }

        @media print {
            body { background: white; padding: 0; }
            .receipt-container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .actions { display: none; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <!-- Header Section -->
        <div class="header">
            <!-- Hardware Logo එක (logo.png නමින් root folder එකේ තිබිය යුතුය) -->
            <img src="logo.png" alt="FixIt Hardware Logo" class="logo" onerror="this.style.display='none'">
            <div class="company-name">FixIt Hardware</div>
            <div class="contact-details">
                No 123, Main Street, Weligama.<br>
                Tel: +94 41 222 XXXX | Email: info@fixithardware.com
            </div>
        </div>

        <!-- Meta Details -->
        <div class="receipt-meta">
            <div>
                <strong>Receipt No:</strong> #REC-<?php echo $payment_id; ?><br>
                <strong>Order ID:</strong> #ORD-<?php echo $pay['order_id']; ?><br>
                <strong>Customer:</strong> <?php echo $pay['customer_name'] ? htmlspecialchars($pay['customer_name']) : 'Walk-in Customer'; ?>
            </div>
            <div style="text-align: right;">
                <strong>Date:</strong> <?php echo date('d M Y', strtotime($pay['payment_date'])); ?><br>
                <strong>Time:</strong> <?php echo date('h:i A', strtotime($pay['payment_date'])); ?>
            </div>
        </div>

        <!-- Items Table -->
        <table class="item-table">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($items_query && $items_query->num_rows > 0): 
                    while($item = $items_query->fetch_assoc()):
                        // order_items වල ඇති price සහ quantity ගුණ කර amount එක හදයි
                        $amt = $item['price'] * $item['quantity']; 
                ?>
                    <tr>
                        <td>#<?php echo $item['item_id']; ?></td>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">Rs. <?php echo number_format($amt, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#94a3b8;">Items detailed in Order Invoice.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="totals-row">
                <span>Gross Amount:</span>
                <span>Rs. <?php echo number_format($pay['gross_amount'], 2); ?></span>
            </div>
            <div class="totals-row" style="color: #ef4444;">
                <span>Discount:</span>
                <span>-Rs. <?php echo number_format($pay['discount_amount'], 2); ?></span>
            </div>
            <div class="totals-row grand-total">
                <span>Net Amount:</span>
                <span>Rs. <?php echo number_format($pay['net_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-info">
            <strong>Payment Summary:</strong><br>
            Method: <?php echo $pay['payment_method']; ?> | Status: <strong><?php echo $pay['payment_status']; ?></strong><br>
            Total Paid: Rs. <?php echo number_format($pay['paid_amount'], 2); ?><br>
            <?php if($pay['balance_amount'] > 0): ?>
                <span style="color: #d97706; font-weight: bold;">Remaining Due: Rs. <?php echo number_format($pay['balance_amount'], 2); ?></span>
            <?php elseif($pay['change_amount'] > 0): ?>
                <span style="color: #10b981; font-weight: bold;">Balance Changed: Rs. <?php echo number_format($pay['change_amount'], 2); ?></span>
            <?php endif; ?>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <div class="thank-you">Thank you! Come Again.</div>
            Issued on: <?php echo date('Y-m-d H:i:s'); ?><br>
            Handled By: <strong><?php echo htmlspecialchars($pay['handled_by']); ?></strong>
        </div>
    </div>

    <!-- Print Button Actions -->
    <div class="actions">
        <button class="btn-print" onclick="window.print()">Print Receipt</button>
    </div>

</body>
</html>