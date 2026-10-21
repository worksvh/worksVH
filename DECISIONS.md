# Rozhodnutí a návrhy

Pořadí priorit podle zadání: (1) SPEC.md, (2) design/, (3) zdravý rozum.

## Rozpory a jejich řešení

1. **Velký telefon na Kontaktu.** Návrh `05-kontakt.html` ho nemá, zadání (5.5) ano → přidán pod H1 (Exo 2 600, `tel:` odkaz).
2. **CTA blok.** Návrh má dvě podoby: velkou na Úvodu (štítek + nadpis + tlačítko + telefon) a menší na Službách a Pracích (jen nadpis + tlačítko).
   Zadání (6.3) říká, že blok má štítek, nadpis, tlačítko a telefon → všechny CTA bloky obsahují všechny čtyři prvky,
   na Úvodu ve velké velikosti, jinde v menší podle návrhu. Dílna a Kontakt CTA nemají (není v návrhu).
3. **Zakázky na Úvodu vs. na Pracích.** Návrh Úvodu má jiné zástupné názvy („Upínací přípravek pro [ODVĚTVÍ]“) než mřížka na Pracích.
   Zadání chce, aby se na Úvodu zobrazovaly zakázky označené „Na úvodní stránce“ → jsou to tytéž položky, seed převzal texty z návrhu Prací
   a první tři (přípravek, CNC díl, oprava) jsou označené pro Úvod. Pravý popisek u fotky na Úvodu = štítek kategorie
   (návrh má u velké fotky „KONSTRUKCE · CNC · MONTÁŽ“, ten by vyžadoval další pole bez opory v zadání).
4. **Vybraná zakázka v mřížce.** Zakázka označená „Vybraná zakázka“ se v mřížce pod filtrem znovu neopakuje (v návrhu tam také není).
5. **Filtr prací** je udělaný z odkazů `?kategorie=slug` (funguje bez JS, jde sdílet), JS je přepne bez načtení stránky
   a nastaví jim `role="button"`; aktivní má `aria-current="true"`. Stav „aktivní“ vypadá jako v návrhu (černé tlačítko).
6. **Klikací díl bez JS:** piny a detail se schovají a zobrazí se seznam všech 4 položek (`<noscript>` styl, žádné problikávání).
   Pozice pinů z CMS se zapisují do `<style nonce>` v hlavičce, takže CSP nepotřebuje `unsafe-inline`.
7. **Mapa:** statický obrázek z Médií (Nastavení › Firma a kontakty › Obrázek mapy) s odkazem na mapu (výchozí odkaz na profil „Nástrojaři Úvaly“ v Google Mapách; jde změnit na Mapy.cz). Dokud obrázek chybí, je tam šedé místo
   s textem „[MAPA: …]“ jako v návrhu. Žádný iframe (cookies třetích stran).
8. **Zástupné fotky:** pole obrázku má `media` a `alt`. Dokud fotka chybí, `alt` obsahuje popis z návrhu („[FOTO: …]“), web ho ukáže v šedém místě
   a admin ho hlásí jako „K doplnění“. Po nahrání fotky se alt přepíše skutečným popisem.
9. **Každý text editovatelný** (požadavek klienta): kromě obsahu jsou v CMS i drobné texty – popisky formuláře, záhlaví tabulky strojů,
   „POZ.“, text „Vše“ ve filtru, hlášky formuláře, stránka 404, odkaz „Přeskočit na obsah“, popisy pro čtečky obrazovky (aria-label).
   Z kódu nejdou měnit jen technické věci: struktura stránek, SVG dílu a měřítka, ikona nahrávání.
10. **Logo:** výchozí je `public/assets/img/vh-works-logo.svg` (obkreslená verze). Nastavení › Logo a zobrazení › Logo ho nahradí
    nahraným souborem (SVG se sanitizuje). Alt je editovatelný, výchozí „VH Works – Tooling, Engineering, Precision“.
