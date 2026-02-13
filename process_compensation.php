<?php
require_once 'includes/auth.php';
requireRole('social_welfare');

$case_id = $_GET['id'] ?? 0;
if (!$case_id) {
    header('Location: ' . getRoleRedirect($_SESSION['role']));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $status = $_POST['status'] ?? '';
        $comments = $_POST['comments'] ?? '';
        
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new Exception('Invalid status');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update compensation status
        $stmt = $pdo->prepare("
            UPDATE compensation 
            SET status = ?
            WHERE case_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$status, $case_id]);
        
        // Create case update
        $stmt = $pdo->prepare("
            INSERT INTO case_updates (case_id, status, comments, updated_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $case_id, 
            $status === 'approved' ? 'compensation_approved' : 'compensation_rejected',
            $comments,
            $_SESSION['user_id']
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect back to case view
        header('Location: view_case.php?id=' . $case_id . '&success=1');
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Get case and compensation details
try {
    $stmt = $pdo->prepare("
        SELECT c.*, comp.amount, comp.status as comp_status,
        (SELECT status FROM case_updates WHERE case_id = c.case_id ORDER BY created_at DESC LIMIT 1) as current_status
        FROM cases c
        LEFT JOIN compensation comp ON c.case_id = comp.case_id
        WHERE c.case_id = ?
        ORDER BY comp.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    if (!$case) {
        throw new Exception('Case not found');
    }
    
    if (!$case['amount']) {
        throw new Exception('No compensation has been allocated for this case');
    }
    
} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Compensation - SC/ST Case Management</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .case-details {
            margin-bottom: 20px;
        }
        .amount {
            font-size: 1.5em;
            color: #2c3e50;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Process Compensation</h1>
        
        <div class="form-container">
            <div class="case-details">
                <h2>Case #<?php echo htmlspecialchars($case['case_id']); ?></h2>
                <p>Victim: <?php echo htmlspecialchars($case['victim_name']); ?></p>
                <div class="amount">
                    Compensation Amount: ₹<?php echo number_format($case['amount'], 2); ?>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="status">Decision</label>
                    <select id="status" name="status" required>
                        <option value="">Select Decision</option>
                        <option value="approved">Approve Compensation</option>
                        <option value="rejected">Reject Compensation</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="comments">Comments</label>
                    <textarea id="comments" name="comments" rows="4" required></textarea>
                </div>
                
                <button type="submit" class="btn">Submit Decision</button>
                <a href="view_case.php?id=<?php echo $case_id; ?>" class="btn">Back to Case</a>
            </form>
        </div>
    </div>
</body>
</html> 