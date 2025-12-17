<?php
require_once '../auth.php';
require_once '../conn.php';

// Ensure only HR can access this page
requireLogin();
requireRole(['HR Head', 'HR Staff']);

// Fetch Dropdown Data
// Updated: iddepartments, dept_name
$departments = $conn->query("SELECT * FROM departments");
// Updated: idjob_positions, job_category
$positions = $conn->query("SELECT * FROM job_positions");
// Updated: idcontract_types, contract_classification
$contract_types = $conn->query("SELECT * FROM contract_types");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - HRMIS</title>
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
        <h2>Add New Employee (CSC Form 212)</h2>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px;">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="../actions/save_employee.php" method="POST">
            <!-- I. Personal Information -->
            <div class="form-section">
                <h3>I. Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <select name="sex">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status</label>
                        <select name="civil_status">
                            <option value="">Select</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Residential Address</label>
                        <input type="text" name="address" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="form-section">
                <h3>Employment Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" required>
                            <option value="">Select Department</option>
                            <?php while($row = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $row['iddepartments']; ?>"><?php echo $row['dept_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Job Position</label>
                        <select name="position_id" required>
                            <option value="">Select Position</option>
                            <?php while($row = $positions->fetch_assoc()): ?>
                                <option value="<?php echo $row['idjob_positions']; ?>"><?php echo $row['job_category']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contract Type</label>
                        <select name="contract_type_id" required>
                            <option value="">Select Contract Type</option>
                            <?php while($row = $contract_types->fetch_assoc()): ?>
                                <option value="<?php echo $row['idcontract_types']; ?>"><?php echo $row['contract_classification']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Hired / Start Date</label>
                        <input type="date" name="date_hired" required>
                    </div>
                    <div class="form-group">
                        <label>End of Contract / End Date</label>
                        <input type="date" name="appointment_end_date">
                    </div>
                </div>
            </div>

            <!-- II. Family Background -->
            <div class="form-section">
                <h3>II. Family Background</h3>
                <table id="relativesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Relationship</th>
                            <th>Contact Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows added via JS -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addRelativeRow()">+ Add Relative</button>
            </div>

            <!-- III. Educational Background -->
            <div class="form-section">
                <h3>III. Educational Background</h3>
                <table id="educationTable">
                    <thead>
                        <tr>
                            <th>School Name</th>
                            <th>Degree/Course</th>
                            <th>Year Graduated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows added via JS -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addEducationRow()">+ Add Education</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Employee Record</button>
            </div>
        </form>
    </div>

    <script>
        function addRelativeRow() {
            const table = document.getElementById('relativesTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" name="rel_name[]" placeholder="Full Name"></td>
                <td><input type="text" name="rel_relationship[]" placeholder="e.g. Spouse"></td>
                <td><input type="text" name="rel_contact[]" placeholder="Contact No."></td>
                <td><button type="button" class="btn-danger" onclick="removeRow(this)">Remove</button></td>
            `;
        }

        function addEducationRow() {
            const table = document.getElementById('educationTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" name="edu_school[]" placeholder="School Name"></td>
                <td><input type="text" name="edu_degree[]" placeholder="Degree"></td>
                <td><input type="text" name="edu_year[]" placeholder="Year"></td>
                <td><button type="button" class="btn-danger" onclick="removeRow(this)">Remove</button></td>
            `;
        }

        function removeRow(btn) {
            const row = btn.parentNode.parentNode;
            row.parentNode.removeChild(row);
        }

        // Add initial rows
        addRelativeRow();
        addEducationRow();
    </script>
</body>
</html>
