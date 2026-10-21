# VH Works: web a jednoduché CMS (zadání pro Claude Code)

## 0. Jak s tímto zadáním pracovat

- Celé zadání si přečti dřív, než začneš psát kód. Potom si přečti všech pět souborů ve složce `design/`. Jsou to schválené návrhy stránek a mají přednost před tvým vkusem.
- Soubory v `design/` jsou ve formátu „Design Component“ (`<x-dc>`, `{{…}}`, `<sc-for>`, `<script type="text/x-dc">`). **Nekopíruj je jako šablony.** Ber je jako přesnou předlohu vzhledu: barvy, velikosti písma, odsazení, zaoblení, pořadí sekcí a texty. Všechny styly jsou inline, takže hodnoty vyčteš přímo.
- Pracuj po milnících podle kapitoly 13. Po každém milníku spusť web lokálně, ověř akceptační kritéria a commitni.
- Pokud narazíš na rozpor, drž se tohoto pořadí: (1) toto zadání, (2) `design/`, (3) zdravý rozum. Rozhodnutí sepiš do `DECISIONS.md`.
- Nepřidávej sekce, stránky ani obsah, které tu nejsou. Chceš-li něco navrhnout, napiš to do `DECISIONS.md` jako návrh.

## 1. Cíl a kontext

**Klient:** VH Works, nástrojárna v Úvalech u Prahy (Pražská 1473, 250 82 Úvaly, tel. +420 606 879 112). Má 30 let praxe. Dělá konstrukci v SolidWorks, výkresovou dokumentaci, prototypy, CNC obrábění, přípravky (návrh, výroba, montáž) a výrobu a opravy nástrojů.

**Cíl webu:** Získávat poptávky od **firem** (výroba, údržba, vývoj), ne od domácích kutilů. Návštěvník má do 5 vteřin pochopit, že jde o nástrojárnu u Prahy, která firmám navrhne a vyrobí přípravky, nástroje a díly, i když ještě nemají výkres. Hlavní akce je poslat výkres nebo poptávku, vedlejší je zavolat.

**Cíl CMS:** Majitel (netechnik) musí v administraci upravit **každý text, obrázek a údaj na webu**, aniž by sáhl do kódu. To platí i pro položky seznamů, jako jsou služby, zakázky, stroje a lidé.

## 2. Technologie (závazné)

Web poběží na běžném českém sdíleném hostingu (Wedos, Forpsi, Active24), proto:

- **PHP 8.2+** bez frameworku. Composer použij jen pro `phpmailer/phpmailer`.
- **SQLite** přes PDO. Jeden soubor databáze mimo veřejnou složku.
- **Vanilla JS** bez build kroku. Žádný npm, React, Tailwind ani bundler.
- **Apache** a `.htaccess` pro hezké URL. Lokálně musí web běžet přes `php -S localhost:8000 -t public public/router.php`.
- Fonty z Google Fonts: **Exo 2** (500, 600, 700, 800) a **Montserrat** (400, 500, 600, 700), s `display=swap`.
- Vše UTF-8, `lang="cs"`.

## 3. Struktura projektu

```
/
├─ public/                 ← jediná veřejná složka (document root)
│  ├─ index.php            ← front controller
│  ├─ router.php           ← pro php -S
│  ├─ .htaccess
│  ├─ assets/css/site.css
│  ├─ assets/css/admin.css
│  ├─ assets/js/site.js     ← klikací díl, filtr prací, drop zóna
│  ├─ assets/js/admin.js
│  ├─ assets/img/vh-works-logo.svg
│  └─ media/               ← obrázky nahrané přes CMS (jen obrázky, bez spouštění skriptů)
├─ app/
│  ├─ bootstrap.php, config.php (čte .env)
│  ├─ db.php, migrations/
│  ├─ content/schema.php   ← definice všech editovatelných polí (viz kap. 7.3)
│  ├─ content/seed.php     ← výchozí obsah převzatý z design/
│  ├─ views/layout.php, partials/, pages/
│  ├─ admin/               ← controllery a šablony administrace
│  └─ lib/ (auth, csrf, validation, mail, upload, image)
├─ storage/
│  ├─ database.sqlite
│  ├─ inquiries/           ← přílohy poptávek (NEVEŘEJNÉ)
│  ├─ logs/
│  └─ backups/
├─ design/                 ← předlohy, nemazat
├─ .env.example
├─ DECISIONS.md
└─ README.md               ← instalace, nasazení, první přihlášení
```

