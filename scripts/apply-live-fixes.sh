#!/bin/sh
set -eu

cd /opt/daiktuva

if [ -f .env ]; then
  set -a
  . ./.env
  set +a
fi

: "${WORDPRESS_ADMIN_PASSWORD:?WORDPRESS_ADMIN_PASSWORD missing in .env}"
: "${DEMO_SELLER_PASSWORD:?DEMO_SELLER_PASSWORD missing in .env}"

wp() {
  docker compose run --rm -T --entrypoint wp wpcli "$@"
}

prefix="$(wp db prefix)"

wp db query "UPDATE ${prefix}users SET user_login='adminas', user_nicename='adminas', display_name='adminas' WHERE user_login='admin' LIMIT 1;"
wp user update adminas --user_pass="$WORDPRESS_ADMIN_PASSWORD" --display_name="adminas"

for user in pardavejas1 pardavejas2 pardavejas3 pardavejas4 pardavejas5 pardavejas6; do
  if wp user get "$user" >/dev/null 2>&1; then
    wp user update "$user" --user_pass="$DEMO_SELLER_PASSWORD"
  fi
done

wp cache flush || true
docker compose exec -T wordpress sh -c 'rm -rf /var/www/html/wp-content/cache/*' || true

echo "Daiktuva live fixes applied."
