<?php
session_start();
require 'db.php';
require 'GoogleAuthenticator.php';

if (!isset($_SESSION['2fa_pending_userID'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$messageType = 'danger';

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
        $message = "That code did not match. Check your authenticator and try again."; $messageType = "danger";
    }
}

require_once __DIR__ . '/ui.php';
?>
<?php ui_auth_start('One last check', 'Open your authenticator app and enter the current six-digit code.'); ui_alert($message, $messageType); ?>
<form method="post">
    <div class="mb-4"><label class="form-label" for="pin">Authenticator code</label><input class="form-control code-input" id="pin" name="pin" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="000000" aria-describedby="pin-help" required><div class="form-text" id="pin-help">Codes change regularly. Use the one currently shown in your app.</div></div>
    <button class="btn btn-primary w-100" type="submit">Verify and sign in <?= ui_icon('arrow') ?></button>
</form>
<div class="form-divider"></div><p class="auth-bottom mb-0"><a href="login.php">Back to sign in</a></p>
<?php ui_auth_end(); ?>