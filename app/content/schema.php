<?php
declare(strict_types=1);

/*
 * Definice VŠECH editovatelných polí webu. Administrace z ní sama generuje formuláře.
 * Nové pole = přidat ho sem (a vypsat v šabloně). Žádný další kód adminu není potřeba.
 *
 * Pole: type, label, help, required, default, maxLength, min/max (repeater), fields (repeater),
 *       options (select), collection (reference), svg (image: povolit SVG).
 * Typy: text, textarea, richtext, tags, image, number, select, toggle, url, email, tel,
 *       repeater, reference, password (jen SMTP heslo, nikdy se nevypisuje zpět).
 */

$seo = [
    'label' => 'SEO a sdílení',
    'help' => 'Jak se stránka ukáže ve vyhledávači a při sdílení. Prázdné hodnoty se vezmou z Nastavení › SEO.',
    'fields' => [
        'title' => ['type' => 'text', 'label' => 'Titulek stránky', 'maxLength' => 70, 'help' => 'Zobrazí se v záložce prohlížeče a ve výsledcích Googlu. Ideálně do 60 znaků.'],
        'description' => ['type' => 'textarea', 'label' => 'Popis pro vyhledávače', 'maxLength' => 200, 'help' => 'Jedna až dvě věty, ideálně do 155 znaků.'],
        'image' => ['type' => 'image', 'label' => 'Sdílecí obrázek', 'help' => 'Obrázek při sdílení na sociálních sítích (ideálně 1200 × 630 px).'],
    ],
];

$cta = [
    'label' => 'Modrý blok s výzvou (CTA)',
    'anchor' => 'kontakt',
    'help' => 'Prázdná pole převezmou výchozí texty z Nastavení › CTA blok.',
    'fields' => [
        'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
        'title' => ['type' => 'text', 'label' => 'Nadpis'],
        'button' => ['type' => 'text', 'label' => 'Text tlačítka'],
    ],
];

$pinFields = [
    'title' => ['type' => 'text', 'label' => 'Nadpis', 'required' => true, 'maxLength' => 60],
    'text' => ['type' => 'textarea', 'label' => 'Text', 'required' => true],
    'spec' => ['type' => 'text', 'label' => 'Technický řádek', 'help' => 'Krátký řádek pod textem, např. „SolidWorks · STEP · PDF“.'],
    'x' => ['type' => 'number', 'label' => 'Pozice čísla vodorovně (%)', 'required' => true, 'min' => 0, 'max' => 100, 'help' => '0 = levý okraj obrázku, 100 = pravý.'],
    'y' => ['type' => 'number', 'label' => 'Pozice čísla svisle (%)', 'required' => true, 'min' => 0, 'max' => 100, 'help' => '0 = horní okraj obrázku, 100 = spodní.'],
];

