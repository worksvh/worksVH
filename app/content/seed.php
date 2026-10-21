<?php
declare(strict_types=1);

/*
 * Výchozí obsah převzatý z design/. Texty v hranatých závorkách jsou „k doplnění“ od klienta.
 * Obrázky: dokud chybí fotka, alt obsahuje popis toho, co má být na fotce (zobrazí se v šedém místě).
 * Reference na kategorii se zapisuje jako 'cat:<slug>'.
 */

$img = static fn(string $alt): array => ['media' => null, 'alt' => $alt];
$emptyImg = ['media' => null, 'alt' => ''];
$noSeo = ['title' => '', 'description' => '', 'image' => $emptyImg];
$noCta = ['label' => '', 'title' => '', 'button' => ''];

return [
    'pages' => [
        'home' => [
            'hero' => [
                'tags' => ['NÁSTROJÁRNA', 'CNC OBRÁBĚNÍ', 'ÚVALY U PRAHY', 'OD ROKU 1990'],
                'title' => 'Od nápadu po díl, který sedí napoprvé.',
                'lead' => 'Přípravky, jednoúčelové stroje a přesné díly pro firmy. Navrhneme ve vlastní konstrukci, vyrobíme na CNC a smontujeme v Úvalech.',
            ],
            'part' => [
                'aria' => 'Co umíme, ukázáno na dílu',
                'head_left' => 'UPÍNACÍ DESKA · OCEL · M 1:2',
                'head_right' => 'KLIKNĚTE NA ČÍSLA',
                'pos_label' => 'POZ.',
                'pins' => [
                    ['title' => 'Konstrukce a výkres', 'text' => 'Díl nejdřív navrhneme v SolidWorks. Stačí nám náčrt, vzorek nebo popis problému. Dostanete 3D model a výrobní výkres s kótami.', 'spec' => 'SolidWorks · CAM · STEP · PDF · DWG', 'x' => 71, 'y' => 82],
                    ['title' => 'CNC frézování', 'text' => 'Kapsy, drážky a tvarové plochy obrobíme na CNC, podle vašeho i našeho výkresu, kusově i v malé sérii.', 'spec' => '2× MAS MCV 754 Quick · Heidenhain', 'x' => 50, 'y' => 45],
                    ['title' => 'Přesné díry a závity', 'text' => 'Díry pro kolíky, šrouby a ložiska v toleranci podle výkresu.', 'spec' => 'Tolerance podle výkresu', 'x' => 73, 'y' => 45],
                    ['title' => 'Montáž a opravy', 'text' => 'Přípravky a sestavy smontujeme a vyzkoušíme, aby šly rovnou do linky. Opravujeme a vylepšujeme i díly přímo na výrobních linkách.', 'spec' => 'Přípravky · sestavy · opravy na lince', 'x' => 30, 'y' => 64],
                ],
            ],
            'work' => ['title' => 'Z naší dílny', 'link' => 'Všechny práce →'],
            'cta' => $noCta,
            'seo' => ['title' => 'VH Works – nástrojárna v Úvalech u Prahy', 'description' => 'Přípravky, nástroje a CNC obráběné díly pro firmy. Konstrukce v SolidWorks, výroba a montáž v Úvalech u Prahy. Praxe od roku 1990.', 'image' => $emptyImg],
        ],
        'services' => [
            'hero' => ['label' => 'SLUŽBY · 8 POZIC', 'title' => 'Přijďte s tím, co máte. Odejdete s hotovým dílem.'],
            'toc' => ['aria' => 'Seznam služeb', 'note' => 'Nevíte, kam vaše zakázka patří? Zavolejte {telefon}, poradíme.', 'pos_label' => 'POZ.'],
            'cta' => $noCta,
            'seo' => ['title' => 'Služby – konstrukce, CNC, přípravky, jednoúčelové stroje', 'description' => 'Konstrukce a dokumentace, přesné díly na CNC, přípravky, jednoúčelové stroje, sestavy, chapadla pro roboty, 3D tisk a opravy na linkách.', 'image' => $emptyImg],
        ],
        'work' => [
            'hero' => [
                'label' => 'PRÁCE · VÝBĚR ZAKÁZEK',
                'title' => 'Vyrobeno v Úvalech',
                'lead' => 'Ukázky přípravků, dílů a oprav, které jsme dělali pro firmy. Názvy zákazníků uvádíme jen s jejich souhlasem.',
            ],
            'featured' => ['label' => 'VYBRANÁ ZAKÁZKA', 'brief' => 'ZADÁNÍ', 'solution' => 'ŘEŠENÍ', 'result' => 'VÝSLEDEK'],
            'grid' => ['aria' => 'Filtr zakázek', 'all' => 'Vše', 'empty' => 'V této kategorii zatím nic nemáme.'],
            'cta' => ['label' => '', 'title' => 'Máte podobný díl? Pošlete výkres.', 'button' => ''],
            'seo' => ['title' => 'Práce – přípravky, CNC díly a opravy', 'description' => 'Ukázky přípravků, obráběných dílů, prototypů a oprav nástrojů z dílny VH Works v Úvalech.', 'image' => $emptyImg],
        ],
        'workshop' => [
            'hero' => [
                'label' => 'DÍLNA · PRAŽSKÁ 1473 · ÚVALY',
                'title' => 'Malá dílna, řemeslo od roku 1990.',
                'story' => 'Jsme nástrojaři s praxí v oboru od roku 1990. V Úvalech máme dílnu o 160 m² a kancelář o 40 m² s vlastní konstrukcí, takže konstruktér i nástrojař sedí ve stejné budově. Kdo díl navrhne, ví, jak se bude vyrábět, a kdo ho vyrábí, se může hned zeptat. Jsme malý tým, rozhodujeme se rychle a zvládneme i spěšnou zakázku.',
            ],
            'photo' => ['image' => $img('[FOTO NA ŠÍŘKU: celá dílna se stroji, uklizeno, denní světlo, ideálně s člověkem u stroje]')],
            'machines' => ['title' => 'Strojní park', 'status' => 'STAV K ŘÍJNU 2026', 'col_name' => 'STROJ', 'col_type' => 'TYP', 'col_range' => 'PRACOVNÍ ROZSAH', 'col_use' => 'NA CO HO POUŽÍVÁME'],
            'people' => ['team' => array (
  0 => 
  array (
    'count' => '2',
    'role' => 'nástrojaři',
  ),
  1 => 
  array (
    'count' => '3',
    'role' => 'CNC obráběči',
  ),
  2 => 
  array (
    'count' => '1',
    'role' => 'konstruktér',
  ),
  3 => 
  array (
    'count' => '1',
    'role' => 'asistentka',
  ),
), 'title' => 'Kdo vám bude volat zpátky', 'text' => 'Jsme rodinná firma: vede ji otec se synem a o poptávky a domluvu se stará asistentka. Žádné call centrum, o vaší zakázce mluvíte rovnou s tím, kdo ji bude dělat.'],
            'visit' => ['title' => 'Přijeďte se podívat', 'parking' => '', 'button' => 'Domluvit návštěvu'],
            'seo' => ['title' => 'Dílna a strojní park v Úvalech', 'description' => 'Nástrojárna s praxí od roku 1990 a týmem sedmi lidí. Strojní park, lidé a adresa dílny v Úvalech u Prahy.', 'image' => $emptyImg],
        ],
        'contact' => [
            'hero' => ['label' => 'KONTAKT', 'title' => 'Nejrychleji po telefonu.', 'l_email' => 'E-MAIL', 'l_address' => 'DÍLNA', 'l_hours' => 'PROVOZNÍ DOBA', 'l_billing' => 'FAKTURAČNÍ ÚDAJE'],
            'form' => [
                'head_left' => 'POPTÁVKOVÝ LIST',
                'head_right' => 'OZVEME SE S CENOU A TERMÍNEM',
                'title' => 'Pošlete výkres, ozveme se s cenou a termínem.',
                'f_company' => 'FIRMA',
                'f_name' => 'JMÉNO',
                'f_email' => 'E-MAIL',
                'f_phone' => 'TELEFON',
                'f_quantity' => 'POČET KUSŮ',
                'f_deadline' => 'POTŘEBNÝ TERMÍN',
                'f_message' => 'CO POTŘEBUJETE · MATERIÁL · K ČEMU DÍL SLOUŽÍ',
                'files_title' => 'Přiložte výkres, model nebo fotku',
                'files_formats' => 'PDF · STEP · DWG · DXF · JPG',
                'files_button' => 'Vybrat soubor',
                'files_limits' => 'Max. 5 souborů, každý do 20 MB, dohromady do 50 MB.',
                'consent' => 'Souhlasím se zpracováním osobních údajů podle',
                'consent_link' => 'zásad ochrany osobních údajů',
                'note' => 'Výkresy používáme jen pro nacenění. Na přání podepíšeme NDA.',
                'submit' => 'Odeslat poptávku',
                'error_summary' => 'Poptávku se nepodařilo odeslat. Opravte prosím označená pole.',
            ],
            'seo' => ['title' => 'Kontakt a poptávka', 'description' => 'Pošlete výkres, model nebo fotku náčrtu. Ozveme se s cenou a termínem. Tel. +420 606 879 112, Pražská 1473, Úvaly.', 'image' => $emptyImg],
        ],
        'thanks' => [
            'main' => [
                'label' => 'POPTÁVKA ODESLÁNA',
                'title' => 'Děkujeme, poptávku máme.',
                'text' => '<p>Projdeme zadání a přílohy a ozveme se s cenou a termínem. Kopii poptávky jsme vám poslali e-mailem.</p><p>Když bude potřeba něco upřesnit, zavoláme vám.</p>',
                'phone_label' => 'Spěcháte? Zavolejte',
                'back' => 'Zpět na úvod',
            ],
            'seo' => ['title' => 'Poptávka odeslána', 'description' => '', 'image' => $emptyImg],
        ],
        'privacy' => [
            'main' => ['label' => 'DOKUMENT', 'title' => 'Ochrana osobních údajů', 'body' => '<p><strong>Kdo vaše údaje zpracovává</strong><br>Správcem osobních údajů je Vladimír Hujer, IČO 14982919, se sídlem Srbská 359, 250 82 Úvaly (VH Works). Kontakt: <a href="mailto:nastrojar@volny.cz">nastrojar@volny.cz</a>, tel. <a href="tel:+420606879112">+420 606 879 112</a>.</p><p><strong>Jaké údaje a proč</strong><br>Když nám pošlete poptávku přes formulář, zpracováváme jméno, název firmy, e-mail, telefon, text zprávy a přiložené soubory (výkresy, modely, fotky). Používáme je jen k tomu, abychom vaši poptávku nacenili, odpověděli na ni a případně uzavřeli a splnili zakázku. Právním základem je jednání o smlouvě na vaši žádost (čl. 6 odst. 1 písm. b) GDPR).</p><p><strong>Jak dlouho</strong><br>Poptávky uchováváme 24 měsíců od odeslání, potom je anonymizujeme a přílohy smažeme. Pokud z poptávky vznikne zakázka, uchováváme údaje po dobu, kterou nám ukládají účetní a daňové předpisy.</p><p><strong>Komu je předáváme</strong><br>Vaše údaje neprodáváme ani nepředáváme dalším firmám. Přístup k nim mají jen zpracovatelé, kteří nám zajišťují technický provoz webu a e-mailu (hosting). Výkresy a podklady používáme jen pro vaši zakázku, na přání podepíšeme dohodu o mlčenlivosti (NDA).</p><p><strong>Cookies</strong><br>Web nepoužívá analytické ani reklamní cookies. Používá jen jednu technickou cookie, která je nutná pro bezpečné odeslání formuláře.</p><p><strong>Vaše práva</strong><br>Máte právo požádat o přístup ke svým údajům, jejich opravu nebo výmaz, omezení zpracování a přenesení údajů a máte právo vznést námitku. Stačí napsat na <a href="mailto:nastrojar@volny.cz">nastrojar@volny.cz</a>. Pokud se domníváte, že údaje zpracováváme v rozporu s předpisy, můžete podat stížnost u Úřadu pro ochranu osobních údajů (<a href="https://uoou.gov.cz">uoou.gov.cz</a>).</p>'],
            'seo' => ['title' => 'Ochrana osobních údajů', 'description' => '', 'image' => $emptyImg],
        ],
    ],

    'settings' => [
        'company' => [
            'name' => 'VH Works',
            'phone' => '+420 606 879 112',
            'email' => 'nastrojar@volny.cz',
            'street' => 'Pražská 1473',
            'zip' => '250 82',
            'city' => 'Úvaly',
            'hours' => '',
            'hours_schema' => '',
            'ico' => '14982919',
            'ico_label' => 'IČO',
            'dic' => 'CZ6503052226',
            'dic_label' => 'DIČ',
            'operator' => 'Vladimír Hujer',
            'operator_label' => 'Provozovatel',
            'operator_address' => 'Srbská 359, 250 82 Úvaly',
            'register_note' => 'Fyzická osoba zapsaná v živnostenském rejstříku.',
            'map_image' => $img('[MAPA: Pražská 1473, Úvaly]'),
            'map_url' => 'https://share.google/Kw4ntPmqxJECkMDbd',
            'map_link' => 'Otevřít v Google Mapách',
            'map_credit' => '',
        ],
        'display' => ['logo' => ['media' => null, 'alt' => 'VH Works – Tooling, Engineering, Precision'], 'hide_placeholders' => false, 'skip_link' => 'Přeskočit na obsah', 'cookie_bar' => true, 'cookie_text' => 'Tento web používá jen nezbytné technické cookies pro odeslání formuláře. Nesledujeme vás ani nepoužíváme reklamní cookies.', 'cookie_link' => 'Více informací', 'cookie_button' => 'Rozumím'],
        'menu' => [
            'items' => [
                ['label' => 'Služby', 'url' => '/sluzby'],
                ['label' => 'Práce', 'url' => '/prace'],
                ['label' => 'Dílna', 'url' => '/dilna'],
            ],
            'aria' => 'Hlavní menu',
            'button' => 'Poptávka',
            'button_url' => '/kontakt',
            'privacy_link' => 'Ochrana osobních údajů',
            'credit_label' => 'Web vytvořil',
            'credit_name' => 'Weblyx',
            'credit_url' => 'https://www.weblyx.cz',
        ],
        'cta' => [
            'label' => 'PDF · STEP · DWG · NEBO FOTKA NÁČRTU',
            'title' => 'Pošlete výkres, ozveme se s cenou.',
            'button' => 'Poslat poptávku',
            'button_url' => '/kontakt',
        ],
        'seo' => [
            'title_suffix' => ' | VH Works',
            'description' => 'VH Works, nástrojárna v Úvalech u Prahy. Konstrukce v SolidWorks, CNC obrábění, přípravky, jednoúčelové stroje a opravy na výrobních linkách pro firmy.',
            'image' => $emptyImg,
        ],
        'mail' => [
            'inquiry_to' => '',
            'company_subject' => 'Nová poptávka z webu: {firma}',
            'customer_subject' => 'Potvrzení poptávky – VH Works',
            'customer_body' => "Dobrý den {jmeno},\n\nděkujeme za poptávku. Projdeme ji a ozveme se s cenou a termínem.\n\nKdyž spěcháte, zavolejte nám na {telefon}.\n\nS pozdravem\nVH Works\nPražská 1473, 250 82 Úvaly",
            'retention_months' => 24,
            'smtp_host' => '', 'smtp_port' => '', 'smtp_secure' => '', 'smtp_user' => '', 'smtp_pass' => '', 'smtp_from' => '',
        ],
        'notfound' => [
            'label' => 'CHYBA 404',
            'title' => 'Tuhle stránku jsme nenašli.',
            'text' => 'Možná se změnila adresa, nebo je v odkazu překlep.',
            'home' => 'Na úvod',
            'contact' => 'Kontakt a poptávka',
        ],
        'form_msgs' => [
            'required' => 'Toto pole je povinné.',
            'email' => 'Zadejte platný e-mail, např. jan@firma.cz.',
            'consent' => 'Bez souhlasu nemůžeme poptávku zpracovat.',
            'too_fast' => 'Formulář byl odeslán příliš rychle. Zkontrolujte ho prosím a odešlete znovu.',
            'rate' => 'Z vaší adresy přišlo v poslední hodině příliš mnoho poptávek. Zkuste to později, nebo zavolejte.',
            'files_count' => 'Přiložit jde nejvýše {max} souborů.',
            'files_size' => 'Soubor {soubor} je větší než {max}.',
            'files_total' => 'Soubory mají dohromady víc než {max}.',
            'files_type' => 'Soubor {soubor} nemá povolený formát. Povolené: {povolene}.',
            'files_upload' => 'Soubor {soubor} se nepodařilo nahrát, zkuste to znovu.',
            'csrf' => 'Platnost formuláře vypršela. Odešlete ho prosím znovu.',
            'remove' => 'Odebrat',
        ],
    ],

    'collections' => [
        'categories' => [
            ['slug' => 'pripravky', 'data' => ['name' => 'Přípravky', 'label' => 'PŘÍPRAVEK']],
            ['slug' => 'cnc', 'data' => ['name' => 'CNC díly', 'label' => 'CNC']],
            ['slug' => 'prototypy', 'data' => ['name' => 'Prototypy', 'label' => 'PROTOTYP']],
            ['slug' => 'opravy', 'data' => ['name' => 'Opravy', 'label' => 'OPRAVA']],
        ],
        'services' => [
            ['slug' => 'konstrukce', 'data' => ['title' => 'Konstrukce a dokumentace', 'tag' => 'SOLIDWORKS · CAM',
                'description' => '<p>Máme vlastní konstrukční kancelář. Z náčrtu, vzorku nebo popisu problému uděláme 3D model a výrobní výkresy, a zvládneme i kompletní dokumentaci k jednoúčelovým strojům. Poradíme, z jakého materiálu díly navrhnout, aby vydržely: víme, jak se různé materiály v provozu chovají.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'náčrt, vzorek, fotka, popis'], ['label' => 'DOSTANETE', 'value' => '3D model, výkresy a návrh materiálu']]]],
            ['slug' => 'presne-dily', 'data' => ['title' => 'Výroba přesných dílů', 'tag' => 'CNC · PODLE VÝKRESU',
                'description' => '<p>Vyrobíme díly podle výkresů, které nám dodáte, nebo podle vlastních výkresů z naší konstrukce. Kusově i v malých sériích, na obráběcích centrech a CNC soustruhu.</p>',
                'pairs' => [['label' => 'VÝKRESY', 'value' => 'vaše, nebo od nás'], ['label' => 'MATERIÁLY', 'value' => 'ocel, hliník a další podle zadání'], ['label' => 'SÉRIE', 'value' => 'kusově i malé série']]]],
            ['slug' => 'pripravky', 'data' => ['title' => 'Přípravky pro výrobu a měření', 'tag' => 'NÁVRH · VÝROBA · MONTÁŽ',
                'description' => '<p>Upínací, montážní a měřicí přípravky přesně pro váš díl a vaši linku. Navrhneme je, vyrobíme, smontujeme a vyzkoušíme, takže je jen postavíte na místo.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'díl a popis operace nebo měření'], ['label' => 'DOSTANETE', 'value' => 'smontovaný a odzkoušený přípravek s výkresy']]]],
            ['slug' => 'jednoucelove-stroje', 'data' => ['title' => 'Jednoúčelové stroje', 'tag' => 'PRO VÝROBNÍ LINKY',
                'description' => '<p>Navrhneme a postavíme jednoúčelový stroj pro vaši výrobní linku, včetně kompletní dokumentace. Řídicí systém SIMATIC k němu zajistíme ve spolupráci s partnerem.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'popis operace, kterou má stroj dělat'], ['label' => 'DOSTANETE', 'value' => 'stroj s dokumentací, řízení SIMATIC v kooperaci']]]],
            ['slug' => 'sestavy', 'data' => ['title' => 'Montáž sestav a subdodávky', 'tag' => 'SESTAVY',
                'description' => '<p>Z vyrobených dílů smontujeme hotové sestavy. Velkým firmám dodáváme menší sestavy pro jejich výrobní linky.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'výkresy sestavy nebo zadání'], ['label' => 'DOSTANETE', 'value' => 'smontovanou sestavu připravenou do linky']]]],
            ['slug' => 'chapadla', 'data' => ['title' => 'Chapadla pro roboty', 'tag' => 'ROBOTIKA',
                'description' => '<p>Navrhneme a vyrobíme chapadlo pro robota přesně podle dílu, který má uchopit.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'díl a typ robota'], ['label' => 'DOSTANETE', 'value' => 'chapadlo připravené k montáži']]]],
            ['slug' => 'opravy', 'data' => ['title' => 'Nástrojařské práce a opravy na linkách', 'tag' => 'OPRAVA · ÚPRAVA',
                'description' => '<p>Děláme nástrojařské práce a opravy přímo na výrobních linkách. Díly, které vám nevyhovují, vylepšíme nebo upravíme podle vašeho požadavku.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'popis problému nebo nevyhovující díl'], ['label' => 'DOSTANETE', 'value' => 'opravený nebo vylepšený díl, ať linka nestojí']]]],
            ['slug' => '3d-tisk', 'data' => ['title' => '3D tisk a prototypy', 'tag' => 'NA ZKOUŠKU · KE SCHVÁLENÍ',
                'description' => '<p>Díl vám nejdřív vytiskneme na 3D tiskárně: na zkoušku, ke schválení, nebo abyste si ho mohli prohlédnout v ruce dřív, než se vyrobí z kovu.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'zadání nebo 3D model'], ['label' => 'DOSTANETE', 'value' => 'vytištěný díl k odzkoušení']]]],
        ],
        'works' => [
            ['data' => ['title' => '[Montážní přípravek pro …]', 'category' => 'cat:pripravky', 'photo' => $img('[FOTO: nejlepší zakázka, přípravek v provozu u zákazníka]'), 'spec' => '[MATERIÁL] · [KS] · [ODVĚTVÍ]', 'year' => '[ROK]',
                'brief' => '[Co zákazník potřeboval a proč to nešlo koupit hotové.]', 'solution' => '[Jak jsme přípravek navrhli a vyrobili.]', 'result' => '[Co se zákazníkovi zlepšilo, např. kratší čas operace.]',
                'featured' => true, 'on_home' => false]],
            ['data' => ['title' => '[Upínací přípravek]', 'category' => 'cat:pripravky', 'photo' => $img('[FOTO: přípravek]'), 'spec' => '[MATERIÁL] · [KS] · [ODVĚTVÍ]', 'year' => '', 'brief' => '', 'solution' => '', 'result' => '', 'featured' => false, 'on_home' => true]],
            ['data' => ['title' => '[Obrobený díl]', 'category' => 'cat:cnc', 'photo' => $img('[FOTO: díl]'), 'spec' => '[MATERIÁL] · [KS] · [ODVĚTVÍ]', 'year' => '', 'brief' => '', 'solution' => '', 'result' => '', 'featured' => false, 'on_home' => true]],
            ['data' => ['title' => '[Opravený nástroj]', 'category' => 'cat:opravy', 'photo' => $img('[FOTO: před a po opravě]'), 'spec' => '[TYP NÁSTROJE] · [ODVĚTVÍ]', 'year' => '', 'brief' => '', 'solution' => '', 'result' => '', 'featured' => false, 'on_home' => true]],
            ['data' => ['title' => '[Prototyp]', 'category' => 'cat:prototypy', 'photo' => $img('[FOTO: prototyp vedle 3D modelu]'), 'spec' => '[MATERIÁL] · 1 ks', 'year' => '', 'brief' => '', 'solution' => '', 'result' => '', 'featured' => false, 'on_home' => false]],
            ['data' => ['title' => '[Kontrolní přípravek]', 'category' => 'cat:pripravky', 'photo' => $img('[FOTO: přípravek s dílem]'), 'spec' => '[MATERIÁL] · [KS] · [ODVĚTVÍ]', 'year' => '', 'brief' => '', 'solution' => '', 'result' => '', 'featured' => false, 'on_home' => false]],
            ['data' => ['title' => '[Série dílů]', 'category' => 'cat:cnc', 'photo' => $img('[FOTO: série kusů v řadě]'), 'spec' => '[MATERIÁL] · [KS] · [ODVĚTVÍ]', 'year' => '', 'brief' => '', 'solution' => '', 'result' => '', 'featured' => false, 'on_home' => false]],
        ],
        'machines' => [
            ['data' => ['name' => '2× MAS MCV 754 Quick', 'type' => 'Obráběcí centrum · CNC frézování', 'range' => 'zásobník na 24 nástrojů · Heidenhain', 'use' => 'přesné díly, díly přípravků a strojů']],
            ['data' => ['name' => 'CNC soustruh', 'type' => 'CNC soustružení', 'range' => '', 'use' => 'rotační díly']],
            ['data' => ['name' => 'RNGJ 32', 'type' => 'Nástrojařská frézka', 'range' => '', 'use' => 'nástrojařské práce, úpravy dílů']],
            ['data' => ['name' => 'BPH 20', 'type' => 'Bruska na plocho', 'range' => '', 'use' => 'přesné rovinné plochy']],
            ['data' => ['name' => 'Další stroje', 'type' => 'Nástrojařská dílna', 'range' => '', 'use' => 'nástrojařské práce']],
            ['data' => ['name' => 'Prusa', 'type' => '3D tiskárna', 'range' => '', 'use' => 'díly na zkoušku a ke schválení']],
            ['data' => ['name' => 'Kalicí pec', 'type' => 'Kalení', 'range' => 'malá pec · kalení do druhého dne', 'use' => 'kalení nástrojů a dílů']],
            ['data' => ['name' => 'Broušení na kulato', 'type' => 'V kooperaci', 'range' => '', 'use' => 'rotační plochy']],
            ['data' => ['name' => 'Gravírování', 'type' => 'V kooperaci', 'range' => '', 'use' => 'popisy a značení dílů']],
            ['data' => ['name' => 'SolidWorks + CAM', 'type' => 'CAD / CAM', 'range' => '3D · výkresy · programy pro CNC', 'use' => 'konstrukce, dokumentace, programování']],
        ],
        'people' => [
            ['data' => ['name' => '[JMÉNO PŘÍJMENÍ]', 'role' => '[NÁSTROJAŘ · MAJITEL]', 'portrait' => $img('[PORTRÉT v dílně]')]],
            ['data' => ['name' => '[JMÉNO PŘÍJMENÍ]', 'role' => '[KONSTRUKTÉR]', 'portrait' => $img('[PORTRÉT u počítače se SolidWorks]')]],
        ],
    ],
];
