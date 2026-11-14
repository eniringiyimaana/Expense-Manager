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

$message = '';
$message_type = '';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // First, get transaction details for confirmation message
    $check_sql = "SELECT * FROM transactions WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $id, $user_id);
    $check_stmt->execute();
    $transaction = $check_stmt->get_result()->fetch_assoc();
    
    if ($transaction) {
        // Delete the transaction
        $sql = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            $message = "✅ Transaction deleted successfully!";
            $message_type = 'success';
        } else {
            $message = "❌ Error: " . $stmt->error;
            $message_type = 'error';
        }
    } else {
        $message = "❌ Transaction not found or you don't have permission to delete it.";
        $message_type = 'error';
    }
} else {
    $message = "❌ No transaction ID specified.";
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Transaction - Expense Budget System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 50px;
            max-width: 500px;
            text-align: center;
        }

        .message-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .message-title {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .message-text {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .message-success {
            color: #10b981;
        }

        .message-error {
            color: #ef4444;
        }

        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
        }

        .btn-secondary:hover {
            background: #4b5563;
            box-shadow: 0 5px 15px rgba(107, 114, 128, 0.4);
        }

        .transaction-details {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
        }

        .detail-value.income {
            color: #10b981;
        }

        .detail-value.expense {
            color: #ef4444;
        }

        /* Auto-redirect animation */
        .redirect-countdown {
            margin-top: 20px;
            padding: 10px;
            background: #f0f4ff;
            border-radius: 6px;
            font-size: 14px;
            color: #4f46e5;
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
            
            .message-card {
                padding: 30px 20px;
            }
            
            .btn {
                display: block;
                margin: 10px 0;
                width: 100%;
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
        <div class="message-card">
            <div class="message-icon">
                <?php if ($message_type === 'success'): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif; ?>
            </div>
            
            <h1 class="message-title <?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
                <?php echo $message_type === 'success' ? 'Transaction Deleted' : 'Error'; ?>
            </h1>
            
            <p class="message-text"><?php echo $message; ?></p>

            <?php if (isset($transaction) && $transaction): ?>
                <div class="transaction-details">
                    <div class="detail-item">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value <?php echo $transaction['type']; ?>">
                            <?php echo ucfirst($transaction['type']); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Category:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($transaction['category']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Amount:</span>
                        <span class="detail-value <?php echo $transaction['type']; ?>">
                            $<?php echo number_format($transaction['amount'], 2); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value"><?php echo date('M j, Y', strtotime($transaction['date'])); ?></span>
                    </div>
                    <?php if ($transaction['description']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Description:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($transaction['description']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="view_transactions.php" class="btn">📋 View All Transactions</a>
                <a href="dashboard.php" class="btn btn-secondary">📊 Back to Dashboard</a>
            </div>

            <?php if ($message_type === 'success'): ?>
                <div class="redirect-countdown" id="countdown">
                    Redirecting to transactions in <span id="countdown-number">5</span> seconds...
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message_type === 'success'): ?>
    <script>
        // Auto-redirect after 5 seconds
        let countdown = 5;
        const countdownElement = document.getElementById('countdown-number');
        const countdownContainer = document.getElementById('countdown');
        
        const countdownInterval = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = 'view_transactions.php';
            }
        }, 1000);

        // Allow user to cancel auto-redirect
        countdownContainer.addEventListener('click', function() {
            clearInterval(countdownInterval);
            this.innerHTML = 'Auto-redirect cancelled. <a href="view_transactions.php" style="color: #4f46e5;">Click here</a> to view transactions.';
        });
    </script>
    <?php endif; ?>
</body>
</html>