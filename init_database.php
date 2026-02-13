<?php
require_once 'config/db_config.php';

// Initial users data
$users = [
    [
        'username' => 'police',
        'password' => 'police@123',
        'role' => 'police'
    ],
    [
        'username' => 'rdo',
        'password' => 'rdo@456',
        'role' => 'rdo'
    ],
    [
        'username' => 'c_section',
        'password' => 'c@1993',
        'role' => 'c_section'
    ],
    [
        'username' => 'collector',
        'password' => 'SecurePass123!',
        'role' => 'collector'
    ],
    [
        'username' => 'social_welfare',
        'password' => 'social_welfare@123',
        'role' => 'social_welfare'
    ]
];

try {
    // Create users
    foreach ($users as $user) {
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password = ?, role = ?");
        $stmt->execute([
            $user['username'],
            $hashedPassword,
            $user['role'],
            $hashedPassword,
            $user['role']
        ]);
    }
    
    echo "Database initialized successfully!\n";
} catch(PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
?> 