11. **Menu na mobilu:** položky se zalomí pod logo (bez hamburgeru), na 360 px se vejdou do dvou řádků → rozbalovací menu nebylo potřeba.
12. **Cookie `Secure`:** nastavuje se jen na HTTPS nebo při `APP_ENV=production`. Na `http://localhost` by jinak přihlášení nefungovalo.
13. **Relace na veřejném webu** se zakládá jen na `/kontakt` (CSRF a měření doby vyplnění) a pro přihlášeného admina (náhled). Ostatní stránky
    cookie nenastavují.
14. **Doba vyplnění 3 s** se měří od zobrazení formuláře (čas v relaci). Honeypot vrací „úspěch“ bez uložení, ať robot nepozná, že neprošel.
15. **Limit 5 odeslání za hodinu** se počítá z uložených poptávek podle hashe IP (HMAC s tajným klíčem `storage/app.key`, IP se neukládá v čitelné podobě).
16. **MIME kontrola CAD souborů:** STEP/IGES/DXF a DWG `finfo` obvykle hlásí jako `text/plain` nebo `application/octet-stream`.
    Pro každou příponu je seznam přípustných MIME (`app/lib/validation.php`), ostatní (PHP, HTML, spustitelné soubory…) se odmítnou.
17. **Anonymizace poptávek** po lhůtě (výchozí 24 měsíců, Nastavení › E-mail a SMTP) běží při přihlášení do adminu (max. 1× za hodinu)
    a volitelně cronem `php app/cli.php anonymize`. Přidán sloupec `inquiries.anonymized_at`. Poptávku jde i ručně smazat (žádost o výmaz).
18. **Nastavení** má kromě klíčů ze zadání (company, menu, cta, seo, mail, display) ještě `notfound` (texty stránky 404) a `form_msgs`
    (hlášky formuláře), aby šly upravit všechny texty.
19. **SMTP** jde zadat v `.env` i v administraci; hodnoty z administrace mají přednost, prázdné pole = hodnota z `.env`.
    Heslo se v administraci nikdy nevypisuje zpět.
20. **Milníky 2 a 3** se v praxi spojily: web čte obsah z DB od začátku (seed se do DB nahraje při `migrate`). Kritérium M3
    („změna v DB se projeví na webu“) ověřeno, viz níže.
21. **Smazání položky** se zapíše do historie (poslední verze dat), takže jde v nouzi dohledat v DB.
22. **Kategorii** používanou zakázkami nejde smazat (zakázka by ztratila kategorii).

23. **Písma hostovaná lokálně.** Zadání chce Exo 2 a Montserrat z Google Fonts s `display=swap` a `preconnect`. Jde o stejná písma
    z Google Fonts (licence OFL), jen soubory leží v `public/assets/fonts/` a místo `preconnect` se přednačítají (`preload`).
    Důvod: CSS z fonts.googleapis.com blokovalo vykreslení o 1–3 s a výměna písma posouvala rozvržení (Lighthouse mobil: výkon 77–89, CLS 0,197).
    Po změně výkon 98 a CLS 0 na všech stránkách. Vedlejší přínos: prohlížeč návštěvníka neposílá IP adresu Googlu (GDPR) a CSP nepotřebuje cizí domény.
24. **Lighthouse (mobil, lokálně přes `php -S`, 21. 10. 2026):** všech 5 stránek výkon 98, přístupnost 100, best practices 100, SEO 100, CLS 0.
    CSS webu 22 kB, JS 7,5 kB. Na Apache se navíc zapne komprese (`mod_deflate` v `.htaccess`).
    Ruční test klávesnicí: skip link, logické pořadí, modrý obrys 2 px u všech prvků, piny a štítky dílu ovladatelné šipkami.