return [

    /* ------------------------------------------------------------------ STRÁNKY */
    'pages' => [

        'home' => [
            'label' => 'Úvod',
            'path' => '/',
            'groups' => [
                'hero' => [
                    'label' => 'Úvodní nadpis',
                    'anchor' => 'obsah',
                    'fields' => [
                        'tags' => ['type' => 'tags', 'label' => 'Štítky nad nadpisem', 'help' => 'Krátká slova, na webu se oddělí tečkou „·“.'],
                        'title' => ['type' => 'text', 'label' => 'Hlavní nadpis (H1)', 'required' => true, 'maxLength' => 90],
                        'lead' => ['type' => 'textarea', 'label' => 'Perex pod nadpisem'],
                    ],
                ],
                'part' => [
                    'label' => 'Klikací díl',
                    'anchor' => 'sluzby',
                    'fields' => [
                        'aria' => ['type' => 'text', 'label' => 'Popis sekce pro čtečky obrazovky', 'help' => 'Na webu není vidět, předčítá ho čtečka pro nevidomé.'],
                        'head_left' => ['type' => 'text', 'label' => 'Hlavička panelu vlevo', 'help' => 'Ve stylu výkresu, např. „UPÍNACÍ DESKA · OCEL · M 1:2“.'],
                        'head_right' => ['type' => 'text', 'label' => 'Hlavička panelu vpravo'],
                        'pos_label' => ['type' => 'text', 'label' => 'Označení pozice', 'help' => 'Slovo před číslem, např. „POZ.“ → „POZ. 1 / 4“.'],
                        'pins' => [
                            'type' => 'repeater', 'label' => 'Čísla na dílu', 'min' => 4, 'max' => 4,
                            'help' => 'Přesně 4 položky. Pořadí určuje číslo na dílu.',
                            'item_label' => 'title',
                            'fields' => $pinFields,
                        ],
                    ],
                ],
                'work' => [
                    'label' => 'Z naší dílny',
                    'anchor' => 'prace',
                    'help' => 'Zakázky se vybírají v sekci Zakázky přepínačem „Na úvodní stránce“ (max. 3).',
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Nadpis', 'required' => true],
                        'link' => ['type' => 'text', 'label' => 'Text odkazu na všechny práce'],
                    ],
                ],
                'cta' => $cta,
                'seo' => $seo,
            ],
        ],

        'services' => [
            'label' => 'Služby',
            'path' => '/sluzby',
            'groups' => [
                'hero' => [
                    'label' => 'Úvodní nadpis',
                    'anchor' => 'obsah',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
                        'title' => ['type' => 'text', 'label' => 'Hlavní nadpis (H1)', 'required' => true],
                    ],
                ],
                'toc' => [
                    'label' => 'Obsah vlevo',
                    'anchor' => 'seznam-sluzeb',
                    'help' => 'Seznam služeb se skládá sám ze sekce Služby.',
                    'fields' => [
                        'aria' => ['type' => 'text', 'label' => 'Popis seznamu pro čtečky obrazovky'],
                        'note' => ['type' => 'textarea', 'label' => 'Text pod seznamem', 'help' => 'Místo {telefon} se vloží telefon z Nastavení jako odkaz.'],
                        'pos_label' => ['type' => 'text', 'label' => 'Označení pozice na kartách', 'help' => 'Např. „POZ.“ → „POZ. 1“.'],
                    ],
                ],
                'cta' => $cta,
                'seo' => $seo,
            ],
        ],

        'work' => [
            'label' => 'Práce',
            'path' => '/prace',
            'groups' => [
                'hero' => [
                    'label' => 'Úvodní nadpis',
                    'anchor' => 'obsah',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
                        'title' => ['type' => 'text', 'label' => 'Hlavní nadpis (H1)', 'required' => true],
                        'lead' => ['type' => 'textarea', 'label' => 'Text vpravo'],
                    ],
                ],
                'featured' => [
                    'label' => 'Vybraná zakázka',
                    'anchor' => 'vybrana-zakazka',
                    'help' => 'Kterou zakázku ukázat, se volí v sekci Zakázky přepínačem „Vybraná zakázka“.',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Štítek (před rokem)'],
                        'brief' => ['type' => 'text', 'label' => 'Popisek „Zadání“'],
                        'solution' => ['type' => 'text', 'label' => 'Popisek „Řešení“'],
                        'result' => ['type' => 'text', 'label' => 'Popisek „Výsledek“'],
                    ],
                ],
                'grid' => [
                    'label' => 'Filtr a mřížka zakázek',
                    'anchor' => 'zakazky',
                    'fields' => [
                        'aria' => ['type' => 'text', 'label' => 'Popis filtru pro čtečky obrazovky'],
                        'all' => ['type' => 'text', 'label' => 'Text tlačítka „Vše“', 'required' => true],
                        'empty' => ['type' => 'text', 'label' => 'Text, když ve filtru nic není'],
                    ],
                ],
                'cta' => $cta,
                'seo' => $seo,
            ],
        ],

        'workshop' => [
            'label' => 'Dílna',
            'path' => '/dilna',
            'groups' => [
                'hero' => [
                    'label' => 'Úvodní nadpis a příběh',
                    'anchor' => 'obsah',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
                        'title' => ['type' => 'text', 'label' => 'Hlavní nadpis (H1)', 'required' => true],
                        'story' => ['type' => 'textarea', 'label' => 'Příběh firmy (vpravo)'],
                    ],
                ],
                'photo' => [
                    'label' => 'Velká fotka',
                    'anchor' => 'fotka',
                    'fields' => [
                        'image' => ['type' => 'image', 'label' => 'Fotka dílny na šířku', 'help' => 'Dokud fotka chybí, zobrazí se šedé místo s textem z popisu (alt).'],
                    ],
                ],
                'machines' => [
                    'label' => 'Strojní park',
                    'anchor' => 'stroje',
                    'help' => 'Řádky tabulky se upravují v sekci Stroje.',
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Nadpis', 'required' => true],
                        'status' => ['type' => 'text', 'label' => 'Stav k datu', 'help' => 'Např. „STAV K ŘÍJNU 2026“.'],
                        'col_name' => ['type' => 'text', 'label' => 'Záhlaví sloupce 1 (stroj)'],
                        'col_type' => ['type' => 'text', 'label' => 'Záhlaví sloupce 2 (typ)'],
                        'col_range' => ['type' => 'text', 'label' => 'Záhlaví sloupce 3 (rozsah)'],
                        'col_use' => ['type' => 'text', 'label' => 'Záhlaví sloupce 4 (použití)'],
                    ],
                ],
                'people' => [
                    'label' => 'Lidé',
                    'anchor' => 'lide',
                    'help' => 'Karty lidí se upravují v sekci Lidé.',
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Nadpis', 'required' => true],
                        'text' => ['type' => 'textarea', 'label' => 'Text'],
                    ],
                ],
                'visit' => [
                    'label' => 'Návštěva',
                    'anchor' => 'navsteva',
                    'help' => 'Adresa, provozní doba a mapa se berou z Nastavení › Firma a kontakty.',
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Nadpis', 'required' => true],
                        'parking' => ['type' => 'textarea', 'label' => 'Poznámka k parkování a příjezdu'],
                        'button' => ['type' => 'text', 'label' => 'Text tlačítka (vede na Kontakt)'],
                    ],
                ],
                'seo' => $seo,
            ],
        ],

        'contact' => [
            'label' => 'Kontakt',
            'path' => '/kontakt',
            'groups' => [
                'hero' => [
                    'label' => 'Nadpis a kontakty',
                    'anchor' => 'obsah',
                    'help' => 'Samotné údaje (telefon, e-mail, adresa, IČO…) jsou v Nastavení › Firma a kontakty.',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
                        'title' => ['type' => 'text', 'label' => 'Hlavní nadpis (H1)', 'required' => true],
                        'l_email' => ['type' => 'text', 'label' => 'Popisek e-mailu'],
                        'l_address' => ['type' => 'text', 'label' => 'Popisek adresy'],
                        'l_hours' => ['type' => 'text', 'label' => 'Popisek provozní doby'],
                        'l_billing' => ['type' => 'text', 'label' => 'Popisek fakturačních údajů'],
                    ],
                ],
                'form' => [
                    'label' => 'Poptávkový formulář',
                    'anchor' => 'poptavka',
                    'fields' => [
                        'head_left' => ['type' => 'text', 'label' => 'Hlavička panelu vlevo'],
                        'head_right' => ['type' => 'text', 'label' => 'Hlavička panelu vpravo', 'help' => 'Např. „ODPOVÍDÁME DO 2 PRACOVNÍCH DNŮ“.'],
                        'title' => ['type' => 'text', 'label' => 'Nadpis formuláře', 'required' => true],
                        'f_company' => ['type' => 'text', 'label' => 'Popisek pole Firma', 'required' => true],
                        'f_name' => ['type' => 'text', 'label' => 'Popisek pole Jméno', 'required' => true],
                        'f_email' => ['type' => 'text', 'label' => 'Popisek pole E-mail', 'required' => true],
                        'f_phone' => ['type' => 'text', 'label' => 'Popisek pole Telefon', 'required' => true],
                        'f_quantity' => ['type' => 'text', 'label' => 'Popisek pole Počet kusů', 'required' => true],
                        'f_deadline' => ['type' => 'text', 'label' => 'Popisek pole Termín', 'required' => true],
                        'f_message' => ['type' => 'text', 'label' => 'Popisek pole Popis', 'required' => true],
                        'files_title' => ['type' => 'text', 'label' => 'Nadpis přílohy'],
                        'files_formats' => ['type' => 'text', 'label' => 'Formáty pod nadpisem přílohy'],
                        'files_button' => ['type' => 'text', 'label' => 'Text „Vybrat soubor“'],
                        'files_limits' => ['type' => 'text', 'label' => 'Text s limity příloh'],
                        'consent' => ['type' => 'text', 'label' => 'Text souhlasu', 'required' => true, 'help' => 'Část textu, ze které je odkaz na zásady, napište do pole níže.'],
                        'consent_link' => ['type' => 'text', 'label' => 'Text odkazu na zásady', 'required' => true],
                        'note' => ['type' => 'textarea', 'label' => 'Poznámka vedle tlačítka'],
                        'submit' => ['type' => 'text', 'label' => 'Text tlačítka', 'required' => true],
                        'error_summary' => ['type' => 'text', 'label' => 'Hláška nad formulářem při chybě'],
                    ],
                ],
                'seo' => $seo,
            ],
        ],

        'thanks' => [
            'label' => 'Poptávka odeslána',
            'path' => '/poptavka-odeslana',
            'groups' => [
                'main' => [
                    'label' => 'Obsah',
                    'anchor' => 'obsah',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
                        'title' => ['type' => 'text', 'label' => 'Nadpis (H1)', 'required' => true],
                        'text' => ['type' => 'richtext', 'label' => 'Co bude následovat'],
                        'phone_label' => ['type' => 'text', 'label' => 'Text nad telefonem'],
                        'back' => ['type' => 'text', 'label' => 'Text odkazu zpět na úvod'],
                    ],
                ],
                'seo' => $seo,
            ],
        ],

        'privacy' => [
            'label' => 'Ochrana osobních údajů',
            'path' => '/ochrana-osobnich-udaju',
            'groups' => [
                'main' => [
                    'label' => 'Obsah',
                    'anchor' => 'obsah',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
                        'title' => ['type' => 'text', 'label' => 'Nadpis (H1)', 'required' => true],
                        'body' => ['type' => 'richtext', 'label' => 'Text zásad', 'required' => true],
                    ],
                ],
                'seo' => $seo,
            ],
        ],
    ],

    /* ------------------------------------------------------------------ SBÍRKY */
    'collections' => [

        'services' => [
            'label' => 'Služby', 'singular' => 'službu', 'path' => 'sluzby', 'web' => '/sluzby',
            'title' => 'title', 'slug' => 'title', 'visibility' => true,
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Název', 'required' => true, 'maxLength' => 80],
                'tag' => ['type' => 'text', 'label' => 'Štítek vpravo nahoře', 'help' => 'Např. „SOLIDWORKS“ nebo „NÁVRH · VÝROBA · MONTÁŽ“.'],
                'description' => ['type' => 'richtext', 'label' => 'Popis', 'required' => true],
                'pairs' => [
                    'type' => 'repeater', 'label' => 'Dvojice popisek → hodnota', 'min' => 2, 'max' => 3,
                    'help' => '2 až 3 dvojice pod čarou, např. OD VÁS STAČÍ → náčrt, vzorek.', 'item_label' => 'label',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Popisek', 'required' => true],
                        'value' => ['type' => 'text', 'label' => 'Hodnota', 'required' => true],
                    ],
                ],
            ],
        ],

        'works' => [
            'label' => 'Zakázky', 'singular' => 'zakázku', 'path' => 'zakazky', 'web' => '/prace',
            'title' => 'title', 'thumb' => 'photo', 'visibility' => true,
            'fields' => [
                'title' => ['type' => 'text', 'label' => 'Název', 'required' => true],
                'category' => ['type' => 'reference', 'label' => 'Kategorie', 'collection' => 'categories', 'required' => true],
                'photo' => ['type' => 'image', 'label' => 'Fotka', 'help' => 'Ideálně na šířku 4:3. Dokud chybí, zobrazí se šedé místo s textem z popisu (alt).'],
                'spec' => ['type' => 'text', 'label' => 'Technický řádek', 'help' => 'Materiál · počet kusů · odvětví.'],
                'year' => ['type' => 'text', 'label' => 'Rok', 'maxLength' => 10],
                'brief' => ['type' => 'textarea', 'label' => 'Zadání', 'help' => 'Zobrazí se jen u vybrané zakázky.'],
                'solution' => ['type' => 'textarea', 'label' => 'Řešení'],
                'result' => ['type' => 'textarea', 'label' => 'Výsledek'],
                'featured' => ['type' => 'toggle', 'label' => 'Vybraná zakázka', 'help' => 'Velká ukázka nahoře na stránce Práce. Může být jen jedna – zapnutím se u ostatních vypne.'],
                'on_home' => ['type' => 'toggle', 'label' => 'Na úvodní stránce', 'help' => 'Max. 3 zakázky. První v pořadí je velká.'],
            ],
        ],

        'categories' => [
            'label' => 'Kategorie zakázek', 'singular' => 'kategorii', 'path' => 'kategorie', 'web' => '/prace',
            'title' => 'name', 'slug' => 'name', 'visibility' => false,
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Název (tlačítko filtru)', 'required' => true],
                'label' => ['type' => 'text', 'label' => 'Štítek na kartě', 'required' => true, 'help' => 'Verzálkami, např. „PŘÍPRAVEK“.'],
            ],
        ],

        'machines' => [
            'label' => 'Stroje', 'singular' => 'stroj', 'path' => 'stroje', 'web' => '/dilna#stroje',
            'title' => 'name', 'visibility' => true,
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Název stroje', 'required' => true],
                'type' => ['type' => 'text', 'label' => 'Typ'],
                'range' => ['type' => 'text', 'label' => 'Pracovní rozsah'],
                'use' => ['type' => 'text', 'label' => 'Na co se používá'],
            ],
        ],

        'people' => [
            'label' => 'Lidé', 'singular' => 'člověka', 'path' => 'lide', 'web' => '/dilna#lide',
            'title' => 'name', 'thumb' => 'portrait', 'visibility' => true,
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Jméno a příjmení', 'required' => true],
                'role' => ['type' => 'text', 'label' => 'Role'],
                'portrait' => ['type' => 'image', 'label' => 'Portrét', 'help' => 'Na výšku 4:5.'],
            ],
        ],
    ],

    /* ------------------------------------------------------------------ NASTAVENÍ */
    'settings' => [

        'company' => [
            'label' => 'Firma a kontakty',
            'help' => 'Údaje se používají v hlavičce, patičce, na stránce Kontakt a Dílna a pro vyhledávače.',
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Název firmy', 'required' => true],
                'phone' => ['type' => 'tel', 'label' => 'Telefon', 'required' => true, 'help' => 'Ve tvaru +420 606 879 112. V hlavičce se zobrazí bez předvolby.'],
                'email' => ['type' => 'email', 'label' => 'E-mail'],
                'street' => ['type' => 'text', 'label' => 'Ulice a číslo', 'required' => true],
                'zip' => ['type' => 'text', 'label' => 'PSČ', 'required' => true],
                'city' => ['type' => 'text', 'label' => 'Obec', 'required' => true],
                'hours' => ['type' => 'textarea', 'label' => 'Provozní doba', 'help' => 'Jak se zobrazí na webu, např. „Po–Pá 7:00–15:30“.'],
                'hours_schema' => ['type' => 'text', 'label' => 'Provozní doba pro vyhledávače', 'help' => 'Strojový zápis pro Google, např. „Mo-Fr 07:00-15:30“. Na webu není vidět.'],
                'ico' => ['type' => 'text', 'label' => 'IČO'],
                'ico_label' => ['type' => 'text', 'label' => 'Popisek před IČO', 'help' => 'Obvykle „IČO“.'],
                'dic' => ['type' => 'text', 'label' => 'DIČ'],
                'dic_label' => ['type' => 'text', 'label' => 'Popisek před DIČ', 'help' => 'Obvykle „DIČ“.'],
                'map_image' => ['type' => 'image', 'label' => 'Obrázek mapy', 'help' => 'Statický snímek mapy (žádné vložené mapy kvůli cookies). Dokud chybí, zobrazí se šedé místo s textem z popisu.'],
                'map_url' => ['type' => 'url', 'label' => 'Odkaz na Mapy.cz', 'help' => 'Kliknutím na mapu se otevře tento odkaz.'],
                'map_link' => ['type' => 'text', 'label' => 'Text odkazu na mapu'],
            ],
        ],

        'display' => [
            'label' => 'Logo a zobrazení',
            'fields' => [
                'logo' => ['type' => 'image', 'label' => 'Logo', 'svg' => true, 'help' => 'Ideálně SVG. Na webu se zobrazí ve výšce 46 px. Prázdné = výchozí logo.'],
                'hide_placeholders' => ['type' => 'toggle', 'label' => 'Skrýt nevyplněné hodnoty na webu', 'help' => 'Texty v hranatých závorkách [ … ] se na webu nahradí prázdnem.'],
                'skip_link' => ['type' => 'text', 'label' => 'Odkaz „Přeskočit na obsah“', 'help' => 'Je vidět jen při ovládání klávesnicí.'],
            ],
        ],

        'menu' => [
            'label' => 'Menu a patička',
            'fields' => [
                'items' => [
                    'type' => 'repeater', 'label' => 'Položky menu', 'min' => 1, 'max' => 6, 'item_label' => 'label',
                    'fields' => [
                        'label' => ['type' => 'text', 'label' => 'Text', 'required' => true],
                        'url' => ['type' => 'url', 'label' => 'Odkaz', 'required' => true, 'help' => 'Např. /sluzby'],
                    ],
                ],
                'aria' => ['type' => 'text', 'label' => 'Popis menu pro čtečky obrazovky'],
                'button' => ['type' => 'text', 'label' => 'Text tlačítka vpravo', 'required' => true],
                'button_url' => ['type' => 'url', 'label' => 'Odkaz tlačítka', 'required' => true],
                'privacy_link' => ['type' => 'text', 'label' => 'Text odkazu na ochranu osobních údajů (patička)'],
            ],
        ],

        'cta' => [
            'label' => 'CTA blok',
            'help' => 'Výchozí texty modrého bloku. Každá stránka je může přepsat vlastními.',
            'fields' => [
                'label' => ['type' => 'text', 'label' => 'Štítek nad nadpisem'],
                'title' => ['type' => 'text', 'label' => 'Nadpis', 'required' => true],
                'button' => ['type' => 'text', 'label' => 'Text tlačítka', 'required' => true],
                'button_url' => ['type' => 'url', 'label' => 'Odkaz tlačítka', 'required' => true],
            ],
        ],

        'seo' => [
            'label' => 'SEO',
            'fields' => [
                'title_suffix' => ['type' => 'text', 'label' => 'Přípona titulku', 'help' => 'Přidá se za titulek každé stránky, např. „ | VH Works“.'],
                'description' => ['type' => 'textarea', 'label' => 'Výchozí popis pro vyhledávače', 'maxLength' => 200],
                'image' => ['type' => 'image', 'label' => 'Výchozí sdílecí obrázek'],
            ],
        ],

        'mail' => [
            'label' => 'E-mail a SMTP',
            'help' => 'Prázdná pole SMTP použijí hodnoty ze souboru .env. Když SMTP chybí úplně, e-maily se jen uloží do storage/logs/mail.',
            'fields' => [
                'inquiry_to' => ['type' => 'email', 'label' => 'E-mail pro poptávky', 'help' => 'Kam chodí nové poptávky z webu.'],
                'company_subject' => ['type' => 'text', 'label' => 'Předmět e-mailu firmě', 'help' => '{firma} = firma ze formuláře.'],
                'customer_subject' => ['type' => 'text', 'label' => 'Předmět potvrzení zákazníkovi'],
                'customer_body' => ['type' => 'textarea', 'label' => 'Text potvrzení zákazníkovi', 'help' => '{jmeno} = jméno ze formuláře, {telefon} = telefon firmy.'],
                'retention_months' => ['type' => 'number', 'label' => 'Anonymizovat poptávky po (měsících)', 'min' => 1, 'max' => 120, 'required' => true],
                'smtp_host' => ['type' => 'text', 'label' => 'SMTP server'],
                'smtp_port' => ['type' => 'number', 'label' => 'SMTP port', 'min' => 1, 'max' => 65535],
                'smtp_secure' => ['type' => 'select', 'label' => 'Šifrování', 'options' => ['' => 'Automaticky', 'tls' => 'STARTTLS (port 587)', 'ssl' => 'SSL (port 465)']],
                'smtp_user' => ['type' => 'text', 'label' => 'SMTP uživatel'],
                'smtp_pass' => ['type' => 'password', 'label' => 'SMTP heslo', 'help' => 'Nechte prázdné, pokud ho nechcete měnit.'],
                'smtp_from' => ['type' => 'email', 'label' => 'Odesílatel (From)'],
            ],
        ],

        'notfound' => [
            'label' => 'Stránka 404',
            'fields' => [
                'label' => ['type' => 'text', 'label' => 'Štítek'],
                'title' => ['type' => 'text', 'label' => 'Nadpis', 'required' => true],
                'text' => ['type' => 'textarea', 'label' => 'Text'],
                'home' => ['type' => 'text', 'label' => 'Odkaz na úvod'],
                'contact' => ['type' => 'text', 'label' => 'Odkaz na kontakt'],
            ],
        ],

        'form_msgs' => [
            'label' => 'Hlášky formuláře',
            'help' => 'Chybové hlášky poptávkového formuláře.',
            'fields' => [
                'required' => ['type' => 'text', 'label' => 'Povinné pole', 'required' => true],
                'email' => ['type' => 'text', 'label' => 'Neplatný e-mail', 'required' => true],
                'consent' => ['type' => 'text', 'label' => 'Chybí souhlas', 'required' => true],
                'too_fast' => ['type' => 'text', 'label' => 'Odesláno příliš rychle', 'required' => true],
                'rate' => ['type' => 'text', 'label' => 'Příliš mnoho odeslání', 'required' => true],
                'files_count' => ['type' => 'text', 'label' => 'Moc souborů', 'required' => true, 'help' => '{max} = počet'],
                'files_size' => ['type' => 'text', 'label' => 'Soubor je moc velký', 'required' => true, 'help' => '{soubor}, {max}'],
                'files_total' => ['type' => 'text', 'label' => 'Soubory dohromady moc velké', 'required' => true, 'help' => '{max}'],
                'files_type' => ['type' => 'text', 'label' => 'Nepovolený typ souboru', 'required' => true, 'help' => '{soubor}, {povolene}'],
                'files_upload' => ['type' => 'text', 'label' => 'Soubor se nepodařilo nahrát', 'required' => true, 'help' => '{soubor}'],
                'remove' => ['type' => 'text', 'label' => 'Text „Odebrat“ u souboru', 'required' => true],
            ],
        ],
    ],
];