## 4. Design systém

Předlohou je `design/`. Hodnoty níže z ní vycházejí. Zapiš je jako CSS proměnné v `site.css`.

### 4.1 Barvy

| Token | Hodnota | Použití |
|---|---|---|
| `--bg` | `#EEF0F2` | podklad stránky |
| `--panel` | `#F9FAFB` | panely a karty |
| `--line` | `#CDD1D6` | okraje a dělicí čáry |
| `--line-soft` | `#DDE1E5` | jemné čáry, milimetrová síť |
| `--photo` | `#D6DADF` | podklad místa pro fotku |
| `--ink` | `#0E0F11` / `#141517` | text, tmavá tlačítka |
| `--ink-2` | `#44484D` | odstavce |
| `--ink-3` | `#55595E` | popisky |
| `--blue` | `#1A5FD6` | jediný akcent: odkazy, aktivní stavy, CTA blok, piny |
| `--white` | `#FFFFFF` | text na modré a na černé |

Pravidla:
- **Žádné přechody (gradienty), glassmorphism ani emoji.**
- Celý web je světlý. **Nepřepínej sekce na tmavé pozadí**, klient to výslovně odmítl.
- Modrý blok má vždy bílý text.

### 4.2 Typografie

| Prvek | Písmo | Hodnota |
|---|---|---|
| Nadpisy h1–h3 | Exo 2 600 | h1 `clamp(44px, 6.6vw, 104px)`, line-height 0.98, letter-spacing −0.045em |
| Technické popisky (štítky, formáty, „POZ. 1 / 4“) | Exo 2 500, VERZÁLKY | 12–13 px, letter-spacing 0.08em |
| Text | Montserrat 400/500 | 15–21 px, line-height 1.5 |

### 4.3 Tvary a rozvržení

- Obsah má max. šířku **1360 px**, boční odsazení `clamp(16px, 4vw, 40px)`.
- Zaoblení: velké panely 24 px, karty a fotky 18–20 px, tlačítka 999 px (pilulka).
- Klikací prvky mají minimálně 44 px na výšku.
- **Opakující se motivy oboru** (musí zůstat): milimetrový papír pod dílem, kóty, měřítko s ryskami jako oddělovač sekcí, hlavička panelu ve stylu výkresu („UPÍNACÍ DESKA · OCEL · M 1:2“) a označení „POZ.“.
- **Responzivita:** web funguje od 360 px. Mřížky se skládají do jednoho sloupce a menu se na mobilu zalamuje (bez hamburgeru; pokud se nevejde, použij jednoduché rozbalovací menu s `<button aria-expanded>`).

### 4.4 Logo

`design/vh-works-logo.svg` zkopíruj do `public/assets/img/` a vkládej ho jako `<img>` s `alt="VH Works – Tooling, Engineering, Precision"`, výška 46 px. Jde o obkreslenou verzi a klient dodá originál. Výměna loga musí jít přes CMS (Nastavení › Logo).

## 5. Stránky a URL

Každá stránka má vlastní rozvržení podle `design/`. **Nedělej jednu kostru s jiným textem.** Všechny texty níže jsou výchozí obsah (seed) a všechny musí jít upravit v CMS.

Zástupné hodnoty v hranatých závorkách, např. `[E-MAIL]`, `[STROJE]`, seeduj tak, jak jsou. Admin je zvýrazní jako „k doplnění“ (viz 7.6). Prázdná *nepovinná* pole web nezobrazí, ani jejich popisek.

### 5.1 `/`: Úvod (`design/01-uvod.html`)

1. **Hero:** řádek štítků (seznam textů oddělených „·“), H1, perex.
2. **Klikací díl:** panel s hlavičkou (levý a pravý text), vlevo SVG dílu na milimetrovém papíře (SVG převezmi z designu beze změn), přes něj 4 piny. Vpravo detail aktivního pinu: „POZ. n / 4“, nadpis, text, technický řádek a pod tím řada tlačítek se stejnými položkami.
   - Editovatelné položky (opakovač, přesně 4): `title`, `text`, `spec`, `x` a `y` v % (pozice pinu).
   - Chování: klik na pin nebo tlačítko přepne obsah, aktivní je černý a ostatní modré. Piny jsou `<button>` s `aria-label` a `aria-pressed`. Funguje šipkami i tabulátorem. Bez JS se zobrazí všechny 4 položky jako seznam.
