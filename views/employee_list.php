<?php
require_once '../auth.php';
require_once '../conn.php';

requireLogin();
requireRole(['HR Head', 'HR Staff']);

$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $search_param = "%" . $search . "%";
    
    $sql = "SELECT e.idemployees, e.first_name, e.last_name, MAX(d.dept_name) as dept_name, MAX(p.job_category) as job_category 
            FROM employees e 
            LEFT JOIN employees_unitassignments eu ON e.idemployees = eu.employees_idemployees
            LEFT JOIN departments d ON eu.departments_iddepartments = d.iddepartments
            LEFT JOIN service_records sr ON e.idemployees = sr.employees_idemployees
            LEFT JOIN job_positions p ON sr.job_positions_idjob_positions = p.idjob_positions
            WHERE e.first_name LIKE ? OR e.last_name LIKE ? OR e.idemployees LIKE ?
            GROUP BY e.idemployees, e.first_name, e.last_name";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT e.idemployees, e.first_name, e.last_name, MAX(d.dept_name) as dept_name, MAX(p.job_category) as job_category 
            FROM employees e 
            LEFT JOIN employees_unitassignments eu ON e.idemployees = eu.employees_idemployees
            LEFT JOIN departments d ON eu.departments_iddepartments = d.iddepartments
            LEFT JOIN service_records sr ON e.idemployees = sr.employees_idemployees
            LEFT JOIN job_positions p ON sr.job_positions_idjob_positions = p.idjob_positions
            GROUP BY e.idemployees, e.first_name, e.last_name";
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee List - HRMIS</title>
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

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success">
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <h2>Employee List</h2>
        
        <form action="" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Search by Name or ID" value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
            <button type="submit" class="btn-primary">Search</button>
            <?php if(!empty($search)): ?>
                <a href="employee_list.php" class="btn-secondary" style="display: flex; align-items: center;">Reset</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['idemployees']; ?></td>
                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                            <td><?php echo $row['dept_name'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['job_category'] ?? 'N/A'; ?></td>
                            <td>
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <a href="view_employee.php?id=<?php echo $row['idemployees']; ?>" class="btn-secondary">View</a>
                                    
                                    <?php if ($_SESSION['role'] == 'HR Head' || $_SESSION['role'] == 'HR Staff'): ?>
                                        <a href="edit_employee.php?id=<?php echo $row['idemployees']; ?>" class="btn-primary" style="background-color: #ffc107; color: #000;">Edit</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role'] == 'HR Head'): ?>
                                        <a href="../actions/delete_employee.php?id=<?php echo $row['idemployees']; ?>" class="btn-danger" onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No employees found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
