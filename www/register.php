<?php
require 'db.php';
require 'mailer.php';

$message = '';
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['firstname']);
    $last = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    if (!empty($first) && !empty($last) && !empty($email) && !empty($pass)) {
        try {
            $stmt = $pdo->prepare("SELECT userID FROM users WHERE Email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $message = "An account with this email already exists. Sign in to continue."; $messageType = "danger";
            } else {
                $hashedPass = password_hash($pass, PASSWORD_BCRYPT);
                // Extra Credit Feature: Create a unique hex security token payload
                $verificationToken = bin2hex(random_bytes(16));

                $insert = $pdo->prepare("INSERT INTO users (FirstName, LastName, Email, Password, Status, EmailVerified, EmailVerificationToken, RegDatetime) VALUES (?, ?, ?, ?, 0, 0, ?, NOW())");
                $insert->execute([$first, $last, $email, $hashedPass, $verificationToken]);

                $verifyLink = "http://localhost:8080/verify_email.php?token=" . $verificationToken;
                $emailSubject = "Activate Your Docker Core Account Profile";
                $emailContent = "
                    <h3>Welcome to the 3-Tier Laboratory Stack, {$first}!</h3>
                    <p>Please click the secure network connection verification link below to authorize your device profile:</p>
                    <p><a href='{$verifyLink}' style='background:#0076ff; color:#fff; padding:10px; text-decoration:none; border-radius:4px; display:inline-block;'>Verify My Email Address</a></p>
                    <br>
                    <small>Token verification hash signature: <code>{$verificationToken}</code></small>
                ";
                if (sendSystemMail($email, "{$first} {$last}", $emailSubject, $emailContent)) {
                    $message = "Your account is created. Check your inbox for a verification link."; $messageType = "success";
                } else {
                    $message = "Your account was created, but the verification email could not be sent. Please try again later."; $messageType = "warning";
                }
            }
        } catch (\PDOException $e) {
            error_log('Registration failed: ' . $e->getMessage());
            $message = "We could not create your account. Please try again later.";
            $messageType = "danger";
        }
    } else {
        $message = "All fields are required."; $messageType = "warning";
    }
}

require_once __DIR__ . '/ui.php';
?>
<?php ui_auth_start('Create your account', 'A few details now. Your own space in a moment.', 1); ui_alert($message, $messageType); ?>
<form method="post">
    <div class="row g-3 mb-3">
        <div class="col-sm-6"><label class="form-label" for="firstname">First name</label><input class="form-control" id="firstname" name="firstname" autocomplete="given-name" maxlength="100" placeholder="First name" value="<?= ui_escape($_POST['firstname'] ?? '') ?>" required></div>
        <div class="col-sm-6"><label class="form-label" for="lastname">Last name</label><input class="form-control" id="lastname" name="lastname" autocomplete="family-name" maxlength="100" placeholder="Last name" value="<?= ui_escape($_POST['lastname'] ?? '') ?>" required></div>
    </div>
    <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" type="email" id="email" name="email" autocomplete="email" maxlength="255" placeholder="you@example.com" value="<?= ui_escape($_POST['email'] ?? '') ?>" aria-describedby="email-help" required><div id="email-help" class="form-text">We'll send a link to confirm your email.</div></div>
    <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" type="password" id="password" name="password" autocomplete="new-password" placeholder="Create a password" required></div>
    <button class="btn btn-primary w-100" type="submit">Create account <?= ui_icon('arrow') ?></button>
</form>
<div class="form-divider"></div><p class="auth-bottom mb-3">Already have an account? <a class="fw-semibold" href="login.php">Sign in</a></p>
<p class="text-center small mb-0"><a href="verify_email.php">Have a verification token?</a></p>
<?php ui_auth_end(); ?>
