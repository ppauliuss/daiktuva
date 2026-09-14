#!/bin/sh
set -eu

cd /opt/daiktuva

wp() {
  docker compose run --rm -T --entrypoint wp wpcli "$@"
}

prefix="$(wp db prefix | head -n 1)"

wp db query "UPDATE ${prefix}users SET user_login='vilniaus-irankiai', user_nicename='vilniaus-irankiai', display_name='Vilniaus irankiai', user_email='vilniaus-irankiai@daiktuva.lt' WHERE ID=10 LIMIT 1;"
wp eval 'update_user_meta(10, "dokan_profile_settings", array("store_name" => "Vilniaus irankiai", "phone" => "+37061234567", "show_email" => "no", "address" => array("city" => "Vilnius"))); update_user_meta(10, "dokan_store_name", "Vilniaus irankiai"); update_user_meta(10, "dokan_enable_selling", "yes");'

wp post update 200 \
  --post_title='Akumuliatorinis suktuvas su 2 baterijomis ir ikrovikliu' \
  --post_name='akumuliatorinis-suktuvas-su-2-baterijomis-ir-ikrovikliu' \
  --post_excerpt='Tvarkingas akumuliatorinis suktuvas namu remontui, baldams ir smulkiems darbams. Komplekte dvi baterijos ir ikroviklis.' \
  --post_content='Parduodu tvarkinga akumuliatorini suktuva su dviem baterijomis ir ikrovikliu. Naudotas namu remontui, veikia normaliai, baterijos laiko pagal amziu. Tinka baldams surinkti, lentynoms, smulkiems grezimo ir sukimo darbams. Korpusas turi iprastu naudojimo zymiu, bet niekas neluze. Galima apziureti Vilniuje.'

wp post meta update 200 _regular_price 65
wp post meta update 200 _price 65
wp post meta update 200 _daiktuva_phone '+37061234567'
wp post meta update 200 _dk_location 'Vilnius'
wp post meta update 200 _daiktuva_status active
wp post meta update 200 _stock_status instock
wp post meta update 200 _visibility visible
wp post term set 200 product_cat statyba-ir-irankiai --by=slug
wp post term set 200 product_type simple --by=slug

if [ -f /opt/daiktuva/scripts/suktuvas-be-logo.png ]; then
  attachment_id="$(wp media import /scripts/suktuvas-be-logo.png --post_id=200 --title='Akumuliatorinis suktuvas su baterijomis' --featured_image --porcelain | head -n 1)"
  wp post meta update 200 _thumbnail_id "$attachment_id"
fi

wp cache flush || true
docker compose exec -T wordpress sh -c 'rm -rf /var/www/html/wp-content/cache/*' || true
wp post url 200
