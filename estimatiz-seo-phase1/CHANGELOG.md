# CHANGELOG — Estimatiz Phase SEO 1

Date : 2026-04-26

## 🆕 Nouveaux fichiers

### `/mentions-legales.php`
Nouvelle page légale obligatoire (LCEN art. 6 III 1°). Contient :
- Identification de l'éditeur (à compléter)
- Hébergeur (O2switch)
- Propriété intellectuelle
- Lien vers Licence Ouverte Etalab pour DVF
- Contact

### `/confidentialite.php`
Nouvelle page RGPD. Contient :
- Engagement de non-collecte intrusive
- Liste des données traitées
- Mention sessionStorage (transparent)
- Pas de cookies tracking
- Droits RGPD complets (accès, rectification, etc.)
- Lien CNIL

### `/includes/seo-extras.php`
Include partagé pour métadonnées SEO communes :
- Twitter Cards (summary_large_image, paramétrable)
- og:image:width / og:image:height (1200×630)
- og:image:alt
- og:site_name
- meta theme-color (#1E3A8A)
- meta robots avec max-image-preview:large
- **JSON-LD Organization** (sitewide)

### `/includes/footer.php`
Footer enrichi 4 colonnes (Outils / Comprendre / Estimatiz / À propos)
avec maillage interne complet vers toutes les pages clés.
CSS inline scopé.

---

## ♻️ Fichiers remplacés

### `/.htaccess`
**Ajouts :**
- Compression GZIP étendue (XML, woff2)
- Cache navigateur affiné (HTML, fonts, icônes)
- Headers de sécurité : X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy
- Redirection 301 non-www → www
- Blocage 403 sur `_Base/` et `_Backup/`
- Blocage `deploy.sh` et `restore.py`
- HSTS prêt à activer (commenté par défaut)

### `/sitemap.xml`
**Ajouts :**
- `<lastmod>` sur chaque URL (date 2026-04-26)
- 2 nouvelles URLs : `/mentions-legales` et `/confidentialite`
- Refonte des `<changefreq>` et `<priority>` :
  - `/` weekly (avant : monthly)
  - `/ventes` daily (avant : weekly)
  - `/prix-m2` weekly (avant : monthly)
  - Pages éditoriales : monthly
  - Pages légales : yearly

---

## 🔧 Patches `<head>` (10 fichiers)

| Page | Title avant | Title après |
|---|---|---|
| `/` | Estimation immobilière en ligne gratuite \| Estimer son appartement | Estimation immobilière en ligne gratuite – Prix au m² DVF \| Estimatiz |
| `/estimation` | (variable) | Estimer son bien immobilier — Formulaire d'estimation \| Estimatiz |
| `/results` | Estimatiz – Résultats | Résultats d'estimation immobilière \| Estimatiz (+ noindex temporaire) |
| `/prix-m2` | Prix immobilier au m² par ville et département – Estimatiz | Prix au m² par ville et département en France \| Estimatiz |
| `/ventes` | Dernières ventes immobilières – Estimatiz | Dernières ventes immobilières en France (DVF 2014–2025) \| Estimatiz |
| `/donnees` | Données utilisées – Estimatiz | Données DVF utilisées par Estimatiz — Source officielle DGFiP |
| `/methodologie` | Méthodologie – Estimatiz | Méthodologie d'estimation immobilière — Comment Estimatiz calcule les prix |
| `/faq` | FAQ – Estimatiz | FAQ — Estimation immobilière, méthode et données \| Estimatiz |
| `/a-propos` | À propos – Estimatiz | À propos d'Estimatiz — Outil indépendant d'estimation immobilière |
| `/contact` | Contact – Estimatiz | Contact Estimatiz — Signaler une erreur, poser une question |

### Schemas JSON-LD ajoutés

| Page | Schema(s) ajouté(s) |
|---|---|
| `/` | **WebApplication** (NOUVEAU), WebSite enrichi (publisher) |
| `/donnees` | **Dataset** (NOUVEAU) |
| `/methodologie` | **HowTo** (NOUVEAU, 5 steps) |
| `/contact` | **ContactPage** + **ContactPoint** (NOUVEAU) |
| `/faq` | BreadcrumbList (NOUVEAU, en plus du FAQPage existant) |
| `/a-propos` | BreadcrumbList (NOUVEAU) |
| `/contact` | BreadcrumbList (NOUVEAU) |
| **Toutes** | **Organization** (sitewide via seo-extras.php) |

### Twitter Cards
Ajoutées sur les 10 pages via `seo-extras.php` (summary_large_image avec
titre/description paramétrables par page).

### Autres améliorations `<head>`
- `meta theme-color` (sitewide)
- `meta author`
- `og:image:width` / `og:image:height` / `og:image:alt`
- `og:site_name`
- `meta robots` avec `max-image-preview:large`
- `preconnect` + `dns-prefetch` vers `cdn.jsdelivr.net` sur `/prix-m2`
- `defer` sur Chart.js sur `/prix-m2`
- Meta keywords sur `/` (utile pour Bing/Yandex, neutre pour Google)

---

## 🐛 Corrections

### `/a-propos.php` — Coquille
`"que la plupart des particuliers n't ont pas"`
→ `"que la plupart des particuliers n'ont pas"`

---

## 📈 Impact attendu

### Court terme (2–4 semaines après déploiement)
- Apparition des **rich results FAQ + HowTo + Dataset** dans Google
- Amélioration du **CTR** grâce aux titles optimisés
- **Indexation des 2 nouvelles pages** (mentions, confidentialité)
- **Conformité RGPD** restaurée
- **Signal entité** (Organization sitewide) renforcé
- **Note de sécurité** Mozilla Observatory : +20 à +30 points

### Moyen terme (1–3 mois)
- Hausse du **trafic de marque** (signal Organization + cohérence ON-page)
- Hausse de **15–30 % d'impressions** sur pages secondaires (titles enrichis)
- Meilleur **PageSpeed** sur `/prix-m2` (preconnect + defer)

### Mesures à suivre
- Search Console → Couverture (les 11 URLs doivent toutes être indexées sous 2 sem.)
- Search Console → Améliorations → tous les schemas doivent être détectés
- Mozilla Observatory (https://observatory.mozilla.org/)
- PageSpeed Insights sur les 4 pages clés (`/`, `/prix-m2`, `/ventes`, `/methodologie`)
