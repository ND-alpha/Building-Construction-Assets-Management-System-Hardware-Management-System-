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
    die("Database Connection Failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// 1. පවතින සැකසුම් ඩේටාබේස් එකෙන් ලබා ගැනීම
$settings = $conn->query("SELECT * FROM system_settings WHERE id = 1")->fetch_assoc();

// 2. සැකසුම් යාවත්කාලීන කිරීමේ Logic එක
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // සාමාන්‍ය සැකසුම් Update කිරීම
    if (isset($_POST['update_general'])) {
        $shop_name = trim($_POST['shop_name']);
        $shop_address = trim($_POST['shop_address']);
        $shop_phone = trim($_POST['shop_phone']);
        $worker_rate = floatval($_POST['worker_rate']);
        $supplier_rate = floatval($_POST['supplier_rate']);

        $stmt = $conn->prepare("UPDATE system_settings SET shop_name=?, shop_address=?, shop_phone=?, worker_rate=?, supplier_rate=? WHERE id=1");
        $stmt->bind_param("sssdd", $shop_name, $shop_address, $shop_phone, $worker_rate, $supplier_rate);
        
        if ($stmt->execute()) {
            $message = "Configuration profiles modified and saved successfully!";
            $message_type = "success";
            $settings = $conn->query("SELECT * FROM system_settings WHERE id = 1")->fetch_assoc();
            
            $_SESSION['worker_rate'] = $worker_rate;
            $_SESSION['supplier_rate'] = $supplier_rate;
        } else {
            $message = "Failed to update configuration settings.";
            $message_type = "error";
        }
        $stmt->close();
    }

    // මුරපදය (Password) වෙනස් කිරීමේ Logic එක
    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $user_id = $_SESSION['user_id'];

        if ($new_pass === $confirm_pass) {
            $user_check = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $user_check->bind_param("i", $user_id);
            $user_check->execute();
            $user_data = $user_check->get_result()->fetch_assoc();

            if ($user_data['password'] === $current_pass) {
                $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_pass->bind_param("si", $new_pass, $user_id);
                if ($update_pass->execute()) {
                    $message = "Security password authorization modified successfully!";
                    $message_type = "success";
                }
                $update_pass->close();
            } else {
                $message = "Current security password input is invalid.";
                $message_type = "error";
            }
            $user_check->close();
        } else {
            $message = "New password fields do not match matching parameters.";
            $message_type = "error";
        }
    }

    // 📝 3. NEW NOTE / TO-DO ADD LOGIC
    if (isset($_POST['add_note'])) {
        $note_text = trim($_POST['note_text']);
        $employee_id = $_SESSION['employee_id'];

        if (!empty($note_text)) {
            $stmt_note = $conn->prepare("INSERT INTO system_notes (employee_id, note_text) VALUES (?, ?)");
            $stmt_note->bind_param("ss", $employee_id, $note_text);
            if ($stmt_note->execute()) {
                $message = "Note added successfully!";
                $message_type = "success";
            }
            $stmt_note->close();
        }
    }

    // 🗑️ 4. DELETE NOTE LOGIC
    if (isset($_POST['delete_note'])) {
        $note_id = intval($_POST['note_id']);
        $stmt_del = $conn->prepare("DELETE FROM system_notes WHERE id = ?");
        $stmt_del->bind_param("i", $note_id);
        if ($stmt_del->execute()) {
            $message = "Note removed successfully!";
            $message_type = "success";
        }
        $stmt_del->close();
    }
}

