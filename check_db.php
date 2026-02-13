<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sc_st_cases', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check case_status table structure
    echo "Case Status Table Structure:\n";
    $stmt = $pdo->query('DESCRIBE case_status');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

    // Check case_status data
    echo "\nCase Status Data:\n";
    $stmt = $pdo->query('SELECT * FROM case_status ORDER BY created_at DESC');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

    // Check cases table structure
    echo "\nCases Table Structure:\n";
    $stmt = $pdo->query('DESCRIBE cases');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

    // Check cases data
    echo "\nCases Data:\n";
    $stmt = $pdo->query('SELECT * FROM cases');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
} 