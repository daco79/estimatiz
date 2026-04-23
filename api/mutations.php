<?php
// api/mutations.php — V3.0 (DVF_France / V6)
// Structure nouvelle : adresse_nom_voie, nom_commune, valeur_fonciere (DECIMAL),
//                      date_mutation (DATE ISO), lot1_surface_carrez (DECIMAL)

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config.php';
    if (!function_exists('get_pdo')) {
        throw new RuntimeException("Fonction get_pdo() introuvable dans config.php");
    }
    $pdo = get_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- paramètres ---
    $code_voie        = trim($_GET['code_voie']  ?? '');
    $commune          = trim($_GET['commune']    ?? '');
    $no_voie          = trim($_GET['no_voie']    ?? '');
    $adresse_nom_voie = trim($_GET['voie']       ?? '');  // adresse_nom_voie fusionné

    // nouveaux filtres
    $surface_min = isset($_GET['surface_min']) && $_GET['surface_min'] !== '' ? floatval($_GET['surface_min']) : null;
    $surface_max = isset($_GET['surface_max']) && $_GET['surface_max'] !== '' ? floatval($_GET['surface_max']) : null;
    $pieces      = isset($_GET['pieces'])      && $_GET['pieces']      !== '' ? (int)$_GET['pieces']           : null;

    if ($code_voie === '' || $commune === '') {
        echo json_encode(['ok' => false, 'error' => 'Paramètres requis: code_voie et commune']);
        exit;
    }

    // Commune LIKE (Paris/Lyon/Marseille → toute la ville pour les fallbacks)
    if (mb_stripos($commune, 'paris') === 0)         $commune_like = 'paris%';
    elseif (mb_stripos($commune, 'lyon') === 0)      $commune_like = 'lyon%';
    elseif (mb_stripos($commune, 'marseille') === 0) $commune_like = 'marseille%';
    else                                              $commune_like = $commune;

    $table = db_table_dvf();

    // --- filtre pièces ---
    $pieces_sql    = $pieces !== null ? ' AND nombre_pieces_principales = :pieces' : '';
    $pieces_params = $pieces !== null ? [':pieces' => $pieces] : [];

    // --- helpers ---
    $fmt2 = function (?float $n) { return ($n === null) ? null : number_format($n, 2, '.', ''); };

    $rows  = [];
    $level = null;

    // 1) Adresse exacte : numéro + adresse_nom_voie + commune exacte
    if ($no_voie !== '' && $adresse_nom_voie !== '') {
        $params = [
            ':commune'          => $commune,
            ':no_voie'          => $no_voie,
            ':adresse_nom_voie' => $adresse_nom_voie,
        ];
        $sqlExact = "
            SELECT
              adresse_numero      AS no_voie,
              adresse_nom_voie,
              nom_commune         AS commune,
              code_postal,
              valeur_fonciere,
              date_mutation,
              lot1_surface_carrez AS carrez,
              surface_reelle_bati AS reelle,
              nombre_pieces_principales AS nb_pieces,
              lot1_numero AS lot1, lot2_numero AS lot2, lot3_numero AS lot3,
              lot4_numero AS lot4, lot5_numero AS lot5
            FROM $table
            WHERE nom_commune = :commune
              AND adresse_numero = :no_voie
              AND UPPER(adresse_nom_voie) = UPPER(:adresse_nom_voie)
            $pieces_sql
            ORDER BY date_mutation DESC
            LIMIT 10000
        ";
        $stmt = $pdo->prepare($sqlExact);
        $stmt->execute(array_merge($params, $pieces_params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $level = 'exact';
    }

    // 2) Fallback rue — par adresse_code_voie
    if (empty($rows)) {
        $params = [
            ':commune_like' => $commune_like,
            ':code_voie'    => $code_voie,
        ];
        $sqlByCode = "
            SELECT
              adresse_numero      AS no_voie,
              adresse_nom_voie,
              nom_commune         AS commune,
              code_postal,
              valeur_fonciere,
              date_mutation,
              lot1_surface_carrez AS carrez,
              surface_reelle_bati AS reelle,
              nombre_pieces_principales AS nb_pieces,
              lot1_numero AS lot1, lot2_numero AS lot2, lot3_numero AS lot3,
              lot4_numero AS lot4, lot5_numero AS lot5
            FROM $table
            WHERE LOWER(nom_commune) LIKE LOWER(:commune_like)
              AND adresse_code_voie = :code_voie
            $pieces_sql
            ORDER BY date_mutation DESC
            LIMIT 10000
        ";
        $stmt = $pdo->prepare($sqlByCode);
        $stmt->execute(array_merge($params, $pieces_params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $level = 'street_by_code';
    }

    // 3) Fallback — par adresse_nom_voie exact
    if (empty($rows) && $adresse_nom_voie !== '') {
        $params = [
            ':commune_like'     => $commune_like,
            ':adresse_nom_voie' => $adresse_nom_voie,
        ];
        $sqlByName = "
            SELECT
              adresse_numero      AS no_voie,
              adresse_nom_voie,
              nom_commune         AS commune,
              code_postal,
              valeur_fonciere,
              date_mutation,
              lot1_surface_carrez AS carrez,
              surface_reelle_bati AS reelle,
              nombre_pieces_principales AS nb_pieces,
              lot1_numero AS lot1, lot2_numero AS lot2, lot3_numero AS lot3,
              lot4_numero AS lot4, lot5_numero AS lot5
            FROM $table
            WHERE LOWER(nom_commune) LIKE LOWER(:commune_like)
              AND UPPER(adresse_nom_voie) = UPPER(:adresse_nom_voie)
            $pieces_sql
            ORDER BY date_mutation DESC
            LIMIT 10000
        ";
        $stmt = $pdo->prepare($sqlByName);
        $stmt->execute(array_merge($params, $pieces_params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $level = 'street_by_name';
    }

    // 4) Transformation + filtre surface_min/max
    $out = [];
    foreach ($rows as $r) {
        $val_raw = $r['valeur_fonciere'] !== null ? (float)$r['valeur_fonciere'] : null;
        $carrez  = $r['carrez'] !== null ? (float)$r['carrez'] : null;
        $reelle  = $r['reelle'] !== null ? (float)$r['reelle'] : null;

        $surf_num = $carrez ?? $reelle;
        if ($surf_num !== null) $surf_num = round($surf_num, 2);

        if ($surface_min !== null && $surf_num !== null && $surf_num < $surface_min) continue;
        if ($surface_max !== null && $surf_num !== null && $surf_num > $surface_max) continue;

        $surf_str = $fmt2($surf_num);
        $prix_m2  = ($val_raw && $surf_num) ? round($val_raw / $surf_num) : null;

        $adresse = trim(($r['no_voie'] ?? '') . ' ' . ($r['adresse_nom_voie'] ?? '') . ' ' . ($r['commune'] ?? ''));

        $lotsRaw = [$r['lot1'] ?? null, $r['lot2'] ?? null, $r['lot3'] ?? null, $r['lot4'] ?? null, $r['lot5'] ?? null];
        $lots = array_values(array_unique(array_filter(array_map(function ($x) {
            $x = trim((string)$x);
            return $x === '' ? null : $x;
        }, $lotsRaw))));

        $out[] = [
            'adresse'         => $adresse,
            'code_postal'     => $r['code_postal'],
            'valeur_fonciere' => $val_raw,
            'surface'         => $surf_str,
            'prix_m2'         => $prix_m2,
            'nb_pieces'       => ($r['nb_pieces'] !== null && $r['nb_pieces'] !== '') ? (int)$r['nb_pieces'] : null,
            'date_mutation'   => $r['date_mutation'],
            'lots_array'      => $lots,
        ];
    }

    // 5) Stats globales prix/m²
    $pm2 = [];
    foreach ($out as $row) {
        if ($row['prix_m2'] !== null) $pm2[] = (float)$row['prix_m2'];
    }
    sort($pm2);

    $percentile = function (array $sorted, float $p) {
        $n = count($sorted);
        if ($n === 0) return null;
        if ($n === 1) return $sorted[0];
        $rank = ($p / 100) * ($n - 1);
        $lo   = (int)floor($rank);
        $hi   = (int)ceil($rank);
        if ($lo === $hi) return $sorted[$lo];
        $w = $rank - $lo;
        return $sorted[$lo] * (1 - $w) + $sorted[$hi] * $w;
    };

    $stats = null;
    if (count($pm2) > 0) {
        $mean   = array_sum($pm2) / count($pm2);
        $median = $percentile($pm2, 50);
        $p20    = $percentile($pm2, 20);
        $p80    = $percentile($pm2, 80);

        $stats = [
            'count'    => count($pm2),
            'mean'     => (int)round($mean),
            'median'   => (int)round($median),
            'p20'      => (int)round($p20),
            'p80'      => (int)round($p80),
            'min'      => (int)round($pm2[0]),
            'max'      => (int)round($pm2[count($pm2) - 1]),
            'date_min' => $out[count($out) - 1]['date_mutation'] ?? null,
            'date_max' => $out[0]['date_mutation'] ?? null,
        ];
    }

    echo json_encode([
        'ok'         => true,
        'used_level' => $level,
        'count'      => count($out),
        'rows'       => $out,
        'stats'      => $stats
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'error'   => 'SERVER_ERROR',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
