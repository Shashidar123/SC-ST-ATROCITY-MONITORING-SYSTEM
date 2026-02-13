<?php
// Test script to find the collector password
$collector_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Common 8-character passwords to test
$passwords_to_test = [
    'password',      // 8 chars
    'collector',     // 9 chars (but let's test anyway)
    'collect1',      // 8 chars
    'collect2',      // 8 chars
    'police@1',      // 8 chars
    'collect@',      // 9 chars
    '12345678',      // 8 chars
    'Password',      // 8 chars
    'PASSWORD',      // 8 chars
    'collect1',      // 8 chars
    'collect2',      // 8 chars
    'collect3',      // 8 chars
    'collect4',      // 8 chars
    'collect5',      // 8 chars
    'collect6',      // 8 chars
    'collect7',      // 8 chars
    'collect8',      // 8 chars
    'collect9',      // 8 chars
    'collect0',      // 8 chars
    'police@1',      // 8 chars
    'police@2',      // 8 chars
    'police@3',      // 8 chars
    'rdo@1234',      // 8 chars
    'c@12345',       // 7 chars (but let's test)
    'c@123456',      // 8 chars
];

echo "Testing collector password hash...\n";
echo "Hash: $collector_hash\n\n";

foreach ($passwords_to_test as $pwd) {
    if (password_verify($pwd, $collector_hash)) {
        echo "✓ MATCH FOUND! Password: '$pwd' (length: " . strlen($pwd) . ")\n";
        break;
    } else {
        echo "✗ '$pwd' (length: " . strlen($pwd) . ") - No match\n";
    }
}

// Also test the known passwords from setup files
echo "\n--- Testing known passwords from setup files ---\n";
$known_passwords = [
    'police@123',
    'SecurePass123!',
    'password',
    'police@1',
];

foreach ($known_passwords as $pwd) {
    if (password_verify($pwd, $collector_hash)) {
        echo "✓ MATCH FOUND! Password: '$pwd' (length: " . strlen($pwd) . ")\n";
    } else {
        echo "✗ '$pwd' (length: " . strlen($pwd) . ") - No match\n";
    }
}
?>


