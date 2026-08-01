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

// 2. Log vela inna user samanya "Employee" kenek nam eyata access thahanam kirima
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php"); 
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

// Database eken serama employees la gannawa
$query = "SELECT employee_id, full_name, email, role FROM employee";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware System - Employee List (Admin Only)</title>
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
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 900px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #e1e5ee;
            padding-bottom: 15px;
        }

        h2 {
            color: #1e3c72;
            font-weight: 700;
            font-size: 26px;
        }

        .admin-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-add {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-add:hover {
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
            transform: translateY(-1px);
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid #e1e5ee;
            font-size: 14px;
        }

        th {
            background-color: #f4f7fc;
            color: #1e3c72;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f9fbfd;
        }

        .role-pill {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .role-admin {
            background-color: #d4edda;
            color: #155724;
        }

        .role-employee {
            background-color: #cce5ff;
            color: #004085;
        }

        .no-data {
            text-align: center;
            color: #777;
            padding: 30px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <div>
            <h2>Registered Employees</h2>
            <span class="admin-badge">Admin Portal</span>
        </div>
        <a href="register.php" class="btn-add">+ Add New Employee</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>System Role</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <span class="role-pill <?php echo ($row['role'] == 'Admin') ? 'role-admin' : 'role-employee'; ?>">
                                <?php echo htmlspecialchars($row['role']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?> <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data">No employees found in the system.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>