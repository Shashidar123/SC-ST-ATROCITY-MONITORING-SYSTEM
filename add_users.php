<?php
require_once 'config/db_config.php';

// Initial users data
$users = [
    [
        'username' => 'police',
        'password' => password_hash('police@123', PASSWORD_DEFAULT),
        'role' => 'police'
    ],
    [
        'username' => 'rdo',
        'password' => password_hash('rdo@456', PASSWORD_DEFAULT),
        'role' => 'rdo'
    ],
    [
        'username' => 'c_section',
        'password' => password_hash('c@1993', PASSWORD_DEFAULT),
        'role' => 'c_section'
    ],
    [
        'username' => 'collector',
        'password' => password_hash('SecurePass123!', PASSWORD_DEFAULT),
        'role' => 'collector'
    ],
    [
        'username' => 'social_welfare',
        'password' => password_hash('social_welfare@123', PASSWORD_DEFAULT),
        'role' => 'social_welfare'
    ]
];

try {
    foreach ($users as $user) {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$user['username']]);
        
        if (!$stmt->fetch()) {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([
                $user['username'],
                $user['password'],
                $user['role']
            ]);
            echo "Created user: " . $user['username'] . "\n";
        } else {
            echo "User already exists: " . $user['username'] . "\n";
        }
    }
    
    echo "\nUser setup completed successfully!\n";
    echo "\nYou can now login with these credentials:\n";
    echo "Police: username=police, password=police@123\n";
    echo "RDO: username=rdo, password=rdo@456\n";
    echo "C-Section: username=c_section, password=c@1993\n";
    echo "Collector: username=collector, password=SecurePass123!\n";
    echo "Social Welfare: username=social_welfare, password=social_welfare@123\n";
    
} catch(PDOException $e) {
    die("Error setting up users: " . $e->getMessage() . "\n");
}
?> 