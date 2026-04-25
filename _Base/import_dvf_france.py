#!/usr/bin/env python3
"""
import_dvf_france.py — Import du CSV géolocalisé dans DVF_France.dvf_france
Fichier source : ValeursFoncieres-2025_geoloc.csv (ou tout CSV au format nouveau)

Usage :
    python3 import_dvf_france.py                        # importe le CSV 2025 par défaut
    python3 import_dvf_france.py fichier.csv [--truncate]
      --truncate  : vide la table avant d'importer (défaut : append)

Pré-requis :
    pip install mysql-connector-python
    La base DVF_France et la table dvf_france doivent exister (cf. create_dvf_france.sql)
"""

import sys
import csv
import os
import time
import decimal
from datetime import date
import mysql.connector

# ─── Configuration ────────────────────────────────────────────────────────────
DB_CONFIG = {
    'host':     'localhost',
    'port':     3306,
    'user':     'root',
    'password': '',
    'database': 'DVF_France',
    'charset':  'utf8mb4',
    'use_pure': True,
}

BASE_DIR    = os.path.dirname(os.path.abspath(__file__))
DEFAULT_CSV = os.path.join(BASE_DIR, 'ValeursFoncieres-2025_geoloc.csv')
BATCH_SIZE  = 5000   # lignes par commit

