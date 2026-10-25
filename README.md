# Poplatky ČT a ČRo v číslech

Jednostránkový přehled čísel a argumentů ke zrušení televizních a rozhlasových poplatků
(vládní návrh, sněmovní tisk 231, 2026): inflace, mzdy, minimální mzda, důchody,
hospodaření ČT a ČRo, konec výjimky z DPH, dopad na státní dluh. Včetně audio verze.

**Web:** https://michal-okr.github.io/ct_a_cro_cisla/

## Soubory

| Soubor | Účel |
|---|---|
| `index.html` | vygenerovaná statická stránka (to, co se zobrazuje na webu; jde otevřít i lokálně dvojklikem) |
| `data.php` | všechna vstupní data a výpočty |
| `index.php` | šablona stránky |
| `narace.php` | texty pro audio verzi (čísla vypsaná slovy) |
| `build_audio.py` | generuje MP3 přes [edge-tts](https://pypi.org/project/edge-tts/) (hlas cs-CZ-VlastaNeural) |
| `build.ps1` | sestavení: `.\build.ps1` (jen HTML), `.\build.ps1 -Audio` (i MP3) |
| `audio/` | MP3 (celá stránka + sekce), `narace.json` = přepis čtených textů |

## Úprava dat

1. Opravte číslo v `data.php` (případně text v `index.php` / `narace.php`).
2. Spusťte `.\build.ps1 -Audio` (potřebuje PHP nebo Docker; pro audio Python s `pip install edge-tts`).
3. Commitněte a pushněte – GitHub Pages web aktualizuje.

Zdroje dat jsou uvedené přímo na stránce v sekci „Kde si ověřit originály“.
