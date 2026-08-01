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

// මකන්න අවශ්‍ය වුවහොත් (Delete Feature) - ආරක්ෂාව සඳහා Admin දත්තද මෙහිදී පරීක්ෂා කෙරේ
if(isset($_GET['delete_id'])){
    if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin') {
        $delete_id = intval($_GET['delete_id']);
        $del_stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
        $del_stmt->bind_param("i", $delete_id);
        if($del_stmt->execute()){
            header("Location: manage_inventory.php?msg=deleted");
            exit();
        }
        $del_stmt->close();
    } else {
        header("Location: manage_inventory.php?msg=unauthorized");
        exit();
    }
}

// ✅ නිවැරදි කරන ලද SQL QUERY එක (LEFT JOIN භාවිතා කර categories, sub_categories සහ brands සම්බන්ධ කර ඇත)
$query = "SELECT i.*, 
                 c.category_name, 
                 s.sub_category_name, 
                 b.brand_name, 
                 sup.supplier_name 
          FROM inventory i 
          LEFT JOIN categories c ON i.category_id = c.category_id 
          LEFT JOIN sub_categories s ON i.sub_category_id = s.sub_category_id
          LEFT JOIN brands b ON i.brand_id = b.brand_id
          LEFT JOIN supplier sup ON i.supplier_id = sup.supplier_id 
          ORDER BY i.item_id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=300;400;500;600;700&display=swap" rel="stylesheet">

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

        /* --- CONTENT SECTION --- */
        .main-content {
            margin-left: 280px; /* Sidebar එක සඳහා ඉඩ තැබීම */
            width: calc(100% - 280px);
            padding: 40px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Quick Action Buttons */
        .btn-add {
            background: linear-gradient(45deg, #06b6d4, #0284c7);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.25);
            transition: 0.3s;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(6, 182, 212, 0.4);
        }

        /* --- LIVE FILTER SEARCH BAR --- */
        .search-container {
            background: white;
            padding: 15px 25px;
            border-radius: 14px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
            border: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-container i {
            color: #94a3b8;
            font-size: 18px;
        }

        .search-container input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 15px;
            color: #1e293b;
        }

        .search-container input::placeholder {
            color: #94a3b8;
        }

        /* --- DATA TABLE CARD --- */
        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            padding: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            padding: 18px 24px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            border-bottom: 2px solid #edf2f7;
        }

        td {
            padding: 18px 24px;
            color: #334155;
            font-size: 14px;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        /* Stock Status Badges */
        .badge-qty {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
        }

        .badge-qty.normal {
            background: #e2f8e9;
            color: #157337;
        }

        .badge-qty.danger {
            background: #fde8e8;
            color: #c81e1e;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* Action Buttons Styling (Edit/Delete) */
        .actions-btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .btn-edit {
            background: rgba(14, 165, 233, 0.1);
            color: #0ea5e9;
        }

        .btn-edit:hover {
            background: #0ea5e9;
            color: white;
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        /* Success/Error Alert Toast */
        .toast-msg {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.4s ease;
        }

        .toast-msg.success {
            background: #e2f8e9;
            color: #157337;
            border-left: 5px solid #22c55e;
        }

        .toast-msg.info {
            background: #e0f2fe;
            color: #0369a1;
            border-left: 5px solid #0ea5e9;
        }
        
        .toast-msg.error {
            background: #fde8e8;
            color: #c81e1e;
            border-left: 5px solid #ef4444;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Master Stock Register</h1>
            <a href="add_product.php" class="btn-add">
                <i class="fa-solid fa-plus-circle"></i> Add New Product
            </a>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="toast-msg <?php echo $_SESSION['message_type'] == 'success' ? 'success' : 'info'; ?>">
                <i class="fa-solid <?php echo $_SESSION['message_type'] == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="toast-msg success">
                <i class="fa-solid fa-circle-check"></i> Product removed successfully from database!
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div class="toast-msg info">
                <i class="fa-solid fa-circle-check"></i> Product details updated successfully!
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'unauthorized'): ?>
            <div class="toast-msg error">
                <i class="fa-solid fa-circle-exclamation"></i> Unauthorized Access! Only Admins can delete items.
            </div>
        <?php endif; ?>


        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" onkeyup="filterStockTable()" placeholder="Search products by Item Name, Category, Brand or Supplier...">
        </div>

        <div class="table-card">
            <table id="stockTable">
                <thead>
                    <tr>
                        <th style="width: 80px;">Item ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Brand / Specs</th>
                        <th>Available Stock</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Supplier Info</th>
                        <th style="width: 110px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $stockClass = ($row['quantity'] < 10) ? 'danger' : 'normal';
                            $stockLabel = ($row['quantity'] < 10) ? $row['quantity'] . ' (Low Stock)' : $row['quantity'];
                            
                            // Category සහ Sub category එකතු කර පෙන්වීම
                            $displayCategory = htmlspecialchars($row['category_name'] ?? 'Unassigned');
                            if(!empty($row['sub_category_name'])){
                                $displayCategory .= ' <small style="color:#94a3b8">> ' . htmlspecialchars($row['sub_category_name']) . '</small>';
                            }
                            
                            // Brand සහ Measurement එකතු කර පෙන්වීම
                            $displaySpecs = htmlspecialchars($row['brand_name'] ?? '-');
                            if(!empty($row['measurement'])){
                                $displaySpecs .= ' <small style="color:#64748b">(' . htmlspecialchars($row['measurement']) . ')</small>';
                            }
                        ?>
                        <tr>
                            <td><strong>#<?php echo $row['item_id']; ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                            <td><span style="font-weight: 500;"><?php echo $displayCategory; ?></span></td>
                            <td><span><?php echo $displaySpecs; ?></span></td>
                            <td><span class="badge-qty <?php echo $stockClass; ?>"><?php echo $stockLabel; ?></span></td>
                            <td><?php echo htmlspecialchars($row['unit']); ?></td>
                            <td><strong style="color: #0f172a;">Rs. <?php echo number_format($row['price'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'Not Assigned'); ?></td>
                            <td>
                                <div class="actions-btn-group">
                                    <a href="edit_product.php?id=<?php echo $row['item_id']; ?>" class="btn-action btn-edit" title="Edit Item Details">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="manage_inventory.php?delete_id=<?php echo $row['item_id']; ?>" 
                                    class="btn-action btn-delete" 
                                    title="Delete Product" 
                                    onclick="return confirm('Are you completely sure you want to permanently delete this product? \n\nClick [OK/Yes] to Delete or [Cancel/No] to Go Back.');">
                                    <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 50px;">
                                <i class="fa-solid fa-box-open" style="font-size: 40px; margin-bottom: 10px; display:block;"></i>
                                No stock items registered in the system yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

<script>
    function filterStockTable() {
        let input = document.getElementById("searchInput");
        let filter = input.value.toUpperCase();
        let table = document.getElementById("stockTable");
        let tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            let rowContainsSearchQuery = false;
            let tds = tr[i].getElementsByTagName("td");
            
            let nameColumn = tds[1];
            let catColumn = tds[2];
            let brandColumn = tds[3]; 
            let qtyColumn = tds[4]; 
            let supColumn = tds[7];
            
            if (nameColumn || catColumn || brandColumn || supColumn) {
                let nameText = nameColumn.textContent || nameColumn.innerText;
                let catText = catColumn.textContent || catColumn.innerText;
                let brandText = brandColumn.textContent || brandColumn.innerText;
                let supText = supColumn.textContent || supColumn.innerText;
                
                if (nameText.toUpperCase().indexOf(filter) > -1 || 
                    catText.toUpperCase().indexOf(filter) > -1 ||
                    brandText.toUpperCase().indexOf(filter) > -1 ||
                    supText.toUpperCase().indexOf(filter) > -1) {
                    rowContainsSearchQuery = true;
                }
            }

            if (filter === "LOW STOCK" && qtyColumn) {
                let qtyText = qtyColumn.textContent || qtyColumn.innerText;
                let qtyValue = parseInt(qtyText.trim());
                
                if (!isNaN(qtyValue) && qtyValue < 10) {
                    rowContainsSearchQuery = true;
                } else {
                    rowContainsSearchQuery = false; 
                }
            }

            if (rowContainsSearchQuery) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }

    window.addEventListener('DOMContentLoaded', (event) => {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('filter') === 'low') {
            let searchInput = document.getElementById("searchInput");
            if (searchInput) {
                searchInput.value = "Low Stock"; 
                filterStockTable(); 
            }
        }
    });
</script>
</body>
</html>