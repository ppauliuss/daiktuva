<?php
// Prideda slapuku/Google Analytics pastraipa i privatumo politika (jei dar nera).
$p = get_page_by_path( 'privatumo-politika', OBJECT, 'page' );
if ( ! $p ) { echo "NERA privatumo-politika\n"; return; }
if ( strpos( $p->post_content, 'Slapukai ir analitika' ) !== false ) { echo "Jau yra\n"; return; }
$add = <<<'EOT'


<h2>Slapukai ir analitika</h2>
<p>Svetainėje naudojame būtinuosius slapukus, reikalingus jos veikimui, ir, jūsų sutikimu, „Google Analytics" statistikos slapukus, kurie padeda suprasti, kaip lankytojai naudojasi svetaine. Statistikos ir reklamos slapukai įjungiami tik tada, kai paspaudžiate „Sutinku" slapukų pranešime. Savo pasirinkimą galite bet kada pakeisti išvalę naršyklės slapukus. Surinkti duomenys yra apibendrinti ir nenaudojami jūsų asmeniui identifikuoti.</p>
EOT;
wp_update_post( array( 'ID' => $p->ID, 'post_content' => $p->post_content . $add ) );
echo "Privatumo politika papildyta\n";
