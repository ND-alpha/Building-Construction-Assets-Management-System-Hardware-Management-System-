<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 Authentication Check
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 🔌 Database Connection
$conn = new mysqli("localhost", "root", "", "hardware");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$message = "";

// 📝 Form Submission Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_expense'])) {
    $main_cat = trim($_POST['main_category']);
    $sub_cat = isset($_POST['sub_category']) ? trim($_POST['sub_category']) : '';
    
    // Main category එක 'Bills' නම් sub category එක එකතු කරයි (e.g. "Bills (Electricity / Current)")
    if ($main_cat === 'Bills' && !empty($sub_cat)) {
        $final_expense_type = "Bills (" . $sub_cat . ")";
    } else {
        $final_expense_type = $main_cat;
    }

    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $expense_date = $_POST['expense_date'];
    $created_by = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 0;

    // Prepared Statement for Safe Data Insertion
    $stmt = $conn->prepare("INSERT INTO expenses (expense_type, amount, description, expense_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $final_expense_type, $amount, $description, $expense_date);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check'></i> Expense recorded successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation'></i> Error recording expense: " . htmlspecialchars($conn->error) . "</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record New Expense - FixIt Hardware</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-input: #f8fafc;
            --text-title: #0f172a;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #f1f5f9;
            --border-outer: #edf2f7;
            --border-input: #e2e8f0;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);

            --btn-bg: #ef4444;
            --btn-hover: #dc2626;
            --alert-success-bg: #f0fdf4;
            --alert-success-text: #166534;
            --alert-success-border: #bbf7d0;
            --alert-danger-bg: #fef2f2;
            --alert-danger-text: #991b1b;
            --alert-danger-border: #fecaca;
        }

        body.dark-theme {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #0f172a;
            --text-title: #f8fafc;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --border-outer: #334155;
            --border-input: #334155;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.25);

            --btn-bg: #ef4444;
            --btn-hover: #f87171;
            --alert-success-bg: rgba(34, 197, 94, 0.15);
            --alert-success-text: #86efac;
            --alert-success-border: #22c55e;
            --alert-danger-bg: rgba(239, 68, 68, 0.15);
            --alert-danger-text: #fca5a5;
            --alert-danger-border: #ef4444;
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
            display: flex; 
            min-height: 100vh; 
            color: var(--text-main); 
        }

        .main-content { 
            margin-left: 280px; 
            width: calc(100% - 280px); 
            padding: 40px; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-container {
            width: 100%;
            max-width: 650px;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-outer);
            box-shadow: var(--shadow);
            padding: 35px;
        }

        .form-header {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-title);
        }

        .form-header i {
            font-size: 24px;
            color: var(--btn-bg);
            background: rgba(239, 68, 68, 0.1);
            padding: 10px;
            border-radius: 12px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid;
        }

        .alert-success {
            background-color: var(--alert-success-bg);
            color: var(--alert-success-text);
            border-color: var(--alert-success-border);
        }

        .alert-danger {
            background-color: var(--alert-danger-bg);
            color: var(--alert-danger-text);
            border-color: var(--alert-danger-border);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-input);
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            background: var(--bg-input);
            font-weight: 600;
            color: var(--text-main);
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--btn-bg);
        }

        .btn-submit {
            background: var(--btn-bg);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            transition: transform 0.2s, background-color 0.2s;
        }

        .btn-submit:hover {
            background: var(--btn-hover);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
            .form-group.two-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php if (file_exists('sidebar.php')) include('sidebar.php'); ?>

    <div class="main-content">
        <div class="form-container">
            
            <div class="form-header">
                <i class="fa-solid fa-receipt"></i>
                <h2>Record Shop Expense</h2>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    
                    <!-- Main Expense Category -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-tags"></i> Main Category</label>
                        <select name="main_category" id="main_category" onchange="toggleBillSubCategory()" required>
                            <option value="Bills">⚡ Utility Bills & Payments</option>
                            <option value="Hardware Stock Payment">📦 Hardware Stock Fill / Local Item Payments</option>
                            <option value="Transport">🚚 Transport & Freight Charges</option>
                            <option value="Damaged Stock">⚠️ Damaged / Expired Goods Cost</option>
                            <option value="Shop Maintenance">🛠️ Shop Repairs & Maintenance</option>
                            <option value="Staff Allowance">👥 Staff Extra Allowance / Bonus</option>
                            <option value="Other">📝 Other General Expense</option>
                        </select>
                    </div>

                    <!-- Sub Category for Utility Bills -->
                    <div class="form-group" id="sub_category_box">
                        <label><i class="fa-solid fa-bolt"></i> Bill Type Specification</label>
                        <select name="sub_category" id="sub_category">
                            <option value="Electricity / Current">Electricity Bill (Current Bill)</option>
                            <option value="Water">Water Bill</option>
                            <option value="Internet / Wi-Fi">Internet / SLT / Wi-Fi Bill</option>
                            <option value="Telephone">Telephone / Mobile Bill</option>
                            <option value="Other Bill">Other Miscellaneous Bill</option>
                        </select>
                    </div>

                    <!-- Amount & Date (Two Column Layout) -->
                    <div class="form-group two-col">
                        <div>
                            <label><i class="fa-solid fa-money-bill-wave"></i> Amount (LKR)</label>
                            <input type="number" step="0.01" name="amount" placeholder="0.00" required min="0.01">
                        </div>
                        <div>
                            <label><i class="fa-solid fa-calendar-day"></i> Expense Date</label>
                            <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <!-- Description Note -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-note-sticky"></i> Description / Remarks (Optional)</label>
                        <textarea name="description" rows="3" placeholder="e.g. Electricity bill payment for July month or 10 Cement bags damaged in transport..."></textarea>
                    </div>

                    <button type="submit" name="save_expense" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Save Expense Record
                    </button>

                </div>
            </form>

        </div>
    </div>

    <!-- JavaScript for Dynamic Sub-Category & Dark Mode -->
    <script>
        function toggleBillSubCategory() {
            const mainCat = document.getElementById('main_category').value;
            const subCatBox = document.getElementById('sub_category_box');
            
            if (mainCat === 'Bills') {
                subCatBox.style.display = 'flex';
            } else {
                subCatBox.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleBillSubCategory();

            // Theme Loader
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
            }

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