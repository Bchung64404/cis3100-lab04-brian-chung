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
$message = '';
$messageType = 'danger';
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
        $message = "That code did not match. Check your device time and try again."; $messageType = "danger";
    }
}

require_once __DIR__ . '/ui.php';
?>
<?php ui_auth_start('Make it yours. Keep it yours.', 'Connect an authenticator app to finish setting up your account.', 3); ui_alert($message, $messageType); ?>
<ol class="small text-secondary ps-3 mb-3"><li class="mb-2">Open Google Authenticator or another TOTP app.</li><li>Scan this QR code, or enter the setup key manually.</li></ol>
<div class="qr-wrap mb-3"><div id="qrcode" role="img" aria-label="QR code for connecting your authenticator"></div></div>
<details class="mb-4"><summary class="small fw-semibold">Can't scan the code?</summary><p class="form-text mt-2">Add an account manually in your authenticator using this setup key. Keep it private.</p><code class="secret-key"><?= ui_escape($secretKey) ?></code></details>
<form method="post">
    <div class="mb-4"><label class="form-label" for="code">Six-digit code from your app</label><input class="form-control code-input" id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="000000" required></div>
    <button class="btn btn-primary w-100" type="submit">Finish setup <?= ui_icon('check') ?></button>
</form>
<div class="form-divider"></div><p class="auth-bottom mb-0"><a href="login.php">Back to sign in</a></p>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
if (typeof QRCode !== 'undefined') {
    new QRCode(document.getElementById('qrcode'), {text: <?= json_encode($qrCodeDataUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, width: 180, height: 180});
} else {
    document.getElementById('qrcode').textContent = 'QR code unavailable. Use the manual setup key below.';
    document.querySelector('details').open = true;
}
</script>
<?php ui_auth_end(); ?>