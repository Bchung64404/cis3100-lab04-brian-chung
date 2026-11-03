<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$host = getenv('MYSQL_HOST');
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Fail: " . $e->getMessage());
}

// print  "pdo connection established <br>";

// Safely execute schema updates column by column
$columns = [
    "ALTER TABLE users ADD COLUMN EmailVerified INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN EmailVerificationToken VARCHAR(64) NULL",
    "ALTER TABLE users ADD COLUMN TwoFactorPin VARCHAR(6) NULL",
    "ALTER TABLE users ADD COLUMN TwoFactorCreatedAt DATETIME NULL",
    "ALTER TABLE users ADD COLUMN Role VARCHAR(20) NOT NULL DEFAULT 'user'"
];

foreach ($columns as $sql) {
    try {
        $pdo->exec($sql);
    } catch (\PDOException $e) {
        // Ignore error if the column already exists
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN TotpSecret VARCHAR(32) NULL;");
} catch (\PDOException $e) {
    if ($e->errorInfo[1] !== 1060)
        die("Database error: " . $e->getMessage());
}

// print "All done<br>";