<?php
require_once 'auth.php';
require_once 'conn.php';

requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HRMIS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">HRMIS</div>
        <div class="links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="actions/logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['role']); ?></h1>
            <p>Select an option below to proceed.</p>
        </div>

        <div class="dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
            
            <?php if ($_SESSION['role'] == 'HR Head' || $_SESSION['role'] == 'HR Staff'): ?>
                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                    <h3>Add Employee</h3>
                    <p>Register a new employee using CSC Form 212.</p>
                    <a href="views/add_employee.php" class="btn-primary">Go to Form</a>
                </div>

                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                    <h3>Employee List</h3>
                    <p>View, search, and manage employee records.</p>
                    <a href="views/employee_list.php" class="btn-primary">View List</a>
                </div>

                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                    <h3>Reports</h3>
                    <p>Generate departmental and contract reports.</p>
                    <a href="views/reports.php" class="btn-primary">View Reports</a>
                </div>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'Employee'): ?>
                <div class="card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center;">
                    <h3>My Profile</h3>
                    <p>View your personal employment record.</p>
                    <a href="views/view_employee.php?id=<?php echo $_SESSION['employee_id']; ?>" class="btn-primary">View Profile</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
