<?php
require 'db.php';

$message = '';
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['email']);
    $newPass = $_POST['new_password'];

    if (!empty($email) && !empty($newPass)) {
        $stmt = $pdo->prepare("SELECT userID FROM users WHERE Email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $update  = $pdo->prepare("UPDATE users SET Password = ? WHERE Email = ?");
            $update->execute([$newHash, $email]);

            $message = "Your password has been updated. You can now sign in."; $messageType = "success";
        } else {
            $message = "No account found with that email address."; $messageType = "danger";
        }
    }
}

require_once __DIR__ . '/ui.php';
?>
<?php ui_auth_start('A fresh start', 'Update your password and get back to your account.'); ui_alert($message, $messageType); ?>
<form method="post">
    <div class="mb-3"><label class="form-label" for="email">Account email</label><input class="form-control" type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" value="<?= ui_escape($_POST['email'] ?? '') ?>" required></div>
    <div class="mb-4"><label class="form-label" for="new_password">New password</label><input class="form-control" type="password" id="new_password" name="new_password" autocomplete="new-password" placeholder="Enter a new password" required></div>
    <button class="btn btn-primary w-100" type="submit">Update password <?= ui_icon('arrow') ?></button>
</form>
<div class="form-divider"></div><p class="auth-bottom mb-0"><a href="login.php">Back to sign in</a></p>
<?php ui_auth_end(); ?>