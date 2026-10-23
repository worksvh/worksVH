# VH Works – web a CMS

Web nástrojárny VH Works (Úvaly u Prahy) s jednoduchou administrací, ve které jde upravit každý text, obrázek a údaj na webu.

- Next.js 16 (App Router), React 19, TypeScript; vzhled je čisté CSS v `public/assets/css/`, drobné skripty v `public/assets/js/`
- Databáze Turso (libSQL), soubory ve Vercel Blob, e-maily přes Resend, hosting Vercel
- Zadání: `SPEC.md`, schválené návrhy: `design/`, rozhodnutí: `DECISIONS.md`

## Lokální spuštění

```bash
npm install
cp .env.example .env        # vyplňte TURSO_URL, TURSO_TOKEN, APP_KEY (viz komentáře v souboru)
npm run migrate             # vytvoří/aktualizuje tabulky a doplní výchozí obsah, existující nepřepíše
npm run create-admin -- vy@firma.cz
npm run dev
```

Web: http://localhost:3000, administrace: http://localhost:3000/admin

- **Bez `BLOB_READ_WRITE_TOKEN`** se obrázky ukládají do `public/media/` a přílohy poptávek do `storage/poptavky/`.
- **Bez `RESEND_API_KEY` nebo `MAIL_FROM`** se e-maily neodesílají, uloží se do `storage/logs/mail/*.txt` (na Vercelu jen do logu).
- Lokálně jde použít i soubor místo Turso: `TURSO_URL=file:storage/local.db`.

## Nasazení na Vercel

Repo je napojené na Vercel, každý push do `main` se sám nasadí. V projektu na Vercelu je potřeba:

1. **Storage › Create › Blob** (veřejný) a připojit ho k projektu – Vercel sám doplní `BLOB_READ_WRITE_TOKEN`.
2. **Settings › Environment Variables** (Production i Preview):
   - `APP_URL` – adresa webu bez lomítka na konci, např. `https://www.vhworks.cz`
   - `APP_KEY` – dlouhý náhodný řetězec (`openssl rand -hex 32`), podepisuje přihlášení; po změně se všichni odhlásí
   - `TURSO_URL`, `TURSO_TOKEN` – databáze
   - `RESEND_API_KEY`, `MAIL_FROM` – e-maily; `MAIL_FROM` musí být na doméně ověřené v Resendu (např. `web@vhworks.cz`)
   - `CRON_SECRET` – náhodný řetězec pro denní údržbu
   - `INQUIRY_TO` – nepovinné, výchozí adresa pro poptávky (jinak Nastavení › E-mail)
3. Jednorázově nahrát stávající obrázky do Blob: do lokálního `.env` dát `BLOB_READ_WRITE_TOKEN` a spustit `npm run upload-media`.
4. Doménu přidat v Settings › Domains a u registrátora nastavit DNS podle pokynů Vercelu.

Funkce běží v regionu `dub1` (Irsko, stejně jako databáze Turso), viz `vercel.json`.

### Proč přílohy jdou přímo do Blob

Vercel nepřijme požadavek větší než 4,5 MB. Formulář poptávky proto soubory (až 5 × 20 MB) nahraje z prohlížeče
rovnou do Vercel Blob (`/api/poptavka/upload` vydává jednorázové povolení) a serveru pošle jen jejich adresy.
Stejně tak obrázky v administraci (`/admin/api/media/token`); WebP varianty se pak vytvoří na serveru (sharp).
Nedokončené nahrávky starší než den maže denní cron.

### Denní údržba (Vercel Cron)

`/api/cron` ve 3:00: anonymizace poptávek po lhůtě z Nastavení › E-mail, úklid nedokončených nahrávek, starých náhledů
a záznamů o pokusech o přihlášení. Anonymizace se navíc spouští i při otevření přehledu v administraci.

## Struktura

