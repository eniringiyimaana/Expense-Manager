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

// Get transactions
$sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Calculate totals
$total_income = 0;
$total_expense = 0;
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
    if ($row['type'] == 'income') {
        $total_income += $row['amount'];
    } else {
        $total_expense += $row['amount'];
    }
}
$net_balance = $total_income - $total_expense;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Expense Budget System</title>
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

        .summary-value.positive {
            color: #10b981;
        }

        .summary-value.negative {
            color: #ef4444;
        }

        .summary-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        /* Transactions Table */
        .transactions-card {
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

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table th {
            background: #f8fafc;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .transactions-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }

        .transactions-table tr:hover {
            background: #f9fafb;
        }

        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .type-income {
            background: #d1fae5;
            color: #065f46;
        }

        .type-expense {
            background: #fee2e2;
            color: #991b1b;
        }

        .amount {
            font-weight: 600;
        }

        .amount.income {
            color: #10b981;
        }

        .amount.expense {
            color: #ef4444;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #dbeafe;
        }

        .edit-btn:hover {
            background: #1e40af;
            color: white;
        }

        .delete-btn {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fef2f2;
        }

        .delete-btn:hover {
            background: #dc2626;
            color: white;
        }

        .no-transactions {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .no-transactions a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .no-transactions a:hover {
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
            
            .transactions-table {
                font-size: 12px;
            }
            
            .transactions-table th,
            .transactions-table td {
                padding: 10px 8px;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
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
            <a href="view_transactions.php" class="active">💳 Transactions</a>
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
            <h1>Your Transactions</h1>
            <p>View and manage all your income and expenses</p>
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

        <!-- Transactions Table -->
        <div class="transactions-card">
            <div class="card-header">
                <h2>All Transactions</h2>
                <a href="add_transaction.php" class="add-btn">➕ Add New Transaction</a>
            </div>

            <div class="table-container">
                <?php if (count($transactions) > 0): ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $row): ?>
                            <tr>
                                <td>
                                    <span class="type-badge type-<?php echo $row['type']; ?>">
                                        <?php echo ucfirst($row['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td class="amount <?php echo $row['type']; ?>">
                                    $<?php echo number_format($row['amount'], 2); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['description'] ?: '-'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="edit_transaction.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">Edit</a>
                                        <a href="delete_transaction.php?id=<?php echo $row['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-transactions">
                        <p style="font-size: 18px; margin-bottom: 10px;">No transactions yet</p>
                        <p>Start tracking your finances by adding your first transaction!</p>
                        <a href="add_transaction.php" style="display: inline-block; margin-top: 15px;">➕ Add Your First Transaction</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Confirm delete with custom message
        document.addEventListener('DOMContentLoaded', function() {
            const deleteLinks = document.querySelectorAll('.delete-btn');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>