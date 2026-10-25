# Vygeneruje statickou index.html z index.php (otevře se dvojklikem bez PHP serveru).
#   .\build.ps1          ... jen HTML
#   .\build.ps1 -Audio   ... nejdřív přegeneruje MP3 (audio/*.mp3), pak HTML
# Použije lokální PHP, pokud je nainstalované, jinak Docker (image php:8.3-cli).
param([switch]$Audio)
$ErrorActionPreference = 'Stop'
$dir = $PSScriptRoot

function Invoke-Php([string[]]$PhpArgs) {
    if (Get-Command php -ErrorAction SilentlyContinue) {
        Push-Location $dir; try { & php @PhpArgs } finally { Pop-Location }
    } elseif (Get-Command docker -ErrorAction SilentlyContinue) {
        & docker run --rm -v "${dir}:/app" -w /app php:8.3-cli php @PhpArgs
    } else {
        throw 'Není k dispozici PHP ani Docker.'
    }
    if ($LASTEXITCODE -ne 0) { throw "PHP skončilo chybou (exit $LASTEXITCODE)." }
}

if ($Audio) {
    New-Item -ItemType Directory -Force (Join-Path $dir 'audio') | Out-Null
    Invoke-Php @('narace.php', 'audio/narace.json')
    & py (Join-Path $dir 'build_audio.py') (Join-Path $dir 'audio\narace.json')
    if ($LASTEXITCODE -ne 0) { throw 'Generování audia selhalo.' }
}

$html = Invoke-Php @('index.php')
$out = Join-Path $dir 'index.html'
[System.IO.File]::WriteAllText($out, ($html -join "`n"), (New-Object System.Text.UTF8Encoding $false))
Write-Host "Hotovo: $out"
