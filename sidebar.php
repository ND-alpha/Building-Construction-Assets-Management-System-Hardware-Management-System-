<?php
// Session එකක් ආරම්භ කර නොමැති නම් පමණක් ආරම්භ කරන්න
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Authentication Check: User කෙනෙක් Login වෙලා නැත්නම් කෙලින්ම Login Page එකට හරවා යැවීම
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

// 🛍️ DB Connection & System Settings Fetch Logic
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "hardware");
}

// Dynamic Shop Settings Fetch
$sys_shop_name = 'FixIt Hardware'; // Default Fallback
if ($conn && !$conn->connect_error) {
    $settings_query = $conn->query("SELECT shop_name FROM system_settings WHERE id = 1");
    if ($settings_query && $settings_query->num_rows > 0) {
        $setting_data = $settings_query->fetch_assoc();
        if (!empty($setting_data['shop_name'])) {
            $sys_shop_name = $setting_data['shop_name'];
        }
    }
}

// දැනට විවෘතව පවතින පිටුවේ File Name එක ලබා ගැනීම
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = strtolower($_SESSION['user_role']);

// --- 🛡️ ADMIN ONLY ACCESS CONTROL ---
$admin_only_pages = [
    'register.php', 
    'employees.php', 
    'add_expense.php', 
    'monthly_report.php',
    'manage_categories.php',
    'manage_subcategories.php',
    'add_supplier.php',
    'manage_suppliers.php'
];

if (in_array($current_page, $admin_only_pages) && $user_role !== 'admin') {
    header("Location: dashboard.php?error=unauthorized_access");
    exit();
}

