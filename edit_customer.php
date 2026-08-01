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

$customer_id = 0;
$name = $phone = $email = $address = "";
$error_msg = "";

// පිටුවට මුලින්ම එන විට පාරිභෝගික දත්ත ලබා ගැනීම
if(isset($_GET['id'])){
    $customer_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1){
        $row = $result->fetch_assoc();
        $name = $row['customer_name'];
        $phone = $row['phone'];
        $email = $row['email'];
        $address = $row['address'];
    } else {
        header("Location: manage_customer.php");
        exit();
    }
    $stmt->close();
}

// "Save Changes" බටන් එක ක්ලික් කළ විට දත්ත Update කිරීම
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_customer'])){
    $customer_id = intval($_POST['customer_id']);
    $name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if(!empty($name) && !empty($phone)){
        // UPDATE QUERY
        $update_stmt = $conn->prepare("UPDATE customer SET customer_name = ?, phone = ?, email = ?, address = ? WHERE customer_id = ?");
        $update_stmt->bind_param("ssssi", $name, $phone, $email, $address, $customer_id);
        
        if($update_stmt->execute()){
            header("Location: manage_customer.php?msg=updated");
            exit();
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
        $update_stmt->close();
    } else {
        $error_msg = "Name and Phone fields cannot be empty!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 40px; display: flex; justify-content: center; align-items: center; }
        .form-card { background: white; width: 100%; max-width: 600px; padding: 35px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .form-header { margin-bottom: 25px; }
        .form-header h2 { font-size: 22px; color: #1e293b; font-weight: 700; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; }
        .form-group input:focus, .form-group textarea:focus { border-color: #0ea5e9; }
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; width: 50%; }
        .btn-save { background: linear-gradient(45deg, #06b6d4, #0284c7); color: white; }
        .btn-cancel { background: #f1f5f9; color: #64748b; }
        .error-alert { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="form-card">
            <div class="form-header">
                <h2>Modify Customer Profile</h2>
            </div>

            <?php if(!empty($error_msg)): ?>
                <div class="error-alert"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="edit_customer.php?id=<?php echo $customer_id; ?>" method="POST">
                <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">

                <div class="form-group">
                    <label>Customer Name *</label>
                    <input type="text" name="customer_name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group">
                    <label>Mobile / Phone Number *</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="form-group">
                    <label>Registered Address</label>
                    <textarea name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                </div>

                <div class="btn-group">
                    <a href="manage_customer.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" name="update_customer" class="btn btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>