3. **Měřítko** (SVG oddělovač).
4. **„Z naší dílny“:** nadpis, odkaz „Všechny práce →“ a 3 zakázky v rozvržení 1 velká + 2 menší. Zobrazují se zakázky označené v CMS „Na úvodní stránce“ (max. 3, řazení ručně).
5. **CTA blok** (modrý, sdílený, viz 6.3).

### 5.2 `/sluzby`: Služby (`design/02-sluzby.html`)

- Hero: štítek, H1.
- Dva sloupce: vlevo **obsah** (seznam kotevních odkazů na služby s číslem pozice a pod ním text s telefonem), vpravo **karty služeb** pod sebou.
- Karta služby: „POZ. n“ (automaticky podle pořadí), štítek vpravo nahoře, nadpis, popis a pod čarou 2 až 3 dvojice „popisek → hodnota“ (např. OD VÁS STAČÍ / DOSTANETE, nebo MAX. ROZMĚR / MATERIÁLY / SÉRIE).
- Každá služba má kotvu `#slug`.
- CTA blok.

### 5.3 `/prace`: Práce (`design/03-prace.html`)

- Hero: štítek, H1, perex vpravo.
- **Vybraná zakázka:** velká fotka vlevo, vpravo štítek s rokem, nadpis a trojice ZADÁNÍ / ŘEŠENÍ / VÝSLEDEK. Vybírá se v CMS přepínačem „Vybraná zakázka“ (právě jedna).
- **Filtr:** tlačítka „Vše“ a kategorie z CMS. Filtruje bez načtení stránky. Zachovej i URL `?kategorie=slug`, aby fungoval bez JS i jako odkaz.
- **Mřížka zakázek:** fotka 4:3, název, kategorie (modře, verzálky), technický řádek (materiál · ks · odvětví).
- CTA blok s vlastním nadpisem stránky.
- Detailní stránky zakázek **nedělej** (není v návrhu).

### 5.4 `/dilna`: Dílna (`design/04-dilna.html`)

- Hero: štítek, H1 vlevo, příběh firmy vpravo.
- Velká fotka na šířku.
- **Strojní park:** tabulka STROJ / TYP / PRACOVNÍ ROZSAH / NA CO HO POUŽÍVÁME, nad ní „Stav k …“. Na mobilu se tabulka posouvá do strany v rámečku.
- **Lidé:** nadpis a text vlevo, vpravo karty (portrét 4:5, jméno, role).
- **Návštěva:** panel s nadpisem, adresou, provozní dobou, poznámkou k parkování, modrým tlačítkem (odkaz na /kontakt) a mapou. Mapa je **statický obrázek nebo odkaz na Mapy.cz**, ne vložený iframe, který by načítal cookies třetích stran bez souhlasu.

### 5.5 `/kontakt`: Kontakt a poptávka (`design/05-kontakt.html`)

- Vlevo: štítek, H1, velké telefonní číslo (`tel:`), seznam údajů (e-mail, adresa, provozní doba, IČO a DIČ) a mapa.
- Vpravo: **poptávkový formulář** (kap. 9). Panel má hlavičku „POPTÁVKOVÝ LIST“ a „ODPOVÍDÁME DO X PRACOVNÍCH DNŮ“.
- V menu je tlačítko „Poptávka“ na této stránce modré (aktivní stav).

### 5.6 Další nutné stránky (jednoduché, bez vlastního designu)

- `/poptavka-odeslana`: poděkování, co bude následovat, telefon. Používá styl stránky Kontakt.
- `/ochrana-osobnich-udaju`: text stránky editovatelný v CMS. **Je povinná**, protože formulář sbírá osobní údaje. Seeduj text `[DOPLNIT: zásady zpracování osobních údajů]`.
- `404`: krátká zpráva a odkazy na Úvod a Kontakt, ve stylu webu.

## 6. Společné prvky

### 6.1 Hlavička

Logo (odkaz na /), menu Služby · Práce · Dílna, telefon (Exo 2) a tlačítko „Poptávka“ (černá pilulka). Aktivní položka je podtržená modrou čárou 3 px s `text-underline-offset: 8px` a má `aria-current="page"`. Položky menu (text a pořadí) jdou upravit v CMS.

### 6.2 Patička

Vlevo „VH Works · adresa“, vpravo „e-mail · IČO“. Přidej odkaz na Ochranu osobních údajů. Údaje se berou z globálního Nastavení.