$display_role = ($user_role === 'admin') ? 'Admin' : 'Employee';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    /* 🎨 GLOBAL THEME SYSTEM VARIABLES */
    :root {
        --sys-bg: #f8fafc;
        --sys-card: #ffffff;
        --sys-text: #1e293b;
        --sys-border: #edf2f7;
        --sys-input: #f8fafc;
        --sys-muted: #64748b;
        
        --sb-bg: #0f172a;
        --sb-text: #94a3b8;
        --sb-card-bg: rgba(255,255,255,0.03);
        --sb-card-border: rgba(255,255,255,0.05);
        --sb-hover: #1e293b;
        --accent-cyan: #06b6d4;
    }

    /* 🌙 DARK MODE OVERRIDES */
    [data-theme="dark"] {
        --sys-bg: #0f172a;
        --sys-card: #1e293b;
        --sys-text: #f8fafc;
        --sys-border: #334155;
        --sys-input: #0f172a;
        --sys-muted: #94a3b8;
        
        --sb-bg: #090d16;
        --sb-text: #64748b;
        --sb-card-bg: rgba(0,0,0,0.25);
        --sb-card-border: rgba(255,255,255,0.03);
        --sb-hover: #131b2e;
        --accent-cyan: #06b6d4;
    }

    /* 🖥️ GLOBAL APPLICATION BASE */
    body {
        background-color: var(--sys-bg) !important;
        color: var(--sys-text) !important;
        transition: background-color 0.3s, color 0.3s;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .settings-card, .table-card, .analytic-card, .control-card {
        background-color: var(--sys-card) !important;
        border-color: var(--sys-border) !important;
        color: var(--sys-text) !important;
    }

    input, select, textarea {
        background-color: var(--sys-input) !important;
        border-color: var(--sys-border) !important;
        color: var(--sys-text) !important;
    }

    /* --- SIDEBAR CORE STYLING --- */
    .sidebar {
        width: 280px;
        background: var(--sb-bg);
        color: var(--sb-text);
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        border-right: 1px solid rgba(255,255,255,0.05);
        z-index: 999;
        transition: all 0.3s ease;
    }
    
    .brand {
        padding: 22px 25px;
        font-size: 18px;
        font-weight: 800;
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        letter-spacing: -0.5px;
    }
    .brand i { color: var(--accent-cyan); font-size: 22px; }
    
    .user-profile {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--sb-card-bg);
        margin: 15px 15px 10px 15px;
        border-radius: 14px;
        border: 1px solid var(--sb-card-border);
    }
    .avatar {
        width: 40px;
        height: 40px;
        background: rgba(6, 182, 212, 0.12);
        color: var(--accent-cyan);
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 18px;
    }
    .user-info h4 { color: white; font-size: 13px; font-weight: 700; margin-bottom: 3px; }
    
    /* 🏷️ DYNAMIC ROLE BADGE */
    .role-badge { 
        font-size: 10px; 
        padding: 2px 8px; 
        border-radius: 20px; 
        font-weight: 700; 
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .role-badge.admin-badge {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }
    .role-badge.staff-badge {
        background: rgba(6, 182, 212, 0.15);
        color: #06b6d4;
    }

    /* 🕒 LIVE DATE & TIME COMPONENT */
    .datetime-widget {
        background: var(--sb-card-bg);
        border: 1px solid var(--sb-card-border);
        margin: 0 15px 15px 15px;
        padding: 10px 14px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .live-time { color: #ffffff; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .live-date { color: #64748b; font-size: 11px; font-weight: 600; padding-left: 22px; }

    /* --- SECTION HEADERS --- */
    .menu-section-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: #475569;
        padding: 12px 20px 6px 20px;
        letter-spacing: 0.8px;
    }

    /* Navigation Links Styling */
    .nav-links { list-style: none; padding: 0 15px 15px 15px; flex-grow: 1; overflow-y: auto; }
    .nav-links::-webkit-scrollbar { width: 4px; }
    .nav-links::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 10px; }
    .nav-links li { margin-bottom: 4px; position: relative; }
    
    .nav-links a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 13.5px;
        transition: all 0.25s ease;
    }
    .nav-links a:hover { background: var(--sb-hover); color: white; }
    .nav-links a i { font-size: 15px; width: 20px; text-align: center; }
    
    /* 🎯 ACTIVE LINK HIGHLIGHT STYLING */
    .nav-links li.active-page > a {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        color: white !important;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(6, 182, 212, 0.25);
    }
    .nav-links li.active-page > a i { color: white !important; }

    /* Dropdown Elements */
    .dropdown-btn { cursor: pointer; }
    .arrow { margin-left: auto; font-size: 11px; transition: transform 0.3s ease; }
    .arrow.rotate { transform: rotate(180deg); color: var(--accent-cyan); }

    /* Dropdown Sub-menu Container */
    .dropdown-container {
        list-style: none;
        padding-left: 15px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        background: rgba(0, 0, 0, 0.15);
        border-radius: 0 0 10px 10px;
    }
    .dropdown-container.active {
        max-height: 250px;
        margin-top: 2px;
        margin-bottom: 6px;
        padding-top: 6px;
        padding-bottom: 6px;
    }
    .dropdown-container a { padding: 9px 12px; font-size: 12.5px; color: #cbd5e1; font-weight: 500; }
    .dropdown-container a:hover { background: rgba(255, 255, 255, 0.04); color: var(--accent-cyan); }
    .dropdown-container a i { font-size: 11px; color: rgba(255,255,255,0.3); }
    
    .dropdown-container li.active-page a { color: var(--accent-cyan) !important; font-weight: 700; }
    .dropdown-container li.active-page a i { color: var(--accent-cyan) !important; }

    /* Footer System Switch Adjustments */
    .sidebar-footer { padding: 15px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; gap: 10px; }
    
    /* 🌓 SWITCH TOGGLE STYLE BUTTON */
    .mode-toggle-panel { display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.03); }
    .toggle-label { color: #e2e8f0; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    
    .switch-btn { position: relative; display: inline-block; width: 44px; height: 22px; }
    .switch-btn input { opacity: 0; width: 0; height: 0; }
    .slider-round { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #475569; transition: .3s; border-radius: 34px; }
    .slider-round:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .slider-round { background-color: var(--accent-cyan); }
    input:checked + .slider-round:before { transform: translateX(22px); }

    .logout-btn { display: flex; align-items: center; gap: 12px; text-decoration: none; padding: 11px 14px; background: rgba(239, 68, 68, 0.08); border-radius: 10px; color: #ef4444; font-weight: 700; font-size: 13.5px; transition: all 0.3s ease; cursor: pointer; }
    .logout-btn:hover { background: #ef4444; color: white; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); }

    /* 🚨 LOGOUT MODAL STYLING */
    .logout-modal {
        display: none; 
        position: fixed; 
        z-index: 10000; 
        left: 0; top: 0; 
        width: 100%; height: 100%; 
        background-color: rgba(15, 23, 42, 0.6); 
        backdrop-filter: blur(5px);
        align-items: center; 
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .logout-modal.show { display: flex; opacity: 1; }
    .modal-content {
        background-color: var(--sys-card);
        color: var(--sys-text);
        padding: 30px;
        border-radius: 18px;
        width: 90%;
        max-width: 380px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        border: 1px solid var(--sys-border);
        transform: translateY(-30px);
        transition: transform 0.3s ease;
    }
    .logout-modal.show .modal-content { transform: translateY(0); }
    .modal-icon { font-size: 46px; color: #ef4444; margin-bottom: 15px; }
    .modal-content h3 { margin-bottom: 8px; font-size: 20px; font-weight: 700; }
    .modal-content p { color: var(--sys-muted); font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
    .modal-buttons { display: flex; gap: 12px; justify-content: center; }
    .modal-btn { padding: 12px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; border: none; transition: all 0.2s ease; flex: 1; }
    .btn-yes { background-color: #ef4444; color: white; }
    .btn-yes:hover { background-color: #dc2626; }
    .btn-no { background-color: var(--sys-input); color: var(--sys-text); border: 1px solid var(--sys-border); }
</style>

<div class="sidebar">
    <!-- Brand Logo & Dynamic Shop Name -->
    <div class="brand">
        <i class="fa-solid fa-screwdriver-wrench"></i>
        <span><?php echo htmlspecialchars($sys_shop_name); ?></span>
    </div>
    
    <!-- Profile Info -->
    <div class="user-profile">
        <div class="avatar">
            <i class="fa-solid <?php echo ($user_role === 'admin') ? 'fa-user-shield' : 'fa-user-gear'; ?>"></i>
        </div>
        <div class="user-info">
            <h4><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'System User'; ?></h4>
            <span class="role-badge <?php echo ($user_role === 'admin') ? 'admin-badge' : 'staff-badge'; ?>">
                <?php echo $display_role; ?>
            </span>
        </div>
    </div>

    <!-- Live Date & Time Widget -->
    <div class="datetime-widget">
        <div class="live-time" id="clock-display">
            <i class="fa-regular fa-clock" style="color: #06b6d4;"></i> 00:00:00 AM
        </div>
        <div class="live-date" id="calendar-display">Loading date info...</div>
    </div>

    <!-- Menu Links -->
    <ul class="nav-links">
        
        <!-- SECTION 1: MAIN -->
        <div class="menu-section-label">Main</div>
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active-page' : ''; ?>">
            <a href="dashboard.php">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- SECTION 2: OPERATIONS -->
        <div class="menu-section-label">Operations</div>
        
        <?php $ord_active = in_array($current_page, ['add_order.php', 'manage_order.php']); ?>
        <li class="dropdown-item">
            <a href="javascript:void(0)" class="dropdown-btn">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Orders</span>
                <i class="fa-solid fa-chevron-down arrow <?php echo $ord_active ? 'rotate' : ''; ?>"></i>
            </a>
            <ul class="dropdown-container <?php echo $ord_active ? 'active' : ''; ?>">
                <li class="<?php echo ($current_page == 'add_order.php') ? 'active-page' : ''; ?>"><a href="add_order.php?action=add"><i class="fa-solid fa-circle-plus"></i> Add Order</a></li>
                <li class="<?php echo ($current_page == 'manage_order.php') ? 'active-page' : ''; ?>"><a href="manage_order.php?action=manage"><i class="fa-solid fa-layer-group"></i> Manage Orders</a></li>
            </ul>
        </li>

        <?php $pay_active = in_array($current_page, ['add_payment.php', 'manage_payment.php']); ?>
        <li class="dropdown-item">
            <a href="javascript:void(0)" class="dropdown-btn">
                <i class="fa-solid fa-credit-card"></i>
                <span>Payments Vault</span>
                <i class="fa-solid fa-chevron-down arrow <?php echo $pay_active ? 'rotate' : ''; ?>"></i>
            </a>
            <ul class="dropdown-container <?php echo $pay_active ? 'active' : ''; ?>">
                <li class="<?php echo ($current_page == 'add_payment.php') ? 'active-page' : ''; ?>"><a href="add_payment.php?action=add"><i class="fa-solid fa-circle-plus"></i> Add Payment</a></li>
                <li class="<?php echo ($current_page == 'manage_payment.php') ? 'active-page' : ''; ?>"><a href="manage_payment.php?action=manage"><i class="fa-solid fa-receipt"></i> Manage Payments</a></li>
            </ul>
        </li>

        <!-- SECTION 3: INVENTORY & SUPPLIERS -->
        <div class="menu-section-label">Inventory & Stock</div>

        <?php $inv_active = in_array($current_page, ['add_product.php', 'manage_inventory.php', 'manage_categories.php', 'manage_subcategories.php']); ?>
        <li class="dropdown-item">
            <a href="javascript:void(0)" class="dropdown-btn">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Inventory Stock</span>
                <i class="fa-solid fa-chevron-down arrow <?php echo $inv_active ? 'rotate' : ''; ?>"></i>
            </a>
            <ul class="dropdown-container <?php echo $inv_active ? 'active' : ''; ?>">
                <li class="<?php echo ($current_page == 'add_product.php') ? 'active-page' : ''; ?>"><a href="add_product.php?action=add"><i class="fa-solid fa-circle-plus"></i> Add New Product</a></li>
                <li class="<?php echo ($current_page == 'manage_inventory.php') ? 'active-page' : ''; ?>"><a href="manage_inventory.php?action=manage"><i class="fa-solid fa-layer-group"></i> Manage Inventory</a></li>
                
                <?php if ($user_role === 'admin'): ?>
                    <li class="<?php echo ($current_page == 'manage_categories.php') ? 'active-page' : ''; ?>"><a href="manage_categories.php"><i class="fa-solid fa-folder-plus"></i> Manage Categories</a></li>
                    <li class="<?php echo ($current_page == 'manage_subcategories.php') ? 'active-page' : ''; ?>"><a href="manage_subcategories.php"><i class="fa-solid fa-tags"></i> Manage Sub-Categories</a></li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- 🚚 SUPPLIERS HUB (ADMIN ONLY) -->
        <?php if ($user_role === 'admin'): ?>
            <?php $sup_active = in_array($current_page, ['add_supplier.php', 'manage_suppliers.php']); ?>
            <li class="dropdown-item">
                <a href="javascript:void(0)" class="dropdown-btn">
                    <i class="fa-solid fa-truck-field"></i>
                    <span>Suppliers Hub</span>
                    <i class="fa-solid fa-chevron-down arrow <?php echo $sup_active ? 'rotate' : ''; ?>"></i>
                </a>
                <ul class="dropdown-container <?php echo $sup_active ? 'active' : ''; ?>">
                    <li class="<?php echo ($current_page == 'add_supplier.php') ? 'active-page' : ''; ?>"><a href="add_supplier.php?action=add"><i class="fa-solid fa-circle-plus"></i> Add New Supplier</a></li>
                    <li class="<?php echo ($current_page == 'manage_suppliers.php') ? 'active-page' : ''; ?>"><a href="manage_suppliers.php?action=manage"><i class="fa-solid fa-address-book"></i> Manage Suppliers</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <!-- SECTION 4: FINANCE & EXPENSES (Admin Only) -->
        <?php if ($user_role === 'admin'): ?>
            <div class="menu-section-label">Finance & Reports</div>

            <li class="<?php echo ($current_page == 'add_expense.php') ? 'active-page' : ''; ?>">
                <a href="add_expense.php">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Shop Expenses</span>
                </a>
            </li>

            <li class="<?php echo ($current_page == 'monthly_report.php') ? 'active-page' : ''; ?>">
                <a href="monthly_report.php">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Monthly Report</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- SECTION 5: MANAGEMENT -->
        <div class="menu-section-label">Administration</div>

        <?php $cust_active = in_array($current_page, ['add_customer.php', 'manage_customer.php']); ?>
        <li class="dropdown-item">
            <a href="javascript:void(0)" class="dropdown-btn">
                <i class="fa-solid fa-users"></i>
                <span>Customer Base</span>
                <i class="fa-solid fa-chevron-down arrow <?php echo $cust_active ? 'rotate' : ''; ?>"></i>
            </a>
            <ul class="dropdown-container <?php echo $cust_active ? 'active' : ''; ?>">
                <li class="<?php echo ($current_page == 'add_customer.php') ? 'active-page' : ''; ?>"><a href="add_customer.php?action=add"><i class="fa-solid fa-circle-plus"></i> Add New Customer</a></li>
                <li class="<?php echo ($current_page == 'manage_customer.php') ? 'active-page' : ''; ?>"><a href="manage_customer.php?action=manage"><i class="fa-solid fa-users-viewfinder"></i> Manage Customers</a></li>
            </ul>
        </li>

        <?php if ($user_role === 'admin'): ?>
            <?php $staff_active = in_array($current_page, ['register.php', 'employees.php']); ?>
            <li class="dropdown-item">
                <a href="javascript:void(0)" class="dropdown-btn">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Staff Management</span>
                    <i class="fa-solid fa-chevron-down arrow <?php echo $staff_active ? 'rotate' : ''; ?>"></i>
                </a>
                <ul class="dropdown-container <?php echo $staff_active ? 'active' : ''; ?>">
                    <li class="<?php echo ($current_page == 'register.php') ? 'active-page' : ''; ?>"><a href="register.php"><i class="fa-solid fa-user-plus"></i> Create Account</a></li>
                    <li class="<?php echo ($current_page == 'employees.php') ? 'active-page' : ''; ?>"><a href="employees.php?action=manage"><i class="fa-solid fa-users-gear"></i> Manage Registry</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <li class="<?php echo ($current_page == 'settings.php') ? 'active-page' : ''; ?>">
            <a href="settings.php">
                <i class="fa-solid fa-sliders"></i>
                <span>Settings Control</span>
            </a>
        </li>

    </ul>

    <!-- Footer Controls -->
    <div class="sidebar-footer">
        <div class="mode-toggle-panel">
            <div class="toggle-label" id="sys-mode-label">
                <i class="fa-solid fa-sun" style="color: #f59e0b;"></i> Light UI
            </div>
            <label class="switch-btn">
                <input type="checkbox" id="themeSwitchCheckbox" onclick="toggleGlobalSystemTheme()">
                <span class="slider-round"></span>
            </label>
        </div>

        <a href="javascript:void(0)" class="logout-btn" id="logoutSystemBtn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout System</span>
        </a>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div id="customLogoutModal" class="logout-modal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <h3>Do you want to log out?</h3>
        <p>Are you sure you want to exit from <?php echo htmlspecialchars($sys_shop_name); ?> system?</p>
        <div class="modal-buttons">
            <button class="modal-btn btn-yes" onclick="processSystemLogout(true)">Yes, Logout</button>
            <button class="modal-btn btn-no" onclick="processSystemLogout(false)">No, Dashboard</button>
        </div>
    </div>
</div>

<script>
    // 1. DROPDOWN ACCORDION LOGIC
    document.querySelectorAll('.dropdown-btn').forEach(button => {
        button.addEventListener('click', () => {
            const dropdownContainer = button.nextElementSibling;
            const arrow = button.querySelector('.arrow');
            
            document.querySelectorAll('.dropdown-container').forEach(container => {
                if(container !== dropdownContainer) {
                    container.classList.remove('active');
                    if(container.previousElementSibling.querySelector('.arrow')) {
                        container.previousElementSibling.querySelector('.arrow').classList.remove('rotate');
                    }
                }
            });

            dropdownContainer.classList.toggle('active');
            arrow.classList.toggle('rotate');
        });
    });

    // 2. LIVE RUNNING CLOCK AND CALENDAR LOGIC
    function processLiveSystemClock() {
        const timeBox = document.getElementById('clock-display');
        const dateBox = document.getElementById('calendar-display');
        
        setInterval(() => {
            const current = new Date();
            let h = current.getHours();
            const m = String(current.getMinutes()).padStart(2, '0');
            const s = String(current.getSeconds()).padStart(2, '0');
            const period = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            h = h ? h : 12;
            
            if(timeBox) timeBox.innerHTML = `<i class="fa-regular fa-clock" style="color: #06b6d4;"></i> ${String(h).padStart(2, '0')}:${m}:${s} ${period}`;
            if(dateBox) dateBox.innerText = current.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
        }, 1000);
    }

    // 3. PERSISTENT GLOBAL DARK THEME LOGIC
    const initialThemeState = localStorage.getItem('theme') ? localStorage.getItem('theme') : 'light';
    const themeCheckbox = document.getElementById('themeSwitchCheckbox');
    const labelUI = document.getElementById('sys-mode-label');

    if (initialThemeState === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        if(themeCheckbox) themeCheckbox.checked = true;
        if(labelUI) labelUI.innerHTML = '<i class="fa-solid fa-moon" style="color: #a5b4fc;"></i> Dark UI';
    }

    function toggleGlobalSystemTheme() {
        const check = document.getElementById('themeSwitchCheckbox');
        const label = document.getElementById('sys-mode-label');
        
        if (check.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            label.innerHTML = '<i class="fa-solid fa-moon" style="color: #a5b4fc;"></i> Dark UI';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            label.innerHTML = '<i class="fa-solid fa-sun" style="color: #f59e0b;"></i> Light UI';
        }
    }

    // 4. CONFIRMATION LOGOUT MODAL SYSTEM LOGIC
    const logoutBtnTrigger = document.getElementById('logoutSystemBtn');
    const customLogoutModal = document.getElementById('customLogoutModal');

    if (logoutBtnTrigger && customLogoutModal) {
        logoutBtnTrigger.addEventListener('click', () => {
            customLogoutModal.classList.add('show');
        });
    }

    function processSystemLogout(isConfirmed) {
        customLogoutModal.classList.remove('show');
        if (isConfirmed) {
            window.location.href = 'logout.php';
        }
    }

    window.addEventListener('click', (event) => {
        if (event.target === customLogoutModal) {
            customLogoutModal.classList.remove('show');
        }
    });

    processLiveSystemClock();
</script>