<?php
require_once 'includes/auth.php';
requireRole('c_section');

$case_id = $_GET['id'] ?? 0;
if (!$case_id) {
    header('Location: ' . getRoleRedirect($_SESSION['role']));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $amount = $_POST['amount'] ?? 0;
        $comments = $_POST['comments'] ?? '';
        
        if (!$amount || $amount <= 0) {
            throw new Exception('Please enter a valid compensation amount');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Create compensation record
        $stmt = $pdo->prepare("
            INSERT INTO compensation (case_id, amount, status, approved_by)
            VALUES (?, ?, 'pending', ?)
        ");
        $stmt->execute([$case_id, $amount, $_SESSION['user_id']]);
        
        // Update case status
        $stmt = $pdo->prepare("
            INSERT INTO case_updates (case_id, status, comments, updated_by)
            VALUES (?, 'compensation_allocated', ?, ?)
        ");
        $stmt->execute([$case_id, $comments, $_SESSION['user_id']]);
        
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

// Get case details
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
        (SELECT status FROM case_updates WHERE case_id = c.case_id ORDER BY created_at DESC LIMIT 1) as current_status
        FROM cases c
        WHERE c.case_id = ?
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    if (!$case) {
        throw new Exception('Case not found');
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
    <title>Allocate Compensation - SC/ST Case Management</title>
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
        input[type="number"],
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
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Allocate Compensation</h1>
        
        <div class="form-container">
            <h2>Case #<?php echo htmlspecialchars($case['case_id']); ?></h2>
            <p>Victim: <?php echo htmlspecialchars($case['victim_name']); ?></p>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="amount">Compensation Amount (₹)</label>
                    <input type="number" id="amount" name="amount" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="comments">Comments</label>
                    <textarea id="comments" name="comments" rows="4"></textarea>
                </div>
                
                <button type="submit" class="btn">Allocate Compensation</button>
                <a href="view_case.php?id=<?php echo $case_id; ?>" class="btn">Back to Case</a>
            </form>
        </div>
    </div>
</body>
</html> 