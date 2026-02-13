<?php
// Test all user passwords from the database
$users = [
    ['police', '$2y$10$3d/YEUDgNlpEb4IwUlZKzup1v41zMcb4ed.akldKpffy//8aC9hFa', 'police'],
    ['rdo', '$2y$10$LuXzniiMvTccK788KEKREuViVLa1G3EwN0409IMVwfblQywYKSHoy', 'rdo'],
    ['c_section', '$2y$10$/YK5puW08TMDj8eumY1Sye0TDYCN9.6J7g3gatK8mt7V512sTYJRC', 'c_section'],
    ['collector', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'collector'],
    ['social_welfare', '$2y$10$nCegL0gaXqSvpLGKVIxCWefuaKF60pYg6G8TbPuG2QpJ5Fl70ePlO', 'social_welfare'],
    ['dcr_user', '$2y$10$VwCXqxA/Tbqn3evBLIVvB..uKnP2.uFhxjj.1wK/kWo5T2cnu/nP.', 'dcr'],
    ['io_user1', '$2y$10$CfE4GzAwJ6IUCywwtdwp..xqxrgbGfUKLEYs1NDaKX2tsLuBX/dgS', 'io'],
];

// Passwords to test for each user
$password_candidates = [
    'police' => ['police@123', 'password', 'police@1'],
    'rdo' => ['rdo@456', 'password', 'rdo@123'],
    'c_section' => ['c@1993', 'password', 'c@123'],
    'collector' => ['password', 'SecurePass123!', 'police@123'],
    'social_welfare' => ['social_welfare@123', 'password', 'social@123'],
    'dcr_user' => ['dcr@123', 'password', 'dcr@456'],
    'io_user1' => ['io@123', 'password', 'io@456'],
];

echo "Testing all user passwords from database...\n\n";

$results = [];

foreach ($users as $user_data) {
    $username = $user_data[0];
    $hash = $user_data[1];
    $role = $user_data[2];
    
    $found = false;
    $candidates = $password_candidates[$username] ?? ['password'];
    
    foreach ($candidates as $pwd) {
        if (password_verify($pwd, $hash)) {
            $results[] = [
                'role' => $role,
                'username' => $username,
                'password' => $pwd,
                'length' => strlen($pwd)
            ];
            $found = true;
            echo "✓ $username ($role): '$pwd' (length: " . strlen($pwd) . ")\n";
            break;
        }
    }
    
    if (!$found) {
        echo "✗ $username ($role): Password not found in candidates\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY TABLE:\n";
echo str_repeat("=", 80) . "\n";
echo sprintf("%-20s | %-20s | %-25s | %s\n", "ROLE", "USERNAME", "PASSWORD", "LENGTH");
echo str_repeat("-", 80) . "\n";

foreach ($results as $r) {
    echo sprintf("%-20s | %-20s | %-25s | %d\n", $r['role'], $r['username'], $r['password'], $r['length']);
}
?>


