<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hardware");

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $cat_name = trim($_POST['category_name']);
    if (!empty($cat_name)) {
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param("s", $cat_name);
        if ($stmt->execute()) {
            $message = "Category added successfully!";
        } else {
            $message = "Error: Category might already exist.";
        }
        $stmt->close();
    }
}
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f8fafc; padding: 40px; }
        .container { max-width: 500px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin: 0 auto; }
        input, button { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        button { background: #06b6d4; color: white; font-weight: bold; cursor: pointer; border: none; }
        ul { list-style: none; padding: 0; margin-top: 20px; }
        li { padding: 10px; background: #f1f5f9; margin-bottom: 5px; border-radius: 6px; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fa-solid fa-folder-plus"></i> Add New Main Category</h2>
    <?php if($message) echo "<p style='color:green;'>$message</p>"; ?>
    
    <form method="POST">
        <input type="text" name="category_name" required placeholder="e.g., Carpentry, Roofing">
        <button type="submit" name="add_category">Save Category</button>
    </form>

    <h3>Existing Categories</h3>
    <ul>
        <?php while($row = $categories->fetch_assoc()): ?>
            <li><?php echo $row['category_name']; ?> <small style="color:#94a3b8;">ID: <?php echo $row['category_id']; ?></small></li>
        <?php endwhile; ?>
    </ul>
    <br>
    <a href="add_product.php" style="color: #0284c7; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Add Product</a>
</div>
</body>
</html>