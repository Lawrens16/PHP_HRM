<?php
class Employee {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function setAuditUser() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $userId = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;
        if ($userId) {
            $stmt = $this->conn->prepare("SET @current_user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function getNextId($table, $column) {
        $sql = "SELECT MAX($column) as max_id FROM $table WHERE $column < 2147483647";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return ($row["max_id"] ?? 0) + 1;
    }

    private function getOrCreateInstitution($name) {
        $name = trim($name);
        if (empty($name)) return null;

        $stmt = $this->conn->prepare("SELECT idinstitutions FROM institutions WHERE institution_name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return $row["idinstitutions"];
        }
        $stmt->close();

        $newId = $this->getNextId("institutions", "idinstitutions");
        $stmt = $this->conn->prepare("INSERT INTO institutions (idinstitutions, institution_name) VALUES (?, ?)");
        $stmt->bind_param("is", $newId, $name);
        $stmt->execute();
        $stmt->close();
        return $newId;
    }

    private function getOrCreateJobPosition($title) {
        $title = trim($title);
        if (empty($title)) return null;

        $stmt = $this->conn->prepare("SELECT idjob_positions FROM job_positions WHERE job_category = ?");
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return $row["idjob_positions"];
        }
        $stmt->close();

        $newId = $this->getNextId("job_positions", "idjob_positions");
        $desc = "Created from PDS";
        $stmt = $this->conn->prepare("INSERT INTO job_positions (idjob_positions, job_category, Job_description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $newId, $title, $desc);
        $stmt->execute();
        $stmt->close();
        return $newId;
    }

    private function getOrCreateContractType($type) {
        $type = trim($type);
        if (empty($type)) return null;

        $stmt = $this->conn->prepare("SELECT idcontract_types FROM contract_types WHERE contract_classification = ?");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return $row["idcontract_types"];
        }
        $stmt->close();

        $newId = $this->getNextId("contract_types", "idcontract_types");
        $desc = "Created from PDS";
        $stmt = $this->conn->prepare("INSERT INTO contract_types (idcontract_types, contract_classification, contract_description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $newId, $type, $desc);
        $stmt->execute();
        $stmt->close();
        return $newId;
    }

    private function getOrCreateExam($name) {
        $name = trim($name);
        if (empty($name)) return null;

        $stmt = $this->conn->prepare("SELECT idprofessional_exams FROM professional_exams WHERE Exam_description = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            return $row["idprofessional_exams"];
        }
        $stmt->close();

        $newId = $this->getNextId("professional_exams", "idprofessional_exams");
        $stmt = $this->conn->prepare("INSERT INTO professional_exams (idprofessional_exams, Exam_description) VALUES (?, ?)");
        $stmt->bind_param("is", $newId, $name);
        $stmt->execute();
        $stmt->close();
        return $newId;
    }

    public function create($data) {
        try {
            $this->conn->begin_transaction();
            $this->setAuditUser();

            $newId = $this->getNextId("employees", "idemployees");
            
            $defaults = [
                "middle_name" => "", "name_extension" => "", "gsis_no" => "", "sss_no" => "", "philhealthno" => "",
                "birth_province" => "", "birth_country" => "Philippines",
                "res_spec_address" => "", "res_street_address" => "", "res_vill_address" => "", "res_municipality" => "",
                "perm_spec_address" => "", "perm_street_address" => "", "perm_vill_address" => "", "perm_municipality" => "",
                "telephone" => "", "email" => "",
                "Q34A" => 0, "Q34B" => 0, "Q34_details" => "",
                "Q35a" => 0, "Q35b" => 0, "Q35_details" => "",
                "Q36" => "", "Q36_details" => "",
                "Q37" => 0, "Q37_details" => "",
                "Q38a" => 0, "Q38b" => 0, "Q38_details" => "",
                "Q39a" => 0, "Q39b" => 0, "Q39_details" => "",
                "Q40a" => 0, "Q40a_details" => "",
                "Q40b" => 0, "Q40b_details" => "",
                "Q40c" => 0, "Q40c_details" => ""
            ];
            
            $d = array_merge($defaults, $data);
            
            // Populate municipality from city if empty
            if (empty($d["res_municipality"]) && !empty($d["res_city"])) {
                $d["res_municipality"] = $d["res_city"];
            }
            if (empty($d["perm_municipality"]) && !empty($d["perm_city"])) {
                $d["perm_municipality"] = $d["perm_city"];
            }

            $sql = "INSERT INTO employees (
                idemployees, first_name, middle_name, last_name, name_extension, 
                birthdate, birth_city, birth_province, birth_country, sex, civil_status,
                height_in_meter, weight_in_kg, contactno, blood_type, gsis_no, sss_no, philhealthno, tin, employee_no, citizenship,
                res_spec_address, res_street_address, res_vill_address, res_barangay_address, res_city, res_municipality, res_province, res_zipcode,
                perm_spec_address, perm_street_address, perm_vill_address, perm_barangay_address, perm_city, perm_municipality, perm_province, perm_zipcode,
                telephone, mobile_no, email,
                Q34A, Q34B, Q34_details, Q35a, Q35b, Q35_details, Q36, Q36_details, Q37, Q37_details,
                Q38a, Q38b, Q38_details, Q39a, Q39b, Q39_details, Q40a, Q40a_details, Q40b, Q40b_details, Q40c, Q40c_details
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $this->conn->error);

            // Type string: i + 10s + 2d + 8s + 8s + 8s + 3s + 22 mixed (Q34-40)
            // Q types: iis(34), iis(35), ss(36), is(37), iis(38), iis(39), is(40a), is(40b), is(40c)
            // Note: middle block is ssssssis (8 chars)
            $types = "issssssssss" . "dd" . "ssssssis" . str_repeat("s", 19) . "iisiisssisiisiisisisis";

            $stmt->bind_param($types,
                $newId, $d["first_name"], $d["middle_name"], $d["last_name"], $d["name_extension"],
                $d["birthdate"], $d["birth_city"], $d["birth_province"], $d["birth_country"], $d["sex"], $d["civil_status"],
                $d["height"], $d["weight"], $d["contact_number"], $d["blood_type"], $d["gsis_no"], $d["sss_no"], $d["philhealthno"], $d["tin"], $newId, $d["citizenship"],
                $d["res_spec_address"], $d["res_street_address"], $d["res_vill_address"], $d["res_barangay_address"], $d["res_city"], $d["res_municipality"], $d["res_province"], $d["res_zipcode"],
                $d["perm_spec_address"], $d["perm_street_address"], $d["perm_vill_address"], $d["perm_barangay_address"], $d["perm_city"], $d["perm_municipality"], $d["perm_province"], $d["perm_zipcode"],
                $d["telephone"], $d["contact_number"], $d["email"],
                $d["Q34A"], $d["Q34B"], $d["Q34_details"], $d["Q35a"], $d["Q35b"], $d["Q35_details"], $d["Q36"], $d["Q36_details"], $d["Q37"], $d["Q37_details"],
                $d["Q38a"], $d["Q38b"], $d["Q38_details"], $d["Q39a"], $d["Q39b"], $d["Q39_details"], $d["Q40a"], $d["Q40a_details"], $d["Q40b"], $d["Q40b_details"], $d["Q40c"], $d["Q40c_details"]
            );

            if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
            $stmt->close();

            $this->saveRelatedData($newId, $data);

            $this->conn->commit();
            $_SESSION["success"] = "Employee added successfully";
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $_SESSION["error"] = "Error adding employee: " . $e->getMessage();
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $this->conn->begin_transaction();
            $this->setAuditUser();

             $sql = "UPDATE employees SET 
                first_name=?, middle_name=?, last_name=?, name_extension=?, 
                birthdate=?, birth_city=?, birth_province=?, birth_country=?, sex=?, civil_status=?,
                height_in_meter=?, weight_in_kg=?, contactno=?, blood_type=?, gsis_no=?, sss_no=?, philhealthno=?, tin=?, citizenship=?,
                res_spec_address=?, res_street_address=?, res_vill_address=?, res_barangay_address=?, res_city=?, res_municipality=?, res_province=?, res_zipcode=?,
                perm_spec_address=?, perm_street_address=?, perm_vill_address=?, perm_barangay_address=?, perm_city=?, perm_municipality=?, perm_province=?, perm_zipcode=?,
                telephone=?, mobile_no=?, email=?,
                Q34A=?, Q34B=?, Q34_details=?, Q35a=?, Q35b=?, Q35_details=?, Q36=?, Q36_details=?, Q37=?, Q37_details=?,
                Q38a=?, Q38b=?, Q38_details=?, Q39a=?, Q39b=?, Q39_details=?, Q40a=?, Q40a_details=?, Q40b=?, Q40b_details=?, Q40c=?, Q40c_details=?
                WHERE idemployees=?";
            
            $defaults = [
                "middle_name" => "", "name_extension" => "", "gsis_no" => "", "sss_no" => "", "philhealthno" => "",
                "birth_province" => "", "birth_country" => "Philippines",
                "res_spec_address" => "", "res_street_address" => "", "res_vill_address" => "", "res_municipality" => "",
                "perm_spec_address" => "", "perm_street_address" => "", "perm_vill_address" => "", "perm_municipality" => "",
                "telephone" => "", "email" => "",
                "Q34A" => 0, "Q34B" => 0, "Q34_details" => "",
                "Q35a" => 0, "Q35b" => 0, "Q35_details" => "",
                "Q36" => "", "Q36_details" => "",
                "Q37" => 0, "Q37_details" => "",
                "Q38a" => 0, "Q38b" => 0, "Q38_details" => "",
                "Q39a" => 0, "Q39b" => 0, "Q39_details" => "",
                "Q40a" => 0, "Q40a_details" => "",
                "Q40b" => 0, "Q40b_details" => "",
                "Q40c" => 0, "Q40c_details" => ""
            ];
            $d = array_merge($defaults, $data);

            // Populate municipality from city if empty
            if (empty($d["res_municipality"]) && !empty($d["res_city"])) {
                $d["res_municipality"] = $d["res_city"];
            }
            if (empty($d["perm_municipality"]) && !empty($d["perm_city"])) {
                $d["perm_municipality"] = $d["perm_city"];
            }

            $stmt = $this->conn->prepare($sql);
            
            // Explicitly construct type string to ensure match with 61 bind params
            $types = "";
            $types .= "ssssssssss"; // 1-10: Personal (10)
            $types .= "dd";         // 11-12: Height, Weight (2)
            $types .= "sssssss";    // 13-19: Contact, Blood, IDs (7)
            $types .= "ssssssss";   // 20-27: Res Address (8)
            $types .= "ssssssss";   // 28-35: Perm Address (8)
            $types .= "sss";        // 36-38: Tel, Mobile, Email (3)
            $types .= "iis";        // 39-41: Q34 (3)
            $types .= "iis";        // 42-44: Q35 (3)
            $types .= "ss";         // 45-46: Q36 (2)
            $types .= "is";         // 47-48: Q37 (2)
            $types .= "iis";        // 49-51: Q38 (3)
            $types .= "iis";        // 52-54: Q39 (3)
            $types .= "is";         // 55-56: Q40a (2)
            $types .= "is";         // 57-58: Q40b (2)
            $types .= "is";         // 59-60: Q40c (2)
            $types .= "i";          // 61: ID (1)
            
            $stmt->bind_param($types,
                $d["first_name"], $d["middle_name"], $d["last_name"], $d["name_extension"],
                $d["birthdate"], $d["birth_city"], $d["birth_province"], $d["birth_country"], $d["sex"], $d["civil_status"],
                $d["height"], $d["weight"], $d["contact_number"], $d["blood_type"], $d["gsis_no"], $d["sss_no"], $d["philhealthno"], $d["tin"], $d["citizenship"],
                $d["res_spec_address"], $d["res_street_address"], $d["res_vill_address"], $d["res_barangay_address"], $d["res_city"], $d["res_municipality"], $d["res_province"], $d["res_zipcode"],
                $d["perm_spec_address"], $d["perm_street_address"], $d["perm_vill_address"], $d["perm_barangay_address"], $d["perm_city"], $d["perm_municipality"], $d["perm_province"], $d["perm_zipcode"],
                $d["telephone"], $d["contact_number"], $d["email"],
                $d["Q34A"], $d["Q34B"], $d["Q34_details"], $d["Q35a"], $d["Q35b"], $d["Q35_details"], $d["Q36"], $d["Q36_details"], $d["Q37"], $d["Q37_details"],
                $d["Q38a"], $d["Q38b"], $d["Q38_details"], $d["Q39a"], $d["Q39b"], $d["Q39_details"], $d["Q40a"], $d["Q40a_details"], $d["Q40b"], $d["Q40b_details"], $d["Q40c"], $d["Q40c_details"],
                $id
            );
            
            if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
            $stmt->close();

            // Delete existing related data
            $this->conn->query("DELETE FROM `references` WHERE employees_idemployees = $id");
            $this->conn->query("DELETE FROM employees_prof_eligibility WHERE employees_idemployees = $id");
            $this->conn->query("DELETE FROM service_records WHERE employees_idemployees = $id");
            $this->conn->query("DELETE FROM employees_ext_involvements WHERE employees_idemployees = $id");
            $this->conn->query("DELETE FROM employees_has_trainings WHERE employees_idemployees = $id");
            $this->conn->query("DELETE FROM employees_education WHERE employees_idemployees = $id");
            $this->conn->query("DELETE FROM employees_relatives WHERE employees_idemployees = $id");
            $this->conn->query("DELETE FROM employees_unitassignments WHERE employees_idemployees = $id");

            $this->saveRelatedData($id, $data);

            $this->conn->commit();
            $_SESSION["success"] = "Employee updated successfully";
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $_SESSION["error"] = "Error updating employee: " . $e->getMessage();
            return false;
        }
    }

