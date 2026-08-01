<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// පරිශීලකයා ලොග් වී නොමැති නම් login.php වෙත යොමු කරයි
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "hardware");

if ($conn->connect_error) {
    die("Database Connection Failed : " . $conn->connect_error);
}

$supplier_id = 0;
$supplier_name = $contact_person = $phone = $email = $address = "";
$commission_rate = 18.00;
$msg = "";

// 1. දැනට පවතින දත්ත ලබා ගැනීම (Fetch Current Data via GET)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $supplier_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM supplier WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $supplier_name  = $row['supplier_name'] ?? '';
        $contact_person = $row['contact_person'] ?? '';
        $phone          = $row['phone'] ?? '';
        $email          = $row['email'] ?? '';
        $address        = $row['address'] ?? '';
        $commission_rate = isset($row['commission_rate']) ? floatval($row['commission_rate']) : 18.00;
    } else {
        header("Location: manage_suppliers.php");
        exit();
    }
    $stmt->close();
} elseif ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // ID එකක් නොමැතිව direct පිවිසීමට උත්සාහ කළහොත්
    header("Location: manage_suppliers.php");
    exit();
}

// 2. දත්ත යාවත්කාලීන කිරීම (Update Data via POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_supplier'])) {
    $supplier_id    = intval($_POST['supplier_id'] ?? 0);
    $supplier_name  = trim($_POST['supplier_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    
    $raw_rate        = $_POST['commission_rate'] ?? 18.00;
    $commission_rate = is_numeric($raw_rate) ? floatval($raw_rate) : 18.00;

    if (!empty($supplier_name) && !empty($phone) && $supplier_id > 0) {
        
        // Prepared Statement මගින් UPDATE කිරීම (Commission Rate එක ඇතුළත්ව)
        $update_stmt = $conn->prepare("UPDATE supplier SET supplier_name = ?, contact_person = ?, phone = ?, email = ?, address = ?, commission_rate = ? WHERE supplier_id = ?");
        
        if ($update_stmt) {
            $update_stmt->bind_param("sssssdi", $supplier_name, $contact_person, $phone, $email, $address, $commission_rate, $supplier_id);
            
            if ($update_stmt->execute()) {
                header("Location: manage_suppliers.php?msg=updated");
                exit();
            } else {
                $msg = "<div class='toast-msg error'><i class='fa-solid fa-triangle-exclamation'></i> Error updating record: " . htmlspecialchars($conn->error) . "</div>";
            }
            $update_stmt->close();
        } else {
            $msg = "<div class='toast-msg error'><i class='fa-solid fa-triangle-exclamation'></i> Database Error: Statement preparation failed.</div>";
        }
    } else {
        $msg = "<div class='toast-msg error'><i class='fa-solid fa-triangle-exclamation'></i> Company Name and Phone Number are required fields!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            width: 100%;
            max-width: 750px;
            margin-bottom: 30px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header h1 { font-size: 26px; font-weight: 700; color: #1e293b; }

        .btn-back {
            padding: 10px 18px; background: white; border: 1px solid #e2e8f0; border-radius: 10px;
            color: #475569; text-decoration: none; font-size: 14px; font-weight: 600;
            display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;
        }
        .btn-back:hover { background: #f1f5f9; color: #1e293b; transform: translateX(-2px); }
        
        .form-card { 
            background: white; 
            width: 100%;
            max-width: 750px; 
            padding: 40px; 
            border-radius: 20px; 
            border: 1px solid #edf2f7; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02); 
        }

        .form-card h3 {
            font-size: 18px; color: #0f172a; font-weight: 600; margin-bottom: 25px;
            padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;
        }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media(max-width: 640px) { 
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
            .form-grid { grid-template-columns: 1fr; } 
        }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        @media(max-width: 640px) { .form-group.full-width { grid-column: span 1; } }

        .form-group label { font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-group input, .form-group textarea { 
            width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px; 
            outline: none; font-size: 14px; color: #1e293b; background: #f8fafc; transition: all 0.3s ease; 
        }
        .form-group input:focus, .form-group textarea:focus { 
            border-color: #06b6d4; background: white; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1); 
        }

        /* Commission Rate Field Highlight Style */
        .rate-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        .rate-input-group input {
            padding-right: 45px;
            font-weight: 700;
            color: #0284c7;
        }
        .rate-badge {
            position: absolute;
            right: 15px;
            font-weight: 700;
            color: #64748b;
            font-size: 15px;
        }
        
        .btn-group { display: flex; gap: 15px; margin-top: 35px; justify-content: flex-end; }
        
        .btn-save { 
            padding: 14px 30px; background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%); 
            color: white; font-size: 15px; font-weight: 600; border-radius: 12px; border: none; 
            cursor: pointer; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; 
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4); }
        
        .btn-cancel { 
            padding: 14px 25px; background: #f1f5f9; color: #64748b; font-size: 15px; font-weight: 600; 
            border-radius: 12px; border: none; text-decoration: none; text-align: center; display: flex; align-items: center; transition: all 0.3s ease; 
        }
        .btn-cancel:hover { background: #e2e8f0; color: #334155; }
        
        .toast-msg { 
            width: 100%; max-width: 750px; padding: 14px 20px; border-radius: 12px; font-weight: 500; 
            font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; 
        }
        .toast-msg.error { background: #fde8e8; color: #c81e1e; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Edit Supplier Details</h1>
            <a href="manage_suppliers.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back to Hub
            </a>
        </div>

        <?php echo $msg; ?>

        <div class="form-card">
            <h3><i class="fa-solid fa-pen-to-square" style="color: #06b6d4; margin-right: 8px;"></i> Update Supplier Information</h3>
            
            <form action="edit_supplier.php" method="POST">
                <input type="hidden" name="supplier_id" value="<?php echo htmlspecialchars($supplier_id); ?>">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Company / Supplier Name *</label>
                        <input type="text" name="supplier_name" value="<?php echo htmlspecialchars($supplier_name); ?>" required placeholder="e.g. Nippon Paint Lanka (Pvt) Ltd">
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Person Name</label>
                        <input type="text" name="contact_person" value="<?php echo htmlspecialchars($contact_person); ?>" placeholder="e.g. Mr. Asela Perera">
                    </div>

                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required placeholder="e.g. 0771234567">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="e.g. info@nippon.lk">
                    </div>

                    <!-- Commission / Agreement Rate Field -->
                    <div class="form-group">
                        <label>Agreement Rate (%)</label>
                        <div class="rate-input-group">
                            <input type="number" step="0.01" min="0" max="100" name="commission_rate" value="<?php echo htmlspecialchars(number_format($commission_rate, 2, '.', '')); ?>" required>
                            <span class="rate-badge">%</span>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Postal / Business Address</label>
                        <textarea name="address" rows="3" placeholder="e.g. No. 45, Kandy Road, Colombo"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="update_supplier" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Update Supplier
                    </button>
                    <a href="manage_suppliers.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>