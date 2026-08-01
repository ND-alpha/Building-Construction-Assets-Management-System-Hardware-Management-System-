<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hardware");

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subcategory'])) {
    $cat_id = intval($_POST['category_id']);
    $sub_cat_name = trim($_POST['sub_category_name']);
    
    if ($cat_id > 0 && !empty($sub_cat_name)) {
        $stmt = $conn->prepare("INSERT INTO sub_categories (category_id, sub_category_name) VALUES (?, ?)");
        $stmt->bind_param("is", $cat_id, $sub_cat_name);
        if ($stmt->execute()) {
            $message = "Sub Category added successfully!";
        } else {
            $message = "Error adding sub category.";
        }
        $stmt->close();
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$sub_categories = $conn->query("SELECT s.*, c.category_name FROM sub_categories s JOIN categories c ON s.category_id = c.category_id ORDER BY c.category_name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Sub Categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f8fafc; padding: 40px; }
        .container { max-width: 500px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin: 0 auto; }
        select, input, button { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        button { background: #0284c7; color: white; font-weight: bold; cursor: pointer; border: none; }
        ul { list-style: none; padding: 0; margin-top: 20px; }
        li { padding: 10px; background: #f1f5f9; margin-bottom: 5px; border-radius: 6px; }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fa-solid fa-tags"></i> Add New Sub Category</h2>
    <?php if($message) echo "<p style='color:green;'>$message</p>"; ?>
    
    <form method="POST">
        <label>Select Main Category:</label>
        <select name="category_id" required>
            <option value="">-- Choose Main Category --</option>
            <?php while($cat = $categories->fetch_assoc()): ?>
                <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
            <?php endwhile; ?>
        </select>
        
        <input type="text" name="sub_category_name" required placeholder="e.g., Wood Glue, Hinges, Screws">
        <button type="submit" name="add_subcategory">Save Sub Category</button>
    </form>

    <h3>Existing Sub Categories</h3>
    <ul>
        <?php while($row = $sub_categories->fetch_assoc()): ?>
            <li><strong><?php echo $row['sub_category_name']; ?></strong> <span style="font-size:12px; color:#64748b;">(Under: <?php echo $row['category_name']; ?>)</span></li>
        <?php endwhile; ?>
    </ul>
    <br>
    <a href="add_product.php" style="color: #0284c7; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Add Product</a>
</div>
</body>
</html>