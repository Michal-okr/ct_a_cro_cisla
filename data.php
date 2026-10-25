<?php
declare(strict_types=1);

// Data a výpočty – sdílí je index.php (stránka) i narace.php (texty pro audio).

/* ------------------------------------------------------------------
 *  DATA – vše na jednom místě, ať se dá snadno aktualizovat.
 *  Zdroje jsou vypsány dole na stránce (sekce „Zdroje").
 * ------------------------------------------------------------------ */

// Průměrná roční míra inflace (CPI), ČSÚ
$inflace = [
    2006 => 2.5, 2007 => 2.8, 2008 => 6.3, 2009 => 1.0, 2010 => 1.5, 2011 => 1.9,
    2012 => 3.3, 2013 => 1.4, 2014 => 0.4, 2015 => 0.3, 2016 => 0.7, 2017 => 2.5,
    2018 => 2.1, 2019 => 2.8, 2020 => 3.2, 2021 => 3.8, 2022 => 15.1, 2023 => 10.7,
    2024 => 2.4, 2025 => 2.5,
];
// Předpoklad pro roky, které ještě nejsou uzavřené (ČSÚ: 12měs. průměr k 8/2026 = 2,0 %)
$inflacePredpoklad = [2026 => 2.0, 2027 => 2.0, 2028 => 2.0];

// Česká televize – „Vybrané ukazatele hospodaření" (ceskatelevize.cz), tis. Kč
$ct = [
    // rok => [výnosy celkem, z toho TV poplatky, přepočtený počet zaměstnanců, průměrná mzda ČT]
    2008 => [6760158, 5786967, 2881.25, 32934],
    2009 => [6925930, 5828617, 2896.25, 35239],
    2010 => [6886996, 5792441, 2824.75, 36403],
    2011 => [6664625, 5771222, 2797.50, 36124],
    2012 => [6636203, 5756009, 2855.75, 36797],
    2013 => [6788347, 5716915, 2925.50, 37173],
    2014 => [6932775, 5677569, 2902.50, 37829],
    2015 => [6852083, 5621037, 2906.25, 38325],
    2016 => [6830789, 5690429, 2913.25, 38669],
    2017 => [6457530, 5664548, 2920.75, 39620],
    2018 => [6553459, 5635063, 2929.00, 42284],
    2019 => [6468978, 5675771, 2945.75, 43693],
    2020 => [6183956, 5642900, 2970.50, 44735],
    2021 => [6682838, 5706938, 2991.60, 46239],
    2022 => [7174999, 5719814, 2955.65, 49219],
    2023 => [7208185, 5720838, 2934.48, 55254],
    2024 => [7543359, 5741826, 2971.63, 57796],
    2025 => [7457750, 6337872, 2877.80, 60745],
];

// Český rozhlas – výroční zprávy o hospodaření ČRo (skutečnost), tis. Kč
$cro = [
    // rok => [výnosy celkem, z toho rozhlasové poplatky, přepočtený počet zaměstnanců, průměrná mzda ČRo]
    2008 => [2242566, 1954996, 1465.00, 30830],
    2009 => [2237359, 1987533, 1470.00, 31487],
    2010 => [2205425, 1974476, 1453.00, 32047],
    2011 => [2188785, 1964754, 1425.00, 32747],
    2012 => [2158318, 1950121, 1415.00, 33867],
    2013 => [2321778, 2000291, 1430.00, 37021],
    2014 => [2236009, 2027309, 1464.00, 36639],
    2015 => [2249258, 2046157, 1464.00, 37634],
    2016 => [2212340, 2064100, 1486.00, 37436],
    2017 => [2298033, 2084834, 1489.50, 37901],
    2018 => [2264413, 2087062, 1477.64, 39394],
    2019 => [2295490, 2094236, 1419.50, 41324],
    2020 => [2299105, 2084453, 1417.48, 42386],
    2021 => [2321342, 2078374, 1413.33, 42938],
    2022 => [2257586, 2071494, 1378.33, 43432],
    2023 => [2288833, 2062718, 1353.29, 45670],
    2024 => [2400840, 2065766, 1359.77, 46665],
    2025 => [2583163, 2316180, 1353.96, 49076],
];
// Komerční výnosy ČRo (reklama + sponzoring + ostatní obchodní činnost), tis. Kč
$croKomercni = [2008 => 142061, 2024 => 104839, 2025 => 57677];

// Příjmy obyvatel (Kč/měsíc)
$prumMzda   = [2005 => 18344, 2008 => 22592, 2015 => 26591, 2020 => 36176, 2024 => 46165, 2025 => 49215]; // ČSÚ
$minMzda    = [2005 => 7185, 2008 => 8000, 2016 => 9900, 2020 => 14600, 2024 => 18900, 2025 => 20800, 2026 => 22400]; // MPSV/VZP
$duchod     = [2005 => 7728, 2008 => 9347, 2019 => 13431, 2025 => 21171, 2026 => 21839]; // ČSSZ/MPSV, viz poznámka

