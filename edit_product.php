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

// -------------------------------------------------------------------------
// 1. AJAX REQUEST HANDLE කිරීම (Category එකක් තෝරන විට Sub Categories JSON ලෙස ලබා දීම)
// -------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'get_subcategories' && isset($_GET['category_id'])) {
    header('Content-Type: application/json');
    $cat_id = intval($_GET['category_id']);
    
    $stmt = $conn->prepare("SELECT sub_category_id, sub_category_name FROM sub_categories WHERE category_id = ? ORDER BY sub_category_name ASC");
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sub_categories = [];
    while ($row = $result->fetch_assoc()) {
        $sub_categories[] = $row;
    }
    
    echo json_encode($sub_categories);
    $stmt->close();
    $conn->close();
    exit();
}

$error_msg = "";
$success_msg = "";

// 2. URL එකෙන් එන ID එකට අදාළ දත්ත ලබා ගැනීම
if(isset($_GET['id'])){
    $item_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $item = $result->fetch_assoc();
    } else {
        die("Product not found!");
    }
    $stmt->close();
} else {
    header("Location: manage_inventory.php");
    exit();
}

// 3. Form එක Submit කල පසු දත්ත Update කිරීම (POST)
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])){
    $item_name = trim($_POST['item_name']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $sub_category_id = !empty($_POST['sub_category_id']) ? intval($_POST['sub_category_id']) : null;
    $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $quantity = intval($_POST['quantity']);
    $unit = trim($_POST['unit']);
    $measurement = trim($_POST['measurement']);
    $price = floatval($_POST['price']);
    $supplier_id = intval($_POST['supplier_id']);

    if (!empty($item_name) && $category_id > 0 && $price > 0 && $supplier_id > 0) {
        
        // අලුත් ව්‍යුහයට අනුව UPDATE Query එක
        $update_stmt = $conn->prepare("UPDATE inventory SET item_name=?, category_id=?, sub_category_id=?, brand_id=?, quantity=?, unit=?, measurement=?, price=?, supplier_id=? WHERE item_id=?");
        $update_stmt->bind_param("siiiissdii", $item_name, $category_id, $sub_category_id, $brand_id, $quantity, $unit, $measurement, $price, $supplier_id, $item_id);
        
        if($update_stmt->execute()){
            header("Location: manage_inventory.php?msg=updated");
            exit();
        } else {
            $error_msg = "Error updating product: " . $conn->error;
        }
        $update_stmt->close();
    } else {
        $error_msg = "Please fill all required fields with valid details.";
    }
}

// Dropdowns සඳහා දත්ත ලබා ගැනීම
$categories_result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$brands_result = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM supplier ORDER BY supplier_name ASC");

// දැනට පවතින Category එකට අදාළ Sub Categories ලැයිස්තුව (Dropdown එක කලින්ම පිරවීමට)
$current_sub_categories = [];
if (!empty($item['category_id'])) {
    $sub_stmt = $conn->prepare("SELECT sub_category_id, sub_category_name FROM sub_categories WHERE category_id = ? ORDER BY sub_category_name ASC");
    $sub_stmt->bind_param("i", $item['category_id']);
    $sub_stmt->execute();
    $sub_res = $sub_stmt->get_result();
    while ($sub_row = $sub_res->fetch_assoc()) {
        $current_sub_categories[] = $sub_row;
    }
    $sub_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 40px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 26px; color: #1e293b; font-weight: 700; }
        
        .form-card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; max-width: 650px; margin: 0 auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        
        .form-group label { font-weight: 600; color: #475569; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; outline: none; transition: 0.2s; background: #f8fafc; color: #1e293b; }
        .form-group input:focus, .form-group select:focus { border-color: #06b6d4; background: white; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1); }
        
        .btn-group { display: flex; gap: 15px; margin-top: 25px; justify-content: flex-end; grid-column: span 2; }
        .btn-save { background: linear-gradient(135deg, #06b6d4, #0284c7); color: white; padding: 14px 30px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3); }
        .btn-cancel { background: #edf2f7; color: #64748b; padding: 14px 30px; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-block; transition: 0.2s; text-align: center; }
        .btn-cancel:hover { background: #e2e8f0; color: #475569; }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
        .alert-danger { background: #fde8e8; color: #c81e1e; border-left: 4px solid #f05252; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Edit Product Details (#<?php echo $item['item_id']; ?>)</h1>
        </div>

        <div class="form-card">
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Product Name</label>
                        <input type="text" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Main Category</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Choose Category --</option>
                            <?php while($cat = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $item['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sub Category</label>
                        <select name="sub_category_id" id="sub_category_id">
                            <option value="">-- Choose Sub Category --</option>
                            <?php foreach($current_sub_categories as $subCat): ?>
                                <option value="<?php echo $subCat['sub_category_id']; ?>" <?php echo ($subCat['sub_category_id'] == $item['sub_category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subCat['sub_category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Brand / Manufacturer</label>
                        <select name="brand_id">
                            <option value="">-- Select Brand --</option>
                            <?php while($brand = $brands_result->fetch_assoc()): ?>
                                <option value="<?php echo $brand['brand_id']; ?>" <?php echo ($brand['brand_id'] == $item['brand_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['brand_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Measurement (Size / Volume)</label>
                        <input type="text" name="measurement" value="<?php echo htmlspecialchars($item['measurement'] ?? ''); ?>" placeholder="e.g. 4 Liters, 3 Inch">
                    </div>

                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" required>
                            <option value="">-- Select Supplier --</option>
                            <?php while($sup = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $sup['supplier_id']; ?>" <?php echo ($sup['supplier_id'] == $item['supplier_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sup['supplier_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Unit of Measure</label>
                        <input type="text" name="unit" value="<?php echo htmlspecialchars($item['unit']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Available Stock (Quantity)</label>
                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Unit Price (Rs.)</label>
                        <input type="number" step="0.01" name="price" value="<?php echo $item['price']; ?>" required>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="update_product" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Update Details</button>
                        <a href="manage_inventory.php" class="btn-cancel">Cancel</a>
                    </div>
                    
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('category_id').addEventListener('change', function() {
        const categoryId = this.value;
        const subCategoryDropdown = document.getElementById('sub_category_id');
        
        subCategoryDropdown.innerHTML = '<option value="">-- Loading Sub Categories... --</option>';
        
        if (categoryId === '') {
            subCategoryDropdown.innerHTML = '<option value="">-- Choose Sub Category --</option>';
            return;
        }
        
        // edit_product.php පිටුවටම AJAX Request එක යවයි
        fetch(`edit_product.php?action=get_subcategories&category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                subCategoryDropdown.innerHTML = '<option value="">-- Choose Sub Category --</option>';
                if (data.length > 0) {
                    data.forEach(subCat => {
                        const option = document.createElement('option');
                        option.value = subCat.sub_category_id;
                        option.textContent = subCat.sub_category_name;
                        subCategoryDropdown.appendChild(option);
                    });
                } else {
                    subCategoryDropdown.innerHTML = '<option value="">-- No Sub Categories Found --</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching subcategories:', error);
                subCategoryDropdown.innerHTML = '<option value="">-- Error Loading Data --</option>';
            });
    });
    </script>
</body>
</html>