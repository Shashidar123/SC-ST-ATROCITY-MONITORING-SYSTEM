<?php
file_put_contents(__DIR__.'/../debug_ajax.txt', 'IN DB.PHP: '.date('c').PHP_EOL, FILE_APPEND);
require_once __DIR__ . '/config/db_config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'DB: after PDO connect '.date('c').PHP_EOL, FILE_APPEND);
} catch(PDOException $e) {
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'DB: PDO ERROR: '.$e->getMessage().' '.date('c').PHP_EOL, FILE_APPEND);
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}
?> 