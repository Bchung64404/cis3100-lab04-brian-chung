# MySQL 8.4

The application Compose configuration now uses `mysql:8.4`. The local database
integration suite and disposable UI preview use the same series. The tag permits
8.4 patch updates without changing to another MySQL release series.

PHP continues to use PDO MySQL. For the verified account configuration, no
application schema migration or password change was needed. Existing migration
files and checksums must remain unchanged.

## Existing local data

An 8.0-to-8.4 server upgrade changes MySQL's internal data dictionary. Before
opening existing data with 8.4, retain a verified backup and check compatibility.
The supported upgrade paths include 8.0 to the next LTS series, 8.4:
[MySQL upgrade paths](https://dev.mysql.com/doc/refman/8.4/en/upgrade-paths.html).

For this checkout, the local containers were already stopped. A cold backup of
`db_data` and separate rehearsal copies were created under ignored `backups/`.
All 189 persistent files matched SHA-256 hashes; the stale `mysql.sock` runtime
link was excluded from hashing. A logical SQL dump was also retained.

The original `db_data` was left untouched and application containers were left
stopped. The tests use copies, not the original directory. See the local results
under `docs/evidence/mysql-84-upgrade/` for exact versions, fingerprints, and logs.

Start the updated local stack from the repository root when ready:

```powershell
docker compose up -d --build
docker compose ps -a
docker compose logs database migrate php-app
docker compose exec php-app php /var/www/scripts/migrate.php --check
```

On the first startup, MySQL performs the required server upgrade against the
existing directory. Compose then waits for database health, successful application
migrations, and PHP health before starting Nginx. Existing users, database accounts,
grants, and migration history should be retained; the copied-data rehearsal checks
those application connections and data fingerprints before this first startup.

Do not change the image back to 8.0 while pointing it at a directory already opened
and upgraded by 8.4. A rollback needs the pre-upgrade backup and a reviewed restore
procedure, with all servers using that directory stopped first. Retain the cold
backup until the real 8.4 installation has been verified and new backups established.

## Authentication compatibility

MySQL 8.4 disables `mysql_native_password` by default. Use `caching_sha2_password`
for application, migration, and administrator accounts; PHP's current PDO driver
was checked against 8.4. Do not add the removed `default_authentication_plugin`
server setting. See [MySQL 8.4 changes](https://dev.mysql.com/doc/refman/8.4/en/mysql-nutshell.html).

The checked copy had no active legacy-authentication accounts. For another checkout
with different database accounts, inspect their plugins and migrate legacy accounts
while the old server is available before attempting the upgrade.

## Repeat the fresh-database checks

```powershell
docker compose -p mysql84-setup-test -f tests/database-compose.yml up --build --abort-on-container-exit --exit-code-from test
docker compose -p mysql84-setup-test -f tests/database-compose.yml down
```

`tests/` and evidence remain ignored by Git. These checks cover fresh migrations,
baselining, preservation, checksums, partial failures, and runtime permissions.

## Hosted databases

Changing a local Compose image does not upgrade a hosted Cloud SQL instance. Its
server version must be selected or upgraded separately through the provider's
supported process. No cloud deployment or hosted database upgrade is performed by
this project configuration change.
