# Database setup before launch

This procedure implements the database preparation requirement only. It does not
deploy to Google Cloud, configure CI/CD, or complete the separate UI/unit-test
assignment. Run commands from the repository root using Docker Compose v2.

## What changed

- `database/migrations/001_create_users.sql` defines the complete users schema.
- `scripts/migrate.php` applies pending SQL files in filename order, records SHA-256
  checksums, and refuses changed, unknown, or incomplete migrations.
- `scripts/migrate.php --check` checks migration history and required user columns,
  defaults, primary/unique keys, and InnoDB without changing tables or data.
- `--baseline` explicitly adopts an existing users table after checking that same
  contract. It preserves records, roles, passwords, and 2FA secrets. It does not
  repair incompatible columns or convert existing character sets/collations.
- `www/db.php` only connects. `www/index.php` redirects to login.
- The PHP image installs PDO MySQL at build time. Compose waits for authenticated
  database readiness and successful migrations before starting PHP. PHP also runs
  its own read-only schema check before starting FPM; Nginx waits for FPM readiness.

The new schema uses utf8mb4. Older installations can be baselined with their existing
collation if their required structure matches. Inspect `SHOW CREATE TABLE users`
and plan any character-set conversion separately after checking existing data.

## 1. Configure credentials

For a new checkout, copy `.env.example` to `.env` and replace the password
placeholders. If `.env` already exists, preserve it and add only the two new keys:

```dotenv
MIGRATION_USER=lab_migrator
MIGRATION_PASSWORD=your-unique-migration-password
```

`MYSQL_DATABASE`, `MYSQL_USER`, and `MYSQL_PASSWORD` must match the real database and
application account. PHP previously used hardcoded credentials; it now uses these
values consistently. The migration service gets only migration credentials, and
the PHP service gets only application credentials. Root credentials stay in the
database service. Do not commit `.env` or use example passwords in production.

MySQL image variables initialize a NEW data directory only. Editing `.env` does
not reset passwords, create users, or rename an existing database in `db_data`.
Use the actual existing credentials. Never delete `db_data` to resolve a login error.

## 2. Stop application traffic and back up an existing installation

The shared connection code now lives outside `www`, so old PHP containers must be
recreated with the new mounts/image before using the updated application.

```powershell
docker compose stop web-server php-app
docker compose up -d database
docker compose exec database sh
```

Inside the database container shell, use the actual root password when prompted:

```sh
mysqldump -u root -p --single-transaction --no-tablespaces "$MYSQL_DATABASE" > /tmp/pre-migration.sql
exit
```

Check the dump command succeeded before continuing. Back in PowerShell:

```powershell
New-Item -ItemType Directory -Force backups
docker compose cp database:/tmp/pre-migration.sql ./backups/pre-migration.sql
```

Store a separate protected copy and verify restoration to a separate database
before making production changes. `backups/` is ignored by Git and Docker builds.
Skip the dump only for a genuinely empty, new installation.

## 3. Inspect the schema and provision a migration account

```powershell
docker compose exec database mysql -u root -p
```

In MySQL, inspect existing state. The SQL below assumes `lab_db`, `app_user`, and
`lab_migrator`; substitute your configured database/account names throughout.

```sql
USE lab_db;
SHOW TABLES;
-- Existing installations only:
SHOW CREATE TABLE users;
SHOW COLUMNS FROM users;
SHOW INDEX FROM users;
SELECT COUNT(*) AS user_count FROM users;

CREATE USER 'lab_migrator'@'%' IDENTIFIED BY 'your-unique-migration-password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
    ON lab_db.* TO 'lab_migrator'@'%';
EXIT;
```

Use a migration account distinct from the application account, with the password
set in `.env`. If it already exists, inspect its grants rather than blindly
recreating it. These are database accounts, separate from application RBAC users.
No default application administrator or password is seeded by setup. Existing
admins remain intact; for a fresh database, use your established controlled admin
bootstrap procedure after registration.

