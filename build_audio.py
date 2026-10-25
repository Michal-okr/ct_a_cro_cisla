"""Vygeneruje MP3 pro audio verzi stránky (celek + jednotlivé sekce).

Texty bere z JSON souboru, který vytvoří narace.php, hlas: neurální čeština z Microsoft Edge (edge-tts).
Použití:  php narace.php audio/narace.json  &&  py build_audio.py audio/narace.json
Sekce, jejichž text se nezměnil, se negenerují znovu (porovnává se hash v audio/hash.json).
"""
import asyncio
import hashlib
import json
import pathlib
import sys

import edge_tts

AUDIO = pathlib.Path(__file__).resolve().parent / "audio"
RATE = "+0%"


async def tts(text: str, voice: str, out: pathlib.Path) -> None:
    await edge_tts.Communicate(text, voice, rate=RATE).save(str(out))


async def main() -> None:
    data = json.loads(pathlib.Path(sys.argv[1]).read_text(encoding="utf-8"))
    voice = data["hlas"]
    sekce = data["sekce"]
    sekce.append({
        "id": "cela-stranka",
        "nazev": "Celá stránka",
        "text": " ".join(f"{s['nazev']}. {s['text']}" for s in sekce),
    })

    AUDIO.mkdir(exist_ok=True)
    hash_file = AUDIO / "hash.json"
    try:
        hashes = json.loads(hash_file.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        hashes = {}

    for s in sekce:
        out = AUDIO / f"{s['id']}.mp3"
        h = hashlib.sha256((voice + RATE + s["text"]).encode("utf-8")).hexdigest()
        if out.exists() and hashes.get(s["id"]) == h:
            print(f"beze změny: {out.name}")
            continue
        await tts(s["text"], voice, out)
        hashes[s["id"]] = h
        print(f"vygenerováno: {out.name}")

    hash_file.write_text(json.dumps(hashes, indent=1), encoding="utf-8")


asyncio.run(main())
