<?php
session_start();
require 'db.php';
require 'GoogleAuthenticator.php';

if (!isset($_SESSION['2fa_pending_userID'])) {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userPin = trim($_POST['pin']);
    $userId = $_SESSION['2fa_pending_userID'];

    $stmt = $pdo->prepare("SELECT TotpSecret, FirstName, Role FROM users WHERE userID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['2fa_pending_userID']);
        header("Location: login.php");
        exit;
    }

    $ga = new PHPGangsta_GoogleAuthenticator();

    // 1 = Allowance window for 30s drift
    $checkResult = $ga->verifyCode($user['TotpSecret'], $userPin, 1);

    if ($checkResult) {
        session_regenerate_id(true);   // prevents session fixation

        $update = $pdo->prepare("UPDATE users SET LoginDatetime = NOW(), Status = 1 WHERE userID = ?");
        $update->execute([$userId]);

        $_SESSION['userID'] = $userId;
        $_SESSION['FirstName'] = $user['FirstName'];
        $_SESSION['Role'] = $user['Role'];
        unset($_SESSION['2fa_pending_userID']);

        header("Location: dashboard.php");
        exit;
    } else {
        $message = "<p style='color:red; font-weight:bold;'>Access Denied: Invalid configuration key or verification PIN expired.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MFA Checkpoint</title>
</head>

<body
    style="font-family: sans-serif; max-width: 400px; margin: 40px auto; padding: 20px; text-align: center; line-height: 1.6;">
    <h2>Device Authenticator Checkpoint</h2>
    <p>Open your mobile verification device application to capture your current dynamic login passkey string:</p>

    <?php echo $message; ?>

    <form method="POST">
        <input type="text" name="pin" maxlength="6" required autocomplete="off" placeholder="Enter 6-digit PIN"
            style="padding: 10px; font-size: 16px; text-align: center; width: 80%; margin-bottom: 15px;">
        <br>
        <button type="submit" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Verify PIN</button>
    </form>
</body>

</html>