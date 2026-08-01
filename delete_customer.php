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

if(!isset($_GET['id'])){
    header("Location: manage_customer.php");
    exit();
}

$delete_id = intval($_GET['id']);

// පාරිභෝගිකයාගේ නම ලබා ගැනීම
$stmt = $conn->prepare("SELECT customer_name FROM customer WHERE customer_id = ?");
$stmt->bind_param("i", $delete_id);
$stmt->execute();
$result = $stmt->get_result();
$customer_name = "";

if($result->num_rows === 1){
    $row = $result->fetch_assoc();
    $customer_name = $row['customer_name'];
} else {
    header("Location: manage_customer.php");
    exit();
}
$stmt->close();

// YES හෝ NO ක්‍රියාවලිය
if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['confirm_yes'])) {
        // DATABASE එකෙන් ස්ථිරවම මැකීම
        $del_stmt = $conn->prepare("DELETE FROM customer WHERE customer_id = ?");
        $del_stmt->bind_param("i", $delete_id);
        if($del_stmt->execute()){
            header("Location: manage_customer.php?msg=deleted");
            exit();
        }
        $del_stmt->close();
    } else {
        // NO කිව්වොත් සාමාන්‍ය පරිදි ආපසු යාම
        header("Location: manage_customer.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .confirm-card { background: white; width: 100%; max-width: 450px; padding: 40px 30px; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; text-align: center; }
        .warn-icon { width: 70px; height: 70px; background: #fef2f2; color: #ef4444; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 32px; margin: 0 auto 20px auto; }
        .confirm-card h2 { font-size: 22px; color: #1e293b; font-weight: 700; margin-bottom: 10px; }
        .confirm-card p { color: #64748b; font-size: 15px; line-height: 1.5; margin-bottom: 30px; }
        .btn-group { display: flex; gap: 15px; justify-content: center; }
        .btn { padding: 12px 30px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; border: none; width: 45%; transition: 0.2s; }
        .btn-yes { background: #ef4444; color: white; }
        .btn-yes:hover { background: #dc2626; }
        .btn-no { background: #f1f5f9; color: #475569; }
        .btn-no:hover { background: #e2e8f0; }
    </style>
</head>
<body>

    <div class="confirm-card">
        <div class="warn-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h2>Are you sure?</h2>
        <p>Do you really want to permanently delete the profile of <br><strong>"<?php echo htmlspecialchars($customer_name); ?>"</strong>?<br> This action cannot be undone.</p>
        
        <form action="" method="POST">
            <div class="btn-group">
                <button type="submit" name="confirm_no" class="btn btn-no">No, Keep It</button>
                <button type="submit" name="confirm_yes" class="btn btn-yes">Yes, Delete</button>
            </div>
        </form>
    </div>

</body>
</html>