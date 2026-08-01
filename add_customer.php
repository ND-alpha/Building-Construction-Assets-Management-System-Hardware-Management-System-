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

$message = "";
$message_type = "";

// Form එක Submit වූ පසු ක්‍රියාත්මක වන කොටස
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if (!empty($customer_name) && !empty($phone)) {
        
        $stmt = $conn->prepare("INSERT INTO customer (customer_name, phone, email, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $customer_name, $phone, $email, $address);
        
        if ($stmt->execute()) {
            $message = "New Customer successfully registered in the system!";
            $message_type = "success";
        } else {
            $message = "Error: Something went wrong. Mobile number might already exist.";
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Please fill all required fields correctly.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }

        .main-content {
            margin-left: 280px; width: calc(100% - 280px); padding: 40px;
            display: flex; flex-direction: column; align-items: center;
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

        .form-group label { font-size: 13px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-group input, .form-group textarea {
            width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px;
            outline: none; font-size: 14px; color: #1e293b; background: #f8fafc; transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #06b6d4; background: white; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1);
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
            <h1>Register Customer</h1>
            <a href="manage_customer.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Customer List
            </a>
        </div>

        <?php if(!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3><i class="fa-solid fa-user-plus" style="color: #06b6d4; margin-right: 8px;"></i> Customer Information</h3>
            
            <form method="POST" action="add_customer.php">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Full Name / Company Name</label>
                        <input type="text" name="customer_name" required placeholder="e.g. Sunil Perera or Gamage Constructions">
                    </div>

                    <div class="form-group">
                        <label>Mobile / Phone Number</label>
                        <input type="text" name="phone" required placeholder="e.g. 071XXXXXXX">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="e.g. sunil@gmail.com">
                    </div>

                    <div class="form-group full-width">
                        <label>Residential / Delivery Address</label>
                        <textarea name="address" rows="3" placeholder="e.g. No. 12, Galle Road, Colombo"></textarea>
                    </div>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Save Customer Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>