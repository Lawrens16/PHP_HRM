<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}


function requireRole($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // Simple text response for security; could be a redirect to an error page
        die("<h1>Access Denied</h1><p>You do not have permission to access this resource.</p>");
    }
}


function checkOwnership($target_employee_id) {
    if ($_SESSION['role'] == 'Employee' && $_SESSION['employee_id'] != $target_employee_id) {
        die("<h1>Access Denied</h1><p>You can only view your own record.</p>");
    }
}
?>
