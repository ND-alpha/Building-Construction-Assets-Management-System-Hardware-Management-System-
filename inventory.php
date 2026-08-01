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

// Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "hardware");

if($conn->connect_error){
    die("Database Connection Failed : " . $conn->connect_error);
}

$message = "";
$message_type = "";

// භාණ්ඩයක් Delete කිරීමට අදාළ කොටස
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = "Item successfully removed from inventory!";
        $message_type = "success";
    } else {
        $message = "Unable to delete item. Try again!";
        $message_type = "error";
    }
    $stmt->close();
}

// දැනට පියවර තීරණය කිරීම (URL parameters අනුව)
$view = "main_categories"; 
$main_category_id = 0;
$sub_category_id = 0;

if (isset($_GET['sub_id'])) {
    $view = "items";
    $sub_category_id = intval($_GET['sub_id']);
} elseif (isset($_GET['main_id'])) {
    $view = "sub_categories";
    $main_category_id = intval($_GET['main_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: #f8fafc;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 280px; 
            width: calc(100% - 280px);
            padding: 40px;
        }

        .page-header {
            width: 100%;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
        }

        /* ආපසු යාමේ බොත්තම (Breadcrumb / Back Button) */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: color 0.2s;
        }
        .btn-back:hover {
            color: #0284c7;
        }

        .btn-add {
            padding: 12px 22px;
            background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%);
            border: none;
            border-radius: 12px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.3);
        }

        /* --- CARGILLS APP STYLE GRID --- */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 10px;
        }

        .category-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #edf2f7;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #1e293b;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.05);
            border-color: #06b6d4;
        }

        .category-image-wrapper {
            width: 100px;
            height: 100px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .category-image-wrapper img {
            width: 60%;
            height: 60%;
            object-fit: contain;
        }

        /* Default placeholder icon if no image available */
        .category-image-wrapper i {
            font-size: 40px;
            color: #0284c7;
        }

        .category-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #334155;
        }

        /* --- TABLE CARD --- */
        .table-card {
            background: white;
            width: 100%;
            border-radius: 20px;
            border: 1px solid #edf2f7;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .table-header {
            padding: 24px 30px;
            border-bottom: 2px solid #f1f5f9;
        }

        .table-header h3 {
            font-size: 18px;
            color: #0f172a;
            font-weight: 600;
        }

        .responsive-table {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #f8fafc;
            padding: 16px 30px;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 18px 30px;
            color: #334155;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-category { background: #e0f2fe; color: #0369a1; }
        .badge-brand { background: #f0fdf4; color: #166534; }
        .badge-measure { background: #fef3c7; color: #92400e; }

        .actions-cell { display: flex; gap: 10px; }
        .btn-action {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 14px; transition: all 0.2s ease;
        }
        .btn-delete { background: #fee2e2; color: #ef4444; }
        .btn-delete:hover { background: #ef4444; color: white; }

        .alert {
            padding: 14px 20px; border-radius: 12px; font-weight: 500; font-size: 14px;
            margin-bottom: 25px; display: flex; align-items: center; gap: 12px;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert.success { background: #e2f8e9; color: #157337; border-left: 5px solid #22c55e; }
        .alert.error { background: #fde8e8; color: #c81e1e; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <?php if(!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($view == "main_categories"): ?>
            <div class="page-header">
                <h1>Product Categories</h1>
                <a href="add_product.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i> Add New Product
                </a>
            </div>

            <div class="category-grid">
                <?php
                // ප්‍රධාන කැටගරි ටික ඩේටාබේස් එකෙන් ගැනීම
                $main_cat_res = $conn->query("SELECT * FROM main_categories ORDER BY category_name ASC");
                if ($main_cat_res && $main_cat_res->num_rows > 0):
                    while($m_row = $main_cat_res->fetch_assoc()):
                        // Cargills App එකේ වගේ එක එක කැටගරි එකට අදාළ Icons මෙතනින් සකසන්න පුළුවන්
                        $icon = "fa-box"; 
                        if(stripos($m_row['category_name'], 'paint') !== false) $icon = "fa-paint-roller";
                        if(stripos($m_row['category_name'], 'fastener') !== false || stripos($m_row['category_name'], 'nail') !== false) $icon = "fa-screwdriver-wrench";
                        if(stripos($m_row['category_name'], 'tap') !== false || stripos($m_row['category_name'], 'plumb') !== false) $icon = "fa-faucet";
                ?>
                        <a href="inventory.php?main_id=<?php echo $m_row['main_category_id']; ?>" class="category-card">
                            <div class="category-image-wrapper">
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            </div>
                            <h3><?php echo $m_row['category_name']; ?></h3>
                        </a>
                <?php 
                    endwhile;
                else:
                    echo "<p style='color:#94a3b8;'>No categories found. Please add main categories to database.</p>";
                endif; 
                ?>
            </div>

        <?php elseif ($view == "sub_categories"): 
            // තෝරාගත් ප්‍රධාන කැටගරියේ නම ලබා ගැනීම
            $main_name_q = $conn->query("SELECT category_name FROM main_categories WHERE main_category_id = $main_category_id");
            $main_name_row = $main_name_q->fetch_assoc();
        ?>
            <a href="inventory.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Main Categories</a>
            
            <div class="page-header">
                <h1><?php echo $main_name_row['category_name']; ?> ➔ Sub Categories</h1>
            </div>

            <div class="category-grid">
                <?php
                // උප කැටගරි ටික ඩේටාබේස් එකෙන් ගැනීම
                $sub_cat_res = $conn->query("SELECT * FROM sub_categories WHERE main_category_id = $main_category_id ORDER BY sub_category_name ASC");
                if ($sub_cat_res && $sub_cat_res->num_rows > 0):
                    while($s_row = $sub_cat_res->fetch_assoc()):
                ?>
                        <a href="inventory.php?sub_id=<?php echo $s_row['sub_category_id']; ?>" class="category-card">
                            <div class="category-image-wrapper">
                                <i class="fa-solid fa-layer-group" style="color: #06b6d4;"></i>
                            </div>
                            <h3><?php echo $s_row['sub_category_name']; ?></h3>
                        </a>
                <?php 
                    endwhile;
                else:
                    echo "<p style='color:#94a3b8;'>No sub-categories found for this category.</p>";
                endif; 
                ?>
            </div>

        <?php elseif ($view == "items"): 
            // තෝරාගත් උප කැටගරියේ නම සහ ප්‍රධාන කැටගරි ID එක ලබා ගැනීම
            $sub_info_q = $conn->query("SELECT sub_category_name, main_category_id FROM sub_categories WHERE sub_category_id = $sub_category_id");
            $sub_info = $sub_info_q->fetch_assoc();
        ?>
            <a href="inventory.php?main_id=<?php echo $sub_info['main_category_id']; ?>" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Sub Categories</a>

            <div class="page-header">
                <h1>Items in "<?php echo $sub_info['sub_category_name']; ?>"</h1>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h3><i class="fa-solid fa-list-check" style="color: #06b6d4; margin-right: 8px;"></i> Stock Levels</h3>
                </div>
                
                <div class="responsive-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Brand</th>
                                <th>Size / Measure</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Price (Rs.)</th>
                                <th>Supplier</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // අලුත් 4-level structure එක JOIN කරලා අදාළ බඩු ටික විතරක් ගැනීම
                            $query = "SELECT i.*, b.brand_name, m.measurement_value, s.supplier_name 
                                      FROM inventory i 
                                      LEFT JOIN brands b ON i.brand_id = b.brand_id
                                      LEFT JOIN item_measurements m ON i.measurement_id = m.measurement_id
                                      LEFT JOIN supplier s ON i.supplier_id = s.supplier_id 
                                      WHERE i.sub_category_id = $sub_category_id
                                      ORDER BY i.item_id DESC";

                            $inventory_result = $conn->query($query);

                            if($inventory_result && $inventory_result->num_rows > 0): 
                                while($row = $inventory_result->fetch_assoc()): 
                            ?>
                                    <tr>
                                        <td>#<?php echo $row['item_id']; ?></td>
                                        <td><strong><?php echo $row['item_name']; ?></strong></td>
                                        <td><span class="badge badge-brand"><?php echo $row['brand_name'] ? $row['brand_name'] : 'Generic'; ?></span></td>
                                        <td><span class="badge badge-measure"><?php echo $row['measurement_value'] ? $row['measurement_value'] : 'N/A'; ?></span></td>
                                        <td><?php echo $row['quantity']; ?></td>
                                        <td><?php echo $row['unit']; ?></td>
                                        <td><?php echo number_format($row['price'], 2); ?></td>
                                        <td><?php echo $row['supplier_name'] ? $row['supplier_name'] : '<i style="color:#94a3b8;">Not Assigned</i>'; ?></td>
                                        <td class="actions-cell">
                                            <a href="inventory.php?sub_id=<?php echo $sub_category_id; ?>&delete_id=<?php echo $row['item_id']; ?>" 
                                               class="btn-action btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this item?');" 
                                               title="Delete Item">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fa-solid fa-box-open" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                                        No items found under this sub-category.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>