## 4. Build, migrate, and verify

```powershell
docker compose build php-app
```

For an existing `users` table with no migration history, after backup/inspection:

```powershell
docker compose run --rm migrate php /var/www/scripts/migrate.php --baseline
```

If validation fails, stop and review the reported mismatch. Do not mark the
migration complete manually or drop existing tables. Design a reviewed upgrade
for the actual schema. Baseline validates required fields and preserves extra
columns/indexes, but does not prove arbitrary existing schema changes are safe.

For both fresh and baselined installations:

```powershell
docker compose run --rm migrate
docker compose run --rm migrate php /var/www/scripts/migrate.php --check
```

Every command must exit successfully. A second migration run should say
`Already applied: 001_create_users.sql`. Check mode should say
`Database schema is ready.` Baseline only records the initial migration; it does
not automatically execute later migrations.

## 5. Restrict the application account

MySQL's image initially gives `MYSQL_USER` broad database privileges. After the
tables exist, narrow the application's grants before opening traffic:

```powershell
docker compose exec database mysql -u root -p
```

```sql
SHOW GRANTS FOR 'app_user'@'%';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'app_user'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON lab_db.users TO 'app_user'@'%';
GRANT SELECT ON lab_db.schema_migrations TO 'app_user'@'%';
SHOW GRANTS FOR 'app_user'@'%';
EXIT;
```

Use a dedicated application account; do not apply this to root or an account
shared with another application. The runtime can read migration history but
cannot forge it or change tables. Grant access explicitly when adding future tables.

Verify with the actual runtime account, without starting the web server:

```powershell
docker compose run --rm --no-deps php-app php /var/www/scripts/migrate.php --check
```

## 6. Launch locally only after successful verification

```powershell
docker compose up -d --build
docker compose ps -a
docker compose logs migrate php-app
```

Visit `http://localhost:8080/` and verify registration, email verification, login,
2FA, and admin access with your existing mail configuration and accounts. This
manual application check is separate from the isolated database checks below.

Compose's dependency gates apply to Compose startup. For a release with changes,
stop old application traffic first, explicitly run and check migrations, then
recreate application containers. An old running container is not stopped by a
later failed migration. A future cloud release process must enforce this same
order before routing traffic; no cloud resources are created here.

## Future migrations and failure recovery

Add ordered SQL files such as `002_add_profile.sql`; never edit an applied file.
Update the schema contract in `verify_users()` when intentionally changing required
fields. SQL files are executed by PDO without a custom SQL statement parser;
MySQL client-only directives such as `DELIMITER` are not supported.

An advisory lock prevents simultaneous migration/check operations for the database.
Checksums detect edits to applied files. Before executing SQL, the runner inserts
a tracking row with a NULL `applied_at`. If execution or verification fails, that
row remains incomplete and subsequent setup/check commands fail closed.

MySQL DDL can implicitly commit; a failed migration cannot be assumed rolled back.
Inspect the actual schema and tracking row, restore the verified backup or repair
the failed change through a reviewed procedure, and only then reconcile migration
history. Do not simply delete the incomplete row and retry potentially partial SQL.
See the [MySQL implicit commit documentation](https://dev.mysql.com/doc/refman/8.0/en/implicit-commit.html).

## Isolated database setup checks

These integration checks use a temporary MySQL data directory in memory, a separate
Compose project, no published database ports, and no `.env` credentials or `db_data`.
They verify fresh setup, repeat runs, preservation of users/roles/2FA, explicit
baseline, unique emails, least-privilege runtime access, checksum/incomplete-history
failures, missing configuration, invalid credentials, and schema drift.

```powershell
docker compose -p lab-db-setup-test -f tests/database-compose.yml up --build --abort-on-container-exit --exit-code-from test
docker compose -p lab-db-setup-test -f tests/database-compose.yml down
```

The first command must exit 0 and print `All database setup checks passed.` Cleanup
is scoped to the disposable test project. These checks do not send email or exercise
the application's browser login flow.
