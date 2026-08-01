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

// භාණ්ඩ ඉවත් කිරීමේ කේතය
if(isset($_GET['delete_id'])){
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $del_id);
    if($stmt->execute()){
        header("Location: manage_products.php?msg=deleted");
        exit();
    } else {
        header("Location: manage_products.php?msg=error");
        exit();
    }
}

$products = $conn->query("SELECT * FROM inventory ORDER BY item_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cement Product Gallery - FixIt Crafts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }

        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 40px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 26px; font-weight: 700; color: #1e293b; }

        .btn-add { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 12px 22px; border-radius: 12px; border: none; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2); transition: 0.3s; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(245, 158, 11, 0.35); }

        .search-container { background: white; padding: 15px 25px; border-radius: 14px; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); border: 1px solid #edf2f7; display: flex; align-items: center; gap: 15px; }
        .search-container i { color: #94a3b8; font-size: 18px; }
        .search-container input { width: 100%; border: none; outline: none; font-size: 15px; color: #1e293b; }

        /* Modern Grid Layout */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 25px; }

        /* Premium Card UI Design */
        .product-card { background: white; border-radius: 20px; border: 1px solid #edf2f7; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.01); display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.05); border-color: #f59e0b; }

        .card-image-box { width: 100%; height: 200px; background: #f8fafc; position: relative; overflow: hidden; }
        .card-image-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .product-card:hover .card-image-box img { transform: scale(1.06); }

        /* Badges */
        .stock-badge { position: absolute; top: 15px; left: 15px; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .instock { background: #e2f8e9; color: #157337; }
        .lowstock { background: #fff3cd; color: #856404; }
        .soldout { background: #fde8e8; color: #c81e1e; }

        .card-details { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
        .product-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 6px; line-height: 1.4; }
        .product-desc { font-size: 13px; color: #64748b; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.5; }
        
        .price-stock-row { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; border-top: 1px dashed #f1f5f9; }
        .price-tag { font-size: 17px; font-weight: 800; color: #0f172a; }
        .qty-indicator { font-size: 12px; color: #475569; font-weight: 600; }

        /* Card Floating Controls */
        .card-actions { position: absolute; top: 15px; right: 15px; display: flex; flex-direction: column; gap: 8px; opacity: 0; transition: 0.3s ease; }
        .product-card:hover .card-actions { opacity: 1; }
        
        .action-circle { width: 34px; height: 34px; border-radius: 50%; background: white; display: flex; justify-content: center; align-items: center; color: #475569; text-decoration: none; font-size: 13px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: 0.2s; border: none; cursor: pointer; }
        .action-circle.edit:hover { background: #0ea5e9; color: white; }
        .action-circle.delete:hover { background: #ef4444; color: white; }

        /* Alerts & Toast Messages */
        .toast-msg { background: #e2f8e9; color: #157337; padding: 12px 20px; border-radius: 10px; font-weight: 500; margin-bottom: 25px; border-left: 5px solid #22c55e; display: flex; align-items: center; gap: 10px; }
        .toast-msg.error-msg { background: #fde8e8; color: #c81e1e; border-left-color: #ef4444; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Handmade Showroom</h1>
            <a href="add_products.php" class="btn-add">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Add New Craft
            </a>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'deleted'): ?>
                <div class="toast-msg">
                    <i class="fa-solid fa-circle-check"></i> Item was successfully removed from the gallery.
                </div>
            <?php elseif($_GET['msg'] == 'updated'): ?>
                <div class="toast-msg">
                    <i class="fa-solid fa-circle-check"></i> Product details were successfully updated!
                </div>
            <?php elseif($_GET['msg'] == 'error'): ?>
                <div class="toast-msg error-msg">
                    <i class="fa-solid fa-circle-exclamation"></i> Oops! Something went wrong. Action could not be completed.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="prodSearch" onkeyup="searchProducts()" placeholder="Type to search handmade items instantly...">
        </div>

        <div class="products-grid" id="productShowcase">
            <?php if($products && $products->num_rows > 0): ?>
                <?php while($p = $products->fetch_assoc()): 
                    // Stock එක අනුව Status එක හැදීම
                    $qty = intval($p['quantity']);
                    if($qty == 0) {
                        $badge = '<span class="stock-badge soldout">⛔ Sold Out</span>';
                    } elseif($qty < 5) {
                        $badge = '<span class="stock-badge lowstock">⚠️ Low ('.$qty.')</span>';
                    } else {
                        $badge = '<span class="stock-badge instock">✅ Available ('.$qty.')</span>';
                    }

                    // Image එකක් නැත්නම් default එකක් පෙන්වීමට
                    $img_src = (!empty($p['image']) && file_exists("uploads/".$p['image'])) ? "uploads/".$p['image'] : "https://images.unsplash.com/photo-1513519245088-0e12902e5a38?q=80&w=400&auto=format&fit=crop";
                ?>
                
                <div class="product-card">
                    <div class="card-image-box">
                        <img src="<?php echo $img_src; ?>" alt="Craft Image">
                        <?php echo $badge; ?>
                    </div>

                    <div class="card-actions">
                        <a href="edit_products.php?id=<?php echo $p['item_id']; ?>" class="action-circle edit" title="Edit Item"><i class="fa-solid fa-pen-to-square"></i></a>
                        <a href="manage_products.php?delete_id=<?php echo $p['item_id']; ?>" class="action-circle delete" title="Delete Item" onclick="return confirm('Do you want to delete this unique craft permanently?');"><i class="fa-solid fa-trash-can"></i></a>
                    </div>

                    <div class="card-details">
                        <h3 class="product-title"><?php echo $p['item_name']; ?></h3>
                        <p class="product-desc"><?php echo !empty($p['description']) ? $p['description'] : 'No customized crafting story shared for this creation yet.'; ?></p>
                        
                        <div class="price-stock-row">
                            <span class="price-tag">Rs. <?php echo number_format($p['price'], 2); ?></span>
                            <span class="qty-indicator">Stock: <b><?php echo $p['quantity']; ?> pcs</b></span>
                        </div>
                    </div>
                </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 80px 0;">
                    <i class="fa-solid fa-palette" style="font-size: 50px; margin-bottom: 15px; display:block; color:#cbd5e1;"></i>
                    No custom handmade items updated inside the system.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        if (typeof searchProducts !== 'function') {
            function searchProducts() {
                let query = document.getElementById("prodSearch").value.toUpperCase();
                let grid = document.getElementById("productShowcase");
                let cards = grid.getElementsByClassName("product-card");

                for (let i = 0; i < cards.length; i++) {
                    let title = cards[i].getElementsByClassName("product-title")[0].textContent || cards[i].getElementsByClassName("product-title")[0].innerText;
                    let desc = cards[i].getElementsByClassName("product-desc")[0].textContent || cards[i].getElementsByClassName("product-desc")[0].innerText;
                    
                    if (title.toUpperCase().indexOf(query) > -1 || desc.toUpperCase().indexOf(query) > -1) {
                        cards[i].style.style.display = "";
                    } else {
                        cards[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>