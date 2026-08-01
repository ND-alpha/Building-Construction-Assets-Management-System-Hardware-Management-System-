<?php

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ලොග් වී නැත්නම් ලොගින් පිටුවට
if(!isset($_SESSION['employee_id'])){
    header("Location: login.php");
    exit();
}

// ඇඩ්මින් කෙනෙක් නොවේ නම් ඩෑෂ්බෝඩ් එකට
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    header("Location: dashboard.php?error=unauthorized_access");
    exit();
}

// Database Connection
$host = "localhost";
$db_user = "root";
$db_pass = ""; // Enter your MySQL password here
$db_name = "hardware";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (!empty($full_name) && !empty($email) && !empty($password)) {
        
        // Check if the email already exists
        $check_stmt = $conn->prepare("SELECT employee_id FROM employee WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "This email address is already registered!";
            $message_type = "error";
        } else {
            // Securely hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert into database using prepared statement
            $stmt = $conn->prepare("INSERT INTO employee (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = "Registration successful! You can now login.";
                $message_type = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $message = "Please fill in all the fields!";
        $message_type = "error";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware System - Register</title>
    <!-- Google Fonts for Modern Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Creative Animated Shapes in Background */
        .background-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float 8s infinite ease-in-out;
        }
        .shape-1 { width: 300px; height: 300px; top: -50px; left: -50px; }
        .shape-2 { width: 200px; height: 200px; bottom: -50px; right: -10px; animation-delay: 2s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        /* Glassmorphic Container */
        .reg-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s ease;
        }

        .reg-container:hover {
            transform: translateY(-5px);
        }

        h2 {
            text-align: center;
            color: #1e3c72;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 28px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #444;
            font-weight: 500;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Beautiful Input Styling */
        input[type="text"], 
        input[type="email"], 
        input[type="password"], 
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5ee;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
            color: #333;
            background-color: #f9fbfd;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2a5298;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.1);
        }

        /* Premium Button */
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            box-shadow: 0 6px 18px rgba(30, 60, 114, 0.4);
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(1px);
        }

        /* Modern Alert Messages */
        .message {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error {
            color: #721c24;
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .success {
            color: #155724;
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }

        /* Footer Link */
        .link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .link a {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .link a:hover {
            color: #2a5298;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- Background Decorative Elements -->
<div class="background-shapes">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
</div>

<div class="reg-container">
    <h2>Create Account</h2>
    <p class="subtitle">Hardware Management System</p>
    
    <?php if(!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required placeholder="e.g. John Doe">
        </div>
        
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="e.g. john@gmail.com">
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        
        <div class="form-group">
            <label>System Role</label>
            <select name="role">
                <option value="Employee">Employee</option>
                <option value="Admin">Admin</option>
            </select>
        </div>
        
        <button type="submit">Sign Up</button>
    </form>
    
    <div class="link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>