<?php
require_once '../auth.php';
require_once '../conn.php';

// Ensure only HR can access this page
requireLogin();
requireRole(['HR Head', 'HR Staff']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - HRMIS</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        .form-section h3 { margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .full-width { grid-column: 1 / -1; }
        table.dynamic-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.dynamic-table th, table.dynamic-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.dynamic-table th { background-color: #f2f2f2; }
        .btn-add { background-color: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer; margin-top: 10px; }
        .btn-remove { background-color: #dc3545; color: white; border: none; padding: 2px 5px; cursor: pointer; }
        .question-block { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    </style>
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
                        <label>Name Extension (Jr, Sr)</label>
                        <input type="text" name="name_extension">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="birthdate" required>
                    </div>
                    <div class="form-group">
                        <label>Place of Birth</label>
                        <input type="text" name="birth_city" placeholder="City/Municipality" required>
                    </div>
                    <div class="form-group">
                        <label>Province of Birth</label>
                        <input type="text" name="birth_province" placeholder="Province" required>
                    </div>
                    <div class="form-group">
                        <label>Country of Birth</label>
                        <input type="text" name="birth_country" value="Philippines" required>
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <select name="sex" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status</label>
                        <select name="civil_status" required>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Height (m)</label>
                        <input type="number" step="0.01" name="height" required>
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" required>
                    </div>
                    <div class="form-group">
                        <label>Blood Type</label>
                        <input type="text" name="blood_type" required>
                    </div>
                    <div class="form-group">
                        <label>GSIS ID No.</label>
                        <input type="text" name="gsis_no">
                    </div>
                    <div class="form-group">
                        <label>PAG-IBIG ID No.</label>
                        <input type="text" name="pagibig_no">
                    </div>
                    <div class="form-group">
                        <label>PhilHealth No.</label>
                        <input type="text" name="philhealthno">
                    </div>
                    <div class="form-group">
                        <label>SSS No.</label>
                        <input type="text" name="sss_no">
                    </div>
                    <div class="form-group">
                        <label>TIN No.</label>
                        <input type="text" name="tin" required>
                    </div>
                    <div class="form-group">
                        <label>Citizenship</label>
                        <input type="text" name="citizenship" value="Filipino" required>
                    </div>
                </div>
                
                <h4>Residential Address</h4>
                <div class="form-grid">
                    <div class="form-group"><label>House/Block/Lot No.</label><input type="text" name="res_spec_address"></div>
                    <div class="form-group"><label>Street</label><input type="text" name="res_street_address"></div>
                    <div class="form-group"><label>Subdivision/Village</label><input type="text" name="res_vill_address"></div>
                    <div class="form-group"><label>Barangay</label><input type="text" name="res_barangay_address" required></div>
                    <div class="form-group"><label>City/Municipality</label><input type="text" name="res_city" required></div>
                    <div class="form-group"><label>Province</label><input type="text" name="res_province" required></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="res_zipcode" required></div>
                </div>

                <h4>Permanent Address</h4>
                <div class="form-grid">
                    <div class="form-group"><label>House/Block/Lot No.</label><input type="text" name="perm_spec_address"></div>
                    <div class="form-group"><label>Street</label><input type="text" name="perm_street_address"></div>
                    <div class="form-group"><label>Subdivision/Village</label><input type="text" name="perm_vill_address"></div>
                    <div class="form-group"><label>Barangay</label><input type="text" name="perm_barangay_address" required></div>
                    <div class="form-group"><label>City/Municipality</label><input type="text" name="perm_city" required></div>
                    <div class="form-group"><label>Province</label><input type="text" name="perm_province" required></div>
                    <div class="form-group"><label>Zip Code</label><input type="text" name="perm_zipcode" required></div>
                </div>

                <div class="form-grid">
                    <div class="form-group"><label>Telephone No.</label><input type="text" name="telephone"></div>
                    <div class="form-group"><label>Mobile No.</label><input type="text" name="contact_number" required></div>
                    <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
                </div>
            </div>

            <!-- II. Family Background -->
            <div class="form-section">
                <h3>II. Family Background</h3>
                <table class="dynamic-table" id="familyTable">
                    <thead>
                        <tr>
                            <th>Name (Last, First, Middle)</th>
                            <th>Relationship</th>
                            <th>Contact No.</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addFamilyRow()">Add Family Member</button>
            </div>

            <!-- III. Educational Background -->
            <div class="form-section">
                <h3>III. Educational Background</h3>
                <table class="dynamic-table" id="educationTable">
                    <thead>
                        <tr>
                            <th>Name of School</th>
                            <th>Degree/Course</th>
                            <th>Year Graduated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addEducationRow()">Add Education</button>
            </div>

            <!-- IV. Civil Service Eligibility -->
            <div class="form-section">
                <h3>IV. Civil Service Eligibility</h3>
                <table class="dynamic-table" id="eligibilityTable">
                    <thead>
                        <tr>
                            <th>Career Service/ Exam Title</th>
                            <th>Rating</th>
                            <th>Date of Exam</th>
                            <th>Place of Exam</th>
                            <th>License No.</th>
                            <th>Date of Validity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addEligibilityRow()">Add Eligibility</button>
            </div>

            <!-- V. Work Experience -->
            <div class="form-section">
                <h3>V. Work Experience</h3>
                <table class="dynamic-table" id="workTable">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Position Title</th>
                            <th>Department / Agency / Office / Company</th>
                            <th>Monthly Salary</th>
                            <th>Pay Grade</th>
                            <th>Status of Appointment</th>
                            <th>Gov't Service (Y/N)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addWorkRow()">Add Work Experience</button>
            </div>

            <!-- VI. Voluntary Work -->
            <div class="form-section">
                <h3>VI. Voluntary Work</h3>
                <table class="dynamic-table" id="voluntaryTable">
                    <thead>
                        <tr>
                            <th>Name & Address of Organization</th>
                            <th>From</th>
                            <th>To</th>
                            <th>No. of Hours</th>
                            <th>Position / Nature of Work</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addVoluntaryRow()">Add Voluntary Work</button>
            </div>

            <!-- VII. Learning and Development -->
            <div class="form-section">
                <h3>VII. Learning and Development (L&D)</h3>
                <table class="dynamic-table" id="trainingTable">
                    <thead>
                        <tr>
                            <th>Title of Learning and Development</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Type of LD</th>
                            <th>Conducted/Sponsored By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addTrainingRow()">Add Training</button>
            </div>

            <!-- VIII. Other Information -->
            <div class="form-section">
                <h3>VIII. Other Information</h3>
                
                <div class="question-block">
                    <p>34. Are you related by consanguinity or affinity to the appointing or recommending authority?</p>
                    <label>a. Within the third degree?</label>
                    <select name="Q34A"><option value="0">No</option><option value="1">Yes</option></select>
                    <label>b. Within the fourth degree (for LGUs)?</label>
                    <select name="Q34B"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q34_details" placeholder="If YES, give details">
                </div>

                <div class="question-block">
                    <p>35. a. Have you ever been found guilty of any administrative offense?</p>
                    <select name="Q35a"><option value="0">No</option><option value="1">Yes</option></select>
                    <p>b. Have you been criminally charged before any court?</p>
                    <select name="Q35b"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q35_details" placeholder="If YES, give details">
                </div>

                <div class="question-block">
                    <p>36. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?</p>
                    <select name="Q36"><option value="No">No</option><option value="Yes">Yes</option></select>
                    <input type="text" name="Q36_details" placeholder="If YES, give details">
                </div>

                <div class="question-block">
                    <p>37. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out?</p>
                    <select name="Q37"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q37_details" placeholder="If YES, give details">
                </div>

                <div class="question-block">
                    <p>38. a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?</p>
                    <select name="Q38a"><option value="0">No</option><option value="1">Yes</option></select>
                    <p>b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?</p>
                    <select name="Q38b"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q38_details" placeholder="If YES, give details">
                </div>

                <div class="question-block">
                    <p>39. a. Have you acquired the status of an immigrant or permanent resident of another country?</p>
                    <select name="Q39a"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q39_details" placeholder="If YES, give details">
                </div>

                <div class="question-block">
                    <p>40. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:</p>
                    <label>a. Are you a member of any indigenous group?</label>
                    <select name="Q40a"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q40a_details" placeholder="If YES, please specify">
                    
                    <label>b. Are you a person with disability?</label>
                    <select name="Q40b"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q40b_details" placeholder="If YES, please specify ID No.">
                    
                    <label>c. Are you a solo parent?</label>
                    <select name="Q40c"><option value="0">No</option><option value="1">Yes</option></select>
                    <input type="text" name="Q40c_details" placeholder="If YES, please specify ID No.">
                </div>
            </div>

            <!-- References -->
            <div class="form-section">
                <h3>References</h3>
                <table class="dynamic-table" id="refTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Tel. No.</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addRefRow()">Add Reference</button>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-add" style="padding: 10px 20px; font-size: 16px;">Save Employee</button>
            </div>
        </form>
    </div>

    <script>
        function addFamilyRow() {
            const table = document.getElementById('familyTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="rel_name[]" placeholder="Surname, First Name, MI" required></td>
                <td><input type="text" name="rel_relationship[]" placeholder="e.g. Spouse, Child, Father" required></td>
                <td><input type="text" name="rel_contact[]"></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }

        function addEducationRow() {
            const table = document.getElementById('educationTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="edu_school[]" required></td>
                <td><input type="text" name="edu_degree[]"></td>
                <td><input type="number" name="edu_year[]" placeholder="YYYY"></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }

        function addRefRow() {
            const table = document.getElementById('refTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="references[][name]" required></td>
                <td><input type="text" name="references[][address]" required></td>
                <td><input type="text" name="references[][tel_no]" required></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }

        function addEligibilityRow() {
            const table = document.getElementById('eligibilityTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="eligibility[][exam_name]" required></td>
                <td><input type="number" step="0.01" name="eligibility[][rating]" required></td>
                <td><input type="date" name="eligibility[][date]" required></td>
                <td><input type="text" name="eligibility[][place]" required></td>
                <td><input type="text" name="eligibility[][license_number]"></td>
                <td><input type="date" name="eligibility[][validity]"></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }

        function addWorkRow() {
            const table = document.getElementById('workTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="date" name="work_experience[][from]" required></td>
                <td><input type="date" name="work_experience[][to]" required></td>
                <td><input type="text" name="work_experience[][position_title]" required></td>
                <td><input type="text" name="work_experience[][company]" required></td>
                <td><input type="number" step="0.01" name="work_experience[][salary]" required></td>
                <td><input type="text" name="work_experience[][pay_grade]"></td>
                <td><input type="text" name="work_experience[][status]" required></td>
                <td><select name="work_experience[][gov_service]"><option value="Y">Yes</option><option value="N">No</option></select></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }

        function addVoluntaryRow() {
            const table = document.getElementById('voluntaryTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="voluntary_work[][organization]" required></td>
                <td><input type="date" name="voluntary_work[][from]" required></td>
                <td><input type="date" name="voluntary_work[][to]" required></td>
                <td><input type="number" name="voluntary_work[][hours]" required></td>
                <td><input type="text" name="voluntary_work[][position]" required></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }

        function addTrainingRow() {
            const table = document.getElementById('trainingTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="trainings[][title]" required></td>
                <td><input type="date" name="trainings[][from]" required></td>
                <td><input type="date" name="trainings[][to]" required></td>
                <td><input type="text" name="trainings[][type]" required></td>
                <td><input type="text" name="trainings[][conductor]" required></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }

        function addRefRow() {
            const table = document.getElementById('refTable').getElementsByTagName('tbody')[0];
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" name="references[][name]" required></td>
                <td><input type="text" name="references[][address]" required></td>
                <td><input type="text" name="references[][tel_no]" required></td>
                <td><button type="button" class="btn-remove" onclick="this.closest('tr').remove()">X</button></td>
            `;
        }
    </script>
</body>
</html>
