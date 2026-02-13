<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Update Case Status</title>
</head>
<body>
    <h2>Manual Test: Update Case Status</h2>
    <form method="POST" action="handlers/update_case_status.php">
        <label>Case ID: <input type="text" name="case_id" value="11"></label><br><br>
        <label>Status: <input type="text" name="status" value="sp_review_from_io"></label><br><br>
        <label>Comments: <input type="text" name="comments" value="Manual test"></label><br><br>
        <button type="submit">Update Status</button>
    </form>
    <p>After submitting, you will see the raw backend response here.</p>
</body>
</html> 