# Checklist post-déploiement Phase 1.5

À cocher après déploiement des 3 correctifs.

## Mentions légales

- [ ] Le bon CAS (1, 2 ou 3) est conservé, les autres supprimés
- [ ] Aucun `[crochet]` ne reste dans le fichier
- [ ] Email `contact@estimatiz.fr` actif (ou remplacé par un email valide)
- [ ] Page `https://www.estimatiz.fr/mentions-legales` s'affiche correctement
- [ ] Lien depuis le footer fonctionne

## .htaccess — Force www + HTTPS

- [ ] Backup `.htaccess.backup-YYYYMMDD` créé avant remplacement
- [ ] `http://estimatiz.fr/` redirige vers `https://www.estimatiz.fr/` (test navigateur privé)
- [ ] `https://estimatiz.fr/` redirige vers `https://www.estimatiz.fr/`
- [ ] `http://www.estimatiz.fr/` redirige vers `https://www.estimatiz.fr/`
- [ ] `https://www.estimatiz.fr/estimation` s'affiche correctement
- [ ] `https://www.estimatiz.fr/api/autocomplete.php?q=rivoli` renvoie du JSON
- [ ] `https://www.estimatiz.fr/sitemap.xml` accessible

## .htaccess — Headers de sécurité

Tester avec `curl -I https://www.estimatiz.fr/` ou https://securityheaders.com/

- [ ] Header `X-Content-Type-Options: nosniff` présent
- [ ] Header `X-Frame-Options: SAMEORIGIN` présent
- [ ] Header `Referrer-Policy: strict-origin-when-cross-origin` présent
- [ ] Header `Permissions-Policy: ...` présent
- [ ] Score sur https://securityheaders.com/ : minimum **B** (idéalement A)
- [ ] Score sur https://observatory.mozilla.org/ : amélioré

## .htaccess — Compression et cache

- [ ] CSS/JS retourne avec header `Content-Encoding: gzip`
  ```bash
  curl -H "Accept-Encoding: gzip" -I https://www.estimatiz.fr/assets/css/site.css
  ```
- [ ] Header `Cache-Control` ou `Expires` présent sur les assets statiques

## .htaccess — Fichiers privés bloqués

- [ ] `https://www.estimatiz.fr/config.local.php` → 403 Forbidden
- [ ] `https://www.estimatiz.fr/.htaccess` → 403 Forbidden
- [ ] `https://www.estimatiz.fr/_Base/` → 403 Forbidden
- [ ] `https://www.estimatiz.fr/lib/cache.php` → 403 Forbidden
- [ ] `https://www.estimatiz.fr/includes/nav.php` → 403 Forbidden

## generate_results.py V2

- [ ] Ancien dossier `rapports/automatique/2026/` vidé (si rapports V1 existaient)
- [ ] Sitemap `sitemap-rapports.xml` réinitialisé (si rapports V1 existaient)
- [ ] Test sur 1 rue : `python3 generate_results.py --voie "RUE VOLTAIRE" --commune "Paris"`
- [ ] Le test génère **1 rapport** (pas 80)
- [ ] Le rapport généré s'affiche correctement en local
- [ ] Le sitemap-rapports.xml contient bien l'URL générée

## Google Search Console (à faire après les correctifs)

- [ ] Soumettre la nouvelle version du sitemap : `https://www.estimatiz.fr/sitemap.xml`
- [ ] Vérifier dans "Couverture" qu'aucune nouvelle erreur n'est apparue
- [ ] Vérifier dans "Améliorations > Données structurées" que les schemas sont reconnus
- [ ] Si présents avant : vérifier que les URLs `estimatiz.fr` (sans www) sont bien marquées comme dupliquées et redirigées vers `www.estimatiz.fr`

## Tests fonctionnels

- [ ] Recherche d'adresse en autocomplete fonctionne
- [ ] Estimation d'un bien fonctionne (parcours complet `/` → `/estimation` → `/results`)
- [ ] Page `/prix-m2` charge le graphique d'évolution
- [ ] Page `/ventes` charge les dernières ventes
- [ ] Footer affiche bien les liens vers toutes les pages
- [ ] Aucun lien cassé dans le footer (404)

## Validation extérieure

- [ ] Tester sur https://search.google.com/test/rich-results l'URL `https://www.estimatiz.fr/`
- [ ] Tester l'URL `/donnees` (Dataset schema)
- [ ] Tester l'URL `/methodologie` (HowTo schema)
- [ ] Tester l'URL `/faq` (FAQPage schema)
- [ ] Tester l'URL `/contact` (ContactPage schema)