### 6.3 CTA blok

Modrý panel se štítkem, nadpisem (bílý), tlačítkem „Poslat poptávku“ (černé, vede na /kontakt) a telefonem. Výchozí texty jsou v Nastavení, každá stránka je může přepsat vlastními.

## 7. CMS (administrace)

### 7.1 Přístup

- URL `/admin`. Přihlášení e-mailem a heslem (`password_hash` / `password_verify`), relace přes `HttpOnly`, `Secure` a `SameSite=Lax` cookie.
- Prvního uživatele vytvoří příkaz `php app/cli.php create-admin email@…`. Na webu žádná registrace.
- Uživatelé: v Nastavení jde přidat dalšího správce a změnit si heslo. Obnova hesla e-mailem přes jednorázový odkaz platný 1 hodinu.
- Po 5 neúspěšných pokusech zablokuj přihlášení z dané IP na 15 minut.

### 7.2 Rozvržení administrace

Jednoduché, česky, ve stejných barvách a písmech jako web, funkční i na tabletu.

Levé menu:
1. **Přehled:** počet nových poptávek, seznam polí „k doplnění“ (viz 7.6), rychlé odkazy.
2. **Stránky:** Úvod, Služby, Práce, Dílna, Kontakt, Poptávka odeslána, Ochrana osobních údajů.
3. **Služby** (sbírka)
4. **Zakázky** (sbírka) a **Kategorie zakázek**
5. **Stroje** (sbírka)
6. **Lidé** (sbírka)
7. **Poptávky** (doručené z formuláře)
8. **Média** (knihovna obrázků)
9. **Nastavení:** firma a kontakty, logo, menu, CTA blok, SEO a výchozí sdílecí obrázek, e-mail pro poptávky, SMTP, uživatelé, záloha.

### 7.3 Editor řízený schématem (klíčový požadavek)

- `app/content/schema.php` popisuje **každé** editovatelné pole: stránky (po sekcích), sbírky a nastavení. Administrace z něj **sama generuje formuláře**. Nové pole se přidá jen úpravou schématu, bez psaní dalšího adminu.
- Typy polí:

| Typ | Popis |
|---|---|
| `text` | jeden řádek, volitelně `maxLength` |
| `textarea` | více řádků |
| `richtext` | odstavce, **tučně**, odkaz a seznam. Jednoduchý editor bez knihovny, ukládá omezené HTML a sanitizuje ho přes whitelist tagů |
| `tags` | seznam krátkých textů (např. štítky v hero) |
| `image` | výběr z Médií nebo nahrání, povinný `alt` text |
| `number`, `select`, `toggle`, `url`, `email`, `tel` | |
| `repeater` | opakovatelná skupina polí s přidáním, smazáním a řazením přetažením i šipkami. Volitelně `min` a `max` (piny na úvodu: přesně 4) |
| `reference` | odkaz na položku sbírky (např. kategorie zakázky) |

- Každé pole má `label` (česky), `help` (nápověda pod polem), `required` a `default`.
- Formulář je rozdělený do skupin podle sekcí stránky ve stejném pořadí jako na webu. U každé skupiny je odkaz „Zobrazit na webu“ (kotva na sekci).
- Tlačítko **Uložit** má potvrzení „Uloženo“. Při odchodu s neuloženými změnami se zobrazí varování.
- **Náhled před uložením:** tlačítko „Náhled“ otevře stránku v nové záložce s neuloženým obsahem (uloženým v session).
- **Historie:** u každé stránky a položky posledních 10 verzí s možností obnovit.

### 7.4 Sbírky

| Sbírka | Pole |
|---|---|
| Služby | název, slug (automaticky, upravitelný), štítek vpravo nahoře, popis (richtext), dvojice popisek/hodnota (repeater, 2–3), pořadí, zobrazit ano/ne |
| Zakázky | název, kategorie (reference), fotka, technický řádek, rok, zadání, řešení, výsledek, „Vybraná zakázka“ (toggle, jen jedna, při zapnutí se u ostatních vypne), „Na úvodní stránce“ (toggle), pořadí, zobrazit ano/ne |
| Kategorie zakázek | název (tlačítko filtru), štítek (verzálky na kartě), slug, pořadí. Seed: Přípravky / PŘÍPRAVEK, CNC díly / CNC, Prototypy / PROTOTYP, Opravy / OPRAVA |
| Stroje | název, typ, pracovní rozsah, na co se používá, pořadí |
| Lidé | jméno, role, portrét, pořadí |

