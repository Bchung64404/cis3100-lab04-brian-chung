<?php
session_start();

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('403 Forbidden: you do not have access to this page.');
    }
}

function has_role(string ...$roles): bool {
    return in_array($_SESSION['role'] ?? '', $roles, true);
}