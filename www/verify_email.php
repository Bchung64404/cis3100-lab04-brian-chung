<?php
require 'db.php';

$message = '';
$messageType = 'danger';

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
            
            $message = "Your email is verified. You can now sign in."; $messageType = "success";
        } else {
            $message = "This verification token is invalid or has already been used."; $messageType = "danger";
        }
    }
}

require_once __DIR__ . '/ui.php';
?>
<?php ui_auth_start('Check your inbox', 'Use the link in your welcome email, or paste your verification token below.', 2); ui_alert($message, $messageType); ?>
<form method="post">
    <div class="mb-4"><label class="form-label" for="token">Verification token</label><input class="form-control" id="token" name="token" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="Paste your email verification token" value="<?= ui_escape($tokenParam) ?>" aria-describedby="token-help" required><div class="form-text" id="token-help">You'll find this token in your account verification email.</div></div>
    <button class="btn btn-primary w-100" type="submit">Verify email <?= ui_icon('check') ?></button>
</form>
<div class="form-divider"></div><p class="auth-bottom mb-0">Ready to continue? <a class="fw-semibold" href="login.php">Sign in</a></p>
<?php ui_auth_end(); ?>