# Estimatiz

Outil d'estimation immobilière basé sur les ventes réelles issues des Demandes de Valeurs Foncières (DVF) publiées par la DGFiP.

Couverture : France entière, 2014 → 2025 (13 millions de transactions).

---

## Fonctionnement général

L'utilisateur saisit une adresse sur la page d'accueil. L'autocomplete suggère des rues en temps réel depuis la base `dvf_voies`. Une fois la rue sélectionnée, l'application cherche les ventes comparables sur la même rue et calcule un prix au m² statistique (p20 / médiane / p80). L'estimation finale est affichée avec les mutations de référence.

```
Utilisateur → /  →  /estimation  →  /results
                         ↑               ↑
                   api/autocomplete   api/surface
                                      api/mutations
                                      api/prix-m2
```

---

## Pages du site

| Fichier | URL | Rôle |
|---|---|---|
| `index.php` | `/` | Page d'accueil — présentation et lien vers estimation |
| `estimation.php` | `/estimation` | Formulaire de saisie d'adresse (autocomplete) |
| `results.php` | `/results` | Résultats : surface estimée, prix m², mutations similaires |
| `prix-m2.php` | `/prix-m2` | Prix au m² — grandes villes (arrondissements) ou France (département → ville → rue) |
| `ventes.php` | `/ventes` | Dernières ventes DVF — scroll infini, filtres type / département / pièces / surface / période |
| `donnees.php` | `/donnees` | Présentation des données sources (DVF) |
| `methodologie.php` | `/methodologie` | Explication de la méthode de calcul |
| `faq.php` | `/faq` | Questions fréquentes |
| `a-propos.php` | `/a-propos` | À propos du projet |
| `contact.php` | `/contact` | Formulaire de contact |
| `mentions-legales.php` | `/mentions-legales` | Mentions légales |
| `confidentialite.php` | `/confidentialite` | Politique de confidentialité |

---

## APIs (répertoire `api/`)

Toutes les APIs retournent du JSON. Elles lisent la base `DVF_France` via PDO (config dans `config.php`).

### `api/autocomplete.php` — V4.0

Suggestions d'adresse en temps réel. Requête sur `dvf_voies` (2,5M voies pré-agrégées) — jamais sur `dvf_france` directement.

**Paramètres GET :**
- `q` — saisie utilisateur (ex: `"av foch paris"`)
- `limit` — nombre max de résultats (défaut : 10)

**Paliers de recherche (S → A → A2 → B → B2 → B2b → C) :**
- S : commune seule
- A : préfixe exact `adresse_nom_voie LIKE 'AV FOCH%'`
- A2 : sans type de voie
- B : recherche par mot-clé principal
- B2 / B2b : fallback `%LIKE%` (évité si type détecté + résultats trouvés)
- C : commune seule en dernier recours

**Normalisation type de voie :** `"avenue"→"AV"`, `"boulevard"→"BD"`, `"chemin"→"CHE"`, etc.

---

### `api/surface.php` — V3.0

Calcule une surface indicative (m²) à partir des ventes existantes sur la même rue.

**Paramètres GET :**
- `code_voie` — code FANTOIR 4 chars (ex: `"8249"`)
- `commune` — nom commune (ex: `"Paris 8e Arrondissement"`)
- `voie` — adresse_nom_voie fusionnée (ex: `"AV FOCH"`)
- `no_voie` *(optionnel)* — numéro de rue
- `btq` *(optionnel)* — suffixe B/T/Q
- `pieces` *(optionnel)* — nombre de pièces

**Retourne :** `{ ok, p20, p50, p80, count, samples[] }`

---

### `api/mutations.php` — V3.0

Liste les mutations (ventes) comparables sur une rue.

**Paramètres GET :**
- `code_voie`, `commune`, `voie` — identifiant de la rue
- `surface_min`, `surface_max` *(optionnel)* — filtre surface Carrez
- `pieces` *(optionnel)* — filtre pièces

**Retourne :** tableau de mutations avec date, valeur, surface, adresse complète.

---

### `api/prix-m2.php` — V4.0

Prix au m² agrégé. Couvre France entière via 5 modes.

**Modes (`?mode=`) :**
| Mode | Description | Paramètres clés |
|---|---|---|
| `arrondissements` *(défaut)* | Paris / Lyon / Marseille | `ville=paris\|lyon\|marseille` |
| `departements` | Liste tous les départements disponibles | — |
| `villes` | Villes d'un département | `dep=33` |
| `rues` | Rues d'un CP ou d'une commune | `cp=75008` ou `code_commune=33063` |
| `evolution` | Évolution annuelle du prix médian | `ville=`, `dep=`, `code_commune=` ou `cp=` |

