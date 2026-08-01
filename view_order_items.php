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
    die("Database Error : ".$conn->connect_error);
}

// URL එකෙන් Order ID එක ලබා ගැනීම
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if($order_id === 0) {
    die("Invalid Order ID.");
}

// ==========================================
// 💡 [විසඳුම] මෙතන ඔයාගේ සැබෑ HARDWARE විස්තර ඇතුළත් කරන්න!
// ==========================================
$shop_name = "Nugaduwa Hardware"; 
$shop_address = "Nugaduwa,Weligama, Sri Lanka";
$shop_phone = "0771234567";
$shop_email = "infonugaduwahardware@gmail.com";
$shop_logo = "fa-solid fa-screwdriver-wrench"; // ඔයාගේ Logo එක තියෙන path එක (උදා: assets/img/logo.png)

// ==========================================
// ORDER DETAILS ලබා ගැනීම
// ==========================================
$order_query = "
    SELECT o.*, c.customer_name, c.phone, e.full_name AS employee_name 
    FROM orders o
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    LEFT JOIN employee e ON o.employee_id = e.employee_id
    WHERE o.order_id = $order_id
";
$order_result = $conn->query($order_query);
$order = $order_result->fetch_assoc();

if(!$order) {
    die("Order not found.");
}

// ==========================================
// ORDER ITEMS ලබා ගැනීම
// ==========================================
$items_query = "
    SELECT oi.*, i.item_name, i.unit 
    FROM order_items oi
    JOIN inventory i ON oi.item_id = i.item_id
    WHERE oi.order_id = $order_id
";
$items_result = $conn->query($items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #ORD-<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --body-bg: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --table-th-bg: #f8fafc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background: var(--body-bg); 
            color: var(--text-main); 
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-height: 100vh;
        }

        .receipt-container {
            width: 100%;
            max-width: 850px;
            background: var(--card-bg);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .top-action-bar {
            width: 100%;
            max-width: 850px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .btn-back { background: #ffffff; color: var(--text-main); border-color: var(--border-color); }
        .btn-print { background: #3b82f6; color: #ffffff; }
        .btn-print:hover { background: #2563eb; }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed var(--border-color);
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        
        .company-info { display: flex; align-items: center; gap: 20px; }
        .company-logo { width: 75px; height: 75px; object-fit: contain; border-radius: 12px; }
        .company-details h2 { font-size: 26px; font-weight: 800; color: #3b82f6; margin-bottom: 4px; text-transform: uppercase; }
        .company-details p { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
        
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { font-size: 24px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.5px; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-completed { background: rgba(22, 163, 74, 0.12); color: #16a34a; }
        .status-pending { background: rgba(217, 119, 6, 0.12); color: #d97706; }

        .bill-details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
        }
        .bill-box h4 { font-size: 11px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.8px; margin-bottom: 6px; }
        .bill-box p { font-size: 14px; color: var(--text-main); font-weight: 600; }
        .bill-box span { font-size: 13px; color: var(--text-muted); display: block; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th {
            background: var(--table-th-bg);
            color: var(--text-muted);
            padding: 14px 16px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }
        td { padding: 16px 16px; color: var(--text-main); font-size: 14px; border-bottom: 1px solid var(--border-color); }
        .txt-right { text-align: right; }
        .txt-center { text-align: center; }

        .invoice-summary { display: flex; justify-content: flex-end; }
        .summary-box { width: 320px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; color: var(--text-muted); }
        .summary-row.total {
            border-top: 2px solid var(--text-main);
            padding-top: 12px;
            margin-top: 8px;
            font-size: 19px;
            font-weight: 800;
            color: var(--text-main);
        }
        .summary-row.total .price { color: #3b82f6; }

        @media print {
            body { background: #ffffff; padding: 0; }
            .top-action-bar { display: none !important; }
            .receipt-container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .bill-details-grid { background: #ffffff !important; border: 1px solid var(--border-color); }
        }
    </style>
</head>
<body>

    <div class="top-action-bar">
        <a href="manage_order.php" class="btn btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <button onclick="window.print()" class="btn btn-print">
            <i class="fa-solid fa-print"></i> Print Receipt
        </button>
    </div>

    <div class="receipt-container">
        
        <div class="invoice-header">
            <div class="company-info">
                <?php if(!empty($shop_logo) && file_exists($shop_logo)): ?>
                    <img src="<?php echo htmlspecialchars($shop_logo); ?>" alt="Logo" class="company-logo">
                <?php else: ?>
                    <!-- Logo එකක් නැත්නම් icon එකක් පෙන්වීමට -->
                    <div style="background: rgba(59, 130, 246, 0.1); width: 65px; height: 65px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 28px;">
                        <i class="fa-solid fa-store"></i>
                    </div>
                <?php endif; ?>
                
                <div class="company-details">
                    <h2><?php echo htmlspecialchars($shop_name); ?></h2>
                    <p><?php echo nl2br(htmlspecialchars($shop_address)); ?><br>
                    Tel: <?php echo htmlspecialchars($shop_phone); ?> | Email: <?php echo htmlspecialchars($shop_email); ?></p>
                </div>
            </div>
            <div class="invoice-meta">
                <h2>RECEIPT</h2>
                <p style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Order: <span style="color: #3b82f6;">#ORD-<?php echo $order_id; ?></span></p>
                <span class="status-badge <?php echo ($order['status'] == 'Completed') ? 'status-completed' : 'status-pending'; ?>">
                    <?php echo $order['status'] ?? 'Pending'; ?>
                </span>
            </div>
        </div>

        <div class="bill-details-grid">
            <div class="bill-box">
                <h4>Customer Information</h4>
                <p><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer'); ?></p>
                <span><?php echo htmlspecialchars($order['phone'] ?? ''); ?></span>
            </div>
            <div class="bill-box">
                <h4>Billed By</h4>
                <p><?php echo htmlspecialchars($order['employee_name'] ?? 'System Admin'); ?></p>
            </div>
            <div class="bill-box" style="text-align: right;">
                <h4>Date & Time</h4>
                <p><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></p>
                <span><?php echo date('h:i A', strtotime($order['order_date'])); ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Item Description</th>
                    <th class="txt-right">Price</th>
                    <th class="txt-center">Qty</th>
                    <th class="txt-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                $subtotal = 0;
                if($items_result && $items_result->num_rows > 0) {
                    while($item = $items_result->fetch_assoc()) { 
                        $item_total = $item['quantity'] * $item['price'];
                        $subtotal += $item_total;
                    ?>
                    <tr>
                        <td><span style="color: var(--text-muted);">0<?php echo $counter++; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong> <span style="font-size:11px; color:var(--text-muted);"> (<?php echo $item['unit']; ?>)</span></td>
                        <td class="txt-right">Rs. <?php echo number_format($item['price'], 2); ?></td>
                        <td class="txt-center" style="font-weight: 600;"><?php echo $item['quantity']; ?></td>
                        <td class="txt-right" style="font-weight: 700;">Rs. <?php echo number_format($item_total, 2); ?></td>
                    </tr>
                    <?php 
                    }
                } else { ?>
                    <tr><td colspan="5" style="text-align:center; color:#94a3b8;">No items found.</td></tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="invoice-summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rs. <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Discount</span>
                    <span>Rs. <?php echo number_format($order['discount'] ?? 0, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Net Amount</span>
                    <!-- 💡 FIXED: Net amount එක $subtotal එකෙන් සැබෑ එකතුව ලස්සනට පෙන්වයි -->
                    <span class="price">Rs. <?php echo number_format($subtotal - ($order['discount'] ?? 0), 2); ?></span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>