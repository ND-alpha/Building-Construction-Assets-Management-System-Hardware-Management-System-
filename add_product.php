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
    exit(); // AJAX Request එකක් නම් මෙතනින් කේතය අවසන් කරයි
}

$message = "";
$message_type = "";

// -------------------------------------------------------------------------
// 2. FORM SUBMIT වූ පසු දත්ත ඇතුළත් කිරීම (POST Request)
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = trim($_POST['item_name']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $sub_category_id = !empty($_POST['sub_category_id']) ? intval($_POST['sub_category_id']) : null;
    $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $quantity = intval($_POST['quantity']);
    $unit = trim($_POST['unit']);
    $measurement = trim($_POST['measurement']); // උදා: 4 Liters, 3 Inch, 1KG
    $price = floatval($_POST['price']);
    $supplier_id = intval($_POST['supplier_id']);

    // අනිවාර්ය Fields පරීක්ෂා කිරීම
    if (!empty($item_name) && $category_id > 0 && $price > 0 && $supplier_id > 0) {
        
        // අලුත් ව්‍යුහයට අනුව INSERT Query එක (category_id, sub_category_id, brand_id, measurement සහිතව)
        $stmt = $conn->prepare("INSERT INTO inventory (item_name, category_id, sub_category_id, brand_id, quantity, unit, measurement, price, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiissdi", $item_name, $category_id, $sub_category_id, $brand_id, $quantity, $unit, $measurement, $price, $supplier_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "New product successfully added with Category and Sub-category!";
            $_SESSION['message_type'] = "success";
            
            // සාර්ථක වූ පසු කෙලින්ම manage_inventory.php වෙත යයි
            header("Location: manage_inventory.php?success=1");
            exit();
        } else {
            $message = "Something went wrong. Please try again! (" . $conn->error . ")";
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Please fill all required fields (Product Name, Category, Price & Supplier).";
        $message_type = "error";
    }
}

// Dropdowns සඳහා දත්ත ලබා ගැනීම
$categories_result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$brands_result = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$suppliers_result = $conn->query("SELECT supplier_id, supplier_name FROM supplier ORDER BY supplier_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }
        
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .page-header {
            width: 100%; max-width: 750px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .page-header h1 { font-size: 26px; font-weight: 700; color: #1e293b; }

        .btn-back {
            padding: 10px 18px; background: white; border: 1px solid #e2e8f0; border-radius: 10px;
            color: #475569; text-decoration: none; font-size: 14px; font-weight: 600;
            display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;
        }
        .btn-back:hover { background: #f1f5f9; color: #1e293b; transform: translateX(-2px); }

        .form-card {
            background: white; width: 100%; max-width: 750px; padding: 40px;
            border-radius: 20px; border: 1px solid #edf2f7; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
        }
        .form-card h3 {
            font-size: 18px; color: #0f172a; font-weight: 600; margin-bottom: 25px;
            padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media(max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        @media(max-width: 640px) { .form-group.full-width { grid-column: span 1; } }

        .form-group label { font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select {
            width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px;
            outline: none; font-size: 14px; color: #1e293b; background: #f8fafc; transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus { border-color: #06b6d4; background: white; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1); }

        .btn-container { margin-top: 35px; display: flex; justify-content: flex-end; }
        .btn-submit {
            padding: 14px 35px; background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%);
            border: none; color: white; font-size: 15px; font-weight: 600; border-radius: 12px;
            cursor: pointer; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4); }

        .alert {
            width: 100%; max-width: 750px; padding: 14px 20px; border-radius: 12px;
            font-weight: 500; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;
        }
        .alert.success { background: #e2f8e9; color: #157337; border-left: 5px solid #22c55e; }
        .alert.error { background: #fde8e8; color: #c81e1e; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Add New Stock Item</h1>
            <a href="manage_inventory.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back to Stock
            </a>
        </div>

        <?php if(!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3><i class="fa-solid fa-boxes-stacked" style="color: #06b6d4; margin-right: 8px;"></i> Advanced Product Categorization</h3>
            
            <form method="POST" action="add_product.php">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Product Name / Description <span style="color:red;">*</span></label>
                        <input type="text" name="item_name" required placeholder="e.g. Nippon WeatherCoat, Cement Nails">
                    </div>

                    <div class="form-group">
                        <label>Main Category <span style="color:red;">*</span></label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Choose Main Category --</option>
                            <?php if($categories_result && $categories_result->num_rows > 0): ?>
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sub Category</label>
                        <select name="sub_category_id" id="sub_category_id" disabled>
                            <option value="">-- Select Main Category First --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Brand / Manufacturer</label>
                        <select name="brand_id">
                            <option value="">-- Select Brand (Optional) --</option>
                            <?php if($brands_result && $brands_result->num_rows > 0): ?>
                                <?php while($brand = $brands_result->fetch_assoc()): ?>
                                    <option value="<?php echo $brand['brand_id']; ?>"><?php echo $brand['brand_name']; ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Measurement (Size / Volume)</label>
                        <input type="text" name="measurement" placeholder="e.g. 4 Liters, 3 Inch, 10mm, 1KG">
                    </div>

                    <div class="form-group">
                        <label>Primary Supplier <span style="color:red;">*</span></label>
                        <select name="supplier_id" required>
                            <option value="">-- Select Assigned Supplier --</option>
                            <?php if($suppliers_result && $suppliers_result->num_rows > 0): ?>
                                <?php while($sup = $suppliers_result->fetch_assoc()): ?>
                                    <option value="<?php echo $sup['supplier_id']; ?>"><?php echo $sup['supplier_name']; ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Unit of Measure <span style="color:red;">*</span></label>
                        <input type="text" name="unit" required placeholder="e.g. Pcs, Box, Pack, Roll">
                    </div>

                    <div class="form-group">
                        <label>Opening Stock Quantity</label>
                        <input type="number" name="quantity" min="0" value="0" required>
                    </div>

                    <div class="form-group">
                        <label>Selling Price (Rs.) <span style="color:red;">*</span></label>
                        <input type="number" name="price" step="0.01" min="0" required placeholder="0.00">
                    </div>

                </div>

                <div class="btn-container">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-plus-circle"></i> Save & Insert Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('category_id').addEventListener('change', function() {
        const categoryId = this.value;
        const subCategoryDropdown = document.getElementById('sub_category_id');
        
        // මුලින්ම Sub Category Dropdown එක හිස් කරයි
        subCategoryDropdown.innerHTML = '<option value="">-- Loading Sub Categories... --</option>';
        subCategoryDropdown.disabled = true;
        
        if (categoryId === '') {
            subCategoryDropdown.innerHTML = '<option value="">-- Select Main Category First --</option>';
            return;
        }
        
        // AJAX Fetch Request එක (මේ පිටුවටම Action එකක් ලෙස යවයි)
        fetch(`add_product.php?action=get_subcategories&category_id=${categoryId}`)
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
                    subCategoryDropdown.disabled = false;
                } else {
                    subCategoryDropdown.innerHTML = '<option value="">-- No Sub Categories Found --</option>';
                    subCategoryDropdown.disabled = false; // User ට හිස්ව තබා ඉදිරියට යා හැක
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