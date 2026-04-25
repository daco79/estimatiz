#!/usr/bin/env python3
"""
dedup_legacy.py — Nettoyage (dédoublonnage) des CSV DVF format legacy (2014–2024)

Règles appliquées (identiques à l'ancien pipeline) :
  1. Doublon de vente : même (Date mutation, Valeur fonciere, No voie, B/T/Q,
     Type de voie, Code voie, Voie, Code postal, Commune, 1er lot)
     → on garde la ligne avec la priorité Type local la plus haute
       (Appartement > Maison > Local > Dépendance)
     + en cas d'égalité, on préfère la ligne avec Surface reelle bati renseignée
  2. Absence de surface : si après dédup la ligne n'a aucune surface valide
     (ni Surface Carrez d'aucun lot, ni Surface reelle bati) → supprimée
     (inutilisable pour l'estimation)

Entrée  : ValeursFoncieres-{YYYY}.csv / valeursfoncieres-{yyyy}.csv  (pipe-séparé)
Sortie  : ValeursFoncieres-{YYYY}_clean.csv  (pipe-séparé, même répertoire)

Usage :
    python3 dedup_legacy.py                  # traite 2014→2024 par défaut
    python3 dedup_legacy.py 2018 2019 2020   # années choisies

Pré-requis : Python 3.8+, aucune dépendance externe
"""

import csv
import os
import sys
import time
from pathlib import Path

# ─── Configuration ─────────────────────────────────────────────────────────────
BASE_DIR   = Path(__file__).parent
DELIMITER  = '|'

# Noms de fichiers source possibles par année (ordre de priorité)
SOURCE_PATTERNS = [
    'ValeursFoncieres-{year}.csv',
    'valeursfoncieres-{year}.csv',
    'ValeursFoncieres-{year}_geoloc.csv',   # ignoré si déjà au nouveau format
]

OUTPUT_PATTERN = 'ValeursFoncieres-{year}_clean.csv'

# Colonnes de déduplication (identifient une même vente)
KEY_COLS = [
    'Date mutation',
    'Valeur fonciere',
    'No voie',
    'B/T/Q',
    'Type de voie',
    'Code voie',
    'Voie',
    'Code postal',
    'Commune',
    '1er lot',
]

# Priorité Type local (plus haut = meilleure ligne à garder)
TYPE_LOCAL_PRIORITY = {
    'Appartement': 10,
    'Maison': 9,
    'Local industriel. commercial ou assimilé': 5,
    'Dépendance': 1,
}

# Colonnes surface : au moins une doit être > 0 pour garder la ligne
SURFACE_COLS = [
    'Surface reelle bati',
    'Surface Carrez du 1er lot',
    'Surface Carrez du 2eme lot',
    'Surface Carrez du 3eme lot',
    'Surface Carrez du 4eme lot',
    'Surface Carrez du 5eme lot',
]

# ─── Helpers ───────────────────────────────────────────────────────────────────

def find_source(year: int) -> Path | None:
    for pat in SOURCE_PATTERNS:
        p = BASE_DIR / pat.format(year=year)
        if p.exists():
            return p
    return None

def surface_valide(v: str) -> bool:
    s = v.strip().replace(',', '.')
    if not s:
        return False
    try:
        return float(s) > 0
    except ValueError:
        return False

