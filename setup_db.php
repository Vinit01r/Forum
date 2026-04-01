<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'forumhub');

try {
    // 1. Connect without selecting a database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL successfully...\n";
    
    // 2. Create the database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
    echo "Database '" . DB_NAME . "' created (or already exists)...\n";
    
    // 3. Select the new database
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // 4. Read the db.sql file
    $sql_file = __DIR__ . '/db.sql';
    if (!file_exists($sql_file)) {
        die("Error: db.sql file not found at " . $sql_file);
    }
    
    $sql = file_get_contents($sql_file);
    
    // 5. Execute the SQL statements
    $pdo->exec($sql);
    
    echo "Setup complete! The database schema and seed data have been imported successfully.\n";
    
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
