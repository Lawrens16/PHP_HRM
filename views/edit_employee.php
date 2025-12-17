<?php
require_once '../auth.php';
require_once '../conn.php';

requireLogin();
requireRole(['HR Head', 'HR Staff', 'Employee']);

if (!isset($_GET['id'])) {
    header("Location: employee_list.php");
    exit();
}

$id = $_GET['id'];

// Access Control: Employees can only edit their own profile
if ($_SESSION['role'] == 'Employee' && $_SESSION['employee_id'] != $id) {
    die("<h1>Access Denied</h1><p>You can only edit your own profile.</p>");
}

// Fetch Employee Data
$sql = "SELECT e.*, 
        d.iddepartments, d.dept_name,
        p.idjob_positions, p.job_category,
        c.idcontract_types, c.contract_classification,
        sr.appointment_start_date,
        sr.appointment_end_date,
        sr.monthly_salary
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

// Fetch Dropdown Options
$departments = $conn->query("SELECT * FROM departments");
$positions = $conn->query("SELECT * FROM job_positions");
$contract_types = $conn->query("SELECT * FROM contract_types");

// Fetch Education
$sql_edu = "SELECT ee.*, i.institution_name 
            FROM employees_education ee
            JOIN institutions i ON ee.institutions_idinstitutions = i.idinstitutions
            WHERE ee.employees_idemployees = ?";
$stmt_edu = $conn->prepare($sql_edu);
$stmt_edu->bind_param("i", $id);
$stmt_edu->execute();
$education = $stmt_edu->get_result();

