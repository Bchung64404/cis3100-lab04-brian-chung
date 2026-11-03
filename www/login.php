<?php
require 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    if (!empty($email) && !empty($pass)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['Password'])) {

            if ($user['EmailVerified'] == 0) {
                die("<p style='color:red; font-family:sans-serif; max-width:400px; margin:40px auto;'>❌ Login Blocked: Complete your baseline link validation first.</p>");
            }

            // Route target profile tracking tokens into temporary validation zones[cite: 1]
            $_SESSION['2fa_pending_userID'] = $user['userID'];

            // NOTE: user_id and role are intentionally omitted here 
            // and should be set only after successful 2FA verification 
            // to prevent bypassing the 2FA security checkpoint.

            // Check if user has initialized their TOTP token device profile previously[cite: 1]
            if (empty($user['TotpSecret'])) {
                header("Location: setup_2fa.php");
            } else {
                header("Location: verify_2fa.php");
            }
            exit;

        } else {
            $message = "<p style='color:red;'>Invalid email or password parameters.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>System Login Proxy</title>
</head>

<body style="font-family:sans-serif; max-width:400px; margin:40px auto; padding:20px;">

    <h2>Account Sign-In Terminal</h2>

    <?php echo $message; ?>

    <form method="POST">
        <label>Registered Email Address:</label><br>
        <input type="email" name="email" style="width:100%; padding:8px; margin:5px 0;" required><br>

        <label>Secure Password Key:</label><br>
        <input type="password" name="password" style="width:100%; padding:8px; margin:5px 0;" required><br><br>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="submit"
                style="padding:10px 15px; background:#0076ff; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">
                Request Login Handshake
            </button>
            <a href="reset.php"
                style="padding:10px 15px; background:#0076ff; color:#fff; text-decoration:none; border-radius:4px; cursor:pointer; font-size:14px; display:inline-block;">
                Reset Password
            </a>
        </div>
    </form>

    <hr style="margin:25px 0;">

    <p style="text-align:center; margin-bottom:10px;">Don't have an account?</p>
    <a href="register.php"
        style="display:block; text-align:center; padding:10px 15px; background:#0076ff; color:#fff; text-decoration:none; border-radius:4px; font-size:14px;">
        Register
    </a>

</body>

</html>