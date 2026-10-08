#!/bin/sh
set -eu
umask 077

repository=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
acceptance_root=${CODEQUEST_ACCEPTANCE_ROOT:-/tmp/codequest-acceptance-$(id -u)}
database_port=${CODEQUEST_ACCEPTANCE_DB_PORT:-33309}
application_port=${CODEQUEST_ACCEPTANCE_APP_PORT:-8768}

case "$acceptance_root" in /tmp/codequest-*) ;; *) echo 'Use a private /tmp/codequest-* acceptance directory.' >&2; exit 1 ;; esac
case "${acceptance_root#/tmp/}" in */*) echo 'Acceptance directory must be a direct child of /tmp.' >&2; exit 1 ;; esac
case "$database_port:$application_port" in *[!0-9:]*|:*|*:) echo 'Ports must be numeric.' >&2; exit 1 ;; esac
[ ! -L "$acceptance_root" ] || { echo 'Acceptance directory must not be a symlink.' >&2; exit 1; }
[ ! -f "$repository/bootstrap/cache/config.php" ] || { echo 'Refusing a cached application configuration.' >&2; exit 1; }
: "${CODEQUEST_DEMO_PASSWORD:?Supply the local demo password externally.}"
mkdir -p "$acceptance_root"
[ "$(stat -c %u "$acceptance_root")" = "$(id -u)" ] || { echo 'Acceptance directory must belong to this user.' >&2; exit 1; }
chmod 700 "$acceptance_root"
for path in data database.sock database.pid database.env install.log database.log database-start.log; do
    [ ! -L "$acceptance_root/$path" ] || { echo 'Acceptance paths must not be symlinks.' >&2; exit 1; }
done

if [ ! -d "$acceptance_root/data/mysql" ]; then
    mariadb-install-db --no-defaults --datadir="$acceptance_root/data" --auth-root-authentication-method=normal > "$acceptance_root/install.log" 2>&1
fi
if ! mariadb-admin --no-defaults --socket="$acceptance_root/database.sock" --user=root ping >/dev/null 2>&1; then
    setsid --fork mariadbd --no-defaults --datadir="$acceptance_root/data" \
        --socket="$acceptance_root/database.sock" --pid-file="$acceptance_root/database.pid" \
        --port="$database_port" --bind-address=127.0.0.1 --skip-name-resolve \
        --log-error="$acceptance_root/database.log" > "$acceptance_root/database-start.log" 2>&1
    attempts=0
    until mariadb-admin --no-defaults --socket="$acceptance_root/database.sock" --user=root ping >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        [ "$attempts" -lt 30 ] || { echo "Database failed to start; inspect $acceptance_root/database.log" >&2; exit 1; }
        sleep 1
    done
fi

running_port=$(mariadb --no-defaults --socket="$acceptance_root/database.sock" --user=root --batch --skip-column-names -e 'SELECT @@port')
[ "$running_port" = "$database_port" ] || { echo 'Existing disposable server uses a different port.' >&2; exit 1; }
running_data=$(mariadb --no-defaults --socket="$acceptance_root/database.sock" --user=root --batch --skip-column-names -e 'SELECT @@datadir')
[ "$running_data" = "$acceptance_root/data/" ] || { echo 'Connected server does not use the private acceptance data directory.' >&2; exit 1; }

mariadb --no-defaults --socket="$acceptance_root/database.sock" --user=root \
    -e 'CREATE DATABASE IF NOT EXISTS codequest_acceptance_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS codequest_acceptance_regression_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'

export CODEQUEST_ALLOW_DISPOSABLE_DATABASE=1 DB_CONNECTION=mariadb DB_HOST=127.0.0.1
export DB_PORT="$database_port" DB_SOCKET='' DB_URL='' DB_DATABASE=codequest_acceptance_test
export DB_USERNAME=root DB_PASSWORD='' APP_ENV=local SESSION_DRIVER=database CACHE_STORE=database
export APP_URL="http://127.0.0.1:$application_port"
export CODEQUEST_GRADER_URL=http://127.0.0.1:4318
cd "$repository"
php artisan migrate --seed --force --no-interaction
php artisan db:seed --class=DevelopmentDemoSeeder --force --no-interaction

cat > "$acceptance_root/database.env" <<EOF
export CODEQUEST_ALLOW_DISPOSABLE_DATABASE=1 DB_CONNECTION=mariadb DB_HOST=127.0.0.1
export DB_PORT=$database_port DB_SOCKET='' DB_URL='' DB_DATABASE=codequest_acceptance_test
export DB_USERNAME=root DB_PASSWORD='' APP_ENV=local SESSION_DRIVER=database CACHE_STORE=database
export APP_URL=http://127.0.0.1:$application_port
export CODEQUEST_GRADER_URL=http://127.0.0.1:4318
EOF
printf 'Disposable database ready. Environment: %s/database.env\n' "$acceptance_root"