// Fetch Relatives
// Updated: Select relationship from employees_relatives (er) instead of relatives (r)
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
    <title>Edit Employee - HRMIS</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .dynamic-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .dynamic-row input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .dynamic-row button {
            padding: 8px 15px;
            white-space: nowrap;
        }
    </style>
    <script>
        function addEducation() {
            const container = document.getElementById('education-container');
            const div = document.createElement('div');
            div.className = 'dynamic-row';
            div.innerHTML = `
                <input type="text" name="edu_school[]" placeholder="School Name" required>
                <input type="text" name="edu_degree[]" placeholder="Degree" required>
                <input type="number" name="edu_year[]" placeholder="Year Graduated">
                <button type="button" class="btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(div);
        }

        function addRelative() {
            const container = document.getElementById('relatives-container');
            const div = document.createElement('div');
            div.className = 'dynamic-row';
            div.innerHTML = `
                <input type="text" name="rel_name[]" placeholder="Full Name" required>
                <input type="text" name="rel_relationship[]" placeholder="Relationship" required>
                <input type="text" name="rel_contact[]" placeholder="Contact Number" required>
                <button type="button" class="btn-danger" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(div);
        }
    </script>
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
        <h2>Edit Employee Record</h2>
        
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

        <form action="../actions/update_employee.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $employee['idemployees']; ?>">
            
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo htmlspecialchars($employee['middle_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" value="<?php echo htmlspecialchars($employee['contactno']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Name Extension</label>
                        <input type="text" name="name_extension" value="<?php echo htmlspecialchars($employee['name_extension'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="birthdate" value="<?php echo htmlspecialchars($employee['birthdate']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Place of Birth (City/Mun)</label>
                        <input type="text" name="birth_city" value="<?php echo htmlspecialchars($employee['birth_city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Province of Birth</label>
                        <input type="text" name="birth_province" value="<?php echo htmlspecialchars($employee['birth_province'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Country of Birth</label>
                        <input type="text" name="birth_country" value="<?php echo htmlspecialchars($employee['birth_country'] ?? 'Philippines'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <select name="sex" required>
                            <option value="Male" <?php echo ($employee['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($employee['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status</label>
                        <select name="civil_status" required>
                            <option value="Single" <?php echo ($employee['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo ($employee['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                            <option value="Widowed" <?php echo ($employee['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                            <option value="Separated" <?php echo ($employee['civil_status'] == 'Separated') ? 'selected' : ''; ?>>Separated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Height (m)</label>
                        <input type="number" step="0.01" name="height" value="<?php echo htmlspecialchars($employee['height_in_meter'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" value="<?php echo htmlspecialchars($employee['weight_in_kg'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Blood Type</label>
                        <input type="text" name="blood_type" value="<?php echo htmlspecialchars($employee['blood_type'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>GSIS No.</label>
                        <input type="text" name="gsis_no" value="<?php echo htmlspecialchars($employee['gsis_no'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>SSS No.</label>
                        <input type="text" name="sss_no" value="<?php echo htmlspecialchars($employee['sss_no'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>PhilHealth No.</label>
                        <input type="text" name="philhealthno" value="<?php echo htmlspecialchars($employee['philhealthno'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>TIN</label>
                        <input type="text" name="tin" value="<?php echo htmlspecialchars($employee['tin'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Citizenship</label>
                        <input type="text" name="citizenship" value="<?php echo htmlspecialchars($employee['citizenship'] ?? 'Filipino'); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Addresses</h3>
                <h4>Residential</h4>
                <div class="form-grid">
                    <div class="form-group"><label>House/Block/Lot</label><input type="text" name="res_spec_address" value="<?php echo htmlspecialchars($employee['res_spec_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Street</label><input type="text" name="res_street_address" value="<?php echo htmlspecialchars($employee['res_street_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Village</label><input type="text" name="res_vill_address" value="<?php echo htmlspecialchars($employee['res_vill_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Barangay</label><input type="text" name="res_barangay_address" value="<?php echo htmlspecialchars($employee['res_barangay_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>City/Mun</label><input type="text" name="res_city" value="<?php echo htmlspecialchars($employee['res_city'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Province</label><input type="text" name="res_province" value="<?php echo htmlspecialchars($employee['res_province'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="res_zipcode" value="<?php echo htmlspecialchars($employee['res_zipcode'] ?? ''); ?>"></div>
                </div>
                <h4>Permanent</h4>
                <div class="form-grid">
                    <div class="form-group"><label>House/Block/Lot</label><input type="text" name="perm_spec_address" value="<?php echo htmlspecialchars($employee['perm_spec_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Street</label><input type="text" name="perm_street_address" value="<?php echo htmlspecialchars($employee['perm_street_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Village</label><input type="text" name="perm_vill_address" value="<?php echo htmlspecialchars($employee['perm_vill_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Barangay</label><input type="text" name="perm_barangay_address" value="<?php echo htmlspecialchars($employee['perm_barangay_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>City/Mun</label><input type="text" name="perm_city" value="<?php echo htmlspecialchars($employee['perm_city'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Province</label><input type="text" name="perm_province" value="<?php echo htmlspecialchars($employee['perm_province'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="perm_zipcode" value="<?php echo htmlspecialchars($employee['perm_zipcode'] ?? ''); ?>"></div>
                </div>

            <div class="form-section">
                <h3>Employment Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Department</label>
                        <?php if ($_SESSION['role'] == 'Employee'): ?>
                            <input type="text" value="<?php echo htmlspecialchars($employee['dept_name'] ?? 'N/A'); ?>" readonly style="background-color: #e9ecef;">
                            <input type="hidden" name="department_id" value="<?php echo $employee['iddepartments']; ?>">
                        <?php else: ?>
                            <select name="department_id" required>
                                <option value="">Select Department</option>
                                <?php while($row = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $row['iddepartments']; ?>" <?php echo ($row['iddepartments'] == $employee['iddepartments']) ? 'selected' : ''; ?>>
                                        <?php echo $row['dept_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <?php if ($_SESSION['role'] == 'Employee'): ?>
                            <input type="text" value="<?php echo htmlspecialchars($employee['job_category'] ?? 'N/A'); ?>" readonly style="background-color: #e9ecef;">
                            <input type="hidden" name="position_id" value="<?php echo $employee['idjob_positions']; ?>">
                        <?php else: ?>
                            <select name="position_id" required>
                                <option value="">Select Position</option>
                                <?php while($row = $positions->fetch_assoc()): ?>
                                    <option value="<?php echo $row['idjob_positions']; ?>" <?php echo ($row['idjob_positions'] == $employee['idjob_positions']) ? 'selected' : ''; ?>>
                                        <?php echo $row['job_category']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Monthly Salary</label>
                        <?php if ($_SESSION['role'] == 'Employee'): ?>
                            <input type="number" step="0.01" value="<?php echo htmlspecialchars($employee['monthly_salary'] ?? '0.00'); ?>" readonly style="background-color: #e9ecef;">
                            <input type="hidden" name="monthly_salary" value="<?php echo $employee['monthly_salary']; ?>">
                        <?php else: ?>
                            <input type="number" step="0.01" name="monthly_salary" value="<?php echo htmlspecialchars($employee['monthly_salary'] ?? '0.00'); ?>" required>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Contract Type</label>
                        <?php if ($_SESSION['role'] == 'Employee'): ?>
                            <input type="text" value="<?php echo htmlspecialchars($employee['contract_classification'] ?? 'N/A'); ?>" readonly style="background-color: #e9ecef;">
                            <input type="hidden" name="contract_type_id" value="<?php echo $employee['idcontract_types']; ?>">
                        <?php else: ?>
                            <select name="contract_type_id" required>
                                <option value="">Select Contract Type</option>
                                <?php while($row = $contract_types->fetch_assoc()): ?>
                                    <option value="<?php echo $row['idcontract_types']; ?>" <?php echo ($row['idcontract_types'] == $employee['idcontract_types']) ? 'selected' : ''; ?>>
                                        <?php echo $row['contract_classification']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Date Hired / Start Date</label>
                        <?php if ($_SESSION['role'] == 'Employee'): ?>
                            <input type="date" value="<?php echo htmlspecialchars($employee['appointment_start_date'] ?? ''); ?>" readonly style="background-color: #e9ecef;">
                            <input type="hidden" name="date_hired" value="<?php echo $employee['appointment_start_date']; ?>">
                        <?php else: ?>
                            <input type="date" name="date_hired" value="<?php echo htmlspecialchars($employee['appointment_start_date'] ?? ''); ?>" required>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>End of Contract / End Date</label>
                        <?php if ($_SESSION['role'] == 'Employee'): ?>
                            <input type="date" value="<?php echo htmlspecialchars($employee['appointment_end_date'] ?? ''); ?>" readonly style="background-color: #e9ecef;">
                            <input type="hidden" name="appointment_end_date" value="<?php echo $employee['appointment_end_date']; ?>">
                        <?php else: ?>
                            <input type="date" name="appointment_end_date" value="<?php echo htmlspecialchars($employee['appointment_end_date'] ?? ''); ?>">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Educational Background</h3>
                <div id="education-container">
                    <?php while($row = $education->fetch_assoc()): ?>
                        <div class="dynamic-row">
                            <input type="text" name="edu_school[]" value="<?php echo htmlspecialchars($row['institution_name']); ?>" placeholder="School Name" required>
                            <input type="text" name="edu_degree[]" value="<?php echo htmlspecialchars($row['Education_degree']); ?>" placeholder="Degree" required>
                            <input type="number" name="edu_year[]" value="<?php echo intval($row['year_graduated']); ?>" placeholder="Year Graduated">
                            <button type="button" class="btn-danger" onclick="this.parentElement.remove()">Remove</button>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="button" class="btn-secondary" onclick="addEducation()">+ Add Education</button>
            </div>

            <div class="form-section">
                <h3>Family Background</h3>
                <div id="relatives-container">
                    <?php while($row = $relatives->fetch_assoc()): ?>
                        <div class="dynamic-row">
                            <input type="text" name="rel_name[]" value="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>" placeholder="Full Name" required>
                            <input type="text" name="rel_relationship[]" value="<?php echo htmlspecialchars($row['relationship']); ?>" placeholder="Relationship" required>
                            <input type="text" name="rel_contact[]" value="<?php echo htmlspecialchars($row['telephone']); ?>" placeholder="Contact Number" required>
                            <button type="button" class="btn-danger" onclick="this.parentElement.remove()">Remove</button>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="button" class="btn-secondary" onclick="addRelative()">+ Add Relative</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Update Record</button>
                <?php if ($_SESSION['role'] == 'Employee'): ?>
                    <a href="view_employee.php?id=<?php echo $id; ?>" class="btn-secondary">Cancel</a>
                <?php else: ?>
                    <a href="employee_list.php" class="btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>
