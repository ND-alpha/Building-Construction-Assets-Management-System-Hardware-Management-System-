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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = intval($_POST['customer_id']);
    $employee_id = intval($_POST['employee_id']);
    
    $item_ids = $_POST['item_id'];
    $prices = $_POST['price'];
    $qtys = $_POST['qty'];

    // 1. මුලින්ම පාරිභෝගිකයා ඇත්තටම යම් භාණ්ඩයක් තෝරාගෙන තිබේදැයි පරික්ෂා කිරීම
    $has_items = false;
    foreach($qtys as $q) {
        if(intval($q) > 0) {
            $has_items = true;
            break;
        }
    }

    if(!$has_items) {
        header("Location: orders.php?status=no_items");
        exit();
    }

    // 2. ප්‍රධාන Order එක `orders` (හෝ ඔයාගේ db එකේ ඇති order ටේබල් එක) වෙත ඇතුලත් කිරීම
    // (මෙහි මුළු එකතුව පසුව ගණනය කර Update කල හැක, දැනට සරලව ඇතුලත් කරමු)
    $order_date = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, employee_id, order_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $customer_id, $employee_id, $order_date);
    
    if($stmt->execute()) {
        $order_id = $conn->insert_id; // අලුතින් හැදුණු Order ID එක ලබා ගැනීම
        $stmt->close();

        // 3. හැම Item එකක්ම ලූප් එකක් හරහා පරික්ෂා කර Stock අඩු කිරීම
        for($i = 0; $i < count($item_ids); $i++) {
            $item_id = intval($item_ids[$i]);
            $price = floatval($prices[$i]);
            $qty = intval($qtys[$i]);

            if($qty > 0) {
                // ა) Order Items ටේබල් එකට විස්තර ඇතුලත් කිරීම
                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                $item_stmt->bind_param("iiid", $order_id, $item_id, $qty, $price);
                $item_stmt->execute();
                $item_stmt->close();

                // ආ) ⚠️ විශේෂම කොටස: Inventory එකෙන් Stock ප්‍රමාණය අඩු කිරීම ⚠️
                $update_stock = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?");
                $update_stock->bind_param("ii", $qty, $item_id);
                $update_stock->execute();
                $update_stock->close();
            }
        }

        // සාර්ථකව අවසන් වූ පසු නැවත Form එක වෙත එවයි
        header("Location: orders.php?status=success");
        exit();

    } else {
        header("Location: orders.php?status=db_error");
        exit();
    }
}
?>