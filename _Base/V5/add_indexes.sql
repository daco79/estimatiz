-- Index pour accélérer les requêtes Estimatiz sur data_paris_I
-- À exécuter une seule fois dans phpMyAdmin ou via mysql en ligne de commande.
-- Attention : sur o2switch, adapte le nom de base si besoin avant d'exécuter.
-- Exemple local :
--   USE `CSV_DB 6`;
-- Exemple o2switch :
--   USE `nom_de_ta_base_o2switch`;

USE `CSV_DB 6`;

-- ---------------------------------------------------------------------------
-- Index simples utiles aux recherches directes et à l'autocomplétion
-- ---------------------------------------------------------------------------

CREATE INDEX idx_voie       ON `data_paris_I` (`Voie`);
CREATE INDEX idx_cp         ON `data_paris_I` (`Code postal`);
CREATE INDEX idx_commune    ON `data_paris_I` (`Commune`);
CREATE INDEX idx_code_voie  ON `data_paris_I` (`Code voie`);
CREATE INDEX idx_type_voie  ON `data_paris_I` (`Type de voie`);
CREATE INDEX idx_type_local ON `data_paris_I` (`Type local`);
CREATE INDEX idx_pieces     ON `data_paris_I` (`Nombre pieces principales`);
CREATE INDEX idx_date_mut   ON `data_paris_I` (`Date mutation`);
CREATE INDEX idx_section    ON `data_paris_I` (`Section`);

-- ---------------------------------------------------------------------------
-- Index composés pour api/autocomplete.php
-- Recherche par voie, commune/code postal et type de voie
-- ---------------------------------------------------------------------------

CREATE INDEX idx_ac_voie_cp_commune
  ON `data_paris_I` (`Voie`, `Code postal`, `Commune`);

CREATE INDEX idx_ac_typevoie_voie_cp
  ON `data_paris_I` (`Type de voie`, `Voie`, `Code postal`);

-- ---------------------------------------------------------------------------
-- Index composés pour api/surface.php et api/mutations.php
-- Recherche par rue/adresse, puis filtre éventuel par nombre de pièces
-- ---------------------------------------------------------------------------

CREATE INDEX idx_mut_codevoie_commune_pieces
  ON `data_paris_I` (`Code voie`, `Commune`, `Nombre pieces principales`);

CREATE INDEX idx_mut_adresse
  ON `data_paris_I` (`Commune`, `No voie`, `Type de voie`, `Voie`);

CREATE INDEX idx_mut_adresse_pieces
  ON `data_paris_I` (`Commune`, `No voie`, `Type de voie`, `Voie`, `Nombre pieces principales`);

-- ---------------------------------------------------------------------------
-- Index composés pour api/prix-m2.php
-- Stats par code postal, type de bien, pièces et période
-- ---------------------------------------------------------------------------

CREATE INDEX idx_pm2_cp_type_pieces
  ON `data_paris_I` (`Code postal`, `Type local`, `Nombre pieces principales`);

CREATE INDEX idx_pm2_cp_type_date
  ON `data_paris_I` (`Code postal`, `Type local`, `Date mutation`);

-- ---------------------------------------------------------------------------
-- Index composés pour api/estimate.php et api/export.php
-- Scopes rue / section / commune avec type local et pièces
-- ---------------------------------------------------------------------------

CREATE INDEX idx_est_street
  ON `data_paris_I` (`Code voie`, `Commune`, `Type local`, `Nombre pieces principales`);

CREATE INDEX idx_est_section
  ON `data_paris_I` (`Section`, `Commune`, `Type local`, `Nombre pieces principales`);

CREATE INDEX idx_est_commune
  ON `data_paris_I` (`Commune`, `Type local`, `Nombre pieces principales`);