// Poplatky (Kč/měsíc)
$tvPoplatek  = ['stary' => 135, 'od' => '1. 1. 2008', 'novy' => 150, 'novyOd' => '1. 5. 2025'];
$roPoplatek  = ['stary' => 45,  'od' => '1. 10. 2005', 'novy' => 55, 'novyOd' => '1. 5. 2025'];

// Návrh vlády (sněmovní tisk 231) – pevný roční příspěvek ze státního rozpočtu, mil. Kč
$navrh = ['ct' => 5740, 'cro' => 2065];
// Vláda počítala s účinností 1. 1. 2027. V říjnu 2026 je realistický start nejdříve 2028
// (Klempíř: 1. 7. 2027 nebo 1. 1. 2028; rozpočet 2027 s převodem nepočítá).
$prvniRok = 2028;
// Výjimka v zákoně o DPH (od 2017, prodloužena do 31. 12. 2027) – roční dopad po skončení, mil. Kč
$dph = ['ct' => 420, 'cro' => 130];
// Dlouhodobý plán ČT – plánovaný výnos z poplatků, mil. Kč
$ctPlanPoplatky = [2026 => 6730, 2027 => 7050, 2028 => 7300];
$postaVypadek = 275; // ztráta České pošty za výběr poplatků (připomínka MV), mil. Kč/rok
// Rok 2026 – rozpočty/plán výběru poplatků, mil. Kč
$plan2026 = ['ct_poplatky' => 6730, 'ct_rozpocet' => 8500, 'cro_poplatky' => 2476, 'cro_rozpocet' => 2736];
// Ohlášené dopady návrhu (vyjádření vedení)
$dopady = ['ct' => '300–500 lidí', 'cro' => '150–200 lidí'];
$obyvatel = 10.9; // mil. (ČSÚ, zaokrouhleno)

/* ------------------------------------------------------------------
 *  VÝPOČTY
 * ------------------------------------------------------------------ */
