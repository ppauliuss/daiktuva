# Daiktuva

WordPress + WooCommerce skelbimu portalas, veikiantis gyvai: <https://daiktuva.lt>

Visas custom funkcionalumas parasytas kaip `mu-plugins` sluoksnis (55 failai),
kad isliktu per temos ir pluginu atnaujinimus. Tema - `daiktuva-child` su
WooCommerce sablonu perrasymais.

Stackas veikia kaip katalogas:

- prekes su nuotraukomis, kaina, aprasymu ir kategorijomis;
- pardavejo telefonas prie prekes;
- busena `Aktyvi`, `Rezervuota`, `Parduota`;
- krepšelis ir checkout isjungti;
- veliau galima ijungti WooCommerce pirkima ir Dokan pardaveju marketplace funkcijas.

## Stackas

- WordPress `php8.3-apache`, MariaDB 11.4, Redis 7, Meilisearch
- WooCommerce + Dokan Lite (pardaveju marketplace)
- Cache Enabler + Redis Object Cache
- Cloudflare Tunnel (origin IP neskelbiamas)
- Docker Compose, LXC konteineryje

## Architektura

Custom logika suskirstyta i temines `mu-plugins` grupes:

| Sritis | Ka daro |
| --- | --- |
| Katalogas | katalogo rezimas, skelbimu limitai, kortelu vienodinimas, perziuru skaitliukas, rate limit |
| Pardavejai | pardavejo tapatybe ir SVG avatarai, parduotuviu sarasas, registracijos patvirtinimas, Dokan i18n |
| Autentifikacija | registracijos piltuvas, Google prisijungimas, Turnstile, el. pasto patvirtinimas |
| SEO | Product/Offer JSON-LD, OpenGraph, FAQ schema, IndexNow, `llms.txt`, AI robots |
| Nasumas | puslapiu ir paieskos pilnas cache, WebP generavimas, ikeliamu foto optimizavimas |
| Sauga | REST user endpointu slepimas, XML-RPC isjungimas, prisijungimu ribojimas, CSP ataskaitos |
| Pranesimai | SMTP, Telegram pranesimai, GA4 ivykiai, pasto sveikatos endpointas |
| REST API | `daiktuva/v1` skelbimu feed'as ir busenos keitimas |

## Failai

- `.env.example` - konfiguracijos sablonas (tikras `.env` neversijuojamas).
- `docker-compose.yml` - konteineriai.
- `wp-content/mu-plugins/` - visas custom funkcionalumas.
- `wp-content/themes/daiktuva-child/` - tema ir WooCommerce sablonai.
- `scripts/` - WP-CLI paruosimas, turinio importas, smoke testai.

## Paleidimas lokaliai arba serveryje

Reikia Docker su Compose pluginu.

```powershell
cd C:\Users\Justina\Projects\daiktuva
docker compose up -d db redis wordpress
docker compose --profile tools run --rm wpcli
```

Tada lokaliai:

```text
http://localhost:8088
```

Admin:

```text
http://localhost:8088/prisijungimas-dk7
```

Prisijungimas:

- vartotojas: `adminas`
- slaptažodis: žiūrėti `.env` faile `WORDPRESS_ADMIN_PASSWORD`

Viešoje produkcijoje `/wp-admin` ir `/wp-login.php` slepiami per WPS Hide Login.
Nelaikyk slaptažodžių versijuojamoje dokumentacijoje; naudok `.env`.

## Cloudflare DNS variantas

Dabartinis `docker-compose.yml` WordPress porta pririsa tik prie `127.0.0.1:8088`, kad serveris nebutu atvertas i interneta. Jei kada nors noresi naudoti tiesiogini Cloudflare DNS be Tunnel, reikes papildomo reverse proxy arba pakeisti portu publikavima.

Tiesioginio DNS pavyzdys, jei turi public reverse proxy:

```text
A      daiktuva.lt       SERVERIO_IP    Proxied
CNAME  www               daiktuva.lt    Proxied
```

Tokiu atveju ugniasieneje verta leisti `80/443` tik is Cloudflare IP adresu.

## Cloudflare Tunnel variantas

Jei nenori viesinti serverio IP, geriausias kelias yra Cloudflare Tunnel.

1. Cloudflare Zero Trust sukurk tunnel `daiktuva`.
2. Public hostname:

```text
daiktuva.lt -> http://wordpress:80
www.daiktuva.lt -> http://wordpress:80
```

3. Nukopijuok tunnel token i `.env`:

```text
CLOUDFLARE_TUNNEL_TOKEN=...
```

4. Paleisk:

```powershell
docker compose --profile tunnel up -d
```

Tada nereikia atidaryti serverio `80/443` portu i interneta.

## Po pirmo paleidimo

WordPress admin dalyje:

1. Eik i `Settings -> Daiktuva`.
2. Irasyk bendra telefono numeri.
3. `Products -> Categories` susikurk kategorijas.
4. `Products -> Add New` pridek preke.
5. Prekes `General` skiltyje nustatyk pardavejo telefona ir busena.

## MVP kategorijos

Pradziai siulyciau:

- Zemes ukis
- Technika
- Irankiai
- Statyba
- Namai
- Transportas
- Elektronika
- Kita

## Svarbu del IP

Cloudflare paslepia serverio IP tik tada, kai:

- DNS irasai yra `Proxied`;
- nera `DNS only` subdomenu i ta pati serveri;
- pastas nera hostinamas tame paciame serveryje su matomu MX/A irasu;
- origin serveris neatsako tiesiogiai visam internetui arba naudojamas Cloudflare Tunnel.

## REST API

Sava `daiktuva/v1` erdve (`wp-content/mu-plugins/daiktuva-rest-api.php`).
Naudinga mobiliai app'ei, partneriu feed'ams ir busenos keitimui is isores.

| Metodas | Marsrutas | Prieiga |
| --- | --- | --- |
| `GET` | `/wp-json/daiktuva/v1/listings` | vieša |
| `GET` | `/wp-json/daiktuva/v1/listings/<id>` | vieša |
| `POST` | `/wp-json/daiktuva/v1/listings/<id>/status` | reikia `edit_post` teisiu prekei |

Kolekcijos parametrai: `status` (`active`/`reserved`/`sold`), `category` (slug),
`search`, `page`, `per_page` (1-50, numatyta 20). Bendras kiekis grazinamas
`X-WP-Total` ir `X-WP-TotalPages` antrastese.

Telefono numeris grazinamas tik vienos prekes atsakyme - masinis feed'as su
numeriais butu scraping'o taikinys.

```bash
# aktyvus skelbimai
curl 'https://daiktuva.lt/wp-json/daiktuva/v1/listings?status=active&per_page=5'

# busenos keitimas (application password)
curl -u 'adminas:APP_PASSWORD' \
  -X POST 'https://daiktuva.lt/wp-json/daiktuva/v1/listings/123/status' \
  -H 'Content-Type: application/json' \
  -d '{"status":"sold"}'
```

Busena rasoma i ta pacia `_daiktuva_status` meta reiksme, kuria naudoja
katalogo rezimas, todel pakeitimas is karto matomas prekes zenkliuke; po
irasymo isvalomas tos prekes Cache Enabler puslapio cache.
