#!/bin/sh
set -eu

cd /var/www/html

until wp core is-installed >/dev/null 2>&1; do
  if wp core install \
    --url="$WORDPRESS_URL" \
    --title="$WORDPRESS_TITLE" \
    --admin_user="$WORDPRESS_ADMIN_USER" \
    --admin_password="$WORDPRESS_ADMIN_PASSWORD" \
    --admin_email="$WORDPRESS_ADMIN_EMAIL" \
    --skip-email; then
    break
  fi

  echo "Waiting for WordPress files and database..."
  sleep 5
done

wp option update blogdescription "Daiktu katalogas ir prekybos vieta"
wp option update timezone_string "Europe/Vilnius"
wp option update permalink_structure "/%postname%/"
wp rewrite flush --hard

wp plugin install woocommerce --activate
wp plugin install dokan-lite --activate || true
wp plugin install redis-cache --activate || true
wp redis enable || true

wp theme install twentytwentyfour --activate || true

wp option update woocommerce_store_address "Vilnius"
wp option update woocommerce_default_country "LT"
wp option update woocommerce_currency "EUR"
wp option update woocommerce_enable_guest_checkout "yes"
wp option update woocommerce_enable_checkout_login_reminder "no"

echo "Daiktuva WordPress setup finished."
