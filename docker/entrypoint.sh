#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ -n "${DB_HOST:-}" ] && [ -n "${DB_USER:-}" ] && [ -n "${DB_NAME:-}" ]; then
  DB_PORT="${DB_PORT:-3306}"
  DB_PREFIX="${DB_PREFIX:-pay}"
  DB_PASS="${DB_PASS:-}"

  php_escape() {
    # Escape for PHP single-quoted strings
    # - backslash: \\ -> \\\\
    # - single quote: ' -> \'
    printf "%s" "$1" | sed "s/\\\\/\\\\\\\\/g; s/'/\\\\'/g"
  }

  DB_HOST_ESC="$(php_escape "$DB_HOST")"
  DB_USER_ESC="$(php_escape "$DB_USER")"
  DB_PASS_ESC="$(php_escape "$DB_PASS")"
  DB_NAME_ESC="$(php_escape "$DB_NAME")"
  DB_PREFIX_ESC="$(php_escape "$DB_PREFIX")"

  cat > /var/www/html/config.php <<EOF
<?php
/*数据库配置*/
\$dbconfig=array(
  'host' => '${DB_HOST_ESC}',
  'port' => ${DB_PORT},
  'user' => '${DB_USER_ESC}',
  'pwd' => '${DB_PASS_ESC}',
  'dbname' => '${DB_NAME_ESC}',
  'dbqz' => '${DB_PREFIX_ESC}'
);
EOF

  chown www-data:www-data /var/www/html/config.php || true
fi

exec "$@"
