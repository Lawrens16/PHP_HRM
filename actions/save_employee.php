<?php
require_once '../auth.php';
require_once '../conn.php';

// Ensure only HR can access this action
requireLogin();
requireRole(['HR Head', 'HR Staff']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    function sanitizeInput($data) {
        $data = trim($data);
        if (empty($data)) {
            return 'Not Applicable';
        }
        return htmlspecialchars($data);
    }

    // 1. Collect and Sanitize Employee Data
    $first_name = sanitizeInput($_POST['first_name']);
    $middle_name = sanitizeInput($_POST['middle_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $address = sanitizeInput($_POST['address']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $email = sanitizeInput($_POST['email']);
    
    // Additional fields from form
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '1900-01-01';
    $sex = !empty($_POST['sex']) ? $_POST['sex'] : 'Male';
    $civil_status = !empty($_POST['civil_status']) ? $_POST['civil_status'] : 'Single';
    
    $date_hired = !empty($_POST['date_hired']) ? $_POST['date_hired'] : date('Y-m-d'); // Default to today if missing
    $appointment_end_date = !empty($_POST['appointment_end_date']) ? $_POST['appointment_end_date'] : NULL;
    $department_id = $_POST['department_id'];
    $position_id = $_POST['position_id'];
    $contract_type_id = $_POST['contract_type_id'];

    // 2. Start Transaction (ACID)
    $conn->begin_transaction();

    try {
        // Generate new ID (Fix for missing AUTO_INCREMENT and outlier max ID)
        // Ignore the max integer value (2147483647) to find the real next ID
        $res_id = $conn->query("SELECT MAX(idemployees) as max_id FROM employees WHERE idemployees < 2147483647");
        $row_id = $res_id->fetch_assoc();
        $new_id = ($row_id['max_id'] ?? 0) + 1;

        // 3. Insert into Employees Table
        // Schema: idemployees, first_name, middle_name, last_name, res_spec_address, contactno, email
        // Added missing required fields with defaults
        $sql_emp = "INSERT INTO employees (
            idemployees, first_name, middle_name, last_name, res_spec_address, contactno, email,
            birthdate, sex, civil_status,
            birth_city, birth_province, birth_country,
            height_in_meter, weight_in_kg, blood_type, tin, employee_no, citizenship,
            res_barangay_address, res_city, res_municipality, res_province, res_zipcode,
            perm_barangay_address, perm_city, perm_municipality, perm_province, perm_zipcode,
            mobile_no,
            Q34A, Q34B, Q35a, Q35b, Q36, Q37, Q38a, Q38b, Q39a, Q39b, Q40a, Q40b, Q40c
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        
        $stmt_emp = $conn->prepare($sql_emp);
        if (!$stmt_emp) {
            throw new Exception("Prepare failed (Employees): " . $conn->error);
        }

        // Defaults
        $def_str = 'N/A';
        $def_float = 0.0;
        $def_int = 0;
        $def_tiny = 0;
        $def_country = 'Philippines';
        $def_cit = 'Filipino';

        $stmt_emp->bind_param("issssssssssssddssissssssssssssiiiisiiiiiiii", 
            $new_id, $first_name, $middle_name, $last_name, $address, $contact_number, $email,
            $date_of_birth, $sex, $civil_status,
            $def_str, $def_str, $def_country,
            $def_float, $def_float, $def_str, $def_str, $new_id, $def_cit,
            $def_str, $def_str, $def_str, $def_str, $def_str,
            $def_str, $def_str, $def_str, $def_str, $def_str,
            $contact_number,
            $def_tiny, $def_tiny, $def_tiny, $def_tiny, $def_str, $def_tiny, $def_tiny, $def_tiny, $def_tiny, $def_tiny, $def_tiny, $def_tiny, $def_tiny
        );

        if (!$stmt_emp->execute()) {
            throw new Exception("Execute failed (Employees): " . $stmt_emp->error);
        }

        $employee_id = $new_id; // Use the manually generated ID
        $stmt_emp->close();

        // 4. Insert Unit Assignment (Department)
        $sql_dept = "INSERT INTO employees_unitassignments (employees_idemployees, departments_iddepartments, transfer_date) VALUES (?, ?, CURDATE())";
        $stmt_dept = $conn->prepare($sql_dept);
        if (!$stmt_dept) {
            throw new Exception("Prepare failed (Dept): " . $conn->error);
        }
        $stmt_dept->bind_param("ii", $employee_id, $department_id);
        if (!$stmt_dept->execute()) {
            throw new Exception("Execute failed (Dept): " . $stmt_dept->error);
        }
        $stmt_dept->close();

        // 5. Insert Service Record (Position & Contract)
        $sql_sr = "INSERT INTO service_records (employees_idemployees, job_positions_idjob_positions, contract_types_idcontract_types, appointment_start_date, appointment_end_date, institutions_idinstitutions, monthly_salary, pay_grade, gov_service) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_sr = $conn->prepare($sql_sr);
        if (!$stmt_sr) {
            throw new Exception("Prepare failed (Service Record): " . $conn->error);
        }

        // Default values
        $default_inst_id = 0;
        $default_salary = 0.0;
        $default_pay_grade = 'N/A';
        $default_gov_service = 0;

        $stmt_sr->bind_param("iiissidsi", $employee_id, $position_id, $contract_type_id, $date_hired, $appointment_end_date, $default_inst_id, $default_salary, $default_pay_grade, $default_gov_service);

        if (!$stmt_sr->execute()) {
            throw new Exception("Execute failed (Service Record): " . $stmt_sr->error);
        }
        $stmt_sr->close();

        // 6. Insert Relatives
        // Schema: relatives (first_name, last_name, telephone) -> employees_relatives (employees_idemployees, Relatives_idrelatives, relationship)
        if (isset($_POST['rel_name']) && is_array($_POST['rel_name'])) {
            $names = $_POST['rel_name'];
            $relationships = $_POST['rel_relationship'];
            $contacts = $_POST['rel_contact'];

            // Get max ID for relatives
            $res_max_rel = $conn->query("SELECT MAX(idrelatives) as max_id FROM relatives");
            $row_max_rel = $res_max_rel->fetch_assoc();
            $next_rel_id = ($row_max_rel['max_id'] ?? 0) + 1;

            // Insert with all required fields
            $sql_rel_insert = "INSERT INTO relatives (idrelatives, first_name, last_name, telephone, name_extension, Occupation, Emp_business, business_address, birthdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_rel_insert = $conn->prepare($sql_rel_insert);
            
            // Added relationship to employees_relatives table insert
            $sql_rel_link = "INSERT INTO employees_relatives (employees_idemployees, Relatives_idrelatives, relationship) VALUES (?, ?, ?)";
            $stmt_rel_link = $conn->prepare($sql_rel_link);

            // Default values
            $def_ext = '';
            $def_occ = 'N/A';
            $def_bus = 'N/A';
            $def_addr = 'N/A';
            $def_bday = '1900-01-01';

            for ($i = 0; $i < count($names); $i++) {
                if (empty($names[$i]) && empty($relationships[$i])) continue;

                $full_name = sanitizeInput($names[$i]);
                $parts = explode(' ', $full_name, 2);
                $r_fname = $parts[0];
                $r_lname = isset($parts[1]) ? $parts[1] : '';
                
                $r_rel = sanitizeInput($relationships[$i]);
                $r_cont = sanitizeInput($contacts[$i]);

                // Insert Relative
                $stmt_rel_insert->bind_param("issssssss", $next_rel_id, $r_fname, $r_lname, $r_cont, $def_ext, $def_occ, $def_bus, $def_addr, $def_bday);
                if (!$stmt_rel_insert->execute()) {
                    throw new Exception("Relative insert failed: " . $stmt_rel_insert->error);
                }
                
                $relative_id = $next_rel_id;
                $next_rel_id++;

                // Link Relative with Relationship
                $stmt_rel_link->bind_param("iis", $employee_id, $relative_id, $r_rel);
                if (!$stmt_rel_link->execute()) {
                    throw new Exception("Relative link failed: " . $stmt_rel_link->error);
                }
            }
            $stmt_rel_insert->close();
            $stmt_rel_link->close();
        }

        // 7. Insert Education
        if (isset($_POST['edu_school']) && is_array($_POST['edu_school'])) {
            $schools = $_POST['edu_school'];
            $degrees = $_POST['edu_degree'];
            $years = $_POST['edu_year'];

            // Prepare Check Institution
            $sql_inst_check = "SELECT idinstitutions FROM institutions WHERE institution_name = ?";
            $stmt_inst_check = $conn->prepare($sql_inst_check);
            $check_school_name = "";
            $stmt_inst_check->bind_param("s", $check_school_name);
            
            // Prepare Insert Institution
            $sql_inst_insert = "INSERT INTO institutions (idinstitutions, institution_name) VALUES (?, ?)";
            $stmt_inst_insert = $conn->prepare($sql_inst_insert);
            $new_inst_id = 0;
            $insert_school_name = "";
            $stmt_inst_insert->bind_param("is", $new_inst_id, $insert_school_name);

            // Prepare Insert Education
            $sql_edu_link = "INSERT INTO employees_education (employees_idemployees, institutions_idinstitutions, Education_degree, year_graduated, start_period, end_period, units_earned, scholarships, acad_honors) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_edu_link = $conn->prepare($sql_edu_link);
            
            // Variables for binding
            $bind_id = $employee_id;
            $bind_inst_id = 0;
            $bind_degree = "";
            $bind_year_date = "";
            $bind_start = '1900-01-01';
            $bind_end = '1900-01-01';
            $bind_units = 0;
            $bind_schol = 'N/A';
            $bind_honors = 'N/A';
            
            $stmt_edu_link->bind_param("iissssiss", $bind_id, $bind_inst_id, $bind_degree, $bind_year_date, $bind_start, $bind_end, $bind_units, $bind_schol, $bind_honors);

            for ($i = 0; $i < count($schools); $i++) {
                if (empty($schools[$i])) continue;

                // Set variables for Check
                $check_school_name = sanitizeInput($schools[$i]);
                $stmt_inst_check->execute();
                $res_inst = $stmt_inst_check->get_result();
                
                if ($res_inst->num_rows > 0) {
                    $inst_row = $res_inst->fetch_assoc();
                    $bind_inst_id = $inst_row['idinstitutions'];
                } else {
                    // Generate new ID
                    $res_max = $conn->query("SELECT MAX(idinstitutions) as max_id FROM institutions");
                    $row_max = $res_max->fetch_assoc();
                    $new_inst_id = ($row_max['max_id'] ?? 0) + 1;
                    
                    // Set variables for Insert Institution
                    $insert_school_name = $check_school_name;
                    if (!$stmt_inst_insert->execute()) {
                        throw new Exception("Institution insert failed: " . $stmt_inst_insert->error);
                    }
                    
                    $bind_inst_id = $new_inst_id;
                }

                // Set variables for Insert Education
                $bind_degree = sanitizeInput($degrees[$i]);
                $raw_year = sanitizeInput($years[$i]);
                $bind_year_date = (is_numeric($raw_year) ? $raw_year : date('Y')) . '-01-01';
                
                if (!$stmt_edu_link->execute()) {
                    throw new Exception("Education link failed: " . $stmt_edu_link->error);
                }
            }
            $stmt_inst_check->close();
            $stmt_inst_insert->close();
            $stmt_edu_link->close();
        }

        $conn->commit();
        
        // Redirect with success message
        header("Location: ../views/employee_list.php?msg=Employee added successfully");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error saving record: " . $e->getMessage());
    }
}
?>
