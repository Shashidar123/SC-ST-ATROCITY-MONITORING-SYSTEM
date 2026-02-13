<?php
require_once 'includes/db.php';
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
if (!$case_id) {
    echo "<form method='get'>Case ID: <input name='case_id' type='number' required><button type='submit'>Check</button></form>";
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at DESC");
$stmt->execute([$case_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h2>Files for Case ID: $case_id</h2>";
if (!$documents) {
    echo "<p>No files found for this case.</p>";
    exit;
}
echo "<table border='1' cellpadding='6'><tr><th>Document Type</th><th>File Path</th><th>Uploaded By</th><th>Uploaded At</th></tr>";
foreach ($documents as $doc) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($doc['document_type']) . "</td>";
    echo "<td>" . htmlspecialchars($doc['file_path']) . "</td>";
    echo "<td>" . htmlspecialchars($doc['uploaded_by']) . "</td>";
    echo "<td>" . htmlspecialchars($doc['created_at']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?> 