   <?php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);

   try {
       require_once 'config/db_config.php';
       echo "<h2>Database connection successful!</h2>";
       
       // Test query to verify database setup
       $stmt = $pdo->query("SHOW TABLES");
       $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
       
       echo "<h3>Available tables:</h3>";
       echo "<ul>";
       foreach ($tables as $table) {
           echo "<li>" . htmlspecialchars($table) . "</li>";
       }
       echo "</ul>";
       
   } catch(PDOException $e) {
       echo "<h2>Connection failed!</h2>";
       echo "Error: " . htmlspecialchars($e->getMessage());
   }
   ?>