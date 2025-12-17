<?php
require_once '../auth.php';
require_once '../conn.php';

requireLogin();
requireRole(['HR Head']); // Only HR Head can delete

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $conn->begin_transaction();

    try {
        // 1. Delete Education Links
        $stmt = $conn->prepare("DELETE FROM employees_education WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 2. Delete Relative Links
        $stmt = $conn->prepare("DELETE FROM employees_relatives WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // 3. Delete Unit Assignments
        $stmt = $conn->prepare("DELETE FROM employees_unitassignments WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 4. Delete Service Records
        $stmt = $conn->prepare("DELETE FROM service_records WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 5. Delete External Involvements (Fix for Foreign Key Constraint)
        // Check if table exists or just try delete
        $stmt = $conn->prepare("DELETE FROM employees_ext_involvements WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 5.1 Delete Professional Eligibility (Fix for Foreign Key Constraint)
        $stmt = $conn->prepare("DELETE FROM employees_prof_eligibility WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 5.2 Delete Trainings (Fix for Foreign Key Constraint)
        $stmt = $conn->prepare("DELETE FROM employees_has_trainings WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 5.3 Delete Skills (Fix for Foreign Key Constraint)
        $stmt = $conn->prepare("DELETE FROM skills_has_employees WHERE employees_idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 6. Delete Employee
        $stmt = $conn->prepare("DELETE FROM employees WHERE idemployees = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: ../views/employee_list.php?msg=Employee deleted successfully");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error deleting record: " . $e->getMessage());
    }
}
?>
