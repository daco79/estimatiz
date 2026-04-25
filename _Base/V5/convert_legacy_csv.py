#!/usr/bin/env python3
"""
convert_legacy_csv.py — Convertit les CSV DVF legacy (2014–2024) au format V6

Entrée  : ValeursFoncieres-{YYYY}_clean.csv  (pipe-séparé, format ancien)
Sortie  : ValeursFoncieres-{YYYY}_v6.csv     (virgule-séparé, format nouveau)

Le fichier de sortie est directement importable avec import_dvf_france.py
et a exactement les mêmes colonnes que ValeursFoncieres-2025_geoloc.csv.

Transformations :
  - Date mutation  DD/MM/YYYY  →  YYYY-MM-DD (ISO)
  - Valeur fonciere  "450 000,00"  →  "450000.00"
  - Surfaces  "93,54"  →  "93.54"
  - Code postal  INT sans zéro  →  "01460" (5 chars, zero-padded)
  - Type de voie + Voie  →  adresse_nom_voie fusionné ("RUE DE RIVOLI")
  - B/T/Q  →  adresse_suffixe
  - code_commune  =  code_dept + code_commune_3chars  →  "75104"
  - nom_commune  "PARIS 01"  →  "Paris 1er Arrondissement"
  - id_parcelle  reconstruit sur 14 chars
  - id_mutation  =  Identifiant de document  ou  "LEGACY-{year}-{n}"
  - longitude / latitude  →  vide (absent des anciens fichiers)
  - code_nature_culture  →  vide (pas dans l'ancien format)
  - colonnes supprimées : Articles CGI 1-5, Identifiant local, Prefixe de section,
                          Section, No plan, Reference document

Usage :
    python3 convert_legacy_csv.py                  # convertit 2014→2019
    python3 convert_legacy_csv.py 2017 2018        # années spécifiques
    python3 convert_legacy_csv.py --all            # convertit 2014→2024
"""

import csv
import re
import sys
import time
from pathlib import Path

# ─── Configuration ──────────────────────────────────────────────────────────
BASE_DIR        = Path(__file__).parent
INPUT_DELIMITER = '|'
INPUT_PATTERN   = 'ValeursFoncieres-{year}_clean.csv'
OUTPUT_PATTERN  = 'ValeursFoncieres-{year}_v6.csv'
BATCH_SIZE      = 10_000   # lignes par flush disque

# Colonnes de sortie — ordre identique à ValeursFoncieres-2025_geoloc.csv
OUTPUT_COLS = [
    'id_mutation', 'date_mutation', 'numero_disposition', 'nature_mutation',
    'valeur_fonciere', 'adresse_numero', 'adresse_suffixe', 'adresse_nom_voie',
    'adresse_code_voie', 'code_postal', 'code_commune', 'nom_commune',
    'code_departement', 'ancien_code_commune', 'ancien_nom_commune',
    'id_parcelle', 'ancien_id_parcelle', 'numero_volume',
    'lot1_numero', 'lot1_surface_carrez',
    'lot2_numero', 'lot2_surface_carrez',
    'lot3_numero', 'lot3_surface_carrez',
    'lot4_numero', 'lot4_surface_carrez',
    'lot5_numero', 'lot5_surface_carrez',
    'nombre_lots', 'code_type_local', 'type_local',
    'surface_reelle_bati', 'nombre_pieces_principales',
    'code_nature_culture', 'nature_culture',
    'code_nature_culture_speciale', 'nature_culture_speciale',
    'surface_terrain', 'longitude', 'latitude',
]

# ─── Helpers de transformation ───────────────────────────────────────────────

def clean(v: str) -> str:
    """Retire BOM, espaces insécables, espaces de bord."""
    return v.strip().lstrip('\ufeff').replace('\xa0', ' ').replace('\u202f', ' ').strip()


def conv_date(v: str) -> str:
    """DD/MM/YYYY → YYYY-MM-DD. Retourne '' si invalide."""
    v = clean(v)
    if not v:
        return ''
    m = re.match(r'^(\d{2})/(\d{2})/(\d{4})$', v)
    if m:
        return f"{m.group(3)}-{m.group(2)}-{m.group(1)}"
    return ''


