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
    die("Database Error : ".$conn->connect_error);
}

// =======================
// Summary Data
// =======================
$total_employee = $conn->query("SELECT COUNT(*) AS total FROM employee")->fetch_assoc()['total'];
$total_items = $conn->query("SELECT COUNT(*) AS total FROM inventory")->fetch_assoc()['total'];
$total_supplier = $conn->query("SELECT COUNT(*) AS total FROM supplier")->fetch_assoc()['total'];
$total_customer = $conn->query("SELECT COUNT(*) AS total FROM customer")->fetch_assoc()['total'];

$stock_value = $conn->query("SELECT SUM(quantity * price) AS total FROM inventory")->fetch_assoc()['total'];
$stock_value = $stock_value ?? 0;

$low_stock = $conn->query("SELECT COUNT(*) AS total FROM inventory WHERE quantity < 10")->fetch_assoc()['total'];

// =======================
// Category Chart Data
// =======================
$category = [];
$query = "
    SELECT COALESCE(c.category_name, 'Uncategorized') AS category, COUNT(i.item_id) AS total 
    FROM inventory i
    LEFT JOIN categories c ON i.category_id = c.category_id
    GROUP BY c.category_id, c.category_name
";

$result = $conn->query($query);
if($result) {
    while($row = $result->fetch_assoc()){
        $category[] = $row;
    }
}

// =======================
// Employee Role Chart Data
// =======================
$roles = [];
$result = $conn->query("SELECT role, COUNT(*) AS total FROM employee GROUP BY role");
if($result) {
    while($row = $result->fetch_assoc()){
        $roles[] = $row;
    }
}

// =======================
// Recent Items (JOIN Categories Table)
// =======================
$query_items = "
    SELECT i.*, COALESCE(c.category_name, 'Uncategorized') AS category_name 
    FROM inventory i 
    LEFT JOIN categories c ON i.category_id = c.category_id 
    ORDER BY i.item_id DESC LIMIT 5
