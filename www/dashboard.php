<?php
require 'db.php';
require_once __DIR__ . '/ui.php';
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM users WHERE userID = ?');
$stmt->execute([$_SESSION['userID']]);
$profile = $stmt->fetch();
if (!$profile) {
    unset($_SESSION['userID']);
    header('Location: login.php');
    exit;
}
$initial = preg_match('/^./us', $profile['FirstName'], $initialMatch) ? strtoupper($initialMatch[0]) : '';
ui_header('Your overview', $profile);
?>
<main id="main-content" class="container dashboard-layout">
    <section class="dashboard-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><span class="eyebrow">YOUR OVERVIEW</span><span class="status-badge"><span class="status-dot"></span> Signed in</span></div>
        <div class="d-flex align-items-center gap-3"><div class="profile-avatar" aria-hidden="true"><?= ui_escape($initial) ?></div><div><h1 class="mb-2">Welcome, <?= ui_escape($profile['FirstName']) ?>.</h1><p class="mb-0 text-secondary">A little overview of your account, all in one place.</p></div></div>
    </section>
    <div class="row g-4">
        <section class="col-lg-7"><div class="surface-card"><div class="d-flex justify-content-between align-items-center gap-3"><h2 class="mb-0">Your profile</h2><span class="badge rounded-pill text-bg-light border"><?= ui_escape(ucfirst($profile['Role'])) ?></span></div>
            <dl class="detail-list"><div><dt>Full name</dt><dd><?= ui_escape($profile['FirstName'] . ' ' . $profile['LastName']) ?></dd></div><div><dt>Email address</dt><dd><?= ui_escape($profile['Email']) ?></dd></div><div><dt>Member since</dt><dd><?= ui_escape($profile['RegDatetime']) ?></dd></div><div><dt>Account ID</dt><dd>#<?= ui_escape($profile['userID']) ?></dd></div></dl>
        </div></section>
        <section class="col-lg-5"><div class="surface-card"><h2 class="mb-2">Sign-in &amp; security</h2><p class="small text-secondary mb-2">Your account at a glance.</p>
            <div class="verification-row"><span class="verification-icon"><?= ui_icon('mail') ?></span><div><span class="small fw-semibold d-block">Email address</span><span class="small text-secondary"><?= $profile['EmailVerified'] ? 'Verified' : 'Awaiting verification' ?></span></div><?php if ($profile['EmailVerified']): ?><span class="ms-auto text-success"><?= ui_icon('check') ?></span><?php endif; ?></div>
            <div class="verification-row"><span class="verification-icon"><?= ui_icon('lock') ?></span><div><span class="small fw-semibold d-block">Authenticator app</span><span class="small text-secondary"><?= empty($profile['TotpSecret']) ? 'Not connected' : 'Connected' ?></span></div><?php if (!empty($profile['TotpSecret'])): ?><span class="ms-auto text-success"><?= ui_icon('check') ?></span><?php endif; ?></div>
            <dl class="detail-list mt-2"><div><dt>Last sign-in</dt><dd><?= ui_escape($profile['LoginDatetime'] ?? 'No sign-in recorded') ?></dd></div><div><dt>Previous sign-out</dt><dd><?= ui_escape($profile['LogoutDatetime'] ?? 'No sign-out recorded') ?></dd></div></dl>
        </div></section>
    </div>
    <?php if ($profile['Role'] === 'admin'): ?><section class="surface-card mt-4 d-flex flex-wrap align-items-center justify-content-between gap-3"><div><h2 class="mb-1">Account administration</h2><p class="small text-secondary mb-0">Review accounts and manage access permissions.</p></div><a class="btn btn-outline-primary" href="admin.php">Open admin <?= ui_icon('arrow') ?></a></section><?php endif; ?>
</main>
<?php ui_footer(); ?>
