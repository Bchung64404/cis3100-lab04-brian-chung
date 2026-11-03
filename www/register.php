<?php
require 'db.php';
require 'mailer.php';

$message = "";

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
                $message = "<p style='color:red;'>Email already registered!</p>";
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
                    $message = "<p style='color:green;'>Registration successful! A real verification link has been dispatched. Check your Mailtrap inbox to validate your access profile.</p>";
                } else {
                    $message = "<p style='color:orange;'>Profile built, but system failed to transmit verification message wrapper.</p>";
                }
            }
        } catch (\PDOException $e) {
            $message = "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
    } else {
        $message = "<p style='color:orange;'>All fields are required.</p>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register & Verify Stack</title>
</head>

<body style="font-family:sans-serif; max-width:400px; margin:40px auto; padding:20px; line-height:1.6;">

    <h2>Step 1: Create Account Portal</h2>

    <?php echo $message; ?>

    <form method="POST">
        <label>First Name:</label><br>
        <input type="text" name="firstname" style="width:100%; padding:8px; margin:5px 0;" required><br>

        <label>Last Name:</label><br>
        <input type="text" name="lastname" style="width:100%; padding:8px; margin:5px 0;" required><br>

        <label>Email Address:</label><br>
        <input type="email" name="email" style="width:100%; padding:8px; margin:5px 0;" required><br>

        <label>Password Asset:</label><br>
        <input type="password" name="password" style="width:100%; padding:8px; margin:5px 0;" required><br><br>

        <button type="submit"
            style="padding:10px 15px; background:#0076ff; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Register
            Core Account</button>
    </form>

    <hr>

    <p style="text-align:center; margin-bottom:10px;">Already have an account?</p>
    <a href="login.php"
        style="display:block; text-align:center; padding:10px 15px; background:#0076ff; color:#fff; text-decoration:none; border-radius:4px; font-size:14px; font-weight:bold;">
        Login
    </a>

    <p>Looking to verify an existing link? <a href="verify_email.php">Manual Verification Input Terminal</a></p>

</body>

</html>