def conv_decimal(v: str) -> str:
    """'450 000,00' → '450000.00'. Retourne '' si vide/invalide."""
    v = clean(v)
    if not v:
        return ''
    # Retire tous les espaces (séparateurs de milliers)
    v = re.sub(r'\s+', '', v)
    # Virgule → point
    v = v.replace(',', '.')
    # Vérifie que c'est numérique
    if not re.match(r'^\d+(\.\d+)?$', v):
        return ''
    return v


def conv_surface(v: str) -> str:
    """Comme conv_decimal, mais '0' ou '0.00' → '' (surface nulle = absent)."""
    s = conv_decimal(v)
    if not s:
        return ''
    try:
        if float(s) == 0:
            return ''
    except ValueError:
        return ''
    return s


def conv_code_postal(v: str) -> str:
    """INT sans zéro → VARCHAR 5 chars. '1460' → '01460', '75001' → '75001'."""
    v = clean(v)
    if not v:
        return ''
    # Retire d'éventuels .0 (lecture pandas)
    v = re.sub(r'\.0+$', '', v)
    if not v.isdigit():
        return v
    return v.zfill(5)


def conv_code_commune(dept: str, commune: str) -> str:
    """
    dept (1-3 chars) + commune (1-3 chars int) → code_commune 5 chars.
    Ex: "75" + "104" → "75104"
        "01" + "289" → "01289"
        "974" + "1"  → "97401"
    """
    dept    = clean(dept)
    commune = re.sub(r'\.0+$', '', clean(commune))
    if not dept or not commune:
        return ''
    # Zero-pad commune pour compléter jusqu'à 5 chars total
    pad = max(0, 5 - len(dept))
    return dept + commune.zfill(pad)


def conv_id_parcelle(dept: str, commune: str, prefixe: str, section: str, no_plan: str) -> str:
    """
    Reconstruit l'id_parcelle 14 chars :
    code_commune(5) + prefixe_section(3) + section(2) + no_plan(4)
    """
    cc = conv_code_commune(dept, commune)
    if not cc:
        return ''
    pref = clean(prefixe).zfill(3)[:3]   # '000' par défaut
    sec  = clean(section).upper()[:2]
    np   = re.sub(r'\.0+$', '', clean(no_plan))
    np   = np.zfill(4)[:4] if np.isdigit() else np[:4]
    if not sec or not np:
        return ''
    return cc + pref + sec + np


def conv_adresse_nom_voie(type_voie: str, voie: str) -> str:
    """Fusionne Type de voie + Voie en un seul champ."""
    t = clean(type_voie).upper()
    v = clean(voie).upper()
    if t and v:
        return f"{t} {v}"
    return t or v


# Ordinals français pour arrondissements
def ordinal_fr(n: int) -> str:
    return '1er' if n == 1 else f"{n}e"


# Regex pour détecter "PARIS 01", "PARIS 1", "PARIS 15EM" etc.
_RE_VILLE_ARR = re.compile(
    r'^(PARIS|LYON|MARSEILLE)\s+(\d{1,2})(?:E(?:ME?)?)?$',
    re.IGNORECASE
)

# Mapping ville → (nom propre, code_dept_prefix pour validation)
_VILLE_MAP = {
    'PARIS':      ('Paris',      20),
    'LYON':       ('Lyon',        9),
    'MARSEILLE':  ('Marseille',  16),
}


def conv_nom_commune(v: str) -> str:
    """
    Normalise le nom de commune :
    - "PARIS 01"     → "Paris 1er Arrondissement"
    - "LYON 03"      → "Lyon 3e Arrondissement"
    - "MARSEILLE 16" → "Marseille 16e Arrondissement"
    - "LE HAVRE"     → "Le Havre"
    - "SAINT-NAZAIRE"→ "Saint-Nazaire"
    """
    v = clean(v).upper()
    if not v:
        return ''

    m = _RE_VILLE_ARR.match(v)
    if m:
        ville_key = m.group(1).upper()
        arr       = int(m.group(2))
        ville_nom, arr_max = _VILLE_MAP.get(ville_key, (ville_key.title(), 99))
        if 1 <= arr <= arr_max:
            return f"{ville_nom} {ordinal_fr(arr)} Arrondissement"

    # Titre case général : "LE HAVRE" → "Le Havre", "SAINT-NAZAIRE" → "Saint-Nazaire"
    return v.title()


