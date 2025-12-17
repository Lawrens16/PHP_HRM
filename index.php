<?php
session_start();
require_once 'conn.php';

// If already logged in, redirect to dashboard (we will create dashboard.php later)
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Security: Use Prepared Statements to prevent SQL Injection
        $sql = "SELECT id, password, role, employee_id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $stored_password, $role, $employee_id);
                $stmt->fetch();

                // Direct comparison (No Hashing)
                if ($stored_password === $password) {
                    // Password is correct, start session
                    $_SESSION['user_id'] = $id;
                    $_SESSION['role'] = $role;
                    $_SESSION['employee_id'] = $employee_id;

                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "Invalid username.";
            }
            $stmt->close();
        } else {
            $error_message = "Database error.";
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMIS Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h2>HRMIS Login</h2>
            <p>Human Resources Management Information System</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Login</button>
            </div>
        </form>
        
    </div>
</body>
</html>
