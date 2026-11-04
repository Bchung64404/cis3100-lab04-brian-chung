<?php
// CLI only: never expose migration credentials through a public endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/database/connection.php';

function table_exists(PDO $pdo, string $table): bool
{
    $query = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $query->execute([$table]);
    return (bool) $query->fetchColumn();
}

function verify_users(PDO $pdo): void
{
    // Compare against the lab's schema contract without changing stored data.
    $expected = [
        'userID' => ['int', 'NO', null, 'auto_increment'],
        'FirstName' => ['varchar(100)', 'NO', null, ''],
        'LastName' => ['varchar(100)', 'NO', null, ''],
        'Email' => ['varchar(255)', 'NO', null, ''],
        'Password' => ['varchar(255)', 'NO', null, ''],
        'Status' => ['int', 'YES', '0', ''],
        'Role' => ['varchar(20)', 'NO', 'user', ''],
        'EmailVerified' => ['int', 'YES', '0', ''],
        'EmailVerificationToken' => ['varchar(64)', 'YES', null, ''],
        'TwoFactorPin' => ['varchar(6)', 'YES', null, ''],
        'TwoFactorCreatedAt' => ['datetime', 'YES', null, ''],
        'TotpSecret' => ['varchar(32)', 'YES', null, ''],
        'RegDatetime' => ['datetime', 'YES', 'current_timestamp', ''],
        'LoginDatetime' => ['datetime', 'YES', null, ''],
        'LogoutDatetime' => ['datetime', 'YES', null, ''],
    ];
    $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_UNIQUE);
    foreach ($expected as $name => $definition) {
        if (!isset($columns[$name])) {
            throw new RuntimeException("users.$name is missing; inspect the schema before proceeding.");
        }
        $column = $columns[$name];
        $type = preg_replace('/^int\(\d+\)$/', 'int', strtolower($column['Type']));
        $default = $column['Default'];
        if (is_string($default) && strcasecmp($default, 'CURRENT_TIMESTAMP()') === 0) {
            $default = 'current_timestamp';
        } elseif (is_string($default) && strcasecmp($default, 'CURRENT_TIMESTAMP') === 0) {
            $default = 'current_timestamp';
        }
        $extra = trim(str_replace('DEFAULT_GENERATED', '', $column['Extra']));
        if ([$type, $column['Null'], $default, $extra] !== $definition) {
            throw new RuntimeException("users.$name differs from the required schema; inspect it before proceeding.");
        }
    }
    $indexes = [];
    foreach ($pdo->query('SHOW INDEX FROM users') as $index) {
        if ((int) $index['Non_unique'] === 0) {
            $indexes[$index['Key_name']][(int) $index['Seq_in_index']] =
                [$index['Column_name'], $index['Sub_part']];
        }
    }
    if (($indexes['PRIMARY'] ?? []) !== [1 => ['userID', null]] ||
        !in_array([1 => ['Email', null]], $indexes, true)) {
        throw new RuntimeException('users requires a userID primary key and a full unique Email index.');
    }
    $engine = $pdo->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'")->fetchColumn();
    if ($engine !== 'InnoDB') {
        throw new RuntimeException('users must use InnoDB.');
    }
}

$pdo = null;
$locked = false;
$exitCode = 0;
try {
    $args = array_slice($argv, 1);
    if (count($args) > 1 || ($args && !in_array($args[0], ['--check', '--baseline'], true))) {
        throw new RuntimeException('Usage: php scripts/migrate.php [--check|--baseline]');
    }
    $check = ($args[0] ?? '') === '--check';
    $baseline = ($args[0] ?? '') === '--baseline';
    $files = glob(dirname(__DIR__) . '/database/migrations/*.sql');
    sort($files, SORT_STRING);
    if (!$files || basename($files[0]) !== '001_create_users.sql') {
        throw new RuntimeException('The initial migration is missing.');
    }
    $pdo = database_connection();
    // Serialize setup/check operations for this database across containers.
    $lockName = 'lab_migrate_' . substr(hash('sha256', getenv('MYSQL_DATABASE')), 0, 40);
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 30)');
    $lock->execute([$lockName]);
    $locked = (int) $lock->fetchColumn() === 1;
    if (!$locked) {
        throw new RuntimeException('Could not acquire the database migration lock.');
    }
    $hasHistory = table_exists($pdo, 'schema_migrations');
    $history = $hasHistory ? $pdo->query('SELECT * FROM schema_migrations')->fetchAll(PDO::FETCH_UNIQUE) : [];
    $versions = array_map('basename', $files);
    foreach ($history as $version => $entry) {
        if (!in_array($version, $versions, true) || $entry['applied_at'] === null) {
            throw new RuntimeException("Unknown or incomplete migration: $version. Inspect and recover before retrying.");
        }
    }
    // Refuse to silently adopt an existing installation.
    $adopt = !isset($history['001_create_users.sql']) && table_exists($pdo, 'users');
    if ($adopt && !$baseline) {
        throw new RuntimeException('Existing users table has no migration history. Back up, inspect, then run --baseline.');
    }
    if ($baseline) {
        if (!$adopt) {
            throw new RuntimeException('--baseline requires an existing, untracked users table.');
        }
        verify_users($pdo);
    }
    if (!$hasHistory) {
        if ($check) {
            throw new RuntimeException('Database is not initialized. Run migrations before launching.');
        }
        $pdo->exec('CREATE TABLE schema_migrations (
            version VARCHAR(190) PRIMARY KEY,
            checksum CHAR(64) NOT NULL,
            applied_at DATETIME NULL
        ) ENGINE=InnoDB');
    }
    foreach ($files as $file) {
        $version = basename($file);
        $checksum = hash_file('sha256', $file);
        if (isset($history[$version])) {
            if (!hash_equals($history[$version]['checksum'], $checksum)) {
                throw new RuntimeException("Applied migration was changed: $version. Add a new migration instead.");
            }
            echo "Already applied: $version\n";
            continue;
        }
        if ($check) {
            throw new RuntimeException("Pending migration: $version");
        }
        if ($baseline && $version !== '001_create_users.sql') {
            // Baseline only the known initial schema; apply later changes separately.
            break;
        }
        // MySQL DDL implicitly commits. Record an incomplete attempt first so a
        // partially executed migration can never be silently treated as applied.
        $insert = $pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (?, ?)');
        $insert->execute([$version, $checksum]);
        if (!$baseline) {
            $pdo->exec(file_get_contents($file));
        }
        // Validate the final contract after all pending changes, allowing later
        // migrations to introduce fields required by a newer application.
        if ($file === $files[array_key_last($files)]) {
            verify_users($pdo);
        }
        $done = $pdo->prepare('UPDATE schema_migrations SET applied_at = NOW() WHERE version = ?');
        $done->execute([$version]);
        echo ($baseline ? 'Baselined: ' : 'Applied: ') . "$version\n";
    }
    verify_users($pdo);
    echo $baseline ? "Baseline complete. Run migrations next.\n" : "Database schema is ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Database setup failed: ' . $e->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if ($locked && $pdo) {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }
}
exit($exitCode);
