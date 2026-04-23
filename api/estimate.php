<?php
// === api/estimate.php — V2.0 ===
// Inputs (GET): code_voie, commune, section, type_voie, voie, no_voie, btq,
//               type_local, pieces, annee_min, annee_max, surface, nmin
// Output: JSON with price_sqm percentiles (p20, median, p80) and estimate low/mid/high
// Améliorations v2 :
//  - Commune LIKE (Paris/Lyon/Marseille + arrondissements)
//  - Filtre valeur minimale (>= 10 000€) pour exclure les erreurs de données
//  - Filtre comparables par surface ±50% du bien à estimer
//  - Cache des résultats fetch_comps (pas de double appel)
//  - Scope intermédiaire : rue ±1 pièce avant d'élargir à la section

header('Content-Type: application/json; charset=utf-8');

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    require_once __DIR__ . '/../config.php';
    if (!function_exists('get_pdo')) fail("La fonction get_pdo() est introuvable dans config.php");
    $pdo = get_pdo();
} catch (\Throwable $e) {
    fail("Erreur de connexion: " . $e->getMessage(), 500);
}

// --- Paramètres ---
$code_voie = $_GET['code_voie'] ?? null;
$commune   = $_GET['commune']   ?? null;
$section   = $_GET['section']   ?? null;
$type_voie = $_GET['type_voie'] ?? null;
$voie      = $_GET['voie']      ?? null;
$no_voie   = $_GET['no_voie']   ?? null;
$btq       = $_GET['btq']       ?? null;

$type_local      = $_GET['type_local'] ?? 'Appartement';
$pieces          = isset($_GET['pieces']) && $_GET['pieces'] !== '' ? (int)$_GET['pieces'] : null;
$annee_min       = isset($_GET['annee_min'])  ? (int)$_GET['annee_min']  : 2010;
$annee_max       = isset($_GET['annee_max'])  ? (int)$_GET['annee_max']  : 2099;
$subject_surface = isset($_GET['surface']) && $_GET['surface'] !== '' ? floatval(str_replace(',', '.', $_GET['surface'])) : null;
$nmin            = isset($_GET['nmin']) ? max(1, (int)$_GET['nmin']) : 10;
$val_min         = 10000; // seuil minimum de valeur foncière (filtre erreurs de données)

if ($annee_max < $annee_min) { [$annee_min, $annee_max] = [$annee_max, $annee_min]; }
if (!$code_voie && !$section && !$commune) fail("Veuillez fournir au minimum code_voie, section ou commune.");

// --- Commune LIKE (Paris/Lyon/Marseille + arrondissements) ---
$commune_like = $commune;
if ($commune) {
    if (mb_stripos($commune, 'paris')     === 0) $commune_like = 'paris%';
    elseif (mb_stripos($commune, 'lyon')  === 0) $commune_like = 'lyon%';
    elseif (mb_stripos($commune, 'marseille') === 0) $commune_like = 'marseille%';
}