Seznam položek: tabulka s náhledem, vyhledáváním a řazením přetažením. Mazání vyžaduje potvrzení.

### 7.5 Média

- Nahrání JPG, PNG, WebP a SVG (SVG jen pro logo a sanitizované), max. 10 MB.
- Po nahrání vygeneruj varianty WebP (480, 960, 1600 px) a na webu použij `<img srcset sizes loading="lazy">`. Pokud chybí GD nebo Imagick, ulož jen originál a zapiš to do logu.
- U každého obrázku je `alt` text a vidět, kde je obrázek použitý. Použitý obrázek nejde smazat.

### 7.6 „K doplnění“

Každé pole, jehož hodnota obsahuje text v hranatých závorkách `[…]`, je v adminu označené žlutým štítkem „K doplnění“. Přehled je vypíše jako seznam odkazů přímo na dané pole. Na veřejném webu se takové hodnoty **zobrazují**, protože klient web zatím ukazuje jako návrh. V Nastavení je přepínač „Skrýt nevyplněné hodnoty na webu“, který je na webu nahradí prázdnem.

### 7.7 Poptávky v adminu

Seznam obsahuje datum, firmu, jméno, kontakt a stav (Nová / Vyřizuje se / Hotovo). Detail zobrazí všechna pole, stažení příloh (jen po přihlášení, přes PHP, ne přímým odkazem), změnu stavu a interní poznámku. Poptávky starší než 24 měsíců se automaticky anonymizují a jejich přílohy se smažou (lhůta je nastavitelná).

## 8. Datový model (SQLite)

```
users(id, email UNIQUE, password_hash, name, created_at, last_login_at)
password_resets(token_hash, user_id, expires_at)
login_attempts(ip, attempted_at)
pages(key PRIMARY KEY, data JSON, updated_at, updated_by)      -- key: home, services, work, workshop, contact, thanks, privacy
settings(key PRIMARY KEY, data JSON, updated_at)              -- key: company, menu, cta, seo, mail, display
items(id, collection, slug, data JSON, sort, visible, created_at, updated_at)
revisions(id, entity_type, entity_key, data JSON, created_at, user_id)
media(id, filename, original_name, mime, width, height, alt, variants JSON, created_at)
inquiries(id, created_at, company, name, email, phone, quantity, deadline, message, status, note, ip_hash, consent_at)
inquiry_files(id, inquiry_id, stored_name, original_name, mime, size)
```

Obsah stránek a položek je JSON validovaný proti schématu při každém uložení. Migrace jsou v `app/migrations/` a spouští je `php app/cli.php migrate`.

## 9. Poptávkový formulář

- Pole podle `design/05-kontakt.html`: Firma*, Jméno*, E-mail*, Telefon, Počet kusů, Potřebný termín, Popis* a přílohy (drag & drop zóna a klasický výběr souborů).
- Přidej povinný checkbox „Souhlasím se zpracováním osobních údajů“ s odkazem na /ochrana-osobnich-udaju.
- **Přílohy:** max. 5 souborů, každý max. 20 MB, celkem max. 50 MB. Povolené přípony: pdf, step, stp, igs, iges, dwg, dxf, jpg, jpeg, png, zip. Kontroluj příponu i MIME. Soubory ukládej do `storage/inquiries/{id}/` pod náhodným jménem. Limity zobraz uživateli ještě před odesláním (JS) a znovu je ověř na serveru.
- **Ochrana proti spamu:** skryté honeypot pole, minimální doba vyplnění 3 s, max. 5 odeslání z jedné IP za hodinu. Bez CAPTCHA od třetích stran.
- **Po odeslání:**
  1. uložit do DB;
  2. poslat e-mail firmě (adresa z Nastavení) přes SMTP (PHPMailer) se všemi údaji a odkazem do adminu, přičemž přílohy do 10 MB celkem přilož přímo;
  3. poslat potvrzení zákazníkovi s textem z CMS;
  4. přesměrovat na /poptavka-odeslana.
- Při chybě validace se formulář vrátí s vyplněnými hodnotami a chybami u polí (`aria-describedby`, `aria-invalid`).
- Formulář musí fungovat i **bez JavaScriptu**.

## 10. SEO, výkon, přístupnost

