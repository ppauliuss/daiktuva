<?php
// Likusiu 4 prekiu zmogiski aprasymai + pavadinimu raides.
$items = array(
54 => array('Mobilus telefonas Samsung A52',
  'Samsung A52, geras ekranas ir baterija, su dėklu.',
  'Samsung Galaxy A52, veikia sklandžiai, jokio strigimo. Ekranas didelis ir ryškus, baterijos lengvai užtenka dienai. Kamera fotografuoja gerai net ir prieblandoje. Korpusas tvarkingas, ekranas nuo pradžių buvo po stiklu, todėl be įbrėžimų. Pridėsiu dėklą ir kroviklį. Susitiksim, galėsite ramiai patikrinti.'),
55 => array('Nešiojamas Lenovo ThinkPad',
  'Lenovo ThinkPad, tvirtas darbinis nešiojamas, SSD diskas.',
  'Lenovo ThinkPad – tas pats legendinis darbinis nešiojamas, kuris tarnauja metų metus. Klaviatūra patogi, korpusas tvirtas, nebijo kasdienio nešiojimo. Viduje SSD diskas, todėl įsijungia per kelias sekundes. Baterija dar laiko, dienai biure užtenka. Naudotas darbui, prižiūrėtas. Tinka tiek studijoms, tiek namų reikalams.'),
56 => array('Televizorius LG 50 colių',
  'LG 50 colių Smart TV, vaizdas ryškus, su pultu.',
  'LG televizorius, 50 colių, Smart TV – telpa ir YouTube, ir kitos programėlės. Vaizdas ryškus, spalvos sodrios, žiūrėti malonu iš bet kurio kampo. Ekranas be dėmių ir defektų. Yra keli HDMI lizdai, pultas veikia. Naudotas namuose, prižiūrėtas. Padėsiu saugiai supakuoti pervežimui.'),
57 => array('Kalnų dviratis 27,5',
  'Kalnų dviratis 27,5, amortizatorius ir disciniai stabdžiai.',
  'Kalnų dviratis su 27,5 colio ratais. Priekinis amortizatorius minkština duobes, disciniai stabdžiai stabdo patikimai net ir šlapiu oru. Pavaros persijungia tiksliai, grandinė sutepta. Padangos su geru protektoriumi. Važinėtas miške ir mieste, prižiūrėtas. Tinka tiek pradedančiam, tiek mėgstančiam aktyvesnį važiavimą.'),
);
$ok = 0;
foreach ( $items as $id => $d ) {
	$r = wp_update_post( array( 'ID' => $id, 'post_title' => $d[0], 'post_excerpt' => $d[1], 'post_content' => $d[2] ), true );
	if ( ! is_wp_error( $r ) && $r ) { $ok++; } else { echo "FAIL $id\n"; }
}
echo "ATNAUJINTA: $ok / 4\n";
