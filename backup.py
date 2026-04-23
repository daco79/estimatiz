#!/usr/bin/env python3
"""
backup.py — Sauvegarde du site Estimatiz
Usage : python3 backup.py
Crée un ZIP horodaté dans ~/Documents/Sauvegarde Estimatiz/
Exclusions : CSV du dossier _Base et dossiers techniques .git, .venv, __pycache__, .cache
"""

import os
import zipfile
from datetime import datetime
from pathlib import Path

SITE_DIR  = Path(__file__).parent.resolve()
DEST_DIR  = Path.home() / "Documents" / "Sauvegarde Estimatiz"
DEST_DIR.mkdir(parents=True, exist_ok=True)

EXCLUDE_DIRS       = {".git", ".venv", "__pycache__", ".cache"}
EXCLUDE_BASE_CSVS  = "_Base"

def should_exclude(path: Path) -> bool:
    parts = set(path.parts)
    if parts & EXCLUDE_DIRS:
        return True
    if path.parts and path.parts[0] == EXCLUDE_BASE_CSVS and path.suffix.lower() == ".csv":
        return True
    return False

timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
zip_name  = f"estimatiz_backup_{timestamp}.zip"
zip_path  = DEST_DIR / zip_name

print("=" * 50)
print("  Sauvegarde Estimatiz")
print(f"  Destination : {zip_path}")
print("=" * 50)

count = 0
with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
    for file in SITE_DIR.rglob("*"):
        if not file.is_file():
            continue
        rel = file.relative_to(SITE_DIR)
        if should_exclude(rel):
            continue
        zf.write(file, rel)
        count += 1
        print(f"  + {rel}")

size_mb = zip_path.stat().st_size / (1024 * 1024)
print()
print(f"OK — {count} fichiers archivés")
print(f"Taille : {size_mb:.1f} Mo")
print(f"Fichier : {zip_path}")
