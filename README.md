# Estimatiz

Outil d'estimation immobilière basé sur les ventes réelles issues des Demandes de Valeurs Foncières (DVF) publiées par la DGFiP.

Couverture : France entière, 2014 → 2025 (13 millions de transactions).

---

## Fonctionnement général

L'utilisateur saisit une adresse sur la page d'accueil. L'autocomplete suggère des rues en temps réel depuis la base `dvf_voies`. Une fois la rue sélectionnée, l'application cherche les ventes comparables sur la même rue et calcule un prix au m² statistique (p20 / médiane / p80). L'estimation finale est affichée avec les mutations de référence.

```
Utilisateur → index.php → estimation.php → results.php
                              ↑                  ↑
                         api/autocomplete    api/surface
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
| `prix-m2.php` | `/prix-m2` | Carte/tableau des prix au m² par commune ou par rue |
| `donnees.php` | `/donnees` | Présentation des données sources (DVF) |
| `methodologie.php` | `/methodologie` | Explication de la méthode de calcul |
| `faq.php` | `/faq` | Questions fréquentes |
| `a-propos.php` | `/a-propos` | À propos du projet |
| `contact.php` | `/contact` | Formulaire de contact |

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

### `api/prix-m2.php` — V3.0

Prix au m² agrégé par arrondissement ou par rue.

**Paramètres GET :**
- `commune` — filtre commune
- `voie` *(optionnel)* — filtre rue (mode rue)
- `annee` *(optionnel)* — filtre année

**Retourne :** `{ ok, stats: { p20, p50, p80, count }, rows[] }`

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

## Notes techniques

- **Format `adresse_nom_voie`** : type et nom fusionnés, ex `"RUE DE RIVOLI"`, `"AV FOCH"`. Pas de colonne type_voie séparée.
- **`code_commune`** : INSEE 5 chars. Paris 1er = `75101` … 20e = `75120`.
- **`id_parcelle`** : 14 chars. `section = SUBSTRING(id_parcelle, 9, 2)`.
- **innodb_buffer_pool_size** : XAMPP démarre à 16 MB. Les scripts `build_dvf_voies.py` et `import_dvf_france_automatique.py` le montent temporairement à 512 MB pour les opérations massives.
- **Cache API** : préfixe `acV7_` pour l'autocomplete, `pm2v6_` pour prix-m2.