```
src/app/(site)/      veřejný web (stránky, kontaktní formulář a jeho server action)
src/app/admin/       administrace (stránky) a admin/api/ (ukládání, média, poptávky…)
src/app/api/         nahrávání příloh poptávek, cron
src/components/      šablony webu (site/) a administrace (admin/)
src/lib/             databáze, obsah, přihlášení, poptávky, e-maily, úložiště souborů
src/content/         schema.json (všechna editovatelná pole) a seed.json (výchozí obsah)
src/migrations/      SQL migrace (npm run migrate)
public/assets/       CSS, JS, písma, logo
design/              předlohy stránek (nemazat)
```

### Jak přidat nové editovatelné pole

1. Přidejte pole do `src/content/schema.json` (typ, `label`, `help`, `required`, `default`…).
2. Vypište ho v šabloně (např. `{c.ph(page.hero.nove_pole)}` v `src/app/(site)/…/page.tsx`).
3. Případně doplňte výchozí hodnotu do `src/content/seed.json`.

Formulář v administraci se vygeneruje sám. Existující obsah v DB se nepřepisuje, nové pole má výchozí hodnotu.

### Jak se obsah načítá

Celý obsah webu (stránky, nastavení, sbírky, média) se z Turso načte jedním dotazem a drží se v cache Next.js
(`unstable_cache`, značka `content`). Každé uložení v administraci cache hned zneplatní, takže změna je na webu okamžitě.

## Administrace

- První správce: `npm run create-admin -- email`. Další správce přidáte v Nastavení › Uživatelé. Registrace na webu není.
- Zapomenuté heslo: odkaz na přihlašovací stránce, pošle jednorázový odkaz platný 1 hodinu.
- Po 5 neúspěšných pokusech je přihlášení z dané IP zablokované na 15 minut.
- **K doplnění:** texty v [hranatých závorkách] jsou údaje, které ještě chybí od klienta. Přehled je vypíše s odkazem přímo na pole.
  Na webu se zobrazují, dokud v Nastavení › Logo a zobrazení nezapnete „Skrýt nevyplněné hodnoty na webu“.
- Každá stránka a položka má historii posledních 10 uložení s možností obnovit a tlačítko Náhled (neuložený obsah v nové záložce).
- Záloha: Nastavení › Záloha › Stáhnout zálohu (databáze jako JSON + SQL pro obnovu, obrázky; bez příloh poptávek).
  Turso má navíc vlastní automatické zálohy s obnovou k okamžiku.

## Písma

Exo 2 a Montserrat (Google Fonts, licence OFL) jsou kvůli rychlosti a GDPR hostované lokálně v `public/assets/fonts/`.

## Cookies a analytika

Web nepoužívá analytiku ani sledování, souhlas s cookies proto není potřeba; dole je jen informační lišta (texty a vypnutí
v Nastavení › Logo a zobrazení). Technické cookies: `vhw_s` (přihlášení do administrace), `vhw_preview` a `vhw_flash` (jen v administraci).
**Pokud se později přidá analytika (Google Analytics, Meta Pixel apod.), je nutné doplnit lištu se souhlasem**
a skripty spouštět až po souhlasu. Mapa je proto jen statický obrázek s odkazem, ne vložený iframe.

## Bezpečnost

- Přihlášení je podepsaná cookie (HMAC s `APP_KEY`, httpOnly, SameSite=Lax), uživatel se ověřuje v databázi při každém požadavku.
- Formuláře administrace kontrolují token svázaný s relací a hlavičku Origin; kontaktní formulář je server action (Next.js kontroluje původ),
  má skryté pole proti robotům, minimální dobu vyplnění a limit 5 poptávek za hodinu z jedné IP.
- Přílohy poptávek mají náhodná jména, odkazy na ně se nikde nezobrazují a stahují se jen po přihlášení přes `/admin/api/inquiry/…`.
- SVG se při nahrání čistí, rich text projde whitelistem (odstavce, tučně, odkazy, seznamy).
- Hlavičky X-Content-Type-Options, Referrer-Policy, Permissions-Policy, X-Frame-Options (`next.config.ts`); administrace má noindex a no-store.
