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
$sql = "SELECT e.*, d.dept_name, p.job_category, c.contract_classification, sr.appointment_start_date, sr.appointment_end_date, sr.monthly_salary 
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
                <div class="form-group"><label>First Name:</label> <?php echo htmlspecialchars($employee['first_name']); ?></div>
                <div class="form-group"><label>Middle Name:</label> <?php echo htmlspecialchars($employee['middle_name']); ?></div>
                <div class="form-group"><label>Last Name:</label> <?php echo htmlspecialchars($employee['last_name']); ?></div>
                <div class="form-group"><label>Name Extension:</label> <?php echo htmlspecialchars($employee['name_extension'] ?? 'N/A'); ?></div>
                
                <div class="form-group"><label>Contact Number:</label> <?php echo htmlspecialchars($employee['contactno']); ?></div>
                <div class="form-group"><label>Email Address:</label> <?php echo htmlspecialchars($employee['email']); ?></div>
                
                <div class="form-group"><label>Date of Birth:</label> <?php echo htmlspecialchars($employee['birthdate']); ?></div>
                <div class="form-group"><label>Place of Birth:</label> <?php echo htmlspecialchars($employee['birth_city'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Province of Birth:</label> <?php echo htmlspecialchars($employee['birth_province'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Country of Birth:</label> <?php echo htmlspecialchars($employee['birth_country'] ?? 'N/A'); ?></div>
                
                <div class="form-group"><label>Sex:</label> <?php echo htmlspecialchars($employee['sex']); ?></div>
                <div class="form-group"><label>Civil Status:</label> <?php echo htmlspecialchars($employee['civil_status']); ?></div>
                
                <div class="form-group"><label>Height (m):</label> <?php echo htmlspecialchars($employee['height_in_meter'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Weight (kg):</label> <?php echo htmlspecialchars($employee['weight_in_kg'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Blood Type:</label> <?php echo htmlspecialchars($employee['blood_type'] ?? 'N/A'); ?></div>
                
                <div class="form-group"><label>GSIS No.:</label> <?php echo htmlspecialchars($employee['gsis_no'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>SSS No.:</label> <?php echo htmlspecialchars($employee['sss_no'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>PhilHealth No.:</label> <?php echo htmlspecialchars($employee['philhealthno'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>TIN:</label> <?php echo htmlspecialchars($employee['tin'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Citizenship:</label> <?php echo htmlspecialchars($employee['citizenship'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Addresses</h3>
            <h4>Residential</h4>
            <div class="form-grid">
                <div class="form-group"><label>House/Block/Lot:</label> <?php echo htmlspecialchars($employee['res_spec_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Street:</label> <?php echo htmlspecialchars($employee['res_street_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Village:</label> <?php echo htmlspecialchars($employee['res_vill_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Barangay:</label> <?php echo htmlspecialchars($employee['res_barangay_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>City/Mun:</label> <?php echo htmlspecialchars($employee['res_city'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Province:</label> <?php echo htmlspecialchars($employee['res_province'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Zip Code:</label> <?php echo htmlspecialchars($employee['res_zipcode'] ?? 'N/A'); ?></div>
            </div>
            <h4>Permanent</h4>
            <div class="form-grid">
                <div class="form-group"><label>House/Block/Lot:</label> <?php echo htmlspecialchars($employee['perm_spec_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Street:</label> <?php echo htmlspecialchars($employee['perm_street_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Village:</label> <?php echo htmlspecialchars($employee['perm_vill_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Barangay:</label> <?php echo htmlspecialchars($employee['perm_barangay_address'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>City/Mun:</label> <?php echo htmlspecialchars($employee['perm_city'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Province:</label> <?php echo htmlspecialchars($employee['perm_province'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Zip Code:</label> <?php echo htmlspecialchars($employee['perm_zipcode'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Employment Details</h3>
            <div class="form-grid">
                <div class="form-group"><label>Department:</label> <?php echo htmlspecialchars($employee['dept_name'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Position:</label> <?php echo htmlspecialchars($employee['job_category'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Monthly Salary:</label> <?php echo isset($employee['monthly_salary']) ? number_format($employee['monthly_salary'], 2) : 'N/A'; ?></div>
                <div class="form-group"><label>Contract Type:</label> <?php echo htmlspecialchars($employee['contract_classification'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>Date Hired / Start Date:</label> <?php echo htmlspecialchars($employee['appointment_start_date'] ?? 'N/A'); ?></div>
                <div class="form-group"><label>End of Contract / End Date:</label> <?php echo htmlspecialchars($employee['appointment_end_date'] ?? 'N/A'); ?></div>
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
                            <td><?php echo htmlspecialchars($row['institution_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Education_degree']); ?></td>
                            <td><?php echo htmlspecialchars($row['year_graduated']); ?></td>
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
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['relationship']); ?></td>
                            <td><?php echo htmlspecialchars($row['telephone']); ?></td>
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
