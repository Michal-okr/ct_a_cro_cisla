<?php
declare(strict_types=1);

/* ------------------------------------------------------------------
 *  Texty pro audio verzi. Čísla se vypisují slovy, aby je syntetický
 *  hlas četl správně česky. Spuštění z příkazové řádky vypíše JSON:
 *  php narace.php [soubor.json]  ->  {"hlas": "...", "sekce": [{"id", "nazev", "text"}, ...]}
 * ------------------------------------------------------------------ */

require_once __DIR__ . '/data.php';

const JEDNOTKY = ['nula', 'jeden', 'dva', 'tři', 'čtyři', 'pět', 'šest', 'sedm', 'osm', 'devět', 'deset',
    'jedenáct', 'dvanáct', 'třináct', 'čtrnáct', 'patnáct', 'šestnáct', 'sedmnáct', 'osmnáct', 'devatenáct'];
const DESITKY = [2 => 'dvacet', 3 => 'třicet', 4 => 'čtyřicet', 5 => 'padesát', 6 => 'šedesát', 7 => 'sedmdesát', 8 => 'osmdesát', 9 => 'devadesát'];
const STOVKY = [1 => 'sto', 2 => 'dvě stě', 3 => 'tři sta', 4 => 'čtyři sta', 5 => 'pět set', 6 => 'šest set', 7 => 'sedm set', 8 => 'osm set', 9 => 'devět set'];

// Jednotky: [1, 2–4, 5+, za desetinným číslem], rod číslovky
const U_MLD = [['miliarda', 'miliardy', 'miliard', 'miliardy'], 'f'];
const U_MIL = [['milion', 'miliony', 'milionů', 'milionu'], 'm'];
const U_KC  = [['koruna', 'koruny', 'korun', 'koruny'], 'f'];
const U_PCT = [['procento', 'procenta', 'procent', 'procenta'], 'n'];
const U_PB  = [['procentní bod', 'procentní body', 'procentních bodů', 'procentního bodu'], 'm'];
const U_ZAM = [['zaměstnanec', 'zaměstnanci', 'zaměstnanců', 'zaměstnance'], 'm'];

/** Celé číslo slovy (do miliard). Rod se uplatní jen u samostatné 1 a 2. */
function slovy(int $n, string $rod = 'm'): string
{
    if ($n < 0) return 'mínus ' . slovy(-$n, $rod);
    if ($n === 0) return 'nula';
    $casti = [];
    foreach ([[1_000_000_000, ['miliarda', 'miliardy', 'miliard'], 'f'], [1_000_000, ['milion', 'miliony', 'milionů'], 'm'], [1000, ['tisíc', 'tisíce', 'tisíc'], 'm']] as [$rad, $tvary, $r]) {
        $k = intdiv($n, $rad);
        if ($k > 0) {
            $tvar = $k === 1 ? $tvary[0] : ($k <= 4 ? $tvary[1] : $tvary[2]);
            $casti[] = $k === 1 ? $tvar : stovky($k, $r) . ' ' . $tvar;
            $n %= $rad;
        }
    }
    if ($n > 0) $casti[] = stovky($n, $rod);
    return implode(' ', $casti);
}

function stovky(int $n, string $rod): string
{
    $o = [];
    if ($n >= 100) { $o[] = STOVKY[intdiv($n, 100)]; $n %= 100; }
    $slozene = $o !== [];
    if ($n >= 20) {
        $o[] = DESITKY[intdiv($n, 10)];
        $n %= 10;
        $slozene = true;
        if ($n === 1) { $o[] = 'jedna'; $n = 0; }
    }
    if ($n > 0) {
        if (!$slozene && $n === 1) $o[] = ['m' => 'jeden', 'f' => 'jedna', 'n' => 'jedno'][$rod];
        elseif ($n === 2 && $rod !== 'm' && !$slozene) $o[] = 'dvě';
        else $o[] = JEDNOTKY[$n];
    }
    return implode(' ', $o);
}

