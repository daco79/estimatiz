#!/usr/bin/env python3
"""
dedup_geoloc.py — Dédoublonnage des CSV DVF format géolocalisé (nouveau format V6)

Dans le nouveau format, id_mutation regroupe toutes les lignes d'une même vente
(appartement principal + cave + parking + terrain...).
On garde UNE seule ligne par id_mutation, la plus représentative.

Règles de sélection (score décroissant) :
  1. Priorité type_local :
       Appartement                          → 10
       Maison                               →  9
       Local industriel/commercial          →  5
       (vide / autre)                       →  3  ← terrain nu = vente légitime
       Dépendance                           →  1
  2. À priorité égale : préférer lot1_surface_carrez renseignée
  3. À égalité : préférer surface_reelle_bati renseignée
  4. À égalité : préférer longitude renseignée (géolocalisation)

Filtre final : on conserve la ligne uniquement si elle a
  - lot1_surface_carrez > 0  OU
  - surface_reelle_bati > 0  OU
  - surface_terrain > 0      (vente de terrain pur)
  Sinon la mutation est écartée (aucune surface exploitable pour estimation).

Entrée  : ValeursFoncieres-{YYYY}_geoloc.csv  (virgule-séparé, format V6)
Sortie  : ValeursFoncieres-{YYYY}_geoloc_clean.csv

Usage :
    python3 dedup_geoloc.py                    # traite 2025_geoloc par défaut
    python3 dedup_geoloc.py 2025               # idem
    python3 dedup_geoloc.py fichier.csv        # fichier explicite
"""

import csv
import sys
import time
from pathlib import Path

# ─── Configuration ──────────────────────────────────────────────────────────
BASE_DIR = Path(__file__).parent

TYPE_LOCAL_PRIORITY = {
    'Appartement':                               10,
    'Maison':                                     9,
    'Local industriel. commercial ou assimilé':   5,
    'Dépendance':                                 1,
}
# Tout ce qui n'est pas dans le dict → priorité 3 (terrain nu, autre)
DEFAULT_PRIORITY = 3

# ─── Helpers ────────────────────────────────────────────────────────────────

def surface_val(v) -> float:
    """Retourne la valeur float de la surface, 0.0 si vide/invalide."""
    if v is None:
        return 0.0
    s = str(v).strip()
    if not s:
        return 0.0
    try:
        return max(0.0, float(s))
    except ValueError:
        return 0.0

def row_score(row: dict) -> tuple:
    """
    Calcule le score de sélection d'une ligne.
    Plus le score est élevé, plus on préfère cette ligne.
    """
    tl       = str(row.get('type_local', '') or '').strip()
    prio     = TYPE_LOCAL_PRIORITY.get(tl, DEFAULT_PRIORITY)
    has_carrez = 1 if surface_val(row.get('lot1_surface_carrez')) > 0 else 0
    has_reelle = 1 if surface_val(row.get('surface_reelle_bati')) > 0 else 0
    has_geo    = 1 if str(row.get('longitude', '') or '').strip() else 0
    return (prio, has_carrez, has_reelle, has_geo)

def has_any_surface(row: dict) -> bool:
    """True si la ligne a au moins une surface exploitable."""
    return (
        surface_val(row.get('lot1_surface_carrez')) > 0
        or surface_val(row.get('surface_reelle_bati')) > 0
        or surface_val(row.get('surface_terrain')) > 0
    )

# ─── Déduplication ──────────────────────────────────────────────────────────

def dedup_file(src: Path, dst: Path):
    t0 = time.time()
    size_mb = src.stat().st_size / 1_048_576

    print(f"Source  : {src.name}  ({size_mb:.0f} Mo)")
    print("Lecture en mémoire...")

    with src.open('r', encoding='utf-8', errors='replace', newline='') as f:
        reader = csv.DictReader(f)
        fieldnames = reader.fieldnames
        all_rows = list(reader)

    rows_read = len(all_rows)
    print(f"{rows_read:,} lignes lues")

    # ── Passe 1 : sélection meilleure ligne par id_mutation ──────────────────
    print("Sélection de la meilleure ligne par mutation...")
    best: dict = {}   # id_mutation → (score, idx)

    for idx, row in enumerate(all_rows):
        mid   = str(row.get('id_mutation', '') or '').strip()
        if not mid:
            mid = f'__nokey_{idx}'   # sécurité si id_mutation vide
        score = row_score(row)
        if mid not in best or score > best[mid][0]:
            best[mid] = (score, idx)

    kept_indices = {idx for (_, idx) in best.values()}
    rows_dup = rows_read - len(kept_indices)

    # ── Passe 2 : filtre surface ─────────────────────────────────────────────
    final_rows   = []
    rows_nosurf  = 0

    for idx, row in enumerate(all_rows):
        if idx not in kept_indices:
            continue
        if not has_any_surface(row):
            rows_nosurf += 1
            continue
        final_rows.append(row)

    rows_kept = len(final_rows)

    # ── Écriture ─────────────────────────────────────────────────────────────
    print(f"Écriture de {dst.name}...")
    with dst.open('w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, lineterminator='\n')
        writer.writeheader()
        writer.writerows(final_rows)

    elapsed = time.time() - t0

    print()
    print("─" * 50)
    print(f"Lignes lues              : {rows_read:>10,}")
    print(f"Lignes dupliquées retirées: {rows_dup:>10,}  ({rows_dup/rows_read*100:.1f}%)")
    print(f"Lignes sans surface      : {rows_nosurf:>10,}")
    print(f"Lignes conservées        : {rows_kept:>10,}  ({rows_kept/rows_read*100:.1f}%)")
    print(f"Durée                    : {elapsed:>10.1f}s")
    print(f"Fichier produit          : {dst.name}  ({dst.stat().st_size/1_048_576:.0f} Mo)")
    print("─" * 50)
    print()
    print("Vérification par type_local conservé :")

    from collections import Counter
    tl_counts = Counter(r.get('type_local', '') or '(vide)' for r in final_rows)
    for tl, cnt in sorted(tl_counts.items(), key=lambda x: -x[1])[:8]:
        print(f"  {tl or '(vide)':<45} {cnt:>8,}")

    return rows_kept

# ─── Main ───────────────────────────────────────────────────────────────────

def main():
    args = [a for a in sys.argv[1:] if not a.startswith('-')]

    if args:
        # Argument = année (2025) ou chemin de fichier
        arg = args[0]
        if arg.isdigit():
            year = int(arg)
            src  = BASE_DIR / f'ValeursFoncieres-{year}_geoloc.csv'
            dst  = BASE_DIR / f'ValeursFoncieres-{year}_geoloc_clean.csv'
        else:
            src = Path(arg)
            if not src.is_absolute():
                src = BASE_DIR / src
            dst = src.with_name(src.stem + '_clean' + src.suffix)
    else:
        # Défaut : 2025
        src = BASE_DIR / 'ValeursFoncieres-2025_geoloc.csv'
        dst = BASE_DIR / 'ValeursFoncieres-2025_geoloc_clean.csv'

    if not src.exists():
        print(f"[ERREUR] Fichier introuvable : {src}")
        sys.exit(1)
    if dst.exists():
        print(f"[SKIP] {dst.name} existe déjà — supprimer pour relancer")
        sys.exit(0)

    print("=" * 55)
    print("  dedup_geoloc.py — dédoublonnage par id_mutation")
    print("=" * 55)
    print()

    dedup_file(src, dst)
    print(f"Prêt pour l'import : python3 import_dvf_france.py {dst.name} --truncate")


if __name__ == '__main__':
    main()
