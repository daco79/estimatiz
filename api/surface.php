<?php
// api/surface.php — V3.0 (DVF_France / V6)
// Calcule une surface indicative (m²) à partir des ventes existantes
// Structure nouvelle : adresse_nom_voie (fusionné), nom_commune, lot1_surface_carrez

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config.php';
    if (!function_exists('get_pdo')) {
        throw new RuntimeException("Fonction get_pdo() introuvable dans config.php");
    }
    $pdo = get_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // === Paramètres ===
    $code_voie      = trim($_GET['code_voie']       ?? '');
    $commune        = trim($_GET['commune']          ?? '');
    $no_voie        = trim($_GET['no_voie']          ?? '');
    $btq            = trim($_GET['btq']              ?? '');
    $adresse_nom_voie = trim($_GET['voie']           ?? '');  // adresse_nom_voie fusionné
    $pieces         = isset($_GET['pieces']) && $_GET['pieces'] !== '' ? (int)$_GET['pieces'] : null;

    if ($code_voie === '' || $commune === '') {
        echo json_encode(['ok' => false, 'error' => 'Paramètres requis: code_voie et commune']);
        exit;
    }

    // Commune LIKE pour les fallbacks (Paris/Lyon/Marseille → toute la ville)
    if (mb_stripos($commune, 'paris') === 0)         $commune_like = 'paris%';
    elseif (mb_stripos($commune, 'lyon') === 0)      $commune_like = 'lyon%';
    elseif (mb_stripos($commune, 'marseille') === 0) $commune_like = 'marseille%';
    else                                              $commune_like = $commune;

    $table = db_table_dvf();

    // === Helpers ===
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

    $pieces_sql    = $pieces !== null ? ' AND nombre_pieces_principales = :pieces' : '';
    $pieces_params = $pieces !== null ? [':pieces' => $pieces] : [];

    $rows  = [];
    $level = null;

    // === 1) Adresse exacte ===
    // Conditions : numéro + suffixe + adresse_nom_voie + commune exacte
    if ($no_voie !== '' && $adresse_nom_voie !== '') {
        $stmt = $pdo->prepare("
            SELECT lot1_surface_carrez AS carrez, surface_reelle_bati AS reelle
            FROM $table
            WHERE nom_commune = :commune
              AND adresse_numero = :no_voie
              AND (:btq = '' OR adresse_suffixe = :btq2)
              AND UPPER(adresse_nom_voie) = UPPER(:adresse_nom_voie)
              $pieces_sql
            LIMIT 10000
        ");
        $stmt->execute(array_merge([
            ':commune'          => $commune,
            ':no_voie'          => $no_voie,
            ':btq'              => $btq,
            ':btq2'             => $btq,
            ':adresse_nom_voie' => $adresse_nom_voie,
        ], $pieces_params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $level = 'exact';
    }

    // === 2) Fallback rue — par adresse_code_voie ===
    if (empty($rows)) {
        $stmt = $pdo->prepare("
            SELECT lot1_surface_carrez AS carrez, surface_reelle_bati AS reelle
            FROM $table
            WHERE adresse_code_voie = :code_voie
              AND LOWER(nom_commune) LIKE LOWER(:commune_like)
              $pieces_sql
            LIMIT 10000
        ");
        $stmt->execute(array_merge([
            ':code_voie'    => $code_voie,
            ':commune_like' => $commune_like,
        ], $pieces_params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $level = 'street';
    }

    // === 3) Fallback rue — par adresse_nom_voie si code_voie donne rien ===
    if (empty($rows) && $adresse_nom_voie !== '') {
        $stmt = $pdo->prepare("
            SELECT lot1_surface_carrez AS carrez, surface_reelle_bati AS reelle
            FROM $table
            WHERE UPPER(adresse_nom_voie) = UPPER(:adresse_nom_voie)
              AND LOWER(nom_commune) LIKE LOWER(:commune_like)
              $pieces_sql
            LIMIT 10000
        ");
        $stmt->execute(array_merge([
            ':adresse_nom_voie' => $adresse_nom_voie,
            ':commune_like'     => $commune_like,
        ], $pieces_params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) $level = 'street_by_name';
    }

    // === Extraction des surfaces (Carrez prioritaire, sinon Réelle) ===
    $vals = [];
    foreach ($rows as $r) {
        $carrez = isset($r['carrez']) && $r['carrez'] !== null ? (float)$r['carrez'] : null;
        $reelle = isset($r['reelle']) && $r['reelle'] !== null ? (float)$r['reelle'] : null;
        $s = $carrez ?? $reelle;
        if ($s !== null && $s >= 5 && $s <= 500) $vals[] = $s;
    }

    sort($vals);
    $count = count($vals);

    if ($count === 0) {
        echo json_encode([
            'ok'         => true,
            'used_level' => $level,
            'count'      => 0,
            'surface'    => null,
            'stats'      => null,
            'samples'    => [],
            'unit'       => 'm²'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $p20 = $percentile($vals, 20);
    $p50 = $percentile($vals, 50);
    $p80 = $percentile($vals, 80);

    echo json_encode([
        'ok'         => true,
        'used_level' => $level,
        'count'      => $count,
        'surface'    => round($p50),
        'stats'      => [
            'p20'    => round($p20, 1),
            'median' => round($p50, 1),
            'p80'    => round($p80, 1),
        ],
        'samples'    => array_map(fn($v) => round($v, 1), array_slice($vals, 0, 20)),
        'unit'       => 'm²'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'error'   => 'SERVER_ERROR',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
