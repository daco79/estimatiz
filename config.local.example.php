<?php
/**
 * Exemple de configuration production.
 *
 * Copie ce fichier en config.local.php sur o2switch, puis remplace les valeurs.
 * Ne versionne pas config.local.php si tu y mets de vrais identifiants.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_DVF_France');   // nom préfixé cPanel (ex: zece2169_DVF_France)
define('DB_USER', 'cpaneluser_utilisateur');   // ex: zece2169_estimatiz
define('DB_PASSWORD', 'mot_de_passe_mysql');
define('DB_CHARSET', 'utf8mb4');
define('DB_TABLE_DVF', 'dvf_france');
