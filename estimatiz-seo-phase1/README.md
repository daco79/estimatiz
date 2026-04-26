# Estimatiz — Phase SEO 1 (Quick wins)

Ce dossier contient toutes les modifications de la **Phase 1** du plan SEO.
Elles sont à fort impact, sans risque pour la stabilité, et exécutables en 30 à 60 minutes.

---

## 📦 Contenu du livrable

```
estimatiz-seo-phase1/
├── README.md                    ← ce fichier
├── CHANGELOG.md                 ← résumé des changements
│
├── .htaccess                    ← REMPLACE l'existant à la racine
├── sitemap.xml                  ← REMPLACE l'existant à la racine
│
├── mentions-legales.php         ← NOUVEAU fichier à la racine
├── confidentialite.php          ← NOUVEAU fichier à la racine
│
├── includes/
│   ├── seo-extras.php           ← NOUVEAU fichier dans includes/
│   └── footer.php               ← NOUVEAU fichier dans includes/
│
└── patches/                     ← Instructions de modification du <head>
    ├── 01-index.php.head.txt
    ├── 02-estimation.php.head.txt
    ├── 03-results.php.head.txt
    ├── 04-prix-m2.php.head.txt
    ├── 05-ventes.php.head.txt
    ├── 06-donnees.php.head.txt
    ├── 07-methodologie.php.head.txt
    ├── 08-faq.php.head.txt
    ├── 09-a-propos.php.head.txt   (+ 1 correction dans le body)
    └── 10-contact.php.head.txt
```

---

## 🚀 Procédure de déploiement (recommandée)

### Étape 1 — Backup (obligatoire)

```bash
# Sur ton poste local, dans le dépôt git
git checkout -b seo-phase1
git status   # vérifier que la branche est propre
```

### Étape 2 — Fichiers à remplacer (copier directement)

| Source dans le livrable           | Destination dans le repo            |
|-----------------------------------|-------------------------------------|
| `.htaccess`                       | `/.htaccess` (RACINE)               |
| `sitemap.xml`                     | `/sitemap.xml` (RACINE)             |
| `mentions-legales.php`            | `/mentions-legales.php` (NOUVEAU)   |
| `confidentialite.php`             | `/confidentialite.php` (NOUVEAU)    |
| `includes/seo-extras.php`         | `/includes/seo-extras.php` (NOUVEAU)|
| `includes/footer.php`             | `/includes/footer.php` (NOUVEAU)    |

### Étape 3 — Patches `<head>` page par page

Pour chaque fichier `patches/0X-XXXX.php.head.txt` :

1. Ouvre le fichier de patch
2. Lis l'objectif et l'opération
3. Ouvre le fichier PHP correspondant dans ton repo
4. **Remplace** l'intégralité du bloc `<head>...</head>` par le bloc fourni
5. Conserve TOUT le reste (style inline, body, scripts, includes)

⚠️ Cas particulier — `09-a-propos.php.head.txt` :
contient en plus une correction de coquille dans le BODY (`n't ont pas` → `n'ont pas`).

⚠️ Cas particulier — `08-faq.php.head.txt` :
le schéma JSON-LD `FAQPage` existant doit être CONSERVÉ tel quel.

### Étape 4 — Footer

Le nouveau footer enrichi (`includes/footer.php`) peut être adopté de deux manières :

**Option A — Progressive (recommandée) :**
Sur chaque page, remplace l'ancien `<footer>...</footer>` par :
```php
<?php include 'includes/footer.php'; ?>
```

**Option B — Conservatrice :**
Garde les anciens footers. Le nouveau ne sera utilisé que dans les NOUVELLES pages
(`mentions-legales.php`, `confidentialite.php`). Tu pourras migrer plus tard.

### Étape 5 — Lien "Mentions légales" dans la nav

Optionnel mais recommandé : ajoute un lien vers `mentions-legales` et `confidentialite`
dans le menu mobile et dans le dropdown "À propos" de `includes/nav.php`.

### Étape 6 — Mentions légales : remplir tes infos

