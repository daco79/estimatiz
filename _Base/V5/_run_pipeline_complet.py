#!/usr/bin/env python3
"""
Script maître — à relancer à chaque mise à jour des données ou des règles.
Étapes :
  0. Vide la table MySQL (TRUNCATE)
  1. Supprime les anciens fichiers *_75.csv
  2. Filtre chaque fichier annuel → *_75.csv (codes postaux Paris 75001-75020)
  3. Pour chaque *_75.csv : dédup + priorité type local + filtre surface → validation → import MySQL
"""

# ============================================================
# CONFIG — à adapter si besoin
# ============================================================
DB_HOST     = "127.0.0.1"
DB_PORT     = 3306
DB_USER     = "root"
DB_PASSWORD = ""
DB_NAME     = "CSV_DB 6"
TABLE_NAME  = "data_paris_I"
CSV_DELIMITER = "|"
BATCH_SIZE    = 2000
CODES_PARIS   = [f"750{str(i).zfill(2)}" for i in range(1, 21)]
# ============================================================

import csv, re, sys, time, unicodedata
from datetime import datetime
from pathlib import Path

import mysql.connector as mysql
import pandas as pd

# ── Découverte fichiers DVF ─────────────────────────────────
SOURCE_RE = re.compile(r"valeursfoncieres-(20\d{2})\.csv$", re.IGNORECASE)
FILTERED_RE = re.compile(r"valeursfoncieres-(20\d{2})_75\.csv$", re.IGNORECASE)

def dvf_year(path: Path) -> int:
    for pattern in (SOURCE_RE, FILTERED_RE):
        m = pattern.fullmatch(path.name)
        if m:
            return int(m.group(1))
    return 0

# ── Helpers normalisation ────────────────────────────────────
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

