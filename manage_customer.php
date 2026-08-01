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
    die("Database Connection Failed : " . $conn->connect_error);
}

// සියලුම පාරිභෝගිකයන්ගේ දත්ත ලබා ගැනීම
$query = "SELECT * FROM customer ORDER BY customer_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Light & Dark Theme CSS Variables */
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-hover: #f8fafc;
            --text-title: #1e293b;
            --text-main: #334155;
            --text-muted: #64748b;
            --text-subtle: #94a3b8;
            --border-color: #f1f5f9;
            --border-outer: #edf2f7;
            --th-bg: #f8fafc;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.02);

            /* Alert / Toast Messages */
            --msg-deleted-bg: #fef2f2;
            --msg-deleted-text: #991b1b;
            --msg-updated-bg: #e0f2fe;
            --msg-updated-text: #0369a1;
        }

        body.dark-theme {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --text-title: #f8fafc;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --text-subtle: #64748b;
            --border-color: #334155;
            --border-outer: #334155;
            --th-bg: #0f172a;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.2);

            /* Dark theme alerts */
            --msg-deleted-bg: rgba(239, 68, 68, 0.15);
            --msg-deleted-text: #f87171;
            --msg-updated-bg: rgba(14, 165, 233, 0.15);
            --msg-updated-text: #38bdf8;
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            transition: background-color 0.25s, color 0.25s, border-color 0.25s;
        }

        body { 
            background: var(--bg-body); 
            color: var(--text-main);
            display: flex; 
            min-height: 100vh; 
        }

        .main-content { 
            margin-left: 280px; 
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
            color: var(--text-title); 
        }

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

        .search-container {
            background: var(--bg-card); 
            padding: 15px 25px; 
            border-radius: 14px; 
            margin-bottom: 25px;
            box-shadow: var(--shadow); 
            border: 1px solid var(--border-outer);
            display: flex; 
            align-items: center; 
            gap: 15px;
        }

        .search-container i { 
            color: var(--text-subtle); 
            font-size: 18px; 
        }

        .search-container input { 
            width: 100%; 
            border: none; 
            outline: none; 
            font-size: 15px; 
            background: transparent;
            color: var(--text-title); 
        }

        .search-container input::placeholder {
            color: var(--text-subtle);
        }

        .table-card { 
            background: var(--bg-card); 
            border-radius: 16px; 
            box-shadow: var(--shadow); 
            border: 1px solid var(--border-color); 
            overflow: hidden; 
            padding: 10px 0; 
        }

        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
        }

        th { 
            background: var(--th-bg); 
            color: var(--text-muted); 
            padding: 18px 24px; 
            font-weight: 600; 
            font-size: 13px; 
            text-transform: uppercase; 
            border-bottom: 2px solid var(--border-outer); 
            text-align: left; 
        }

        td { 
            padding: 18px 24px; 
            color: var(--text-main); 
            font-size: 14px; 
            border-bottom: 1px solid var(--border-color); 
            text-align: left; 
        }

        tr:hover td { 
            background-color: var(--bg-hover); 
        }

        .td-name {
            color: var(--text-title);
        }

        .td-phone {
            color: #10b981;
            font-weight: 600;
        }

        .td-address {
            color: var(--text-muted);
            font-size: 13px;
        }

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
            background: rgba(14, 165, 233, 0.15); 
            color: #0ea5e9; 
        }

        .btn-edit:hover { 
            background: #0ea5e9; 
            color: white; 
        }

        .btn-delete { 
            background: rgba(239, 68, 68, 0.15); 
            color: #ef4444; 
        }

        .btn-delete:hover { 
            background: #ef4444; 
            color: white; 
        }

        .toast-msg { 
            padding: 12px 20px; 
            border-radius: 10px; 
            font-weight: 500; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        .msg-deleted { 
            background: var(--msg-deleted-bg); 
            color: var(--msg-deleted-text); 
            border-left: 5px solid #ef4444; 
        }

        .msg-updated { 
            background: var(--msg-updated-bg); 
            color: var(--msg-updated-text); 
            border-left: 5px solid #0ea5e9; 
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Customer Database</h1>
            <a href="add_customer.php" class="btn-add">
                <i class="fa-solid fa-user-plus"></i> Add New Customer
            </a>
        </div>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="toast-msg msg-deleted">
                <i class="fa-solid fa-circle-check"></i> Customer account successfully removed from system.
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div class="toast-msg msg-updated">
                <i class="fa-solid fa-circle-check"></i> Customer profile changes successfully saved.
            </div>
        <?php endif; ?>

        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="custSearch" onkeyup="filterCustomers()" placeholder="Search customers by Name, Mobile Number or Location...">
        </div>

        <div class="table-card">
            <table id="customerTable">
                <thead>
                    <tr>
                        <th style="width: 80px;">C-ID</th>
                        <th>Customer Name</th>
                        <th>Mobile / Phone</th>
                        <th>Email Address</th>
                        <th>Registered Address</th>
                        <th style="width: 110px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($row['customer_id']); ?></strong></td>
                            <td class="td-name"><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></td>
                            <td><span class="td-phone"><?php echo htmlspecialchars($row['phone']); ?></span></td>
                            <td><?php echo !empty($row['email']) ? htmlspecialchars($row['email']) : '<span style="color:var(--text-subtle)">No Email</span>'; ?></td>
                            <td><span class="td-address"><?php echo htmlspecialchars($row['address']); ?></span></td>
                            <td>
                                <div class="actions-btn-group">
                                    <a href="edit_customer.php?id=<?php echo $row['customer_id']; ?>" class="btn-action btn-edit" title="Modify Registry">
                                        <i class="fa-solid fa-user-pen"></i>
                                    </a>
                                    <a href="delete_customer.php?id=<?php echo $row['customer_id']; ?>" class="btn-action btn-delete" title="Purge Profile" onclick="return confirm('Are you sure you want to delete this customer?');">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-subtle); padding: 50px;">
                                <i class="fa-solid fa-users-slash" style="font-size: 40px; margin-bottom: 10px; display:block;"></i>
                                No customers registered inside database yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Search & Theme Switcher Sync Script -->
    <script>
        function filterCustomers() {
            let input = document.getElementById("custSearch");
            let filter = input.value.toUpperCase();
            let table = document.getElementById("customerTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let rowContainsQuery = false;
                let tds = tr[i].getElementsByTagName("td");
                
                let nameCol = tds[1];
                let phoneCol = tds[2];
                let addrCol = tds[4];
                
                if (nameCol || phoneCol || addrCol) {
                    let nameText = nameCol.textContent || nameCol.innerText;
                    let phoneText = phoneCol.textContent || phoneCol.innerText;
                    let addrText = addrCol.textContent || addrCol.innerText;
                    
                    if (nameText.toUpperCase().indexOf(filter) > -1 || 
                        phoneText.toUpperCase().indexOf(filter) > -1 ||
                        addrText.toUpperCase().indexOf(filter) > -1) {
                        rowContainsQuery = true;
                    }
                }
                tr[i].style.display = rowContainsQuery ? "" : "none";
            }
        }

        // Light & Dark Mode Persistence Logic
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
            }

            // Theme toggle event handler for Sidebar integration
            const themeToggle = document.querySelector('.theme-toggle input, #themeToggle, input[type="checkbox"]');
            if (themeToggle) {
                if (document.body.classList.contains('dark-theme')) {
                    themeToggle.checked = true;
                }
                themeToggle.addEventListener('change', () => {
                    if (themeToggle.checked) {
                        document.body.classList.add('dark-theme');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.body.classList.remove('dark-theme');
                        localStorage.setItem('theme', 'light');
                    }
                });
            }
        });
    </script>
</body>
</html>