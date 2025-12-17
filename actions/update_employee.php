<?php
require_once "../auth.php";
require_once "../conn.php";
require_once "../classes/Employee.php";

// Ensure only HR can access this action
requireLogin();
requireRole(["HR Head", "HR Staff", "Employee"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];

    // Access Control: Employees can only update their own profile
    if ($_SESSION['role'] == 'Employee') {
        if ($_SESSION['employee_id'] != $id) {
            die("Access Denied: You can only update your own profile.");
        }
        
        // Security: Prevent Employees from modifying sensitive fields
        // Unset these fields from $_POST so they are not updated (or use existing values if logic requires)
        // However, Employee::update expects all fields or uses defaults. 
        // Better approach: Fetch current values for sensitive fields and overwrite $_POST
        
        $current_emp_sql = "SELECT 
            sr.monthly_salary, 
            sr.job_positions_idjob_positions, 
            sr.contract_types_idcontract_types, 
            sr.appointment_start_date, 
            sr.appointment_end_date,
            eu.departments_iddepartments
            FROM employees e
            LEFT JOIN service_records sr ON e.idemployees = sr.employees_idemployees
            LEFT JOIN employees_unitassignments eu ON e.idemployees = eu.employees_idemployees
            WHERE e.idemployees = ?";
            
        $stmt = $conn->prepare($current_emp_sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $curr = $stmt->get_result()->fetch_assoc();
        
        // Force sensitive fields to remain as they are in the database
        $_POST['monthly_salary'] = $curr['monthly_salary'];
        $_POST['position_id'] = $curr['job_positions_idjob_positions'];
        $_POST['contract_type_id'] = $curr['contract_types_idcontract_types'];
        $_POST['date_hired'] = $curr['appointment_start_date'];
        $_POST['appointment_end_date'] = $curr['appointment_end_date'];
        $_POST['department_id'] = $curr['departments_iddepartments'];
    }

    $employee = new Employee($conn);
    
    if ($employee->update($id, $_POST)) {
        if ($_SESSION['role'] == 'Employee') {
            header("Location: ../views/view_employee.php?id=" . $id);
        } else {
            header("Location: ../views/employee_list.php");
        }
    } else {
        header("Location: ../views/edit_employee.php?id=" . $id);
    }
    exit();
} else {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'Employee') {
        header("Location: ../dashboard.php");
    } else {
        header("Location: ../views/employee_list.php");
    }
    exit();
}
?>
