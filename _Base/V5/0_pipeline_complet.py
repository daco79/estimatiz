#!/usr/bin/env python3
"""
Pipeline complet : dédoublonnage → validation/normalisation → import MySQL
Le fichier CSV source est modifié directement (pas de backup, pas de fichier intermédiaire).
"""

# ============================================================
# PARAMÈTRE À CHANGER
# ============================================================
CSV_FILE = "ValeursFoncieres-2024_75.csv"
# ============================================================

# --- Config MySQL ---
DB_HOST     = "127.0.0.1"
DB_PORT     = 3306
DB_USER     = "root"
DB_PASSWORD = ""
DB_NAME     = "CSV_DB 6"
TABLE_NAME  = "data_paris_I"

# --- Config CSV ---
CSV_DELIMITER = "|"
BATCH_SIZE    = 2000

# Colonnes clés pour détecter les doublons (même vente = même transaction)
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

# Priorité du type de bien : en cas de doublon, on garde le rang le plus élevé
# Appartement > Maison > autres > Dépendance
TYPE_LOCAL_PRIORITY = {
    'Appartement': 10,
    'Maison': 9,
    'Local industriel. commercial ou assimilé': 5,
    'Dépendance': 1,
}

# Colonnes surface : Carrez (5 lots) + surface réelle bâtie
SURFACE_CARREZ_COLS = [
    'Surface reelle bati',
    'Surface Carrez du 1er lot',
    'Surface Carrez du 2eme lot',
    'Surface Carrez du 3eme lot',
    'Surface Carrez du 4eme lot',
    'Surface Carrez du 5eme lot',
]

# ============================================================

import csv, re, sys, time, unicodedata
from datetime import datetime
from pathlib import Path

import mysql.connector as mysql
import pandas as pd

def _surface_valide(v: str) -> bool:
    """Retourne True si la valeur est une surface valide (non vide et > 0)."""
    s = v.strip().replace(',', '.')
    if not s:
        return False
    try:
        return float(s) > 0
    except ValueError:
        return False

src = Path(CSV_FILE)

# ────────────────────────────────────────────────────────────
# ÉTAPE 1 — Suppression des doublons (en place)
# ────────────────────────────────────────────────────────────
print("\n" + "=" * 60)
print("ÉTAPE 1 — Suppression des doublons")
print("=" * 60)

# Passe 1 : lecture complète en mémoire
with src.open('r', encoding='utf-8', errors='replace', newline='') as fin:
    reader = csv.reader(fin, delimiter=CSV_DELIMITER)
    header = next(reader)
    if header:
        header[0] = header[0].lstrip('\ufeff')
    all_rows = list(reader)

key_indices = [header.index(c) for c in KEY_COLS if c in header]
try:
    type_local_idx = header.index('Type local')
except ValueError:
    print("[ATTENTION] Colonne 'Type local' introuvable — priorité désactivée")
    type_local_idx = None
carrez_indices = [header.index(c) for c in SURFACE_CARREZ_COLS if c in header]

# Passe 2 : déduplication avec priorité Type local
# best_row[key] = (rank, original_index, row)
try:
    surface_reelle_idx = header.index('Surface reelle bati')
except ValueError:
    surface_reelle_idx = None

best_row: dict = {}
for idx, row in enumerate(all_rows):
    key = tuple(row[i] for i in key_indices)
    rank = TYPE_LOCAL_PRIORITY.get(row[type_local_idx].strip(), 3) if type_local_idx is not None else 0
    has_surface = 1 if (surface_reelle_idx is not None and _surface_valide(row[surface_reelle_idx])) else 0
    score = (rank, has_surface)
    if key not in best_row or score > best_row[key][0]:
        best_row[key] = (score, idx, row)

kept_indices = {idx for (_, idx, _) in best_row.values()}

# Passe 3 : filtre surface Carrez + comptages
rows_read = len(all_rows)
rows_removed = 0
rows_no_carrez = 0
rows_kept = 0
final_rows = []

for idx, row in enumerate(all_rows):
    if idx not in kept_indices:
        rows_removed += 1
        continue
    if carrez_indices and not any(_surface_valide(row[i]) for i in carrez_indices):
        rows_no_carrez += 1
        continue
    final_rows.append(row)
    rows_kept += 1

# Réécriture en place
tmp = src.with_name(src.stem + "_tmp" + src.suffix)
with tmp.open('w', encoding='utf-8', newline='') as fout:
    writer = csv.writer(fout, delimiter=CSV_DELIMITER)
    writer.writerow(header)
    writer.writerows(final_rows)
tmp.replace(src)

print(f"Lignes lues             : {rows_read}")
print(f"Doublons retirés        : {rows_removed}")
print(f"Sans surface Carrez     : {rows_no_carrez}")
print(f"Lignes conservées       : {rows_kept}")

# ────────────────────────────────────────────────────────────
# ÉTAPE 2 — Validation & normalisation
# ────────────────────────────────────────────────────────────
print("\n" + "=" * 60)
print("ÉTAPE 2 — Validation & normalisation")
print("=" * 60)

