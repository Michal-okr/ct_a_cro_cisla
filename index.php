<?php
declare(strict_types=1);

require __DIR__ . '/data.php';

/* Audio přehrávač – zobrazí se jen tehdy, když MP3 existuje (vygeneruje ho build.ps1 -Audio). */
function player(string $id, string $label = 'Poslechnout sekci'): string
{
    $f = "audio/$id.mp3";
    if (!is_file(__DIR__ . "/$f") || filesize(__DIR__ . "/$f") === 0) return '';
    return "<div class='player'><span>🔊 " . h($label) . "</span><audio controls preload='none' src='$f'></audio></div>";
}

/* SVG čárový graf */
function lineChart(array $data, float $minY, float $maxY, float $step, string $id, string $title): string
{
    $W = 760; $H = 320; $pl = 46; $pr = 16; $pt = 16; $pb = 34;
    $x0 = 2008; $x1 = end($data)['rok'];
    $x = fn($r) => $pl + ($r - $x0) / ($x1 - $x0) * ($W - $pl - $pr);
    $y = fn($v) => $pt + (1 - ($v - $minY) / ($maxY - $minY)) * ($H - $pt - $pb);
    $o = "<svg viewBox='0 0 $W $H' class='chart' role='img' aria-labelledby='$id'><title id='$id'>" . h($title) . "</title>";
    for ($v = $minY; $v <= $maxY + 1e-9; $v += $step) {
        $yy = round($y($v), 1);
        $o .= "<line x1='$pl' x2='" . ($W - $pr) . "' y1='$yy' y2='$yy' class='grid'/>";
        $o .= "<text x='" . ($pl - 6) . "' y='" . ($yy + 4) . "' class='ax' text-anchor='end'>" . number_format($v, $step < 0.5 ? 2 : 1, ',', '') . "</text>";
    }
    foreach ([2008, 2012, 2016, 2020, 2024, $x1] as $r) {
        $o .= "<text x='" . round($x($r), 1) . "' y='" . ($H - 10) . "' class='ax' text-anchor='middle'>$r</text>";
    }
    // mezera 2025 → první rok návrhu čárkovaně
    foreach (['nom' => 's1', 'real' => 's2'] as $k => $cls) {
        $pts = []; $last = null; $nav = null;
        foreach ($data as $d) {
            if (!empty($d['navrh'])) { $nav = $d; continue; }
            $pts[] = round($x($d['rok']), 1) . ',' . round($y($d[$k]), 1);
            $last = $d;
        }
        $o .= "<polyline points='" . implode(' ', $pts) . "' class='ln $cls'/>";
        $o .= "<line x1='" . round($x($last['rok']), 1) . "' y1='" . round($y($last[$k]), 1) . "' x2='" . round($x($nav['rok']), 1) . "' y2='" . round($y($nav[$k]), 1) . "' class='ln $cls dash'/>";
        foreach ($data as $d) {
            $lab = ($k === 'nom' ? 'Nominálně' : 'V cenách 2008') . ': ' . number_format($d[$k], 2, ',', ' ') . ' mld. Kč' . (!empty($d['navrh']) ? ' (návrh vlády)' : '');
            $o .= "<circle cx='" . round($x($d['rok']), 1) . "' cy='" . round($y($d[$k]), 1) . "' r='" . (!empty($d['navrh']) ? 5 : 3) . "' class='pt $cls' data-tip='" . h($d['rok'] . ' – ' . $lab) . "'/>";
        }
    }
    $o .= "<text x='" . round($x($x1), 1) . "' y='" . round($y($data[count($data) - 1]['nom']) + 24, 1) . "' class='lbl' text-anchor='end'>návrh $x1</text>";
    return $o . '</svg>';
}

