<?php
$host = 'database';
$db   = getenv('MYSQL_DATABASE') ?: 'lab_db';
$user = getenv('MYSQL_USER') ?: 'app_user'; 
$pass = getenv('MYSQL_PASSWORD') ?: 'app_user_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Execute table creation query after PDO connection succeeds
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            userID INT AUTO_INCREMENT PRIMARY KEY,
            FirstName VARCHAR(100) NOT NULL,
            LastName VARCHAR(100) NOT NULL,
            Email VARCHAR(255) NOT NULL UNIQUE,
            Password VARCHAR(255) NOT NULL,
            Status INT DEFAULT 0,
            RegDatetime DATETIME DEFAULT CURRENT_TIMESTAMP,
            LoginDatetime DATETIME NULL,
            LogoutDatetime DATETIME NULL
        ) ENGINE=InnoDB;
    ");

    echo "<h1>PHP Container Connected to MySQL & Table Created Successfully!</h1>";
} catch (PDOException $e) {
    echo "<h1>Database Connection Failed:</h1> " . $e->getMessage();
}
?>w