# ── Constantes pipeline ──────────────────────────────────────
KEY_COLS = [
    'Date mutation', 'Valeur fonciere', 'No voie', 'B/T/Q',
    'Type de voie', 'Code voie', 'Voie', 'Code postal', 'Commune', '1er lot',
]
TYPE_LOCAL_PRIORITY = {
    'Appartement': 10,
    'Maison': 9,
    'Local industriel. commercial ou assimilé': 5,
    'Dépendance': 1,
}
# Colonnes surface : Surface reelle bati en priorité + Carrez 5 lots
SURFACE_COLS = [
    'Surface reelle bati',
    'Surface Carrez du 1er lot',
    'Surface Carrez du 2eme lot',
    'Surface Carrez du 3eme lot',
    'Surface Carrez du 4eme lot',
    'Surface Carrez du 5eme lot',
]
VALIDATION_RULES = {
    'Identifiant de document':      {'type': 'varchar', 'max_length': 10},
    'Reference document':           {'type': 'varchar', 'max_length': 10},
    '1 Articles CGI':               {'type': 'varchar', 'max_length': 10},
    '2 Articles CGI':               {'type': 'varchar', 'max_length': 10},
    '3 Articles CGI':               {'type': 'varchar', 'max_length': 10},
    '4 Articles CGI':               {'type': 'varchar', 'max_length': 10},
    '5 Articles CGI':               {'type': 'varchar', 'max_length': 10},
    'No disposition':               {'type': 'varchar', 'max_length': 6},
    'Date mutation':                {'type': 'date',    'max_length': 10},
    'Nature mutation':              {'type': 'varchar', 'max_length': 34},
    'Valeur fonciere':              {'type': 'varchar', 'max_length': 12},
    'No voie':                      {'type': 'varchar', 'max_length': 4},
    'B/T/Q':                        {'type': 'varchar', 'max_length': 1},
    'Type de voie':                 {'type': 'varchar', 'max_length': 4},
    'Code voie':                    {'type': 'varchar', 'max_length': 4},
    'Voie':                         {'type': 'varchar', 'max_length': 26},
    'Code postal':                  {'type': 'int',     'max_value': 99999},
    'Commune':                      {'type': 'varchar', 'max_length': 45},
    'Code departement':             {'type': 'varchar', 'max_length': 3},
    'Code commune':                 {'type': 'int',     'max_value': 999},
    'Prefixe de section':           {'type': 'varchar', 'max_length': 10},
    'Section':                      {'type': 'varchar', 'max_length': 2},
    'No plan':                      {'type': 'int',     'max_value': 9999},
    'No Volume':                    {'type': 'varchar', 'max_length': 6},
    '1er lot':                      {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 1er lot':    {'type': 'varchar', 'max_length': 7},
    '2eme lot':                     {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 2eme lot':   {'type': 'varchar', 'max_length': 7},
    '3eme lot':                     {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 3eme lot':   {'type': 'varchar', 'max_length': 7},
    '4eme lot':                     {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 4eme lot':   {'type': 'varchar', 'max_length': 7},
    '5eme lot':                     {'type': 'varchar', 'max_length': 6},
    'Surface Carrez du 5eme lot':   {'type': 'varchar', 'max_length': 7},
    'Nombre de lots':               {'type': 'int',     'max_value': 999},
    'Code type local':              {'type': 'varchar', 'max_length': 1},
    'Type local':                   {'type': 'varchar', 'max_length': 40},
    'Identifiant local':            {'type': 'varchar', 'max_length': 10},
    'Surface reelle bati':          {'type': 'varchar', 'max_length': 5},
    'Nombre pieces principales':    {'type': 'varchar', 'max_length': 2},
    'Nature culture':               {'type': 'varchar', 'max_length': 2},
    'Nature culture speciale':      {'type': 'varchar', 'max_length': 10},
    'Surface terrain':              {'type': 'varchar', 'max_length': 7},
}

def validate_date(v):
    if pd.isna(v) or str(v).strip() == '': return True, None
    v = str(v).strip()
    if not re.match(r'^\d{2}/\d{2}/\d{4}$', v): return False, f"Format invalide: {v}"
    try: datetime.strptime(v, '%d/%m/%Y'); return True, None
    except: return False, f"Date invalide: {v}"

def validate_int(v, max_value=None):
    if pd.isna(v) or str(v).strip() == '': return True, None
    try:
        n = int(float(v))
        if max_value and n > max_value: return False, f"Trop grand (max {max_value}): {n}"
        return True, None
    except: return False, f"Pas un entier: {v}"

def validate_varchar(v, max_length):
    if pd.isna(v) or str(v).strip() == '': return True, None
    try: s = str(int(v)) if isinstance(v, float) and not pd.isna(v) else str(v)
    except: s = str(v)
    if len(s) > max_length: return False, f"Trop long (max {max_length}, actuel {len(s)}): '{s[:30]}'"
    return True, None

def _surface_valide(v: str) -> bool:
    """Retourne True si la valeur est une surface valide (non vide et > 0)."""
    s = v.strip().replace(',', '.')
    if not s:
        return False
    try:
        return float(s) > 0
    except ValueError:
        return False

# ════════════════════════════════════════════════════════════════
print("\n" + "█" * 60)
print("  PIPELINE MAÎTRE — IMPORT DVF PARIS")
print("█" * 60)
t_start = time.time()

# ── ÉTAPE 0 : TRUNCATE ──────────────────────────────────────
print("\n▶ ÉTAPE 0 — Vidage de la table MySQL")
try:
    conn = mysql.connect(host=DB_HOST, port=DB_PORT, user=DB_USER,
                         password=DB_PASSWORD, database=DB_NAME, autocommit=True)
    with conn.cursor() as cur:
        cur.execute(f"TRUNCATE TABLE `{TABLE_NAME}`")
    conn.close()
    print(f"  Table `{TABLE_NAME}` vidée.")
except Exception as e:
    print(f"  [ERREUR] {e}")
    sys.exit(1)

# ── ÉTAPE 1 : Suppression des anciens *_75.csv ─────────────
print("\n▶ ÉTAPE 1 — Suppression des anciens fichiers *_75.csv")
base_dir = Path(__file__).parent
deleted = sorted(
    (p for p in base_dir.iterdir() if p.is_file() and FILTERED_RE.fullmatch(p.name)),
    key=lambda p: (dvf_year(p), p.name.lower())
)
for f in deleted:
    f.unlink()
    print(f"  Supprimé : {f.name}")
if not deleted:
    print("  Aucun fichier à supprimer.")

# ── ÉTAPE 2 : Filtre par commune ────────────────────────────
print("\n▶ ÉTAPE 2 — Filtrage des fichiers annuels → *_75.csv")
sources = sorted(
    (p for p in base_dir.iterdir() if p.is_file() and SOURCE_RE.fullmatch(p.name)),
    key=lambda p: (dvf_year(p), p.name.lower())
)
if not sources:
    print("  [ERREUR] Aucun fichier source ValeursFoncieres-20XX.csv trouvé.")
    sys.exit(1)

filtered_files = []
for src in sources:
    out = src.with_name(src.stem + "_75.csv")
    df = pd.read_csv(src, sep=CSV_DELIMITER, dtype=str, header=0,
                     encoding='utf-8', on_bad_lines='skip')
    df.columns = [c.strip() for c in df.columns]
    df = df.map(lambda x: x.strip() if isinstance(x, str) else x)
    df_filtre = df[df["Code postal"].isin(CODES_PARIS)]
    df_filtre.to_csv(out, sep=CSV_DELIMITER, index=False, encoding='utf-8')
    print(f"  {src.name} → {out.name} ({len(df_filtre)} lignes)")
    filtered_files.append(out)

# ── ÉTAPE 3 : Pipeline par fichier ──────────────────────────
print("\n▶ ÉTAPE 3 — Dédup + validation + import MySQL")

grand_total = 0
summary = []

conn = mysql.connect(host=DB_HOST, port=DB_PORT, user=DB_USER,
                     password=DB_PASSWORD, database=DB_NAME, autocommit=False)
with conn.cursor() as cur:
    cur.execute("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS "
                "WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s ORDER BY ORDINAL_POSITION",
                (DB_NAME, TABLE_NAME))
    table_cols = [r[0] for r in cur.fetchall() if r[0].lower() != 'id_data']
conn.close()

NORMALIZERS_SQL = {
    'No voie': norm_no_voie,
    'Code type local': norm_code_type_local,
    'Nombre pieces principales': norm_nb_pieces,
    'Surface reelle bati': norm_surface,
}

for src in filtered_files:
    t0 = time.time()
    print(f"\n  {'─'*54}")
    print(f"  {src.name}")
    print(f"  {'─'*54}")

    # 3a — Dédup + priorité type local + surface reele bati + filtre surface
    with src.open('r', encoding='utf-8', errors='replace', newline='') as fin:
        reader = csv.reader(fin, delimiter=CSV_DELIMITER)
        header = next(reader)
        if header: header[0] = header[0].lstrip('\ufeff')
        all_rows = list(reader)

    key_indices      = [header.index(c) for c in KEY_COLS if c in header]
    type_local_idx   = header.index('Type local') if 'Type local' in header else None
    surface_reelle_idx = header.index('Surface reelle bati') if 'Surface reelle bati' in header else None
    surface_indices  = [header.index(c) for c in SURFACE_COLS if c in header]

    best_row = {}
    for idx, row in enumerate(all_rows):
        key = tuple(row[i] for i in key_indices)
        rank = TYPE_LOCAL_PRIORITY.get(row[type_local_idx].strip(), 3) if type_local_idx is not None else 0
        has_surface = 1 if (surface_reelle_idx is not None and _surface_valide(row[surface_reelle_idx])) else 0
        score = (rank, has_surface)
        if key not in best_row or score > best_row[key][0]:
            best_row[key] = (score, idx, row)

    kept_indices = {idx for (_, idx, _) in best_row.values()}
    rows_read = len(all_rows)
    rows_dup = rows_no_surface = rows_kept = 0
    final_rows = []

    for idx, row in enumerate(all_rows):
        if idx not in kept_indices:
            rows_dup += 1
            continue
        if surface_indices and not any(_surface_valide(row[i]) for i in surface_indices):
            rows_no_surface += 1
            continue
        final_rows.append(row)
        rows_kept += 1

    tmp = src.with_name(src.stem + "_tmp" + src.suffix)
    with tmp.open('w', encoding='utf-8', newline='') as fout:
        writer = csv.writer(fout, delimiter=CSV_DELIMITER)
        writer.writerow(header)
        writer.writerows(final_rows)
    tmp.replace(src)
    print(f"  [dédup]  lus:{rows_read}  doublons:{rows_dup}  sans surface:{rows_no_surface}  conservés:{rows_kept}")

    # 3b — Validation & normalisation
    df = pd.read_csv(str(src), sep=CSV_DELIMITER, encoding='utf-8-sig',
                     engine='python', dtype=str, keep_default_na=False)
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
            if not ok: errors.append({'ligne': i+2, 'colonne': col, 'valeur': v, 'erreur': msg})

    if errors:
        report = base_dir / '_validation_erreurs_csv.txt'
        with open(report, 'w', encoding='utf-8') as f:
            f.write(f"Fichier: {src.name}\n\n")
            for e in errors:
                f.write(f"Ligne {e['ligne']} | {e['colonne']} | {e['valeur']} | {e['erreur']}\n")
        print(f"  [valid]  {len(errors)} ERREURS → import annulé (voir _validation_erreurs_csv.txt)")
        summary.append({'fichier': src.name, 'lus': rows_read, 'doublons': rows_dup,
                        'no_surface': rows_no_surface, 'importes': 0, 'erreurs': len(errors)})
        continue

    df.to_csv(str(src), sep=CSV_DELIMITER, index=False, encoding='utf-8-sig')
    print(f"  [valid]  OK — 0 erreur")

    # 3c — Import MySQL
    conn = mysql.connect(host=DB_HOST, port=DB_PORT, user=DB_USER,
                         password=DB_PASSWORD, database=DB_NAME, autocommit=False)
    table_lower = {c.lower(): c for c in table_cols}
    total = 0
    batch = []

    with open(str(src), 'r', encoding='utf-8-sig', newline='') as f:
        reader = csv.reader(f, delimiter=CSV_DELIMITER)
        hdr = [h.strip().lstrip('\ufeff') for h in next(reader)]
        ordered_cols = [table_lower[h.lower()] for h in hdr if h.lower() in table_lower]
        col_to_idx   = {table_lower[h.lower()]: i for i, h in enumerate(hdr) if h.lower() in table_lower}
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
                with conn.cursor() as cur: cur.executemany(insert_sql, batch)
                conn.commit()
                total += len(batch)
                batch.clear()
        if batch:
            with conn.cursor() as cur: cur.executemany(insert_sql, batch)
            conn.commit()
            total += len(batch)

    conn.close()
    elapsed = time.time() - t0
    grand_total += total
    print(f"  [import] {total} lignes en {elapsed:.1f}s")
    summary.append({'fichier': src.name, 'lus': rows_read, 'doublons': rows_dup,
                    'no_surface': rows_no_surface, 'importes': total, 'erreurs': 0})

# ── RÉSUMÉ ──────────────────────────────────────────────────
t_total = time.time() - t_start
print(f"\n{'█'*60}")
print("  RÉSUMÉ FINAL")
print(f"{'█'*60}")
print(f"{'Fichier':<35} {'Lus':>7} {'Doublons':>9} {'Sans surf.':>10} {'Importés':>9}")
print("─" * 74)
for s in summary:
    flag = " ⚠ ERREURS" if s['erreurs'] else ""
    print(f"{s['fichier']:<35} {s['lus']:>7} {s['doublons']:>9} {s['no_surface']:>10} {s['importes']:>9}{flag}")
print("─" * 74)
print(f"{'TOTAL':<35} {sum(s['lus'] for s in summary):>7} "
      f"{sum(s['doublons'] for s in summary):>9} "
      f"{sum(s['no_surface'] for s in summary):>10} "
      f"{grand_total:>9}")
print(f"\n  Durée totale : {t_total:.0f}s")
print(f"{'█'*60}\n")
