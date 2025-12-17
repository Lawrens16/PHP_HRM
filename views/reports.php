<?php
require_once '../auth.php';
require_once '../conn.php';

requireLogin();
requireRole(['HR Head', 'HR Staff']);

// Report A: Count of employees grouped by department
// Using employees_unitassignments
$sql_dept = "SELECT d.dept_name, COUNT(eu.employees_idemployees) as emp_count 
             FROM departments d 
             LEFT JOIN employees_unitassignments eu ON d.iddepartments = eu.departments_iddepartments 
             GROUP BY d.iddepartments, d.dept_name";
$result_dept = $conn->query($sql_dept);

// Report B: List of employees grouped by contract type
// Using service_records
$sql_contract = "SELECT c.contract_classification, e.first_name, e.last_name 
                 FROM contract_types c 
                 JOIN service_records sr ON c.idcontract_types = sr.contract_types_idcontract_types
                 JOIN employees e ON sr.employees_idemployees = e.idemployees
                 ORDER BY c.contract_classification, e.last_name";
$result_contract = $conn->query($sql_contract);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - HRMIS</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">HRMIS</div>
        <div class="links">
            <a href="../dashboard.php">Dashboard</a>
            <a href="../actions/logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2>HR Reports</h2>
        
        <div class="form-section">
            <h3>Employees by Department</h3>
            <table>
                <thead>
                    <tr>
                        <th>Department Name</th>
                        <th>Employee Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_dept && $result_dept->num_rows > 0): ?>
                        <?php while($row = $result_dept->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['dept_name']; ?></td>
                                <td><?php echo $row['emp_count']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-section">
            <h3>Employees by Contract Type</h3>
            <table>
                <thead>
                    <tr>
                        <th>Contract Type</th>
                        <th>Employee Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_contract && $result_contract->num_rows > 0): ?>
                        <?php 
                        $current_contract = "";
                        while($row = $result_contract->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>
                                    <?php 
                                    if ($current_contract != $row['contract_classification']) {
                                        echo "<strong>" . $row['contract_classification'] . "</strong>";
                                        $current_contract = $row['contract_classification'];
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
