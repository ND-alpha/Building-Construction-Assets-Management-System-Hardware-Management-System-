<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['employee_id'])){
    header("Location: login.php");
    exit();
}

// Database Connection
$conn = new mysqli("localhost", "root", "", "hardware");
if($conn->connect_error){
    die("Database Connection Failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses - FixIt Hardware</title>
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Modern CSS Variables for Light & Dark Modes */
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;
            --text-title: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #ffffff;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            --table-header-bg: #f8fafc;
            --chart-grid: #e2e8f0;
        }

        body.dark-theme {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --text-title: #f8fafc;
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #0f172a;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            --table-header-bg: #172131;
            --chart-grid: #334155;
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
            color: var(--text-main); 
            display: flex; 
            min-height: 100vh; 
        }

        .main-content { 
            margin-left: 280px; 
            width: calc(100% - 280px); 
            padding: 40px; 
        }

        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }

        .page-header h1 { 
            font-size: 26px; 
            font-weight: 700; 
            color: var(--text-title); 
        }

        .btn-add-payment { 
            padding: 12px 20px; 
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); 
            color: white; 
            text-decoration: none; 
            border-radius: 12px; 
            font-size: 14px; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); 
            transition: 0.3s; 
            cursor: pointer; 
            border: none; 
        }

        .btn-add-payment:hover { 
            transform: translateY(-2px); 
        }

        /* Summary Cards - 4 Columns */
        .analytics-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px; 
            margin-bottom: 35px; 
        }

        @media (max-width: 1024px) { .analytics-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .analytics-grid { grid-template-columns: 1fr; } }

        .analytic-card { 
            background: var(--bg-card); 
            padding: 22px; 
            border-radius: 18px; 
            border: 1px solid var(--border-color); 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            box-shadow: var(--shadow); 
        }

        .card-icon { 
            width: 44px; 
            height: 44px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 20px; 
        }

        .total-exp-icon { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .month-exp-icon { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .today-exp-icon { background: rgba(6, 182, 212, 0.15); color: #06b6d4; }
        .category-icon { background: rgba(16, 185, 129, 0.15); color: #10b981; }

        .analytic-card span { 
            font-size: 11px; 
            font-weight: 700; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }

        .analytic-card h2 { 
            font-size: 22px; 
            font-weight: 800; 
            color: var(--text-title); 
            word-break: break-all; 
        }

        /* Chart Section Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 35px;
        }

        @media (max-width: 992px) { .charts-grid { grid-template-columns: 1fr; } }

        .chart-card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .chart-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Filter Section & Table */
        .table-card { 
            background: var(--bg-card); 
            border-radius: 20px; 
            box-shadow: var(--shadow); 
            border: 1px solid var(--border-color); 
            padding: 25px; 
        }

        .table-header-tools {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .table-header-tools h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-title);
        }

        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-form input, .filter-form select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-title);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
        }

        .btn-filter {
            padding: 8px 16px;
            background: var(--bg-hover);
            color: var(--text-title);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Custom Table */
        .table-wrapper { overflow-x: auto; }

        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            min-width: 900px; 
        }

        th { 
            background: var(--table-header-bg); 
            color: var(--text-muted); 
            padding: 16px 18px; 
            font-weight: 600; 
            font-size: 12px; 
            text-transform: uppercase; 
            border-bottom: 2px solid var(--border-color); 
            text-align: left; 
        }

        td { 
            padding: 16px 18px; 
            color: var(--text-main); 
            font-size: 14px; 
            border-bottom: 1px solid var(--border-color); 
            vertical-align: middle; 
        }

        tr:hover td { background-color: var(--bg-hover); }

        .category-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .btn-action-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            color: var(--text-main);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-action-icon:hover {
            background: var(--bg-hover);
        }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fa-solid fa-chart-pie text-primary me-2"></i> Expense Dashboard</h1>
                <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">වියදම් විශ්ලේෂණය සහ කළමනාකරණය</p>
            </div>
            <a href="add_payment.php" class="btn-add-payment">
                <i class="fa-solid fa-plus-circle"></i> Add New Payment / Expense
            </a>
        </div>

        <!-- 4 Stat Cards -->
        <div class="analytics-grid">
            <div class="analytic-card">
                <div class="card-icon total-exp-icon"><i class="fa-solid fa-wallet"></i></div>
                <span>Total Expenses</span>
                <h2 style="color: #ef4444;">Rs. 145,800.00</h2>
            </div>
            <div class="analytic-card">
                <div class="card-icon month-exp-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <span>This Month</span>
                <h2 style="color: #f59e0b;">Rs. 38,500.00</h2>
            </div>
            <div class="analytic-card">
                <div class="card-icon today-exp-icon"><i class="fa-solid fa-clock"></i></div>
                <span>Today's Expenses</span>
                <h2 style="color: #06b6d4;">Rs. 3,200.00</h2>
            </div>
            <div class="analytic-card">
                <div class="card-icon category-icon"><i class="fa-solid fa-tags"></i></div>
                <span>Total Categories</span>
                <h2 style="color: #10b981;">6</h2>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fa-solid fa-chart-column" style="color: #3b82f6;"></i> Monthly Expense Trend</h3>
                <canvas id="monthlyTrendChart" height="110"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fa-solid fa-chart-pie" style="color: #10b981;"></i> By Category</h3>
                <canvas id="categoryPieChart" height="200"></canvas>
            </div>
        </div>

        <!-- Expense Table Section -->
        <div class="table-card">
            <div class="table-header-tools">
                <h3><i class="fa-solid fa-list text-primary"></i> Recent Expenses List</h3>
                <form class="filter-form" method="GET">
                    <input type="date" name="from_date">
                    <input type="date" name="to_date">
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="utilities">Utilities</option>
                        <option value="supplies">Office Supplies</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="other">Other</option>
                    </select>
                    <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Filter</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Expense Title</th>
                            <th>Category</th>
                            <th>Payment Method</th>
                            <th>Date</th>
                            <th style="text-align: right;">Amount (LKR)</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Demo Data Rows (Database loop එකක් හරහා දත්ත ගන්න) -->
                        <tr>
                            <td>01</td>
                            <td><strong>Office Electricity Bill</strong></td>
                            <td><span class="category-badge">Utilities</span></td>
                            <td>Bank Transfer</td>
                            <td style="color: var(--text-muted);">2026-07-18</td>
                            <td style="text-align: right; font-weight: 700; color: #ef4444;">12,500.00</td>
                            <td style="text-align: center;">
                                <a href="#" class="btn-action-icon"><i class="fa-solid fa-pen-to-square" style="color: #3b82f6;"></i></a>
                                <a href="#" class="btn-action-icon" onclick="return confirm('Do you want to delete this?')"><i class="fa-solid fa-trash" style="color: #ef4444;"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>02</td>
                            <td><strong>Internet Connection Bill</strong></td>
                            <td><span class="category-badge">Utilities</span></td>
                            <td>Cash</td>
                            <td style="color: var(--text-muted);">2026-07-15</td>
                            <td style="text-align: right; font-weight: 700; color: #ef4444;">4,800.00</td>
                            <td style="text-align: center;">
                                <a href="#" class="btn-action-icon"><i class="fa-solid fa-pen-to-square" style="color: #3b82f6;"></i></a>
                                <a href="#" class="btn-action-icon" onclick="return confirm('Do you want to delete this?')"><i class="fa-solid fa-trash" style="color: #ef4444;"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>03</td>
                            <td><strong>Stationery & Paper Supplies</strong></td>
                            <td><span class="category-badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Supplies</span></td>
                            <td>Card</td>
                            <td style="color: var(--text-muted);">2026-07-10</td>
                            <td style="text-align: right; font-weight: 700; color: #ef4444;">3,200.00</td>
                            <td style="text-align: center;">
                                <a href="#" class="btn-action-icon"><i class="fa-solid fa-pen-to-square" style="color: #3b82f6;"></i></a>
                                <a href="#" class="btn-action-icon" onclick="return confirm('Do you want to delete this?')"><i class="fa-solid fa-trash" style="color: #ef4444;"></i></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Chart Scripts & Theme Switcher -->
    <script>
        let barChart, pieChart;

        function getChartColors() {
            const isDark = document.body.classList.contains('dark-theme');
            return {
                text: isDark ? '#cbd5e1' : '#64748b',
                grid: isDark ? '#334155' : '#e2e8f0'
            };
        }

        function initCharts() {
            const colors = getChartColors();

            // Monthly Trend Chart
            const ctxBar = document.getElementById('monthlyTrendChart').getContext('2d');
            barChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Expenses (LKR)',
                        data: [25000, 18000, 30000, 22000, 28000, 42000, 38500],
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: colors.grid },
                            ticks: { color: colors.text }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: colors.text }
                        }
                    }
                }
            });

            // Category Doughnut Chart
            const ctxPie = document.getElementById('categoryPieChart').getContext('2d');
            pieChart = new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: ['Utilities', 'Supplies', 'Maintenance', 'Other'],
                    datasets: [{
                        data: [45, 20, 25, 10],
                        backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444', '#10b981'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { 
                        legend: { 
                            position: 'bottom',
                            labels: { color: colors.text, padding: 15 } 
                        } 
                    }
                }
            });
        }

        // Light / Dark Theme Auto Switch
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
            }

            initCharts();

            // Theme Toggle connector
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
                    
                    // Chart colors update on theme toggle
                    if (barChart && pieChart) {
                        barChart.destroy();
                        pieChart.destroy();
                        initCharts();
                    }
                });
            }
        });
    </script>
</body>
</html>