25. **Databáze Turso místo souboru SQLite** (pokyn klienta, 21. 10. 2026). Turso je SQLite kompatibilní, takže schéma
    a dotazy zůstaly. Napojení přes HTTP API bez rozšíření PHP (sdílený hosting). Protože každý dotaz je cesta po síti
    (z Prahy do eu-west-1 asi 0,3 s), veřejný web čte obsah z lokální kopie (`storage/cache/mirror.sqlite`) a admin ukládá
    stránku i historii jedním požadavkem. Přihlášený uživatel se ověřuje v DB nejvýš jednou za minutu, počet nových poptávek
    za 30 s. Lokální SQLite zůstává jako záložní režim (prázdné `TURSO_URL`).

## Návrhy (neimplementováno)

- Po dodání fotek zvážit `<link rel="preload">` pro první fotku stránky (LCP).
- Až bude známý obor zákazníků, doplnit do zakázek pole „Odvětví“ jako samostatnou hodnotu (teď je součástí technického řádku).

## Kontrola milníku 4: každý viditelný text a obrázek jde změnit v adminu

Ověřeno 21. 10. 2026: šablony webu (`app/views/`) neobsahují žádný viditelný text mimo hodnoty z CMS
(automatická kontrola + ruční průchod). Natvrdo zůstává jen lišta náhledu (vidí ji jen přihlášený admin), skryté pole proti spamu
a kóty v SVG dílu (součást výkresu z návrhu).

**Společné prvky**
- [x] Logo a jeho alt – Nastavení › Logo a zobrazení
- [x] Položky menu (text, odkaz, pořadí), tlačítko Poptávka, popis menu pro čtečky – Nastavení › Menu a patička
- [x] Telefon v hlavičce, patička (firma, adresa, e-mail, IČO), odkaz na zásady – Nastavení › Firma a kontakty, Menu a patička
- [x] CTA blok (štítek, nadpis, tlačítko, odkaz) – Nastavení › CTA blok + přepsání na každé stránce
- [x] Odkaz „Přeskočit na obsah“ – Nastavení › Logo a zobrazení
- [x] SEO titulek, popis, sdílecí obrázek – na každé stránce + výchozí v Nastavení › SEO

**Úvod** – štítky, H1, perex · hlavička panelu (vlevo, vpravo), „POZ.“, 4 piny (nadpis, text, technický řádek, pozice x/y) ·
nadpis „Z naší dílny“ a odkaz · 3 zakázky (Zakázky › Na úvodní stránce: název, fotka, kategorie) · CTA. [x]

**Služby** – štítek, H1 · popis seznamu, text s telefonem, „POZ.“ · karty ze sbírky Služby (název, slug/kotva, štítek, popis, 2–3 dvojice, pořadí, zobrazit) · CTA. [x]

**Práce** – štítek, H1, perex · vybraná zakázka (štítek, popisky Zadání/Řešení/Výsledek; obsah ze zakázky) ·
filtr (text „Vše“, kategorie ze sbírky Kategorie, text prázdného filtru) · karty (fotka, název, štítek kategorie, technický řádek) · CTA. [x]

**Dílna** – štítek, H1, příběh · velká fotka · strojní park (nadpis, stav k datu, 4 záhlaví, řádky ze sbírky Stroje) ·
lidé (nadpis, text, karty ze sbírky Lidé) · návštěva (nadpis, adresa a provozní doba z Nastavení, parkování, tlačítko, mapa). [x]

**Kontakt** – štítek, H1, velký telefon, 4 popisky údajů a údaje z Nastavení, mapa · formulář (hlavičky panelu, nadpis,
7 popisků polí, texty přílohy a limitů, souhlas a odkaz, poznámka, tlačítko, hláška chyby) · hlášky validace (Nastavení › Hlášky formuláře). [x]

**Poptávka odeslána** – štítek, H1, text (rich text), text nad telefonem, odkaz zpět. [x]
**Ochrana osobních údajů** – štítek, H1, text (rich text). [x]
**404** – štítek, nadpis, text, 2 odkazy (Nastavení › Stránka 404). [x]
**E-maily** – předměty a text potvrzení zákazníkovi (Nastavení › E-mail a SMTP). [x]
