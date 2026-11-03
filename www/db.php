<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer's autoloader and phpdotenv
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // safeLoad won't crash if the .env file is missing in production

// Access credentials using getenv() robustly
$host = getenv('MYSQL_HOST') ?: $_ENV['MYSQL_HOST'] ?? null;
$db   = getenv('MYSQL_DATABASE') ?: $_ENV['MYSQL_DATABASE'] ?? null;
$user = getenv('MYSQL_USER') ?: $_ENV['MYSQL_USER'] ?? null;
$pass = getenv('MYSQL_PASSWORD') ?: $_ENV['MYSQL_PASSWORD'] ?? null;
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