#!/usr/bin/env python3
"""Download the locally hosted editorial photos for Ostadid Wardavard."""
from pathlib import Path
from urllib.request import Request, urlopen
from urllib.error import URLError, HTTPError
import json

ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / "ostadid-wardavard" / "assets" / "images"
DEST.mkdir(parents=True, exist_ok=True)

PHOTOS = [
    ("wardavard-01.jpg", "https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1500&auto=format&fit=crop&q=86", "Architecture planning and building design"),
    ("wardavard-02.jpg", "https://images.unsplash.com/photo-1541976590-713941681591?w=1500&auto=format&fit=crop&q=86", "Construction site and structure"),
    ("wardavard-03.jpg", "https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=1500&auto=format&fit=crop&q=86", "Construction work and site activity"),
    ("wardavard-04.jpg", "https://images.unsplash.com/photo-1590725140246-20acdee442be?w=1500&auto=format&fit=crop&q=86", "Building work and construction details"),
    ("wardavard-05.jpg", "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1500&auto=format&fit=crop&q=86", "Modern building exterior"),
    ("wardavard-06.jpg", "https://images.unsplash.com/photo-1518005020951-eccb494ad742?w=1500&auto=format&fit=crop&q=86", "Contemporary architecture"),
]

metadata = []
for filename, url, caption in PHOTOS:
    target = DEST / filename
    request = Request(url, headers={"User-Agent": "OstadidWardavardAssetCollector/1.0"})
    try:
        with urlopen(request, timeout=45) as response:
            data = response.read()
            content_type = response.headers.get("Content-Type", "")
            if not content_type.startswith("image/") or len(data) < 20_000:
                raise RuntimeError(f"Unexpected image response for {filename}: {content_type}, {len(data)} bytes")
            target.write_bytes(data)
        metadata.append({"file": filename, "source": url, "caption": caption, "provider": "Unsplash", "bytes": target.stat().st_size})
        print(f"Downloaded {filename} ({target.stat().st_size:,} bytes)")
    except (HTTPError, URLError, TimeoutError, RuntimeError) as exc:
        raise SystemExit(f"Could not download {filename}: {exc}")

(DEST / "sources.json").write_text(json.dumps(metadata, ensure_ascii=False, indent=2), encoding="utf-8")
print(f"Saved {len(metadata)} images to {DEST}")
