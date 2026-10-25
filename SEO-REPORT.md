# SEO / GEO / AIEO report – VH Works

Datum: 2026-10-25 · Audit: `seo-geo-audit/scripts/audit.py` nad https://worksvh.vercel.app a localhost

## Shrnutí
- Výchozí stav byl dobrý: obsah je v HTML ze serveru, canonical, `lang="cs"`, sitemap s lastmod, jedno H1, popisy a OG obrázky.
- Stav před → po: P0 0 → 0 · P1 3 typy (sameAs, duplicitní title u filtrů, chybí WebSite) → 2 (sameAs čeká na data, filtry jsou v pořádku) · P2 3 → 2
- 3 nejdůležitější změny:
  1. Strukturovaná data jako `@graph`: LocalBusiness (logo, popis, mapa, geo, sameAs) + WebSite + BreadcrumbList + Service pro každou službu.
  2. robots.txt výslovně povoluje vyhledávače a AI vyhledávací boty, skrývá `/admin` a `/api/`.
  3. `favicon.ico` a `apple-touch-icon.png` (dřív 404).
- Čeká na klienta: vlastní doména, profily firmy (sameAs), poloha a provozní doba, rozhodnutí o tréninkových botech.

## Opraveno v kódu
| Priorita | Co | Soubor(y) |
| --- | --- | --- |
| P0 | robots.txt: vyhledávače a AI vyhledávací/uživatelští boti povoleni výslovně, `/admin` a `/api/` zakázané | `src/app/robots.ts` |
| P1 | JSON-LD `@graph`: LocalBusiness s `@id`, logem, obrázkem, popisem, `hasMap`, `geo` a `sameAs` (z CMS) | `src/components/site/Shell.tsx` |
| P1 | WebSite schema | `src/components/site/Shell.tsx` |
| P1 | BreadcrumbList na podstránkách (název z menu, jinak titulek) | `src/components/site/Shell.tsx` |
| P1 | Service pro každou viditelnou službu na /sluzby (název + popis z CMS) | `src/components/site/Shell.tsx` |
| P1 | Nová pole v administraci: Poloha pro vyhledávače, Profily firmy na jiných webech | `src/content/schema.json`, `seed.json` |
| P1 | Nové titulky a popisy všech stránek, sdílecí obrázky pro Služby, Práce, Dílnu (schváleno 2026-10-25, uloženo v CMS) | DB `pages.seo`, `src/content/seed.json` |
| P2 | Nová tmavá patička ve 4 sloupcích (všechny texty v Nastavení › Menu a patička) | `src/components/site/Shell.tsx`, `site.css` |
| P2 | favicon.ico (16/32/48) a apple-touch-icon 180 px z firemní ikony | `public/`, `src/app/(site)/layout.tsx` |

## Zbývá v kódu (čeká na rozhodnutí nebo data)
| Priorita | Co | Co je potřeba |
| --- | --- | --- |
| P0 | Canonical, sitemap a robots ukazují na worksvh.vercel.app | Po aktivaci domény změnit `APP_URL` na Vercelu a nastavit přesměrování vercel.app → doména |
| P1 | `sameAs` prázdné | Odkazy na Firmy.cz, Google firemní profil atd. vložit do Nastavení › Firma a kontakty |
| P1 | `geo`, `openingHours` prázdné | Souřadnice dílny a provozní doba od klienta |
| P1 | Tréninkoví boti (GPTBot, ClaudeBot, Google-Extended, CCBot) | Rozhodnutí klienta; teď povoleni přes `*` |
| info | Audit hlásí stejný title/description u `/prace?kategorie=…` | Není chyba: filtry mají canonical na `/prace`, Google je sloučí |

