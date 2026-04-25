<?php
// api/prix-m2.php — V3.0 (DVF_France / V6)
// Prix au m² par arrondissement ou par rue
// Mode 1 (défaut) : ?type_local=Appartement&annee_min=2020&annee_max=2025
// Mode 2 (drill)  : ?mode=rues&cp=75011&type_local=Appartement&annee_min=2020&annee_max=2025
// Mode 3 (evol)   : ?mode=evolution&type_local=Appartement&annee_min=2020&annee_max=2025
// Structure V6 : colonnes snake_case, valeur_fonciere DECIMAL, date_mutation DATE ISO

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/cache.php';

// ── Helpers ───────────────────────────────────────────────────────────────────

function applyIQR(array $vals, float $factor = 1.5): array {
    $n = count($vals);
    if ($n < 4) return $vals;
    sort($vals);
    $q1 = $vals[(int)floor($n * 0.25)];
    $q3 = $vals[(int)floor($n * 0.75)];
    $iqr = $q3 - $q1;
    if ($iqr <= 0) return $vals;
    $lo = $q1 - $factor * $iqr;
    $hi = $q3 + $factor * $iqr;
    return array_values(array_filter($vals, function ($v) use ($lo, $hi) {
        return $v >= $lo && $v <= $hi;
    }));
}

function pct(array $sorted, float $p): ?float {
    $n = count($sorted);
    if ($n === 0) return null;
    if ($n === 1) return $sorted[0];
    $rank = ($p / 100) * ($n - 1);
    $lo   = (int)floor($rank);
    $hi   = (int)ceil($rank);
    if ($lo === $hi) return $sorted[$lo];
    return $sorted[$lo] * (1 - ($rank - $lo)) + $sorted[$hi] * ($rank - $lo);
}

function statsBlock(array $vals): array {
    $vals = applyIQR($vals);
    $n    = count($vals);
    if ($n === 0) return ['count' => 0, 'p20' => null, 'median' => null, 'p80' => null, 'mean' => null];
    sort($vals);
    return [
        'count'  => $n,
        'p20'    => round((float)pct($vals, 20)),
        'median' => round((float)pct($vals, 50)),
        'p80'    => round((float)pct($vals, 80)),
        'mean'   => round(array_sum($vals) / $n),
    ];
}

// ── Villes disponibles ────────────────────────────────────────────────────────
$VILLES = [
    'paris'      => ['label' => 'Paris',      'cp_from' => '75001', 'cp_to' => '75020'],
    // 'lyon'    => ['label' => 'Lyon',       'cp_from' => '69001', 'cp_to' => '69009'],
    // 'marseille'=> ['label'=>'Marseille',   'cp_from' => '13001', 'cp_to' => '13016'],
];

// ── Paramètres ────────────────────────────────────────────────────────────────
$mode      = $_GET['mode']   ?? 'arrondissements';
$ville_key = isset($_GET['ville']) && isset($VILLES[$_GET['ville']]) ? $_GET['ville'] : 'paris';
$ville_cfg = $VILLES[$ville_key];
$cp_filter = trim($_GET['cp'] ?? '');
$type_local = isset($_GET['type_local']) && $_GET['type_local'] !== '' ? trim($_GET['type_local']) : '';
$annee_min  = isset($_GET['annee_min']) ? (int)$_GET['annee_min'] : 2015;
$annee_max  = isset($_GET['annee_max']) ? (int)$_GET['annee_max'] : 2025;
$pieces     = isset($_GET['pieces']) && $_GET['pieces'] !== '' ? (int)$_GET['pieces'] : null;

// ── Cache ─────────────────────────────────────────────────────────────────────
$cacheKey = 'pm2v6_' . md5(json_encode(compact('mode','ville_key','cp_filter','type_local','annee_min','annee_max','pieces')));
$cacheTtl = 21600; // 6 heures
$useCache = function_exists('apcu_enabled') && apcu_enabled();
if ($useCache) {
    $cached = apcu_fetch($cacheKey, $found);
    if ($found) cache_send_json($cached, $cacheTtl, 'apcu');
}
$cachedFile = cache_get('prix_m2', $cacheKey, $cacheTtl);
if ($cachedFile !== null) cache_send_json($cachedFile, $cacheTtl, 'file');