**Filtres communs :** `type_local`, `pieces`, `annee_min`, `annee_max`

**Retourne :** selon le mode — `data[]`, `villes[]`, `rues[]`, `evolution[]`, `departements[]`

Chaque entrée contient `{ p20, median, p80, mean, count }`.

**Cache :** fichier `.cache/api/prix_m2_*.json`, TTL 6 mois (données DVF 2×/an). Invalider : `rm .cache/api/prix_m2_*.json`

---

### `api/ventes.php` — V1.0

Dernières ventes DVF avec pagination keyset (scroll infini).

**Paramètres GET :**
- `cursor` *(optionnel)* — curseur de pagination `"YYYY-MM-DD_id"` (ex: `"2025-06-15_12345"`)
- `limit` *(optionnel)* — résultats par page (défaut: 20, max: 40)
- `type_local` *(optionnel)* — `"Appartement"`, `"Maison"`, etc.
- `dep` *(optionnel)* — département `"33"` (complète le zéro automatiquement)
- `code_commune` *(optionnel)* — code INSEE 5 chars (prioritaire sur cp et dep)
- `cp` *(optionnel)* — code postal 5 chars
- `annee_min` / `annee_max` *(optionnel)* — filtre période
- `pieces` *(optionnel)* — nombre de pièces exactes
- `surface_min` / `surface_max` *(optionnel)* — filtre surface (Carrez ou réelle)

**Retourne :** `{ ok, ventes[], has_more, next_cursor }`

Chaque vente : `{ id, date, adresse, commune, cp, valeur, surface, surf_src, prix_m2, type, pieces }`

**Pagination :** `WHERE (date_mutation < :c_date OR (date_mutation = :c_date2 AND id < :c_id))` — aucun OFFSET, performant sur 13M lignes.

---

### `api/save-rapport.php` — Rapport manuel

Génère et sauvegarde une page HTML de rapport d'estimation à la demande de l'utilisateur (bouton "Générer le rapport" sur `/results`).

- Sauvegarde dans `rapports/{hash}.html`
- Template simple avec bouton "Imprimer / PDF"
- CSS inline (nav + footer) — fonctionne en `file://` et via Apache

**Payload JSON POST :**
- `label`, `surface`, `pieces`, `surfaceMin`, `surfaceMax` — contexte de la recherche
- `suggestion` — `{ cp, voie, commune, code_voie }`
- `estimation` — `{ p20, p50, p80, conf, count }`
- `rows[]` — jusqu'à 25 ventes de référence

**Retourne :** `{ ok, url, filename }`

---

### `api/save-rapport-seo.php` — Rapport automatique SEO

Génère des pages HTML statiques optimisées SEO dans `rapports/automatique/{année}/`. Appelé par `generate_results.py`.

- Slug calculé depuis le code postal + rue + hash court (ex: `75011-rue-voltaire-ad9220.html`)
- Template complet : H1, H2, JSON-LD (BreadcrumbList + Article + FAQPage), canonical, OG tags
- CSS intégralement inline — fonctionne en `file://` et via Apache
- Sections : bannière estimations P20/médiane/P80, tableau des ventes, analyse de marché, FAQ

**Retourne :** `{ ok, url, path, filename }`

---

### `api/estimate.php` — V2.0 *(non connecté)*

Ancienne API d'estimation, non utilisée dans le parcours actuel. À décider : conserver ou supprimer.

---

### `api/export.php` *(non connecté)*

Export du résultat sous différents formats : JSON, CSV, XLS, PDF. Non connecté à l'interface actuelle.

---

## Base de données

```
Serveur  : MySQL local (XAMPP) — localhost:3306
Base     : DVF_France
```

| Table | Lignes | Description |
|---|---|---|
| `dvf_france` | ~13 000 000 | Toutes les transactions DVF 2014–2025 |
| `dvf_voies` | ~2 500 000 | Voies uniques pré-agrégées (autocomplete) |

**Configuration :** copier `config.local.example.php` → `config.local.php` et renseigner les accès MySQL.

---

## Scripts `_Base/` — Pipeline de données

### Scripts actifs

#### `dedup_geoloc.py`
Dédoublonne un fichier `_full.csv` (format géoloc DVF) : conserve une ligne par `id_mutation`, favorise le meilleur `type_local`.

```bash
python3 dedup_geoloc.py 2026_full.csv
# → produit 2026_full_clean.csv (renommer en ValeursFoncieres-2026_v6.csv)
```

