<?php
// Database configuration
$db_config = [
    'host' => 'localhost',
    'dbname' => 'sc_st_cases',
    'username' => 'root',
    'password' => ''
];

// Only create connection if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['dbname']}",
            $db_config['username'],
            $db_config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?> 