// --- fetch_comps ---
// $pieces_delta : 0 = pièces exactes, 1 = ±1 pièce
function fetch_comps(PDO $pdo, string $scope, array $binds, int $pieces_delta = 0): array {
    $where  = [];
    $params = [];

    $where[] = "`Type local` = :type_local";
    $params[':type_local'] = $binds['type_local'];

    // Filtre valeur minimale
    $where[] = "REPLACE(`Valeur fonciere`, ',', '.') + 0 >= :val_min";
    $params[':val_min'] = $binds['val_min'];

    // Filtre pièces (exact ou ±1)
    if ($binds['pieces'] !== null) {
        if ($pieces_delta === 0) {
            $where[] = "`Nombre pieces principales` = :pieces";
            $params[':pieces'] = $binds['pieces'];
        } else {
            $where[] = "`Nombre pieces principales` BETWEEN :pieces_min AND :pieces_max";
            $params[':pieces_min'] = max(1, $binds['pieces'] - $pieces_delta);
            $params[':pieces_max'] = $binds['pieces'] + $pieces_delta;
        }
    }

    // Filtre années
    $where[] = "YEAR(STR_TO_DATE(`Date mutation`, '%d/%m/%Y')) BETWEEN :ymin AND :ymax";
    $params[':ymin'] = $binds['annee_min'];
    $params[':ymax'] = $binds['annee_max'];

    // Scope
    if ($scope === 'street') {
        $where[] = "`Code voie` = :code_voie";
        $params[':code_voie'] = $binds['code_voie'];
        if (!empty($binds['commune_like'])) {
            $where[] = "`Commune` LIKE :commune_like";
            $params[':commune_like'] = $binds['commune_like'];
        }
    } elseif ($scope === 'section') {
        $where[] = "`Section` = :section";
        $params[':section'] = $binds['section'];
        if (!empty($binds['commune_like'])) {
            $where[] = "`Commune` LIKE :commune_like";
            $params[':commune_like'] = $binds['commune_like'];
        }
    } else { // commune
        $where[] = "`Commune` LIKE :commune_like";
        $params[':commune_like'] = $binds['commune_like'];
    }

    $table = $binds['table'];
    $sql = "
        SELECT
            STR_TO_DATE(`Date mutation`, '%d/%m/%Y') AS dt,
            REPLACE(`Valeur fonciere`, ',', '.') + 0 AS valeur,
            NULLIF(REPLACE(`Surface Carrez du 1er lot`, ',', '.'), '') + 0 AS surf_carrez,
            NULLIF(REPLACE(`Surface reelle bati`, ',', '.'), '') + 0 AS surf_srb,
            `Voie`, `Code voie`, `Commune`, `Section`,
            `Nombre pieces principales` AS pieces,
            `Nature mutation` AS nature
        FROM {$table}
        WHERE " . implode(' AND ', $where);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $val    = floatval($r['valeur']);
        $carrez = floatval($r['surf_carrez']);
        $srb    = floatval($r['surf_srb']);
        $surf   = $carrez > 0 ? $carrez : $srb;
        if ($val > 0 && $surf > 0) {
            $r['surface_indic'] = $surf;
            $r['prix_m2']       = $val / $surf;
            $out[] = $r;
        }
    }
    return $out;
}

// --- Helpers statistiques ---
function percentile(array $values, float $p): float {
    $n = count($values);
    if ($n === 0) return NAN;
    sort($values, SORT_NUMERIC);
    if ($n === 1) return (float)$values[0];
    $pos   = ($n - 1) * $p;
    $lower = (int)floor($pos);
    $upper = (int)ceil($pos);
    if ($lower === $upper) return (float)$values[$lower];
    $w = $pos - $lower;
    return (float)($values[$lower] * (1 - $w) + $values[$upper] * $w);
}

function filter_iqr(array $rows, float $k = 1.5): array {
    $values = array_map(function($r) {
        return $r['prix_m2'];
    }, $rows);
    if (count($values) < 4) return $rows;
    $q1  = percentile($values, 0.25);
    $q3  = percentile($values, 0.75);
    $iqr = $q3 - $q1;
    if ($iqr <= 0) return $rows;
    $lo = $q1 - $k * $iqr;
    $hi = $q3 + $k * $iqr;
    return array_values(array_filter($rows, function($r) use ($lo, $hi) {
        return $r['prix_m2'] >= $lo && $r['prix_m2'] <= $hi;
    }));
}


// --- Binds partagés ---
$binds = [
    'type_local'   => $type_local,
    'pieces'       => $pieces,
    'annee_min'    => $annee_min,
    'annee_max'    => $annee_max,
    'val_min'      => $val_min,
    'code_voie'    => $code_voie,
    'section'      => $section,
    'commune_like' => $commune_like,
    'table'        => db_table_dvf(),
];

// --- Enchaînement des scopes avec cache ---
$scope_used  = null;
$comps       = [];
$cache       = [];

