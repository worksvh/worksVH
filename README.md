# VH Works – web a CMS

Web nástrojárny VH Works (Úvaly u Prahy) s jednoduchou administrací, ve které jde upravit každý text, obrázek a údaj na webu.

- PHP 8.2+ bez frameworku, SQLite (PDO), vanilla JS, žádný build krok
- Composer jen pro `phpmailer/phpmailer`
- Zadání: `SPEC.md`, schválené návrhy: `design/`, rozhodnutí: `DECISIONS.md`

## Lokální spuštění

```bash
composer install                 # PHPMailer (bez něj se e-maily jen ukládají do logu)
cp .env.example .env             # už existuje, pokud jste repo jen naklonovali, vytvořte
php app/cli.php migrate          # vytvoří storage/database.sqlite a naplní výchozí obsah
php app/cli.php create-admin vy@firma.cz
php -S localhost:8000 -t public public/router.php
```

Web: http://localhost:8000, administrace: http://localhost:8000/admin

**Přílohy poptávek lokálně:** vestavěný server PHP čte limity z `php.ini` (výchozí jsou 2 MB na soubor a 8 MB na celý formulář).
Pro test s většími přílohami spusťte server takto:

```bash
php -d upload_max_filesize=20M -d post_max_size=55M -d max_file_uploads=10 -S localhost:8000 -t public public/router.php
```

Na hostingu se limity nastaví samy přes `public/.htaccess` (mod_php) nebo `public/.user.ini` (PHP-FPM).

**E-maily lokálně:** když v `.env` (ani v Nastavení › E-mail a SMTP) není `SMTP_HOST`, e-maily se neodesílají,
ale celé se uloží do `storage/logs/mail/*.eml`.

## Struktura

```
public/          jediná veřejná složka (document root): index.php, router.php, assets/, media/
app/             bootstrap, config, db, cli.php, migrations/, content/schema.php + seed.php, views/, admin/, lib/
storage/         database.sqlite, inquiries/ (přílohy, neveřejné), logs/, backups/
design/          předlohy stránek (nemazat)
```

### Jak přidat nové editovatelné pole

1. Přidejte pole do `app/content/schema.php` (typ, `label`, `help`, `required`…).
2. Vypište ho v šabloně v `app/views/` (např. `<?= t($page['hero']['nove_pole']) ?>`).
3. Případně doplňte výchozí hodnotu do `app/content/seed.php`.

Formulář v administraci se vygeneruje sám. Existující obsah v DB se nepřepisuje, nové pole má výchozí hodnotu.

## Nasazení na sdílený hosting (Wedos, Forpsi, Active24)

Požadavky: PHP 8.2+, rozšíření `pdo_sqlite`, `fileinfo`, `dom`, `mbstring`; doporučeno `gd` (WebP varianty obrázků) a `zip` (zálohy).

1. Lokálně spusťte `composer install --no-dev` (vznikne složka `vendor/`).
2. Přes FTP/SFTP nahrajte celý projekt **kromě** `storage/database.sqlite`, `.env` a `public/media/*` (ty vzniknou na serveru).
3. **Document root nastavte na složku `public/`** (Wedos: Nastavení domény › Adresář; Forpsi/Active24: správa webhostingu).
4. Na serveru vytvořte `.env` podle `.env.example`: `APP_URL=https://www.vhworks.cz`, `APP_ENV=production`, SMTP údaje.
5. Spusťte migraci a vytvořte správce. Pokud hosting nemá SSH, spusťte příkazy lokálně a nahrajte hotový `storage/database.sqlite`:
   ```bash
   php app/cli.php migrate
   php app/cli.php create-admin majitel@vhworks.cz
   ```
6. Zkontrolujte práva zápisu pro `storage/` (včetně podsložek) a `public/media/`.
7. V `public/.htaccess` odkomentujte přesměrování na HTTPS.

### Když hosting nedovolí změnit document root

Nahrajte obsah `public/` do kořene webu (např. `www/`) a ostatní složky (`app/`, `storage/`, `vendor/`, `.env`) o úroveň výš,
mimo veřejný web. Pokud ani to nejde, nahrajte vše do kořene webu a:

- v kořenovém `index.php` (zkopírovaný `public/index.php`) upravte cestu na `require __DIR__ . '/app/bootstrap.php';`,
- v `app/bootstrap.php` nastavte `define('PUBLIC_PATH', BASE_PATH);`,
- nechte v `app/`, `storage/` a `vendor/` soubory `.htaccess` s `Require all denied` (v `app/` a `storage/` už jsou, pro `vendor/` ho vytvořte),
- ověřte, že `https://domena/storage/database.sqlite` a `https://domena/.env` vrací 403.

### SMTP

Údaje dostanete u hostingu (např. Wedos: `wes1-smtp.wedos.net`, port 587, STARTTLS, celá e-mailová adresa jako uživatel).
Zadejte je do `.env`, nebo v administraci v Nastavení › E-mail a SMTP (ty mají přednost).
Adresu, kam chodí poptávky, nastavte v Nastavení › E-mail a SMTP › E-mail pro poptávky.

### Cron (doporučeno)

```
0 3 * * *   php /cesta/k/webu/app/cli.php backup      # denní záloha, drží posledních 14
30 3 * * *  php /cesta/k/webu/app/cli.php anonymize   # anonymizace poptávek po lhůtě (jinak běží při přihlášení do adminu)
```

## Administrace

- První správce: `php app/cli.php create-admin email`. Další správce přidáte v Nastavení › Uživatelé. Registrace na webu není.
- Zapomenuté heslo: odkaz na přihlašovací stránce, pošle jednorázový odkaz platný 1 hodinu.
- Po 5 neúspěšných pokusech je přihlášení z dané IP zablokované na 15 minut.
- **K doplnění:** texty v [hranatých závorkách] jsou údaje, které ještě chybí od klienta. Přehled je vypíše s odkazem přímo na pole.
  Na webu se zobrazují, dokud v Nastavení › Logo a zobrazení nezapnete „Skrýt nevyplněné hodnoty na webu“.
- Každá stránka a položka má historii posledních 10 uložení s možností obnovit a tlačítko Náhled (neuložený obsah v nové záložce).
- Záloha: Nastavení › Záloha › Stáhnout zálohu (databáze + obrázky, bez příloh poptávek).

## Cookies a analytika

Web nepoužívá analytiku ani sledování, proto nemá cookie lištu. Jediná cookie (`vhw`) je technická relace
pro poptávkový formulář (ochrana CSRF) a administraci.
**Pokud se později přidá analytika (Google Analytics, Meta Pixel apod.), je nutné doplnit lištu se souhlasem**
a skripty spouštět až po souhlasu. Mapa je proto jen statický obrázek s odkazem na Mapy.cz, ne vložený iframe.

## Bezpečnost

- `app/`, `storage/` a `.env` jsou mimo document root, navíc s `.htaccess` `Require all denied`.
- V `public/media/` je zakázané spouštění skriptů; SVG se při nahrání čistí a servíruje se sandboxovou CSP.
- Přílohy poptávek leží v `storage/inquiries/{id}/` pod náhodnými jmény a stahují se jen po přihlášení přes PHP.
- CSRF token ve všech formulářích, prepared statements, escapování výstupů, whitelist pro rich text.
- Hlavičky CSP, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, X-Frame-Options; HSTS jen při `APP_ENV=production` na HTTPS.
- Chyby se logují do `storage/logs/`, návštěvníkovi se nevypisují.
