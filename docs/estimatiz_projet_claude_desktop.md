# Estimatiz — Document de projet pour Claude Desktop

> Synthèse complète du projet au 23/04/2026.  
> À coller en **Project Instructions** dans Claude Desktop.

---

## 1. Qu'est-ce qu'Estimatiz ?

Outil d'estimation immobilière basé sur les données DVF (Demandes de Valeurs Foncières) officielles de la DGFiP / data.gouv.fr.

- **Stack** : PHP 8.2 + MySQL + JavaScript vanilla (pas de framework)
- **Hébergement local** : XAMPP sur macOS (`/Applications/XAMPP/xamppfiles/htdocs/estimatiz`)
- **Hébergement prod** : o2switch (déploiement à venir)
- **Développeur** : Laurent Da Costa (solo)

**Parcours utilisateur :**
1. `index.php` — saisie adresse avec autocomplete → sélection suggestion
2. `estimation.php` — surface indicative récupérée, filtres (surface min/max, pièces)
3. `results.php` — tableau des ventes comparables, estimation P20/médiane/P80, export PDF

---

## 2. Base de données — DVF_France (V6)

### Connexion MySQL
- Base : `DVF_France`
- Table principale : `dvf_france`
- Charset : `utf8mb4`
- Config locale : `config.local.php` (non versionné)

### État actuel (17/04/2026)
| Année | Lignes      | Source                  |
|-------|-------------|-------------------------|
| 2014  | 800 753     | Legacy CSV converti V6  |
| 2015  | 896 692     | Legacy CSV converti V6  |
| 2016  | 950 584     | Legacy CSV converti V6  |
| 2017  | 1 087 740   | Legacy CSV converti V6  |
| 2018  | 754 629     | Legacy CSV converti V6  |
| 2019  | 331 500     | Legacy CSV converti V6  |
| 2025  | 467 492     | Géoloc dédoublonné      |
| **Total** | **5 289 390** | Période 2014–2025 |

**2020–2024 non encore importés** (à faire, pipeline prêt).

### Schéma de la table `dvf_france` (colonnes clés)
```sql
id                      INT UNSIGNED AUTO_INCREMENT
id_mutation             VARCHAR(20)       -- groupe les lignes d'une même vente
date_mutation           DATE              -- format ISO YYYY-MM-DD
nature_mutation         VARCHAR(100)
valeur_fonciere         DECIMAL(15,2)     -- euros, point décimal

adresse_numero          VARCHAR(10)       -- numéro de voie
adresse_suffixe         VARCHAR(5)        -- bis, ter...
adresse_nom_voie        VARCHAR(255)      -- type+nom fusionnés : "RUE DE RIVOLI"
adresse_code_voie       CHAR(4)           -- code FANTOIR : "8249"
code_postal             VARCHAR(5)        -- "75001"
code_commune            VARCHAR(5)        -- INSEE : "75101" (Paris 1er)
nom_commune             VARCHAR(100)      -- "Paris 1er Arrondissement"
code_departement        VARCHAR(3)

id_parcelle             VARCHAR(14)       -- "75104000AJ0071"
  -- section = SUBSTRING(id_parcelle, 9, 2)  → "AJ"
  -- no_plan = SUBSTRING(id_parcelle, 11, 4) → "0071"

lot1_numero … lot5_numero       VARCHAR(20)
lot1_surface_carrez … lot5_…   DECIMAL(10,2)
nombre_lots             SMALLINT UNSIGNED

type_local              VARCHAR(50)       -- "Appartement", "Maison", etc.
surface_reelle_bati     DECIMAL(10,2)
nombre_pieces_principales SMALLINT UNSIGNED
nature_culture          VARCHAR(50)
surface_terrain         DECIMAL(10,2)

longitude               DECIMAL(10,7)     -- WGS-84, NULL pour données legacy
latitude                DECIMAL(10,7)
```

### Index
```
idx_code_voie_commune   (adresse_code_voie, code_commune)
idx_nom_voie_commune    (adresse_nom_voie, nom_commune)
idx_code_commune        (code_commune)
idx_code_postal         (code_postal)
idx_date_mutation       (date_mutation)
idx_type_local          (type_local)
idx_id_mutation         (id_mutation)
idx_id_parcelle         (id_parcelle)
idx_adresse_nom_voie    (adresse_nom_voie)   ← prefix search
```

### Points clés des données
- `adresse_nom_voie` : type abrégé + nom fusionnés — ex: "AV FOCH", "BD SAINT GERMAIN"
  - Types : AV, BD, RUE, CHE, RTE, ALL, IMP, PL, SQ, QUAI, CRS, PAS...
  - SAINT parfois abrégé "ST", parfois écrit entier → incohérence dans la base
- `nom_commune` : accentué — "Paris 8e Arrondissement"
- `code_commune` INSEE 5 chars :
  - Paris : 75101 (1er) … 75120 (20e)
  - Lyon  : 69381 (1er) … 69389 (9e)
  - Marseille : 13201 (1er) … 13216 (16e)