def dedup_file(src: Path, dst: Path) -> dict:
    """
    Déduplique src → dst.
    Retourne un dict de stats.
    """
    t0 = time.time()

    # ── Lecture complète ──────────────────────────────────────
    print(f"  Lecture de {src.name} ({src.stat().st_size / 1_048_576:.0f} Mo)...")
    with src.open('r', encoding='utf-8', errors='replace', newline='') as f:
        reader = csv.reader(f, delimiter=DELIMITER)
        header = next(reader)
        if header:
            header[0] = header[0].lstrip('\ufeff').strip()
        all_rows = list(reader)

    rows_read = len(all_rows)
    print(f"  {rows_read:,} lignes lues")

    # ── Index des colonnes ────────────────────────────────────
    key_indices = []
    missing_keys = []
    for col in KEY_COLS:
        try:
            key_indices.append(header.index(col))
        except ValueError:
            missing_keys.append(col)
    if missing_keys:
        print(f"  [WARN] Colonnes clé absentes : {missing_keys}")

    try:
        type_local_idx = header.index('Type local')
    except ValueError:
        type_local_idx = None
        print("  [WARN] 'Type local' absent — priorité désactivée")

    try:
        surface_reelle_idx = header.index('Surface reelle bati')
    except ValueError:
        surface_reelle_idx = None

    surface_indices = []
    for col in SURFACE_COLS:
        try:
            surface_indices.append(header.index(col))
        except ValueError:
            pass

    # ── Passe 1 : déduplication ───────────────────────────────
    # best_row[key] = (score, original_idx, row)
    print("  Déduplication en cours...")
    best_row: dict = {}
    for idx, row in enumerate(all_rows):
        key = tuple(row[i] for i in key_indices if i < len(row))
        rank = TYPE_LOCAL_PRIORITY.get(
            row[type_local_idx].strip() if type_local_idx is not None and type_local_idx < len(row) else '',
            3
        )
        has_surf = 1 if (surface_reelle_idx is not None
                         and surface_reelle_idx < len(row)
                         and surface_valide(row[surface_reelle_idx])) else 0
        score = (rank, has_surf)
        if key not in best_row or score > best_row[key][0]:
            best_row[key] = (score, idx, row)

    kept_indices = {idx for (_, idx, _) in best_row.values()}

    # ── Passe 2 : filtre surface ──────────────────────────────
    final_rows  = []
    rows_dup    = 0
    rows_nosurf = 0

    for idx, row in enumerate(all_rows):
        if idx not in kept_indices:
            rows_dup += 1
            continue
        # Au moins une surface valide
        if surface_indices and not any(
            surface_valide(row[i]) for i in surface_indices if i < len(row)
        ):
            rows_nosurf += 1
            continue
        final_rows.append(row)

    rows_kept = len(final_rows)

    # ── Écriture du CSV propre ────────────────────────────────
    print(f"  Écriture de {dst.name}...")
    with dst.open('w', encoding='utf-8', newline='') as f:
        writer = csv.writer(f, delimiter=DELIMITER)
        writer.writerow(header)
        writer.writerows(final_rows)

    elapsed = time.time() - t0
    return {
        'annee'      : int(dst.stem.split('-')[1].split('_')[0]),  # ex: "2019" from "ValeursFoncieres-2019_clean"
        'source'     : src.name,
        'output'     : dst.name,
        'lus'        : rows_read,
        'doublons'   : rows_dup,
        'sans_surf'  : rows_nosurf,
        'conserves'  : rows_kept,
        'taux_keep'  : f"{rows_kept/rows_read*100:.1f}%" if rows_read else '?',
        'duree_s'    : round(elapsed, 1),
    }

# ─── Main ──────────────────────────────────────────────────────────────────────

def main():
    if len(sys.argv) > 1:
        years = [int(a) for a in sys.argv[1:] if a.isdigit()]
    else:
        years = list(range(2014, 2025))  # 2014 → 2024

    print("=" * 65)
    print(f"  dedup_legacy.py — {len(years)} fichier(s) à traiter")
    print("=" * 65)

    summary = []
    for year in years:
        src = find_source(year)
        if src is None:
            print(f"\n[SKIP] {year} — fichier source introuvable dans {BASE_DIR}")
            continue

        dst = BASE_DIR / OUTPUT_PATTERN.format(year=year)
        if dst.exists():
            print(f"\n[SKIP] {year} — {dst.name} existe déjà (supprimer pour relancer)")
            continue

        print(f"\n── {year} ──────────────────────────────────────────────────")
        try:
            stats = dedup_file(src, dst)
            summary.append(stats)
            print(f"  ✓  {stats['conserves']:>8,} lignes conservées  "
                  f"({stats['doublons']:,} doublons, {stats['sans_surf']:,} sans surface)  "
                  f"— {stats['duree_s']}s")
        except Exception as e:
            print(f"  [ERREUR] {e}")
            import traceback; traceback.print_exc()

    # ── Tableau récap ─────────────────────────────────────────
    if summary:
        print("\n" + "=" * 65)
        print(f"  {'Année':<6} {'Lus':>9} {'Doublons':>9} {'SansSurf':>9} {'Gardés':>9} {'%':>6} {'Durée':>7}")
        print("  " + "─" * 60)
        for s in summary:
            print(f"  {s['annee']:<6} {s['lus']:>9,} {s['doublons']:>9,} {s['sans_surf']:>9,} "
                  f"{s['conserves']:>9,} {s['taux_keep']:>6} {s['duree_s']:>6.1f}s")
        total_lus  = sum(s['lus']       for s in summary)
        total_kept = sum(s['conserves'] for s in summary)
        print("  " + "─" * 60)
        print(f"  {'TOTAL':<6} {total_lus:>9,} {'':>9} {'':>9} {total_kept:>9,}")
        print("=" * 65)
        print(f"\nFichiers _clean.csv créés dans : {BASE_DIR}")

if __name__ == '__main__':
    main()
