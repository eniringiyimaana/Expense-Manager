<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize variables with default values
$total_income = 0;
$total_expense = 0;
$balance = 0;
$current_month_income = 0;
$current_month_expense = 0;

// Totals with error handling
$income_result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='income'");
if ($income_result) {
    $total_income = $income_result->fetch_assoc()['total'] ?? 0;
}

$expense_result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='expense'");
if ($expense_result) {
    $total_expense = $expense_result->fetch_assoc()['total'] ?? 0;
}

$balance = $total_income - $total_expense;

// Recent transactions with error handling - FIXED
$recent_transactions = $conn->query("SELECT * FROM transactions WHERE user_id=$user_id ORDER BY date DESC, created_at DESC LIMIT 5");
if (!$recent_transactions) {
    $recent_transactions = false; // Set to false if query fails
}

// Monthly summary with error handling - FIXED
$month_income_result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='income' AND MONTH(date) = MONTH(CURRENT_DATE())");
if ($month_income_result) {
    $current_month_income = $month_income_result->fetch_assoc()['total'] ?? 0;
}

$month_expense_result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE user_id=$user_id AND type='expense' AND MONTH(date) = MONTH(CURRENT_DATE())");
if ($month_expense_result) {
    $current_month_expense = $month_expense_result->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Expense Budget System</title>
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

        .welcome-section {
            margin-bottom: 30px;
        }

        .welcome-section h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: #6b7280;
            font-size: 16px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
        }

        .stat-card.income {
            border-left-color: #10b981;
        }

        .stat-card.expense {
            border-left-color: #ef4444;
        }

        .stat-card.balance {
            border-left-color: #3b82f6;
        }

        .stat-card.monthly {
            border-left-color: #8b5cf6;
        }

        .stat-icon {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
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

        .neutral {
            color: #3b82f6;
        }

        /* Recent Transactions */
        .recent-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 20px;
            color: #1f2937;
        }

        .view-all {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table th {
            text-align: left;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
        }

        .transactions-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .transaction-amount.income {
            color: #10b981;
            font-weight: 600;
        }

        .transaction-amount.expense {
            color: #ef4444;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            padding: 40px;
            font-style: italic;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 20px;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        /* Database Error Message */
        .db-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .action-btn {
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
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="view_transactions.php">💳 Transactions</a>
            <a href="add_transaction.php">➕ Add Transaction</a>
            <a href="view_budgets.php">💰 Budgets</a>
            <a href="add_budget.php">🎯 Set Budget</a>
            <a href="report_summary.php">📈 Reports</a>
            <a href="profile.php">👤 Profile</a>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['name']; ?></div>
                <div class="user-email">Welcome back!</div>
            </div>
        </div>
        
        <a href="logout.php" class="logout-link">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo $_SESSION['name']; ?>! 👋</h1>
            <p>Here's your financial overview</p>
        </div>

        <!-- Database Error Check -->
        <?php if (!$income_result || !$expense_result): ?>
            <div class="db-error">
                <strong>Note:</strong> Some financial data may not be available. Make sure you have created some transactions first.
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card income">
                <div class="stat-icon">💰</div>
                <div class="stat-value positive">$<?php echo number_format($total_income, 2); ?></div>
                <div class="stat-label">Total Income</div>
            </div>

            <div class="stat-card expense">
                <div class="stat-icon">💸</div>
                <div class="stat-value negative">$<?php echo number_format($total_expense, 2); ?></div>
                <div class="stat-label">Total Expenses</div>
            </div>

            <div class="stat-card balance">
                <div class="stat-icon">⚖️</div>
                <div class="stat-value <?php echo $balance >= 0 ? 'positive' : 'negative'; ?>">
                    $<?php echo number_format($balance, 2); ?>
                </div>
                <div class="stat-label">Current Balance</div>
            </div>

            <div class="stat-card monthly">
                <div class="stat-icon">📅</div>
                <div class="stat-value neutral">$<?php echo number_format($current_month_income - $current_month_expense, 2); ?></div>
                <div class="stat-label">This Month's Balance</div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="recent-section">
            <div class="section-header">
                <h2>Recent Transactions</h2>
                <a href="view_transactions.php" class="view-all">View All →</a>
            </div>
            
            <?php if ($recent_transactions && $recent_transactions->num_rows > 0): ?>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($transaction = $recent_transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['description'] ?? 'N/A'); ?></td>
                            <td>
                                <span style="color: <?php echo ($transaction['type'] ?? '') == 'income' ? '#10b981' : '#ef4444'; ?>">
                                    <?php echo ucfirst($transaction['type'] ?? 'unknown'); ?>
                                </span>
                            </td>
                            <td class="transaction-amount <?php echo $transaction['type'] ?? ''; ?>">
                                $<?php echo number_format($transaction['amount'] ?? 0, 2); ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($transaction['date'] ?? 'now')); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    No transactions yet. <a href="add_transaction.php" style="color: #4f46e5;">Add your first transaction</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="add_transaction.php" class="action-btn">➕ Add Transaction</a>
            <a href="view_transactions.php" class="action-btn">📋 View All Transactions</a>
            <a href="add_budget.php" class="action-btn">🎯 Set Budget</a>
            <a href="report_summary.php" class="action-btn">📈 Generate Report</a>
        </div>
    </div>
</body>
</html>