<?php
require_once "../auth.php";
require_once "../conn.php";
require_once "../classes/Employee.php";

// Ensure only HR can access this action
requireLogin();
requireRole(["HR Head", "HR Staff"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee = new Employee($conn);
    

    if ($employee->create($_POST)) {
        header("Location: ../views/employee_list.php");
    } else {
        header("Location: ../views/add_employee.php");
    }
    exit();
} else {
    header("Location: ../views/add_employee.php");
    exit();
}
?>