# ─── Conversion d'un fichier ─────────────────────────────────────────────────

def convert_file(year: int) -> dict:
    src = BASE_DIR / INPUT_PATTERN.format(year=year)
    dst = BASE_DIR / OUTPUT_PATTERN.format(year=year)

    if not src.exists():
        return {'annee': year, 'ok': False, 'msg': f"Source introuvable : {src.name}"}
    if dst.exists():
        return {'annee': year, 'ok': False, 'msg': f"Déjà converti : {dst.name} (supprimer pour relancer)"}

    t0 = time.time()
    print(f"\n── {year} ─────────────────────────────────────────────────")
    print(f"  Source : {src.name}  ({src.stat().st_size / 1_048_576:.0f} Mo)")

    rows_in   = 0
    rows_out  = 0
    rows_skip = 0

    with (src.open('r', encoding='utf-8', errors='replace', newline='') as fin,
          dst.open('w', encoding='utf-8', newline='') as fout):

        reader = csv.DictReader(fin, delimiter=INPUT_DELIMITER)
        # Normalise les noms de colonnes (retire BOM éventuel)
        reader.fieldnames = [clean(f) for f in (reader.fieldnames or [])]

        writer = csv.DictWriter(fout, fieldnames=OUTPUT_COLS, lineterminator='\n')
        writer.writeheader()

        buffer = []

        for i, row in enumerate(reader, start=1):
            rows_in += 1

            # ── id_mutation ──────────────────────────────────────────
            id_doc = clean(row.get('Identifiant de document', ''))
            id_mut = id_doc if id_doc else f"LEGACY-{year}-{i}"

            # ── date_mutation ────────────────────────────────────────
            date = conv_date(row.get('Date mutation', ''))
            if not date:
                rows_skip += 1
                continue   # date invalide → ligne inutilisable

            # ── valeur_fonciere ──────────────────────────────────────
            valeur = conv_decimal(row.get('Valeur fonciere', ''))

            # ── adresse ──────────────────────────────────────────────
            adresse_nom_voie = conv_adresse_nom_voie(
                row.get('Type de voie', ''),
                row.get('Voie', '')
            )

            # ── commune / code ───────────────────────────────────────
            dept    = clean(row.get('Code departement', ''))
            commune = clean(row.get('Code commune', ''))
            cp      = conv_code_postal(row.get('Code postal', ''))
            cc      = conv_code_commune(dept, commune)
            nom_com = conv_nom_commune(row.get('Commune', ''))

            # ── id_parcelle ──────────────────────────────────────────
            id_parc = conv_id_parcelle(
                dept,
                commune,
                row.get('Prefixe de section', ''),
                row.get('Section', ''),
                row.get('No plan', ''),
            )

            # ── surfaces ─────────────────────────────────────────────
            lot1_sc = conv_surface(row.get('Surface Carrez du 1er lot', ''))
            lot2_sc = conv_surface(row.get('Surface Carrez du 2eme lot', ''))
            lot3_sc = conv_surface(row.get('Surface Carrez du 3eme lot', ''))
            lot4_sc = conv_surface(row.get('Surface Carrez du 4eme lot', ''))
            lot5_sc = conv_surface(row.get('Surface Carrez du 5eme lot', ''))
            surf_r  = conv_surface(row.get('Surface reelle bati', ''))
            surf_t  = conv_surface(row.get('Surface terrain', ''))

            # ── écriture ─────────────────────────────────────────────
            buffer.append({
                'id_mutation'               : id_mut,
                'date_mutation'             : date,
                'numero_disposition'        : clean(row.get('No disposition', '')),
                'nature_mutation'           : clean(row.get('Nature mutation', '')),
                'valeur_fonciere'           : valeur,
                'adresse_numero'            : clean(row.get('No voie', '')),
                'adresse_suffixe'           : clean(row.get('B/T/Q', '')),
                'adresse_nom_voie'          : adresse_nom_voie,
                'adresse_code_voie'         : clean(row.get('Code voie', '')),
                'code_postal'               : cp,
                'code_commune'              : cc,
                'nom_commune'               : nom_com,
                'code_departement'          : dept,
                'ancien_code_commune'       : '',
                'ancien_nom_commune'        : '',
                'id_parcelle'               : id_parc,
                'ancien_id_parcelle'        : '',
                'numero_volume'             : clean(row.get('No Volume', '')),
                'lot1_numero'               : clean(row.get('1er lot', '')),
                'lot1_surface_carrez'       : lot1_sc,
                'lot2_numero'               : clean(row.get('2eme lot', '')),
                'lot2_surface_carrez'       : lot2_sc,
                'lot3_numero'               : clean(row.get('3eme lot', '')),
                'lot3_surface_carrez'       : lot3_sc,
                'lot4_numero'               : clean(row.get('4eme lot', '')),
                'lot4_surface_carrez'       : lot4_sc,
                'lot5_numero'               : clean(row.get('5eme lot', '')),
                'lot5_surface_carrez'       : lot5_sc,
                'nombre_lots'               : clean(row.get('Nombre de lots', '')),
                'code_type_local'           : clean(row.get('Code type local', '')),
                'type_local'                : clean(row.get('Type local', '')),
                'surface_reelle_bati'       : surf_r,
                'nombre_pieces_principales' : clean(row.get('Nombre pieces principales', '')),
                'code_nature_culture'       : '',   # absent de l'ancien format
                'nature_culture'            : clean(row.get('Nature culture', '')),
                'code_nature_culture_speciale': '',
                'nature_culture_speciale'   : clean(row.get('Nature culture speciale', '')),
                'surface_terrain'           : surf_t,
                'longitude'                 : '',   # absent de l'ancien format
                'latitude'                  : '',
            })
            rows_out += 1

            if len(buffer) >= BATCH_SIZE:
                writer.writerows(buffer)
                buffer.clear()
                print(f"  {rows_out:>8,} lignes converties...", end='\r')

        if buffer:
            writer.writerows(buffer)

    elapsed = time.time() - t0
    print(f"  {rows_out:>8,} lignes converties  ({rows_skip} ignorées)  — {elapsed:.1f}s")

    return {
        'annee'   : year,
        'ok'      : True,
        'entree'  : rows_in,
        'sortie'  : rows_out,
        'ignores' : rows_skip,
        'duree_s' : round(elapsed, 1),
        'fichier' : dst.name,
        'taille'  : f"{dst.stat().st_size / 1_048_576:.0f} Mo",
    }


