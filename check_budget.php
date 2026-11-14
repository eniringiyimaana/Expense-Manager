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
$sql = "SELECT * FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$budgets = [];
$total_budgets = 0;
$exceeded_budgets = 0;
$warning_budgets = 0;
$good_budgets = 0;

// Array to track notifications that were created
$notifications_created = [];

while ($budget = $result->fetch_assoc()) {
    $category = $budget['category'];
    $limit = $budget['amount_limit'];
    $start = $budget['start_date'];
    $end = $budget['end_date'];

    $sql_expense = "SELECT SUM(amount) AS total FROM transactions 
                    WHERE user_id=? AND type='expense' AND category=? 
                    AND date BETWEEN ? AND ?";
    $stmt_exp = $conn->prepare($sql_expense);
    $stmt_exp->bind_param("isss", $user_id, $category, $start, $end);
    $stmt_exp->execute();
    $spent = $stmt_exp->get_result()->fetch_assoc()['total'] ?? 0;

    $percentage = ($limit > 0) ? ($spent / $limit) * 100 : 0;
    $remaining = $limit - $spent;
    
    if ($percentage >= 100) {
        $status = 'exceeded';
        $exceeded_budgets++;
        $message = "🚨 Budget Exceeded: You have exceeded your $category budget by $" . number_format(abs($remaining), 2) . "!";
    } elseif ($percentage >= 80) {
        $status = 'warning';
        $warning_budgets++;
        $message = "⚠️ Budget Alert: You've used " . number_format($percentage, 1) . "% of your $category budget. Only $" . number_format($remaining, 2) . " remaining.";
    } else {
        $status = 'good';
        $good_budgets++;
        $message = null;
    }

    // Create notification if budget is exceeded or near limit
    if ($message) {
        // Check if a similar unread notification already exists from today
        $today = date('Y-m-d');
        $sql_check = "SELECT id FROM notifications WHERE user_id=? AND message LIKE ? AND DATE(created_at) = ? AND status='unread'";
        $stmt_check = $conn->prepare($sql_check);
        
        // Use partial matching for the message
        $search_pattern = "%$category%";
        $stmt_check->bind_param("iss", $user_id, $search_pattern, $today);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows == 0) {
            // Insert new notification
            $sql_insert = "INSERT INTO notifications (user_id, message, budget_id) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("isi", $user_id, $message, $budget['id']);
            
            if ($stmt_insert->execute()) {
                $notifications_created[] = [
                    'category' => $category,
                    'status' => $status,
                    'message' => $message
                ];
            }
        }
    }

    $budgets[] = [
        'category' => $category,
        'limit' => $limit,
        'spent' => $spent,
        'remaining' => $remaining,
        'percentage' => $percentage,
        'status' => $status,
        'start_date' => $start,
        'end_date' => $end
    ];

    $total_budgets++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Status - Expense Budget System</title>
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

        /* Notification Alert */
        .notification-alert {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .notification-alert h3 {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-list {
            list-style: none;
            margin-top: 10px;
        }

        .notification-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        /* Status Summary */
        .status-summary {
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

        .summary-card.good {
            border-top: 4px solid #10b981;
        }

        .summary-card.warning {
            border-top: 4px solid #f59e0b;
        }

        .summary-card.exceeded {
            border-top: 4px solid #ef4444;
        }

        .summary-card.total {
            border-top: 4px solid #4f46e5;
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

        /* Budget Status Cards */
        .budget-status-container {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .budget-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 25px;
            border-left: 6px solid;
        }

        .budget-card.good {
            border-left-color: #10b981;
        }

        .budget-card.warning {
            border-left-color: #f59e0b;
        }

        .budget-card.exceeded {
            border-left-color: #ef4444;
        }

        .budget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .budget-category {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .budget-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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

        .budget-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 16px;
        }

        .detail-value.positive {
            color: #10b981;
        }

        .detail-value.negative {
            color: #ef4444;
        }

        .progress-container {
            margin-top: 15px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .progress-label {
            color: #6b7280;
            font-size: 14px;
        }

        .progress-percentage {
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }

        .progress-bar {
            width: 100%;
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

        .budget-message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .message-good {
            background: #f0fdf4;
            color: #065f46;
            border: 1px solid #bbf7d0;
        }

        .message-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .message-exceeded {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .no-budgets {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .no-budgets a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .no-budgets a:hover {
            text-decoration: underline;
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
            
            .status-summary {
                grid-template-columns: 1fr;
            }
            
            .budget-details {
                grid-template-columns: 1fr;
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
            <a href="check_budget.php" class="active">📊 Budget Status</a>
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
            <h1>Budget Status Check</h1>
            <p>Monitor your budget performance and spending alerts</p>
        </div>

        <!-- Notification Alert -->
        <?php if (count($notifications_created) > 0): ?>
            <div class="notification-alert">
                <h3>🔔 New Budget Alerts Created</h3>
                <p>The following budget notifications have been added to your dashboard:</p>
                <ul class="notification-list">
                    <?php foreach ($notifications_created as $notification): ?>
                    <li class="notification-item">
                        <?php echo $notification['message']; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (count($budgets) > 0): ?>
            <!-- Status Summary -->
            <div class="status-summary">
                <div class="summary-card total">
                    <div class="summary-value"><?php echo $total_budgets; ?></div>
                    <div class="summary-label">Total Budgets</div>
                </div>
                <div class="summary-card good">
                    <div class="summary-value"><?php echo $good_budgets; ?></div>
                    <div class="summary-label">On Track</div>
                </div>
                <div class="summary-card warning">
                    <div class="summary-value"><?php echo $warning_budgets; ?></div>
                    <div class="summary-label">Near Limit</div>
                </div>
                <div class="summary-card exceeded">
                    <div class="summary-value"><?php echo $exceeded_budgets; ?></div>
                    <div class="summary-label">Exceeded</div>
                </div>
            </div>

            <!-- Budget Status Cards -->
            <div class="budget-status-container">
                <?php foreach ($budgets as $budget): ?>
                <div class="budget-card <?php echo $budget['status']; ?>">
                    <div class="budget-header">
                        <div class="budget-category"><?php echo htmlspecialchars($budget['category']); ?></div>
                        <div class="budget-status status-<?php echo $budget['status']; ?>">
                            <?php 
                            if ($budget['status'] == 'good') echo '✅ On Track';
                            elseif ($budget['status'] == 'warning') echo '⚠️ Near Limit';
                            else echo '❌ Exceeded';
                            ?>
                        </div>
                    </div>

                    <div class="budget-details">
                        <div class="detail-item">
                            <span class="detail-label">Budget Limit</span>
                            <span class="detail-value">$<?php echo number_format($budget['limit'], 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amount Spent</span>
                            <span class="detail-value">$<?php echo number_format($budget['spent'], 2); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Remaining</span>
                            <span class="detail-value <?php echo $budget['remaining'] >= 0 ? 'positive' : 'negative'; ?>">
                                $<?php echo number_format($budget['remaining'], 2); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Progress</span>
                            <span class="detail-value"><?php echo number_format($budget['percentage'], 1); ?>%</span>
                        </div>
                    </div>

                    <div class="progress-container">
                        <div class="progress-header">
                            <span class="progress-label">Spending Progress</span>
                            <span class="progress-percentage"><?php echo number_format($budget['percentage'], 1); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-<?php echo $budget['status']; ?>" 
                                 style="width: <?php echo min($budget['percentage'], 100); ?>%"></div>
                        </div>
                    </div>

                    <div class="budget-message message-<?php echo $budget['status']; ?>">
                        <?php if ($budget['status'] == 'exceeded'): ?>
                            ⚠️ You have exceeded your <?php echo $budget['category']; ?> budget by $<?php echo number_format(abs($budget['remaining']), 2); ?>!
                        <?php elseif ($budget['status'] == 'warning'): ?>
                            ⚠️ Warning: You've used <?php echo number_format($budget['percentage'], 1); ?>% of your <?php echo $budget['category']; ?> budget.
                        <?php else: ?>
                            ✅ You're within budget for <?php echo $budget['category']; ?>. $<?php echo number_format($budget['remaining'], 2); ?> remaining.
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-budgets">
                <p style="font-size: 18px; margin-bottom: 10px;">No budgets to monitor</p>
                <p>Create budgets to start tracking your spending limits and receive alerts.</p>
                <a href="add_budget.php" style="display: inline-block; margin-top: 15px;">🎯 Create Your First Budget</a>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="view_budgets.php" class="btn">📋 View All Budgets</a>
            <a href="add_budget.php" class="btn">🎯 Add New Budget</a>
            <a href="dashboard.php" class="btn btn-secondary">🏠 Back to Dashboard</a>
        </div>
    </div>
</body>
</html>