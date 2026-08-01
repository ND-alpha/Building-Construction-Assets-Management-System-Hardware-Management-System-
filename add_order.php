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

// Order එකක් Submit වූ විට ක්‍රියාත්මක වන කොටස
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $customer_id = (!empty($_POST['customer_id'])) ? intval($_POST['customer_id']) : null;
    
    // Session එකෙන් කෙලින්ම සේවක ID එක ලබා ගැනීම
    $employee_id = isset($_SESSION['employee_id']) ? intval($_SESSION['employee_id']) : 0;
    
    $item_ids = isset($_POST['item_id']) ? $_POST['item_id'] : [];
    $prices = isset($_POST['price']) ? $_POST['price'] : [];
    $qtys = isset($_POST['qty']) ? $_POST['qty'] : [];

    // ප්‍රථමයෙන් මෙම Employee ID එක database එකේ ඇත්තටම ඉන්නවද කියා පරීක්ෂා කිරීම
    $check_emp = $conn->prepare("SELECT employee_id FROM employee WHERE employee_id = ?");
    $check_emp->bind_param("i", $employee_id);
    $check_emp->execute();
    $check_emp->store_result();
    
    if ($check_emp->num_rows === 0) {
        $status = "fk_error"; // වලංගු සේවකයෙක් නොවේ නම් ඇතුළත් කිරීම නතර කරයි
        $check_emp->close();
    } else {
        $check_emp->close();

        $has_items = false;
        foreach($qtys as $q) {
            if(intval($q) > 0) {
                $has_items = true;
                break;
            }
        }

        if(!$has_items) {
            $status = "no_items";
        } else {
            $order_date = date("Y-m-d H:i:s");
            
            try {
                // Database Transactions භාවිතයෙන් ආරක්ෂාව වැඩි කිරීම
                $conn->begin_transaction();

                $stmt = $conn->prepare("INSERT INTO orders (customer_id, employee_id, order_date) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $customer_id, $employee_id, $order_date);
                
                if($stmt->execute()) {
                    $order_id = $conn->insert_id;
                    $stmt->close();

                    for($i = 0; $i < count($item_ids); $i++) {
                        $item_id = intval($item_ids[$i]);
                        $price = floatval($prices[$i]);
                        $qty = intval($qtys[$i]);

                        if($qty > 0) {
                            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                            $item_stmt->bind_param("iiid", $order_id, $item_id, $qty, $price);
                            $item_stmt->execute();
                            $item_stmt->close();

                            $update_stock = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?");
                            $update_stock->bind_param("ii", $qty, $item_id);
                            $update_stock->execute();
                            $update_stock->close();
                        }
                    }
                    
                    $conn->commit();
                    $status = "success";
                } else {
                    $status = "db_error";
                }
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $status = "fk_error"; 
            }
        }
    }
}

// Customers සහ Items දත්ත නැවත ලබා ගැනීම
$customers = $conn->query("SELECT * FROM customer ORDER BY customer_name ASC");
$items = $conn->query("SELECT * FROM inventory ORDER BY item_name ASC");

