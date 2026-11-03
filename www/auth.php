<?php
require_once 'db.php';

function current_user(): ?array {
    global $pdo;
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['userID'])) {
            $stmt = $pdo->prepare("SELECT userID, FirstName, Role FROM users WHERE userID = ?");
            $stmt->execute([$_SESSION['userID']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_role(string ...$roles): array {
    $user = require_login();
    if (!in_array($user['Role'], $roles, true)) {
        http_response_code(403);
        exit('403 Forbidden: you do not have access to this page.');
    }
    return $user;
}

function has_role(string ...$roles): bool {
    $user = current_user();
    return $user !== null && in_array($user['Role'], $roles, true);
}