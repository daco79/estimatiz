-- =============================================================================
-- build_dvf_voies.sql — Reconstruit la table dvf_voies depuis dvf_france
-- Équivalent SQL de build_dvf_voies.py
-- À relancer après chaque import annuel.
-- ATTENTION : opération longue (~3-5 min sur 13M lignes) — préférer SSH
-- =============================================================================

-- Étape 1 : Créer la table si elle n'existe pas encore
CREATE TABLE IF NOT EXISTS dvf_voies (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  adresse_nom_voie  VARCHAR(255)  DEFAULT NULL,
  code_postal       VARCHAR(5)    DEFAULT NULL,
  code_commune      VARCHAR(5)    DEFAULT NULL,
  nom_commune       VARCHAR(100)  DEFAULT NULL,
  adresse_code_voie CHAR(4)       DEFAULT NULL,
  section           VARCHAR(2)    DEFAULT NULL,
  nb_mutations      INT UNSIGNED  DEFAULT 0,
  PRIMARY KEY (id),
  INDEX idx_nom_voie_cp   (adresse_nom_voie(100), code_postal),
  INDEX idx_code_commune  (code_commune),
  INDEX idx_code_voie     (adresse_code_voie),
  INDEX idx_nom_commune   (nom_commune(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Étape 2 : Vider la table
TRUNCATE TABLE dvf_voies;

-- Étape 3 : Insérer les voies uniques agrégées
INSERT INTO dvf_voies
  (adresse_nom_voie, code_postal, code_commune, nom_commune, adresse_code_voie, section, nb_mutations)
SELECT
    adresse_nom_voie,
    code_postal,
    code_commune,
    nom_commune,
    adresse_code_voie,
    MIN(SUBSTRING(id_parcelle, 9, 2)) AS section,
    COUNT(*)                          AS nb_mutations
FROM dvf_france
WHERE adresse_nom_voie IS NOT NULL
  AND adresse_nom_voie != ''
GROUP BY
    adresse_nom_voie,
    code_postal,
    code_commune,
    nom_commune,
    adresse_code_voie;
