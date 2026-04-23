#!/usr/bin/env python3
"""
restore.py — Restauration d'une sauvegarde Estimatiz

Usage :
    1. Modifier BACKUP_ZIP ci-dessous avec le fichier ZIP à restaurer.
    2. Lancer : python3 restore.py

Le script restaure les fichiers et dossiers contenus dans l'archive, y compris
les fichiers et dossiers cachés présents dans le ZIP.

Sécurité :
    - supprime les fichiers/dossiers du site avant restauration ;
    - conserve toujours le dossier _Base/ ;
    - dans _Base/, supprime tout sauf les fichiers CSV ;
    - n'écrase jamais les fichiers CSV du dossier _Base/ ;
    - refuse les chemins dangereux qui sortiraient du dossier du site.
"""

import shutil
import sys
import zipfile
from pathlib import Path, PurePosixPath

SITE_DIR = Path(__file__).parent.resolve()

# À MODIFIER À CHAQUE RESTAURATION :
# Exemple :
# BACKUP_ZIP = Path.home() / "Documents" / "Sauvegarde Estimatiz" / "estimatiz_backup_20260417_153000.zip"
BACKUP_ZIP = Path.home() / "Documents" / "Sauvegarde Estimatiz" / "estimatiz_backup_A_REMPLACER.zip"

EXCLUDE_BASE_CSVS = "_Base"


def is_base_csv(relative_path: PurePosixPath) -> bool:
    return (
        len(relative_path.parts) >= 2
        and relative_path.parts[0] == EXCLUDE_BASE_CSVS
        and relative_path.suffix.lower() == ".csv"
    )


def safe_target(relative_path: PurePosixPath) -> Path | None:
    if relative_path.is_absolute() or ".." in relative_path.parts:
        return None

    target = (SITE_DIR / Path(*relative_path.parts)).resolve()

    try:
        target.relative_to(SITE_DIR)
    except ValueError:
        return None

    return target


def remove_path(path: Path) -> None:
    if path.is_dir() and not path.is_symlink():
        shutil.rmtree(path)
        return

    path.unlink()


def clean_base_dir(base_dir: Path) -> tuple[int, int]:
    deleted = 0
    kept_csv = 0

    if not base_dir.exists():
        base_dir.mkdir(parents=True, exist_ok=True)
        return deleted, kept_csv

    for item in sorted(base_dir.rglob("*"), key=lambda p: len(p.parts), reverse=True):
        if item.is_file() and item.suffix.lower() == ".csv":
            kept_csv += 1
            print(f"  = CSV conservé : {item.relative_to(SITE_DIR)}")
            continue

        if item.is_dir() and not item.is_symlink() and any(item.iterdir()):
            continue

        remove_path(item)
        deleted += 1
        print(f"  x supprimé : {item.relative_to(SITE_DIR)}")

    return deleted, kept_csv


def clean_site() -> tuple[int, int]:
    deleted = 0
    kept_csv = 0
    base_dir = SITE_DIR / EXCLUDE_BASE_CSVS

    for item in list(SITE_DIR.iterdir()):
        if item == base_dir:
            base_deleted, base_kept_csv = clean_base_dir(base_dir)
            deleted += base_deleted
            kept_csv += base_kept_csv
            continue

        remove_path(item)
        deleted += 1
        print(f"  x supprimé : {item.relative_to(SITE_DIR)}")

    if not base_dir.exists():
        base_dir.mkdir(parents=True, exist_ok=True)

    return deleted, kept_csv


def restore_file(zf: zipfile.ZipFile, member: zipfile.ZipInfo, target: Path) -> None:
    target.parent.mkdir(parents=True, exist_ok=True)

    with zf.open(member, "r") as src, target.open("wb") as dst:
        while True:
            chunk = src.read(1024 * 1024)
            if not chunk:
                break
            dst.write(chunk)


def main() -> int:
    backup_zip = BACKUP_ZIP.expanduser().resolve()

    print("=" * 60)
    print("  Restauration Estimatiz")
    print(f"  Site   : {SITE_DIR}")
    print(f"  Backup : {backup_zip}")
    print("=" * 60)

    if not backup_zip.is_file():
        print()
        print("ERREUR — fichier ZIP introuvable.")
        print("Modifie BACKUP_ZIP dans restore.py avec le chemin exact du backup.")
        return 1

    try:
        backup_zip.relative_to(SITE_DIR)
    except ValueError:
        pass
    else:
        print()
        print("ERREUR — le ZIP de sauvegarde est dans le dossier du site.")
        print("Place le ZIP hors du site, par exemple dans ~/Documents/Sauvegarde Estimatiz/.")
        return 1

    print()
    print("ATTENTION — cette restauration va supprimer le contenu actuel du site.")
    print("Protection appliquée : le dossier _Base/ est conservé, et ses CSV aussi.")
    print("Tous les autres fichiers/dossiers seront supprimés avant extraction du ZIP.")

    confirmation = input('\nTaper "EFFACER ET RESTAURER" pour confirmer : ').strip()
    if confirmation != "EFFACER ET RESTAURER":
        print("Annulé — aucune modification effectuée.")
        return 0

    restored = 0
    skipped_csv = 0
    skipped_unsafe = 0
    created_dirs = 0
    deleted, kept_csv = clean_site()

    with zipfile.ZipFile(backup_zip, "r") as zf:
        for member in zf.infolist():
            relative_path = PurePosixPath(member.filename)
            target = safe_target(relative_path)

            if target is None:
                skipped_unsafe += 1
                print(f"  ! chemin ignoré : {member.filename}")
                continue

            if is_base_csv(relative_path):
                skipped_csv += 1
                print(f"  - CSV protégé ignoré : {relative_path}")
                continue

            if member.is_dir():
                target.mkdir(parents=True, exist_ok=True)
                created_dirs += 1
                print(f"  d {relative_path}")
                continue

            restore_file(zf, member, target)
            restored += 1
            print(f"  + {relative_path}")

    print()
    print(f"OK — {restored} fichiers restaurés")
    print(f"Fichiers/dossiers supprimés avant restauration : {deleted}")
    print(f"CSV _Base conservés avant restauration : {kept_csv}")
    print(f"Dossiers créés/vérifiés : {created_dirs}")
    print(f"CSV _Base du ZIP ignorés : {skipped_csv}")
    print(f"Chemins dangereux ignorés : {skipped_unsafe}")
    print("Le dossier _Base/ et ses fichiers CSV locaux ont été préservés.")

    return 0


if __name__ == "__main__":
    sys.exit(main())
