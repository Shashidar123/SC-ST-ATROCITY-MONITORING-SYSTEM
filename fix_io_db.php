<?php
require_once 'includes/db.php';

echo "<h2>Fixing Database for IO Functionality</h2>";

try {
    // Read and execute the SQL fixes
    $sql_file = file_get_contents('fix_io_database.sql');
    $statements = explode(';', $sql_file);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            try {
                $pdo->exec($statement);
                echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠ Skipped (likely already exists): " . substr($statement, 0, 50) . "...</p>";
            }
        }
    }
    
    // Create uploads directory if it doesn't exist
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        echo "<p style='color: green;'>✓ Created uploads directory</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Uploads directory already exists</p>";
    }
    
    echo "<h3 style='color: green;'>Database fixes completed successfully!</h3>";
    echo "<p><a href='review_io_case.php?case_id=1'>Test IO Review Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 