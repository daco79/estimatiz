-- =============================================================================
-- add_autocomplete_indexes.sql
-- Ajoute les index manquants à la table dvf_france pour optimiser autocomplete.php
-- Exécute avec : mysql -u root DVF_France < add_autocomplete_indexes.sql
-- =============================================================================

USE DVF_France;

-- Index pour les recherches prefix sur adresse_nom_voie
-- Utilisé par : LIKE 'AV FOCH%', LIKE 'RUE DE RIVOLI%'
ALTER TABLE dvf_france
  ADD INDEX idx_adresse_nom_voie (adresse_nom_voie(100));

-- Index pour les recherches de numéro exact ou partiel
-- Utilisé par : adresse_numero = ?, adresse_numero LIKE ?
ALTER TABLE dvf_france
  ADD INDEX idx_adresse_numero (adresse_numero);

-- Index pour les recherches de suffixe (bis, ter, quater...)
-- Utilisé par : UPPER(adresse_suffixe) = ?
ALTER TABLE dvf_france
  ADD INDEX idx_adresse_suffixe (adresse_suffixe);

-- Index pour les recherches de commune par prefix
-- Utilisé par : nom_commune LIKE 'Paris 8%', LIKE 'Lyon 3%'
ALTER TABLE dvf_france
  ADD INDEX idx_nom_commune (nom_commune(100));

-- Index composite pour les recherches coupler adresse_nom_voie + commune
-- Utilisé par : WHERE adresse_nom_voie LIKE ? AND nom_commune LIKE ?
ALTER TABLE dvf_france
  ADD INDEX idx_search_adresse (adresse_nom_voie(100), nom_commune(100), code_postal);

-- Vérification : afficher les index de la table
SHOW INDEXES FROM dvf_france;
