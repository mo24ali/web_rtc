<?php
require_once 'config/database.php';

echo "<h1>Database Check</h1>";

try {
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✅ Database connected successfully</p>";
    echo "<p>Tables found: " . implode(', ', $tables) . "</p>";

    if (in_array('rooms', $tables) && in_array('participants', $tables)) {
        echo "<p>✅ Required tables exist</p>";

        // Check room count
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM rooms');
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Rooms in database: " . $count['count'] . "</p>";

        // Check participant count
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM participants');
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Participants in database: " . $count['count'] . "</p>";

        echo "<p style='color: green; font-weight: bold;'>🎉 Database is ready!</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing required tables. Please run the SQL setup.</p>";
    }

} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>