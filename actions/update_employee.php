<?php
require_once "../auth.php";
require_once "../conn.php";
require_once "../classes/Employee.php";

// Ensure only HR can access this action
requireLogin();
requireRole(["HR Head", "HR Staff"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $employee = new Employee($conn);
    
    if ($employee->update($id, $_POST)) {
        header("Location: ../views/employee_list.php");
    } else {
        header("Location: ../views/edit_employee.php?id=" . $id);
    }
    exit();
} else {
    header("Location: ../views/employee_list.php");
    exit();
}
?>