VALIDATION_RULES = {
    'Identifiant de document': {'type': 'varchar', 'max_length': 10},
    'Reference document': {'type': 'varchar', 'max_length': 10},
    '1 Articles CGI': {'type': 'varchar', 'max_length': 10},
    '2 Articles CGI': {'type': 'varchar', 'max_length': 10},
    '3 Articles CGI': {'type': 'varchar', 'max_length': 10},
    '4 Articles CGI': {'type': 'varchar', 'max_length': 10},
    '5 Articles CGI': {'type': 'varchar', 'max_length': 10},
    'No disposition': {'type': 'varchar', 'max_length': 6},
    'Date mutation': {'type': 'date', 'format': 'DD/MM/YYYY', 'max_length': 10},
    'Nature mutation': {'type': 'varchar', 'max_length': 34},
    'Valeur fonciere': {'type': 'varchar', 'max_length': 12},
    'No voie': {'type': 'varchar', 'max_length': 4},
    'B/T/Q': {'type': 'varchar', 'max_length': 1},
    'Type de voie': {'type': 'varchar', 'max_length': 4},
    'Code voie': {'type': 'varchar', 'max_length': 4},
    'Voie': {'type': 'varchar', 'max_length': 26},
    'Code postal': {'type': 'int', 'max_value': 99999},
    'Commune': {'type': 'varchar', 'max_length': 45},
    'Code departement': {'type': 'varchar', 'max_length': 3},
    'Code commune': {'type': 'int', 'max_value': 999},
    'Prefixe de section': {'type': 'varchar', 'max_length': 10},
    'Section': {'type': 'varchar', 'max_length': 2},
    'No plan': {'type': 'int', 'max_value': 9999},
    'No Volume': {'type': 'varchar', 'max_length': 6},
    '1er lot': {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 1er lot': {'type': 'varchar', 'max_length': 7},
    '2eme lot': {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 2eme lot': {'type': 'varchar', 'max_length': 7},
    '3eme lot': {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 3eme lot': {'type': 'varchar', 'max_length': 7},
    '4eme lot': {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 4eme lot': {'type': 'varchar', 'max_length': 7},
    '5eme lot': {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 5eme lot': {'type': 'varchar', 'max_length': 7},
    'Nombre de lots': {'type': 'int', 'max_value': 999},
    'Code type local': {'type': 'varchar', 'max_length': 1},
    'Type local': {'type': 'varchar', 'max_length': 40},
    'Identifiant local': {'type': 'varchar', 'max_length': 10},
    'Surface reelle bati': {'type': 'varchar', 'max_length': 5},
    'Nombre pieces principales': {'type': 'varchar', 'max_length': 2},
    'Nature culture': {'type': 'varchar', 'max_length': 2},
    'Nature culture speciale': {'type': 'varchar', 'max_length': 10},
    'Surface terrain': {'type': 'varchar', 'max_length': 7},
}

def _norm(s):
    if s is None: return ''
    return unicodedata.normalize('NFKC', str(s)).replace('\xa0', ' ').replace('\u202f', ' ').strip()

def norm_no_voie(v):
    s = _norm(v).replace(',', '.')
    if not s: return s
    m = re.match(r'^(\d+)(?:\.0+)?$', s)
    if m: return m.group(1)
    try: return str(int(float(s)))
    except: return s[:-2] if s.endswith('.0') else s

def norm_code_type_local(v):
    s = _norm(v).replace(',', '.')
    if not s: return s
    if re.match(r'^\d', s):
        try: return str(int(float(s)))
        except: pass
    m = re.search(r'([A-Za-z0-9])', s)
    return m.group(1).upper() if m else s[:1].upper()

def norm_nb_pieces(v):
    s = _norm(v).replace(',', '.')
    if not s: return s
    try: return str(int(float(s)))
    except:
        m = re.search(r'(\d+)', s)
        return m.group(1) if m else s

def norm_surface(v):
    s = _norm(v).replace(',', '.')
    if not s: return s
    try:
        f = float(s)
        if abs(f - int(f)) < 1e-9: return str(int(f))
        s2 = re.sub(r'(\.\d*?[1-9])0+$', r'\1', f"{f:.2f}")
        return s2.rstrip('0').rstrip('.')
    except: return s[:-2] if s.endswith('.0') else s

def validate_date(v):
    if pd.isna(v) or str(v).strip() == '': return True, None
    v = str(v).strip()
    if not re.match(r'^\d{2}/\d{2}/\d{4}$', v):
        return False, f"Format invalide (attendu JJ/MM/AAAA): {v}"
    try: datetime.strptime(v, '%d/%m/%Y'); return True, None
    except: return False, f"Date invalide: {v}"

def validate_int(v, max_value=None):
    if pd.isna(v) or str(v).strip() == '': return True, None
    try:
        n = int(float(v))
        if max_value and n > max_value:
            return False, f"Trop grand (max {max_value}): {n}"
        return True, None
    except: return False, f"Pas un entier: {v}"

def validate_varchar(v, max_length):
    if pd.isna(v) or str(v).strip() == '': return True, None
    try:
        s = str(int(v)) if isinstance(v, float) and not pd.isna(v) else str(v)
    except: s = str(v)
    if len(s) > max_length:
        return False, f"Trop long (max {max_length}, actuel {len(s)}): '{s[:50]}'"
    return True, None

df = pd.read_csv(str(src), sep=CSV_DELIMITER, encoding='utf-8-sig',
                 engine='python', dtype=str, keep_default_na=False)
print(f"Lignes chargées : {len(df)}")

for col, fn in [('No voie', norm_no_voie), ('Code type local', norm_code_type_local),
                ('Nombre pieces principales', norm_nb_pieces),
                ('Surface reelle bati', norm_surface), ('Surface terrain', norm_surface)]:
    if col in df.columns:
        df[col] = df[col].apply(fn)

errors = []
for i, (_, row) in enumerate(df.iterrows()):
    for col, rules in VALIDATION_RULES.items():
        if col not in df.columns: continue
        v = row[col]
        if rules['type'] == 'int':    ok, msg = validate_int(v, rules.get('max_value'))
        elif rules['type'] == 'date': ok, msg = validate_date(v)
        else:                         ok, msg = validate_varchar(v, rules['max_length'])
        if not ok:
            errors.append({'ligne': i + 2, 'colonne': col, 'valeur': v, 'erreur': msg})

print(f"Erreurs trouvées: {len(errors)}")
if errors:
    report = src.with_name('_validation_erreurs_csv.txt')
    with open(report, 'w', encoding='utf-8') as f:
        f.write(f"Rapport du {datetime.now():%d/%m/%Y %H:%M:%S}\n")
        f.write(f"Fichier: {src}\n\n")
        for e in errors:
            f.write(f"Ligne {e['ligne']} | {e['colonne']} | {e['valeur']} | {e['erreur']}\n")
    print(f"Rapport écrit : {report}")
    print("Import annulé à cause des erreurs.")
    sys.exit(1)

# Réécrire le CSV normalisé sur le fichier original
df.to_csv(str(src), sep=CSV_DELIMITER, index=False, encoding='utf-8-sig')
print("Fichier normalisé sauvegardé.")

# ────────────────────────────────────────────────────────────
# ÉTAPE 3 — Import MySQL
# ────────────────────────────────────────────────────────────
print("\n" + "=" * 60)
print("ÉTAPE 3 — Import MySQL")
print("=" * 60)

NORMALIZERS_SQL = {
    'No voie': norm_no_voie,
    'Code type local': norm_code_type_local,
    'Nombre pieces principales': norm_nb_pieces,
    'Surface reelle bati': norm_surface,
}

try:
    conn = mysql.connect(host=DB_HOST, port=DB_PORT, user=DB_USER,
                         password=DB_PASSWORD, database=DB_NAME, autocommit=False)
except Exception as e:
    print(f"[ERREUR] Connexion MySQL impossible: {e}")
    sys.exit(1)

with conn.cursor() as cur:
    cur.execute("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS "
                "WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION",
                (DB_NAME, TABLE_NAME))
    table_cols = [r[0] for r in cur.fetchall() if r[0].lower() != 'id_data']

start = time.time()
total = 0
batch = []

with open(str(src), 'r', encoding='utf-8-sig', newline='') as f:
    reader = csv.reader(f, delimiter=CSV_DELIMITER)
    header = [h.strip().lstrip('\ufeff') for h in next(reader)]
    table_lower = {c.lower(): c for c in table_cols}
    ordered_cols = [table_lower[h.lower()] for h in header if h.lower() in table_lower]
    col_to_idx   = {table_lower[h.lower()]: i for i, h in enumerate(header) if h.lower() in table_lower}
    insert_sql   = (f"INSERT INTO `{TABLE_NAME}` "
                    f"({', '.join('`'+c+'`' for c in ordered_cols)}) "
                    f"VALUES ({', '.join(['%s']*len(ordered_cols))})")

    for row in reader:
        values = []
        for col in ordered_cols:
            v = row[col_to_idx[col]].strip() if col_to_idx[col] < len(row) else ''
            v = None if v == '' else v
            fn = NORMALIZERS_SQL.get(col)
            if fn: v = fn(v)
            if isinstance(v, str) and v.strip() == '': v = None
            values.append(v)
        batch.append(values)

        if len(batch) >= BATCH_SIZE:
            with conn.cursor() as cur:
                cur.executemany(insert_sql, batch)
            conn.commit()
            total += len(batch)
            batch.clear()
            print(f"  {total} lignes insérées...")

    if batch:
        with conn.cursor() as cur:
            cur.executemany(insert_sql, batch)
        conn.commit()
        total += len(batch)

conn.close()
elapsed = time.time() - start

print(f"\n{'=' * 60}")
print("PIPELINE TERMINÉ")
print(f"{'=' * 60}")
print(f"Doublons supprimés      : {rows_removed}")
print(f"Sans surface Carrez     : {rows_no_carrez}")
print(f"Erreurs validation      : 0")
print(f"Lignes importées        : {total}")
print(f"Durée import            : {elapsed:.2f} s")
