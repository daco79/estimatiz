#!/usr/bin/env python3
"""
Importer un fichier CSV (séparé par |) dans la table `data_paris_I`.
Le CSV ne contient pas la colonne `Id_Data` (auto-incrément MySQL).
- Lecture UTF-8 avec BOM géré
- Mapping robuste CSV -> colonnes SQL
- Normalisation AVANT insertion : No voie, Code type local,
  Nombre pieces principales, Surface reelle bati
"""

import mysql.connector as mysql
import csv
import time
import os
import sys
import argparse
import re

# === PARAMÈTRES À PERSONNALISER ==============================
DB_HOST = "127.0.0.1"
DB_PORT = 3306
DB_USER = "root"
DB_PASSWORD = ""          # ← mets ton mot de passe ici
DB_NAME = "CSV_DB 6"
TABLE_NAME = "data_paris_I"
CSV_PATH = "ValeursFoncieres-2025_75_sans_doublon.csv"  # ← chemin du CSV (gardé en dur)
CSV_DELIMITER = "|"
BATCH_SIZE = 2000
# =============================================================

def info(msg):
    print(f"[INFO] {msg}", flush=True)

def die(msg):
    print(f"[ERREUR] {msg}", file=sys.stderr)
    sys.exit(1)

def connect_db():
    try:
        conn = mysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME,
            autocommit=False,
            allow_local_infile=False
        )
        return conn
    except Exception as e:
        die(f"Impossible de se connecter à la base MySQL: {e}")

def fetch_table_columns(conn):
    sql = """
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s
        ORDER BY ORDINAL_POSITION
    """
    with conn.cursor() as cur:
        cur.execute(sql, (DB_NAME, TABLE_NAME))
        rows = cur.fetchall()
    if not rows:
        die(f"Aucune colonne trouvée pour la table {TABLE_NAME}")
    return [r[0] for r in rows]

def drop_id_column(cols, id_name="Id_Data"):
    return [c for c in cols if c.lower() != id_name.lower()]

def make_placeholders(n):
    return "(" + ",".join(["%s"] * n) + ")"