$items_array = [];
while($row = $items->fetch_assoc()) {
    $items_array[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Order - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }

        .main-content {
            margin-left: 280px; width: calc(100% - 280px); padding: 40px;
            display: flex; flex-direction: column; align-items: center;
        }

        .page-header { width: 100%; max-width: 850px; margin-bottom: 30px; }
        .page-header h1 { font-size: 26px; font-weight: 700; color: #1e293b; }

        .form-card {
            background: white; width: 100%; max-width: 850px; padding: 40px;
            border-radius: 20px; border: 1px solid #edf2f7; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
        }

        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px; }
        .form-group label { font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        
        select, input[type="number"] {
            width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px;
            outline: none; font-size: 14px; color: #1e293b; background: #f8fafc; transition: all 0.3s ease;
        }
        select:focus, input[type="number"]:focus { border-color: #06b6d4; background: white; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1); }

        .selector-box {
            background: #f1f5f9; padding: 20px; border-radius: 12px; margin-bottom: 25px;
            border: 1px dashed #cbd5e1; display: flex; flex-direction: column; gap: 10px;
        }

        h3 { font-size: 15px; color: #0f172a; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }

        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 30px; border-radius: 12px; overflow: hidden; border: 1px solid #f1f5f9; }
        th { background: #0f172a; color: #f8fafc; padding: 16px; font-weight: 600; font-size: 13px; text-transform: uppercase; text-align: left; }
        td { padding: 16px; color: #334155; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge.low { background: #fff3cd; color: #856404; }
        .badge.available { background: #e2f8e9; color: #157337; }

        .btn-remove { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .btn-remove:hover { color: #b91c1c; transform: scale(1.1); }

        .btn-submit {
            width: 100%; padding: 16px; background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            border: none; color: white; font-size: 16px; font-weight: 600; border-radius: 12px;
            cursor: pointer; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3); display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4); }

        .alert { width: 100%; max-width: 850px; padding: 14px 20px; border-radius: 12px; font-weight: 500; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert.success { background: #e2f8e9; color: #157337; border-left: 5px solid #22c55e; }
        .alert.error { background: #fde8e8; color: #c81e1e; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Create Customer Order</h1>
        </div>

        <?php if(!empty($status)): ?>
            <?php if($status == 'success'): ?>
                <div class="alert success"><i class="fa-solid fa-circle-check"></i> Order created successfully & Stock inventory updated!</div>
            <?php elseif($status == 'no_items'): ?>
                <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> Please add at least one item with a valid quantity before saving.</div>
            <?php elseif($status == 'db_error'): ?>
                <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> Database Error: Could not generate the order sequence.</div>
            <?php elseif($status == 'fk_error'): ?>
                <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i> Error: Logged-in Employee ID (ID: <?php echo $employee_id; ?>) is invalid or missing in Employee database! Please re-login.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="">
                <input type="hidden" name="place_order" value="1">

                <div class="form-group">
                    <label>Select Customer</label>
                    <select name="customer_id">
                        <option value="">-- Walk-in Customer (No Account) --</option>
                        <?php foreach($customers as $c){ ?>
                            <option value="<?php echo $c['customer_id']; ?>"><?php echo $c['customer_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="selector-box">
                    <h3><i class="fa-solid fa-magnifying-glass"></i> Quick Add Item</h3>
                    <select id="itemPicker" onchange="addItemToOrder()">
                        <option value="">-- Type or Select Item to Add --</option>
                        <?php foreach($items_array as $i){ 
                            if($i['quantity'] <= 0) continue; 
                            $stock_status = ($i['quantity'] < 10) ? ' (Low Stock: '.$i['quantity'].')' : ' (Available: '.$i['quantity'].')';
                        ?>
                            <option value="<?php echo $i['item_id']; ?>"><?php echo $i['item_name'] . " - Rs." . $i['price'] . $stock_status; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <h3>Selected Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th style="width: 150px;">Price</th>
                            <th style="width: 150px;">Qty</th>
                            <th style="width: 60px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="orderTableBody">
                        <tr id="emptyRow">
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No items added yet. Choose from the picker above.</td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-cart-shopping"></i> Complete & Place Order
                </button>
            </form>
        </div>
    </div>

    <script>
        const availableItems = <?php echo json_encode($items_array); ?>;
        const addedItemIds = new Set();

        function addItemToOrder() {
            const picker = document.getElementById('itemPicker');
            const itemId = picker.value;
            
            if (!itemId) return;

            if (addedItemIds.has(itemId)) {
                alert("This item is already added to the order list!");
                picker.value = "";
                return;
            }

            const item = availableItems.find(i => i.item_id == itemId);
            if (!item) return;

            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            const tbody = document.getElementById('orderTableBody');
            
            const stockBadge = (item.quantity < 10) 
                ? `<span class="badge low">Low Stock (${item.quantity})</span>` 
                : `<span class="badge available">Available (${item.quantity})</span>`;

            const tr = document.createElement('tr');
            tr.id = `row_${item.item_id}`;
            tr.innerHTML = `
                <td>
                    <strong>${item.item_name}</strong><br>${stockBadge}
                    <input type="hidden" name="item_id[]" value="${item.item_id}">
                </td>
                <td>
                    <strong>Rs. ${parseFloat(item.price).toFixed(2)}</strong>
                    <input type="hidden" name="price[]" value="${item.price}">
                </td>
                <td>
                    <input type="number" name="qty[]" value="1" min="1" max="${item.quantity}" required>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn-remove" onclick="removeItemFromOrder('${item.item_id}')">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            addedItemIds.add(itemId);
            picker.value = "";
        }

        function removeItemFromOrder(itemId) {
            const row = document.getElementById(`row_${itemId}`);
            if (row) row.remove();
            
            addedItemIds.delete(itemId);

            if (addedItemIds.size === 0) {
                const tbody = document.getElementById('orderTableBody');
                tbody.innerHTML = `
                    <tr id="emptyRow">
                        <td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No items added yet. Choose from the picker above.</td>
                    </tr>
                `;
            }
        }
    </script>
</body>
</html>