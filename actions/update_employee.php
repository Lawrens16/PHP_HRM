<?php
require_once '../auth.php';
require_once '../conn.php';

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

    $id = $_POST['id'];
    
    // 1. Collect Data
    $first_name = sanitizeInput($_POST['first_name']);
    $middle_name = sanitizeInput($_POST['middle_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $address = sanitizeInput($_POST['address']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $email = sanitizeInput($_POST['email']);
    
    // Additional fields
    // $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : NULL; // Removed as column does not exist
    // $sex = !empty($_POST['sex']) ? $_POST['sex'] : NULL; // Removed as column does not exist
    // $civil_status = !empty($_POST['civil_status']) ? $_POST['civil_status'] : NULL; // Removed as column does not exist
    $date_hired = !empty($_POST['date_hired']) ? $_POST['date_hired'] : date('Y-m-d');
    $appointment_end_date = !empty($_POST['appointment_end_date']) ? $_POST['appointment_end_date'] : NULL;

    $department_id = $_POST['department_id'];
    $position_id = $_POST['position_id'];
    $contract_type_id = $_POST['contract_type_id'];

    $conn->begin_transaction();

    try {
        // 2. Update Employee Table
        $sql_emp = "UPDATE employees SET first_name=?, middle_name=?, last_name=?, res_spec_address=?, contactno=?, email=? WHERE idemployees=?";
        $stmt_emp = $conn->prepare($sql_emp);
        $stmt_emp->bind_param("ssssssi", $first_name, $middle_name, $last_name, $address, $contact_number, $email, $id);
        if (!$stmt_emp->execute()) {
            throw new Exception("Update employee failed: " . $stmt_emp->error);
        }
        $stmt_emp->close();

        // 3. Update Unit Assignment (Delete & Insert)
        $conn->query("DELETE FROM employees_unitassignments WHERE employees_idemployees = $id");
        $sql_dept = "INSERT INTO employees_unitassignments (employees_idemployees, departments_iddepartments, transfer_date) VALUES (?, ?, CURDATE())";
        $stmt_dept = $conn->prepare($sql_dept);
        $stmt_dept->bind_param("ii", $id, $department_id);
        if (!$stmt_dept->execute()) {
            throw new Exception("Update department failed: " . $stmt_dept->error);
        }
        $stmt_dept->close();

        // 4. Update Service Record (Delete & Insert)
        $conn->query("DELETE FROM service_records WHERE employees_idemployees = $id");
        $sql_sr = "INSERT INTO service_records (employees_idemployees, job_positions_idjob_positions, contract_types_idcontract_types, appointment_start_date, appointment_end_date, institutions_idinstitutions, monthly_salary, pay_grade, gov_service) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_sr = $conn->prepare($sql_sr);
        
        // Default values for missing fields
        $default_inst_id = 0; // Default to ID 0 (e.g., Lyceum)
        $default_salary = 0.0;
        $default_pay_grade = 'N/A';
        $default_gov_service = 0;

        $stmt_sr->bind_param("iiissidsi", $id, $position_id, $contract_type_id, $date_hired, $appointment_end_date, $default_inst_id, $default_salary, $default_pay_grade, $default_gov_service);
        if (!$stmt_sr->execute()) {
            throw new Exception("Update service record failed: " . $stmt_sr->error);
        }
        $stmt_sr->close();

        // 5. Update Relatives (Delete & Insert)
        $conn->query("DELETE FROM employees_relatives WHERE employees_idemployees = $id");
        
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
            
            // Added relationship to employees_relatives table
            $sql_rel_link = "INSERT INTO employees_relatives (employees_idemployees, Relatives_idrelatives, relationship) VALUES (?, ?, ?)";
            $stmt_rel_link = $conn->prepare($sql_rel_link);

            // Default values
            $def_ext = '';
            $def_occ = 'N/A';
            $def_bus = 'N/A';
            $def_addr = 'N/A';
            $def_bday = '1900-01-01';

            for ($i = 0; $i < count($names); $i++) {
                if (empty($names[$i])) continue;

                $full_name = sanitizeInput($names[$i]);
                $parts = explode(' ', $full_name, 2);
                $r_fname = $parts[0];
                $r_lname = isset($parts[1]) ? $parts[1] : '';
                $r_rel = sanitizeInput($relationships[$i]);
                $r_cont = sanitizeInput($contacts[$i]);

                $stmt_rel_insert->bind_param("issssssss", $next_rel_id, $r_fname, $r_lname, $r_cont, $def_ext, $def_occ, $def_bus, $def_addr, $def_bday);
                if (!$stmt_rel_insert->execute()) {
                     throw new Exception("Relative insert failed: " . $stmt_rel_insert->error);
                }
                
                $relative_id = $next_rel_id;
                $next_rel_id++;

                $stmt_rel_link->bind_param("iis", $id, $relative_id, $r_rel);
                $stmt_rel_link->execute();
            }
        }

        // 6. Update Education (Delete & Insert)
        $conn->query("DELETE FROM employees_education WHERE employees_idemployees = $id");
        
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
            $bind_id = $id;
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
                    $stmt_inst_insert->execute();
                    
                    $bind_inst_id = $new_inst_id;
                }

                // Set variables for Insert Education
                $bind_degree = sanitizeInput($degrees[$i]);
                $raw_year = sanitizeInput($years[$i]);
                $bind_year_date = (is_numeric($raw_year) ? $raw_year : date('Y')) . '-01-01';
                
                $stmt_edu_link->execute();
            }
        }

        $conn->commit();
        header("Location: ../views/employee_list.php?msg=Employee updated successfully");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error updating record: " . $e->getMessage());
    }
}
?>
