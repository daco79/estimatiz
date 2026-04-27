# Estimatiz — Correctifs prioritaires (Phase 1.5)

3 fichiers à déployer pour corriger les problèmes critiques de l'audit du 27 avril 2026.

---

## 📦 Contenu du package

```
estimatiz-fixes/
├── .htaccess               → REMPLACE l'actuel à la racine
├── generate_results.py     → REMPLACE l'actuel à la racine
├── mentions-legales.php    → REMPLACE l'actuel à la racine
├── README.md               → Ce fichier
└── CHECKLIST.md            → Checklist post-déploiement
```

---

## 🚨 Ordre de déploiement (important)

### Étape 1 — Mentions légales (5 min, AVANT le force-www)

1. Ouvrir `mentions-legales.php`
2. Choisir le **CAS** correspondant à ta situation :
   - **CAS 1** : particulier (projet personnel, pas de société)
   - **CAS 2** : auto-entrepreneur
   - **CAS 3** : société (SAS, SARL, EURL)
3. Remplir les `[champs entre crochets]` du cas choisi
4. **Supprimer** les blocs commentés des autres cas
5. Sauvegarder

⚠️ **Ne pas déployer le fichier tant que les `[crochets]` sont là** — ce serait
afficher publiquement le template incomplet, ce qui est pire que l'état actuel.

### Étape 2 — `.htaccess` (10 min, demande un test)

⚠️ **Le force-www en HTTPS est une opération à risque** : si HTTPS n'est pas
parfaitement configuré, le site devient inaccessible.

#### Pré-vérifications

Avant de remplacer `.htaccess`, vérifie en navigation privée :

1. ✅ `https://www.estimatiz.fr/` → s'affiche correctement
2. ✅ `https://estimatiz.fr/` → s'affiche correctement (sans www)
3. ✅ Le certificat SSL est valide pour les **deux** versions (avec/sans www)

Si l'une des deux URL ne marche pas en HTTPS, **NE PAS DÉPLOYER** le nouveau
`.htaccess`. Aller d'abord dans le panel o2switch → SSL → activer Let's Encrypt
sur les deux variantes du domaine.

#### Déploiement

1. **Faire un backup** du `.htaccess` actuel :
   ```bash
   ssh user@dark.o2switch.net "cp ~/estimatiz.fr/.htaccess ~/estimatiz.fr/.htaccess.backup-$(date +%Y%m%d)"
   ```

2. Uploader le nouveau `.htaccess`

3. **Tester immédiatement** dans 4 navigateurs / devices :
   - `http://estimatiz.fr/` → doit rediriger vers `https://www.estimatiz.fr/`
   - `https://estimatiz.fr/` → doit rediriger vers `https://www.estimatiz.fr/`
   - `http://www.estimatiz.fr/` → doit rediriger vers `https://www.estimatiz.fr/`
   - `https://www.estimatiz.fr/` → doit rester en `https://www.estimatiz.fr/`
   - `https://www.estimatiz.fr/estimation` → s'ouvre correctement
   - `https://www.estimatiz.fr/api/autocomplete.php?q=rivoli` → renvoie du JSON

4. Si une URL ne marche pas : restaurer le backup immédiatement
   ```bash
   ssh user@dark.o2switch.net "cp ~/estimatiz.fr/.htaccess.backup-YYYYMMDD ~/estimatiz.fr/.htaccess"
   ```

#### Vérifier les headers de sécurité

```bash
curl -I https://www.estimatiz.fr/
```

Doit afficher entre autres :
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()...
```

Test en ligne (recommandé) :
- https://securityheaders.com/?q=estimatiz.fr
- https://observatory.mozilla.org/

### Étape 3 — `generate_results.py` (5 min)

⚠️ **Avant de relancer la génération avec V2** :

#### Si tu as déjà généré des rapports en V1 (avec numéros)

Tu vas devoir **supprimer les rapports V1** avant de générer en V2, sinon tu
auras les anciens (par numéro) ET les nouveaux (par rue) dans le même dossier.

```bash
# Sur o2switch, en SSH :
cd ~/estimatiz.fr/rapports/automatique/
rm -rf 2026/   # ou l'année concernée

# Vider aussi le sitemap des rapports :
cat > ~/estimatiz.fr/sitemap-rapports.xml <<'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>
EOF
```

#### Si tu n'as encore rien généré

Remplace simplement `generate_results.py` à la racine du projet (en local, pas
sur o2switch — ce script tourne en local sur ta base MySQL).

#### Test V2

```bash
# Test sur une seule rue, pour vérifier que ça marche
python3 generate_results.py --voie "RUE VOLTAIRE" --commune "Paris"
```

Tu dois voir :
```
  Génération : RUE VOLTAIRE, Paris

  XXX ventes | P20=... · P50=... · P80=... €/m² | confiance XX%
    ✓ Rue Voltaire, Paris 11e Arrondissement → http://localhost/estimatiz/rapports/automatique/2026/...

  ✓ 1 rapport généré
```

→ **1 rapport**, pas 80 comme avant.

---

## ✅ Récapitulatif des changements

| Fichier | Avant | Après | Impact |
|---|---|---|---|
| `.htaccess` | Pas de headers sécurité, pas de force-www | 4 headers + force-www-https | Score Mozilla Observatory de F → A · canonicals respectés |
| `mentions-legales.php` | Placeholders `[à compléter]` | Template avec 3 cas, à choisir | Conformité LCEN |
| `generate_results.py` | 1 rapport par numéro (80 par rue) | 1 rapport par rue | Pas de pénalité Panda · contenu plus dense |

---

## 🔍 Vérifications post-déploiement

Voir `CHECKLIST.md` pour la liste complète.

---

## ❓ FAQ

### "Si je casse le site avec le force-www, comment je fais ?"

Le serveur o2switch garde toujours un accès SSH même si le `.htaccess` est cassé.
Tu peux toujours te connecter et restaurer le backup du `.htaccess`.

Si tu n'as pas SSH (FTP only), tu peux passer par cPanel → File Manager pour
remplacer le fichier.

### "HSTS, je l'active quand ?"

**Pas tout de suite.** HSTS bloque l'accès HTTP pendant 1 an pour TOUS les sous-domaines
(si `includeSubDomains` est actif). Si demain tu crées `dev.estimatiz.fr` sans HTTPS,
il sera bloqué.

Étapes recommandées :
1. Déployer ces correctifs sans HSTS
2. Laisser tourner 1 mois sans incident
3. Activer HSTS avec `max-age=300` (5 min) pour tester
4. Si OK, passer à `max-age=31536000` (1 an)
5. Demander la soumission au [HSTS preload list](https://hstspreload.org/) (optionnel)

### "Je veux garder les anciens rapports V1 pour ne pas perdre le SEO acquis"

Si tu as déjà soumis des URLs V1 à Google et qu'elles sont indexées :
- ⚠️ Faire un `301` de chaque URL V1 vers la nouvelle URL V2 (la rue, sans numéro)
- Ou laisser un `noindex` sur les anciennes pendant que tu déploies les V2
- Ou garder les V1 ET ajouter V2 (mais alors thin content garanti)

Le plus propre = supprimer les V1 si elles sont peu indexées encore.

---

## 📞 En cas de souci

Si quelque chose ne marche pas après déploiement :
- Restaurer les backups
- M'envoyer le message d'erreur exact
- M'envoyer le résultat de `curl -I https://www.estimatiz.fr/`