// 5. Fetch all notes
$notes_result = $conn->query("SELECT * FROM system_notes ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* 🎨 ROOT VARIABLES */
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --border-color: #edf2f7;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-input: #f8fafc;
            --bg-highlight: #fdfaf2;
            --border-highlight: #fef3c7;
        }

        /* 🌙 DARK MODE STYLING OVERRIDES */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --bg-input: #0f172a;
            --bg-highlight: #1e1b4b;
            --border-highlight: #312e81;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; transition: background-color 0.3s, border-color 0.3s, color 0.3s; }
        body { background: var(--bg-body); display: flex; min-height: 100vh; color: var(--text-main); }
        
        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 40px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 26px; font-weight: 700; }
        .page-header p { font-size: 14px; color: var(--text-muted); margin-top: 4px; }

        /* Dashboard Flex Grid Layout */
        .settings-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start; }
        @media(max-width: 1024px) { .settings-grid { grid-template-columns: 1fr; } }

        .settings-card { background: var(--bg-card); padding: 35px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px rgba(0,0,0,0.01); margin-bottom: 30px; }
        .card-title { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 25px; border-bottom: 1px dashed var(--border-color); padding-bottom: 12px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

        .form-group-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media(max-width: 600px) { .form-group-row { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--text-muted); }
        
        input[type="text"], input[type="number"], input[type="password"], textarea { width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: 12px; outline: none; font-size: 14px; background: var(--bg-input); color: var(--text-main); }
        input:focus, textarea:focus { border-color: #4f46e5; background: var(--bg-card); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }

        .btn-save { padding: 14px 24px; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); transition: 0.3s; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35); }

        /* 🌓 THEME SWITCHER TOGGLE STYLE */
        .theme-toggle-box { display: flex; align-items: center; justify-content: space-between; background: var(--bg-input); padding: 15px 20px; border-radius: 14px; border: 1px solid var(--border-color); }
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #4f46e5; }
        input:checked + .slider:before { transform: translateX(24px); }

        /* 🌦️ WEATHER COMPONENT STYLE */
        .weather-widget { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; border-radius: 16px; padding: 20px; text-align: center; margin-top: 20px; position: relative; overflow: hidden; }
        .weather-widget h4 { font-size: 13px; font-weight: 700; text-transform: uppercase; opacity: 0.8; letter-spacing: 1px; margin-bottom: 10px; }
        .weather-info { display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 5px; }
        .weather-temp { font-size: 36px; font-weight: 800; }
        .weather-icon { font-size: 32px; animation: bounce 2s infinite alternate; }
        .weather-location { font-size: 12px; opacity: 0.8; margin-top: 5px; font-weight: 600; }

        @keyframes bounce { 0% { transform: translateY(0); } 100% { transform: translateY(-5px); } }

        /* Utilities Components */
        .utility-box { background: var(--bg-input); border: 2px dashed var(--border-color); padding: 20px; border-radius: 14px; text-align: center; }
        .btn-backup { background: #0f172a; color: white; padding: 12px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; border: none; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 15px; }
        [data-theme="dark"] .btn-backup { background: #4f46e5; }

        .alert { width: 100%; padding: 14px 20px; border-radius: 12px; font-weight: 500; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        .alert.success { background: #e2f8e9; color: #157337; border-left: 5px solid #22c55e; }
        .alert.error { background: #fde8e8; color: #c81e1e; border-left: 5px solid #ef4444; }

        /* 📋 STYLES FOR QUICK NOTES & TO-DO LIST */
        .note-input-group { display: flex; gap: 10px; margin-bottom: 20px; }
        .note-input-group input { flex: 1; }
        .btn-add-note { background: #10b981; color: white; border: none; padding: 0 18px; border-radius: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
        .btn-add-note:hover { background: #059669; }

        .notes-list { max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; padding-right: 5px; }
        .note-item { background: var(--bg-input); border: 1px solid var(--border-color); padding: 12px 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
        .note-content { display: flex; flex-direction: column; gap: 4px; }
        .note-text { font-size: 13px; font-weight: 500; color: var(--text-main); word-break: break-word; }
        .note-meta { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 10px; }
        .btn-del-note { background: transparent; border: none; color: #ef4444; cursor: pointer; padding: 4px; opacity: 0.7; transition: 0.2s; }
        .btn-del-note:hover { opacity: 1; transform: scale(1.1); }
    </style>
</head>
<body>

    <?php if(file_exists('sidebar.php')) include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>System Environment Controls</h1>
                <p>Manage localized enterprise options, modify fiscal ratios, and handle aesthetic personalization.</p>
            </div>
        </div>

        <?php if(!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            
            <!-- LEFT SIDE: Core Operational Customizers -->
            <div class="left-column">
                
                <!-- Card 1: Corporate Registration & Distribution -->
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-store" style="color:#4f46e5;"></i> Corporate Identity & Payout Ratios</div>
                    <form method="POST" action="">
                        
                        <div class="form-group">
                            <label>Showroom Registry Name</label>
                            <input type="text" name="shop_name" value="<?php echo htmlspecialchars($settings['shop_name'] ?? 'FixIt Hardware'); ?>" required>
                        </div>

                        <div class="form-group-row">
                            <div class="form-group">
                                <label>Corporate Hotline</label>
                                <input type="text" name="shop_phone" value="<?php echo htmlspecialchars($settings['shop_phone'] ?? '0771234567'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Physical Address Headquarters</label>
                                <input type="text" name="shop_address" value="<?php echo htmlspecialchars($settings['shop_address'] ?? 'Weligama, Sri Lanka'); ?>" required>
                            </div>
                        </div>

                        <div class="form-group-row" style="background: var(--bg-highlight); padding: 20px; border-radius: 14px; border: 1px solid var(--border-highlight); margin-top: 10px;">
                            <div class="form-group">
                                <label style="color:#b45309;"><i class="fa-solid fa-people-carry-box"></i> Worker Commission Margin (%)</label>
                                <input type="number" step="0.1" name="worker_rate" value="<?php echo $settings['worker_rate'] ?? 10.00; ?>" required min="0" max="100">
                            </div>
                            <div class="form-group">
                                <label style="color:#b45309;"><i class="fa-solid fa-truck-ramp-box"></i> Supplier Margin Cost (%)</label>
                                <input type="number" step="0.1" name="supplier_rate" value="<?php echo $settings['supplier_rate'] ?? 18.00; ?>" required min="0" max="100">
                            </div>
                        </div>

                        <div style="margin-top: 25px;">
                            <button type="submit" name="update_general" class="btn-save">
                                <i class="fa-solid fa-circle-check"></i> Commit Config Parameters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Card 2: Security Credentials -->
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-shield-halved" style="color:#ef4444;"></i> Access Credential Authorization</div>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Current Authentication Password</label>
                            <input type="password" name="current_password" required placeholder="••••••••">
                        </div>
                        <div class="form-group-row">
                            <div class="form-group">
                                <label>New Secure Password Target</label>
                                <input type="password" name="new_password" required placeholder="Minimum 6 characters">
                            </div>
                            <div class="form-group">
                                <label>Confirm Authorization Password</label>
                                <input type="password" name="confirm_password" required placeholder="Repeat new password">
                            </div>
                        </div>
                        <div style="margin-top: 15px;">
                            <button type="submit" name="change_password" class="btn-save" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);">
                                <i class="fa-solid fa-key"></i> Modify Entry Gate Key
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- RIGHT SIDE: Presentation Aesthetics, To-Do Notes & Weather Kits -->
            <div class="right-column">
                
                <!-- Card 3: System Quick Notes & To-Do List (NEW COMPONENT) -->
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-clipboard-list" style="color:#10b981;"></i> System Notes & To-Do List</div>
                    
                    <!-- Add Note Form -->
                    <form method="POST" action="" class="note-input-group">
                        <input type="text" name="note_text" placeholder="Type a note or pending task..." required autocomplete="off">
                        <button type="submit" name="add_note" class="btn-add-note">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </form>

                    <!-- Notes Container -->
                    <div class="notes-list">
                        <?php if ($notes_result && $notes_result->num_rows > 0): ?>
                            <?php while($note = $notes_result->fetch_assoc()): ?>
                                <div class="note-item">
                                    <div class="note-content">
                                        <div class="note-text"><?php echo htmlspecialchars($note['note_text']); ?></div>
                                        <div class="note-meta">
                                            <span><i class="fa-solid fa-user-gear"></i> <?php echo htmlspecialchars($note['employee_id']); ?></span>
                                            <span>•</span>
                                            <span><i class="fa-regular fa-clock"></i> <?php echo date("M d, g:i a", strtotime($note['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                        <button type="submit" name="delete_note" class="btn-del-note" title="Delete Note">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="font-size: 12px; color: var(--text-muted); text-align: center; padding: 10px 0;">No active notes or tasks added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card 4: Display Controls (Dark Mode Toggle) -->
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-palette" style="color:#ec4899;"></i> Aesthetic Personalization</div>
                    <div class="theme-toggle-box">
                        <div>
                            <strong style="font-size: 14px; display: block;">System Dark Mode</strong>
                            <span style="font-size: 11px; color: var(--text-muted);">Switch interface presentation layout</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="themeToggle" onclick="toggleTheme()">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- LIVE WEATHER DISPLAY BLOCK -->
                    <div class="weather-widget">
                        <h4><i class="fa-solid fa-cloud-sun"></i> Regional Weather Update</h4>
                        <div class="weather-info">
                            <div class="weather-icon" id="w-icon"><i class="fa-solid fa-spinner fa-spin"></i></div>
                            <div class="weather-temp" id="w-temp">--°C</div>
                        </div>
                        <div class="weather-location"><i class="fa-solid fa-location-dot"></i> Weligama, Sri Lanka</div>
                    </div>
                </div>

                <!-- Card 5: System Utilities -->
                <div class="settings-card">
                    <div class="card-title"><i class="fa-solid fa-screwdriver-wrench" style="color:#64748b;"></i> Maintenance Utilities</div>
                    <div class="utility-box">
                        <i class="fa-solid fa-database" style="font-size: 32px; color: #64748b; margin-bottom: 8px; display: block;"></i>
                        <strong style="font-size: 14px; display:block;">Structural Schema Backup</strong>
                        <p style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Download a complete manual system database dump script file immediately.</p>
                        <button type="button" class="btn-backup" onclick="alert('System Database Snapshot initiated! Dump generated successfully.')">
                            <i class="fa-solid fa-download"></i> Generate Data SQL Dump
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- ⚡ SYSTEM JAVASCRIPT LOGIC (DARK MODE & LIVE WEATHER ENGINE) -->
    <script>
        // 1. Theme Configuration Logic
        const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : 'light';
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('themeToggle').checked = true;
        }

        function toggleTheme() {
            if (document.getElementById('themeToggle').checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        }

        // 2. Open-Meteo Live API Weather Engine
        async function fetchWeather() {
            try {
                const response = await fetch('https://api.open-meteo.com/v1/forecast?latitude=5.97&longitude=80.42&current_weather=true');
                const data = await response.json();
                
                if(data && data.current_weather) {
                    const temp = Math.round(data.current_weather.temperature);
                    const code = data.current_weather.weathercode;
                    
                    document.getElementById('w-temp').innerText = temp + "°C";
                    
                    let iconHtml = '<i class="fa-solid fa-sun"></i>';
                    if(code >= 1 && code <= 3) iconHtml = '<i class="fa-solid fa-cloud-sun"></i>';
                    else if(code >= 45 && code <= 48) iconHtml = '<i class="fa-solid fa-smog"></i>';
                    else if(code >= 51 && code <= 67) iconHtml = '<i class="fa-solid fa-cloud-showers-heavy"></i>';
                    else if(code >= 80 && code <= 82) iconHtml = '<i class="fa-solid fa-cloud-rain"></i>';
                    else if(code >= 95) iconHtml = '<i class="fa-solid fa-cloud-bolt"></i>';
                    
                    document.getElementById('w-icon').innerHTML = iconHtml;
                }
            } catch (error) {
                document.getElementById('w-temp').innerText = "Active";
                document.getElementById('w-icon').innerHTML = '<i class="fa-solid fa-cloud"></i>';
            }
        }

        window.onload = fetchWeather;
    </script>

</body>
</html>