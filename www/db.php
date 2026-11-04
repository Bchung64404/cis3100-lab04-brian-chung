<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/database/connection.php';

try {
    $pdo = database_connection();
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('The service is temporarily unavailable. Please try again later.');
}
