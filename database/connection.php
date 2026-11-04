
<?php

function database_connection(): PDO
{
    $config = [];

    // Required in both local Docker and Google Cloud
    foreach (['MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'] as $name) {
        $value = getenv($name);

        if ($value === false || $value === '') {
            throw new RuntimeException(
                "Missing required environment variable: $name"
            );
        }

        $config[$name] = $value;
    }

    // Optional Cloud SQL Unix socket
    $socket = getenv('MYSQL_SOCKET');

    if ($socket !== false && $socket !== '') {

        // Google Cloud Run connection
        if ($socket[0] !== '/' ||
            preg_match('/[;\x00-\x1f]/', $socket)) {
            throw new RuntimeException('Invalid MYSQL_SOCKET.');
        }

        $dsn = "mysql:unix_socket=$socket;" .
               "dbname={$config['MYSQL_DATABASE']};" .
               "charset=utf8mb4";

    } else {

        // Existing local Docker connection
        $host = getenv('MYSQL_HOST');

        if ($host === false || $host === '') {
            throw new RuntimeException(
                'Missing required environment variable: MYSQL_HOST'
            );
        }

        $port = getenv('MYSQL_PORT') ?: '3306';

        if (!ctype_digit($port) ||
            (int) $port < 1 ||
            (int) $port > 65535) {
            throw new RuntimeException(
                'MYSQL_PORT must be between 1 and 65535.'
            );
        }

        if (preg_match('/[;\x00-\x1f]/', $host)) {
            throw new RuntimeException('Invalid MYSQL_HOST.');
        }

        $dsn = "mysql:host=$host;port=$port;" .
               "dbname={$config['MYSQL_DATABASE']};" .
               "charset=utf8mb4";
    }

    // Validate the database name in either mode
    if (preg_match('/[;\x00-\x1f]/', $config['MYSQL_DATABASE'])) {
        throw new RuntimeException('Invalid MYSQL_DATABASE.');
    }

    return new PDO(
        $dsn,
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
