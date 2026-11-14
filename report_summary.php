<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info for sidebar
$user_sql = "SELECT * FROM users WHERE id=?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Get total income and expenses per category
$category_data = [];
$sql = "SELECT category, type, SUM(amount) as total 
        FROM transactions 
        WHERE user_id = ? 
        GROUP BY category, type";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

foreach ($result as $row) {
    $category = $row['category'];
    $type = $row['type'];
    $total = $row['total'];

    $category_data[$category][$type] = $total;
}

// Get monthly data for line chart
$monthly_data = [];
$sql_monthly = "SELECT 
    DATE_FORMAT(date, '%Y-%m') as month,
    type,
    SUM(amount) as total
    FROM transactions 
    WHERE user_id = ? 
    GROUP BY DATE_FORMAT(date, '%Y-%m'), type
    ORDER BY month";
$stmt_monthly = $conn->prepare($sql_monthly);
$stmt_monthly->bind_param("i", $user_id);
$stmt_monthly->execute();
$monthly_result = $stmt_monthly->get_result();

while ($row = $monthly_result->fetch_assoc()) {
    $month = $row['month'];
    $type = $row['type'];
    $total = $row['total'];
    
    $monthly_data[$month][$type] = $total;
}

// Get overall totals
$total_income = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='income'")->fetch_assoc()['total'] ?? 0;
$total_expense = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='expense'")->fetch_assoc()['total'] ?? 0;
$net_balance = $total_income - $total_expense;

// Get top expense categories
$top_expenses = [];
$sql_top = "SELECT category, SUM(amount) as total 
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' 
            GROUP BY category 
            ORDER BY total DESC 
            LIMIT 5";
$stmt_top = $conn->prepare($sql_top);
$stmt_top->bind_param("i", $user_id);
$stmt_top->execute();
$top_result = $stmt_top->get_result();

while ($row = $top_result->fetch_assoc()) {
    $top_expenses[] = $row;
}

// Prepare data for Chart.js
$labels = json_encode(array_keys($category_data));
$income_data = json_encode(array_map(fn($c) => $c['income'] ?? 0, $category_data));
$expense_data = json_encode(array_map(fn($c) => $c['expense'] ?? 0, $category_data));

// Monthly data for line chart
$month_labels = json_encode(array_keys($monthly_data));
$monthly_income = json_encode(array_map(fn($m) => $m['income'] ?? 0, $monthly_data));
$monthly_expense = json_encode(array_map(fn($m) => $m['expense'] ?? 0, $monthly_data));

// Top expenses for pie chart
$top_labels = json_encode(array_column($top_expenses, 'category'));
$top_amounts = json_encode(array_column($top_expenses, 'total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Expense Budget System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 20px;
            font-weight: 600;
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: rgba(255, 255, 255, 0.5);
        }

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: white;
        }

        .user-info {
            padding: 20px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
        }

        .user-email {
            font-size: 12px;
            opacity: 0.8;
        }

        .logout-link {
            display: block;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
            border-left: 3px solid transparent;
        }

        .logout-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #6b7280;
            font-size: 16px;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .summary-card.income {
            border-top: 4px solid #10b981;
        }

        .summary-card.expense {
            border-top: 4px solid #ef4444;
        }

        .summary-card.balance {
            border-top: 4px solid #3b82f6;
        }

        .summary-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .summary-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .positive {
            color: #10b981;
        }

        .negative {
            color: #ef4444;
        }

        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
        }

        .chart-header {
            margin-bottom: 20px;
        }

        .chart-header h3 {
            font-size: 18px;
            color: #1f2937;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Top Expenses */
        .top-expenses {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 30px;
        }

        .expenses-list {
            list-style: none;
        }

        .expense-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .expense-item:last-child {
            border-bottom: none;
        }

        .expense-category {
            font-weight: 500;
            color: #374151;
        }

        .expense-amount {
            font-weight: 600;
            color: #ef4444;
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
        }

        .btn-secondary:hover {
            background: #4b5563;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Expense Budget System</div>
        
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="view_transactions.php">💳 Transactions</a>
            <a href="add_transaction.php">➕ Add Transaction</a>
            <a href="view_budgets.php">💰 Budgets</a>
            <a href="add_budget.php">🎯 Set Budget</a>
            <a href="check_budget.php">📊 Budget Status</a>
            <a href="report_summary.php" class="active">📈 Reports</a>
            <a href="profile.php">👤 Profile</a>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $user['name']; ?></div>
                <div class="user-email"><?php echo $user['email']; ?></div>
            </div>
        </div>
        
        <a href="logout.php" class="logout-link">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Financial Reports</h1>
            <p>Comprehensive analysis of your income and expenses</p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card income">
                <div class="summary-value positive">$<?php echo number_format($total_income, 2); ?></div>
                <div class="summary-label">Total Income</div>
            </div>
            <div class="summary-card expense">
                <div class="summary-value negative">$<?php echo number_format($total_expense, 2); ?></div>
                <div class="summary-label">Total Expenses</div>
            </div>
            <div class="summary-card balance">
                <div class="summary-value <?php echo $net_balance >= 0 ? 'positive' : 'negative'; ?>">
                    $<?php echo number_format($net_balance, 2); ?>
                </div>
                <div class="summary-label">Net Balance</div>
            </div>
        </div>

        <!-- Charts Container -->
        <div class="charts-container">
            <!-- Category Comparison Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Income vs Expenses by Category</h3>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Monthly Trend Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Monthly Income & Expense Trend</h3>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <!-- Top Expenses Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Top Expense Categories</h3>
                </div>
                <div class="chart-container">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Expenses List -->
        <div class="top-expenses">
            <div class="chart-header">
                <h3>Top 5 Expense Categories</h3>
            </div>
            <?php if (count($top_expenses) > 0): ?>
                <ul class="expenses-list">
                    <?php foreach ($top_expenses as $expense): ?>
                    <li class="expense-item">
                        <span class="expense-category"><?php echo htmlspecialchars($expense['category']); ?></span>
                        <span class="expense-amount">$<?php echo number_format($expense['total'], 2); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-data">
                    No expense data available for analysis.
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="dashboard.php" class="btn">📊 Back to Dashboard</a>
            <a href="view_transactions.php" class="btn btn-secondary">💳 View All Transactions</a>
        </div>
    </div>

    <script>
        // Category Comparison Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $labels; ?>,
                datasets: [
                    {
                        label: 'Income',
                        data: <?php echo $income_data; ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expense',
                        data: <?php echo $expense_data; ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Monthly Trend Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo $month_labels; ?>,
                datasets: [
                    {
                        label: 'Income',
                        data: <?php echo $monthly_income; ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Expense',
                        data: <?php echo $monthly_expense; ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Top Expenses Pie Chart
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $top_labels; ?>,
                datasets: [{
                    data: <?php echo $top_amounts; ?>,
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(249, 115, 22, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(234, 179, 8, 0.7)',
                        'rgba(132, 204, 22, 0.7)'
                    ],
                    borderColor: [
                        'rgba(239, 68, 68, 1)',
                        'rgba(249, 115, 22, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(234, 179, 8, 1)',
                        'rgba(132, 204, 22, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>