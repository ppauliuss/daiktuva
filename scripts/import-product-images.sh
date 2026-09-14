#!/bin/sh
set -eu

for file in /scripts/generated-product-images/daiktuva-product-*.jpg; do
	base="$(basename "$file" .jpg)"
	product_id="${base##*-}"
	attachment_id="$(wp media import "$file" --post_id="$product_id" --porcelain)"
	wp post meta update "$product_id" _thumbnail_id "$attachment_id" >/dev/null
	echo "$product_id $attachment_id"
done