def import_csv(conn, batch_size=None, debug=False):
    # --- normaliseurs demandés (appliqués AVANT insertion) ---
    def norm_no_voie(val: str) -> str | None:
        if val is None: return None
        s = str(val).strip().replace(',', '.')
        if s == "": return None
        m = re.match(r'^(\d+)(?:\.0+)?$', s)   # 9001.0 -> 9001
        if m: return m.group(1)
        try:
            return str(int(float(s)))          # 12.5 -> 12 (à adapter si tu veux arrondir différemment)
        except:
            return s[:-2] if s.endswith('.0') else s

    def norm_code_type_local(val: str) -> str | None:
        if val is None: return None
        s = str(val).strip().replace(',', '.')
        if s == "": return None
        if re.match(r'^\d', s):
            try: return str(int(float(s)))     # 1.0 -> "1"
            except: pass
        m = re.search(r'([A-Za-z0-9])', s)
        return m.group(1).upper() if m else s[:1].upper()

    def norm_nb_pieces(val: str) -> str | None:
        if val is None: return None
        s = str(val).strip().replace(',', '.')
        if s == "": return None
        try:
            return str(int(float(s)))          # 3.0 -> "3"
        except:
            m = re.search(r'(\d+)', s)
            return m.group(1) if m else s

    def norm_surface_reelle(val: str) -> str | None:
        if val is None: return None
        s = str(val).strip().replace(',', '.')
        if s == "": return None
        try:
            f = float(s)
            if abs(f - int(f)) < 1e-9:
                return str(int(f))             # 100.0 -> "100"
            s2 = f"{f:.2f}"
            s2 = re.sub(r'(\.\d*?[1-9])0+$', r'\1', s2)  # 12.50 -> 12.5
            s2 = s2.rstrip('0').rstrip('.')             # 12.40 -> 12.4 ; 12.00 -> 12
            return s2
        except:
            return s[:-2] if s.endswith('.0') else s

    # colonnes SQL -> fonction de normalisation
    NORMALIZERS = {
        'No voie': norm_no_voie,
        'Code type local': norm_code_type_local,
        'Nombre pieces principales': norm_nb_pieces,
        'Surface reelle bati': norm_surface_reelle,
    }

    start = time.time()
    table_cols = fetch_table_columns(conn)
    table_cols_wo_id = drop_id_column(table_cols)
    info(f"Colonnes de la table (sans Id_Data): {table_cols_wo_id}")

    # lecture du CSV (UTF-8 *avec* BOM géré)
    with open(CSV_PATH, "r", encoding="utf-8-sig", newline="") as f:
        reader = csv.reader(f, delimiter=CSV_DELIMITER)
        try:
            header = next(reader)
        except StopIteration:
            die("Le fichier CSV est vide.")

        # strip + enlève éventuel BOM restant
        header = [h.strip().lstrip("\ufeff") for h in header]
        info(f"En-têtes CSV détectés: {header}")

        # maps lowercase pour joindre CSV->SQL
        table_lower_map = {c.lower(): c for c in table_cols_wo_id}
        header_lower = [h.lower() for h in header]

        # check colonnes inconnues
        unknown = [h for h in header if h.lower() not in table_lower_map]
        if unknown:
            die(f"Colonnes CSV inconnues dans la table: {unknown}")

        # ordre d'insertion = colonnes SQL dans l'ordre des headers CSV présents
        ordered_cols = [table_lower_map[h.lower()] for h in header]
        missing_cols = [c for c in table_cols_wo_id if c not in ordered_cols]
        if missing_cols:
            info(f"Colonnes absentes du CSV (valeur par défaut/NULL): {missing_cols}")

        info(f"Ordre d’insertion : {ordered_cols}")

        # build mapping : colonne SQL -> index CSV (sécurisé)
        col_to_idx = {}
        for i, h in enumerate(header):
            sql_col = table_lower_map[h.lower()]
            col_to_idx[sql_col] = i

        placeholders = make_placeholders(len(ordered_cols))
        insert_sql = f"INSERT INTO `{TABLE_NAME}` ({', '.join('`'+c+'`' for c in ordered_cols)}) VALUES {placeholders}"

        total = 0
        batch = []
        file_line_no = 2  # first data line = 2
        if batch_size is None:
            batch_size = BATCH_SIZE

        for row in reader:
            # Construire la ligne *dans l'ordre des colonnes SQL à insérer*
            values = []
            for col in ordered_cols:
                idx = col_to_idx[col]  # index de la colonne dans la ligne CSV
                v = row[idx].strip() if idx < len(row) else ""
                v = None if v == "" else v

                # normalisation *par nom de colonne SQL* (avant insertion)
                norm_fn = NORMALIZERS.get(col)
                if norm_fn:
                    v = norm_fn(v)

                # chaîne vide -> NULL
                if isinstance(v, str) and v.strip() == "":
                    v = None

                values.append(v)

            batch.append(values)

            if len(batch) >= batch_size:
                with conn.cursor() as cur:
                    try:
                        cur.executemany(insert_sql, batch)
                    except Exception as e:
                        if debug:
                            err_file = 'import_error_rows.txt'
                            info(f"Erreur lors de l'executemany: {e}")
                            info(f"Dump des {len(batch)} lignes du batch dans {err_file}")
                            with open(err_file, 'w', encoding='utf-8') as ef:
                                for i, vals in enumerate(batch):
                                    ef.write(f"CSV_line={file_line_no - len(batch) + i}: {vals}\n")
                            die(f"Erreur durant l’import: {e} (batch dumped to {err_file})")
                        else:
                            raise
                conn.commit()
                total += len(batch)
                batch.clear()
                info(f"{total} lignes insérées...")

            file_line_no += 1

        # dernier batch
        if batch:
            with conn.cursor() as cur:
                try:
                    cur.executemany(insert_sql, batch)
                except Exception as e:
                    if debug:
                        err_file = 'import_error_rows.txt'
                        info(f"Erreur lors de l'executemany final: {e}")
                        info(f"Dump des {len(batch)} lignes restantes du batch dans {err_file}")
                        with open(err_file, 'w', encoding='utf-8') as ef:
                            for i, vals in enumerate(batch):
                                ef.write(f"CSV_line={file_line_no - len(batch) + i}: {vals}\n")
                        die(f"Erreur durant l’import: {e} (batch dumped to {err_file})")
                    else:
                        raise
            conn.commit()
            total += len(batch)

    elapsed = time.time() - start
    info(f"Import terminé. {total} lignes insérées en {elapsed:.2f} s.")

def main():
    info("Connexion à la base...")
    parser = argparse.ArgumentParser(description='Import CSV to MySQL')
    parser.add_argument('--debug', action='store_true', help='Mode debug: dump failing batch and stop')
    parser.add_argument('--batch-size', type=int, default=None, help='Override batch size')
    args = parser.parse_args()

    conn = connect_db()
    try:
        import_csv(conn, batch_size=args.batch_size, debug=args.debug)
    except Exception as e:
        conn.rollback()
        die(f"Erreur durant l’import: {e}")
    finally:
        conn.close()
        info("Connexion fermée.")

if __name__ == "__main__":
    main()
