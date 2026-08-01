<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ලොග් වී නැත්නම් ලොගින් පිටුවට හරවා යැවීම
if(!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "hardware");
    if($conn->connect_error){
        die("Database Error : ".$conn->connect_error);
    }
}

// ----------------------------------------------------------------
// Dynamic Shop Settings Fetch
// ----------------------------------------------------------------
$sys_shop_name = "FixIt Hardware";
$settings_query = $conn->query("SELECT shop_name FROM system_settings WHERE id = 1");
if ($settings_query && $settings_query->num_rows > 0) {
    $setting_data = $settings_query->fetch_assoc();
    $sys_shop_name = !empty($setting_data['shop_name']) ? $setting_data['shop_name'] : $sys_shop_name;
}

// ==========================================
// ORDER STATUS UPDATE (AJAX)
// ==========================================
if(isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $ord_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $update_stmt->bind_param("si", $status, $ord_id);
    
    if($update_stmt->execute()) {
        echo "Success";
    } else {
        echo "Error";
    }
    $update_stmt->close();
    exit();
}

// ==========================================
// FETCH ORDERS WITH REAL TOTAL AMOUNT & CORRECT EMPLOYEE NAME
// ==========================================
$query = "
    SELECT 
        o.order_id, 
        o.order_date, 
        o.status,
        COALESCE(c.customer_name, 'Walk-in Customer') AS customer_name,
        COALESCE(e.full_name, 'System Admin') AS employee_name,
        COALESCE(SUM(oi.quantity * oi.price), 0) AS calculated_total
    FROM orders o
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    LEFT JOIN employee e ON o.employee_id = e.employee_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - <?php echo htmlspecialchars($sys_shop_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --body-bg: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(241, 245, 249, 0.6);
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.03);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.05);
            --table-th-bg: #f8fafc;
            --table-tr-hover: rgba(248, 250, 252, 0.6);
        }

        [data-theme="dark"], body.dark-theme {
            --body-bg: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --glass-bg: rgba(30, 41, 59, 0.45);
            --glass-border: rgba(255, 255, 255, 0.05);
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.2);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.4);
            --table-th-bg: #1e293b;
            --table-tr-hover: rgba(255, 255, 255, 0.02);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; transition: background-color 0.25s, color 0.25s; }
        body { background: var(--body-bg); display: flex; min-height: 100vh; color: var(--text-main); }

        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 40px 50px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .top-bar h1 { font-size: 32px; font-weight: 800; letter-spacing: -0.75px; }

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--glass-border);
            overflow-x: auto;
        }

        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th {
            background: var(--table-th-bg);
            color: var(--text-muted);
            padding: 16px 20px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            border-bottom: 2px solid var(--glass-border);
        }
        td { padding: 16px 20px; font-size: 14px; border-bottom: 1px solid var(--glass-border); color: var(--text-main); }
        tr:hover td { background-color: var(--table-tr-hover); }

        .status-select {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            border: 1px solid transparent;
            cursor: pointer;
            outline: none;
            text-transform: uppercase;
        }
        .status-completed { background: rgba(22, 163, 74, 0.15); color: #16a34a; }
        .status-pending { background: rgba(217, 119, 6, 0.15); color: #d97706; }

        .btn-view {
            background: rgba(6, 182, 212, 0.1);
            color: #0891b2;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(6, 182, 212, 0.2);
            transition: all 0.2s ease;
        }
        .btn-view:hover { background: #0891b2; color: #fff; }

        @media(max-width: 1200px) { .main-content { margin-left: 0; width: 100%; padding: 20px; } }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="top-bar">
            <h1>Order Registry & Control</h1>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Details</th>
                        <th>Handled By</th>
                        <th>Placement Date</th>
                        <th>Total Amount</th>
                        <th>Order Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()){ 
                            $db_id = $row['order_id'];
                            $current_status = $row['status'] ?? 'Pending';
                            $select_class = ($current_status == 'Completed') ? 'status-completed' : 'status-pending';
                        ?>
                        <tr>
                            <td><strong>#ORD-<?php echo $db_id; ?></strong></td>
                            <td><i class="fa-solid fa-user" style="color: var(--text-muted); margin-right: 8px;"></i><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            
                            <td><i class="fa-solid fa-id-card" style="color: var(--text-muted); margin-right: 8px;"></i><?php echo htmlspecialchars($row['employee_name']); ?></td>
                            
                            <td><i class="fa-regular fa-clock" style="color: var(--text-muted); margin-right: 8px;"></i><?php echo date('M d, Y - h:i A', strtotime($row['order_date'])); ?></td>
                            
                            <td><strong style="color: var(--text-main);">Rs. <?php echo number_format($row['calculated_total'], 2); ?></strong></td>
                            
                            <td>
                                <select class="status-select <?php echo $select_class; ?>" onchange="updateOrderStatus(this, <?php echo $db_id; ?>)">
                                    <option value="Pending" <?php if($current_status == 'Pending') echo 'selected'; ?>>⏳ Pending</option>
                                    <option value="Completed" <?php if($current_status == 'Completed') echo 'selected'; ?>>✅ Completed</option>
                                </select>
                            </td>
                            
                            <td>
                                <a href="view_order_items.php?order_id=<?php echo $db_id; ?>" class="btn-view">
                                    <i class="fa-regular fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php 
                        }
                    } else { ?>
                        <tr><td colspan="7" style="text-align:center; color: var(--text-muted);">No orders found</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updateOrderStatus(selectElement, orderId) {
            const newStatus = selectElement.value;
            
            if(newStatus === 'Completed') {
                selectElement.className = 'status-select status-completed';
            } else {
                selectElement.className = 'status-select status-pending';
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "manage_order.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if(xhr.responseText.trim() === "Success") {
                        console.log("Order #" + orderId + " updated to " + newStatus);
                    } else {
                        alert("Failed to update status. Try again.");
                    }
                }
            };
            xhr.send("update_status=1&order_id=" + orderId + "&status=" + newStatus);
        }

        // Theme Sync
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