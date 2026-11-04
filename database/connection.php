<?php

function database_connection(): PDO
{
    $config = [];
    foreach (['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'] as $name) {
        $value = getenv($name);
        if ($value === false || $value === '') {
            throw new RuntimeException("Missing required environment variable: $name");
        }
        $config[$name] = $value;
    }
    $port = getenv('MYSQL_PORT') ?: '3306';
    if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('MYSQL_PORT must be between 1 and 65535.');
    }
    foreach (['MYSQL_HOST', 'MYSQL_DATABASE'] as $name) {
        if (preg_match('/[;\x00-\x1f]/', $config[$name])) {
            throw new RuntimeException("Invalid configuration value: $name");
        }
    }

    return new PDO(
        "mysql:host={$config['MYSQL_HOST']};port=$port;dbname={$config['MYSQL_DATABASE']};charset=utf8mb4",
        $config['MYSQL_USER'],
        $config['MYSQL_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
}
