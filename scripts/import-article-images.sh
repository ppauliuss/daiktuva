#!/bin/sh
set -eu

for file in /scripts/article-images/*.jpg; do
	base="$(basename "$file" .jpg)"
	post_id="${base##*-}"
	attachment_id="$(wp media import "$file" --post_id="$post_id" --porcelain)"
	wp post meta update "$post_id" _thumbnail_id "$attachment_id" >/dev/null
	echo "$post_id $attachment_id"
done
