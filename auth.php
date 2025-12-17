<?php
session_start();

/**
 * Checks if the user is logged in.
 * If not, redirects to the login page.
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Checks if the logged-in user has one of the allowed roles.
 * If not, terminates execution with an Access Denied message.
 * 
 * @param array $allowed_roles Array of strings representing allowed roles (e.g., ['HR Head', 'HR Staff'])
 */
function requireRole($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // Simple text response for security; could be a redirect to an error page
        die("<h1>Access Denied</h1><p>You do not have permission to access this resource.</p>");
    }
}

/**
 * Checks if the logged-in user is accessing their own record.
 * HR Head and HR Staff can access anyone's record (depending on specific logic), 
 * but Employees can only access their own.
 * 
 * @param int $target_employee_id The ID of the employee record being accessed
 */
function checkOwnership($target_employee_id) {
    if ($_SESSION['role'] == 'Employee' && $_SESSION['employee_id'] != $target_employee_id) {
        die("<h1>Access Denied</h1><p>You can only view your own record.</p>");
    }
}
?>
