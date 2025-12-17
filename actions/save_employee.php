<?php
require_once "../auth.php";
require_once "../conn.php";
require_once "../classes/Employee.php";

// Ensure only HR can access this action
requireLogin();
requireRole(["HR Head", "HR Staff"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee = new Employee($conn);
    
    // Pass the entire $_POST array to the create method
    // The Employee class handles sanitization and defaults internally or via prepared statements
    if ($employee->create($_POST)) {
        header("Location: ../views/employee_list.php");
    } else {
        // Error is already set in session by the class
        header("Location: ../views/add_employee.php");
    }
    exit();
} else {
    header("Location: ../views/add_employee.php");
    exit();
}
?>