    private function saveRelatedData($empId, $data) {
        // 0. Current Employment (Department, Position, Contract)
        if (!empty($data["department_id"])) {
            // Unit Assignment
            $stmt = $this->conn->prepare("INSERT INTO employees_unitassignments (employees_idemployees, departments_iddepartments, transfer_date) VALUES (?, ?, CURDATE())");
            $stmt->bind_param("ii", $empId, $data["department_id"]);
            $stmt->execute();
            $stmt->close();

            // Service Record (Current)
            $stmt = $this->conn->prepare("INSERT INTO service_records (employees_idemployees, job_positions_idjob_positions, contract_types_idcontract_types, appointment_start_date, appointment_end_date, institutions_idinstitutions, monthly_salary, pay_grade, gov_service) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $instId = 0; // Default for internal
            $salary = !empty($data["monthly_salary"]) ? $data["monthly_salary"] : 0.0;
            $payGrade = "N/A";
            $gov = 1; // Assuming internal is gov
            $endDate = !empty($data["appointment_end_date"]) ? $data["appointment_end_date"] : "1900-01-01";
            
            $stmt->bind_param("iiissidsi", $empId, $data["position_id"], $data["contract_type_id"], $data["date_hired"], $endDate, $instId, $salary, $payGrade, $gov);
            $stmt->execute();
            $stmt->close();
        }

        // 1. References
        if (isset($data["references"]) && is_array($data["references"])) {
            $stmt = $this->conn->prepare("INSERT INTO `references` (name, address, tel_no, employees_idemployees) VALUES (?, ?, ?, ?)");
            foreach ($data["references"] as $ref) {
                if(!empty($ref["name"])) {
                    $stmt->bind_param("sssi", $ref["name"], $ref["address"], $ref["tel_no"], $empId);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // 2. Civil Service Eligibility
        if (isset($data["eligibility"]) && is_array($data["eligibility"])) {
            $stmt = $this->conn->prepare("INSERT INTO employees_prof_eligibility (professional_exams_idprofessional_exams, employees_idemployees, rating, exam_date, exam_place, license_no, license_validity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($data["eligibility"] as $elig) {
                if(!empty($elig["exam_name"])) {
                    $examId = $this->getOrCreateExam($elig["exam_name"]);
                    $validity = !empty($elig["validity"]) ? $elig["validity"] : "1900-01-01";
                    $stmt->bind_param("iidssss", $examId, $empId, $elig["rating"], $elig["date"], $elig["place"], $elig["license_number"], $validity);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // 3. Work Experience (Service Records - Past)
        if (isset($data["work_experience"]) && is_array($data["work_experience"])) {
            $stmt = $this->conn->prepare("INSERT INTO service_records (employees_idemployees, job_positions_idjob_positions, appointment_start_date, appointment_end_date, institutions_idinstitutions, monthly_salary, pay_grade, contract_types_idcontract_types, gov_service) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data["work_experience"] as $work) {
                if(!empty($work["position_title"])) {
                    $posId = $this->getOrCreateJobPosition($work["position_title"]);
                    $instId = $this->getOrCreateInstitution($work["company"]);
                    $contractId = $this->getOrCreateContractType($work["status"]);
                    $gov = isset($work["gov_service"]) && $work["gov_service"] == "Y" ? 1 : 0;
                    $salary = !empty($work["salary"]) ? $work["salary"] : 0;
                    
                    $stmt->bind_param("iissidsii", $empId, $posId, $work["from"], $work["to"], $instId, $salary, $work["pay_grade"], $contractId, $gov);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // 4. Voluntary Work
        if (isset($data["voluntary_work"]) && is_array($data["voluntary_work"])) {
            $stmt = $this->conn->prepare("INSERT INTO employees_ext_involvements (employees_idemployees, institutions_idinstitutions, involvement_type, start_date, end_date, no_hours, work_nature) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($data["voluntary_work"] as $vol) {
                if(!empty($vol["organization"])) {
                    $instId = $this->getOrCreateInstitution($vol["organization"]);
                    $type = "Voluntary";
                    $stmt->bind_param("iisssis", $empId, $instId, $type, $vol["from"], $vol["to"], $vol["hours"], $vol["position"]);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // 5. Learning & Development (Trainings)
        if (isset($data["trainings"]) && is_array($data["trainings"])) {
            $stmtTrain = $this->conn->prepare("INSERT INTO trainings (idtrainings, training_venue, training_type, start_date, end_date, training_title, institutions_idinstitutions) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtLink = $this->conn->prepare("INSERT INTO employees_has_trainings (employees_idemployees, trainings_idtrainings, participation_type) VALUES (?, ?, ?)");
            
            foreach ($data["trainings"] as $train) {
                if(!empty($train["title"])) {
                    $trainId = $this->getNextId("trainings", "idtrainings");
                    $instId = $this->getOrCreateInstitution($train["conductor"]);
                    $type = $train["type"] ?? "Training";
                    $venue = "N/A";
                    
                    $stmtTrain->bind_param("isssssi", $trainId, $venue, $type, $train["from"], $train["to"], $train["title"], $instId);
                    $stmtTrain->execute();
                    
                    $partType = "Participant";
                    $stmtLink->bind_param("iis", $empId, $trainId, $partType);
                    $stmtLink->execute();
                }
            }
            $stmtTrain->close();
            $stmtLink->close();
        }

        // 6. Relatives
        if (isset($data["rel_name"]) && is_array($data["rel_name"])) {
            $stmtRel = $this->conn->prepare("INSERT INTO relatives (idrelatives, first_name, last_name, telephone, name_extension, Occupation, Emp_business, business_address, birthdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtLink = $this->conn->prepare("INSERT INTO employees_relatives (employees_idemployees, Relatives_idrelatives, relationship) VALUES (?, ?, ?)");
            
            $names = $data["rel_name"];
            $relationships = $data["rel_relationship"];
            $contacts = $data["rel_contact"];

            for ($i = 0; $i < count($names); $i++) {
                if (empty($names[$i])) continue;
                
                $relId = $this->getNextId("relatives", "idrelatives");
                $parts = explode(" ", $names[$i], 2);
                $fname = $parts[0];
                $lname = $parts[1] ?? "";
                $contact = $contacts[$i] ?? "";
                $rel = $relationships[$i] ?? "";
                
                $def = "N/A"; $date = "1900-01-01";
                $stmtRel->bind_param("issssssss", $relId, $fname, $lname, $contact, $def, $def, $def, $def, $date);
                $stmtRel->execute();
                
                $stmtLink->bind_param("iis", $empId, $relId, $rel);
                $stmtLink->execute();
            }
            $stmtRel->close();
            $stmtLink->close();
        }

        // 7. Education
        if (isset($data["edu_school"]) && is_array($data["edu_school"])) {
            $stmtLink = $this->conn->prepare("INSERT INTO employees_education (employees_idemployees, institutions_idinstitutions, Education_degree, year_graduated, start_period, end_period, units_earned, scholarships, acad_honors) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $schools = $data["edu_school"];
            $degrees = $data["edu_degree"];
            $years = $data["edu_year"];

            for ($i = 0; $i < count($schools); $i++) {
                if (empty($schools[$i])) continue;
                
                $instId = $this->getOrCreateInstitution($schools[$i]);
                $degree = $degrees[$i] ?? "";
                $year = (is_numeric($years[$i]) ? $years[$i] : date("Y")) . "-01-01";
                $defDate = "1900-01-01"; $defInt = 0; $defStr = "N/A";
                
                $stmtLink->bind_param("iissssiss", $empId, $instId, $degree, $year, $defDate, $defDate, $defInt, $defStr, $defStr);
                $stmtLink->execute();
            }
            $stmtLink->close();
        }
    }
}
?>
