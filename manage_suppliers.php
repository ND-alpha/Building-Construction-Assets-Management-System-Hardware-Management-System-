<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. පරිශීලකයා ලොග් වී ඇත්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['user_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Admin ද නැද්ද යන්න පරීක්ෂා කිරීම
$user_role = $_SESSION['role'] ?? $_SESSION['user_type'] ?? '';
$is_admin = (strtolower($user_role) === 'admin');

// Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "hardware");

if ($conn->connect_error) {
    die("Database Connection Failed : " . $conn->connect_error);
}

$msg = "";

// 3. Supplier කෙනෙක් Delete කිරීමේ කොටස (Admin ට විතරයි Access දෙන්නේ)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    
    // Admin නොවේ නම් Delete කිරීමට ඉඩ නොදී පහත සඳහන් Alert එක පෙන්වයි
    if (!$is_admin) {
        header("Location: manage_suppliers.php?msg=unauthorized");
        exit();
    }

    $supplier_id = intval($_GET['id']);
    
    // Delete Prepared Statement
    $del_stmt = $conn->prepare("DELETE FROM supplier WHERE supplier_id = ?");
    if ($del_stmt) {
        $del_stmt->bind_param("i", $supplier_id);
        if ($del_stmt->execute()) {
            header("Location: manage_suppliers.php?msg=deleted");
            exit();
        } else {
            $msg = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Cannot delete supplier. They may be linked to existing inventory/purchases.</div>";
        }
        $del_stmt->close();
    }
}

// System messages (Alerts)
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') {
        $msg = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Supplier details updated successfully!</div>";
    } elseif ($_GET['msg'] === 'deleted') {
        $msg = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Supplier deleted successfully!</div>";
    } elseif ($_GET['msg'] === 'unauthorized') {
        $msg = "<div class='alert error'><i class='fa-solid fa-shield-halved'></i> Access Denied! Only Admins can perform this action.</div>";
    }
}

// 4. Suppliers ලැයිස්තුව ලබා ගැනීම
$query = "SELECT * FROM supplier ORDER BY supplier_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }

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

        .page-header h1 { font-size: 26px; font-weight: 700; color: #1e293b; }

        .btn-add {
            padding: 12px 22px;
            background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%);
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
            transition: all 0.3s ease;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4); }

        .table-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #edf2f7;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 16px 20px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
        tr:hover { background: #f8fafc; }

        .rate-badge {
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            display: inline-block;
        }

        .action-btns { display: flex; gap: 10px; }
        .btn-edit {
            padding: 8px 12px; background: #e0f2fe; color: #0284c7; border-radius: 8px;
            text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s;
        }
        .btn-edit:hover { background: #0284c7; color: white; }

        .btn-delete {
            padding: 8px 12px; background: #fee2e2; color: #dc2626; border-radius: 8px;
            text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s;
        }
        .btn-delete:hover { background: #dc2626; color: white; }

        .alert {
            padding: 14px 20px; border-radius: 12px; font-weight: 500;
            font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;
        }
        .alert.success { background: #e2f8e9; color: #157337; border-left: 5px solid #22c55e; }
        .alert.error { background: #fde8e8; color: #c81e1e; border-left: 5px solid #ef4444; }

        @media(max-width: 768px) {
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Suppliers Directory</h1>
                <p style="color: #64748b; font-size: 14px; margin-top: 4px;">View and manage registered hardware suppliers</p>
            </div>
            
            <!-- Admin ට විතරක් 'Add New Supplier' බටන් එක පෙන්වයි -->
            <?php if ($is_admin): ?>
                <a href="add_supplier.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i> Add New Supplier
                </a>
            <?php endif; ?>
        </div>

        <?php echo $msg; ?>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Supplier / Company</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Agreement Rate</th>
                        <!-- Admin නම් විතරක් Actions Column එක පෙන්වයි -->
                        <?php if ($is_admin): ?>
                            <th style="text-align: center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['supplier_name']); ?></strong><br>
                                    <small style="color: #94a3b8;"><?php echo htmlspecialchars($row['address']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['contact_person'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                                <td>
                                    <span class="rate-badge">
                                        <?php echo number_format($row['commission_rate'] ?? 18.00, 2); ?>%
                                    </span>
                                </td>
                                
                                <!-- Admin ට විතරක් Edit & Delete බටන් පෙන්වයි -->
                                <?php if ($is_admin): ?>
                                    <td style="text-align: center;">
                                        <div class="action-btns" style="justify-content: center;">
                                            <a href="edit_supplier.php?id=<?php echo $row['supplier_id']; ?>" class="btn-edit" title="Edit Supplier">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>
                                            <a href="manage_suppliers.php?action=delete&id=<?php echo $row['supplier_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this supplier?');" title="Delete Supplier">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? '6' : '5'; ?>" style="text-align: center; color: #94a3b8; padding: 30px;">
                                No suppliers registered yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>