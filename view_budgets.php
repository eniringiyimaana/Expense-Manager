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

// Get budgets
$sql = "SELECT * FROM budgets WHERE user_id = ? ORDER BY start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$budgets = [];
$total_budgets = 0;
$active_budgets = 0;

while ($row = $result->fetch_assoc()) {
    // Calculate total expenses within this budget period
    $category = $row['category'];
    $start = $row['start_date'];
    $end = $row['end_date'];
    $budget_limit = $row['amount_limit'];

    $sql_expense = "SELECT SUM(amount) AS total FROM transactions 
                    WHERE user_id = ? AND type='expense' AND category = ? 
                    AND date BETWEEN ? AND ?";
    $stmt_exp = $conn->prepare($sql_expense);
    $stmt_exp->bind_param("isss", $user_id, $category, $start, $end);
    $stmt_exp->execute();
    $spent = $stmt_exp->get_result()->fetch_assoc()['total'] ?? 0;

    // Calculate percentage and status
    $percentage = $budget_limit > 0 ? ($spent / $budget_limit) * 100 : 0;
    $remaining = $budget_limit - $spent;
    
    if ($spent > $budget_limit) {
        $status = "exceeded";
        $status_text = "Exceeded";
    } elseif ($percentage >= 80) {
        $status = "warning";
        $status_text = "Near Limit";
    } else {
        $status = "good";
        $status_text = "Within Budget";
    }

    // Check if budget is active
    $today = date('Y-m-d');
    $is_active = ($today >= $start && $today <= $end);

    $budgets[] = [
        'id' => $row['id'],
        'category' => $row['category'],
        'budget_limit' => $budget_limit,
        'start_date' => $start,
        'end_date' => $end,
        'spent' => $spent,
        'remaining' => $remaining,
        'percentage' => $percentage,
        'status' => $status,
        'status_text' => $status_text,
        'is_active' => $is_active
    ];

    $total_budgets++;
    if ($is_active) $active_budgets++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Budgets - Expense Budget System</title>
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

        .summary-card.total {
            border-top: 4px solid #4f46e5;
        }

        .summary-card.active {
            border-top: 4px solid #10b981;
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

        /* Budgets Table */
        .budgets-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 20px;
            color: #1f2937;
        }

        .add-btn {
            padding: 10px 20px;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .table-container {
            overflow-x: auto;
        }

        .budgets-table {
            width: 100%;
            border-collapse: collapse;
        }

        .budgets-table th {
            background: #f8fafc;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .budgets-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }

        .budgets-table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-good {
            background: #d1fae5;
            color: #065f46;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-exceeded {
            background: #fee2e2;
            color: #991b1b;
        }

        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-good {
            background: #10b981;
        }

        .progress-warning {
            background: #f59e0b;
        }

        .progress-exceeded {
            background: #ef4444;
        }

        .amount {
            font-weight: 600;
        }

        .amount.positive {
            color: #10b981;
        }

        .amount.negative {
            color: #ef4444;
        }

        .active-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .active-true {
            background: #10b981;
        }

        .active-false {
            background: #6b7280;
        }

        .no-budgets {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .no-budgets a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .no-budgets a:hover {
            text-decoration: underline;
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
            
            .card-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .budgets-table {
                font-size: 12px;
            }
            
            .budgets-table th,
            .budgets-table td {
                padding: 10px 8px;
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
            <a href="view_budgets.php" class="active">💰 Budgets</a>
            <a href="add_budget.php">🎯 Set Budget</a>
            <a href="report_summary.php">📈 Reports</a>
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
            <h1>Your Budgets</h1>
            <p>Monitor your spending limits and track progress</p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card total">
                <div class="summary-value"><?php echo $total_budgets; ?></div>
                <div class="summary-label">Total Budgets</div>
            </div>
            <div class="summary-card active">
                <div class="summary-value"><?php echo $active_budgets; ?></div>
                <div class="summary-label">Active Budgets</div>
            </div>
        </div>

        <!-- Budgets Table -->
        <div class="budgets-card">
            <div class="card-header">
                <h2>Budget Overview</h2>
                <a href="add_budget.php" class="add-btn">➕ Add New Budget</a>
            </div>

            <div class="table-container">
                <?php if (count($budgets) > 0): ?>
                    <table class="budgets-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Budget Limit</th>
                                <th>Spent</th>
                                <th>Remaining</th>
                                <th>Progress</th>
                                <th>Period</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($budgets as $budget): ?>
                            <tr>
                                <td>
                                    <span class="active-indicator active-<?php echo $budget['is_active'] ? 'true' : 'false'; ?>"></span>
                                    <?php echo htmlspecialchars($budget['category']); ?>
                                </td>
                                <td class="amount">$<?php echo number_format($budget['budget_limit'], 2); ?></td>
                                <td class="amount">$<?php echo number_format($budget['spent'], 2); ?></td>
                                <td class="amount <?php echo $budget['remaining'] >= 0 ? 'positive' : 'negative'; ?>">
                                    $<?php echo number_format($budget['remaining'], 2); ?>
                                </td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill progress-<?php echo $budget['status']; ?>" 
                                             style="width: <?php echo min($budget['percentage'], 100); ?>%"></div>
                                    </div>
                                    <small style="color: #6b7280; font-size: 12px;">
                                        <?php echo number_format($budget['percentage'], 1); ?>%
                                    </small>
                                </td>
                                <td>
                                    <?php echo date('M j', strtotime($budget['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($budget['end_date'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $budget['status']; ?>">
                                        <?php echo $budget['status_text']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-budgets">
                        <p style="font-size: 18px; margin-bottom: 10px;">No budgets set yet</p>
                        <p>Start managing your expenses by creating your first budget!</p>
                        <a href="add_budget.php" style="display: inline-block; margin-top: 15px;">🎯 Create Your First Budget</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>