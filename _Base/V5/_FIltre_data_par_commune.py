
import pandas as pd

input_file = "ValeursFoncieres-2025.csv"       # fichier source (séparé par |)
output_file = "ValeursFoncieres-2025_75.csv"  # fichier filtré (séparé par ;)

# Lecture du fichier CSV avec séparateur |
df = pd.read_csv(input_file, sep='|', dtype=str, header=0)

# Nettoyage des colonnes et valeurs
df.columns = [c.strip() for c in df.columns]
df = df.applymap(lambda x: x.strip() if isinstance(x, str) else x)

# Filtre sur le Code postal (75018 ou 75011)
df_filtre = df[df["Code postal"].isin(["75001", "75002", "75003", "75004", "75005", "75006", "75007", "75008", "75009", "75010", "75011", "75012", "75013", "75014", "75015", "75016", "75017", "75018", "75019", "75020"])]

# Sauvegarde dans un fichier CSV avec séparateur ;
df_filtre.to_csv(output_file, sep='|', index=False, encoding='utf-8')

print(f"Fichier nettoyé et filtré sauvegardé sous : {output_file}")