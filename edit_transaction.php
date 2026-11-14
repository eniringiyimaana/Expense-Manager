<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get user info for sidebar
$user_sql = "SELECT * FROM users WHERE id=?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Get transaction data
$sql = "SELECT * FROM transactions WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();

if (!$transaction) {
    $error = "❌ Transaction not found.";
}

if (isset($_POST['update'])) {
    $type = $_POST['type'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $sql = "UPDATE transactions SET type=?, category=?, amount=?, description=?, date=? WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssii", $type, $category, $amount, $description, $date, $id, $user_id);

    if ($stmt->execute()) {
        $success = "✅ Transaction updated successfully!";
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction - Expense Budget System</title>
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

        /* Form Styles */
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 40px;
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 14px 30px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 15px;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-secondary:hover {
            background: #4b5563;
            box-shadow: 0 5px 15px rgba(107, 114, 128, 0.4);
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .type-option {
            display: flex;
            gap: 15px;
        }

        .type-option label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
            justify-content: center;
        }

        .type-option input[type="radio"] {
            display: none;
        }

        .type-option label.income-option {
            border-color: <?php echo ($transaction['type'] ?? '') == 'income' ? '#10b981' : '#e5e7eb'; ?>;
            background-color: <?php echo ($transaction['type'] ?? '') == 'income' ? '#f0fdf4' : '#f9fafb'; ?>;
        }

        .type-option label.expense-option {
            border-color: <?php echo ($transaction['type'] ?? '') == 'expense' ? '#ef4444' : '#e5e7eb'; ?>;
            background-color: <?php echo ($transaction['type'] ?? '') == 'expense' ? '#fef2f2' : '#f9fafb'; ?>;
        }

        .type-option input[type="radio"]:checked + span {
            font-weight: 600;
        }

        .type-option label.income-option input[type="radio"]:checked + span {
            color: #10b981;
        }

        .type-option label.expense-option input[type="radio"]:checked + span {
            color: #ef4444;
        }

        /* Transaction Preview */
        .transaction-preview {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .preview-header {
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .preview-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            font-size: 14px;
        }

        .preview-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .preview-label {
            color: #6b7280;
            font-weight: 500;
        }

        .preview-value {
            color: #1f2937;
            font-weight: 600;
        }

        .preview-value.income {
            color: #10b981;
        }

        .preview-value.expense {
            color: #ef4444;
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
            
            .form-card {
                padding: 30px 20px;
            }
            
            .type-option {
                flex-direction: column;
            }
            
            .preview-details {
                grid-template-columns: 1fr;
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
        <div class="page-header">
            <h1>Edit Transaction</h1>
            <p>Update your transaction details</p>
        </div>

        <div class="form-card">
            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="message error">
                    <?php echo $error; ?>
                    <?php if (strpos($error, 'not found') !== false): ?>
                        <br><a href="view_transactions.php" style="color: #991b1b; text-decoration: underline;">Back to Transactions</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (isset($success)): ?>
                <div class="message success">
                    <?php echo $success; ?> 
                    <a href="view_transactions.php" style="color: #065f46; text-decoration: underline; margin-left: 10px;">View Transactions</a>
                </div>
            <?php endif; ?>

            <?php if ($transaction): ?>
                <!-- Transaction Preview -->
                <div class="transaction-preview">
                    <div class="preview-header">Current Transaction Details</div>
                    <div class="preview-details">
                        <div class="preview-item">
                            <span class="preview-label">Type</span>
                            <span class="preview-value <?php echo $transaction['type']; ?>">
                                <?php echo ucfirst($transaction['type']); ?>
                            </span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Category</span>
                            <span class="preview-value"><?php echo htmlspecialchars($transaction['category']); ?></span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Amount</span>
                            <span class="preview-value <?php echo $transaction['type']; ?>">
                                $<?php echo number_format($transaction['amount'], 2); ?>
                            </span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Date</span>
                            <span class="preview-value"><?php echo date('M j, Y', strtotime($transaction['date'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Your Original Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Transaction Type</label>
                        <div class="type-option">
                            <label class="income-option">
                                <input type="radio" name="type" value="income" <?php echo ($transaction['type'] == 'income') ? 'checked' : ''; ?> required> 
                                <span>💰 Income</span>
                            </label>
                            <label class="expense-option">
                                <input type="radio" name="type" value="expense" <?php echo ($transaction['type'] == 'expense') ? 'checked' : ''; ?> required>
                                <span>💸 Expense</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" class="form-control" value="<?php echo htmlspecialchars($transaction['category']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount ($)</label>
                        <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" value="<?php echo $transaction['amount']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Add a note about this transaction"><?php echo htmlspecialchars($transaction['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" class="form-control" value="<?php echo $transaction['date']; ?>" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update" class="btn">💾 Update Transaction</button>
                        <a href="view_transactions.php" class="btn btn-secondary">⬅ Back to Transactions</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Update radio button styles when selection changes
        document.addEventListener('DOMContentLoaded', function() {
            const typeRadios = document.querySelectorAll('input[name="type"]');
            const incomeLabel = document.querySelector('.income-option');
            const expenseLabel = document.querySelector('.expense-option');
            
            typeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'income') {
                        incomeLabel.style.borderColor = '#10b981';
                        incomeLabel.style.backgroundColor = '#f0fdf4';
                        expenseLabel.style.borderColor = '#e5e7eb';
                        expenseLabel.style.backgroundColor = '#f9fafb';
                    } else {
                        expenseLabel.style.borderColor = '#ef4444';
                        expenseLabel.style.backgroundColor = '#fef2f2';
                        incomeLabel.style.borderColor = '#e5e7eb';
                        incomeLabel.style.backgroundColor = '#f9fafb';
                    }
                });
            });
        });
    </script>
</body>
</html>