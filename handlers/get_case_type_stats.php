<?php
require_once '../includes/auth.php';
requireRole('collector');
require_once '../includes/db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT case_type, COUNT(*) as count FROM cases GROUP BY case_type");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} 