## Návrhy obsahu (neaplikováno bez souhlasu)
| Stránka | Návrh | Proč |
| --- | --- | --- |
| / | V prvním odstavci jmenovat obor + místo + pro koho (už z velké části splněno) | AI citují hlavně první třetinu stránky |
| /sluzby | U každé služby 1–2 konkrétní údaje (materiály, tolerance, velikost dílů, typické dodací lhůty) | Konkrétní data zvyšují citovanost |
| nová | Krátké FAQ na /sluzby nebo /kontakt („Co od vás potřebujete k poptávce?“, „Děláte i kusovou výrobu?“, „Jak rychle?“) + FAQPage schema | Otázky zákazníka = formát AI odpovědí |
| /prace | U zakázek uvést obor zákazníka a co se řešilo (bez jmen, pokud nesmí) | Případové studie = E-E-A-T |

Titulky jdou změnit v administraci u každé stránky v části SEO.

## Mimo kód – úkoly pro klienta / marketing
- [ ] Google firemní profil – kategorie „Nástrojárna“ / „Strojírenská firma“, fotky dílny, provozní doba, sbírat recenze
- [ ] Firmy.cz – kompletní profil se stejným názvem, adresou a telefonem jako na webu
- [ ] Google Search Console, Bing Webmaster Tools, Seznam Webmaster – napojit po přepnutí domény, odeslat sitemap
- [ ] Recenze od stálých zákazníků (Google, Firmy.cz)
- [ ] Oborové katalogy a registry dodavatelů (např. strojírenské katalogy, poptávkové portály), členství v asociacích
- [ ] Zmínky u partnerů a zákazníků („dodavatel přípravků“), případně článek v regionálním/oborovém médiu
- [ ] Core Web Vitals na produkci (PageSpeed Insights) po přepnutí domény
- [ ] Analytika na webu záměrně není; pokud se přidá (GA4 s kanálem „AI asistenti“), je nutná lišta se souhlasem

## Sada promptů pro měření AI viditelnosti
Spouštět měsíčně, každý prompt 3×, v ChatGPT, Google AI Mode, Perplexity, Gemini, Copilot, Seznam Asistent.
Zaznamenat: jmenováno / citováno / pozice / sentiment / citované zdroje.

| # | Prompt | Záměr |
| --- | --- | --- |
| 1 | Nástrojárna v okolí Prahy, která udělá přípravek podle výkresu | lokální, nákup |
| 2 | Kdo vyrobí přípravek pro výrobu nebo měření, Praha východ | lokální, nákup |
| 3 | CNC obrábění kusová výroba Úvaly | lokální, nákup |
| 4 | Nástrojárna Úvaly | brand/lokalita |
| 5 | VH Works Úvaly zkušenosti | brand |
| 6 | VH Works nástrojárna kontakt | brand |
| 7 | Firma na výrobu jednoúčelových strojů ve Středočeském kraji | nákup |
| 8 | Kdo navrhne a vyrobí chapadlo pro robota na zakázku | nákup |
| 9 | Oprava nástrojů a přípravků na výrobní lince, rychlý servis Praha | nákup, urgentní |
| 10 | Konstrukce v SolidWorks na zakázku pro malou firmu | nákup |
| 11 | Výroba náhradního dílu podle vzorku bez výkresu | problém |
| 12 | Kde nechat vyrobit prototyp kovového dílu v malém množství | problém |
| 13 | 3D tisk a prototypy pro strojírenství Praha | nákup |
| 14 | Montáž sestav a subdodávky strojírenství Praha východ | B2B |
| 15 | Jak vybrat nástrojárnu pro výrobu přípravků | rozhodování |
| 16 | Kolik stojí výroba přípravku na zakázku | cena |
| 17 | Co poslat nástrojárně k poptávce dílu | informační |
| 18 | Rozdíl mezi přípravkem a nástrojem ve výrobě | informační |
| 19 | Malá rodinná nástrojárna vs. velká strojírenská firma – výhody | srovnání |
| 20 | Nejlepší nástrojárny v okolí Prahy | žebříček |
| 21 | Strojírenská firma Úvaly Český Brod Mukařov | lokalita |
| 22 | Kdo udělá výrobní výkresy z náčrtu | nákup |
| 23 | Subdodavatel CNC dílů pro údržbu výroby | B2B |
| 24 | Přípravek pro kontrolu rozměrů dílů na zakázku | nákup |
| 25 | Nástrojař s praxí od roku 1990 Praha | brand/E-E-A-T |