/** Číslo (i desetinné) slovy + správný tvar jednotky. */
function mn(float $x, int $dec, array $u): string
{
    [$tvary, $rod] = $u;
    $x = round(abs($x), $dec);
    $cele = (int)floor($x);
    $des = $dec > 0 ? (int)round(($x - $cele) * 10 ** $dec) : 0;
    if ($dec > 0 && $des > 0) {
        while ($des % 10 === 0) { $des /= 10; $dec--; }
        $celaSl = $cele <= 1 ? ($cele === 1 ? 'jedna celá' : 'nula celá') : ($cele >= 2 && $cele <= 4 ? slovy($cele, 'f') . ' celé' : slovy($cele, 'f') . ' celých');
        $desSl = str_pad((string)$des, $dec, '0', STR_PAD_LEFT);
        $desSl = $desSl[0] === '0' ? 'nula ' . slovy((int)$desSl) : slovy($des, 'f');
        return "$celaSl $desSl {$tvary[3]}";
    }
    $tvar = $cele === 1 ? $tvary[0] : ($cele >= 2 && $cele <= 4 ? $tvary[1] : $tvary[2]);
    return slovy($cele, $rod) . " $tvar";
}

function rokSl(int $r): string { return slovy($r); }
function mld(float $milKc, int $dec = 2): string { return mn($milKc / 1000, $dec, U_MLD) . ' korun'; }
function mil(float $milKc): string { return mn($milKc, 0, U_MIL) . ' korun'; }
function kcs(float $kc): string { return mn($kc, 0, U_KC); }
function proc(float $p, int $dec = 0): string { return mn($p, $dec, U_PCT); }

