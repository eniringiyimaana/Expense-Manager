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
    $category = $_POST['category'];
    $amount_limit = $_POST['amount_limit'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $sql = "INSERT INTO budgets (user_id, category, amount_limit, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $user_id, $category, $amount_limit, $start_date, $end_date);

    if ($stmt->execute()) {
        $success = "✅ Budget added successfully!";
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
    <title>Add Budget - Expense Budget System</title>
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

        .date-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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

        .budget-tips {
            background: #f0f4ff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .tips-header {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tips-list {
            list-style: none;
            color: #4b5563;
            font-size: 14px;
        }

        .tips-list li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }

        .tips-list li:before {
            content: "💡";
            position: absolute;
            left: 0;
        }

        .category-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .category-tag {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 4px 12px;
            font-size: 12px;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .category-tag:hover {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
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
            
            .date-group {
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
            <a href="add_budget.php" class="active">🎯 Set Budget</a>
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
            <h1>Set New Budget</h1>
            <p>Create spending limits for your expense categories</p>
        </div>

        <div class="form-card">
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="message success">
                    <?php echo $success; ?> 
                    <a href="view_budgets.php" style="color: #065f46; text-decoration: underline; margin-left: 10px;">View Budgets</a>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Budget Tips -->
            <div class="budget-tips">
                <div class="tips-header">
                    <span>💡 Budgeting Tips</span>
                </div>
                <ul class="tips-list">
                    <li>Set realistic limits based on your past spending</li>
                    <li>Consider your income and fixed expenses first</li>
                    <li>Review and adjust budgets monthly</li>
                    <li>Track your progress regularly</li>
                </ul>
            </div>

            <!-- Your Original Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="category">Budget Category</label>
                    <input type="text" id="category" name="category" class="form-control" placeholder="e.g., Food, Entertainment, Transportation" required>
                    <div class="category-suggestions">
                        <span class="category-tag" onclick="document.getElementById('category').value = 'Food'">Food</span>
                        <span class="category-tag" onclick="document.getElementById('category').value = 'Entertainment'">Entertainment</span>
                        <span class="category-tag" onclick="document.getElementById('category').value = 'Transportation'">Transportation</span>
                        <span class="category-tag" onclick="document.getElementById('category').value = 'Shopping'">Shopping</span>
                        <span class="category-tag" onclick="document.getElementById('category').value = 'Utilities'">Utilities</span>
                        <span class="category-tag" onclick="document.getElementById('category').value = 'Healthcare'">Healthcare</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="amount_limit">Budget Limit ($)</label>
                    <input type="number" id="amount_limit" name="amount_limit" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label>Budget Period</label>
                    <div class="date-group">
                        <div>
                            <label for="start_date" style="font-size: 12px;">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div>
                            <label for="end_date" style="font-size: 12px;">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="add" class="btn">💾 Save Budget</button>
                    <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set default dates
        document.getElementById('start_date').valueAsDate = new Date();
        
        const nextMonth = new Date();
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        document.getElementById('end_date').valueAsDate = nextMonth;

        // Validate date range
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(this.value);
            
            if (endDate <= startDate) {
                alert('End date must be after start date');
                this.value = '';
            }
        });

        // Quick date presets
        function setDateRange(days) {
            const start = new Date();
            const end = new Date();
            end.setDate(end.getDate() + days);
            
            document.getElementById('start_date').valueAsDate = start;
            document.getElementById('end_date').valueAsDate = end;
        }

        // Add quick preset buttons (optional enhancement)
        const datePresets = document.createElement('div');
        datePresets.innerHTML = `
            <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                <span style="font-size: 12px; color: #6b7280;">Quick presets:</span>
                <button type="button" onclick="setDateRange(7)" style="padding: 4px 8px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 12px; cursor: pointer;">1 Week</button>
                <button type="button" onclick="setDateRange(30)" style="padding: 4px 8px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 12px; cursor: pointer;">1 Month</button>
                <button type="button" onclick="setDateRange(90)" style="padding: 4px 8px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 12px; cursor: pointer;">3 Months</button>
            </div>
        `;
        document.querySelector('.date-group').appendChild(datePresets);
    </script>
</body>
</html>