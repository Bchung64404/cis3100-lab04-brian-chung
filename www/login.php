<?php
require 'db.php';
require_once __DIR__ . '/ui.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($email !== '' && $pass !== '') {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE Email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['Password'])) {
            if ($user['EmailVerified'] == 0) {
                $message = 'Please verify your email before signing in. Use the link in your welcome email.';
            } else {
                // Full authentication is established only after the authenticator check.
                $_SESSION['2fa_pending_userID'] = $user['userID'];
                header('Location: ' . (empty($user['TotpSecret']) ? 'setup_2fa.php' : 'verify_2fa.php'));
                exit;
            }
        } else {
            $message = 'The email or password did not match. Please try again.';
        }
    } else {
        $message = 'Enter your email address and password to continue.';
    }
}
ui_auth_start('Good to see you.', 'Sign in to your account. Pick up right where you left off.');
ui_alert($message);
?>
<form method="post">
    <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" type="email" id="email" name="email" autocomplete="username" placeholder="you@example.com" value="<?= ui_escape($_POST['email'] ?? '') ?>" required></div>
    <div class="mb-4"><div class="d-flex align-items-center justify-content-between gap-2"><label class="form-label" for="password">Password</label><a class="form-link mb-2" href="reset.php">Forgot password?</a></div><input class="form-control" type="password" id="password" name="password" autocomplete="current-password" placeholder="Enter your password" required></div>
    <button class="btn btn-primary w-100" type="submit">Sign in <?= ui_icon('arrow') ?></button>
    <p class="form-text d-flex align-items-center justify-content-center gap-2 mt-3 mb-0"><?= ui_icon('lock') ?> Your authenticator code comes next. THIS IS CI/CD TEST 1</p>
</form>
<div class="form-divider"></div><p class="auth-bottom mb-3">New here? <a class="fw-semibold" href="register.php">Create an account</a></p>
<p class="text-center small mb-0"><a href="verify_email.php">Need to verify your email?</a></p>
<?php ui_auth_end(); ?>