function narace(): array
{
    global $navrh, $plan2026, $prvniRok, $cpi0825, $cpi0525, $navrhPodil, $croNavrhPodil, $ct, $cro, $prumMzda, $minMzda,
        $duchod, $tvDleInflace, $tvDleMzdy, $tvDleMin, $tvDleDuchodu, $roDleInflace, $roDleMzdy, $dph, $ctPlanPoplatky,
        $postaVypadek, $obyvatel, $navrhReal08, $croNavrhReal, $croKomercni, $navrhVs2026, $cro2026Vs, $ctPopl2008, $inflace,
        $prijmovyLimit, $poplatekMesic, $dluh, $dluhSim, $prispevekCelkem, $scenarRealne, $scenare, $poplatekReal;

    $R = rokSl($prvniRok);
    $infl = proc(($cpi0825 - 1) * 100);
    $sum = $navrh['ct'] + $navrh['cro'];
    $dphSum = $dph['ct'] + $dph['cro'];
    $ctReal24 = $ct[2024][1] / 1000 / cpi($inflace, 2008, 2024);

    $s = [];

    $s[] = ['uvod', 'Úvod a stav projednávání',
        "Konec poplatků za Českou televizi a Český rozhlas: co říkají čísla. "
        . "Vláda chce místo poplatků posílat televizi a rozhlasu peníze ze státního rozpočtu. Koalice říká, že vrací financování na úroveň roku dva tisíce dvacet čtyři. "
        . "Opozice a obě instituce odpovídají, že úroveň roku dva tisíce dvacet čtyři je ve skutečnosti úroveň roku dva tisíce osm, protože se poplatek sedmnáct let neměnil. "
        . "Důležité upozornění: v sobotu dvacátého čtvrtého října dva tisíce dvacet šest Sněmovna zákon nepřijala, pouze ho poslala do druhého čtení. "
        . "Návrhy opozice na zamítnutí a na vrácení vládě podpořilo shodně osmdesát jedna ze sto sedmdesáti sedmi přítomných poslanců. "
        . "Zbývá druhé a třetí čtení, Senát a prezident. Realistický start státního financování je až rok $R. "
        . "Vláda navrhuje pro Českou televizi " . mld($navrh['ct']) . " ročně a pro Český rozhlas " . mld($navrh['cro'], 3) . ". "
        . "Proti letošnímu plánu poplatků je to u televize méně o " . proc(abs($navrhVs2026), 1) . " a u rozhlasu o " . proc(abs($cro2026Vs), 1) . ". "
        . "Ceny přitom od roku dva tisíce osm do roku dva tisíce dvacet pět vzrostly o $infl."];

    $s[] = ['start-dph', "Start v roce $prvniRok a konec výjimky z DPH",
        "Proč ne rok dva tisíce dvacet sedm. Po prvním čtení zbývá projednání ve výboru, druhé a třetí čtení, Senát s opoziční většinou a prezident. "
        . "Ministr kultury Klempíř v září řekl, že termín prvního ledna dva tisíce dvacet sedm je jen obtížně dosažitelný, a jako možné uvedl první červenec dva tisíce dvacet sedm nebo první leden dva tisíce dvacet osm. "
        . "Ministryně financí Schillerová potvrdila, že rozpočet na rok dva tisíce dvacet sedm s převodem nepočítá. Poplatky sto padesát a padesát pět korun se tedy v roce dva tisíce dvacet sedm nejspíš platí dál. "
        . "Ve stejném roce, kdy by mělo začít státní financování, končí výjimka z DPH. Od roku dva tisíce sedmnáct umožňuje zákon o DPH televizi a rozhlasu odečítat daň z nákupů stejně jako komerčním stanicím. "
        . "Výjimka platí do třicátého prvního prosince dva tisíce dvacet sedm a vládní návrh ji neprodlužuje. "
        . "Od roku dva tisíce dvacet osm tak televize přijde přibližně o " . mil($dph['ct']) . " ročně a rozhlas o " . mil($dph['cro']) . ". "
        . "V prvním roce státního financování by tak televizi oproti letošnímu plánu chybělo celkem " . mld($plan2026['ct_poplatky'] - $navrh['ct'] + $dph['ct']) . " "
        . "a rozhlasu " . mil($plan2026['cro_poplatky'] - $navrh['cro'] + $dph['cro']) . ", což je zhruba pětina jeho rozpočtu. "
        . "Pro státní rozpočet je to jinak: nový výdaj činí " . mld($sum) . " ročně, ale konec výjimky z DPH státu naopak přinese asi " . mil($dphSum) . " na dani navíc. "
        . "Čistá zátěž rozpočtu je tak zhruba " . mld($sum - $dphSum) . ". Státní Česká pošta navíc přijde o " . mil($postaVypadek) . " ročně za výběr poplatků."];

    $s[] = ['spor', 'Návrat na rok 2024, nebo na rok 2008?',
        "Obě strany mluví o stejném čísle. Výnos České televize z poplatků byl v roce dva tisíce dvacet čtyři " . mld($ct[2024][1] / 1000) . ". "
        . "V roce dva tisíce osm to bylo " . mld($ct[2008][1] / 1000) . ", tedy prakticky stejně. Vláda navrhuje " . mld($navrh['ct']) . ". "
        . "Nominálně je to tedy stejná částka jako v roce dva tisíce osm i dva tisíce dvacet čtyři. Reálně ale ne. "
        . "V cenách roku dva tisíce osm měl výnos z roku dva tisíce dvacet čtyři hodnotu jen " . mld($ctReal24) . ". "
        . "Navržený příspěvek bude mít v roce $R v cenách roku dva tisíce osm hodnotu asi " . mld($navrhReal08) . ", "
        . "tedy jen " . proc($navrhPodil) . " toho, co televize dostávala v roce dva tisíce osm."];

    $s16 = $scenarRealne(2016); $s24 = $scenarRealne(2024); $s08 = $scenarRealne(2008); $stred = $scenare[3];
    $s[] = ['kompromis', 'Kompromis: sejít se uprostřed, u roku dva tisíce šestnáct?',
        "Mezi rokem dva tisíce osm a rokem dva tisíce dvacet čtyři je šestnáct let, polovina je rok dva tisíce šestnáct. "
        . "Aby měla Česká televize v roce $R stejnou kupní sílu jako její výnos z poplatků v roce dva tisíce šestnáct, potřebovala by asi " . mld($s16['ct'], 1) . ". "
        . "Český rozhlas by potřeboval asi " . mld($s16['cro'], 1) . ". Vláda přitom navrhuje " . mld($navrh['ct']) . " a " . mld($navrh['cro'], 3) . ". "
        . "Měsíční poplatky sto třicet pět a čtyřicet pět korun z roku dva tisíce šestnáct by dnes odpovídaly asi " . kcs($poplatekReal(2016)) . ". Dnes se platí dvě stě pět korun. "
        . "Proč střed nevychází uprostřed? Inflace nebyla rovnoměrná. Mezi lety dva tisíce osm a dva tisíce šestnáct vzrostly ceny jen asi o " . proc((cpi($inflace, 2008, 2016) - 1) * 100) . " a většina zdražení přišla až v letech dva tisíce dvacet jedna až dva tisíce dvacet tři. "
        . "Úroveň roku dva tisíce šestnáct je proto skoro stejně vysoko jako úroveň roku dva tisíce osm a znamenala by víc peněz, než mají televize a rozhlas dnes. "
        . "Kompromis v penězích, tedy střed mezi reálnou úrovní roku dva tisíce osm a dva tisíce dvacet čtyři, vychází pro televizi asi na " . mld($stred[1], 1) . " a pro rozhlas na " . mld($stred[2], 1) . ". "
        . "Stát by to stálo asi o " . mld($stred[1] + $stred[2] - $prispevekCelkem, 1) . " ročně víc než vládní návrh. "
        . "A ještě jedna věc: i skutečná úroveň roku dva tisíce dvacet čtyři po dorovnání inflace by byla " . mld($s24['ct'], 1) . " pro televizi, tedy víc, než vláda navrhuje."];

    $s[] = ['inflace', 'Kolik z poplatku snědla inflace',
        "Televizní poplatek sto třicet pět korun platil od prvního ledna dva tisíce osm do dubna dva tisíce dvacet pět. Pak se zvýšil na sto padesát korun. "
        . "Aby měl v roce dva tisíce dvacet pět stejnou kupní sílu jako v roce dva tisíce osm, musel by být " . kcs($tvDleInflace) . ". "
        . "Reálná hodnota sto třiceti pěti korun klesla v cenách roku dva tisíce osm na " . kcs(135 / $cpi0825) . ". "
        . "Rozhlasový poplatek čtyřicet pět korun platil dokonce od října dva tisíce pět. Ceny od té doby vzrostly o " . proc(($cpi0525 - 1) * 100) . ", "
        . "takže stejná kupní síla by dnes znamenala " . kcs($roDleInflace) . ". Kdyby rostl jako průměrná mzda, byl by " . kcs($roDleMzdy) . "."];

    $s[] = ['prijmy', 'Poplatek ve srovnání se mzdami, minimální mzdou a důchody',
        "Od roku dva tisíce osm do roku dva tisíce dvacet pět vzrostly ceny o $infl. "
        . "Průměrná hrubá mzda byla v roce dva tisíce osm " . kcs($prumMzda[2008]) . ", v roce dva tisíce dvacet pět " . kcs($prumMzda[2025]) . ". To je nárůst o " . proc(rust($prumMzda[2008], $prumMzda[2025])) . ". "
        . "Minimální mzda byla " . kcs($minMzda[2008]) . ", dnes " . kcs($minMzda[2025]) . ". Nárůst o " . proc(rust($minMzda[2008], $minMzda[2025])) . ". "
        . "Průměrný starobní důchod byl " . kcs($duchod[2008]) . ", na konci roku dva tisíce dvacet pět " . kcs($duchod[2025]) . ". Nárůst o " . proc(rust($duchod[2008], $duchod[2025])) . ". "
        . "Televizní poplatek vzrostl jen o jedenáct procent. Kdyby rostl jako průměrná mzda, byl by " . kcs($tvDleMzdy) . ", "
        . "jako minimální mzda " . kcs($tvDleMin) . " a jako důchod " . kcs($tvDleDuchodu) . ". "
        . "Minimální mzda dělala v roce dva tisíce osm " . proc($minMzda[2008] / $prumMzda[2008] * 100) . " průměrné mzdy, dnes " . proc($minMzda[2025] / $prumMzda[2025] * 100) . ". "
        . "Oba poplatky dohromady tvořily v roce dva tisíce osm " . proc(180 / $prumMzda[2008] * 100, 1) . " průměrné mzdy, dnes jen " . proc(205 / $prumMzda[2025] * 100, 1) . ". "
        . "Domácnost tedy dává na média veřejné služby zhruba polovinu toho podílu ze svého příjmu co v roce dva tisíce osm. "
        . "Po zrušení poplatků ušetří domácnost dva tisíce čtyři sta šedesát korun ročně, stát ale z daní zaplatí přibližně " . kcs($sum / $obyvatel) . " ročně za každého obyvatele."];

    $s[] = ['kdo-plati', 'Kdo platí dnes a kdo bude platit po změně',
        "Dnes platí poplatek domácnosti, které mají jakékoli zařízení schopné příjmu, od května dva tisíce dvacet pět i mobil nebo počítač s internetem. Platí se jednou za domácnost. "
        . "Nemusí platit domácnosti s nízkým příjmem: od prvního října dva tisíce dvacet šest je hranice " . kcs($prijmovyLimit['jednotlivec']) . " čistého měsíčně pro jednotlivce. O osvobození je ale potřeba požádat a každého půl roku ho obnovit. "
        . "Dále nemusí platit lidé s těžkým postižením zraku nebo sluchu, klienti domovů pro seniory bez vlastního přijímače a cizinci bez trvalého pobytu. "
        . "Firmy dříve platily za každý přijímač, tedy i za každé autorádio ve služebním autě. Od července dva tisíce dvacet pět platí podle počtu zaměstnanců. "
        . "Firmy do pětadvaceti zaměstnanců neplatí. Od pětadvaceti zaměstnanců se platí pětinásobek, tedy " . kcs(5 * $poplatekMesic) . " měsíčně, "
        . "a největší firmy s pěti sty a více zaměstnanci platí stonásobek, tedy " . kcs(100 * $poplatekMesic) . " měsíčně. "
        . "Po změně se bude platit ze všech daní dohromady. Žádné výjimky ani neplátci už nebudou. Na Českou televizi a Český rozhlas přispěje každý, kdo platí daně, i ten, kdo je dnes osvobozený. "
        . "Nízkopříjmové domácnosti zaplatí přes daň z přidané hodnoty a spotřební daně při každém nákupu. "
        . "Průměrně to vychází asi na " . kcs($prispevekCelkem / $obyvatel) . " ročně na každého obyvatele včetně dětí."];

    $s[] = ['hospodareni', 'Hospodaření České televize',
        "Česká televize měla v roce dva tisíce osm průměrně " . mn($ct[2008][2], 0, U_ZAM) . " a v roce dva tisíce dvacet pět " . mn($ct[2025][2], 0, U_ZAM) . ", tedy prakticky stejně. "
        . "Průměrná mzda v televizi byla v roce dva tisíce osm " . kcs($ct[2008][3]) . ", v roce dva tisíce dvacet pět " . kcs($ct[2025][3]) . ". To je nárůst o " . proc(rust($ct[2008][3], $ct[2025][3])) . ". "
        . "Průměrná mzda v celé republice přitom vzrostla o " . proc(rust($prumMzda[2008], $prumMzda[2025])) . ". "
        . "Celkové výnosy televize vzrostly nominálně o " . proc(rust($ct[2008][0], $ct[2025][0])) . ", reálně ale klesly o " . proc(abs(rust($ct[2008][0], $ct[2025][0] / $cpi0825))) . ". "
        . "Na účtech měla televize na konci roku dva tisíce dvacet pět asi jednu celou osm miliardy korun. Vláda tvrdí, že rezervy stačí, Rada České televize to odmítá."];

    $s[] = ['rozhlas', 'Hospodaření Českého rozhlasu',
        "Výnos Českého rozhlasu z poplatků se v letech dva tisíce osm až dva tisíce dvacet čtyři pohyboval kolem dvou miliard korun. "
        . "V roce dva tisíce osm to bylo " . mld($cro[2008][1] / 1000) . ", v roce dva tisíce dvacet čtyři " . mld($cro[2024][1] / 1000, 3) . " a po zvýšení poplatku v roce dva tisíce dvacet pět " . mld($cro[2025][1] / 1000) . ". "
        . "Vláda navrhuje " . mld($navrh['cro'], 3) . ", tedy přesně úroveň roku dva tisíce dvacet čtyři. Reálně je to " . proc($croNavrhPodil) . " hodnoty z roku dva tisíce osm. "
        . "Oproti plánu na rok dva tisíce dvacet šest chybí " . mil($plan2026['cro_poplatky'] - $navrh['cro']) . ". Vedení rozhlasu mluví o odchodu sto padesáti až dvou set lidí. "
        . "Rozhlas přitom už mezi lety dva tisíce osm a dva tisíce dvacet pět snížil počet zaměstnanců asi o osm procent. V roce dva tisíce osm jich měl " . slovy((int)round($cro[2008][2])) . ", v roce dva tisíce dvacet pět " . slovy((int)round($cro[2025][2])) . ". "
        . "Průměrná mzda v rozhlase vzrostla o " . proc(rust($cro[2008][3], $cro[2025][3])) . " na " . kcs($cro[2025][3]) . ", což je dnes zhruba průměrná mzda v Česku. "
        . "Reklamní a obchodní příjmy rozhlasu byly v roce dva tisíce osm " . mil($croKomercni[2008] / 1000) . ", v roce dva tisíce dvacet pět už jen " . mil($croKomercni[2025] / 1000) . "."];

    $s[] = ['srovnani', 'Česká televize a Český rozhlas vedle sebe',
        "Televizní poplatek vzrostl ze sto třiceti pěti na sto padesát korun, tedy o jedenáct procent. Rozhlasový ze čtyřiceti pěti na padesát pět korun, tedy o dvacet dva procent. "
        . "Navržený příspěvek je u televize o " . proc(abs($navrhVs2026), 1) . " a u rozhlasu o " . proc(abs($cro2026Vs), 1) . " nižší než letošní plán poplatků. "
        . "Reálně bude mít v roce $R hodnotu " . proc($navrhPodil) . " u televize a " . proc($croNavrhPodil) . " u rozhlasu oproti výnosu z poplatků v roce dva tisíce osm. "
        . "Televize dostane " . proc($navrh['ct'] / $sum * 100, 1) . " celkového příspěvku, rozhlas " . proc($navrh['cro'] / $sum * 100, 1) . ". "
        . "Na obyvatele a rok to dělá " . kcs($navrh['ct'] / $obyvatel) . " pro televizi a " . kcs($navrh['cro'] / $obyvatel) . " pro rozhlas. "
        . "Konec výjimky z DPH vezme televizi dalších " . mil($dph['ct']) . " a rozhlasu " . mil($dph['cro']) . " ročně. "
        . "Televize ohlásila možné propouštění tří set až pěti set lidí, rozhlas sto padesáti až dvou set."];

    $posl = end($dluhSim);
    $s[] = ['dluh', 'Stát jede na dluh: kolik stojí úroky',
        "Poplatky dnes stojí mimo státní rozpočet. Rozpočet je ale ve schodku: vládní návrh na rok dva tisíce dvacet sedm počítá se schodkem " . mn($dluh['schodek2027'], 0, U_MLD) . " korun. "
        . "Každý nový výdaj tak schodek zvětší, pokud vláda jinde neušetří nebo nezvýší daně. Stát si tedy bude " . mld($prispevekCelkem) . " ročně nejspíš půjčovat. "
        . "Státní dluh byl ke třicátému září dva tisíce dvacet šest " . mn($dluh['stav'], 0, U_MLD) . " korun. Nové státní dluhopisy se letos prodávaly v průměru s výnosem " . proc($dluh['vynosNovych'], 1) . ". "
        . "Pokud se celý příspěvek financuje novým dluhem za tuto sazbu, zaplatí stát v prvním roce na úrocích asi " . mil($dluhSim[0]['urok']) . ". "
        . "Dluh navíc ale každý rok roste, takže v desátém roce už úroky dělají asi " . mld($posl['urok']) . " ročně. "
        . "Za deset let to dohromady dělá asi " . mld($posl['urokyCelkem'], 1) . " jen na úrocích a dluh se zvýší asi o " . mn($posl['dluh'] / 1000, 0, U_MLD) . " korun. "
        . "Při průměrném nákladu celého státního dluhu kolem tří procent by úroky byly asi o čtyřicet procent nižší."];

    $s[] = ['valorizace', 'Valorizace příspěvku',
        "Podle návrhu se příspěvek zvýší o deset procent, až součet ročních měr inflace počítaný od roku dva tisíce dvacet šest dosáhne deseti procentních bodů. Mezitím zůstává částka stejná. "
        . "Při inflaci kolem dvou a půl procenta ročně přijde první navýšení v roce dva tisíce třicet. Do té doby příspěvek reálně ztrácí. "
        . "Na stránce si můžete posuvníkem nastavit vlastní předpoklad inflace a uvidíte, jak se příspěvek vyvíjí."];

    $s[] = ['argumenty', 'Hlavní argumenty obou stran',
        "Argumenty pro změnu, tedy vlády a koalice hnutí ANO, SPD a Motoristů: domácnost ušetří dva tisíce čtyři sta šedesát korun ročně a odpadne evidence a vymáhání poplatků. "
        . "Financování se podle vlády vrací na úroveň roku dva tisíce dvacet čtyři. Média budou samostatnou kapitolou rozpočtu a Nejvyšší kontrolní úřad bude kontrolovat i jejich hospodaření s majetkem. "
        . "Podobný model mají i jiné evropské země. Mění se podle vlády jen způsob financování, ne nezávislost ani obsah vysílání. "
        . "Argumenty proti, tedy opozice, obou institucí a novinářských organizací: o penězích by každý rok rozhodovala vládní většina, což média vystavuje politickému tlaku. "
        . "Úroveň roku dva tisíce dvacet čtyři je nominálně úroveň roku dva tisíce osm, reálně jde zhruba o " . proc(100 - $navrhPodil) . " méně. "
        . "Valorizace přichází pozdě a skokově. Ve stejném roce končí výjimka z DPH. Hrozí propouštění a méně vlastní tvorby. "
        . "Kritizován je i postup ve Sněmovně a opozice zvažuje Ústavní soud. Proti se vyslovily mimo jiné Rada České televize, Senát, Syndikát novinářů, Evropská vysílací unie a Česká konference rektorů."];

    return array_map(fn($x) => ['id' => $x[0], 'nazev' => $x[1], 'text' => $x[2]], $s);
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $json = json_encode(['hlas' => 'cs-CZ-VlastaNeural', 'sekce' => narace()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    isset($argv[1]) ? file_put_contents($argv[1], $json) : print($json);
}
