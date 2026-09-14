<?php
// Daiktuva: pavyzdinis blogo straipsnis (zmogiskas tonas, SEO meta).
$slug = 'kaip-greiciau-parduoti-naudota-daikta';

$content = <<<'HTML'
<p>Per kelerius metus esu pardavęs visko – nuo seno traktoriaus iki vaikiško vežimėlio. Vieni daiktai išskrisdavo per porą dienų, kiti kabodavo savaitėmis, nors atrodė nė kiek ne prastesni. Ilgainiui supratau, kad skirtumą beveik visada lemia ne pati kaina, o tai, kaip skelbimas padarytas. Surašiau tai, kas man pačiam suveikė geriausiai – gal pravers ir jums.</p>

<h2>1. Nuotrauka parduoda anksčiau už tekstą</h2>
<p>Žmogus pirmiausia mato paveikslėlį, o tik paskui skaito. Todėl nepagailėkite kelių minučių ir nufotografuokite daiktą dienos šviesoje, geriausiai prie lango arba lauke. Tamsios, blankios nuotraukos iš karto kelia įtarimą, kad kažkas slepiama.</p>
<p>Parodykite daiktą iš kelių pusių, o jei yra įbrėžimų ar nusidėvėjimo – nufotografuokite ir juos. Kad ir kaip keista, sąžininga nuotrauka su trūkumu sukelia daugiau pasitikėjimo nei dešimt gražių kadrų, kuriuose viskas tobula.</p>

<h2>2. Kainą rašykite iš karto</h2>
<p>„Kaina sutartinė" arba „rašykite į žinutes" daugumą pirkėjų tiesiog atbaido. Žmonės nenori derėtis dar net nepamatę skaičiaus. Parašykite realią kainą, o jei esate pasiruošę nusileisti – tiesiog palikite sau nedidelį tarpą deryboms.</p>

<h2>3. Aprašykite taip, kaip pasakotumėte kaimynui</h2>
<p>Geriausi aprašymai skamba paprastai ir gyvai, tarsi pasakotumėte per tvorą. Nereikia sausų sąrašų ar techninio žargono. Parašykite, kiek laiko daiktą turėjote, kaip jį naudojote, kodėl parduodate. Tokios smulkmenos žmogui pasako daugiau nei dešimt būdvardžių.</p>
<p>Venkite šablono „geros būklės, parduodu". Jį mato visi ir niekas į jį nebereaguoja. Geriau parašykite konkrečiai: „naudojau dvejus metus savo sode, žiemą laikiau garaže".</p>

<h2>4. Atsiliepkite greitai</h2>
<p>Pirkėjas dažniausiai rašo iškart keliems pardavėjams. Kas atsako pirmas, tas paprastai ir parduoda. Net jei tuo metu negalite kalbėti, parašykite trumpą žinutę, kad pamatėte ir susisieksite vakare. Tylėjimas pusę dienos – tai prarastas pirkėjas.</p>

<h2>5. Būkite sąžiningi dėl trūkumų</h2>
<p>Jei kažkas cypia, braška ar yra apdaužyta – parašykite tai patys. Pirkėjas vis tiek pamatys atvažiavęs, o nutylėtas trūkumas baigiasi tuo, kad žmogus apsisuka ir išvažiuoja, o jūs gaištate laiką iš naujo. Atvirai įvardytas trūkumas, priešingai, dažnai net pagreitina sandorį.</p>

<h2>6. Pataikykite į tinkamą sezoną</h2>
<p>Daug ką lemia laikas. Sodo techniką lengviau parduoti pavasarį, žiemines padangas – rudenį, o vaikiškus daiktus tada, kai jų tikrai reikia. Jei daiktas nėra skubus, kartais verta palaukti kelias savaites ir pataikyti į sezoną – parduosite ir greičiau, ir brangiau.</p>

<h2>7. Derėtis galima, bet žinokite savo ribą</h2>
<p>Lietuvoje derėtis įprasta, tad nenustebkite gavę klausimą „kiek paskutinė kaina". Iš anksto nuspręskite, už kiek tikrai parduotumėte, ir laikykitės to. Maža nuolaida dažnai padeda žmogui apsispręsti, bet leisti numušti kainą per pusę nebūtina – jei daiktas geras, atsiras kitas pirkėjas.</p>

<h2>Trumpai</h2>
<p>Gera nuotrauka, aiški kaina, žmogiškas aprašymas ir greitas atsakymas – tiek dažniausiai ir reikia, kad daiktas rastų naują šeimininką. Visa kita ateina su patirtimi. Sėkmingų pardavimų!</p>
HTML;

$excerpt = 'Praktiniai patarimai iš asmeninės patirties, kaip greičiau ir brangiau parduoti naudotą daiktą: nuo nuotraukų iki derybų.';

// jau yra?
$existing = get_page_by_path( 'kaip-greiciau-parduoti-naudota-daikta', OBJECT, 'post' );
if ( $existing ) {
	$post_id = wp_update_post( array(
		'ID'           => $existing->ID,
		'post_title'   => 'Kaip greičiau parduoti naudotą daiktą: 7 patarimai iš patirties',
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status'  => 'publish',
	), true );
	echo "ATNAUJINTAS irasas ID=" . ( is_wp_error($post_id) ? $post_id->get_error_message() : $post_id ) . "\n";
} else {
	$post_id = wp_insert_post( array(
		'post_title'   => 'Kaip greičiau parduoti naudotą daiktą: 7 patarimai iš patirties',
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status'  => 'publish',
		'post_type'    => 'post',
		'post_author'  => 1,
	), true );
	echo "SUKURTAS irasas ID=" . ( is_wp_error($post_id) ? $post_id->get_error_message() : $post_id ) . "\n";
}

if ( ! is_wp_error( $post_id ) && $post_id ) {
	// Rank Math SEO meta (zmogiskas, ne perspaustas)
	update_post_meta( $post_id, 'rank_math_title', 'Kaip greičiau parduoti naudotą daiktą: 7 praktiniai patarimai' );
	update_post_meta( $post_id, 'rank_math_description', 'Praktiški patarimai, kaip parduoti naudotą daiktą greičiau ir brangiau – nuotraukos, kaina, aprašymas, derybos. Iš realios patirties.' );
	update_post_meta( $post_id, 'rank_math_focus_keyword', 'parduoti naudotą daiktą' );
	echo "URL: " . get_permalink( $post_id ) . "\n";
}

