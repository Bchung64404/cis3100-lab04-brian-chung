<?php
require 'db.php';
require 'GoogleAuthenticator.php';
// Route out to login if the session profile context isn't set yet
if (!isset($_SESSION['2fa_pending_userID'])) {
    header("Location: login.php");
    exit;
}
$userId = $_SESSION['2fa_pending_userID'];
$ga = new PHPGangsta_GoogleAuthenticator();
$message = "";
// 1. Fetch user email to populate the authenticator UI label
$stmt = $pdo->prepare("SELECT Email, TotpSecret FROM users WHERE userID = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
// 2. Generate a permanent secret key if one does not exist yet
if (empty($user['TotpSecret'])) {
    $secretKey = $ga->createSecret();
    $update = $pdo->prepare("UPDATE users SET TotpSecret = ? WHERE userID = ?");
    $update->execute([$secretKey, $userId]);
} else {
    $secretKey = $user['TotpSecret'];
}
// 3. Construct the official RFC-6238 TOTP URI profile parameter string
$appName = "DockerLab-3Tier";
$qrCodeDataUrl =
    "otpauth://totp/{$appName}:{$user['Email']}?secret={$secretKey}&issuer={$appName}";
// 4. Validate a 6-digit test submission before activating the device layer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeAttempt = trim($_POST['code']);
    // Check code against the current clock cycle (includes a grace window of 1 interval)
    $checkResult = $ga->verifyCode($secretKey, $codeAttempt, 1);
    if ($checkResult) {
        // Activate full authentication metrics state variables
        $update = $pdo->prepare("UPDATE users SET LoginDatetime = NOW(), Status = 1
WHERE userID = ?");
        $update->execute([$userId]);
        $_SESSION['userID'] = $userId;
        unset($_SESSION['2fa_pending_userID']);
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "<p style='color:red; font-weight:bold;'>￿ Verification Failed: PIN does not
match clock signature. Sync your device time and try again.</p>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Authenticator Setup</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: sans-serif;
            max-width: 450px;
            margin: 40px auto;
            padding: 20px;
            line-height: 1.6;
            text-align: center;
        }

        .qr-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px dashed #ccc;
        }

        input[type="text"] {
            width: 80%;
            padding: 10px;
            font-size: 18px;
            letter-spacing: 4px;
            text- align: center;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin: 10px 0;
        }

        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size:
                16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            width: 84%;
        }
    </style>
</head>

<body>
    <h2>Lock Device Configuration</h2>
    <p>Scan the code wrapper below using <strong>Google Authenticator</strong> or any
        TOTP terminal application:</p>
    <div class="qr-container">
        <div id="qrcode"></div>
    </div>
    <p style="font-size: 13px; color: #666;">Manual Secret Key Configuration: <br><code
            style="font-size:15px; font-weight:bold; color:#0076ff;"><?php echo $secretKey;
            ?></code></p>
    <?php echo $message; ?>
    <form method="POST">
        <label><strong>Enter the 6-Digit Generated App Verification
                Code:</strong></label><br>
        <input type="text" name="code" maxlength="6" required autocomplete="off" placeholder="000000"><br><br>
        <button type="submit" class="btn">Verify and Complete Handshake</button>
    </form>
    <!-- Client-Side Javascript Injection Core Hook -->
    <script type="text/javascript">
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $qrCodeDataUrl; ?>",
            width: 200,
            height: 200
        });
    </script>
</body>

</html>