/* SVG pruhový graf růstu */
function barChart(array $rows): string
{
    $W = 760; $rowH = 34; $pl = 230; $pr = 70; $pt = 6;
    $H = $pt + count($rows) * $rowH + 6;
    $max = 170; $min = -15;
    $x = fn($v) => $pl + ($v - $min) / ($max - $min) * ($W - $pl - $pr);
    $o = "<svg viewBox='0 0 $W $H' class='chart' role='img' aria-labelledby='g2t'><title id='g2t'>Růst v procentech mezi lety 2008 a 2025</title>";
    $zx = round($x(0), 1);
    foreach ($rows as $i => [$label, $v, $kind]) {
        $yy = $pt + $i * $rowH;
        $x1 = min($x(0), $x($v)); $w = max(2, abs($x($v) - $x(0)));
        $cls = ['ref' => 's1f', 'ct' => 's2f', 'cro' => 's3f'][$kind];
        $o .= "<text x='" . ($pl - 10) . "' y='" . ($yy + 21) . "' class='lbl' text-anchor='end'>" . h($label) . "</text>";
        $o .= "<rect x='" . round($x1, 1) . "' y='" . ($yy + 7) . "' width='" . round($w, 1) . "' height='20' rx='4' class='$cls' data-tip='" . h($label . ': ' . pct($v)) . "'/>";
        $o .= "<text x='" . round(max($x($v), $x(0)) + 6, 1) . "' y='" . ($yy + 21) . "' class='val'>" . pct($v, 0) . "</text>";
    }
    $o .= "<line x1='$zx' x2='$zx' y1='0' y2='$H' class='axis'/>";
    return $o . '</svg>';
}
?><!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Poplatky ČT a ČRo v číslech</title>
<meta name="description" content="Přehled čísel a argumentů ke zrušení televizních a rozhlasových poplatků (sněmovní tisk 231, 2026): inflace, mzdy, důchody, hospodaření ČT.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
<style>
:root{
  color-scheme:light;
  --bg:#f6f5f1; --surface:#fcfcfb; --surface-2:#efeee9; --border:#dddcd5;
  --text:#0b0b0b; --text-2:#52514e; --muted:#7a7974;
  --s1:#2a78d6; --s2:#eb6834; --s3:#1baf7a; --accent:#2a78d6;
  --good:#1a7f37; --bad:#c2332f; --warn-bg:#fff4dc; --warn-bd:#e5b74a;
}
@media (prefers-color-scheme:dark){
  :root:not([data-theme="light"]){
    color-scheme:dark;
    --bg:#121211; --surface:#1a1a19; --surface-2:#242422; --border:#363633;
    --text:#f0efec; --text-2:#c3c2b7; --muted:#8f8e86;
    --s1:#3987e5; --s2:#d95926; --s3:#199e70; --accent:#5b9cec;
    --good:#4cc26a; --bad:#ec6a66; --warn-bg:#2e2614; --warn-bd:#8a6a1e;
  }
}
:root[data-theme="dark"]{
  color-scheme:dark;
  --bg:#121211; --surface:#1a1a19; --surface-2:#242422; --border:#363633;
  --text:#f0efec; --text-2:#c3c2b7; --muted:#8f8e86;
  --s1:#3987e5; --s2:#d95926; --s3:#199e70; --accent:#5b9cec;
  --good:#4cc26a; --bad:#ec6a66; --warn-bg:#2e2614; --warn-bd:#8a6a1e;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:17px/1.6 "Source Sans 3",system-ui,sans-serif}
.wrap{max-width:980px;margin:0 auto;padding:0 16px}
header.hero{padding:56px 0 28px}
.kicker{font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.08em;font-size:13px}
h1,h2,h3{font-family:"Source Serif 4",Georgia,serif;line-height:1.2;margin:0 0 .5em}
h1{font-size:clamp(30px,5vw,46px)}
h2{font-size:clamp(23px,3.4vw,30px);margin-top:0}
h3{font-size:19px}
p{margin:0 0 1em}
.lead{font-size:19px;color:var(--text-2);max-width:760px}
section{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px;margin:0 0 24px}
.note{background:var(--warn-bg);border:1px solid var(--warn-bd);border-radius:10px;padding:14px 18px;margin:18px 0}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin:20px 0 4px}
.tile{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px 18px}
.tile .n{font-family:"Source Serif 4",serif;font-size:32px;font-weight:700;line-height:1.1}
.tile .d{color:var(--text-2);font-size:14.5px;margin-top:6px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media (max-width:720px){.two{grid-template-columns:1fr} section{padding:20px}}
.side{border-radius:12px;padding:18px 20px;background:var(--surface-2)}
.side h3{display:flex;gap:8px;align-items:center}
.side ul{margin:0;padding-left:20px}
.side li{margin-bottom:.5em}
.tw{overflow-x:auto;margin:10px 0}
table{border-collapse:collapse;width:100%;font-size:15.5px;font-variant-numeric:tabular-nums}
th,td{padding:7px 10px;border-bottom:1px solid var(--border);text-align:right;white-space:nowrap}
th:first-child,td:first-child{text-align:left}
thead th{font-weight:600;color:var(--text-2);font-size:13.5px;text-transform:uppercase;letter-spacing:.03em}
tr.hl td{background:var(--surface-2);font-weight:600}
table.cmp td{white-space:normal;text-align:left;min-width:150px}
.pos{color:var(--good)} .neg{color:var(--bad)}
.muted{color:var(--muted);font-size:14.5px}
.chart{width:100%;height:auto;display:block}
.chart .grid{stroke:var(--border);stroke-width:1}
.chart .axis{stroke:var(--muted);stroke-width:1}
.chart .ax{fill:var(--muted);font-size:12px}
.chart .lbl{fill:var(--text-2);font-size:13.5px}
.chart .val{fill:var(--text);font-size:13.5px;font-weight:600}
.chart .ln{fill:none;stroke-width:2.2;stroke-linejoin:round}
.chart .ln.s1{stroke:var(--s1)} .chart .ln.s2{stroke:var(--s2)}
.chart .dash{stroke-dasharray:5 5}
.chart .pt{stroke:var(--surface);stroke-width:2;cursor:pointer}
.chart .pt.s1{fill:var(--s1)} .chart .pt.s2{fill:var(--s2)}
.chart .s1f{fill:var(--s1)} .chart .s2f{fill:var(--s2)} .chart .s3f{fill:var(--s3)}
.chart rect{cursor:pointer}
.legend{display:flex;gap:18px;flex-wrap:wrap;font-size:14.5px;color:var(--text-2);margin:6px 0 10px}
.legend i{display:inline-block;width:14px;height:14px;border-radius:3px;vertical-align:-2px;margin-right:6px}
#tip{position:fixed;pointer-events:none;background:var(--text);color:var(--bg);padding:6px 10px;border-radius:6px;font-size:13.5px;opacity:0;transition:opacity .1s;z-index:9;max-width:280px}
.calc{display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin:8px 0 14px}
.calc input[type=range]{width:220px}
.calc output{font-weight:700;font-size:20px;min-width:70px}
a{color:var(--accent)}
.src li{margin-bottom:.35em;font-size:15px}
footer{padding:10px 0 50px;color:var(--muted);font-size:14px}
.player{display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px;margin:4px 0 16px;color:var(--text-2);font-size:14.5px}
.player audio{height:36px;width:100%;max-width:420px}
.toggle{position:absolute;top:14px;right:16px;background:var(--surface);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:6px 10px;cursor:pointer;font:inherit;font-size:14px}
</style>
</head>
<body>
<button class="toggle" id="themeBtn" type="button" aria-label="Přepnout světlý/tmavý režim">◐ Režim</button>
<div class="wrap">

<header class="hero">
  <div class="kicker">Rozhlasové a televizní poplatky · stav k 25. 10. 2026</div>
  <h1>Konec poplatků za ČT a ČRo: co říkají čísla</h1>
  <p class="lead">Vláda chce místo poplatků posílat České televizi a Českému rozhlasu peníze ze státního rozpočtu.
  Koalice říká, že tím vrací financování na úroveň roku 2024. Opozice a obě instituce odpovídají, že úroveň 2024 je ve skutečnosti úroveň roku 2008, protože se poplatek 17 let neměnil.
  Tahle stránka dává obě tvrzení vedle sebe, přepočítává je inflací, mzdami, minimální mzdou a důchody a srovnává ČT s ČRo.</p>

  <?= player('cela-stranka', 'Poslechnout celou stránku') ?>
  <?= player('uvod', 'Poslechnout jen úvod') ?>
  <div class="note"><strong>Pozor na stav projednávání.</strong> V sobotu 24. 10. 2026 ráno (hlasování ve 4:36) Sněmovna zákon <em>nepřijala</em>, jen ho poslala do 2. čtení.
  Návrhy opozice na zamítnutí a na vrácení vládě podpořilo shodně 81 ze 177 přítomných poslanců (ODS, STAN, Piráti, KDU-ČSL, TOP 09). Garančním výborem je výbor pro média a kulturu.
  Pak zbývá 2. a 3. čtení, Senát (může zákon vrátit nebo zamítnout) a prezident (může vetovat). Koalice pak může přehlasovat 101 hlasy.<br><strong>Realistický start je až rok <?= $prvniRok ?>.</strong> Vláda chtěla začít 1. 1. 2027. Na to už v říjnu 2026 není čas a rozpočet na rok 2027 s převodem nepočítá. Stránka proto všechny výpočty dělá pro první rok <?= $prvniRok ?>. Ve stejném roce navíc končí výjimka z DPH (viz níže).</div>

  <div class="tiles">
    <div class="tile"><div class="n"><?= kc($navrh['ct'] / 1000, 2) ?> mld.</div><div class="d">navržený příspěvek pro ČT od roku <?= $prvniRok ?> (letos plán poplatků <?= kc($plan2026['ct_poplatky'] / 1000, 2) ?> mld., tj. <?= pct($navrhVs2026) ?>)</div></div>
    <div class="tile"><div class="n"><?= kc($navrh['cro'] / 1000, 3) ?> mld.</div><div class="d">navržený příspěvek pro ČRo od roku <?= $prvniRok ?> (letos poplatky ≈ <?= kc($plan2026['cro_poplatky'] / 1000, 1) ?> mld., tj. <?= pct($cro2026Vs) ?>)</div></div>
    <div class="tile"><div class="n"><?= pct(($cpi0825 - 1) * 100, 0) ?></div><div class="d">nárůst cenové hladiny 2008 → 2025 (ČSÚ, průměrné roční míry inflace)</div></div>
    <div class="tile"><div class="n"><?= kc($navrhPodil, 0) ?> %</div><div class="d">reálná hodnota navržených <?= kc($navrh['ct'] / 1000, 2) ?> mld. pro ČT oproti výnosu poplatků v roce 2008 (start <?= $prvniRok ?>, inflace 2 % ročně v letech 2026–<?= $prvniRok ?>)</div></div>
  </div>
</header>

<section id="start-dph">
  <h2>Realisticky: start v roce <?= $prvniRok ?> a konec výjimky z DPH</h2>
  <?= player('start-dph') ?>
  <div class="two">
    <div>
      <h3>Proč ne 2027</h3>
      <ul>
        <li>Po 1. čtení (24. 10.) zbývá výbor (lhůta 60 dnů, zkrátit ji lze nejvýš na 20), 2. čtení, 3. čtení (nejdříve 14 dnů po 2. čtení), Senát (30 dnů) a prezident (15 dnů). Při vetu se znovu hlasuje ve Sněmovně.</li>
        <li>Senát má opoziční většinu a už v usnesení vyzval vládu, aby od rušení poplatků upustila. Lze tedy čekat vrácení nebo zamítnutí a následné přehlasování.</li>
        <li>Ministr kultury Klempíř (září 2026): 1. 1. 2027 je „jen obtížně dosažitelný“, v úvahu připadá 1. 7. 2027 nebo 1. 1. 2028.</li>
        <li>Ministryně financí Schillerová: rozpočet na rok 2027 převod nepočítá, ponechává jen rezervu pro případ účinnosti během roku.</li>
        <li><strong>V roce 2027 se tedy poplatky (150 + 55 Kč) nejspíš platí dál.</strong> ČT ve svém dlouhodobém plánu počítá s výnosem z poplatků <?= kc($ctPlanPoplatky[2027]) ?> mil. Kč (2027) a <?= kc($ctPlanPoplatky[2028]) ?> mil. Kč (2028).</li>
        <li>Valorizace se počítá od roku 2026. Do startu v roce <?= $prvniRok ?> se tak „nasbírají“ asi 4 procentní body inflace a do 10 bodů zbývá ještě zhruba 3 roky.</li>
      </ul>
    </div>
    <div>
      <h3>Co je výjimka z DPH</h3>
      <ul>
        <li>Od roku 2017 umožňuje zákon o DPH České televizi a Českému rozhlasu uplatňovat <strong>odpočet DPH na vstupu ve stejném rozsahu jako komerční stanice</strong>. Týká se to daně z nakoupeného zboží a služeb na výrobu pořadů a provoz.</li>
        <li>Bez výjimky se vysílání financované poplatkem nebo dotací nebere jako ekonomická činnost. Médium pak DPH z nákupů odečíst nemůže nebo jen částečně a tato daň se pro něj stává nákladem. Před rokem 2017 se kvůli tomu ČT i ČRo soudily s finanční správou.</li>
        <li>Výjimka byla nejdřív na 4 roky, v roce 2021 se prodloužila do roku 2024 a pak <strong>do 31. 12. 2027</strong>. Vládní návrh (tisk 231) ji neprodlužuje.</li>
        <li>Výpadek od 1. 1. 2028: <strong>ČT asi <?= kc($dph['ct']) ?> mil. Kč ročně, ČRo asi <?= kc($dph['cro']) ?> mil. Kč ročně</strong>, dohromady asi <?= kc($dph['ct'] + $dph['cro']) ?> mil.</li>
      </ul>
    </div>
  </div>

  <h3>První rok státního financování (<?= $prvniRok ?>): kolik reálně chybí</h3>
  <?php
  $sc = [
    ['ČT – oproti plánu poplatků 2026', $plan2026['ct_poplatky'], $navrh['ct'], $dph['ct'], $plan2026['ct_rozpocet']],
    ['ČT – oproti vlastnímu plánu poplatků 2028', $ctPlanPoplatky[2028], $navrh['ct'], $dph['ct'], $plan2026['ct_rozpocet']],
    ['ČRo – oproti plánu poplatků 2026', $plan2026['cro_poplatky'], $navrh['cro'], $dph['cro'], $plan2026['cro_rozpocet']],
  ];
  ?>
  <div class="tw"><table>
    <thead><tr><th>Scénář (mil. Kč)</th><th>Poplatky</th><th>Příspěvek</th><th>Rozdíl</th><th>DPH</th><th>Celkem</th><th>% rozp. 2026</th></tr></thead>
    <tbody>
    <?php foreach ($sc as [$n, $pl, $pr, $d, $rozp]): $diff = $pr - $pl; $tot = $diff - $d; ?>
      <tr><td><?= h($n) ?></td><td><?= kc($pl) ?></td><td><?= kc($pr) ?></td><td class="neg"><?= kc($diff) ?></td><td class="neg">−<?= kc($d) ?></td><td class="neg"><strong><?= kc($tot) ?></strong></td><td class="neg"><?= pct($tot / $rozp * 100) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="muted">Médiář s ČT a ČRo počítají pokles rozpočtu mezi lety 2026 a 2028 shodně na zhruba 20 %: ČT 1,7 mld. (1,3 mld. příspěvek a 0,42 mld. DPH), ČRo 541 mil. (411 mil. a 130 mil.).
  Reálně, v cenách roku 2008, bude mít příspěvek v roce <?= $prvniRok ?> hodnotu jen <?= kc($navrhPodil, 0) ?> % (ČT) a <?= kc($croNavrhPodil, 0) ?> % (ČRo) výnosu z poplatků v roce 2008, a to ještě před odečtením DPH.</p>

  <h3>Jak to dopadne na státní rozpočet</h3>
  <?php $vyd = $navrh['ct'] + $navrh['cro']; $dphSum = $dph['ct'] + $dph['cro']; ?>
  <div class="tiles">
    <div class="tile"><div class="n"><?= kc($vyd / 1000, 2) ?> mld.</div><div class="d">nový výdaj státního rozpočtu ročně od <?= $prvniRok ?> (ČT + ČRo). Dnes ho platí domácnosti a firmy mimo rozpočet.</div></div>
    <div class="tile"><div class="n">+<?= kc($dphSum) ?> mil.</div><div class="d">o tolik víc DPH státu zůstane, protože ČT a ČRo si ji po konci výjimky nebudou moct odečíst. Pro média je to náklad, pro rozpočet příjem.</div></div>
    <div class="tile"><div class="n">≈ <?= kc(($vyd - $dphSum) / 1000, 2) ?> mld.</div><div class="d">čistá zátěž rozpočtu po započtení DPH, plus valorizace o 10 % zhruba každé 4–5 let</div></div>
    <div class="tile"><div class="n">−<?= kc($postaVypadek) ?> mil.</div><div class="d">ročně přijde státní Česká pošta o odměnu za výběr poplatků (připomínka ministerstva vnitra)</div></div>
  </div>
  <p>Konec výjimky z DPH tedy <strong>zvyšuje náklady médií, ne výdaje státu</strong>. Stát na DPH od ČT a ČRo naopak vybere víc.
  Kdyby chtěl stát médiím výpadek nahradit, musel by příspěvek zvýšit asi o <?= kc($dphSum) ?> mil. Ty by se mu ale z velké části vrátily zpátky na DPH. Je to tedy hlavně účetní přesun peněz mezi státem a médii.
  Pro ČT a ČRo to ale znamená, že od roku <?= $prvniRok ?> jim souběžně ubudou peníze ze dvou stran: nižší příspěvek a dražší nákupy.
  Ministerstvo financí navíc v připomínkách upozornilo, že návrh neurčuje, z jaké kapitoly rozpočtu se bude platit.</p>
</section>

<section id="spor">
  <h2>„Návrat na rok 2024“, nebo „návrat na rok 2008“?</h2>
  <?= player('spor') ?>
  <p>Obě strany mluví o stejném čísle. Výnos ČT z poplatků byl v roce 2024 <strong><?= kc($ct[2024][1] / 1e6, 2) ?> mld. Kč</strong>.
  V roce 2008 to bylo <strong><?= kc($ct[2008][1] / 1e6, 2) ?> mld. Kč</strong>, tedy prakticky stejně. Vláda navrhuje jako pevnou roční částku (původně od roku 2027, reálně nejdříve od <?= $prvniRok ?>) <strong><?= kc($navrh['ct'] / 1000, 2) ?> mld. Kč</strong>.
  Nominálně je to tedy stejná částka jako v roce 2008 i 2024. Reálně je to ale něco jiného.</p>
  <div class="tw"><table>
    <thead><tr><th>ČT – peníze z poplatků / příspěvku</th><th>Nominálně</th><th>V cenách roku 2008</th><th>Reálně vs. 2008</th></tr></thead>
    <tbody>
    <?php foreach ([2008, 2024, 2025] as $r): $real = $ct[$r][1] / 1000 / cpi($inflace, 2008, $r); ?>
      <tr><td>Rok <?= $r ?> (skutečnost)</td><td><?= kc($ct[$r][1] / 1000) ?> mil.</td><td><?= kc($real) ?> mil.</td><td class="<?= $r === 2008 ? '' : 'neg' ?>"><?= $r === 2008 ? '100 %' : pct(rust($ctPopl2008, $real)) ?></td></tr>
    <?php endforeach; ?>
      <tr class="hl"><td>Rok <?= $prvniRok ?> (návrh vlády)</td><td><?= kc($navrh['ct']) ?> mil.</td><td><?= kc($navrhReal08) ?> mil.</td><td class="neg"><?= pct(rust($ctPopl2008, $navrhReal08)) ?></td></tr>
    </tbody>
  </table></div>
  <p class="muted">Ceny 2008 jsou přepočtené řetězením průměrných ročních měr inflace ČSÚ. Pro roky 2026–<?= $prvniRok ?> je předpoklad <?= kc($inflacePredpoklad[2026], 1) ?> % (12měsíční průměr ČSÚ k srpnu 2026).</p>

  <h3>Výnos ČT z poplatků 2008–2025 a návrh <?= $prvniRok ?> (mld. Kč)</h3>
  <div class="legend"><span><i style="background:var(--s1)"></i>nominálně</span><span><i style="background:var(--s2)"></i>v cenách roku 2008</span><span>čárkovaně: přechod na návrh <?= $prvniRok ?></span></div>
  <?= lineChart($serie, 3.0, 6.5, 0.5, 'g1t', 'Výnos ČT z poplatků 2008–2025 a návrh ' . $prvniRok . ', nominálně a v cenách roku 2008') ?>
</section>

<section id="kompromis">
  <h2>Kompromis: sejít se uprostřed, u roku 2016?</h2>
  <?= player('kompromis') ?>
  <p>Mezi rokem 2008, na který se odvolává opozice, a rokem 2024, na který se odvolává vláda, je 16 let. Polovina je rok 2016.
  Tabulka ukazuje, kolik by ČT a ČRo musely dostat v prvním roce státního financování (<?= $prvniRok ?>), aby měly <strong>stejnou kupní sílu</strong> jako jejich výnos z poplatků v daném roce.</p>
  <?php $uroky10 = end($dluhSim)['urokyCelkem']; ?>
  <div class="tw"><table>
    <thead><tr><th>Scénář (mil. Kč, rok <?= $prvniRok ?>)</th><th>ČT</th><th>ČRo</th><th>Celkem</th><th>Navíc oproti návrhu</th><th>Úroky z dluhu za 10 let</th></tr></thead>
    <tbody>
    <?php foreach ($scenare as $i => [$n, $a, $b]): $t = $a + $b; ?>
      <tr<?= $i === 0 || str_contains($n, '2016') ? ' class="hl"' : '' ?>><td><?= h($n) ?></td><td><?= kc($a) ?></td><td><?= kc($b) ?></td><td><?= kc($t) ?></td>
        <td><?= $i === 0 ? '—' : '+' . kc($t - $prispevekCelkem) ?></td><td><?= kc($uroky10 * $t / $prispevekCelkem) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="tiles">
    <div class="tile"><div class="n"><?= kc($scenarRealne(2016)['ct'] / 1000, 1) ?> mld.</div><div class="d">by potřebovala ČT v roce <?= $prvniRok ?>, aby měla kupní sílu roku 2016 (návrh: <?= kc($navrh['ct'] / 1000, 2) ?> mld.)</div></div>
    <div class="tile"><div class="n"><?= kc($scenarRealne(2016)['cro'] / 1000, 1) ?> mld.</div><div class="d">totéž pro ČRo (návrh: <?= kc($navrh['cro'] / 1000, 3) ?> mld.)</div></div>
    <div class="tile"><div class="n"><?= kc($poplatekReal(2016)) ?> Kč</div><div class="d">tolik by dnes odpovídalo měsíčním poplatkům 135 + 45 Kč v cenách roku 2016 (dnes se platí <?= $poplatekMesic ?> Kč, v cenách roku 2008 by to bylo <?= kc($poplatekReal(2008)) ?> Kč)</div></div>
    <div class="tile"><div class="n"><?= kc(($scenarRealne(2016)['index'] - 1) * 100, 0) ?> %</div><div class="d">nárůst cen mezi lety 2016 a <?= $prvniRok ?> (z toho největší část v letech 2022–2023)</div></div>
  </div>
  <p><strong>Proč „střed“ nevychází uprostřed:</strong> inflace nebyla rozložená rovnoměrně. V letech 2009–2016 byla nízká, ceny vzrostly jen asi o <?= kc((cpi($inflace, 2008, 2016) - 1) * 100, 0) ?> %.
  Většina zdražení přišla až v letech 2021–2023. Reálná úroveň roku 2016 je proto skoro stejně vysoko jako úroveň roku 2008 a o hodně výš než dnešní stav po zvýšení poplatků.
  „Rok 2016“ by tak znamenal víc peněz, než mají ČT a ČRo dnes. Kompromis v penězích, tedy střed mezi reálnou úrovní 2008 a 2024, vychází pro ČT na <?= kc($scenare[3][1]) ?> mil., tedy ještě nad jejím vlastním plánem poplatků
  (<?= kc($ctPlanPoplatky[2028]) ?> mil. na rok 2028), a stát by stál asi o <?= kc(($scenare[3][1] + $scenare[3][2] - $prispevekCelkem) / 1000, 1) ?> mld. ročně víc než vládní návrh.
  Pozor: i „úroveň 2024 reálně“ je vyšší než vládní návrh, protože návrh inflaci za roky 2025–<?= $prvniRok ?> nedorovnává.</p>
  <p class="muted">Výpočet: výnos z poplatků v daném roce × růst cen do roku <?= $prvniRok ?> (ČSÚ, pro roky 2026–<?= $prvniRok ?> odhad 2 % ročně). Úroky jsou přepočtené z modelu v sekci o dluhu (<?= kc($dluh['vynosNovych'], 1) ?> %, 10 let). Konec výjimky z DPH v tabulce není.</p>
</section>

<section id="inflace">
  <h2>Kolik z poplatku „snědla“ inflace</h2>
  <?= player('inflace') ?>
  <div class="two">
    <div>
      <h3>Televizní poplatek</h3>
      <p><?= $tvPoplatek['stary'] ?> Kč platilo od <?= $tvPoplatek['od'] ?> do dubna 2025. Pak se poplatek zvedl na <?= $tvPoplatek['novy'] ?> Kč.</p>
      <ul>
        <li>Aby měl v roce 2025 stejnou kupní sílu jako v roce 2008, musel by být <strong><?= kc($tvDleInflace) ?> Kč</strong>.</li>
        <li>Reálná hodnota 135 Kč klesla do roku 2025 na <strong><?= kc(135 / $cpi0825) ?> Kč</strong> v cenách 2008 (<?= pct(rust(1, 1 / $cpi0825), 0) ?>).</li>
        <li>Zvýšení na 150 Kč (+11 %) z toho vrátilo jen část: 150 Kč odpovídá <?= kc(150 / $cpi0825) ?> Kč roku 2008.</li>
      </ul>
    </div>
    <div>
      <h3>Rozhlasový poplatek</h3>
      <p><?= $roPoplatek['stary'] ?> Kč platilo od <?= $roPoplatek['od'] ?> do dubna 2025. Pak se poplatek zvedl na <?= $roPoplatek['novy'] ?> Kč.</p>
      <ul>
        <li>Ceny od roku 2005 do roku 2025 vzrostly o <strong><?= pct(($cpi0525 - 1) * 100, 0) ?></strong>.</li>
        <li>Stejná kupní síla jako v roce 2005 by znamenala <strong><?= kc($roDleInflace) ?> Kč</strong>.</li>
        <li>Kdyby poplatek rostl jako průměrná mzda od roku 2005, byl by <strong><?= kc($roDleMzdy) ?> Kč</strong>.</li>
      </ul>
    </div>
  </div>
</section>

<section id="prijmy">
  <h2>Poplatek vs. mzdy, minimální mzda a důchody</h2>
  <?= player('prijmy') ?>
  <p>Inflace ukazuje, kolik poplatek ztratil na kupní síle. Druhé měřítko je, jak velkou část příjmu domácnost na poplatek dává. Průměrná mzda, minimální mzda i důchody rostly od roku 2008 mnohem rychleji než ceny i poplatek.</p>
  <div class="tw"><table>
    <thead><tr><th>Ukazatel (Kč/měsíc)</th><th>2008</th><th>2025</th><th>Růst</th><th>TV poplatek 135 Kč, kdyby rostl stejně</th></tr></thead>
    <tbody>
      <tr><td>Cenová hladina (CPI)</td><td>100</td><td><?= kc($cpi0825 * 100, 1) ?></td><td><?= pct(($cpi0825 - 1) * 100) ?></td><td><?= kc($tvDleInflace) ?> Kč</td></tr>
      <tr><td>Průměrná hrubá mzda</td><td><?= kc($prumMzda[2008]) ?></td><td><?= kc($prumMzda[2025]) ?></td><td><?= pct(rust($prumMzda[2008], $prumMzda[2025])) ?></td><td><?= kc($tvDleMzdy) ?> Kč</td></tr>
      <tr><td>Minimální mzda</td><td><?= kc($minMzda[2008]) ?></td><td><?= kc($minMzda[2025]) ?></td><td><?= pct(rust($minMzda[2008], $minMzda[2025])) ?></td><td><?= kc($tvDleMin) ?> Kč</td></tr>
      <tr><td>Průměrný starobní důchod</td><td><?= kc($duchod[2008]) ?></td><td><?= kc($duchod[2025]) ?></td><td><?= pct(rust($duchod[2008], $duchod[2025])) ?></td><td><?= kc($tvDleDuchodu) ?> Kč</td></tr>
      <tr class="hl"><td>TV poplatek ve skutečnosti</td><td>135</td><td>150</td><td><?= pct(rust(135, 150)) ?></td><td>—</td></tr>
    </tbody>
  </table></div>

  <h3>Vývoj poměrů: minimální mzda, průměrná mzda a důchod</h3>
  <div class="tw"><table>
    <thead><tr><th>Poměr</th><th>2008</th><th>2025</th><th>Změna</th></tr></thead>
    <tbody>
      <?php
      $pom = [
        ['Minimální mzda / průměrná mzda', $minMzda[2008] / $prumMzda[2008], $minMzda[2025] / $prumMzda[2025]],
        ['Průměrný důchod / průměrná mzda', $duchod[2008] / $prumMzda[2008], $duchod[2025] / $prumMzda[2025]],
        ['Mzda v ČT / průměrná mzda v ČR', $ct[2008][3] / $prumMzda[2008], $ct[2025][3] / $prumMzda[2025]],
      ];
      foreach ($pom as [$n, $a, $b]): ?>
      <tr><td><?= h($n) ?></td><td><?= kc($a * 100, 1) ?> %</td><td><?= kc($b * 100, 1) ?> %</td><td><?= ($b - $a) >= 0 ? '+' : '−' ?><?= kc(abs($b - $a) * 100, 1) ?> p. b.</td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>

  <h3>Kolik procent příjmu domácnost platí za ČT + ČRo dohromady</h3>
  <?php $sum08 = $tvPoplatek['stary'] + $roPoplatek['stary']; $sum25 = $tvPoplatek['novy'] + $roPoplatek['novy']; ?>
  <div class="tw"><table>
    <thead><tr><th>Podíl poplatků (<?= $sum08 ?> Kč v 2008 / <?= $sum25 ?> Kč od 5/2025) na…</th><th>2008</th><th>2025</th><th>Zátěž klesla na</th></tr></thead>
    <tbody>
      <?php foreach ([['průměrné mzdě', $prumMzda], ['minimální mzdě', $minMzda], ['průměrném důchodu', $duchod]] as [$n, $s]):
        $a = $sum08 / $s[2008] * 100; $b = $sum25 / $s[2025] * 100; ?>
      <tr><td><?= h($n) ?></td><td><?= kc($a, 2) ?> %</td><td><?= kc($b, 2) ?> %</td><td><?= kc($b / $a * 100, 0) ?> % původní zátěže</td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="muted">Domácnost tedy i po zvýšení v roce 2025 dává na média veřejné služby zhruba polovinu toho podílu z příjmu co v roce 2008.
  Po zrušení poplatků domácnost ušetří <?= kc($sum25 * 12) ?> Kč ročně (to je číslo ministra Klempíře). Rozpočet ale zaplatí <?= kc(($navrh['ct'] + $navrh['cro']) / 1000, 2) ?> mld. Kč z daní, tedy asi <?= kc(($navrh['ct'] + $navrh['cro']) / $obyvatel) ?> Kč ročně na každého obyvatele, včetně dětí.</p>

  <h3>Růst 2008 → 2025 v procentech</h3>
  <div class="legend"><span><i style="background:var(--s1)"></i>příjmy obyvatel a ceny</span><span><i style="background:var(--s2)"></i>Česká televize a TV poplatek</span><span><i style="background:var(--s3)"></i>Český rozhlas</span></div>
  <?= barChart($rusty) ?>
</section>

<section id="kdo-plati">
  <h2>Kdo platí dnes a kdo bude platit po změně</h2>
  <?= player('kdo-plati') ?>
  <div class="two">
    <div class="side">
      <h3>Dnes: poplatek (<?= $tvPoplatek['novy'] ?> + <?= $roPoplatek['novy'] ?> Kč měsíčně)</h3>
      <ul>
        <li><strong>Domácnosti</strong> platí jednou za domácnost, pokud mají jakékoli zařízení schopné příjmu. Od května 2025 se počítá i mobil, počítač nebo tablet s internetem.</li>
        <li><strong>Nemusí platit:</strong>
          <ul>
            <li>domácnosti s čistým příjmem do <?= kc($prijmovyLimit['nasobek'], 2) ?>násobku životního minima (od <?= $prijmovyLimit['od'] ?> je to <?= kc($prijmovyLimit['jednotlivec']) ?> Kč pro jednotlivce a asi <?= kc($prijmovyLimit['par']) ?> Kč pro dvojici). Je potřeba o to požádat a každého půl roku to obnovit.</li>
            <li>lidé s těžkým postižením zraku nebo sluchu a jejich domácnosti</li>
            <li>klienti domovů pro seniory a podobných zařízení bez vlastního přijímače</li>
            <li>cizinci bez trvalého nebo dlouhodobého pobytu</li>
            <li>domácnosti bez jakéhokoli přijímače</li>
          </ul>
        </li>
        <li><strong>Firmy a OSVČ</strong> do května 2025 platily za <em>každý</em> přijímač, tedy i za každé autorádio ve služebním autě. Od července 2025 platí násobek poplatku podle počtu zaměstnanců (tabulka vpravo) a navíc za každé pronajímané vozidlo s přijímačem.</li>
        <li>Poslanecký tisk 201 (koalice) chce osvobodit ještě lidi nad 75 let, nezaopatřené děti, držitele průkazů TP/ZTP a firmy do 50 zaměstnanců.</li>
      </ul>
    </div>
    <div class="side">
      <h3>Firmy: měsíční platba podle zaměstnanců</h3>
      <div class="tw"><table>
        <thead><tr><th>Zaměstnanců</th><th>Násobek</th><th>Kč/měsíc</th><th>Kč/rok</th></tr></thead>
        <tbody>
        <?php foreach ($firmyNasobky as [$z, $n]): ?>
          <tr><td><?= h($z) ?></td><td><?= $n ?: '—' ?></td><td><?= $n ? kc($n * $poplatekMesic) : 'neplatí' ?></td><td><?= $n ? kc($n * $poplatekMesic * 12) : '—' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <p class="muted">Násobek se počítá z obou poplatků dohromady (<?= $poplatekMesic ?> Kč). Firmy pod 25 zaměstnanců neplatí, ale musí se registrovat.</p>
    </div>
  </div>
  <h3>Po změně: platí všichni přes daně</h3>
  <p>Příspěvek <?= kc($prispevekCelkem / 1000, 2) ?> mld. Kč ročně půjde ze státního rozpočtu, tedy ze <strong>všech daní dohromady</strong> (DPH, spotřební daně, daně z příjmů a další).
  Žádné výjimky ani „neplátci“ už nebudou. Na ČT a ČRo přispěje každý, kdo platí daně, včetně těch, kdo dnes osvobození jsou: nízkopříjmové domácnosti, senioři v pobytových zařízeních, lidé bez televize i rádia, malé firmy.
  Nejvíc se to dotkne lidí s nízkými příjmy, protože DPH a spotřební daně platí z každého nákupu.</p>
  <p>Rozdíl je i v tom, <em>jak</em> se platí. Poplatek je pevná částka za domácnost bez ohledu na příjem (kromě výjimky pro nejchudší). Daně platí každý podle toho, kolik vydělá a utratí.
  Průměrně to vychází na asi <?= kc($prispevekCelkem / $obyvatel) ?> Kč ročně na každého obyvatele včetně dětí. Domácnost s vyššími příjmy a spotřebou zaplatí víc než dnešních <?= kc($poplatekMesic * 12) ?> Kč, nízkopříjmová méně, ale už ne nulu.</p>
</section>

<section id="hospodareni">
  <h2>Hospodaření České televize 2008–2025</h2>
  <?= player('hospodareni') ?>
  <p>Data jsou z přehledu „Vybrané ukazatele hospodaření“, který ČT zveřejňuje. Počet zaměstnanců je dnes stejný jako v roce 2008. Průměrná mzda v ČT rostla pomaleji než průměrná mzda v celé ČR.
  Výnosy celkem rostly hlavně díky komerčním a ostatním příjmům a v roce 2025 díky vyššímu poplatku.</p>
  <div class="tw"><table>
    <thead><tr><th>Rok</th><th>Výnosy celkem (mil.)</th><th>z toho poplatky (mil.)</th><th>Poplatky v cenách 2008</th><th>Zaměstnanci</th><th>Prům. mzda ČT</th><th>Výnosy na zaměstnance v cenách 2008</th></tr></thead>
    <tbody>
    <?php foreach ($ct as $r => [$v, $p, $z, $m]): $i = cpi($inflace, 2008, $r); ?>
      <tr<?= in_array($r, [2008, 2024, 2025], true) ? ' class="hl"' : '' ?>>
        <td><?= $r ?></td><td><?= kc($v / 1000) ?></td><td><?= kc($p / 1000) ?></td><td><?= kc($p / 1000 / $i) ?></td>
        <td><?= kc($z, 0) ?></td><td><?= kc($m) ?> Kč</td><td><?= kc($v / $z / $i) ?> tis.</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="tiles">
    <div class="tile"><div class="n"><?= pct(rust($ct[2008][2], $ct[2025][2])) ?></div><div class="d">změna počtu zaměstnanců ČT 2008 → 2025 (<?= kc($ct[2008][2], 0) ?> → <?= kc($ct[2025][2], 0) ?>)</div></div>
    <div class="tile"><div class="n"><?= pct(rust($ct[2008][3], $ct[2025][3]), 0) ?></div><div class="d">růst průměrné mzdy v ČT (v ČR celkem <?= pct(rust($prumMzda[2008], $prumMzda[2025]), 0) ?>)</div></div>
    <div class="tile"><div class="n"><?= pct(rust($ct[2008][0], $ct[2025][0] / $cpi0825), 0) ?></div><div class="d">reálná změna celkových výnosů ČT 2008 → 2025</div></div>
    <div class="tile"><div class="n">≈ 1,8 mld.</div><div class="d">hotovost ČT na účtech na konci roku 2025 (Lupa.cz). Vláda tvrdí, že rezervy stačí. Rada ČT to odmítá.</div></div>
  </div>
</section>

<section id="rozhlas">
  <h2>Hospodaření Českého rozhlasu 2008–2025</h2>
  <?= player('rozhlas') ?>
  <p>Data jsou z výročních zpráv o hospodaření ČRo (skutečnost, předkládané Sněmovně). Rozhlasový poplatek byl <?= $roPoplatek['stary'] ?> Kč od roku 2005 do dubna 2025, tedy ještě o tři roky déle bez změny než televizní.
  Výnos z poplatků se v letech 2008–2024 pohyboval kolem 2 mld. Kč. ČRo mezitím snížil počet zaměstnanců o <?= kc(abs(rust($cro[2008][2], $cro[2024][2])), 0) ?> %.
  Navržených <?= kc($navrh['cro'] / 1000, 3) ?> mld. je přesně výnos z poplatků v roce 2024 (<?= kc($cro[2024][1] / 1e6, 3) ?> mld.).</p>

  <div class="tiles">
    <div class="tile"><div class="n"><?= kc($croNavrhPodil, 0) ?> %</div><div class="d">reálná hodnota navržených <?= kc($navrh['cro'] / 1000, 3) ?> mld. oproti výnosu z poplatků v roce 2008 (<?= kc($croPopl2008 / 1000, 2) ?> mld.)</div></div>
    <div class="tile"><div class="n"><?= kc($plan2026['cro_poplatky'] - $navrh['cro']) ?> mil.</div><div class="d">výpadek oproti plánu poplatků na rok 2026 (<?= kc($plan2026['cro_poplatky']) ?> mil.), tj. <?= pct($cro2026Vs) ?></div></div>
    <div class="tile"><div class="n"><?= pct(rust($cro[2008][2], $cro[2025][2])) ?></div><div class="d">změna počtu zaměstnanců ČRo 2008 → 2025 (<?= kc($cro[2008][2], 0) ?> → <?= kc($cro[2025][2], 0) ?>)</div></div>
    <div class="tile"><div class="n"><?= pct(rust($cro[2008][3], $cro[2025][3]), 0) ?></div><div class="d">růst průměrné mzdy v ČRo (v ČR celkem <?= pct(rust($prumMzda[2008], $prumMzda[2025]), 0) ?>, ceny <?= pct(($cpi0825 - 1) * 100, 0) ?>)</div></div>
  </div>

  <h3>Výnos ČRo z poplatků 2008–2025 a návrh <?= $prvniRok ?> (mld. Kč)</h3>
  <div class="legend"><span><i style="background:var(--s1)"></i>nominálně</span><span><i style="background:var(--s2)"></i>v cenách roku 2008</span><span>čárkovaně: přechod na návrh <?= $prvniRok ?></span></div>
  <?= lineChart($serieCro, 1.0, 2.5, 0.25, 'g3t', 'Výnos ČRo z poplatků 2008–2025 a návrh ' . $prvniRok . ', nominálně a v cenách roku 2008') ?>

  <div class="tw"><table>
    <thead><tr><th>Rok</th><th>Výnosy celkem (mil.)</th><th>z toho poplatky (mil.)</th><th>Poplatky v cenách 2008</th><th>Zaměstnanci</th><th>Prům. mzda ČRo</th><th>Výnosy na zaměstnance v cenách 2008</th></tr></thead>
    <tbody>
    <?php foreach ($cro as $r => [$v, $p, $z, $m]): $i = cpi($inflace, 2008, $r); ?>
      <tr<?= in_array($r, [2008, 2024, 2025], true) ? ' class="hl"' : '' ?>>
        <td><?= $r ?></td><td><?= kc($v / 1000) ?></td><td><?= kc($p / 1000) ?></td><td><?= kc($p / 1000 / $i) ?></td>
        <td><?= kc($z, 0) ?></td><td><?= kc($m) ?> Kč</td><td><?= kc($v / $z / $i) ?> tis.</td>
      </tr>
    <?php endforeach; ?>
      <tr class="hl"><td><?= $prvniRok ?> (návrh)</td><td>—</td><td><?= kc($navrh['cro']) ?></td><td><?= kc($croNavrhReal) ?></td><td>—</td><td>—</td><td>—</td></tr>
    </tbody>
  </table></div>
  <p class="muted">Komerční výnosy ČRo (reklama, sponzoring, obchodní činnost) klesly z <?= kc($croKomercni[2008] / 1000) ?> mil. (2008) na <?= kc($croKomercni[2024] / 1000) ?> mil. (2024) a po novele 119/2025 Sb., která omezila reklamu, na <?= kc($croKomercni[2025] / 1000) ?> mil. (2025).
  Celkové výnosy obsahují i jednorázové položky: prodeje nemovitostí v letech 2011 a 2013, čerpání fondů a dotaci MZV na zahraniční vysílání (asi 30 mil. ročně). Výnosy z poplatků se účtují podle předpisu, ne podle skutečně vybrané hotovosti.
  Mzdy za roky 2008–2011 jsou z víceleté tabulky ve zprávě za rok 2011.</p>
</section>

<section id="srovnani">
  <h2>ČT a ČRo vedle sebe</h2>
  <?= player('srovnani') ?>
  <?php
  $sr = [
    ['Poplatek měsíčně: původní → dnes', $tvPoplatek['stary'] . ' → ' . $tvPoplatek['novy'] . ' Kč (' . pct(rust($tvPoplatek['stary'], $tvPoplatek['novy']), 0) . ')', $roPoplatek['stary'] . ' → ' . $roPoplatek['novy'] . ' Kč (' . pct(rust($roPoplatek['stary'], $roPoplatek['novy']), 0) . ')'],
    ['Původní poplatek platil od', $tvPoplatek['od'], $roPoplatek['od']],
    ['Poplatek z roku 2008 přepočtený inflací na rok 2025', kc($tvPoplatek['stary'] * $cpi0825) . ' Kč', kc($roPoplatek['stary'] * $cpi0825) . ' Kč'],
    ['Výnos z poplatků 2008', kc($ct[2008][1] / 1000) . ' mil.', kc($cro[2008][1] / 1000) . ' mil.'],
    ['Výnos z poplatků 2024 (před zvýšením)', kc($ct[2024][1] / 1000) . ' mil.', kc($cro[2024][1] / 1000) . ' mil.'],
    ['Výnos z poplatků 2025', kc($ct[2025][1] / 1000) . ' mil.', kc($cro[2025][1] / 1000) . ' mil.'],
    ['Plán poplatků 2026', kc($plan2026['ct_poplatky']) . ' mil.', kc($plan2026['cro_poplatky']) . ' mil.'],
    ['Návrh vlády – roční příspěvek (od ' . $prvniRok . ')', kc($navrh['ct']) . ' mil.', kc($navrh['cro']) . ' mil.'],
    ['Příspěvek vs. plán poplatků 2026', pct($navrhVs2026), pct($cro2026Vs)],
    ['Příspěvek reálně (v roce ' . $prvniRok . ') vs. poplatky 2008', kc($navrhPodil, 0) . ' %', kc($croNavrhPodil, 0) . ' %'],
    ['Podíl poplatků na výnosech 2025', kc($ct[2025][1] / $ct[2025][0] * 100, 0) . ' %', kc($cro[2025][1] / $cro[2025][0] * 100, 0) . ' %'],
    ['Podíl na navrženém příspěvku celkem', kc($navrh['ct'] / ($navrh['ct'] + $navrh['cro']) * 100, 1) . ' %', kc($navrh['cro'] / ($navrh['ct'] + $navrh['cro']) * 100, 1) . ' %'],
    ['Konec výjimky z DPH od 2028 (ročně)', '−' . kc($dph['ct']) . ' mil.', '−' . kc($dph['cro']) . ' mil.'],
    ['Příspěvek na obyvatele a rok', kc($navrh['ct'] / $obyvatel) . ' Kč', kc($navrh['cro'] / $obyvatel) . ' Kč'],
    ['Zaměstnanci 2008 → 2025', kc($ct[2008][2], 0) . ' → ' . kc($ct[2025][2], 0) . ' (' . pct(rust($ct[2008][2], $ct[2025][2])) . ')', kc($cro[2008][2], 0) . ' → ' . kc($cro[2025][2], 0) . ' (' . pct(rust($cro[2008][2], $cro[2025][2])) . ')'],
    ['Průměrná mzda 2008 → 2025', kc($ct[2008][3]) . ' → ' . kc($ct[2025][3]) . ' Kč (' . pct(rust($ct[2008][3], $ct[2025][3]), 0) . ')', kc($cro[2008][3]) . ' → ' . kc($cro[2025][3]) . ' Kč (' . pct(rust($cro[2008][3], $cro[2025][3]), 0) . ')'],
    ['Mzda vůči průměru ČR: 2008 → 2025', kc($ct[2008][3] / $prumMzda[2008] * 100, 0) . ' % → ' . kc($ct[2025][3] / $prumMzda[2025] * 100, 0) . ' %', kc($cro[2008][3] / $prumMzda[2008] * 100, 0) . ' % → ' . kc($cro[2025][3] / $prumMzda[2025] * 100, 0) . ' %'],
    ['Celkové výnosy 2008 → 2025 reálně', pct(rust($ct[2008][0], $ct[2025][0] / $cpi0825), 0), pct(rust($cro[2008][0], $cro[2025][0] / $cpi0825), 0)],
    ['Výnosy na zaměstnance 2025', kc($ct[2025][0] / $ct[2025][2]) . ' tis. Kč', kc($cro[2025][0] / $cro[2025][2]) . ' tis. Kč'],
    ['Ohlášené propouštění při schválení návrhu', $dopady['ct'], $dopady['cro']],
  ];
  ?>
  <div class="tw"><table class="cmp">
    <thead><tr><th>Ukazatel</th><th>Česká televize</th><th>Český rozhlas</th></tr></thead>
    <tbody>
    <?php foreach ($sr as [$n, $a, $b]): ?>
      <tr><td><?= h($n) ?></td><td><?= h($a) ?></td><td><?= h($b) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="muted">Rozhlas měl starý poplatek o tři roky déle a počet zaměstnanců snížil už před návrhem (−8 %, ČT zůstala na stejném počtu). Mzdy v ČRo jsou dnes zhruba na úrovni průměrné mzdy v ČR, v ČT asi o čtvrtinu vyšší.
  Argument vlády o dostatečných rezervách se týkal hlavně ČT (hotovost asi 1,8 mld. Kč na konci roku 2025).</p>
</section>

<section id="dluh">
  <h2>Stát jede na dluh: kolik stojí úroky</h2>
  <?= player('dluh') ?>
  <p>Poplatky dnes stojí mimo státní rozpočet. Jakmile se z nich stane výdaj rozpočtu, který je ve schodku (návrh na rok 2027: <strong><?= kc($dluh['schodek2027']) ?> mld. Kč</strong>),
  zvětší každá koruna navíc schodek, pokud se jinde neušetří. Stát si tedy bude <?= kc($prispevekCelkem / 1000, 2) ?> mld. Kč ročně půjčovat a platit z nich úroky.</p>
  <div class="tiles">
    <div class="tile"><div class="n"><?= kc($dluh['stav'], 0) ?> mld.</div><div class="d">státní dluh k 30. 9. 2026 (MF)</div></div>
    <div class="tile"><div class="n"><?= kc($dluh['vynosNovych'], 1) ?> %</div><div class="d">průměrný výnos nově prodaných státních dluhopisů v roce 2026. Tolik dnes stojí každá nově půjčená koruna.</div></div>
    <div class="tile"><div class="n"><?= kc($dluh['obsluha2025'], 1) ?> mld.</div><div class="d">čisté výdaje na obsluhu státního dluhu v roce 2025 (průměrný náklad celého dluhu asi <?= kc($dluh['prumNaklad'], 1) ?> %)</div></div>
    <div class="tile"><div class="n"><?= kc(end($dluhSim)['urokyCelkem'] / 1000, 1) ?> mld.</div><div class="d">úroky navíc za 10 let (<?= $prvniRok ?>–<?= $prvniRok + 9 ?>), pokud se příspěvek celý financuje dluhem při <?= kc($dluh['vynosNovych'], 1) ?> %</div></div>
  </div>
  <div class="tw"><table>
    <thead><tr><th>Rok</th><th>Příspěvek ČT + ČRo</th><th>Úroky v daném roce</th><th>Dluh navíc (kumulovaně)</th><th>Úroky celkem od <?= $prvniRok ?></th></tr></thead>
    <tbody>
    <?php foreach ($dluhSim as $r): ?>
      <tr><td><?= $r['rok'] ?></td><td><?= kc($prispevekCelkem) ?> mil.</td><td><?= kc($r['urok']) ?> mil.</td><td><?= kc($r['dluh'] / 1000, 1) ?> mld.</td><td><?= kc($r['urokyCelkem']) ?> mil.</td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="muted">Model počítá s tím, že se celý příspěvek (bez valorizace) i úroky z něj platí novým dluhem za <?= kc($dluh['vynosNovych'], 1) ?> % ročně. Příspěvek se vyplácí měsíčně, proto se v prvním roce úročí v průměru polovina.
  Při průměrném nákladu celého dluhu (<?= kc($dluh['prumNaklad'], 1) ?> %) by úroky byly asi o 40 % nižší. Pokud stát ušetří na DPH od ČT a ČRo (asi <?= kc($dph['ct'] + $dph['cro']) ?> mil. ročně, viz výše), sníží se půjčovaná částka asi o 7 %.
  Peníze v rozpočtu nejsou „označené“. Jde o to, o kolik se zvýší schodek, pokud vláda neušetří jinde nebo nezvýší daně.</p>
</section>

<section id="valorizace">
  <h2>Valorizace: kdy příspěvek poprvé vzroste?</h2>
  <?= player('valorizace') ?>
  <p>Podle návrhu se příspěvek zvýší o 10 %, až <em>součet</em> ročních měr inflace (počítáno od roku 2026) dosáhne 10 procentních bodů. Mezitím zůstává částka stejná. Tabulka začíná prvním realistickým rokem <?= $prvniRok ?>, do kterého se už započítá inflace za roky 2026 a 2027.
  Zkuste si nastavit průměrnou inflaci a podívejte se, kolik ČT reálně ztratí, než přijde první navýšení.</p>
  <div class="calc">
    <label for="inf">Předpokládaná inflace ročně:</label>
    <input type="range" id="inf" min="0.5" max="6" step="0.1" value="2.5">
    <output id="infOut">2,5 %</output>
  </div>
  <div class="tw"><table id="valTab">
    <thead><tr><th>Rok</th><th>Součet inflace od 2026</th><th>Příspěvek ČT (mil. Kč)</th><th>Reálně v cenách roku <?= $prvniRok ?></th><th>Reálná ztráta vs. <?= $prvniRok ?></th></tr></thead>
    <tbody></tbody>
  </table></div>
  <p class="muted">Zjednodušený model: navýšení se projeví v roce následujícím po roce, kdy součet dosáhne 10 p. b. Pak se součet počítá znovu. Za rok 2026 se použije stejná hodnota jako pro ostatní roky. Přesné znění valorizace najdete v textu tisku 231.</p>
</section>

<section id="argumenty">
  <h2>Hlavní argumenty obou stran</h2>
  <?= player('argumenty') ?>
  <div class="two">
    <div class="side">
      <h3>Pro změnu (vláda, ANO, SPD, Motoristé)</h3>
      <ul>
        <li>Úleva pro domácnosti a firmy: domácnost ušetří <?= kc($sum25 * 12) ?> Kč ročně, odpadne evidence a vymáhání poplatků.</li>
        <li>Firmy už nebudou platit podle počtu zaměstnanců (až <?= kc(100 * $poplatekMesic * 12) ?> Kč ročně) a nebudou muset hlásit počty zaměstnanců ani vozidel.</li>
        <li>Financování se „vrací na úroveň roku 2024“, kdy podle vlády ČT a ČRo ve výročních zprávách nehlásily nedostatek peněz.</li>
        <li>Média budou samostatnou rozpočtovou kapitolou a NKÚ bude kontrolovat i jejich hospodaření s majetkem. Podle vlády to znamená víc transparentnosti.</li>
        <li>Financování ze státního rozpočtu používají i jiné evropské země (např. severské státy, kde je ale vázané na zvláštní daň).</li>
        <li>Mění se jen způsob financování, ne nezávislost ani obsah vysílání (Babiš, Klempíř).</li>
        <li>Tlak na efektivitu: ředitelé zvládli navýšení, zvládnou i mírně nižší rozpočet (Klempíř).</li>
      </ul>
    </div>
    <div class="side">
      <h3>Proti (opozice, ČT, ČRo, novinářské organizace)</h3>
      <ul>
        <li>Nezávislost: o penězích by každý rok rozhodovala vládní většina, což média vystavuje politickému tlaku („zestátnění“).</li>
        <li>Výše: „úroveň 2024“ je nominálně úroveň roku 2008. Reálně jde zhruba o <?= kc(100 - $navrhPodil, 0) ?> % méně než v roce 2008.</li>
        <li>Valorizace přichází se zpožděním, jen po skocích o 10 % a jen jednou za několik let.</li>
        <li>Ve stejném roce (2028) končí výjimka z DPH. ČT a ČRo tak přijdou ještě o dalších asi 550 mil. Kč ročně.</li>
        <li>Platit budou přes daně všichni, i ti, kdo jsou dnes osvobozeni (nejchudší domácnosti, lidé bez přijímače).</li>
        <li>Rozpočet je ve schodku, takže stát si bude příspěvek nejspíš půjčovat. Za 10 let to může stát asi <?= kc(end($dluhSim)['urokyCelkem'] / 1000, 0) ?> mld. Kč jen na úrocích.</li>
        <li>Dopad: ČT uvádí propuštění 300–500 lidí, ČRo 150–200 lidí (z asi 1 350). Ubude i vlastní tvorby.</li>
        <li>Rozhlas už v letech 2008–2025 snížil počet zaměstnanců o 8 % a po novele z roku 2025 přišel o polovinu reklamních příjmů.</li>
        <li>Česká pošta přijde asi o 275 mil. Kč ročně za výběr poplatků (připomínka MV). Ministerstvo financí upozornilo, že návrh neurčuje kapitolu rozpočtu.</li>
        <li>Postup ve Sněmovně: pevný čas hlasování, omezená rozprava, 65 přihlášených poslanců nevystoupilo. Opozice zvažuje Ústavní soud.</li>
        <li>Proti se vyslovily Rada ČT, Senát (usnesení), Syndikát novinářů, Reportéři bez hranic, EBU, Evropská federace novinářů a Česká konference rektorů.</li>
      </ul>
    </div>
  </div>
</section>

<section id="dokumenty">
  <h2>Kde si ověřit originály (psp.cz)</h2>
  <ul class="src">
    <li><strong>Projednávaný vládní návrh: sněmovní tisk 231</strong>, „Novela z. o České televizi a o Českém rozhlasu“ (ruší zákon č. 348/2005 Sb. o poplatcích), předložen 19. 6. 2026:
      <a href="https://www.psp.cz/sqw/text/tiskt.sqw?O=10&amp;CT=231&amp;CT1=0" target="_blank" rel="noopener">text tisku 231/0</a> ·
      <a href="https://www.psp.cz/sqw/historie.sqw?o=10&amp;t=231" target="_blank" rel="noopener">historie projednávání</a></li>
    <li>Souběžně je na pořadu <strong>poslanecký tisk 201</strong> (Nacher, Okamura a další). Ten poplatky neruší, jen rozšiřuje osvobození (lidé nad 75 let, nezaopatřené děti, držitelé průkazů TP/ZTP, sociální a zdravotní zařízení):
      <a href="https://www.psp.cz/sqw/historie.sqw?o=10&amp;t=201" target="_blank" rel="noopener">historie tisku 201</a></li>
    <li><strong>Stenoprotokoly</strong> 34. schůze (noční rozprava 23.–24. 10. 2026):
      <a href="https://www.psp.cz/eknih/2025ps/stenprot/034schuz/index.htm" target="_blank" rel="noopener">psp.cz – 34. schůze, stenoprotokoly</a></li>
    <li><strong>Hlasování</strong> 34. schůze (jmenovitě, kdo jak hlasoval, 24. 10. 2026):
      <a href="https://www.psp.cz/sqw/phlasa.sqw?o=10&amp;s=34" target="_blank" rel="noopener">psp.cz – hlasování 34. schůze</a></li>
    <li>Pořad 34. schůze (bod 34 = tisk 231, bod 97 = tisk 201; jsou tu i výroční zprávy ČT a ČRo za rok 2025, tisky 224 a 292):
      <a href="https://www.psp.cz/sqw/ischuze.sqw?o=10&amp;s=34" target="_blank" rel="noopener">pořad schůze</a></li>
  </ul>

  <h3>Zdroje dat</h3>
  <ul class="src">
    <li>Hospodaření ČT 2008–2025: <a href="https://www.ceskatelevize.cz/vse-o-ct/hospodareni-a-financni-situace/vybrane-ukazatele-hospodareni/" target="_blank" rel="noopener">ČT – Vybrané ukazatele hospodaření</a>; <a href="https://www.lupa.cz/clanky/vice-penez-mene-lidi-ct-ukazala-hospodareni-v-prvnim-roce-s-vyssim-poplatkem/" target="_blank" rel="noopener">Lupa.cz – hospodaření ČT 2025</a></li>
    <li>Inflace: <a href="https://csu.gov.cz/pak/mira-inflace-v-cr-v-srpnu-2026" target="_blank" rel="noopener">ČSÚ – míra inflace (tabulka průměrných ročních měr)</a></li>
    <li>Průměrná mzda: <a href="https://csu.gov.cz/rychle-informace/prumerne-mzdy-4-ctvrtleti-2025" target="_blank" rel="noopener">ČSÚ – průměrné mzdy 4. čtvrtletí 2025</a>, <a href="https://csu.gov.cz/produkty/pmz_cr" target="_blank" rel="noopener">ČSÚ – časová řada</a></li>
    <li>Minimální mzda: <a href="https://mpsv.gov.cz/minimalni-mzda-od-ledna-2026-vzroste-o-1-600-korun-na-22-400-korun-mesicne" target="_blank" rel="noopener">MPSV</a>, <a href="https://www.vzp.cz/platci/informace/obzp/minimalni-mzda" target="_blank" rel="noopener">VZP – přehled</a></li>
    <li>Důchody: <a href="https://cs.wikipedia.org/wiki/Starobn%C3%AD_d%C5%AFchod" target="_blank" rel="noopener">řada ČSSZ (přes Wikipedii) pro 2005/2008</a>, <a href="https://mpsv.gov.cz/duchody-se-v-lednu-opet-navysi-prumerna-starobni-penze-dosahne-21-839-korun-mesicne" target="_blank" rel="noopener">MPSV – valorizace 2026</a></li>
    <li>Návrh vlády a částky: <a href="https://vlada.gov.cz/cz/media-centrum/aktualne/vlada-andreje-babise-schvalila-navrh-na-zmenu-financovani-ceske-televize-a-ceskeho-rozhlasu-227533/" target="_blank" rel="noopener">vlada.gov.cz (15. 6. 2026)</a>, <a href="https://www.mediaguru.cz/vlada-schvalila-zruseni-poplatku-pro-ct-a-cro-z-rozpoctu-jim-da-o-1-4-mld-mene" target="_blank" rel="noopener">MediaGuru</a></li>
    <li>Průběh 1. čtení: <a href="https://www.ceskenoviny.cz/zpravy/koalice-poslala-do-druheho-cteni-navrh-zrusit-poplatky-pro-ct-a-cro/2891827" target="_blank" rel="noopener">ČTK / České noviny</a>, <a href="https://www.irozhlas.cz/zpravy-domov/otazky-a-odpovedi-co-znamena-schvaleni-navrhu-o-koncesionarskych-poplatcich-a-co_2610241447_pik" target="_blank" rel="noopener">iRozhlas – otázky a odpovědi</a>, <a href="https://www.newstream.cz/politika/konec-koncesionarskych-poplatku-je-bliz-snemovna-zazila-dvacetihodinovou-bitvu" target="_blank" rel="noopener">Newstream</a></li>
    <li>Hospodaření ČRo 2008–2025: výroční zprávy o hospodaření ČRo předkládané Sněmovně, např. <a href="https://www.psp.cz/sqw/historie.sqw?o=10&amp;t=292" target="_blank" rel="noopener">za rok 2025 (tisk 292)</a>, <a href="https://www.psp.cz/sqw/historie.sqw?o=10&amp;t=39" target="_blank" rel="noopener">za rok 2024 (tisk 39)</a>, <a href="https://informace.rozhlas.cz/sites/default/files/documents/76e5af6835fa4417b9f81795ed7822df.pdf" target="_blank" rel="noopener">za rok 2019</a>, <a href="https://rada.rozhlas.cz/sites/default/files/documents/00990620_0.pdf" target="_blank" rel="noopener">za rok 2008</a>; <a href="https://www.e15.cz/domaci/stovky-lidi-prijdou-o-praci-ceska-televize-a-rozhlas-se-chystaji-na-tvrde-skrty-1433567" target="_blank" rel="noopener">E15 – dopady na ČRo</a></li>
    <li>Výjimka z DPH a termín účinnosti: <a href="https://www.mediar.cz/ct-i-rozhlas-za-dva-roky-prijdou-celkove-az-o-20-pct-prijmu-skonci-vyjimka-na-dph/" target="_blank" rel="noopener">Médiář (17. 6. 2026)</a>, <a href="https://ct24.ceskatelevize.cz/clanek/media/propad-rozpoctu-kvuli-zmene-financovani-muze-byt-175-miliardy-korun-uvedla-ct-374749" target="_blank" rel="noopener">ČT24</a>, <a href="https://www.lupa.cz/aktuality/vyjimka-z-dph-pro-ceskou-televizi-a-cesky-rozhlas-nakonec-bude-platit-dal/" target="_blank" rel="noopener">Lupa.cz – prodloužení výjimky</a>, <a href="https://www.ceskatelevize.cz/vse-o-ct/api/media/documents/Dlouhodobe_plany_CT_2026-2030.pdf" target="_blank" rel="noopener">Dlouhodobé plány ČT 2026–2030</a>, <a href="https://denikn.cz/2194737/budoucnost-poplatku-ct-a-cro-pred-vypjatou-snemovni-debatou-spolehaji-na-uzavrena-jednani/" target="_blank" rel="noopener">Deník N (Klempíř, Schillerová)</a>, <a href="https://ceskeduchody.cz/zpravy/konec-poplatku-pro-duchodce-se-vzdaluje" target="_blank" rel="noopener">rezerva v rozpočtu 2027</a>, <a href="https://www.denik.cz/ekonomika/konec-poplatku-za-ct-a-rozhlas-co-bude-nasledovat-kdy-je-lide-prestanou-platit/" target="_blank" rel="noopener">Deník – lhůty</a></li>
    <li>Výjimky a firmy: <a href="https://www.arecenze.cz/clanky/poplatky-za-ct-a-rozhlas-2026-nizkoprijmove-domacnosti-je-platit-nemusi-od-rijna-dosahne-na-vyjimku-vic-lidi/" target="_blank" rel="noopener">aRecenze – osvobození 2026</a>, <a href="https://www.mediaguru.cz/prehled-zmena-poplatku-za-ct-a-cro-kdo-kdy-a-kolik-plati" target="_blank" rel="noopener">MediaGuru – kdo, kdy a kolik platí</a>, <a href="https://www.mediaguru.cz/poplatky-za-ct-a-cro-do-30-cervna-firmy-hlasi-pocet-zamestnancu" target="_blank" rel="noopener">MediaGuru – firmy a zaměstnanci</a>, <a href="https://www.lupa.cz/aktuality/skupina-vladnich-poslancu-navrhuje-osekat-televizni-a-rozhlasove-poplatky/" target="_blank" rel="noopener">Lupa.cz – tisk 201</a></li>
    <li>Státní dluh: <a href="https://mf.gov.cz/cs/rozpoctova-politika/rizeni-statniho-dluhu/publikace/dluhove-portfolio-ctvrtletni-informace/2026/ctvrtletni-zprava-o-rizeni-statniho-dluhu-ceske-re-65501" target="_blank" rel="noopener">MF – Čtvrtletní zpráva o řízení státního dluhu, 1.–3. čtvrtletí 2026</a>; schodek 2027: <a href="https://ct24.ceskatelevize.cz/clanek/domaci/vlada-se-bude-zabyvat-podobou-navrhu-statniho-rozpoctu-377105" target="_blank" rel="noopener">ČT24</a>, <a href="https://www.infodnes.cz/zpravodajstvi/72386-babisuv-kabinet-schvalil-navrh-statniho-rozpoctu-na-rok-2027-se-schodkem-386-miliard/" target="_blank" rel="noopener">InfoDnes</a></li>
    <li>Historie výše poplatků: <a href="https://www.finance.cz/clanky/74581-jaka-je-cena-za-verejne-vysilani" target="_blank" rel="noopener">Finance.cz</a>, <a href="https://www.e15.cz/ekonomika/data-check-co-udelala-inflace-s-poplatkem-pro-ceskou-televizi-zkuste-si-vyvoj-dokreslit-v-nasi-hre-1432168" target="_blank" rel="noopener">E15 Data Check</a></li>
  </ul>
  <p class="muted"><strong>Metodické poznámky:</strong> Důchod 2008 (9 347 Kč) je roční průměr starobního důchodu. Za rok 2025 je použito 21 171 Kč, tedy stav před lednovou valorizací 2026 (21 839 Kč − 668 Kč podle MPSV). Obě čísla nejsou metodicky úplně shodná.
  Průměrná mzda ČT je mzda z výkazů ČT, průměrná mzda ČR je údaj ČSÚ. Inflace 2026–<?= $prvniRok ?> je odhad. Všechny výpočty dělá tato stránka sama z dat v hlavičce souboru <code>index.php</code>.</p>
</section>

<footer>Sestaveno 25. 10. 2026 z veřejných zdrojů. Čísla jsou zaokrouhlená. Pokud najdete chybu, opravte data v horní části souboru, stránka se přepočítá.</footer>
</div>

<div id="tip" role="tooltip"></div>
<script>
(function () {
  // Tooltipy pro grafy
  var tip = document.getElementById('tip');
  document.querySelectorAll('[data-tip]').forEach(function (el) {
    el.addEventListener('mousemove', function (e) {
      tip.textContent = el.getAttribute('data-tip');
      tip.style.left = Math.min(e.clientX + 14, window.innerWidth - 290) + 'px';
      tip.style.top = (e.clientY + 14) + 'px';
      tip.style.opacity = 1;
    });
    el.addEventListener('mouseleave', function () { tip.style.opacity = 0; });
  });

  // Přepínač režimu
  var btn = document.getElementById('themeBtn');
  try { var t = localStorage.getItem('theme'); if (t) document.documentElement.setAttribute('data-theme', t); } catch (e) {}
  btn.addEventListener('click', function () {
    var cur = document.documentElement.getAttribute('data-theme')
      || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('theme', next); } catch (e) {}
  });

  // Simulace valorizace
  var base = <?= (int)$navrh['ct'] ?>;
  var fmt = function (n, d) { return n.toLocaleString('cs-CZ', { minimumFractionDigits: d || 0, maximumFractionDigits: d || 0 }); };
  var inp = document.getElementById('inf'), out = document.getElementById('infOut');
  var body = document.querySelector('#valTab tbody');
  function run() {
    var inf = parseFloat(inp.value);
    out.textContent = fmt(inf, 1) + ' %';
    var start = <?= (int)$prvniRok ?>;
    var sum = inf * (start - 2026);   // součet měr inflace od roku 2026 do konce předchozího roku
    var amount = base, rows = '';
    for (var y = start; y <= start + 9; y++) {
      var bump = false;
      if (sum >= 10 - 1e-9) { amount *= 1.10; sum -= 10; bump = true; }
      var price = Math.pow(1 + inf / 100, y - start);  // cenová hladina vůči prvnímu roku
      var real = amount / price;
      var loss = (real / base - 1) * 100;
      rows += '<tr' + (bump ? ' class="hl"' : '') + '><td>' + y + (bump ? ' ▲ +10 %' : '') + '</td><td>' +
        fmt(sum + 10 * bump, 1) + ' p. b.</td><td>' + fmt(amount) + '</td><td>' + fmt(real) +
        '</td><td class="' + (loss < -0.05 ? 'neg' : '') + '">' + (y === start ? '—' : fmt(loss, 1) + ' %') + '</td></tr>';
      sum += inf;
    }
    body.innerHTML = rows;
  }
  inp.addEventListener('input', run);
  run();
})();
</script>
</body>
</html>
