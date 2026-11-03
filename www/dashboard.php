<?php
require 'db.php';

// Route back to login if no active session is found
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

// Fetch fresh profile state to see live table tracking markers
$stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
$stmt->execute([$_SESSION['userID']]);
$profile = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 40px auto;">

    <h2>Welcome, <?php echo htmlspecialchars($profile['FirstName']) . " " . htmlspecialchars($profile['LastName']); ?>!</h2>

    <p style="color: green; font-weight: bold;">
        Status: Session Authenticated (Status Code: <?php echo htmlspecialchars($profile['Status']); ?>)
    </p>

    <div style="background: #f4f4f4; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h3>System Diagnostics Metrics:</h3>
        <ul>
            <li><strong>User Account ID:</strong> <?php echo htmlspecialchars($profile['userID']); ?></li>
            <li><strong>Registered On:</strong> <?php echo htmlspecialchars($profile['RegDatetime']); ?></li>
            <li><strong>Last Login Captured:</strong> <?php echo htmlspecialchars($profile['LoginDatetime']); ?></li>
            <li><strong>Previous Logout Captured:</strong> <?php echo htmlspecialchars($profile['LogoutDatetime'] ?? 'Never Logged Out'); ?></li>
        </ul>
    </div>

    <a href="logout.php" style="padding: 10px 15px; background: #ff3b30; color: #fff; text-decoration: none; border-radius: 4px;">Secure Logout</a>

</body>
</html>