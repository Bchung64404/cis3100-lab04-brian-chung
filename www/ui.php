<?php
// Shared presentation only; authentication and database access stay in each page.
function ui_escape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function ui_icon(string $name): string
{
    $paths = [
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
    ];
    return '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['arrow']) . '</svg>';
}

function ui_header(string $title, ?array $profile = null): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= ui_escape($title) ?> | Account Portal</title>
        <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
        <link rel="stylesheet" href="assets/app.css">
    </head>
    <body>
    <a class="visually-hidden-focusable skip-link" href="#main-content">Skip to content</a>
    <header class="site-header">
        <nav class="container d-flex align-items-center justify-content-between gap-3 py-3" aria-label="Main navigation">
            <a class="brand" href="<?= $profile ? 'dashboard.php' : 'login.php' ?>"><span class="brand-mark" aria-hidden="true">a<span>.</span></span><span>account<span class="brand-light">portal</span></span></a>
            <div class="d-flex align-items-center gap-2 gap-sm-4">
                <?php if ($profile): ?>
                    <?php if (($profile['Role'] ?? '') === 'admin'): ?><a class="nav-link small fw-semibold" href="admin.php">Admin</a><?php endif; ?>
                    <a class="btn btn-outline-primary btn-sm" href="logout.php">Sign out</a>
                <?php else: ?>
                    <a class="nav-link small fw-semibold" href="login.php">Sign in</a>
                    <a class="btn btn-primary btn-sm" href="register.php"><span class="d-sm-none">Join</span><span class="d-none d-sm-inline">Get started</span><?= ui_icon('arrow') ?></a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <?php
}

function ui_auth_start(string $title, string $subtitle, int $step = 0): void
{
    ui_header($title);
    ?>
    <main id="main-content" class="container auth-layout">
        <div class="row g-4 g-xl-5 align-items-stretch">
            <aside class="col-lg-5 d-none d-lg-block">
                <div class="welcome-panel h-100">
                    <div class="eyebrow"><span class="status-dot"></span> YOUR PERSONAL SPACE</div>
                    <h2>Your account.<br>A little more<br><span>connected.</span></h2>
                    <p class="welcome-copy">A simple place to manage your profile and keep your account in your hands.</p>
                    <div class="journey" aria-label="Account setup steps">
                        <?php $number = 0; foreach (['Create your account' => 'Start with a few details.', 'Verify your email' => 'Confirm it is really you.', 'Set up your authenticator' => 'Add another layer to sign-in.'] as $label => $copy): $number++; ?>
                            <div class="journey-item <?= $step === $number ? 'is-current' : '' ?>">
                                <span class="journey-number"><?= $number ?></span><div><span class="fw-semibold d-block"><?= $label ?></span><small><?= $copy ?></small></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="welcome-note"><?= ui_icon('lock') ?><span>Your next step starts here.</span></div>
                </div>
            </aside>
            <section class="col-lg-7">
                <div class="auth-panel h-100">
                    <div class="section-icon mb-4"><?= ui_icon($step === 2 ? 'mail' : ($step === 3 ? 'lock' : 'user')) ?></div>
                    <p class="eyebrow text-muted mb-2"><?= $step ? 'STEP ' . $step . ' OF 3' : 'ACCOUNT ACCESS' ?></p>
                    <h1><?= ui_escape($title) ?></h1>
                    <p class="text-secondary mb-4"><?= ui_escape($subtitle) ?></p>
    <?php
}

function ui_alert(string $message, string $type = 'danger'): void
{
    if ($message !== '') {
        $type = in_array($type, ['success', 'warning', 'danger', 'info'], true) ? $type : 'danger';
        echo '<div class="alert alert-' . $type . '" role="alert">' . ui_escape($message) . '</div>';
    }
}

function ui_auth_end(): void
{
    echo '</div></section></div></main>';
    ui_footer();
}

function ui_footer(): void
{
    ?>
    <footer class="site-footer container d-flex flex-wrap justify-content-between gap-2 py-4 small text-secondary">
        <span>&copy; <?= date('Y') ?> Account Portal</span>
        <span>Made for your everyday.</span>
    </footer>
    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