Le fichier `mentions-legales.php` contient des `[crochets]` à compléter :
- Nom du responsable de publication
- Forme juridique
- SIREN/SIRET (si applicable)
- Adresse postale
- Email

C'est une **obligation légale** (article 6 III 1° de la LCEN). Sans ces infos,
le site ne peut pas être considéré comme conforme.

### Étape 7 — Tests locaux

```bash
# Lance le serveur local (XAMPP)
# Ouvre dans le navigateur :
http://localhost/estimatiz/
http://localhost/estimatiz/mentions-legales
http://localhost/estimatiz/confidentialite
http://localhost/estimatiz/methodologie

# Vérifie qu'aucune page ne renvoie d'erreur PHP
# Vérifie que les nouveaux schemas apparaissent dans le source HTML
```

### Étape 8 — Validation des données structurées

Une fois en production, teste sur ces deux outils :
- **Rich Results Test** : https://search.google.com/test/rich-results
- **Schema.org Validator** : https://validator.schema.org/

Pages à tester en priorité :
- `/` → doit afficher WebSite + WebApplication + Organization
- `/donnees` → doit afficher Dataset + BreadcrumbList + Organization
- `/methodologie` → doit afficher HowTo + BreadcrumbList + Organization
- `/faq` → doit afficher FAQPage + BreadcrumbList + Organization

### Étape 9 — Soumettre le sitemap mis à jour

Dans Google Search Console :
1. Aller dans **Sitemaps**
2. Soumettre à nouveau `https://www.estimatiz.fr/sitemap.xml`
3. Demander l'**indexation des deux nouvelles pages** :
   - `/mentions-legales`
   - `/confidentialite`

### Étape 10 — Déploiement

```bash
git add -A
git commit -m "feat(seo): phase 1 - schemas, meta, légal, sitemap, sécurité"
git push origin seo-phase1
# Merge vers main, puis ./deploy.sh
```

---

## ⚠️ Points d'attention

1. **`.htaccess`** : le nouveau force le `www`. Vérifie que ton domaine principal
   est bien `www.estimatiz.fr` (et non `estimatiz.fr`). Sinon, commente la
   redirection `RewriteRule ^ https://www.%{HTTP_HOST}%{REQUEST_URI}`.

2. **HSTS** : la directive est commentée par défaut. Active-la SEULEMENT quand
   tu es sûr que tout passe en HTTPS sans erreur (sinon tu peux te bloquer).

3. **Chart.js avec `defer`** : si ton bloc Chart.js exécute du code en haut de
   page hors `DOMContentLoaded`, retire le `defer`. Sinon c'est OK.

4. **Mentions légales** : si tu n'as pas encore de structure juridique,
   indique au minimum : nom complet, email, statut "particulier", adresse.

5. **Search Console** : surveille pendant 7-14 jours après mise en ligne.
   Tu devrais voir apparaître les nouveaux types structurés et une légère
   hausse d'impressions sur les pages secondaires.

---

## 📊 Ce que cette phase apporte (récap)

| Levier | Avant | Après |
|---|---|---|
| Pages indexables | 9 | 11 (+ légales) |
| URLs avec `<lastmod>` | 0 | 11 |
| Schemas JSON-LD | 5 | 13 |
| Twitter Cards | 0 | 11 |
| Headers de sécurité | 0 | 4 |
| Conformité RGPD | ❌ | ✅ |
| Force www | ❌ | ✅ |
| `og:image` dim explicites | ❌ | ✅ |

---

## ⏭️ Phases suivantes (rappel)

- **Phase 2** (3 à 7 jours) : breadcrumbs visibles, balises sémantiques `<main>/<header>`, refonte `/results` indexable, CTA croisés, blocs trust.
- **Phase 3** (2 à 6 semaines) : pages programmatiques `/prix-m2/[ville]`, `/prix-m2/[département]`, `/prix-m2/[ville]/[arrondissement]` (ROI maximal).
- **Phase 4** (3 à 6 mois) : blog éditorial + netlinking + monitoring.
