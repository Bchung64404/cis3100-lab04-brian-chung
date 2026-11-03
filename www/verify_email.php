<?php
require 'db.php';

$message = "";

// Auto-catch parameters if passed inside an active query link string
$tokenParam = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($tokenParam)) {
    $token = !empty($tokenParam) ? trim($tokenParam) : trim($_POST['token']);

    if (!empty($token)) {
        $stmt = $pdo->prepare("SELECT userID FROM users WHERE EmailVerificationToken = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Null out token value and update validation metrics flag
            $update = $pdo->prepare("UPDATE users SET EmailVerified = 1, EmailVerificationToken = NULL WHERE userID = ?");
            $update->execute([$user['userID']]);
            
            $message = "<p style='color:green; font-weight:bold;'>✔ Success: Email authenticated cleanly! You may now navigate to the <a href='login.php'>Login Terminal</a>.</p>";
        } else {
            $message = "<p style='color:red;'>✖ Error: Invalid, structural mismatch, or expired verification token profile.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activation Core Terminal</title>
</head>
<body style="font-family:sans-serif; max-width:400px; margin:40px auto; padding:20px;">

    <h2>Step 2: Email Authentication Verification</h2>

    <?php echo $message; ?>

    <form method="POST">
        <label>Paste Simulated Email Token String:</label><br>
        <input type="text" name="token" value="<?php echo htmlspecialchars($tokenParam); ?>" style="width:100%; padding:8px; margin:10px 0;" required><br>
        <button type="submit" style="padding:10px 15px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;">Verify Email Authenticity</button>
    </form>

</body>
</html>