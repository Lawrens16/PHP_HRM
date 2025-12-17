<?php
require_once 'conn.php';

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    $result = $conn->query("DESCRIBE `$table`");
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
    }
}
?>
