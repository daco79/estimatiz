#!/usr/bin/env python3
"""
dedup_2020_2024_v6.py — Dédoublonnage des fichiers géoloc V6 2020-2024
Appelle dedup_geoloc.dedup_file pour chaque année avec la nomenclature V6.

Entrée  : 2020_full.csv … 2024_full.csv
Sortie  : ValeursFoncieres-2020_v6_clean.csv … ValeursFoncieres-2024_v6_clean.csv
"""
import sys
import time
from pathlib import Path

BASE_DIR = Path(__file__).parent
sys.path.insert(0, str(BASE_DIR))
from dedup_geoloc import dedup_file

YEARS = [2020, 2021, 2022, 2023, 2024]

totals = {}
t_global = time.time()

print("=" * 60)
print("  dedup_2020_2024_v6.py — 5 fichier(s) à traiter")
print("=" * 60)
print()

for year in YEARS:
    src = BASE_DIR / f'{year}_full.csv'
    dst = BASE_DIR / f'ValeursFoncieres-{year}_v6_clean.csv'

    print(f"── {year} " + "─" * 45)
    if not src.exists():
        print(f"  [SKIP] {src.name} introuvable")
        continue
    if dst.exists():
        print(f"  [SKIP] {dst.name} déjà présent — supprimer pour relancer")
        continue

    kept = dedup_file(src, dst)
    totals[year] = kept

print()
print("=" * 60)
print(f"  Terminé en {time.time() - t_global:.0f}s")
print(f"  Fichiers produits dans : {BASE_DIR}")
for year, kept in totals.items():
    print(f"  {year} → ValeursFoncieres-{year}_v6_clean.csv  ({kept:,} lignes)")
print("=" * 60)
print()
print("Prochaine étape : importer dans MySQL")
for year in totals:
    print(f"  python3 import_dvf_france.py ValeursFoncieres-{year}_v6_clean.csv")
