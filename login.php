<?php
session_start();

// Database Connection
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "hardware";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 1. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. CSRF Token Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid request token. Please try again.";
    } else {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (!empty($email) && !empty($password)) {
            $stmt = $conn->prepare(
                "SELECT employee_id, full_name, password, role 
                 FROM employee 
                 WHERE email = ? LIMIT 1"
            );

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    
                    // 3. Prevent Session Fixation
                    session_regenerate_id(true);

                    // Set Session Variables
                    $_SESSION['employee_id'] = $user['employee_id'];
                    $_SESSION['user_name']   = $user['full_name'];
                    $_SESSION['role']        = $user['role'];
                    $_SESSION['user_role']   = $user['role'];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "Incorrect Password!";
                }
            } else {
                $error_message = "Email account not found!";
            }
            $stmt->close();
        } else {
            $error_message = "Please complete all fields!";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Management Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            overflow: hidden;
            position: relative;
        }

        /* Ambient Dynamic Background Shapes */
        body::before {
            content: "";
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0,242,254,0.3) 0%, rgba(0,242,254,0) 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            z-index: 0;
            animation: pulse 8s infinite alternate;
        }

        body::after {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(0,255,136,0.2) 0%, rgba(0,255,136,0) 70%);
            border-radius: 50%;
            bottom: -100px;
            right: -100px;
            z-index: 0;
            animation: pulse 8s infinite alternate-reverse;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.2; }
            100% { transform: scale(1.1); opacity: 0.4; }
        }

        /* Glassmorphic Login Box Card */
        .login-box {
            width: 100%;
            max-width: 440px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 45px rgba(0,0,0,0.35);
            color: white;
            z-index: 1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 50px rgba(0,0,0,0.45);
        }

        .logo {
            text-align: center;
            font-size: 55px;
            background: linear-gradient(45deg, #00f2fe, #4facfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
            filter: drop-shadow(0 2px 8px rgba(0,242,254,0.3));
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 26px;
            font-weight: 600;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .input-box {
            position: relative;
            margin-bottom: 22px;
        }

        /* Input Field */
        .input-box input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            outline: none;
            border-radius: 12px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.08);
            color: white;
            transition: all 0.3s ease;
        }

        .input-box input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .input-box input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #00f2fe;
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.2);
        }

        /* Field Icons */
        .input-box i.field-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .input-box input:focus ~ i.field-icon {
            color: #00f2fe;
        }

        /* Password Toggle Icon */
        .password-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .password-icon:hover {
            color: #00f2fe;
        }

        /* Action Button */
        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(45deg, #00f2fe, #00ff88);
            font-size: 16px;
            font-weight: 600;
            color: #0f2027;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 242, 254, 0.3);
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 242, 254, 0.5);
            background: linear-gradient(45deg, #00ff88, #00f2fe);
        }

        button:active {
            transform: translateY(1px);
        }

        /* Error Alert */
        .error {
            background: rgba(255, 75, 75, 0.15);
            border-left: 4px solid #ff4b4b;
            color: #ff8888;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(5px);
        }

        .register {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .register a {
            color: #00f2fe;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register a:hover {
            color: #00ff88;
            text-decoration: underline;
        }

        .system-text {
            text-align: center;
            font-size: 12px;
            margin-top: 25px;
            opacity: 0.5;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <div class="login-box">
        <div class="logo">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <h2>Hardware System Login</h2>

        <?php if(!empty($error_message)): ?>
            <div class="error">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- CSRF Token Hidden Input -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-box">
                <input type="email" name="email" required placeholder="Email Address" autocomplete="email">
                <i class="fa-solid fa-envelope field-icon"></i>
            </div>

            <div class="input-box">
                <input type="password" id="password" name="password" required placeholder="Password">
                <i class="fa-solid fa-lock field-icon"></i>
                <i class="fa-solid fa-eye password-icon" onclick="showPassword()" id="eye"></i>
            </div>

            <button type="submit">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
        </form>

        <div class="register">
            Don't have an account? <a href="register.php">Register here</a>
        </div>

        <div class="system-text">
            Inventory • Employees • Hardware Management
        </div>
    </div>

    <script>
        function showPassword() {
            const pass = document.getElementById("password");
            const eye = document.getElementById("eye");

            if (pass.type === "password") {
                pass.type = "text";
                eye.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                pass.type = "password";
                eye.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>

</body>
</html>