<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sc_st_cases', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check cases
    $stmt = $pdo->query('SELECT * FROM cases');
    echo "Cases:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

    // Check case statuses
    $stmt = $pdo->query('SELECT * FROM case_status ORDER BY created_at DESC');
    echo "\nCase Statuses:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
} 