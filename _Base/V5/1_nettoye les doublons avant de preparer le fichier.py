import csv
from pathlib import Path

# === PARAMÈTRES EN DUR ===
SRC = Path(r"ValeursFoncieres-2024.csv")   # ← change ça
DELIMITER = '|'                                # pipe comme dans ton CSV
MAKE_REMOVED_LOG = True                        # False pour ne pas générer l'audit

# Colonnes utilisées pour détecter les doublons (même vente = même transaction)
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

def has_any_carrez(row: list, carrez_indices: list) -> bool:
    """Retourne True si au moins un lot a une surface valide (non vide et > 0)."""
    for i in carrez_indices:
        s = row[i].strip().replace(',', '.')
        if not s:
            continue
        try:
            if float(s) > 0:
                return True
        except ValueError:
            pass
    return False

def type_local_rank(row: list, type_local_idx: int) -> int:
    """Retourne la priorité du type local de la ligne."""
    return TYPE_LOCAL_PRIORITY.get(row[type_local_idx].strip(), 3)

def dedup_by_key(src: Path, suffix: str = "_sans_doublon", delimiter: str = '|', make_removed_log: bool = True):
    # Construit le nom de sortie : insère le suffixe avant l'extension
    if src.suffix:
        dst = src.with_name(src.stem + suffix + src.suffix)
        log = src.with_name(src.stem + suffix + "_removed" + src.suffix)
    else:
        dst = src.with_name(src.name + suffix)
        log = src.with_name(src.name + suffix + "_removed")

    # === PASSE 1 : lecture complète en mémoire ===
    with src.open('r', encoding='utf-8', errors='replace', newline='') as fin:
        reader = csv.reader(fin, delimiter=delimiter)
        header = next(reader)
        if header:
            header[0] = header[0].lstrip('\ufeff')
        all_rows = list(reader)

    # Index des colonnes
    key_indices = []
    for col in KEY_COLS:
        try:
            key_indices.append(header.index(col))
        except ValueError:
            print(f"[ATTENTION] Colonne clé introuvable dans le CSV : '{col}'")

    try:
        type_local_idx = header.index('Type local')
    except ValueError:
        print("[ATTENTION] Colonne 'Type local' introuvable — priorité désactivée")
        type_local_idx = None

    carrez_indices = []
    for col in SURFACE_CARREZ_COLS:
        try:
            carrez_indices.append(header.index(col))
        except ValueError:
            pass  # colonne absente = ignorée

    # === PASSE 2 : déduplication par clé avec priorité Type local ===
    # best_row[key] = (rank, original_row_index, row)
    try:
        surface_reelle_idx = header.index('Surface reelle bati')
    except ValueError:
        surface_reelle_idx = None

    best_row: dict[tuple, tuple] = {}
    for idx, row in enumerate(all_rows):
        key = tuple(row[i] for i in key_indices)
        rank = type_local_rank(row, type_local_idx) if type_local_idx is not None else 0
        has_surface = 1 if (surface_reelle_idx is not None and float(row[surface_reelle_idx].strip().replace(',', '.') or 0) > 0) else 0
        score = (rank, has_surface)
        if key not in best_row or score > best_row[key][0]:
            best_row[key] = (score, idx, row)

    kept_indices = {idx for (_, idx, _) in best_row.values()}

    # === PASSE 3 : filtre surface Carrez ===
    # Les lignes gardées après dédup mais sans aucune surface Carrez sont supprimées
    rows_read = len(all_rows)
    rows_dup = 0
    rows_no_carrez = 0
    rows_kept = 0

    final_rows = []
    removed_rows = []  # (row, reason)

    for idx, row in enumerate(all_rows):
        key = tuple(row[i] for i in key_indices)
        if idx not in kept_indices:
            rows_dup += 1
            removed_rows.append((row, "DUPLICATE_VENTE"))
            continue
        if carrez_indices and not has_any_carrez(row, carrez_indices):
            rows_no_carrez += 1
            removed_rows.append((row, "NO_CARREZ"))
            continue
        final_rows.append(row)
        rows_kept += 1

    # === ÉCRITURE du fichier propre ===
    with dst.open('w', encoding='utf-8', newline='') as fout:
        writer = csv.writer(fout, delimiter=delimiter)
        writer.writerow(header)
        writer.writerows(final_rows)

    # === ÉCRITURE du log d'audit ===
    if make_removed_log:
        with log.open('w', encoding='utf-8', newline='') as flog:
            log_writer = csv.writer(flog, delimiter=delimiter)
            log_writer.writerow(header + ["__REMOVED_REASON__"])
            for row, reason in removed_rows:
                log_writer.writerow(row + [reason])

    print({
        "input": str(src),
        "output": str(dst),
        "removed_log": str(log) if make_removed_log else None,
        "rows_read": rows_read,
        "rows_kept": rows_kept,
        "rows_removed_duplicate": rows_dup,
        "rows_removed_no_carrez": rows_no_carrez,
    })

if __name__ == "__main__":
    dedup_by_key(SRC, delimiter=DELIMITER, make_removed_log=MAKE_REMOVED_LOG)