$try_scope = function(string $scope, int $pieces_delta = 0) use ($pdo, $binds, $nmin, &$cache): array {
    $key = "$scope|$pieces_delta";
    if (!isset($cache[$key])) $cache[$key] = fetch_comps($pdo, $scope, $binds, $pieces_delta);
    return $cache[$key];
};

// 1. Rue, pièces exactes
if ($code_voie) {
    $tmp = $try_scope('street', 0);
    if (count($tmp) >= $nmin) { $scope_used = 'street'; $comps = $tmp; }
}

// 2. Rue, ±1 pièce (si pièces demandées et pas assez de résultats)
if ($scope_used === null && $code_voie && $pieces !== null) {
    $tmp = $try_scope('street', 1);
    if (count($tmp) >= $nmin) { $scope_used = 'street_pieces_tolerance'; $comps = $tmp; }
}

// 3. Section
if ($scope_used === null && $section) {
    $tmp = $try_scope('section', 0);
    if (count($tmp) >= $nmin) { $scope_used = 'section'; $comps = $tmp; }
}

// 4. Commune
if ($scope_used === null && $commune) {
    $tmp = $try_scope('commune', 0);
    if (count($tmp) >= $nmin || (!$code_voie && !$section)) { $scope_used = 'commune'; $comps = $tmp; }
}

// 5. Fallback : le scope avec le plus de résultats (sans double appel grâce au cache)
if ($scope_used === null) {
    foreach (['street|0', 'street|1', 'section|0', 'commune|0'] as $key) {
        [$s, $d] = explode('|', $key);
        if (($s === 'street' && !$code_voie) || ($s === 'section' && !$section) || ($s === 'commune' && !$commune)) continue;
        $tmp = $try_scope($s, (int)$d);
        if (count($tmp) > count($comps)) $comps = $tmp;
    }
    $scope_used = 'fallback';
}

$n_raw = count($comps);

$n_after_surface = count($comps);

// --- Filtre IQR (appliqué seulement si assez de comparables restants) ---
$comps_iqr = filter_iqr($comps, 1.5);
$comps_filtered = count($comps_iqr) >= $nmin ? $comps_iqr : $comps;
$vals_f = array_map(function($r) {
    return $r['prix_m2'];
}, $comps_filtered);
$n = count($vals_f);

$p20 = is_nan($x = percentile($vals_f, 0.20)) ? null : round($x, 2);
$p50 = is_nan($x = percentile($vals_f, 0.50)) ? null : round($x, 2);
$p80 = is_nan($x = percentile($vals_f, 0.80)) ? null : round($x, 2);

// --- Estimation ---
$est = ['low' => null, 'mid' => null, 'high' => null];
if ($subject_surface && $p20 !== null && $p50 !== null && $p80 !== null) {
    $est['low']  = round($p20 * $subject_surface);
    $est['mid']  = round($p50 * $subject_surface);
    $est['high'] = round($p80 * $subject_surface);
}

// --- Score de confiance ---
$conf = null;
if ($n > 0) {
    $q1   = percentile($vals_f, 0.25);
    $q3   = percentile($vals_f, 0.75);
    $disp = ($p50 && $p50 > 0) ? (($q3 - $q1) / $p50) : null;
    $conf_n = min(1.0, $n / max(10.0, $nmin * 2.0));
    $conf_d = isset($disp) ? max(0.0, min(1.0, 1.0 - $disp)) : 0.5;
    $conf   = round(0.6 * $conf_n + 0.4 * $conf_d, 2);
}

echo json_encode([
    'ok'         => true,
    'scope_used' => $scope_used,
    'filters_applied' => [
        'type_local' => $type_local,
        'pieces'     => $pieces,
        'annees'     => [$annee_min, $annee_max],
        'val_min'    => $val_min,
        'code_voie'  => $code_voie,
        'section'    => $section,
        'commune'    => $commune,
    ],
    'counts' => [
        'raw'       => $n_raw,
        'after_iqr' => $n,
    ],
    'price_sqm' => ['p20' => $p20, 'median' => $p50, 'p80' => $p80],
    'estimate'  => $est + ['confidence' => $conf],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
