# Instructions projet Estimatiz

## Règles absolues
- Ne **jamais** lire ou scanner en masse `rapports/` ou `rapports/automatique/` — des milliers de fichiers HTML
- Si besoin de comprendre la logique d'un rapport, lire **1 ou 2 fichiers échantillons** seulement

## Stack
- PHP / MySQL (XAMPP local, o2switch en prod)
- Base : `DVF_France` — table `dvf_france` (~13M lignes, snake_case) + `dvf_voies` (~2,5M voies)
- Python pour la génération de rapports (`generate_results.py`) — script local uniquement, pas sur le serveur
- Déploiement : `./deploy.sh` (zip → scp → unzip sur o2switch)

## Pages du site (12 fichiers PHP à la racine)
`index.php` `/` · `estimation.php` · `results.php` · `prix-m2.php` · `ventes.php` · `donnees.php` · `methodologie.php` · `faq.php` · `a-propos.php` · `contact.php` · `mentions-legales.php` · `confidentialite.php`

## APIs (`api/`)
`autocomplete.php` (V4) · `surface.php` (V3) · `mutations.php` (V3) · `prix-m2.php` (V4) · `ventes.php` · `export.php` · `estimate.php` · `save-rapport.php` · `save-rapport-seo.php`

## Config
`config.php` détecte l'env : localhost → `config.local.php`, prod → `config.o2switch.php` (les deux gitignorés, déjà en place sur les deux envs)

## Répertoires clés
- `includes/` : `nav.php`, `footer.php`, `content-style.php`, `seo-extras.php`
- `lib/` : `cache.php`, `db.php`, `db_o2switch.php`
- `assets/` : `css/`, `js/`, `img/`
- `_Base/` : scripts pipeline Python (import CSV, dédoublonnage, build voies) — local uniquement
- `.cache/api/` : cache JSON prix-m2, TTL 6 mois — invalider : `rm .cache/api/prix_m2_*.json`

## SEO — état actuel
- **V1** : URL rewriting `.php`→propre, canonicals, JSON-LD (WebSite/FAQPage/BreadcrumbList/Article), sitemap, robots
- **V1.5** (27/04/2026) : `.htaccess` + 4 headers sécurité + force www+HTTPS · `generate_results.py` V2 (1 rapport/rue, pas par numéro) · `mentions-legales.php` template à compléter avant déploiement
- HSTS : commenté dans `.htaccess`, à activer après 1 mois de stabilité

## DB — structure essentielle
- `adresse_nom_voie` : type+nom fusionnés (`"AV FOCH"`, `"RUE DE RIVOLI"`) — pas de colonne type séparée
- `adresse_code_voie` : CHAR(4) FANTOIR
- `nom_commune` : accentué (`"Paris 8e Arrondissement"`)
- `code_commune` : INSEE 5 chars (Paris 75101–75120, Lyon 69381–69389, Marseille 13201–13216)
- `valeur_fonciere` : DECIMAL(15,2) · `date_mutation` : DATE ISO · `code_postal` : VARCHAR(5)
