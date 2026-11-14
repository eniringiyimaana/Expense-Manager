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

if (isset($_POST['add'])) {
    $type = $_POST['type'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $sql = "INSERT INTO transactions (user_id, type, category, amount, description, date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issdss", $user_id, $type, $category, $amount, $description, $date);

    if ($stmt->execute()) {
        $success = "✅ Transaction added successfully!";
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
    <title>Add Transaction - Expense Budget System</title>
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
            text-decoration: none;
            display: inline-block;
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
        }

        .type-option input[type="radio"] {
            display: none;
        }

        .type-option input[type="radio"]:checked + span {
            color: #4f46e5;
            font-weight: 600;
        }

        .type-option label.income-option input[type="radio"]:checked + span {
            color: #10b981;
        }

        .type-option label.expense-option input[type="radio"]:checked + span {
            color: #ef4444;
        }

        .type-option input[type="radio"]:checked ~ label {
            border-color: #4f46e5;
            background-color: #f0f4ff;
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
            <a href="add_transaction.php" class="active">➕ Add Transaction</a>
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
            <h1>Add New Transaction</h1>
            <p>Record your income or expense</p>
        </div>

        <div class="form-card">
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="message success">
                    <?php echo $success; ?> 
                    <a href="view_transactions.php" style="color: #065f46; text-decoration: underline; margin-left: 10px;">View Transactions</a>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Your Original Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label>Transaction Type</label>
                    <div class="type-option">
                        <label class="income-option">
                            <input type="radio" name="type" value="income" required> 
                            <span>💰 Income</span>
                        </label>
                        <label class="expense-option">
                            <input type="radio" name="type" value="expense" required>
                            <span>💸 Expense</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" class="form-control" placeholder="e.g., Salary, Food, Rent" required>
                </div>

                <div class="form-group">
                    <label for="amount">Amount ($)</label>
                    <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Add a note about this transaction (optional)"></textarea>
                </div>

                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <button type="submit" name="add" class="btn">➕ Add Transaction</button>
                    <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set today's date as default
        document.getElementById('date').valueAsDate = new Date();

        // Add some common categories based on type selection
        const categoryInput = document.getElementById('category');
        const typeRadios = document.querySelectorAll('input[name="type"]');
        
        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'income') {
                    categoryInput.placeholder = 'e.g., Salary, Freelance, Investment';
                } else {
                    categoryInput.placeholder = 'e.g., Food, Rent, Transportation';
                }
            });
        });
    </script>
</body>
</html>