# ─── Colonnes CSV → colonnes table (dans l'ordre du INSERT) ───────────────────
CSV_COLUMNS = [
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

INSERT_SQL = (
    "INSERT INTO dvf_france ("
    + ", ".join(CSV_COLUMNS)
    + ") VALUES ("
    + ", ".join(["%s"] * len(CSV_COLUMNS))
    + ")"
)

# ─── Helpers de conversion ────────────────────────────────────────────────────

def to_str(v):
    """Retourne None si vide, sinon la valeur telle quelle."""
    v = v.strip()
    return v if v else None

def to_date(v):
    """YYYY-MM-DD → date object, ou None."""
    v = v.strip()
    if not v:
        return None
    try:
        return date.fromisoformat(v)
    except ValueError:
        return None

def to_decimal(v):
    """Chaîne numérique (point décimal) → Decimal, ou None."""
    v = v.strip()
    if not v:
        return None
    try:
        return decimal.Decimal(v)
    except decimal.InvalidOperation:
        return None

def to_int(v):
    """Chaîne entière → int, ou None."""
    v = v.strip()
    if not v:
        return None
    try:
        return int(v)
    except ValueError:
        return None

def row_to_params(r):
    """Convertit un dict CSV en tuple de paramètres pour l'INSERT."""
    return (
        to_str(r['id_mutation']),
        to_date(r['date_mutation']),
        to_str(r['numero_disposition']),
        to_str(r['nature_mutation']),
        to_decimal(r['valeur_fonciere']),
        to_str(r['adresse_numero']),
        to_str(r['adresse_suffixe']),
        to_str(r['adresse_nom_voie']),
        to_str(r['adresse_code_voie']),
        to_str(r['code_postal']),
        to_str(r['code_commune']),
        to_str(r['nom_commune']),
        to_str(r['code_departement']),
        to_str(r['ancien_code_commune']),
        to_str(r['ancien_nom_commune']),
        to_str(r['id_parcelle']),
        to_str(r['ancien_id_parcelle']),
        to_str(r['numero_volume']),
        to_str(r['lot1_numero']),
        to_decimal(r['lot1_surface_carrez']),
        to_str(r['lot2_numero']),
        to_decimal(r['lot2_surface_carrez']),
        to_str(r['lot3_numero']),
        to_decimal(r['lot3_surface_carrez']),
        to_str(r['lot4_numero']),
        to_decimal(r['lot4_surface_carrez']),
        to_str(r['lot5_numero']),
        to_decimal(r['lot5_surface_carrez']),
        to_int(r['nombre_lots']),
        to_str(r['code_type_local']),
        to_str(r['type_local']),
        to_decimal(r['surface_reelle_bati']),
        to_int(r['nombre_pieces_principales']),
        to_str(r['code_nature_culture']),
        to_str(r['nature_culture']),
        to_str(r['code_nature_culture_speciale']),
        to_str(r['nature_culture_speciale']),
        to_decimal(r['surface_terrain']),
        to_decimal(r['longitude']),
        to_decimal(r['latitude']),
    )

# ─── Import principal ─────────────────────────────────────────────────────────

def import_csv(csv_path, truncate=False):
    if not os.path.exists(csv_path):
        print(f"[ERREUR] Fichier introuvable : {csv_path}")
        sys.exit(1)

    file_size = os.path.getsize(csv_path)
    print(f"Fichier  : {csv_path}")
    print(f"Taille   : {file_size / 1_048_576:.1f} Mo")

    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()

    if truncate:
        print("TRUNCATE TABLE dvf_france ...")
        cursor.execute("TRUNCATE TABLE dvf_france")
        conn.commit()

    t0 = time.time()
    total   = 0
    errors  = 0
    batch   = []

    with open(csv_path, newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)

        # Vérifier que les colonnes attendues sont présentes
        missing = [c for c in CSV_COLUMNS if c not in reader.fieldnames]
        if missing:
            print(f"[ERREUR] Colonnes manquantes dans le CSV : {missing}")
            print(f"Colonnes trouvées : {reader.fieldnames}")
            sys.exit(1)

        for line_no, row in enumerate(reader, start=2):  # line 1 = header
            try:
                batch.append(row_to_params(row))
            except Exception as e:
                errors += 1
                if errors <= 5:
                    print(f"  [WARN] ligne {line_no} ignorée : {e} — {dict(list(row.items())[:4])}")
                continue

            if len(batch) >= BATCH_SIZE:
                cursor.executemany(INSERT_SQL, batch)
                conn.commit()
                total += len(batch)
                batch = []
                elapsed = time.time() - t0
                rate = total / elapsed if elapsed > 0 else 0
                print(f"  {total:>8,} lignes importées  ({rate:,.0f} lignes/s)", end='\r')

        # Dernier batch
        if batch:
            cursor.executemany(INSERT_SQL, batch)
            conn.commit()
            total += len(batch)

    elapsed = time.time() - t0
    rate = total / elapsed if elapsed > 0 else 0
    print(f"\n{'─'*60}")
    print(f"Import terminé en {elapsed:.1f}s")
    print(f"Lignes importées : {total:,}")
    print(f"Erreurs ignorées : {errors}")
    print(f"Débit moyen      : {rate:,.0f} lignes/s")

    # Vérification rapide
    cursor.execute("SELECT COUNT(*) FROM dvf_france")
    count_total = cursor.fetchone()[0]
    cursor.execute("SELECT COUNT(*) FROM dvf_france WHERE date_mutation IS NOT NULL AND valeur_fonciere IS NOT NULL")
    count_valid = cursor.fetchone()[0]
    cursor.execute("SELECT COUNT(*) FROM dvf_france WHERE longitude IS NOT NULL")
    count_geo = cursor.fetchone()[0]
    cursor.execute("SELECT MIN(date_mutation), MAX(date_mutation) FROM dvf_france")
    date_range = cursor.fetchone()

    print(f"\nVérification :")
    print(f"  Total lignes      : {count_total:,}")
    print(f"  Avec date+valeur  : {count_valid:,}")
    print(f"  Géolocalisées     : {count_geo:,}")
    print(f"  Période           : {date_range[0]} → {date_range[1]}")

    cursor.close()
    conn.close()


def main():
    args = sys.argv[1:]
    truncate = '--truncate' in args
    args = [a for a in args if not a.startswith('--')]
    csv_path = args[0] if args else DEFAULT_CSV
    import_csv(csv_path, truncate=truncate)


if __name__ == '__main__':
    main()