- Données 2014–2019 : longitude/latitude = NULL (pas dans les fichiers legacy)
- Données 2025 : 465 897 lignes géolocalisées

---

## 3. Structure des fichiers

```
estimatiz/
├── index.php              ← page d'accueil, barre de recherche
├── estimation.php         ← formulaire surface + filtres
├── results.php            ← tableau ventes comparables + estimation
├── prix-m2.php            ← page stats prix/m² par arrondissement
├── config.php             ← chargement config + get_pdo() + db_table_dvf()
├── config.local.php       ← DB_NAME='DVF_France', DB_TABLE_DVF='dvf_france'
├── api/
│   ├── autocomplete.php   ← suggestions d'adresse (V3.0)
│   ├── surface.php        ← surface indicative médiane (V3.0)
│   ├── mutations.php      ← liste ventes comparables (V3.0)
│   └── prix-m2.php        ← stats prix/m² (V3.0)
├── assets/
│   ├── css/site.css
│   └── js/utils.js        ← formatEuro, parseDateToTS, percentileArr, filterIQR...
├── includes/
│   └── nav.php
├── lib/
│   └── cache.php
└── _Base/                 ← scripts ETL (non servis par Apache)
    ├── dedup_legacy.py        ← dédup CSV legacy 2014–2024
    ├── convert_legacy_csv.py  ← conversion legacy → format V6
    ├── dedup_geoloc.py        ← dédup géoloc 2025 par id_mutation
    ├── import_dvf_france.py   ← import CSV V6 dans MySQL
    └── create_dvf_france.sql  ← CREATE TABLE + index
```

---

## 4. APIs — Comportement et paramètres

### `api/autocomplete.php`
**Rôle** : suggestions d'adresse à la saisie

**Paramètres GET** : `q` (texte saisi), `limit` (défaut 20), `commune`

**Logique de recherche (paliers)** :
- **S** : split voie/ville automatique (min 3 chars pour la ville)
- **A** : prefix voie + commune (Paris 75xxx prioritaire si pas de ville)
- **A2** : prefix voie sans commune (élargissement)
- **B** : prefix voie sans commune (Paris prioritaire)
- **B2** : `%keyword%` avec commune (gère SAINT/ST)
- **B2b** : `%tokens bruts%` (SAINT non normalisé vs ST)
- **C** : commune/CP seuls

**Normalisation saisie** :
- `typeUserToDb` : "avenue"→"AV", "boulevard"→"BD", "chemin"→"CHE", "route"→"RTE"...
- `voieWordNorm` : "saint"→"ST", "sainte"→"STE", "general"→"GAL", "docteur"→"DR"...
- Détection arrondissement : "paris 8" → `code_commune="75108"`, `commune_like="Paris 8%"`

**Sortie JSON** :
```json
[{
  "label": "RUE DE RIVOLI — 75001 Paris 1er Arrondissement",
  "code_voie": "8249",
  "commune": "Paris 1er Arrondissement",
  "cp": "75001",
  "adresse_nom_voie": "RUE DE RIVOLI",
  "voie": "RUE DE RIVOLI",
  "type_voie": "",
  "no_voie": "",
  "btq": "",
  "section": "AN"
}]
```

---

### `api/surface.php`
**Rôle** : surface indicative médiane pour une adresse

**Paramètres GET** : `code_voie`, `commune`, `no_voie`, `btq`, `voie` (adresse_nom_voie), `cp`, `pieces`

**Niveaux de fallback** :
1. Exact : numéro + voie + commune exacte
2. Par `adresse_code_voie` + commune LIKE
3. Par `adresse_nom_voie` exact + commune LIKE

**Sortie JSON** :
```json
{
  "ok": true,
  "used_level": "street",
  "count": 236,
  "surface": 58,
  "stats": { "p20": 24.4, "median": 57.8, "p80": 129.0 },
  "samples": [5.1, 5.8, ...],
  "unit": "m²"
}
```

---

### `api/mutations.php`
**Rôle** : liste des ventes comparables avec stats

**Paramètres GET** : `code_voie`, `commune`, `no_voie`, `voie`, `surface_min`, `surface_max`, `pieces`

**Niveaux de fallback** : exact → par code_voie → par nom_voie

**Sortie JSON** :
```json
{
  "ok": true,
  "used_level": "street_by_code",
  "count": 240,
  "rows": [{
    "adresse": "188 RUE DE RIVOLI Paris 1er Arrondissement",
    "valeur_fonciere": 4000000,
    "surface": "266.50",
    "prix_m2": 15009,
    "nb_pieces": 0,
    "date_mutation": "2025-06-30",
    "lots_array": ["6"]
  }],
  "stats": { "count": 240, "mean": 12000, "median": 11500, "p20": 9000, "p80": 14000 }
}
```

---

