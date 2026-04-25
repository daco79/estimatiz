-- =============================================================================
-- DVF_France — Création de la base et de la table principale
-- Structure : snake_case, types propres, géolocalisation WGS-84
-- Généré le 2026-04-16 pour Estimatiz V6
-- =============================================================================

CREATE DATABASE IF NOT EXISTS DVF_France
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE DVF_France;

DROP TABLE IF EXISTS dvf_france;

CREATE TABLE dvf_france (
  id                          INT UNSIGNED      NOT NULL AUTO_INCREMENT,

  -- Identifiants de mutation
  id_mutation                 VARCHAR(20)       DEFAULT NULL,
  date_mutation               DATE              DEFAULT NULL,
  numero_disposition          VARCHAR(10)       DEFAULT NULL,
  nature_mutation             VARCHAR(100)      DEFAULT NULL,
  valeur_fonciere             DECIMAL(15,2)     DEFAULT NULL,

  -- Adresse
  adresse_numero              VARCHAR(10)       DEFAULT NULL,
  adresse_suffixe             VARCHAR(5)        DEFAULT NULL,
  adresse_nom_voie            VARCHAR(255)      DEFAULT NULL,
  adresse_code_voie           CHAR(4)           DEFAULT NULL,
  code_postal                 VARCHAR(5)        DEFAULT NULL,

  -- Commune
  code_commune                VARCHAR(5)        DEFAULT NULL,
  nom_commune                 VARCHAR(100)      DEFAULT NULL,
  code_departement            VARCHAR(3)        DEFAULT NULL,
  ancien_code_commune         VARCHAR(5)        DEFAULT NULL,
  ancien_nom_commune          VARCHAR(100)      DEFAULT NULL,

  -- Parcelle
  id_parcelle                 VARCHAR(14)       DEFAULT NULL,
  ancien_id_parcelle          VARCHAR(14)       DEFAULT NULL,
  numero_volume               VARCHAR(20)       DEFAULT NULL,

  -- Lots (jusqu'à 5)
  lot1_numero                 VARCHAR(20)       DEFAULT NULL,
  lot1_surface_carrez         DECIMAL(10,2)     DEFAULT NULL,
  lot2_numero                 VARCHAR(20)       DEFAULT NULL,
  lot2_surface_carrez         DECIMAL(10,2)     DEFAULT NULL,
  lot3_numero                 VARCHAR(20)       DEFAULT NULL,
  lot3_surface_carrez         DECIMAL(10,2)     DEFAULT NULL,
  lot4_numero                 VARCHAR(20)       DEFAULT NULL,
  lot4_surface_carrez         DECIMAL(10,2)     DEFAULT NULL,
  lot5_numero                 VARCHAR(20)       DEFAULT NULL,
  lot5_surface_carrez         DECIMAL(10,2)     DEFAULT NULL,
  nombre_lots                 SMALLINT UNSIGNED DEFAULT NULL,

  -- Local
  code_type_local             VARCHAR(5)        DEFAULT NULL,
  type_local                  VARCHAR(50)       DEFAULT NULL,
  surface_reelle_bati         DECIMAL(10,2)     DEFAULT NULL,
  nombre_pieces_principales   TINYINT UNSIGNED  DEFAULT NULL,

  -- Culture (terrains)
  code_nature_culture         VARCHAR(5)        DEFAULT NULL,
  nature_culture              VARCHAR(100)      DEFAULT NULL,
  code_nature_culture_speciale VARCHAR(5)       DEFAULT NULL,
  nature_culture_speciale     VARCHAR(100)      DEFAULT NULL,
  surface_terrain             DECIMAL(10,2)     DEFAULT NULL,

  -- Géolocalisation WGS-84
  longitude                   DECIMAL(10,7)     DEFAULT NULL,
  latitude                    DECIMAL(10,7)     DEFAULT NULL,

  PRIMARY KEY (id),

  -- Index pour les patterns de requête Estimatiz
  INDEX idx_code_voie_commune   (adresse_code_voie, code_commune),
  INDEX idx_nom_voie_commune    (nom_commune(50), adresse_nom_voie(100)),
  INDEX idx_code_commune        (code_commune),
  INDEX idx_code_postal         (code_postal),
  INDEX idx_date_mutation       (date_mutation),
  INDEX idx_type_local          (type_local(30)),
  INDEX idx_id_mutation         (id_mutation),
  INDEX idx_id_parcelle         (id_parcelle),
  
  -- Index pour l'autocomplete (adresse_numero, suffixe, recherche)
  INDEX idx_adresse_nom_voie    (adresse_nom_voie(100)),
  INDEX idx_adresse_numero      (adresse_numero),
  INDEX idx_adresse_suffixe     (adresse_suffixe),
  INDEX idx_nom_commune         (nom_commune(100)),
  INDEX idx_search_adresse      (adresse_nom_voie(100), nom_commune(100), code_postal)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
