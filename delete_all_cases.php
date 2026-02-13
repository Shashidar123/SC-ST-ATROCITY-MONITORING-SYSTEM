<?php
require 'includes/db.php';
$pdo->exec('DELETE FROM case_status');
$pdo->exec('DELETE FROM case_documents');
$pdo->exec('DELETE FROM recommendations');
$pdo->exec('DELETE FROM compensation_recommendations');
$pdo->exec('DELETE FROM investigation_reports');
$pdo->exec('DELETE FROM cases');
echo 'All cases and related data deleted.'; 