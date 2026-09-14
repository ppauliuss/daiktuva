<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$no_lt_authors = array( 2, 3, 5 );
$clean_authors = array( 4, 6, 10 );
$typo_authors  = array( 7 );

$rows = array(
	36 => array( 'Traktorius tvarkingas, kuriasi gerai, hidraulika veikia. Naudotas ukio darbams, dokumentai tvarkingi, galima apziureti vietoje.', 'Tinka zemes darbams, priekabai ar padargams tempti. Yra iprastu naudojimo zymiu, bet technika dar darbinga.' ),
	37 => array( 'Belarus 920 geros bukles, vaziuoja ir dirba normaliai. Variklis kuriasi, pavaru deze veikia, padangos dar tinkamos naudojimui.', 'Parduodamas, nes atnaujiname uki. Galima apziureti gyvai ir pasitikrinti vietoje.' ),
	38 => array( 'Dvivagis plugas, naudotas nedideliame ukyje. Remas sveikas, noragai dar tinkami darbui, niekas neluze.', 'Paprastas ir patikimas padargas arimui. Galima kabinti ir veztis ta pacia diena.' ),
	39 => array( 'Kultivatorius 2,5 m plocio, tinkamas dirvos paruosiui. Dantys vietoje, konstrukcija tvirta, naudotas kiekviena sezona.', 'Yra iprastu kosmetiniu zymiu, bet darbui netrukdo. Parduodamas del ukio technikos atnaujinimo.' ),
	40 => array( 'Sėjamoji Amazone tinkama grūdinių kultūrų sėjai. Mechanizmai veikia, bunkeris sandarus, komplekte yra pagrindiniai reguliavimo elementai.', 'Naudota ūkyje, prižiūrėta po sezono. Galima apžiūrėti ir pasitikrinti vietoje.' ),
	41 => array( 'Rotacine sienapjove, dirba graziai, diskai sukasi laisvai. Tinka nedideliam ar vidutiniam ukiui, kardanas komplekte.', 'Yra naudojimo zymiu, bet bukle gera. Parduodu, nes isigijau platesne sienapjove.' ),
	42 => array( 'Priekaba 2PTS-4, remas sveikas, bortai laikosi, hidraulika kelia. Tinka grudams, malkoms ar ukio reikmenims vezioti.', 'Padangos naudotos, bet dar vaziuojamos. Dokumentu bukle galima aptarti telefonu.' ),
	43 => array( 'Akumuliatorinis greztuvas Bosch, naudotas namuose ir smulkiems remonto darbams. Veikia gerai, komplekte baterija ir ikroviklis.', 'Korpusas turi naudojimo zymiu, bet mechaniskai tvarkingas. Galima apziureti ir isbandyti.' ),
	44 => array( 'Kampinis slifuoklis Makita, veikia normaliai, guoliai neskleidzia pasaliniu garsu. Naudotas metalo ir plyteliu pjovimui.', 'Parduodu be disku. Tinka tiek garazui, tiek statybos darbams.' ),
	45 => array( 'Betono maišyklė 140 l, naudota prie namo remonto. Variklis veikia, būgnas sukasi, konstrukcija stabili.', 'Yra cemento žymių, bet techninei būklei tai netrukdo. Galima pasiimti tą pačią dieną.' ),
	46 => array( 'Mini ekskavatorius, kuriasi gerai, hidraulika dirba. Tinka transejoms, sklypo darbams, smulkiems kasimo darbams.', 'Vikšrai naudoti, bet dar tvarkingi. Galima apziureti gyvai ir isbandyti.' ),
	47 => array( 'Grandininis pjuklas Husqvarna, kuriasi ir pjauna gerai. Naudotas malkoms, ne profesionaliai kasdienai.', 'Grandine dar pjauna, komplekte juosta. Yra iprastu naudojimo zymiu.' ),
	48 => array( 'VW Passat B6 2.0 TDI, vaziuojantis automobilis kasdienai. Variklis dirba, pavaru deze jungiasi, salonas naudotas.', 'Yra kosmetiniu defektu pagal metus. Kaina derinama prie automobilio apziuros.' ),
	49 => array( 'Priekaba lengvajam automobiliui, tvarkinga, su bortais. Tinka buities daiktams, sodo reikmenims ar statybinems medziagoms vezioti.', 'Naudota retkarčiais, laikyta sausai. Galima apžiūrėti ir prisikabinti vietoje.' ),
	50 => array( 'Šaldytuvas Samsung, veikia tyliai ir šaldo gerai. Vidus švarus, lentynos sveikos, durelės užsidaro sandariai.', 'Tinka butui, sodybai ar nuomojamam būstui. Parduodamas dėl virtuvės atnaujinimo.' ),
	51 => array( 'Skalbyklė Bosch, skalbia ir gręžia normaliai. Programos veikia, vandens neleidžia, būgnas sukasi tyliai.', 'Yra įprastų naudojimo žymių. Galima apžiūrėti ir susitarti dėl pasiėmimo.' ),
	52 => array( 'Benzinine vejapjove, kuriasi gerai, pjauna lygiai. Naudota sodyboje, peilis pagalastas praeita sezona.', 'Korpusas turi pabraizymu, bet darbui netrukdo. Parduodu, nes perejau prie akumuliatorines.' ),
	53 => array( 'Sofa-lova tvarkinga, išsiskleidžia miegui. Audinys geros būklės, mechanizmas veikia, yra patalynės dėžė.', 'Tinka svetainei, svečių kambariui ar nuomojamam butui. Galima apžiūrėti vietoje.' ),
	54 => array( 'Samsung A52 telefonas, ekranas sveikas, baterija laiko normaliai. Veikia su visais tinklais, kamera tvarkinga.', 'Yra keli smulkūs naudojimo pabraižymai. Komplekte telefonas ir įkrovimo laidas.' ),
	55 => array( 'Lenovo ThinkPad nešiojamas kompiuteris, tinkamas darbui, mokslams ir naršymui. Klaviatūra patogi, ekranas tvarkingas.', 'Baterija laiko pagal amžių. Įrašyta sistema, galima išbandyti prieš perkant.' ),
	56 => array( 'LG 50 colių televizorius, rodo gerai, garsas veikia, pultelis komplekte. Tinka svetainei ar sodybai.', 'Ekranas be matomų defektų, korpusas turi smulkių naudojimo žymių. Galima patikrinti vietoje.' ),
	57 => array( 'Kalnu dviratis 27,5 rato, vaziuoja gerai, stabdziai veikia. Tinka miestui, miskui ir lengvesniems takams.', 'Yra naudojimo zymiu, bet remas sveikas. Galima apziureti ir pravaziuoti.' ),
	58 => array( 'Vaikiškas vežimėlis tvarkingas, lengvai susilanksto. Ratai sukasi gerai, stabdis veikia, audinys švarus.', 'Naudotas vieno vaiko. Tinka kasdieniams pasivaikščiojimams mieste ar parke.' ),
	63 => array( 'Kombainas Claas, darbingas, naudotas ukio darbuose. Variklis kuriasi, pagrindiniai mazgai veikia, galima apziureti vietoje.', 'Yra sezoninio naudojimo zymiu. Platesne informacija telefonu, kaina derinama.' ),
	64 => array( 'Lauko purkstuvas, tinkamas ukio darbams. Talpa sandari, siurblys veikia, sijos issiskleidzia.', 'Naudotas kelis sezonus, laikytas po stogu. Parduodamas del technikos atnaujinimo.' ),
	65 => array( 'Sakiu krautuvas darbingas, kelia normaliai. Tinka sandeliui, ukiui ar statybiniu medziagu perkelimui.', 'Yra iprastu naudojimo zymiu. Galima apziureti vietoje ir patikrinti kelima.' ),
	66 => array( 'Traktoriaus padangos naudotos, protektoriaus dar yra. Tinka pakeitimui ar atsargai ukyje.', 'Bukle matosi nuotraukose. Del tiksliu ismatavimu ir pasiimimo skambinti telefonu.' ),
	67 => array( 'Suvirinimo aparatas tvarkingas, veikia gerai, naudotas garaže. Tinka smulkiems metalo darbams ir remontui.', 'Komplekte pagrindiniai laidai. Galima apžiūrėti ir išbandyti prieš perkant.' ),
	68 => array( 'Generatorius 5 kW, kuriasi ir tiekia srovę. Naudotas atsarginiam maitinimui sodyboje, ne kasdieniam darbui.', 'Tinka įrankiams, apšvietimui ar laikinam elektros poreikiui. Yra įprastų naudojimo žymių.' ),
	69 => array( 'Statybiniai pastoliai tvarkingi, naudoti namo fasado darbams. Konstrukcija tvirta, elementai susirenka normaliai.', 'Tinka remontui, dažymui ar apdailai. Galima apžiūrėti ir susitarti dėl transporto.' ),
	70 => array( 'Plytelių pjaustyklė naudota, bet dar gerai pjauna. Tinka vonios, virtuvės ar kitų patalpų remontui.', 'Yra naudojimo žymių, bet mechanizmas veikia. Galima apžiūrėti vietoje.' ),
	71 => array( 'Audi A4 B8 važiuoja, variklis dirba normaliai. Salonas naudotas tvarkingai, techninė būklė pagal metus gera.', 'Yra keli kosmetiniai defektai, del to kaina nera galutine. Galima apžiurėti po darbo valandų.' ),
	72 => array( 'Motoroleris važiuojantis, kuriasi, stabdžiai veikia. Tinka miestui ar trumpiems atstumams iki darbo.', 'Plastikai turi pabraižymu, bet naudojimui netrukdo. Dokumentai tvarkoje, galima apžiūrėt vietoje.' ),
	73 => array( 'Elektrinė viryklė veikia, kaitvietės šyla, orkaitė kepa. Naudota bute, parduodama dėl virtuvės keitimo.', 'Yra smulkiu naudojimo žymių, bet viskas veikia. Galima pasiimt savaitgalį.' ),
	74 => array( 'Indaplovė Bosch, plauna gerai, programos veikia. Naudota kasdien, bet prižiūrėta, filtrai valyti.', 'Priekyje yra keli pabraižimai, šiaip buklė gera. Pajungimą galima patikrinti vietoje.' ),
	75 => array( 'Dulkių siurblys veikiantis, traukia gerai. Tinka butui, sodybai ar kaip atsarginis siurblys.', 'Yra naudojimo žymiu ant korpuso, bet darbui netrukdo. Komplekte pagrindinis antgalis.' ),
	76 => array( 'Sodo baldų komplektas: stalas ir kėdės, naudoti terasoje. Konstrukcija stabili, sėdėti patogu.', 'Yra kelios dėmelės ir pabraižymai, bet atrodo tvarkingai. Galima apžiurėti gyvai.' ),
	77 => array( 'iPhone 12 veikia gerai, ekranas sveikas, Face ID veikia. Baterija laiko normaliai pagal amžių.', 'Korpusas turi smulkiu pabraižymų. Komplekte telefonas, galima patikrint prieš perkant.' ),
	78 => array( 'Žaidimų kompiuteris veikiantis, tinka žaidimams ir kasdieniam naudojimui. Komponentai tvarkingi, sistema paleista.', 'Yra keli kosmetiniai trukumai korpuse. Galima išbandyti vietoje, kaina derinama.' ),
	79 => array( '27 colių monitorius, rodo aiškiai, spalvos normalios. Tinka darbui, mokslams ar papildomam ekranui.', 'Stovas stabilus, yra smulkiu naudojimo žymių. Galima pajungti ir patikrint.' ),
	80 => array( 'Vaikiškas dviratukas tvarkingas, važiuoja lengvai. Tinka vaikui mokytis važiuoti arba kasdieniams pasivažinėjimams.', 'Yra pabraižymu nuo naudojimo, bet rėmas sveikas. Galima apžiūrėti vietoje.' ),
	81 => array( 'Irklavimo treniruoklis namams, veikia gerai. Tinka sportui namuose, neužima labai daug vietos.', 'Naudotas neilgai, bet yra viena kita naudojimo žymė. Galima išbandyt prieš perkant.' ),
	200 => array( 'Tvarkingas naudotas akumuliatorinis suktuvas namų remontui, baldams ir smulkiems darbams. Komplekte dvi baterijos ir įkroviklis.', 'Parduodu tvarkingą akumuliatorinį suktuvą su dviem baterijomis ir įkrovikliu. Naudotas namų remontui, veikia normaliai, baterijos laiko pagal amžių. Tinka baldams surinkti, lentynoms, smulkiems gręžimo ir sukimo darbams. Korpusas turi įprastų naudojimo žymių, bet niekas nelūžę. Galima apžiūrėti Vilniuje.' ),
);

$updated = 0;
foreach ( $rows as $product_id => $texts ) {
	$product_id = (int) $product_id;
	$post = get_post( $product_id );
	if ( ! $post || $post->post_type !== 'product' ) {
		continue;
	}

	wp_update_post( array(
		'ID'           => $product_id,
		'post_excerpt' => $texts[0],
		'post_content' => $texts[1],
	) );

	update_post_meta( $product_id, 'rank_math_description', mb_substr( wp_strip_all_tags( $texts[0] ), 0, 155 ) );
	$updated++;
}

echo 'UPDATED=' . $updated . PHP_EOL;
