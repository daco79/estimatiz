#!/bin/bash
# =============================================================================
# deploy.sh — Déploiement Estimatiz → o2switch
# Usage : ./deploy.sh
# =============================================================================

# ── Configuration (à adapter) ─────────────────────────────────────────────────
SSH_USER="zece2169"
SSH_HOST="dark.o2switch.net"          # ou cluster0XX.hosting.ovh.net
REMOTE_DIR="estimatiz.fr"                      # répertoire racine du site sur o2switch
ZIP_NAME="estimatiz_v7_deploy.zip"
LOCAL_DIR="$(cd "$(dirname "$0")" && pwd)"

# ── Couleurs terminal ─────────────────────────────────────────────────────────
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}=== Déploiement Estimatiz → o2switch ===${NC}"
echo ""

# ── Étape 1 : Créer le zip ────────────────────────────────────────────────────
echo -e "${BLUE}[1/4] Création du zip...${NC}"
cd "$LOCAL_DIR"
rm -f "$ZIP_NAME"

zip -q -r "$ZIP_NAME" \
  index.php estimation.php results.php prix-m2.php ventes.php \
  donnees.php methodologie.php faq.php a-propos.php contact.php \
  mentions-legales.php confidentialite.php \
  config.php \
  favicon.ico \
  .htaccess robots.txt sitemap.xml sitemap-site.xml sitemap-rapports.xml \
  api/autocomplete.php api/surface.php api/mutations.php \
  api/prix-m2.php api/ventes.php api/export.php api/estimate.php \
  api/save-rapport.php api/save-rapport-seo.php \
  includes/ \
  lib/ \
  assets/ \
  rapports/automatique/ \
  -x "*.DS_Store"

if [ $? -ne 0 ]; then
  echo -e "${RED}Erreur lors de la création du zip.${NC}"
  exit 1
fi

SIZE=$(du -sh "$ZIP_NAME" | cut -f1)
echo -e "${GREEN}✓ $ZIP_NAME créé ($SIZE)${NC}"

# ── Étape 2 : Upload vers o2switch ────────────────────────────────────────────
echo -e "${BLUE}[2/4] Upload vers o2switch...${NC}"
scp "$ZIP_NAME" "${SSH_USER}@${SSH_HOST}:~/"

if [ $? -ne 0 ]; then
  echo -e "${RED}Erreur SCP. Vérifiez SSH_USER et SSH_HOST.${NC}"
  exit 1
fi

echo -e "${GREEN}✓ Upload terminé${NC}"

# ── Étape 3 : Décompresser sur le serveur ─────────────────────────────────────
echo -e "${BLUE}[3/4] Décompression sur le serveur...${NC}"
ssh "${SSH_USER}@${SSH_HOST}" bash << ENDSSH
  set -e
  echo "  → Décompression dans ~/${REMOTE_DIR}/"
  unzip -q -o ~/${ZIP_NAME} -d ~/${REMOTE_DIR}/
  echo "  → Nettoyage du zip distant"
  rm -f ~/${ZIP_NAME}
  echo "  → OK"
ENDSSH

if [ $? -ne 0 ]; then
  echo -e "${RED}Erreur lors de la décompression sur le serveur.${NC}"
  exit 1
fi

echo -e "${GREEN}✓ Décompression réussie${NC}"

# ── Étape 4 : Nettoyage local ─────────────────────────────────────────────────
echo -e "${BLUE}[4/4] Nettoyage local...${NC}"
rm -f "$LOCAL_DIR/$ZIP_NAME"
echo -e "${GREEN}✓ Zip local supprimé${NC}"

echo ""
echo -e "${GREEN}=== Déploiement terminé ===${NC}"
echo -e "Site : https://www.estimatiz.fr/"