---

#### `normalize_voies.py`
Normalise le premier mot de `adresse_nom_voie` dans les fichiers `_v6.csv` selon le standard FANTOIR des fichiers géoloc.

Corrections appliquées : `AVENUE→AV`, `CHEMIN→CHE`, `CHEM→CHE`, `ROUTE→RTE`, `ALLEE→ALL`, `IMPASSE→IMP`, `PLACE→PL`, `FAUBOURG→FG`, `HAMEAU→HAM`, `DOMAINE→DOM`, `MONTEE→MTE`, `COURS→CRS`, `RUELLE→RLE`, `SENTIER→SEN`, `VILLA→VLA`, `SQUARE→SQ`.

Script idempotent : 0 correction si le fichier est déjà propre.

```bash
python3 normalize_voies.py                        # tous les _v6.csv
python3 normalize_voies.py fichier_v6.csv         # un seul fichier
```

---

#### `import_dvf_france.py`
Import d'un fichier CSV V6 dans la table `dvf_france`. Batch de 5 000 lignes.

```bash
python3 import_dvf_france.py ValeursFoncieres-2026_v6.csv           # append
python3 import_dvf_france.py ValeursFoncieres-2026_v6.csv --truncate # vide avant
```

---

#### `import_dvf_france_automatique.py`
TRUNCATE + réimport complet de tous les fichiers `_v6.csv` dans l'ordre chronologique.

```bash
python3 -u import_dvf_france_automatique.py
# Durée : ~1h15 pour 12 fichiers (~13M lignes)
```

---

#### `build_dvf_voies.py`
Reconstruit la table `dvf_voies` depuis `dvf_france` par streaming (curseur non-bufférisé). Déduplique en mémoire Python puis insère en batch.

```bash
python3 build_dvf_voies.py
# Durée : ~3 min pour 2,5M voies
```

---

### Pipeline — ajouter une nouvelle année (ex: 2026)

```
1. Déposer 2026_full.csv dans _Base/

2. python3 dedup_geoloc.py 2026_full.csv
   → renommer le fichier produit en ValeursFoncieres-2026_v6.csv

3. python3 normalize_voies.py ValeursFoncieres-2026_v6.csv

4. python3 import_dvf_france.py ValeursFoncieres-2026_v6.csv

5. python3 build_dvf_voies.py
```

### Pipeline — réimport complet

```
1. python3 normalize_voies.py
2. python3 -u import_dvf_france_automatique.py   (~1h15)
3. python3 build_dvf_voies.py                    (~3 min)
```

---

## SEO

### Phases déployées

| Phase | Date | Changements |
|---|---|---|
| V1 | Avril 2026 | URL rewriting, canonicals, JSON-LD, sitemap, robots |
| V1.5 | 27/04/2026 | Headers sécurité, force www+HTTPS, `generate_results.py` V2 |

---

### V1 — Base SEO

#### URL rewriting (`.htaccess`)
- **301** : toute URL `.php` publique → URL propre sans extension (`/estimation.php` → `/estimation`)
- **301** : toute URL `.html` → sans extension
- **Rewrite interne** : Apache sert `estimation.php` quand le navigateur demande `/estimation`
- Les APIs (`/api/`) ne sont pas concernées (appelées en JS)

#### Meta et structured data
- `<link rel="canonical">` sans `.php` sur toutes les pages
- `og:url` et `og:image` sur toutes les pages (`/assets/img/og-estimatiz.png` — 1200×630px)
- **JSON-LD `WebSite`** + `SearchAction` sur `index.php`
- **JSON-LD `FAQPage`** sur `faq.php` (rich results Google)
- **JSON-LD `BreadcrumbList`** sur `estimation`, `prix-m2`, `ventes`, `methodologie`, `donnees`
- **JSON-LD `BreadcrumbList` + `Article` + `FAQPage`** sur chaque rapport automatique

#### Sitemap et robots
- `sitemap.xml` : **sitemapindex** pointant vers deux sous-sitemaps
  - `sitemap-site.xml` : 11 pages du site (priority 1.0 → 0.3)
  - `sitemap-rapports.xml` : rapports automatiques, mis à jour automatiquement à chaque génération (priority 0.7, changefreq monthly)
- `robots.txt` : bloque `/_Base/`, `/_Backup/`, `/.cache/`, `/lib/`, `/includes/`

---

### V1.5 — Correctifs prioritaires (27/04/2026)

#### `.htaccess` — Headers de sécurité (nouveaux)

Ajout de 4 headers manquants — passage Mozilla Observatory de F → A :

