
"""Normalise et validation d'un fichier CSV selon des règles spécifiques.
Ce script lit un fichier CSV, applique des normalisations spécifiques à certaines colonnes,
valide les données selon des règles définies, et génère un rapport détaillé des erreurs
trouvées. Il offre également la possibilité de supprimer automatiquement les lignes erronées
du fichier source après confirmation de l'utilisateur.
"""
import pandas as pd
import re
from datetime import datetime
import sys
import os
import shutil
import unicodedata

# Définition des règles de validation pour chaque colonne (inchangées)
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
    'Surface terrain': {'type': 'varchar', 'max_length': 7}
}

# ---------- normalisations (pré-validation) ----------
def _norm_text(s: str) -> str:
    if s is None:
        return ''
    s = unicodedata.normalize('NFKC', str(s))
    s = s.replace('\xa0', ' ').replace('\u202f', ' ')
    return s.strip()

def norm_no_voie(v: str) -> str:
    s = _norm_text(v).replace(',', '.')
    if s == '':
        return s
    m = re.match(r'^\s*(\d+)(?:\.0+)?\s*$', s)     # 9001.0 -> 9001
    if m:
        return m.group(1)
    try:
        return str(int(float(s)))                  # 12.5 -> 12 (adapter si besoin)
    except:
        return s[:-2] if s.endswith('.0') else s

def norm_code_type_local(v: str) -> str:
    s = _norm_text(v).replace(',', '.')
    if s == '':
        return s
    # Si débute par un chiffre (ex 1.0), normaliser en entier
    if re.match(r'^\d', s):
        try:
            return str(int(float(s)))
        except:
            pass
    # sinon, on retient le premier caractère alphanumérique en MAJ
    m = re.search(r'([A-Za-z0-9])', s)
    return m.group(1).upper() if m else s[:1].upper()

def norm_nb_pieces(v: str) -> str:
    s = _norm_text(v).replace(',', '.')
    if s == '':
        return s
    try:
        return str(int(float(s)))                  # 3.0 -> 3
    except:
        m = re.search(r'(\d+)', s)
        return m.group(1) if m else s

def norm_surface_reelle(v: str) -> str:
    s = _norm_text(v).replace(',', '.')
    if s == '':
        return s
    try:
        f = float(s)
        if abs(f - int(f)) < 1e-9:
            return str(int(f))                     # 100.0 -> 100
        s2 = f"{f:.2f}"
        s2 = re.sub(r'(\.\d*?[1-9])0+$', r'\1', s2)  # 12.50 -> 12.5
        s2 = s2.rstrip('0').rstrip('.')             # 12.40 -> 12.4 ; 12.00 -> 12
        return s2
    except:
        return s[:-2] if s.endswith('.0') else s

def norm_surface_terrain(v: str) -> str:
    s = _norm_text(v).replace(',', '.')
    if s == '':
        return s
    try:
        f = float(s)
        if abs(f - int(f)) < 1e-9:
            return str(int(f))                     # 100.0 -> 100
        s2 = f"{f:.2f}"
        s2 = re.sub(r'(\.\d*?[1-9])0+$', r'\1', s2)  # 12.50 -> 12.5
        s2 = s2.rstrip('0').rstrip('.')             # 12.40 -> 12.4 ; 12.00 -> 12
        return s2
    except:
        return s[:-2] if s.endswith('.0') else s

# ---------- validate (inchangées de ton script) ----------
def validate_date(value):
    if pd.isna(value) or str(value).strip() == '':
        return True, None
    value_str = str(value).strip()
    pattern = r'^\d{2}/\d{2}/\d{4}$'
    if not re.match(pattern, value_str):
        return False, f"Format invalide (attendu: JJ/MM/AAAA, reçu: {value_str})"
    try:
        datetime.strptime(value_str, '%d/%m/%Y')
        return True, None
    except:
        return False, f"Date invalide: {value_str}"

