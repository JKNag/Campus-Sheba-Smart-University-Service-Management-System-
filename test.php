<?php
require_once 'config/database.php';
echo "✅ Database connected successfully!";
echo "<br>";
echo "Database name: " . $dbname;
echo "<br>";

// Test query
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();

echo "<h3>Users in database:</h3>";
echo "<ul>";
foreach($users as $user) {
    echo "<li>" . $user['full_name'] . " - " . $user['email'] . " (" . $user['role'] . ")</li>";
}
echo "</ul>";
?>