// ── PDO ───────────────────────────────────────────────────────────────────────
try {
    require_once __DIR__ . '/../config.php';
    $pdo      = get_pdo();
    $tableDvf = db_table_dvf();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

// ── Requête SQL ───────────────────────────────────────────────────────────────
// code_postal BETWEEN fonctionne sur VARCHAR numérique
$where  = ["code_postal BETWEEN '{$ville_cfg['cp_from']}' AND '{$ville_cfg['cp_to']}'"];
$params = [];

if ($type_local !== '') {
    $where[]             = 'type_local = :type_local';
    $params[':type_local'] = $type_local;
}
if ($mode === 'rues' && $cp_filter !== '') {
    $where[]       = 'code_postal = :cp';
    $params[':cp'] = $cp_filter;
}
if ($pieces !== null) {
    $where[]           = 'nombre_pieces_principales = :pieces';
    $params[':pieces'] = $pieces;
}

$whereSQL = implode(' AND ', $where);

// Filtrage par année via YEAR() sur DATE : plus besoin de STR_TO_DATE ou substr
$whereSQL .= ' AND YEAR(date_mutation) BETWEEN ' . $annee_min . ' AND ' . $annee_max;

$extraCols = $mode === 'rues' ? ', adresse_nom_voie' : '';

$sql = "SELECT code_postal, nom_commune,
               valeur_fonciere,
               lot1_surface_carrez AS carrez,
               surface_reelle_bati AS reelle,
               date_mutation
               $extraCols
        FROM {$tableDvf}
        WHERE $whereSQL
          AND (lot1_surface_carrez IS NOT NULL OR surface_reelle_bati IS NOT NULL)
          AND valeur_fonciere IS NOT NULL";

try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

// ── Traitement ────────────────────────────────────────────────────────────────
$buckets = [];
$meta    = [];

foreach ($rows as $r) {
    $valeur = $r['valeur_fonciere'] !== null ? (float)$r['valeur_fonciere'] : null;
    $surf   = $r['carrez'] !== null ? (float)$r['carrez']
            : ($r['reelle'] !== null ? (float)$r['reelle'] : null);

    if ($valeur === null || $surf === null || $surf < 5 || $valeur < 5000) continue;

    $prixM2 = $valeur / $surf;
    if ($prixM2 < 500 || $prixM2 > 50000) continue;

    // date_mutation est maintenant un objet DATE MySQL → string "YYYY-MM-DD"
    $annee = (int)substr((string)$r['date_mutation'], 0, 4);
    if ($annee < $annee_min || $annee > $annee_max) continue;

    if ($mode === 'evolution') {
        $buckets[$annee][] = $prixM2;
    } elseif ($mode === 'rues') {
        $key = (string)($r['adresse_nom_voie'] ?? '');
        $meta[$key] = $key;
        $buckets[$key][] = $prixM2;
    } else {
        $key  = (string)$r['code_postal'];
        $meta[$key] = (string)$r['nom_commune'];
        $buckets[$key][] = $prixM2;
    }
}

// ── Construction de la réponse ────────────────────────────────────────────────
$result = [];

if ($mode === 'evolution') {
    ksort($buckets);
    foreach ($buckets as $annee => $vals) {
        $s = statsBlock($vals);
        if ($s['count'] < 10) continue;
        $result[] = array_merge(['annee' => (int)$annee], $s);
    }
    $out = json_encode([
        'ok'        => true,
        'ville'     => $ville_key,
        'filters'   => compact('type_local','annee_min','annee_max','pieces'),
        'evolution' => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} elseif ($mode === 'rues') {
    foreach ($buckets as $voie => $vals) {
        $s = statsBlock($vals);
        if ($s['count'] < 3) continue;
        $result[] = array_merge(['voie' => $voie], $s);
    }
    usort($result, function ($a, $b) { return $b['median'] <=> $a['median']; });
    $out = json_encode([
        'ok'      => true,
        'cp'      => $cp_filter,
        'ville'   => $ville_key,
        'filters' => compact('type_local','annee_min','annee_max'),
        'rues'    => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    // Arrondissements 75001 → 75020
    ksort($buckets);
    foreach ($buckets as $cp => $vals) {
        $s = statsBlock($vals);
        if ($s['count'] < 5) continue;
        $arr = (int)substr((string)$cp, -2);
        $result[] = array_merge([
            'cp'      => $cp,
            'arr'     => $arr,
            'commune' => $meta[$cp] ?? $cp,
        ], $s);
    }
    $out = json_encode([
        'ok'      => true,
        'filters' => compact('type_local','annee_min','annee_max','pieces'),
        'data'    => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if ($useCache) apcu_store($cacheKey, $out, $cacheTtl);
cache_set('prix_m2', $cacheKey, $out);
header('Cache-Control: public, max-age=' . $cacheTtl);
header('X-Estimatiz-Cache: miss');
echo $out;
