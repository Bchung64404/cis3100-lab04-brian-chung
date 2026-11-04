CREATE TABLE users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Status INT DEFAULT 0,
    Role VARCHAR(20) NOT NULL DEFAULT 'user',
    EmailVerified INT DEFAULT 0,
    EmailVerificationToken VARCHAR(64) NULL,
    TwoFactorPin VARCHAR(6) NULL,
    TwoFactorCreatedAt DATETIME NULL,
    TotpSecret VARCHAR(32) NULL,
    RegDatetime DATETIME DEFAULT CURRENT_TIMESTAMP,
    LoginDatetime DATETIME NULL,
    LogoutDatetime DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