";
$items = $conn->query($query_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Premium Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* --- DYNAMIC COLOR VARIABLES (LIGHT MODE DEFAULT) --- */
        :root {
            --body-bg: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --sidebar-bg: #090d16;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(241, 245, 249, 0.6);
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.03);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.05);
            --table-th-bg: #f8fafc;
            --table-tr-hover: rgba(248, 250, 252, 0.6);
            --card-h2: #0f172a;
        }

        /* --- DARK MODE OVERRIDES --- */
        [data-theme="dark"] {
            --body-bg: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --glass-bg: rgba(30, 41, 59, 0.45);
            --glass-border: rgba(255, 255, 255, 0.05);
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.2);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.4);
            --table-th-bg: #1e293b;
            --table-tr-hover: rgba(255, 255, 255, 0.02);
            --card-h2: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background: var(--body-bg);
            display: flex;
            min-height: 100vh;
            color: var(--text-main);
        }

        /* --- MAIN CONTENT WINDOW --- */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 40px 50px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .top-bar h1 { font-size: 32px; font-weight: 800; letter-spacing: -0.75px; }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* --- THEME TOGGLE BUTTON --- */
        .theme-toggle-btn {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s;
        }
        .theme-toggle-btn:hover { transform: scale(1.05); }

        .date-badge { 
            background: var(--glass-bg); 
            backdrop-filter: blur(10px);
            padding: 12px 20px; 
            border-radius: 30px; 
            box-shadow: var(--shadow-sm); 
            font-size: 14px; 
            font-weight: 600; 
            color: var(--text-muted); 
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .date-badge i { color: #3b82f6; }

        /* --- FIXED CARDS GRID --- */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            align-items: stretch;
        }
        
        .card-link {
            text-decoration: none;
            color: inherit;
            display: flex;
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            width: 100%;
            min-height: 110px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .card-details {
            flex: 1;
            min-width: 0;
        }
        .card-details h2 { 
            font-size: 24px; 
            font-weight: 800; 
            color: var(--card-h2); 
            margin-bottom: 4px; 
            letter-spacing: -0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-details p { 
            font-size: 13px; 
            color: var(--text-muted); 
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .card-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        
        .c-emp { background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .c-inv { background: linear-gradient(135deg, rgba(22, 163, 74, 0.15) 0%, rgba(22, 163, 74, 0.05) 100%); color: #16a34a; border: 1px solid rgba(22, 163, 74, 0.2); }
        .c-sup { background: linear-gradient(135deg, rgba(147, 51, 234, 0.15) 0%, rgba(147, 51, 234, 0.05) 100%); color: #9333ea; border: 1px solid rgba(147, 51, 234, 0.2); }
        .c-cus { background: linear-gradient(135deg, rgba(217, 119, 6, 0.15) 0%, rgba(217, 119, 6, 0.05) 100%); color: #d97706; border: 1px solid rgba(217, 119, 6, 0.2); }
        .c-val { background: linear-gradient(135deg, rgba(8, 145, 178, 0.15) 0%, rgba(8, 145, 178, 0.05) 100%); color: #0891b2; border: 1px solid rgba(8, 145, 178, 0.2); }
        .c-low { background: linear-gradient(135deg, rgba(220, 38, 38, 0.15) 0%, rgba(220, 38, 38, 0.05) 100%); color: #dc2626; border: 1px solid rgba(220, 38, 38, 0.2); }

        .charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }
        
        .box {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 35px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--glass-border);
        }
        .box h3 { font-size: 18px; font-weight: 700; margin-bottom: 25px; letter-spacing: -0.3px; }

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 35px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--glass-border);
            margin-top: 40px;
            overflow-x: auto;
        }
        .table-container h3 { font-size: 18px; font-weight: 700; margin-bottom: 25px; letter-spacing: -0.3px; }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        th {
            background: var(--table-th-bg);
            color: var(--text-muted);
            padding: 18px 20px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            text-align: left;
            border-bottom: 2px solid var(--glass-border);
        }
        th:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        th:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        
        td {
            padding: 18px 20px;
            color: var(--text-main);
            font-size: 14px;
            border-bottom: 1px solid var(--glass-border);
            text-align: left;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: var(--table-tr-hover); }
        
        .badge-qty { padding: 6px 14px; border-radius: 30px; font-weight: 700; font-size: 12px; display: inline-block; }
        .badge-qty.normal { background: rgba(22, 163, 74, 0.15); color: #16a34a; }
        .badge-qty.danger { background: rgba(220, 38, 38, 0.15); color: #dc2626; }
        
        .price-text { color: var(--text-main); font-weight: 700; }
        .item-id-text { color: #3b82f6; font-weight: 600; }

        @media(max-width: 1200px) {
            .main-content { margin-left: 0; width: 100%; padding: 30px 20px; }
            .charts { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <div class="top-bar">
            <h1>Dashboard Overview</h1>
            <div class="top-bar-right">
                <button class="theme-toggle-btn" id="themeToggle" title="Toggle Light/Dark Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <div class="date-badge">
                    <i class="fa-regular fa-calendar-days"></i> <?php echo date('D, M d, Y'); ?>
                </div>
            </div>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-details">
                    <h2><?php echo $total_employee;?></h2>
                    <p>Total Employees</p>
                </div>
                <div class="card-icon c-emp"><i class="fa-solid fa-users"></i></div>
            </div>

            <a href="manage_inventory.php" class="card-link">
                <div class="card">
                    <div class="card-details">
                        <h2><?php echo $total_items;?></h2>
                        <p>Inventory Items</p>
                    </div>
                    <div class="card-icon c-inv"><i class="fa-solid fa-box"></i></div>
                </div>
            </a>

            <div class="card">
                <div class="card-details">
                    <h2><?php echo $total_supplier;?></h2>
                    <p>Suppliers Registered</p>
                </div>
                <div class="card-icon c-sup"><i class="fa-solid fa-truck"></i></div>
            </div>

            <div class="card">
                <div class="card-details">
                    <h2><?php echo $total_customer;?></h2>
                    <p>Active Customers</p>
                </div>
                <div class="card-icon c-cus"><i class="fa-solid fa-user"></i></div>
            </div>

            <div class="card">
                <div class="card-details">
                    <h2>Rs. <?php echo number_format($stock_value, 2);?></h2>
                    <p>Total Stock Value</p>
                </div>
                <div class="card-icon c-val"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>

            <a href="manage_inventory.php?filter=low" class="card-link" title="Click to view all low stock items">
                <div class="card">
                    <div class="card-details">
                        <h2><?php echo $low_stock;?></h2>
                        <p>Low Stock Warnings</p>
                    </div>
                    <div class="card-icon c-low"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
            </a>
        </div>

        <div class="charts">
            <div class="box">
                <h3>Stock Levels by Categories</h3>
                <div style="position: relative; height:320px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <div class="box">
                <h3>Employee Composition</h3>
                <div style="position: relative; height:320px; display:flex; justify-content:center;">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h3>Recently Added Stock</h3>
            <table>
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Stock Level</th>
                        <th>Unit Measure</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($items && $items->num_rows > 0) {
                        while($row = $items->fetch_assoc()){ 
                            $statusClass = ($row['quantity'] < 10) ? 'danger' : 'normal';
                            $displayCategory = (!empty($row['category_name']) && $row['category_name'] !== 'Uncategorized') 
                                                ? $row['category_name'] 
                                                : '<i style="color:#94a3b8;">Uncategorized</i>';
                        ?>
                        <tr>
                            <td><span class="item-id-text">#<?php echo $row['item_id'];?></span></td>
                            <td><strong><?php echo $row['item_name'];?></strong></td>
                            <td><?php echo $displayCategory; ?></td>
                            <td><span class="badge-qty <?php echo $statusClass; ?>"><?php echo $row['quantity'];?></span></td>
                            <td><?php echo $row['unit'];?></td>
                            <td><span class="price-text">Rs. <?php echo number_format($row['price'], 2);?></span></td>
                        </tr>
                        <?php 
                        }
                    } else { ?>
                        <tr><td colspan="6" style="text-align:center; color:#94a3b8;">No items found</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        const themeToggleBtn = document.getElementById('themeToggle');
        const themeIcon = themeToggleBtn.querySelector('i');
        
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateToggleIcon(currentTheme);

        themeToggleBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            let newTheme = (theme === 'dark') ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateToggleIcon(newTheme);
        });

        function updateToggleIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'fa-solid fa-sun';
                themeToggleBtn.style.color = '#f59e0b';
            } else {
                themeIcon.className = 'fa-solid fa-moon';
                themeToggleBtn.style.color = '';
            }
        }

        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        
        const barGradient = ctxCategory.createLinearGradient(0, 0, 0, 300);
        barGradient.addColorStop(0, 'rgba(6, 182, 212, 0.85)');
        barGradient.addColorStop(1, 'rgba(59, 130, 246, 0.2)');

        new Chart(ctxCategory, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($category,'category'));?>,
                datasets: [{
                    label: "Items in Category",
                    data: <?php echo json_encode(array_column($category,'total'));?>,
                    backgroundColor: barGradient,
                    borderColor: '#06b6d4',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { grid: { color: 'rgba(148, 163, 184, 0.1)', borderDash: [5, 5] }, ticks: { color: '#64748b', font: { weight: 500 } } }, 
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { weight: 500 } } } 
                }
            }
        });

        new Chart(document.getElementById('roleChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($roles,'role'));?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($roles,'total'));?>,
                    backgroundColor: ['#3b82f6', '#a855f7', '#06b6d4', '#f59e0b', '#10b981'],
                    borderWidth: 4,
                    borderColor: 'transparent'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 20, color: '#64748b', font: { weight: 600, size: 12 } }
                    } 
                },
                cutout: '75%'
            }
        });
    </script>
</body>
</html>