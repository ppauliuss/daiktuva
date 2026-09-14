<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = 200;
$title = 'Akumuliatorinis suktuvas su 2 baterijomis ir įkrovikliu';
$excerpt = 'Tvarkingas naudotas akumuliatorinis suktuvas namų remontui, baldams ir smulkiems darbams. Komplekte dvi baterijos ir įkroviklis.';
$content = 'Parduodu tvarkingą akumuliatorinį suktuvą su dviem baterijomis ir įkrovikliu. Naudotas namų remontui, veikia normaliai, baterijos laiko pagal amžių. Tinka baldams surinkti, lentynoms, smulkiems gręžimo ir sukimo darbams. Korpusas turi įprastų naudojimo žymių, bet niekas nelūžę. Galima apžiūrėti Vilniuje.';

wp_update_post( array(
	'ID'           => $product_id,
	'post_title'   => $title,
	'post_excerpt' => $excerpt,
	'post_content' => $content,
) );

update_post_meta( $product_id, 'rank_math_title', $title . ' | Daiktuva' );
update_post_meta( $product_id, 'rank_math_description', 'Naudotas akumuliatorinis suktuvas su dviem baterijomis ir įkrovikliu. Kaina 65 Eur, galima apžiūrėti Vilniuje.' );
update_post_meta( $product_id, 'rank_math_focus_keyword', 'akumuliatorinis suktuvas' );

echo get_permalink( $product_id ) . PHP_EOL;
