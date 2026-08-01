<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. පරිශීලකයා ලොග් වී ඇත්දැයි පරීක්ෂා කිරීම (user_id හෝ employee_id භාවිතයෙන්)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Admin ට විතරක් Access දීම (Employee කෙනෙක් නම් Dashboard එකට හරවා යවයි)
$user_role = $_SESSION['role'] ?? $_SESSION['user_type'] ?? '';

if (strtolower($user_role) !== 'admin') {
    // Admin නොවන අයට Access නැති නිසා Dashboard එකට Redirect කරයි
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "hardware");

if ($conn->connect_error) {
    die("Database Connection Failed : " . $conn->connect_error);
}

$message = "";
$message_type = "";

// Form එක Submit වූ පසු ක්‍රියාත්මක වන කොටස
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $supplier_name  = trim($_POST['supplier_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    
    // Commission Rate ලබා ගැනීම (Default 18.00%)
    $raw_rate        = $_POST['commission_rate'] ?? 18.00;
    $commission_rate = is_numeric($raw_rate) ? floatval($raw_rate) : 18.00;

    if (!empty($supplier_name) && !empty($phone)) {
        
        // Prepared Statement එක හරහා Supplier Insert කිරීම
        $stmt = $conn->prepare("INSERT INTO supplier (supplier_name, contact_person, phone, email, address, commission_rate) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sssssd", $supplier_name, $contact_person, $phone, $email, $address, $commission_rate);
            
            if ($stmt->execute()) {
                $message = "New Supplier successfully registered in the system with " . number_format($commission_rate, 2) . "% rate!";
                $message_type = "success";
            } else {
                $message = "Error: This supplier name or contact details already exist in the system.";
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Database Error: Unable to prepare statement.";
            $message_type = "error";
        }
    } else {
        $message = "Please fill all required fields (Supplier Name & Phone Number).";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Supplier - FixIt Hardware</title>
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
            background: white; width: 100%; max-width: 750px; padding: 40px;
            border-radius: 20px; border: 1px solid #edf2f7; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
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

        .btn-container { margin-top: 35px; display: flex; justify-content: flex-end; }
        
        .btn-submit {
            padding: 14px 35px; background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%);
            border: none; color: white; font-size: 15px; font-weight: 600; border-radius: 12px;
            cursor: pointer; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4); }

        .alert {
            width: 100%; max-width: 750px; padding: 14px 20px; border-radius: 12px; font-weight: 500;
            font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert.success { background: #e2f8e9; color: #157337; border-left: 5px solid #22c55e; }
        .alert.error { background: #fde8e8; color: #c81e1e; border-left: 5px solid #ef4444; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Onboard Supplier</h1>
            <a href="manage_suppliers.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back to Hub
            </a>
        </div>

        <?php if(!empty($message)): ?>
            <div class="alert <?php echo htmlspecialchars($message_type); ?>">
                <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3><i class="fa-solid fa-truck" style="color: #06b6d4; margin-right: 8px;"></i> Supplier Profile Details</h3>
            
            <form method="POST" action="add_supplier.php">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Company / Supplier Name *</label>
                        <input type="text" name="supplier_name" required placeholder="e.g. Nippon Paint Lanka (Pvt) Ltd">
                    </div>

                    <div class="form-group">
                        <label>Contact Person Name</label>
                        <input type="text" name="contact_person" placeholder="e.g. Mr. Asela Perera">
                    </div>

                    <div class="form-group">
                        <label>Contact Phone Number *</label>
                        <input type="text" name="phone" required placeholder="e.g. 0771234567">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="e.g. info@nippon.lk">
                    </div>

                    <!-- Supplier Agreement Commission Rate Field -->
                    <div class="form-group">
                        <label>Agreement Rate (%)</label>
                        <div class="rate-input-group">
                            <input type="number" step="0.01" min="0" max="100" name="commission_rate" value="18.00" required placeholder="e.g. 15.00">
                            <span class="rate-badge">%</span>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Postal / Business Address</label>
                        <textarea name="address" rows="3" placeholder="e.g. No. 45, Kandy Road, Colombo"></textarea>
                    </div>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-plus-circle"></i> Save & Register Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>