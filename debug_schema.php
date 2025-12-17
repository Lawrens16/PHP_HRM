<?php
require_once 'conn.php';

function describeTable($conn, $table) {
    echo "<h3>Table: $table</h3>";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error describing $table: " . $conn->error;
    }
}

describeTable($conn, 'institutions');
describeTable($conn, 'employees_education');
?>
