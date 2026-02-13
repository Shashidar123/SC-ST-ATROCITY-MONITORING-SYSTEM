<?php
require_once 'includes/config/db_config.php';

try {
    // Create database connection
    $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['dbname']);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Read the SQL file
    $sql = file_get_contents('sql/create_recipients_table.sql');
    
    // Execute the SQL commands
    if ($conn->multi_query($sql)) {
        echo "<h2>Recipients tables created successfully!</h2>";
        echo "<p>The following tables were created:</p>";
        echo "<ul>";
        echo "<li>recipients</li>";
        echo "<li>recipient_documents</li>";
        echo "</ul>";
        echo "<p><a href='social_welfare_dashboard.php'>Return to Dashboard</a></p>";
    } else {
        echo "Error creating tables: " . $conn->error;
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>