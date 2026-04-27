# Instructions projet Estimatiz

## Dossier rapports
- Ne **jamais** lire ou scanner en masse `rapports/` ou `rapports/automatique/`
- Ces dossiers contiennent des milliers de fichiers HTML générés automatiquement
- Si besoin de comprendre la logique d'un rapport, lire **1 ou 2 fichiers échantillons** seulement

## Stack
- PHP / MySQL (XAMPP local, o2switch en prod)
- Base de données : `DVF_France` — table principale `dvf_france` (colonnes snake_case)
- Python pour la génération de rapports (`generate_results.py`)
- Déploiement : `./deploy.sh` (zip → scp → unzip sur o2switch)