### `api/prix-m2.php`
**Rôle** : stats prix/m² par arrondissement, rue ou évolution

**Modes** : `arrondissements` (défaut), `rues` (+ `cp`), `evolution`

**Paramètres** : `type_local`, `annee_min`, `annee_max`, `pieces`, `ville`, `cp`, `mode`

**Filtres appliqués** : surface ≥ 5m², valeur ≥ 5000€, prix/m² entre 500 et 50 000€, IQR 1.5

---

## 5. Flux de données front-end

```
index.php
  └─ input 'rue de rivoli paris 1'
       └─ api/autocomplete.php?q=...
            └─ sélection suggestion (code_voie, commune, voie, cp...)
                 └─ redirect estimation.php?label=...&code_voie=...&commune=...

estimation.php (avec params URL)
  └─ api/surface.php?code_voie=...&commune=...
       └─ surface indicative → pré-remplit surface, surfaceMin, surfaceMax
  └─ [user ajuste filtres]
  └─ api/mutations.php?code_voie=...&surface_min=...&surface_max=...
       └─ sessionStorage.setItem('estimatiz_results', JSON)
            └─ redirect results.php

results.php
  └─ sessionStorage.getItem('estimatiz_results')
  └─ affichage tableau + estimation P20/médiane/P80
  └─ window.print() → PDF
```

---

## 6. Pipeline de données (_Base/)

### Pour ajouter une nouvelle année (ex: 2020)
```bash
# 1. Déposer ValeursFoncieres-2020.csv dans _Base/
# 2. Dédoublonner
python3 _Base/dedup_legacy.py 2020
# → produit ValeursFoncieres-2020_clean.csv (~32% des lignes conservées)

# 3. Convertir au format V6
python3 _Base/convert_legacy_csv.py 2020
# → produit ValeursFoncieres-2020_v6.csv (format CSV virgule, colonnes V6)

# 4. Importer dans MySQL
python3 _Base/import_dvf_france.py ValeursFoncieres-2020_v6.csv
# (ajouter --truncate uniquement pour vider la table avant)
```

### Pour l'année géoloc (2026 etc.)
```bash
python3 _Base/dedup_geoloc.py 2026
# → groupe par id_mutation, garde meilleure ligne
# → priorité : Appartement(10) > Maison(9) > terrain(3) > Dépendance(1)

python3 _Base/import_dvf_france.py ValeursFoncieres-2026_geoloc_clean.csv
```

---

## 7. Ce qui reste à faire (Étape 5)

- [ ] **Ajouter données 2020–2024** (pipeline prêt, fichiers CSV à déposer)
- [ ] **Adapter api/export.php** (actuellement orphelin)
- [ ] **Décider du sort de api/estimate.php** (orphelin)
- [ ] **Activer filtre géographique** (latitude/longitude en base, rayon autour d'une adresse)
- [ ] **Mettre à jour index.php** : stat "423 000 ventes" → dynamique ou mis à jour (réel : 5,2M)
- [ ] **Tests globaux** sur toutes les pages
- [ ] **Déployer sur o2switch**
- [ ] **Vérifier cohérence** adresse_code_voie entre données legacy et 2020+

---

## 8. Conventions et pièges à connaître

### PHP
- `config.php` charge `config.local.php` si localhost, sinon `config.o2switch.php`
- Toujours utiliser `get_pdo()` et `db_table_dvf()` (pas de hardcode)
- `db_table_dvf()` retourne `` `dvf_france` `` avec backticks
- Pas de `declare(strict_types=1)` dans surface.php et mutations.php

### SQL
- `code_postal BETWEEN '75001' AND '75020'` fonctionne sur VARCHAR numérique
- `YEAR(date_mutation)` pour filtrer par année (date_mutation est DATE)
- `LOWER(nom_commune) LIKE LOWER(:commune_like)` pour éviter les problèmes de casse
- Pour Paris : `code_postal LIKE '75%'` ou `code_commune LIKE '751%'`

### Autocomplete
- `adresse_nom_voie` contient le type ET le nom fusionnés : "AV FOCH" pas "FOCH"
- SAINT parfois écrit en entier, parfois "ST" → utiliser les deux dans les recherches
- Sans ville précisée : résultats triés Paris (75xxx) en premier

### JavaScript (utils.js)
- `formatEuro(n)` → format fr-FR monétaire
- `parseDateToTS(s)` → accepte "YYYY-MM-DD" et "DD/MM/YYYY"
- `percentileArr(arr, p)` → p entre 0 et 1
- `filterIQR(arr, k=1.5)` → filtre outliers
- `EstimatizUtils.setStatus(el, msg, type, html)` → classes CSS status

---

## 9. Fichiers de configuration clés

### config.local.php (local XAMPP)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'DVF_France');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_TABLE_DVF', 'dvf_france');
```

### config.o2switch.php (production)
Même structure, avec les identifiants o2switch.

---

*Document généré le 23/04/2026 — à re-générer après changements majeurs.*
