<?php
require 'db.php';

$message = "";

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

            $message = "<p style='color:green;'>Password updated successfully! <a href='login.php'>Login here</a></p>";
        } else {
            $message = "<p style='color:red;'>No account found with that email address.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body style="font-family: sans-serif; max-width: 400px; margin: 40px auto;">

    <h2>Reset Account Password</h2>

    <?php echo $message; ?>

    <form method="POST">
        <label for="email">Enter Account Email:</label><br>
        <input type="email" id="email" name="email" style="width: 100%; padding: 8px; margin: 5px 0;" required><br>

        <label for="new_password">Enter New Password:</label><br>
        <input type="password" id="new_password" name="new_password" style="width: 100%; padding: 8px; margin: 5px 0;" required><br><br>

        <button type="submit" style="padding: 10px 15px; background: #666; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            Update Password Hash
        </button>
    </form>

    <p><a href="login.php">Return to Login</a></p>

</body>
</html>