function cpi(array $inf, int $od, int $do): float
{
    $i = 1.0;
    for ($r = $od + 1; $r <= $do; $r++) {
        $i *= 1 + $inf[$r] / 100;
    }
    return $i;
}
function rust(float $a, float $b): float { return ($b / $a - 1) * 100; }
function kc(float $n, int $d = 0): string { return number_format($n, $d, ',', "\u{00A0}"); }
function pct(float $n, int $d = 1, bool $plus = true): string
{
    return ($plus && $n > 0 ? '+' : '') . number_format($n, $d, ',', "\u{00A0}") . "\u{00A0}%";
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$infAll = $inflace + $inflacePredpoklad;
$cpi0825 = cpi($inflace, 2008, 2025);
$cpi0525 = cpi($inflace, 2005, 2025);
$cpi08R = cpi($infAll, 2008, $prvniRok);

// Poplatek přepočtený různými „měřítky"
$tvDleInflace = $tvPoplatek['stary'] * $cpi0825;
$tvDleMzdy    = $tvPoplatek['stary'] * $prumMzda[2025] / $prumMzda[2008];
$tvDleMin     = $tvPoplatek['stary'] * $minMzda[2025] / $minMzda[2008];
$tvDleDuchodu = $tvPoplatek['stary'] * $duchod[2025] / $duchod[2008];
$roDleInflace = $roPoplatek['stary'] * $cpi0525;
$roDleMzdy    = $roPoplatek['stary'] * $prumMzda[2025] / $prumMzda[2005];

// ČT: návrh 2027 vs. 2008 v reálných cenách
$ctPopl2008   = $ct[2008][1] / 1000;           // mil. Kč
$navrhReal08  = $navrh['ct'] / $cpi08R;        // v cenách 2008
$navrhPodil   = $navrhReal08 / $ctPopl2008 * 100;
$navrhVs2026  = rust($plan2026['ct_poplatky'], $navrh['ct']);
$cro2026Vs    = rust($plan2026['cro_poplatky'], $navrh['cro']);
$croPopl2008  = $cro[2008][1] / 1000;
$croNavrhReal = $navrh['cro'] / $cpi08R;
$croNavrhPodil = $croNavrhReal / $croPopl2008 * 100;

// Srovnání růstu 2008 → 2025 (pro graf)
$rusty = [
    ['Ceny (inflace CPI)',            ($cpi0825 - 1) * 100,                          'ref'],
    ['Průměrná mzda v ČR',            rust($prumMzda[2008], $prumMzda[2025]),        'ref'],
    ['Minimální mzda',                rust($minMzda[2008], $minMzda[2025]),          'ref'],
    ['Průměrný starobní důchod',      rust($duchod[2008], $duchod[2025]),            'ref'],
    ['Průměrná mzda v ČT',            rust($ct[2008][3], $ct[2025][3]),              'ct'],
    ['Celkové výnosy ČT',             rust($ct[2008][0], $ct[2025][0]),              'ct'],
    ['TV poplatek (135 → 150 Kč)',    rust($tvPoplatek['stary'], $tvPoplatek['novy']), 'ct'],
    ['Výnos z TV poplatků ČT',        rust($ct[2008][1], $ct[2025][1]),              'ct'],
    ['Počet zaměstnanců ČT',          rust($ct[2008][2], $ct[2025][2]),              'ct'],
    ['Průměrná mzda v ČRo',           rust($cro[2008][3], $cro[2025][3]),            'cro'],
    ['Celkové výnosy ČRo',            rust($cro[2008][0], $cro[2025][0]),            'cro'],
    ['Výnos z rozhl. poplatků ČRo',   rust($cro[2008][1], $cro[2025][1]),            'cro'],
    ['Počet zaměstnanců ČRo',         rust($cro[2008][2], $cro[2025][2]),            'cro'],
];

// Řada pro čárový graf ČT: výnos z poplatků nominálně vs. v cenách 2008
$serie = [];
foreach ($ct as $rok => $r) {
    $serie[] = ['rok' => $rok, 'nom' => $r[1] / 1e6, 'real' => $r[1] / 1e6 / cpi($inflace, 2008, $rok)];
}
$serie[] = ['rok' => $prvniRok, 'nom' => $navrh['ct'] / 1000, 'real' => $navrh['ct'] / 1000 / $cpi08R, 'navrh' => true];

$serieCro = [];
foreach ($cro as $rok => $r) {
    $serieCro[] = ['rok' => $rok, 'nom' => $r[1] / 1e6, 'real' => $r[1] / 1e6 / cpi($inflace, 2008, $rok)];
}
$serieCro[] = ['rok' => $prvniRok, 'nom' => $navrh['cro'] / 1000, 'real' => $navrh['cro'] / 1000 / $cpi08R, 'navrh' => true];


/* ------------------------------------------------------------------
 *  Kdo platí dnes – výjimky a firmy (zákon 348/2005 Sb. po novele 119/2025 Sb.)
 * ------------------------------------------------------------------ */
$prijmovyLimit = ['nasobek' => 2.15, 'jednotlivec' => 11825, 'par' => 18813, 'od' => '1. 10. 2026'];
// Firmy a OSVČ: násobek měsíčního poplatku podle přepočteného počtu zaměstnanců (od 1. 7. 2025)
$firmyNasobky = [
    ['méně než 25', 0], ['25–49', 5], ['50–99', 10], ['100–199', 20],
    ['200–249', 30], ['250–499', 70], ['500 a více', 100],
];
$poplatekMesic = $tvPoplatek['novy'] + $roPoplatek['novy']; // 205 Kč

/* ------------------------------------------------------------------
 *  Státní dluh (MF – Čtvrtletní zpráva o řízení státního dluhu, 1.–3. Q 2026)
 * ------------------------------------------------------------------ */
$dluh = [
    'stav' => 3882.2,          // mld. Kč k 30. 9. 2026
    'stav2025' => 3365.2,      // mld. Kč k 1. 1. 2025
    'stav2026' => 3677.6,      // mld. Kč k 1. 1. 2026
    'obsluha2025' => 98.1,     // čisté výdaje na obsluhu dluhu 2025, mld. Kč
    'vynosNovych' => 4.7,      // % p. a., průměrný výnos nově prodaných fixních dluhopisů 1.–3. Q 2026
    'schodek2027' => 386,      // mld. Kč, vládní návrh rozpočtu 2027
];
$dluh['prumNaklad'] = $dluh['obsluha2025'] / (($dluh['stav2025'] + $dluh['stav2026']) / 2) * 100; // ≈ 2,8 %

/** Simulace: příspěvek se celý platí z nového dluhu, úroky se také dluhem. Výstup v mil. Kč. */
function dluhSimulace(float $rocne, float $urokPct, int $od, int $let): array
{
    $r = $urokPct / 100; $dluhStav = 0.0; $uroky = 0.0; $rows = [];
    for ($i = 0; $i < $let; $i++) {
        $urok = $r * ($dluhStav + $rocne / 2);      // příspěvek se vyplácí měsíčně → průměrně půl roku
        $dluhStav += $rocne + $urok;
        $uroky += $urok;
        $rows[] = ['rok' => $od + $i, 'urok' => $urok, 'dluh' => $dluhStav, 'urokyCelkem' => $uroky];
    }
    return $rows;
}
$prispevekCelkem = $navrh['ct'] + $navrh['cro'];
$dluhSim = dluhSimulace($prispevekCelkem, $dluh['vynosNovych'], $prvniRok, 10);
