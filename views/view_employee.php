<?php
require_once '../auth.php';
require_once '../conn.php';

requireLogin();

if (!isset($_GET['id'])) {
    header("Location: employee_list.php");
    exit();
}

$id = $_GET['id'];

// Security Check: Employees can only view their own record
checkOwnership($id);

// Fetch Employee Details
// Join with unitassignments, departments, service_records, job_positions, contract_types
$sql = "SELECT e.*, d.dept_name, p.job_category, c.contract_classification, sr.appointment_start_date, sr.appointment_end_date 
        FROM employees e 
        LEFT JOIN employees_unitassignments eu ON e.idemployees = eu.employees_idemployees
        LEFT JOIN departments d ON eu.departments_iddepartments = d.iddepartments
        LEFT JOIN service_records sr ON e.idemployees = sr.employees_idemployees
        LEFT JOIN job_positions p ON sr.job_positions_idjob_positions = p.idjob_positions
        LEFT JOIN contract_types c ON sr.contract_types_idcontract_types = c.idcontract_types
        WHERE e.idemployees = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Employee not found.");
}

$employee = $result->fetch_assoc();

// Fetch Education
// Join with institutions
$sql_edu = "SELECT ee.*, i.institution_name 
            FROM employees_education ee
            JOIN institutions i ON ee.institutions_idinstitutions = i.idinstitutions
            WHERE ee.employees_idemployees = ?";
$stmt_edu = $conn->prepare($sql_edu);
$stmt_edu->bind_param("i", $id);
$stmt_edu->execute();
$education = $stmt_edu->get_result();

// Fetch Relatives
// Join with relatives table
// Updated: Select relationship from employees_relatives (er)
$sql_rel = "SELECT er.*, r.first_name, r.last_name, er.relationship, r.telephone 
            FROM employees_relatives er
            JOIN relatives r ON er.Relatives_idrelatives = r.idrelatives
            WHERE er.employees_idemployees = ?";
$stmt_rel = $conn->prepare($sql_rel);
$stmt_rel->bind_param("i", $id);
$stmt_rel->execute();
$relatives = $stmt_rel->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Employee - HRMIS</title>
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
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        <h2>Employee Details</h2>
        
        <div class="form-section">
            <h3>Personal Information</h3>
            <div class="form-grid">
                <div class="form-group"><label>Name:</label> <?php echo $employee['first_name'] . ' ' . $employee['middle_name'] . ' ' . $employee['last_name']; ?></div>
                <div class="form-group"><label>Address:</label> <?php echo $employee['res_spec_address']; ?></div>
                <div class="form-group"><label>Contact:</label> <?php echo $employee['contactno']; ?></div>
                <div class="form-group"><label>Email:</label> <?php echo $employee['email']; ?></div>
                <div class="form-group"><label>Department:</label> <?php echo $employee['dept_name'] ?? 'N/A'; ?></div>
                <div class="form-group"><label>Position:</label> <?php echo $employee['job_category'] ?? 'N/A'; ?></div>
                <div class="form-group"><label>Contract:</label> <?php echo $employee['contract_classification'] ?? 'N/A'; ?></div>
                <div class="form-group"><label>Date Hired:</label> <?php echo $employee['appointment_start_date'] ?? 'N/A'; ?></div>
                <div class="form-group"><label>End Date:</label> <?php echo $employee['appointment_end_date'] ?? 'N/A'; ?></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Education Background</h3>
            <table>
                <thead>
                    <tr>
                        <th>School Name</th>
                        <th>Degree</th>
                        <th>Year Graduated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $education->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['institution_name']; ?></td>
                            <td><?php echo $row['Education_degree']; ?></td>
                            <td><?php echo $row['year_graduated']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="form-section">
            <h3>Family Background</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Relationship</th>
                        <th>Contact Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $relatives->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                            <td><?php echo $row['relationship']; ?></td>
                            <td><?php echo $row['telephone']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px;">
            <?php if ($_SESSION['role'] != 'Employee'): ?>
                <a href="employee_list.php" class="btn-secondary">Back to List</a>
            <?php else: ?>
                <a href="../dashboard.php" class="btn-secondary">Back to Dashboard</a>
            <?php endif; ?>
            
            <a href="edit_employee.php?id=<?php echo $id; ?>" class="btn-primary">Edit Profile</a>
        </div>
    </div>
</body>
</html>