```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()
```

Vérification : `curl -I https://www.estimatiz.fr/` ou https://securityheaders.com/

#### `.htaccess` — Force www + HTTPS (nouveau)

```apache
RewriteCond %{HTTPS} off [OR]
RewriteCond %{HTTP_HOST} !^www\. [NC]
RewriteCond %{HTTP_HOST} ^(?:www\.)?(.+)$ [NC]
RewriteRule ^ https://www.%1%{REQUEST_URI} [R=301,L,NE]
```

Tous les canonicals utilisent `https://www.estimatiz.fr/` — le serveur est maintenant aligné.

⚠️ **HSTS** : commenté dans `.htaccess`. Activer seulement après 1 mois de stabilité HTTPS : `Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"`

#### `.htaccess` — Fichiers privés bloqués (nouveau)

`config.local.php`, `config.o2switch.php`, `deploy.sh`, `backup.py`, `restore.py`, `generate_results.py` → 403.
Dossiers `.cache/`, `_Base/`, `_Backup/`, `lib/`, `includes/` → 403.

#### `mentions-legales.php` — À compléter

Template avec 3 cas (particulier / auto-entrepreneur / société). Les `[crochets]` doivent être remplis avant déploiement.

---

### Rapports automatiques SEO

Génération en masse de pages HTML statiques par rue et commune, basées sur les données DVF.

#### `generate_results.py` — V2

Script Python en ligne de commande (tourne **en local**, pas sur o2switch). Interroge directement la base MySQL `DVF_France`, calcule les statistiques et appelle `api/save-rapport-seo.php`.

**Changement V2 vs V1 :** V1 générait 1 rapport par numéro de rue (jusqu'à 80 fichiers par rue → thin content). V2 génère **1 rapport par rue**, plus dense, sans risque de pénalité Panda.

⚠️ Si des rapports V1 existent déjà, les supprimer avant de regénérer en V2 :
```bash
ssh zece2169@dark.o2switch.net "rm -rf ~/estimatiz.fr/rapports/automatique/2026/"
```

**Dépendances :** `mysql-connector-python`, `numpy`, `requests`

```bash
# Une rue précise
python3 generate_results.py --voie "RUE VOLTAIRE" --commune "Paris"

# Toutes les rues éligibles d'un département
python3 generate_results.py --dept 75 --min-trans 10
python3 generate_results.py --dept 69 --min-trans 15
```

**Paramètres :**
- `--voie` + `--commune` : génère un seul rapport
- `--dept` : génère tous les rapports du département (rues avec au moins `--min-trans` ventes, défaut : 10)

**Traitement par rapport :**
1. Récupère toutes les transactions Appartement/Maison sur la rue
2. Calcule le prix au m² par vente
3. Filtre les valeurs aberrantes (méthode IQR, k=1.5)
4. Calcule P20 / médiane / P80 + niveau de confiance (85% si ≥30 ventes, 65% si ≥15, 40% sinon)
5. Poste le payload JSON à `api/save-rapport-seo.php`
6. Ajoute l'URL prod dans `sitemap-rapports.xml`

**Structure des fichiers générés :**
```
rapports/
  automatique/
    2026/
      75011-rue-voltaire-ad9220.html
      …
```

**Droits locaux (XAMPP) :** `chmod 777 rapports/automatique/` (Apache tourne en `daemon`). En production o2switch, `755` suffit.

---

## Notes techniques

- **Format `adresse_nom_voie`** : type et nom fusionnés, ex `"RUE DE RIVOLI"`, `"AV FOCH"`. Pas de colonne type_voie séparée.
- **`code_commune`** : INSEE 5 chars. Paris 1er = `75101` … 20e = `75120`.
- **`id_parcelle`** : 14 chars. `section = SUBSTRING(id_parcelle, 9, 2)`.
- **innodb_buffer_pool_size** : XAMPP démarre à 16 MB. Les scripts `build_dvf_voies.py` et `import_dvf_france_automatique.py` le montent temporairement à 512 MB pour les opérations massives.
- **Cache API** : préfixe `acV7_` pour l'autocomplete, `pm2v4_` pour prix-m2 (TTL 6 mois, fichiers dans `.cache/api/`).
- **og:image** : `assets/img/og-estimatiz.png` (1200×630px, 48 Ko) — générée avec Pillow depuis `_Base/`.
- **o2switch** : import via SSH (`mysql -u user -p base < dump.sql`) pour éviter les timeouts phpMyAdmin. `build_dvf_voies.sql` disponible pour reconstruire `dvf_voies` directement en SQL.
