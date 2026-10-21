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
                'tags' => ['NÁSTROJÁRNA', 'CNC OBRÁBĚNÍ', 'ÚVALY U PRAHY', '30 LET'],
                'title' => 'Od nápadu po díl, který sedí napoprvé.',
                'lead' => 'Přípravky, nástroje a obráběné díly pro firmy. Navrhneme v SolidWorks, vyrobíme na CNC a smontujeme v Úvalech.',
            ],
            'part' => [
                'aria' => 'Co umíme, ukázáno na dílu',
                'head_left' => 'UPÍNACÍ DESKA · OCEL · M 1:2',
                'head_right' => 'KLIKNĚTE NA ČÍSLA',
                'pos_label' => 'POZ.',
                'pins' => [
                    ['title' => 'Konstrukce a výkres', 'text' => 'Díl nejdřív navrhneme v SolidWorks. Stačí nám náčrt, vzorek nebo popis problému. Dostanete 3D model a výrobní výkres s kótami.', 'spec' => 'SolidWorks · STEP · PDF · DWG', 'x' => 71, 'y' => 82],
                    ['title' => 'CNC frézování', 'text' => 'Kapsy, drážky a tvarové plochy obrobíme na CNC, kusově i v malé sérii.', 'spec' => '[STROJE] · [MAX. ROZMĚR] · [MATERIÁLY]', 'x' => 50, 'y' => 45],
                    ['title' => 'Přesné díry a závity', 'text' => 'Díry pro kolíky, šrouby a ložiska v toleranci podle výkresu.', 'spec' => '[TYPICKÁ PŘESNOST, např. H7]', 'x' => 73, 'y' => 45],
                    ['title' => 'Montáž a opravy', 'text' => 'Přípravky smontujeme a vyzkoušíme, aby šly rovnou do linky. Opravujeme i opotřebované nástroje, také podle vzorku.', 'spec' => 'Přípravky · nástroje · prototypy', 'x' => 30, 'y' => 64],
                ],
            ],
            'work' => ['title' => 'Z naší dílny', 'link' => 'Všechny práce →'],
            'cta' => $noCta,
            'seo' => ['title' => 'VH Works – nástrojárna v Úvalech u Prahy', 'description' => 'Přípravky, nástroje a CNC obráběné díly pro firmy. Konstrukce v SolidWorks, výroba a montáž v Úvalech u Prahy. 30 let praxe.', 'image' => $emptyImg],
        ],
        'services' => [
            'hero' => ['label' => 'SLUŽBY · 6 POZIC', 'title' => 'Přijďte s tím, co máte. Odejdete s hotovým dílem.'],
            'toc' => ['aria' => 'Seznam služeb', 'note' => 'Nevíte, kam vaše zakázka patří? Zavolejte {telefon}, poradíme.', 'pos_label' => 'POZ.'],
            'cta' => $noCta,
            'seo' => ['title' => 'Služby – konstrukce, CNC, přípravky, nástroje', 'description' => 'Konstrukce v 3D, výkresová dokumentace, prototypy, CNC obrábění, přípravky a výroba i opravy nástrojů pro firmy.', 'image' => $emptyImg],
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
                'title' => 'Malá dílna, 30 let řemesla.',
                'story' => 'Konstruktér i nástrojař sedí ve stejné budově. Kdo díl navrhne, ten ví, jak se bude vyrábět, a kdo ho vyrábí, může se hned zeptat. Proto u nás díly sedí napoprvé. [DOPLNIT: krátký příběh firmy, kdo ji založil a kdy]',
            ],
            'photo' => ['image' => $img('[FOTO NA ŠÍŘKU: celá dílna se stroji, uklizeno, denní světlo, ideálně s člověkem u stroje]')],
            'machines' => ['title' => 'Strojní park', 'status' => 'STAV K [MĚSÍC ROK]', 'col_name' => 'STROJ', 'col_type' => 'TYP', 'col_range' => 'PRACOVNÍ ROZSAH', 'col_use' => 'NA CO HO POUŽÍVÁME'],
            'people' => ['title' => 'Kdo vám bude volat zpátky', 'text' => 'Žádné call centrum. O vaší zakázce mluvíte rovnou s tím, kdo ji bude dělat.'],
            'visit' => ['title' => 'Přijeďte se podívat', 'parking' => '[PARKOVÁNÍ, VJEZD PRO DODÁVKU]', 'button' => 'Domluvit návštěvu'],
            'seo' => ['title' => 'Dílna a strojní park v Úvalech', 'description' => 'Malá nástrojárna s 30 lety praxe. Strojní park, lidé a adresa dílny v Úvalech u Prahy.', 'image' => $emptyImg],
        ],
        'contact' => [
            'hero' => ['label' => 'KONTAKT', 'title' => 'Nejrychleji po telefonu.', 'l_email' => 'E-MAIL', 'l_address' => 'DÍLNA', 'l_hours' => 'PROVOZNÍ DOBA', 'l_billing' => 'FAKTURAČNÍ ÚDAJE'],
            'form' => [
                'head_left' => 'POPTÁVKOVÝ LIST',
                'head_right' => 'ODPOVÍDÁME DO [X] PRACOVNÍCH DNŮ',
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
                'text' => '<p>Projdeme zadání a přílohy a ozveme se s cenou a termínem, obvykle do [X] pracovních dnů. Kopii poptávky jsme vám poslali e-mailem.</p><p>Když bude potřeba něco upřesnit, zavoláme vám.</p>',
                'phone_label' => 'Spěcháte? Zavolejte',
                'back' => 'Zpět na úvod',
            ],
            'seo' => ['title' => 'Poptávka odeslána', 'description' => '', 'image' => $emptyImg],
        ],
        'privacy' => [
            'main' => ['label' => 'DOKUMENT', 'title' => 'Ochrana osobních údajů', 'body' => '<p>[DOPLNIT: zásady zpracování osobních údajů]</p>'],
            'seo' => ['title' => 'Ochrana osobních údajů', 'description' => '', 'image' => $emptyImg],
        ],
    ],

    'settings' => [
        'company' => [
            'name' => 'VH Works',
            'phone' => '+420 606 879 112',
            'email' => '[E-MAIL]',
            'street' => 'Pražská 1473',
            'zip' => '250 82',
            'city' => 'Úvaly',
            'hours' => '[PO–PÁ, OD–DO]',
            'hours_schema' => '',
            'ico' => '[IČO]',
            'ico_label' => 'IČO',
            'dic' => '[DIČ]',
            'dic_label' => 'DIČ',
            'map_image' => $img('[MAPA: Pražská 1473, Úvaly]'),
            'map_url' => 'https://mapy.cz/?q=Pra%C5%BEsk%C3%A1%201473%2C%20%C3%9Avaly',
            'map_link' => 'Otevřít v Mapy.cz',
        ],
        'display' => ['logo' => ['media' => null, 'alt' => 'VH Works – Tooling, Engineering, Precision'], 'hide_placeholders' => false, 'skip_link' => 'Přeskočit na obsah'],
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
        ],
        'cta' => [
            'label' => 'PDF · STEP · DWG · NEBO FOTKA NÁČRTU',
            'title' => 'Pošlete výkres, ozveme se s cenou.',
            'button' => 'Poslat poptávku',
            'button_url' => '/kontakt',
        ],
        'seo' => [
            'title_suffix' => ' | VH Works',
            'description' => 'VH Works, nástrojárna v Úvalech u Prahy. Konstrukce v SolidWorks, výkresy, prototypy, CNC obrábění, přípravky a nástroje pro firmy.',
            'image' => $emptyImg,
        ],
        'mail' => [
            'inquiry_to' => '',
            'company_subject' => 'Nová poptávka z webu: {firma}',
            'customer_subject' => 'Potvrzení poptávky – VH Works',
            'customer_body' => "Dobrý den {jmeno},\n\nděkujeme za poptávku. Projdeme ji a ozveme se s cenou a termínem, obvykle do [X] pracovních dnů.\n\nKdyž spěcháte, zavolejte nám na {telefon}.\n\nS pozdravem\nVH Works\nPražská 1473, 250 82 Úvaly",
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
            ['slug' => 'konstrukce', 'data' => ['title' => 'Konstrukce v 3D', 'tag' => 'SOLIDWORKS',
                'description' => '<p>Z nápadu, náčrtu nebo popisu problému uděláme 3D model, který jde opravdu vyrobit. Konstruujeme s ohledem na to, jak se díl bude obrábět a montovat, protože ho pak sami vyrábíme.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'náčrt, vzorek, fotka, popis'], ['label' => 'DOSTANETE', 'value' => '3D model (STEP) připravený k výrobě']]]],
            ['slug' => 'vykresy', 'data' => ['title' => 'Výkresová dokumentace', 'tag' => 'PDF · DWG',
                'description' => '<p>Výrobní výkresy s kótami, tolerancemi a drsnostmi. Hodí se i ve chvíli, kdy díl máte, ale výkres k němu chybí nebo se ztratil.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => '3D model nebo fyzický vzorek'], ['label' => 'DOSTANETE', 'value' => 'výkresy, podle kterých vyrobí kdokoli']]]],
            ['slug' => 'prototypy', 'data' => ['title' => 'Prototypy', 'tag' => '1 KS',
                'description' => '<p>První kus, na kterém si ověříte funkci a smontovatelnost dřív, než zaplatíte sérii nebo drahý nástroj.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'zadání nebo model'], ['label' => 'DOSTANETE', 'value' => 'funkční kus k odzkoušení · [OBVYKLÁ LHŮTA]']]]],
            ['slug' => 'cnc', 'data' => ['title' => 'CNC obrábění', 'tag' => '[FRÉZOVÁNÍ · SOUSTRUŽENÍ]',
                'description' => '<p>Díly podle vašeho výkresu, kusově i v malých sériích. Každý kus před expedicí přeměříme.</p>',
                'pairs' => [['label' => 'MAX. ROZMĚR', 'value' => '[X × Y × Z mm]'], ['label' => 'MATERIÁLY', 'value' => '[ocel, nerez, hliník…]'], ['label' => 'SÉRIE', 'value' => '1 až [X] ks']]]],
            ['slug' => 'pripravky', 'data' => ['title' => 'Přípravky', 'tag' => 'NÁVRH · VÝROBA · MONTÁŽ',
                'description' => '<p>Upínací, montážní a kontrolní přípravky přesně pro váš díl a vaši linku. Navrhneme je, vyrobíme, smontujeme a vyzkoušíme, takže je jen postavíte na místo.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'díl, který se má upínat, a popis operace'], ['label' => 'DOSTANETE', 'value' => 'smontovaný a odzkoušený přípravek s výkresy']]]],
            ['slug' => 'nastroje', 'data' => ['title' => 'Nástroje a opravy', 'tag' => 'VÝROBA · OPRAVA',
                'description' => '<p>Opravíme opotřebovaný nebo poškozený nástroj, nebo podle starého kusu vyrobíme nový. I když k němu už nikdo nemá výkres.</p>',
                'pairs' => [['label' => 'OD VÁS STAČÍ', 'value' => 'poškozený nebo starý kus'], ['label' => 'DOSTANETE', 'value' => 'opravený nebo nový nástroj, ať linka nestojí']]]],
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
            ['data' => ['name' => '[NÁZEV]', 'type' => 'CNC frézka', 'range' => '[X × Y × Z mm]', 'use' => '[přípravky, tvarové díly]']],
            ['data' => ['name' => '[NÁZEV]', 'type' => '[CNC soustruh]', 'range' => '[Ø × délka mm]', 'use' => '[rotační díly]']],
            ['data' => ['name' => '[NÁZEV]', 'type' => '[Konvenční frézka]', 'range' => '[ROZSAH]', 'use' => '[opravy, úpravy]']],
            ['data' => ['name' => '[NÁZEV]', 'type' => '[Bruska]', 'range' => '[ROZSAH]', 'use' => '[přesné plochy]']],
            ['data' => ['name' => 'SolidWorks', 'type' => 'CAD', 'range' => '3D · výkresy', 'use' => 'konstrukce, dokumentace']],
        ],
        'people' => [
            ['data' => ['name' => '[JMÉNO PŘÍJMENÍ]', 'role' => '[NÁSTROJAŘ · MAJITEL]', 'portrait' => $img('[PORTRÉT v dílně]')]],
            ['data' => ['name' => '[JMÉNO PŘÍJMENÍ]', 'role' => '[KONSTRUKTÉR]', 'portrait' => $img('[PORTRÉT u počítače se SolidWorks]')]],
        ],
    ],
];