- **SEO:** každá stránka má v CMS title, description a sdílecí obrázek (s výchozí hodnotou z Nastavení). Dále `canonical`, Open Graph, `sitemap.xml`, `robots.txt` a JSON-LD `LocalBusiness` (název, adresa, telefon, otevírací doba) z Nastavení. URL jsou česky bez diakritiky.
- **Výkon:** Lighthouse na mobilu má mít Performance ≥ 90 a Accessibility ≥ 95. CSS do 40 kB, JS do 20 kB. Fonty mají `preconnect`. Obrázky jsou lazy (kromě první fotky na stránce) a mají rozměry, aby nenastal posun rozvržení.
- **Přístupnost:** WCAG 2.1 AA, kontrast textu 4.5:1, viditelný focus (modrý obrys 2 px), skip link „Přeskočit na obsah“, správné nadpisy, `alt` u obrázků a dekorativní SVG s `aria-hidden`.
- **Cookies:** web nepoužívá analytiku ani sledování, takže cookie lišta není potřeba. Pokud se analytika přidá později, musí se doplnit lišta se souhlasem. Zapiš to do README.

## 11. Bezpečnost

- CSRF token ve všech formulářích (adminu i webu).
- Všechny výstupy escapovat (`htmlspecialchars`). `richtext` sanitizovat přes whitelist.
- Pouze prepared statements, žádné skládání SQL z řetězců.
- `storage/`, `app/` a `.env` jsou mimo document root. Pro jistotu do nich dej `.htaccess` s `Require all denied`.
- V `public/media/` zakaž spouštění PHP (`.htaccess`).
- Hlavičky: `Content-Security-Policy` (self a Google Fonts), `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`. HSTS jen na produkci.
- Chyby se logují do `storage/logs/` a návštěvníkovi se nevypisují.

## 12. Nasazení a zálohy

- `README.md` obsahuje lokální spuštění, nasazení na sdílený hosting přes FTP/SFTP (document root na `public/`, nebo variantu, kdy hosting document root změnit nedovolí), nastavení `.env`, SMTP, `create-admin` a `migrate`.
- **Záloha:** Nastavení › Záloha › „Stáhnout zálohu“ vytvoří ZIP s databází a složkou `media/` (bez příloh poptávek). Volitelně příkaz `php app/cli.php backup` pro cron (uchovává posledních 14 záloh).
- `.env.example` obsahuje APP_URL, APP_ENV, SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM a INQUIRY_TO.

## 13. Postup po milnících

Každý milník končí funkčním stavem a commitem.

1. **Základ:** struktura, router, layout, design tokeny, hlavička, patička a CTA. Kritérium: prázdné stránky jdou proklikat přes menu a sedí vizuálně s `design/`.
2. **Statický web:** všech 5 stránek podle `design/`, obsah zatím ze `seed.php`. Kritérium: na 1440 px vypadají stránky jako návrhy, na 375 px nic nepřetéká, klikací díl a filtr fungují s JS i bez něj.
3. **Databáze a schéma:** migrace, `schema.php`, `seed.php` naplní DB a web čte obsah z DB. Kritérium: změna hodnoty v DB se projeví na webu.
4. **Admin:** přihlášení, generované formuláře, sbírky, média, náhled, historie a „K doplnění“. Kritérium: **každý viditelný text a obrázek na webu jde změnit v adminu** (projdi stránky jednu po druhé a odškrtej si to v `DECISIONS.md`).
5. **Poptávky:** formulář, uploady, e-maily, správa v adminu a ochrana proti spamu. Kritérium: odeslání s 5 přílohami projde, s 6. nebo nepovoleným typem selže s jasnou chybou a e-mail dorazí (lokálně stačí zachytit do logu).
6. **Dokončení:** SEO, sitemap, JSON-LD, bezpečnostní hlavičky, 404, zálohy a README. Kritérium: Lighthouse podle kap. 10, ruční test klávesnicí a kontrola kontrastu.

## 14. Co zatím chybí od klienta (seedovat jako `[…]`)

E-mail, IČO, DIČ, provozní doba, rok založení, krátký příběh firmy, seznam strojů s rozsahy, maximální rozměr obrobku, materiály, velikost sérií, typická přesnost, obvyklá dodací lhůta, doba odpovědi na poptávku, jména a role lidí, fotky (dílna, stroje, zakázky, portréty), reference zakázek (se souhlasem zákazníků), originální logo (SVG nebo PDF) a text zásad ochrany osobních údajů.
