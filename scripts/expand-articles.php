<?php
/**
 * Expand existing Daiktuva articles and insert article images.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$marker = '<!-- daiktuva-extra-20260607 -->';

$extras = array(
	170 => array(
		'alt'     => 'Naudotų daiktų fotografavimas ir paruošimas pardavimui',
		'caption' => 'Tvarkingai paruoštas daiktas ir aiški pirmoji nuotrauka dažnai padaro daugiau nei ilgas aprašymas.',
		'html'    => <<<HTML
<h2>Prieš skelbiant: trumpas pasiruošimo sąrašas</h2>
<p>Prieš įkeldami skelbimą, skirkite dešimt minučių daikto paruošimui. Nuvalykite dulkes, surinkite priedus, patikrinkite, ar turite įkroviklį, instrukciją, dėžę ar kitus komplekto daiktus. Pirkėjui daug aiškiau, kai vienoje vietoje mato viską, ką gaus.</p>
<p>Jeigu parduodate techniką, verta padaryti vieną nuotrauką, kurioje matosi modelio lipdukas arba pagrindiniai parametrai. Taip sumažėja klausimų ir padidėja pasitikėjimas, nes žmogui nereikia spėlioti, ar tai tikrai tas modelis, kurio jis ieško.</p>

<h2>Kokius klausimus pirkėjas užduos pirmiausia</h2>
<p>Dažniausiai žmonės klausia trijų dalykų: ar daiktas dar yra, kokia galutinė kaina ir kur galima atsiimti. Jei šiuos atsakymus parašote pačiame skelbime, sutaupote laiko sau ir pirkėjui. Pavyzdžiui: „Daiktas Vilniuje, galima apžiūrėti vakare, kaina derinama vietoje protingose ribose".</p>
<p>Brangesniems daiktams pridėkite, ar galima išbandyti prieš perkant. Tai ypač svarbu įrankiams, buitinės technikos prietaisams, dviračiams, sodo technikai ir elektronikai.</p>

<h2>Dažna klaida: per daug bendri žodžiai</h2>
<p>Žodžiai „geras", „tvarkingas" ir „mažai naudotas" veikia tik tada, kai juos pagrindžiate faktais. Geriau rašyti konkrečiai: „naudotas vieną sezoną", „yra smulkių pabraižymų ant šono", „baterija laiko apie dvi valandas". Tokie sakiniai atrodo tikroviškai ir padeda pirkėjui greičiau apsispręsti.</p>
HTML,
	),
	183 => array(
		'alt'     => 'Naudoto traktoriaus apžiūra prieš pirkimą',
		'caption' => 'Prieš mokant už traktorių verta apžiūrėti variklį, hidrauliką, padangas ir dokumentus.',
		'html'    => <<<HTML
<h2>Ką patikrinti per pirmas 10 minučių</h2>
<p>Atvykę pirmiausia apžiūrėkite bendrą vaizdą: ar traktorius neperdažytas tik pardavimui, ar nesimato šviežių tepalų pratekėjimų, ar padangos dėvisi tolygiai. Tada paprašykite užvesti šaltą variklį ir leiskite jam padirbti kelias minutes be didelių apsukų.</p>
<p>Jei yra galimybė, pravažiuokite bent trumpą atstumą. Pavaros turi jungtis be stipraus traškėjimo, vairas neturi turėti didelio laisvumo, stabdžiai turi veikti abiejose pusėse. Smulkūs garsai seniems traktoriams nėra retenybė, bet stiprus kalimas ar metalo trynimosi garsas yra rimtas signalas.</p>

<h2>Kokius klausimus užduoti pardavėjui</h2>
<p>Paklauskite, kada keisti tepalai, filtrai, ar buvo remontuotas variklis, sankaba, hidraulikos siurblys. Svarbu ne tik atsakymas, bet ir tai, kaip žmogus atsako. Pardavėjas, kuris traktorių tikrai naudojo, paprastai gali papasakoti daugiau nei vieną bendrą sakinį.</p>
<p>Taip pat verta paklausti, kodėl parduoda. Atsakymas „atnaujinome techniką" skamba vienaip, o „nebereikia" be jokių detalių - jau priežastis pasidomėti daugiau.</p>

<h2>Kada geriau nevažiuoti į sandorį vienam</h2>
<p>Jeigu traktorius brangus arba pats nesate tikras dėl technikos, pasiimkite žmogų, kuris yra dirbęs su panašiu modeliu. Papildomos akys dažnai pamato tai, ko pirkėjas nepastebi iš noro greičiau užbaigti sandorį.</p>
HTML,
	),
	184 => array(
		'alt'     => 'Saugus pirkimas ir pardavimas internetu',
		'caption' => 'Saugiausi sandoriai yra tie, kuriuose neskubama, tikrinama informacija ir vengiama įtartinų nuorodų.',
		'html'    => <<<HTML
<h2>Saugus susirašinėjimas</h2>
<p>Stenkitės kuo daugiau susitarimo detalių palikti raštu: kaina, atsiėmimo vieta, komplektacija, defektai, pristatymo sąlygos. Jeigu vėliau kyla nesusipratimų, turite aiškią pokalbio istoriją. Venkite pereiti į įtartinas išorines nuorodas, ypač jei jos prašo kortelės duomenų ar prisijungimo prie banko.</p>
<p>Jei pirkėjas ar pardavėjas nenori atsakyti į paprastus klausimus, vengia papildomų nuotraukų ar skambučio, geriau sandorį pristabdyti. Normalus žmogus supranta, kad prieš perkant norisi pasitikslinti.</p>

<h2>Ką daryti su brangesniais daiktais</h2>
<p>Perkant brangesnį daiktą, saugiausia jį apžiūrėti gyvai ir mokėti tik tada, kai įsitikinote, kad viskas atitinka aprašymą. Jei siunčiama, susitarkite dėl siuntos būdo, draudimo ir aiškiai įvardykite, kas prisiima riziką, jei daiktas kelionėje pažeidžiamas.</p>
<p>Jei parduodate, nefotografuokite asmens dokumentų ar banko kortelių, nesiųskite prisijungimų ir netvirtinkite mokėjimų per nuorodas, kurios atėjo iš nepažįstamo žmogaus. Sąžiningam pirkėjui tokių dalykų nereikia.</p>

<h2>Raudonos vėliavos</h2>
<p>Didžiausi įspėjimai: prašomas avansas už labai gerą kainą, skubinimas, nenoras susitikti, neaiškios nuotraukos, nauja anketa be istorijos, nuorodos į „kurjerio apmokėjimą" arba žinutės su keistomis klaidomis. Vienas ženklas dar nieko nereiškia, bet keli kartu - pakankama priežastis sustoti.</p>
HTML,
	),
	185 => array(
		'alt'     => 'Naudotos buitinės technikos patikra prieš pirkimą',
		'caption' => 'Naudotą buitinę techniką verta pirkti tada, kai ją galima įjungti, apžiūrėti ir patikrinti prieš sandorį.',
		'html'    => <<<HTML
<h2>Amžius ir energijos sąnaudos</h2>
<p>Naudota technika gali būti puikus pirkinys, bet labai senas prietaisas kartais kainuoja daugiau per elektros sąskaitas. Prieš perkant verta pažiūrėti modelį, energijos klasę ir realų amžių. Jei šaldytuvas ar skalbyklė jau gerokai pagyvenę, maža kaina nebūtinai reiškia gerą sandorį.</p>
<p>Jeigu pardavėjas turi pirkimo dokumentus ar gali pasakyti tikslų modelį, patikra tampa daug paprastesnė. Pagal modelį galima rasti matmenis, atsarginių dalių kainas ir dažnesnes problemas.</p>

<h2>Kaip tikrinti skalbyklę, šaldytuvą ir indaplovę</h2>
<p>Skalbyklę verta paleisti bent trumpai: ar ima vandenį, ar išleidžia, ar gręžimo metu nešokinėja. Šaldytuvui patikrinkite, ar šąla tolygiai, ar durelių gumos sandarios, ar nėra nemalonaus kvapo. Indaplovei svarbu, ar nėra rūdžių, ar sveikos lentynos ir ar sandariai užsidaro durelės.</p>
<p>Visiems prietaisams galioja ta pati taisyklė: jei pardavėjas neleidžia įjungti arba sako „neturiu kaip patikrinti", kaina turi būti tokia, kad rizika būtų verta.</p>

<h2>Transportavimas taip pat svarbus</h2>
<p>Dalis gedimų atsiranda ne dėl naudojimo, o dėl prasto pervežimo. Šaldytuvą reikia vežti atsargiai, o po pervežimo dažnai verta palaukti prieš įjungiant. Skalbyklę ir indaplovę reikia gerai pritvirtinti, kad transportuojant jos negautų smūgių.</p>
HTML,
	),
	186 => array(
		'alt'     => 'Skelbimo nuotraukos fotografavimas telefonu',
		'caption' => 'Gera skelbimo nuotrauka turi būti šviesi, aiški ir parodyti tikrą daikto būklę.',
		'html'    => <<<HTML
<h2>Kaip paruošti pirmąją nuotrauką</h2>
<p>Pirmoji nuotrauka turėtų parodyti visą daiktą, o ne tik detalę. Jei parduodate įrankį, padėkite šalia visus priedus. Jei parduodate baldą, fotografuokite taip, kad matytųsi forma ir dydis. Jei tai technika, įsitikinkite, kad kadre nėra atsitiktinių daiktų, kurie painioja pirkėją.</p>
<p>Geriausia fotografuoti telefono kamera be stiprių filtrų. Per daug pakeltos spalvos ar kontrastas atrodo gražiai, bet pirkėjui gali sukelti įtarimą, kad bandote paslėpti realią būklę.</p>

<h2>Kokios nuotraukos reikalingos skelbime</h2>
<p>Minimalus rinkinys: bendra nuotrauka, vaizdas iš šono, detalės, komplektacija ir defektai. Jei yra serijos numeris ar modelio lipdukas, galima nufotografuoti taip, kad matytųsi modelis, bet nebūtų atskleista jautri informacija, kurios nenorite rodyti viešai.</p>
<p>Dideliems daiktams naudinga įdėti vieną mastelio nuotrauką: pavyzdžiui, baldą kambaryje arba techniką kieme. Tai padeda žmogui suprasti dydį ir sumažina klausimų skaičių.</p>

<h2>Ko vengti</h2>
<p>Venkite tamsių nuotraukų garaže su įjungta viena lempute, išplaukusių kadrų, per arti prikišto objektyvo ir nuotraukų, kuriose matosi daug asmeninių daiktų. Skelbimo nuotrauka turi parduoti daiktą, o ne rodyti visą kambarį.</p>
HTML,
	),
);

foreach ( $extras as $post_id => $data ) {
	$content = get_post_field( 'post_content', $post_id );
	if ( ! $content ) {
		WP_CLI::warning( "Post {$post_id} not found." );
		continue;
	}

	$attachment_id = (int) get_post_thumbnail_id( $post_id );
	if ( ! $attachment_id ) {
		WP_CLI::warning( "Post {$post_id} has no featured image." );
		continue;
	}

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $data['alt'] );
	$image_url = wp_get_attachment_image_url( $attachment_id, 'large' );
	$image_block = sprintf(
		"\n<!-- wp:image {\"id\":%d,\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"%s\" alt=\"%s\" class=\"wp-image-%d\"/><figcaption class=\"wp-element-caption\">%s</figcaption></figure>\n<!-- /wp:image -->\n",
		$attachment_id,
		esc_url( $image_url ),
		esc_attr( $data['alt'] ),
		$attachment_id,
		esc_html( $data['caption'] )
	);

	if ( false === strpos( $content, 'wp-image-' . $attachment_id ) ) {
		$content = preg_replace( '#</p>#', '</p>' . $image_block, $content, 1 );
	}

	if ( false === strpos( $content, $marker ) ) {
		$content .= "\n\n" . $marker . "\n" . $data['html'];
	}

	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $result ) ) {
		WP_CLI::warning( "{$post_id}: " . $result->get_error_message() );
		continue;
	}

	WP_CLI::log( "Updated {$post_id}" );
}

WP_CLI::success( 'Articles expanded.' );