def validate_int(value, max_value=None):
    if pd.isna(value) or str(value).strip() == '':
        return True, None
    try:
        int_val = int(float(value))
        if max_value and int_val > max_value:
            return False, f"Valeur trop grande (max: {max_value}, reçu: {int_val})"
        return True, None
    except:
        return False, f"N'est pas un entier valide: {value}"

def validate_varchar(value, max_length):
    if pd.isna(value) or str(value).strip() == '':
        return True, None
    # on conserve ta logique ici
    try:
        if isinstance(value, (int, float)) and not pd.isna(value):
            if float(value) == int(value):
                value_str = str(int(value))
            else:
                value_str = str(value)
        else:
            value_str = str(value)
    except:
        value_str = str(value)
    actual_length = len(value_str)
    if actual_length > max_length:
        return False, f"Trop long (max: {max_length}, actuel: {actual_length}, valeur: '{value_str[:50]}...')"
    return True, None

# ---------- pipeline ----------
def validate_csv(csv_file, output_file='_validation_erreurs_csv.txt', separator='|', delete_bad_lines=False):
    print("=" * 80)
    print("VALIDATION DU FICHIER CSV")
    print("=" * 80)
    print(f"Fichier: {csv_file}")
    print(f"Separateur: '{separator}'")
    print(f"Rapport: {output_file}\n")
    
    all_errors = []
    invalid_row_indices = set()
    total_rows = 0
    
    try:
        # 1) Lecture stricte en TEXTE (aucune auto-conversion)
        print("Chargement du fichier (texte strict)...")
        df = pd.read_csv(
            csv_file, sep=separator, encoding='utf-8-sig',
            engine='python', dtype=str, keep_default_na=False
        )
        total_rows = len(df)
        print(f"Fichier charge: {total_rows} lignes")
        print(f"Colonnes detectees: {len(df.columns)}\n")
        
        # 2) Normalisations PREALABLES (en mémoire uniquement, pas de sauvegarde)
        if 'No voie' in df.columns:                       df['No voie'] = df['No voie'].apply(norm_no_voie)
        if 'Code type local' in df.columns:               df['Code type local'] = df['Code type local'].apply(norm_code_type_local)
        if 'Nombre pieces principales' in df.columns:     df['Nombre pieces principales'] = df['Nombre pieces principales'].apply(norm_nb_pieces)
        if 'Surface reelle bati' in df.columns:           df['Surface reelle bati'] = df['Surface reelle bati'].apply(norm_surface_reelle)
        if 'Surface terrain' in df.columns:               df['Surface terrain'] = df['Surface terrain'].apply(norm_surface_terrain)

        
        # 3) Validation
        print("Validation en cours...")
        for i, (idx, row) in enumerate(df.iterrows()):
            if (i + 1) % 1000 == 0:
                print(f"  Progression: {i + 1}/{total_rows} lignes...")
            
            for column, rules in VALIDATION_RULES.items():
                if column not in df.columns:
                    continue
                
                value = row[column]
                if rules['type'] == 'int':
                    is_valid, error_msg = validate_int(value, rules.get('max_value'))
                elif rules['type'] == 'date':
                    is_valid, error_msg = validate_date(value)
                elif rules['type'] == 'varchar':
                    is_valid, error_msg = validate_varchar(value, rules['max_length'])
                else:
                    is_valid, error_msg = True, None
                
                if not is_valid:
                    all_errors.append({
                        'ligne': i + 2,  # +2 car index 0 et ligne header
                        'colonne': column,
                        'valeur': value,
                        'erreur': error_msg
                    })
                    invalid_row_indices.add(i)
        
        print(f"\nValidation terminee!\n")
        print("=" * 80)
        print("RESULTATS")
        print("=" * 80)
        print(f"Lignes analysees: {total_rows}")
        print(f"Erreurs trouvees: {len(all_errors)}")
        
        # 4) Rapport (toujours)
        print(f"\nGeneration du rapport...")
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write("=" * 80 + "\n")
            f.write("RAPPORT DE VALIDATION CSV\n")
            f.write("=" * 80 + "\n")
            f.write(f"Fichier: {csv_file}\n")
            f.write(f"Date: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}\n")
            f.write(f"Total lignes: {total_rows}\n")
            f.write(f"Total erreurs: {len(all_errors)}\n")
            f.write("=" * 80 + "\n\n")
            
            if all_errors:
                errors_by_column = {}
                for error in all_errors:
                    col = error['colonne']
                    errors_by_column[col] = errors_by_column.get(col, 0) + 1
                
                f.write("STATISTIQUES PAR COLONNE:\n")
                f.write("-" * 80 + "\n")
                for col, count in sorted(errors_by_column.items(), key=lambda x: x[1], reverse=True):
                    f.write(f"  {col}: {count} erreur(s)\n")
                
                f.write("\n" + "=" * 80 + "\n")
                f.write("DETAIL DES ERREURS:\n")
                f.write("=" * 80 + "\n\n")
                for i, error in enumerate(all_errors, 1):
                    f.write(f"Erreur #{i}\n")
                    f.write(f"  Ligne: {error['ligne']}\n")
                    f.write(f"  Colonne: {error['colonne']}\n")
                    f.write(f"  Valeur: {error['valeur']}\n")
                    f.write(f"  Probleme: {error['erreur']}\n")
                    f.write("-" * 80 + "\n")
            else:
                f.write("AUCUNE ERREUR DETECTEE\n")
                f.write("-" * 80 + "\n")
                f.write("Le fichier CSV est valide et pret pour l'import!\n")
        
        print(f"Rapport genere: {output_file}")
        print("=" * 80)
        
        # 5) Suppression des lignes erronées (UNIQUEMENT si erreurs ET confirmation)
        if all_errors and delete_bad_lines and invalid_row_indices:
            try:
                backup_path = csv_file + '.bak'
                print(f"\nSauvegarde du fichier original dans: {backup_path}")
                shutil.copy2(csv_file, backup_path)
                cleaned_df = df.drop(index=list(invalid_row_indices))
                cleaned_df.to_csv(csv_file, sep=separator, index=False, encoding='utf-8-sig')
                print(f"Lignes erronées supprimées: {len(invalid_row_indices)}. Fichier mis à jour: {csv_file}")
            except Exception as e:
                print(f"ERREUR lors de la suppression des lignes erronées: {e}")
        elif not all_errors:
            print("\nAucune erreur détectée → aucune suppression, aucun .bak.")
        else:
            print("\nErreurs détectées mais suppression non demandée → aucune modification du fichier.")
        
        return len(all_errors) == 0
        
    except FileNotFoundError:
        print(f"ERREUR: Fichier introuvable: {csv_file}")
        return False
    except Exception as e:
        print(f"ERREUR: {e}")
        import traceback
        traceback.print_exc()
        return False

def main():
    if len(sys.argv) > 1:
        csv_file = sys.argv[1]
    else:
        csv_file = input("Chemin du fichier CSV: ")
        
    separator = '|' if len(sys.argv) <= 2 else sys.argv[2]
    output_file = '_validation_erreurs_csv.txt' if len(sys.argv) <= 3 else sys.argv[3]
    
    # Demande : suppression auto des lignes en erreur ?
    delete_bad_lines = False
    try:
        ans = input("Supprimer automatiquement les lignes contenant des erreurs si trouvées ? (oui/non) [non]: ").strip().lower()
        if ans in ('oui', 'o', 'yes', 'y'):
            delete_bad_lines = True
    except Exception:
        delete_bad_lines = False

    validate_csv(csv_file, output_file, separator, delete_bad_lines=delete_bad_lines)

if __name__ == "__main__":
    main()