# ─── Main ───────────────────────────────────────────────────────────────────

def main():
    args  = sys.argv[1:]
    all_y = '--all' in args
    args  = [a for a in args if not a.startswith('-')]

    if args:
        years = [int(a) for a in args if a.isdigit()]
    elif all_y:
        years = list(range(2014, 2025))   # 2014 → 2024
    else:
        years = list(range(2014, 2020))   # 2014 → 2019 par défaut

    print("=" * 65)
    print(f"  convert_legacy_csv.py — {len(years)} fichier(s)")
    print("  Sortie : format identique à ValeursFoncieres-2025_geoloc.csv")
    print("=" * 65)

    results = []
    for year in years:
        r = convert_file(year)
        results.append(r)
        if not r['ok']:
            print(f"\n[SKIP] {year} — {r['msg']}")

    ok = [r for r in results if r['ok']]
    if ok:
        print("\n" + "=" * 65)
        print(f"  {'Année':<6} {'Entrée':>9} {'Sortie':>9} {'Ignorés':>8} {'Durée':>7} {'Taille':>8}")
        print("  " + "─" * 58)
        for r in ok:
            print(f"  {r['annee']:<6} {r['entree']:>9,} {r['sortie']:>9,} "
                  f"{r['ignores']:>8,} {r['duree_s']:>6.1f}s {r['taille']:>8}")
        print("  " + "─" * 58)
        print(f"  {'TOTAL':<6} {sum(r['entree'] for r in ok):>9,} "
              f"{sum(r['sortie'] for r in ok):>9,}")
        print("=" * 65)
        print(f"\nFichiers _v6.csv prêts dans : {BASE_DIR}")
        print("Import : python3 import_dvf_france.py ValeursFoncieres-YYYY_v6.csv")


if __name__ == '__main__':
    main()
