#!/usr/bin/env python3
"""
import_dvf_france_automatique.py — Vide la table dvf_france et importe
tous les CSV V6 trouvés dans _Base/, dans l'ordre chronologique.

Fichiers reconnus :
  ValeursFoncieres-{YYYY}_v6.csv          (legacy converti + géoloc 2020-2024)
  ValeursFoncieres-{YYYY}_geoloc_clean.csv (géoloc 2025+)

Usage :
    python3 import_dvf_france_automatique.py
    python3 import_dvf_france_automatique.py --dry-run   # liste les fichiers sans importer
"""

import sys
import os
import re
import time
import glob
import mysql.connector

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, BASE_DIR)
from import_dvf_france import import_csv, DB_CONFIG

DRY_RUN = '--dry-run' in sys.argv

# ─── Détection des fichiers CSV à importer ────────────────────────────────────

def detect_files():
    """Retourne la liste (year, path) triée par année."""
    patterns = [
        (r'ValeursFoncieres-(\d{4})_v6\.csv',           ''),
        (r'ValeursFoncieres-(\d{4})_geoloc_clean\.csv',  ''),
    ]
    found = {}
    for fname in os.listdir(BASE_DIR):
        for pattern, _ in patterns:
            m = re.match(pattern, fname)
            if m:
                year = int(m.group(1))
                found[year] = os.path.join(BASE_DIR, fname)
                break
    return sorted(found.items())

# ─── Main ─────────────────────────────────────────────────────────────────────

def main():
    files = detect_files()

    if not files:
        print("[ERREUR] Aucun fichier CSV V6 trouvé dans _Base/")
        sys.exit(1)

    print("=" * 65)
    print("  import_dvf_france_automatique.py")
    print("=" * 65)
    print(f"\nFichiers détectés ({len(files)}) :")
    total_size = 0
    for year, path in files:
        size = os.path.getsize(path) / 1_048_576
        total_size += size
        print(f"  {year}  {os.path.basename(path):<50}  {size:>6.0f} Mo")
    print(f"\n  Total : {total_size:.0f} Mo\n")

    if DRY_RUN:
        print("[DRY-RUN] Aucun import effectué.")
        return

    if sys.stdin.isatty():
        confirm = input("Confirmer TRUNCATE + import complet ? [oui/non] : ").strip().lower()
        if confirm != 'oui':
            print("Annulé.")
            sys.exit(0)
    else:
        print("Mode non-interactif — lancement automatique.")

    # ── Truncate ──────────────────────────────────────────────────────────────
    print("\nTRUNCATE TABLE dvf_france ...")
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    cursor.execute("TRUNCATE TABLE dvf_france")
    conn.commit()
    cursor.close()
    conn.close()
    print("Table vidée.\n")

    # ── Import fichier par fichier ────────────────────────────────────────────
    results = []
    t_global = time.time()

    for year, path in files:
        print("─" * 65)
        print(f"  {year}  —  {os.path.basename(path)}")
        print("─" * 65)
        t0 = time.time()
        import_csv(path, truncate=False)
        results.append((year, os.path.basename(path), time.time() - t0))
        print()

    # ── Résumé final ──────────────────────────────────────────────────────────
    elapsed_total = time.time() - t_global

    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()

    cursor.execute("SELECT COUNT(*) FROM dvf_france")
    total_rows = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM dvf_france WHERE longitude IS NOT NULL")
    geo_rows = cursor.fetchone()[0]

    cursor.execute("SELECT MIN(date_mutation), MAX(date_mutation) FROM dvf_france")
    date_min, date_max = cursor.fetchone()

    cursor.execute("""
        SELECT YEAR(date_mutation), COUNT(*)
        FROM dvf_france
        WHERE date_mutation IS NOT NULL
        GROUP BY YEAR(date_mutation)
        ORDER BY YEAR(date_mutation)
    """)
    by_year = cursor.fetchall()

    cursor.close()
    conn.close()

    print("=" * 65)
    print("  RÉSUMÉ FINAL")
    print("=" * 65)
    print(f"\n  Fichiers importés  : {len(results)}")
    print(f"  Durée totale       : {elapsed_total:.0f}s")
    print(f"\n  Lignes par année :")
    for yr, cnt in by_year:
        print(f"    {yr}  :  {cnt:>10,}")
    print(f"\n  Total lignes       : {total_rows:,}")
    print(f"  Géolocalisées      : {geo_rows:,}")
    print(f"  Période            : {date_min} → {date_max}")
    print("=" * 65)


if __name__ == '__main__':
    main()
