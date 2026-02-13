<?php
require_once 'config/db_config.php';

try {
    // Drop and recreate users table
    $pdo->exec("DROP TABLE IF EXISTS users");
    
    $pdo->exec("CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('police', 'sp', 'c_section', 'collector', 'social_welfare') NOT NULL,
        department VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create default users with properly hashed passwords
    $users = [
        ['police_admin', 'police', 'Police Department'],
        ['sp_admin', 'sp', 'SP Office'],
        ['c_section_admin', 'c_section', 'C-Section'],
        ['collector_admin', 'collector', 'Collector Office'],
        ['social_admin', 'social_welfare', 'Social Welfare Department']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, role, department) 
        VALUES (?, ?, ?, ?)
    ");

    foreach ($users as $user) {
        $password_hash = password_hash('password', PASSWORD_DEFAULT);
        $stmt->execute([$user[0], $password_hash, $user[1], $user[2]]);
    }

    echo "Users reset successfully! You can now login with these credentials:<br><br>";
    foreach ($users as $user) {
        echo "Username: {$user[0]}<br>";
        echo "Password: password<br>";
        echo "Role: {$user[1]}<br><br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 