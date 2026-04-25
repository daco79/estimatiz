<?php
// api/prix-m2.php — V4.0 France (DVF_France / V6)
// Défaut : Paris, comportement historique par arrondissements.
// Modes :
//   ?mode=arrondissements&ville=paris|lyon|marseille
//   ?mode=departements
//   ?mode=villes&dep=33
//   ?mode=rues&ville=paris&cp=75011
//   ?mode=rues&dep=33&code_commune=33063
//   ?mode=evolution&ville=paris | &dep=33 | &code_commune=33063 | &cp=75011

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/cache.php';

// ── Helpers stats ─────────────────────────────────────────────────────────────

function applyIQR(array $vals, float $factor = 1.5): array {
    $n = count($vals);
    if ($n < 4) return $vals;
    sort($vals, SORT_NUMERIC);
    $q1  = $vals[(int)floor(($n - 1) * 0.25)];
    $q3  = $vals[(int)floor(($n - 1) * 0.75)];
    $iqr = $q3 - $q1;
    if ($iqr <= 0) return $vals;
    $lo = $q1 - $factor * $iqr;
    $hi = $q3 + $factor * $iqr;
    return array_values(array_filter($vals, static fn($v) => $v >= $lo && $v <= $hi));
}

function pct(array $sorted, float $p): ?float {
    $n = count($sorted);
    if ($n === 0) return null;
    if ($n === 1) return (float)$sorted[0];
    $rank = ($p / 100) * ($n - 1);
    $lo   = (int)floor($rank);
    $hi   = (int)ceil($rank);
    if ($lo === $hi) return (float)$sorted[$lo];
    $w = $rank - $lo;
    return (float)($sorted[$lo] * (1 - $w) + $sorted[$hi] * $w);
}

function statsBlock(array $vals): array {
    $vals = applyIQR($vals);
    $n    = count($vals);
    if ($n === 0) {
        return ['count' => 0, 'p20' => null, 'median' => null, 'p80' => null, 'mean' => null];
    }
    sort($vals, SORT_NUMERIC);
    return [
        'count'  => $n,
        'p20'    => round((float)pct($vals, 20)),
        'median' => round((float)pct($vals, 50)),
        'p80'    => round((float)pct($vals, 80)),
        'mean'   => round(array_sum($vals) / $n),
    ];
}

function fail(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_json_string(string $out, int $cacheTtl, string $cacheStatus = 'miss'): void {
    header('Cache-Control: public, max-age=' . $cacheTtl);
    header('X-Estimatiz-Cache: ' . $cacheStatus);
    echo $out;
    exit;
}

function normalizeDep(string $dep): string {
    $dep = strtoupper(trim($dep));
    if ($dep === '') return '';
    if (preg_match('~^\d$~', $dep)) return '0' . $dep;
    return $dep;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    try {
        $sql = 'SHOW COLUMNS FROM ' . $table . ' LIKE ' . $pdo->quote($column);
        $st = $pdo->query($sql);
        return $st !== false && $st->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function departmentExpr(bool $hasCodeDepartement, bool $hasCodeCommune): string {
    if ($hasCodeDepartement) {
        return "CASE
            WHEN UPPER(code_departement) IN ('2A','2B') THEN UPPER(code_departement)
            WHEN CHAR_LENGTH(code_departement) = 1 THEN LPAD(code_departement, 2, '0')
            ELSE code_departement
        END";
    }

    if ($hasCodeCommune) {
        return "CASE
            WHEN UPPER(code_commune) LIKE '2A%' THEN '2A'
            WHEN UPPER(code_commune) LIKE '2B%' THEN '2B'
            WHEN code_commune REGEXP '^(97|98)' THEN LEFT(code_commune, 3)
            ELSE LEFT(code_commune, 2)
        END";
    }

    return "CASE
        WHEN code_postal REGEXP '^(97|98)' THEN LEFT(code_postal, 3)
        ELSE LEFT(code_postal, 2)
    END";
}

function addCommonFilters(array &$where, array &$params, string $type_local, int $annee_min, int $annee_max, ?int $pieces): void {
    if ($type_local !== '') {
        $where[] = 'type_local = :type_local';
        $params[':type_local'] = $type_local;
    }

    if ($pieces !== null) {
        $where[] = 'nombre_pieces_principales = :pieces';
        $params[':pieces'] = $pieces;
    }

    $where[] = 'YEAR(date_mutation) BETWEEN :annee_min AND :annee_max';
    $params[':annee_min'] = $annee_min;
    $params[':annee_max'] = $annee_max;

    $where[] = '(lot1_surface_carrez IS NOT NULL OR surface_reelle_bati IS NOT NULL)';
    $where[] = 'valeur_fonciere IS NOT NULL';
}

function priceSqmFromRow(array $r): ?float {
    $valeur = $r['valeur_fonciere'] !== null ? (float)$r['valeur_fonciere'] : null;
    $surf   = $r['carrez'] !== null ? (float)$r['carrez']
            : ($r['reelle'] !== null ? (float)$r['reelle'] : null);

    if ($valeur === null || $surf === null || $surf < 5 || $valeur < 5000) return null;

    $prixM2 = $valeur / $surf;
    if ($prixM2 < 500 || $prixM2 > 50000) return null;

    return $prixM2;
}

function fetchRows(PDO $pdo, string $table, string $selectExtra, array $where, array $params): array {
    $sql = "SELECT valeur_fonciere,
                   lot1_surface_carrez AS carrez,
                   surface_reelle_bati AS reelle,
                   date_mutation
                   $selectExtra
            FROM {$table}
            WHERE " . implode(' AND ', $where);

    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ── Villes avec arrondissements ───────────────────────────────────────────────

$ARRONDISSEMENT_CITIES = [
    'paris'     => ['label' => 'Paris',     'cp_from' => '75001', 'cp_to' => '75020'],
    'lyon'      => ['label' => 'Lyon',      'cp_from' => '69001', 'cp_to' => '69009'],
    'marseille' => ['label' => 'Marseille', 'cp_from' => '13001', 'cp_to' => '13016'],
];

// ── Départements ──────────────────────────────────────────────────────────────

$DEPARTEMENT_LABELS = [
    '01'=>'Ain','02'=>'Aisne','03'=>'Allier','04'=>'Alpes-de-Haute-Provence',
    '05'=>'Hautes-Alpes','06'=>'Alpes-Maritimes','07'=>'Ardèche','08'=>'Ardennes',
    '09'=>'Ariège','10'=>'Aube','11'=>'Aude','12'=>'Aveyron','13'=>'Bouches-du-Rhône',
    '14'=>'Calvados','15'=>'Cantal','16'=>'Charente','17'=>'Charente-Maritime',
    '18'=>'Cher','19'=>'Corrèze','2A'=>'Corse-du-Sud','2B'=>'Haute-Corse',
    '21'=>'Côte-d’Or','22'=>'Côtes-d’Armor','23'=>'Creuse','24'=>'Dordogne',
    '25'=>'Doubs','26'=>'Drôme','27'=>'Eure','28'=>'Eure-et-Loir','29'=>'Finistère',
    '30'=>'Gard','31'=>'Haute-Garonne','32'=>'Gers','33'=>'Gironde','34'=>'Hérault',
    '35'=>'Ille-et-Vilaine','36'=>'Indre','37'=>'Indre-et-Loire','38'=>'Isère',
    '39'=>'Jura','40'=>'Landes','41'=>'Loir-et-Cher','42'=>'Loire','43'=>'Haute-Loire',
    '44'=>'Loire-Atlantique','45'=>'Loiret','46'=>'Lot','47'=>'Lot-et-Garonne',
    '48'=>'Lozère','49'=>'Maine-et-Loire','50'=>'Manche','51'=>'Marne',
    '52'=>'Haute-Marne','53'=>'Mayenne','54'=>'Meurthe-et-Moselle','55'=>'Meuse',
    '56'=>'Morbihan','57'=>'Moselle','58'=>'Nièvre','59'=>'Nord','60'=>'Oise',
    '61'=>'Orne','62'=>'Pas-de-Calais','63'=>'Puy-de-Dôme','64'=>'Pyrénées-Atlantiques',
    '65'=>'Hautes-Pyrénées','66'=>'Pyrénées-Orientales','67'=>'Bas-Rhin',
    '68'=>'Haut-Rhin','69'=>'Rhône','70'=>'Haute-Saône','71'=>'Saône-et-Loire',
    '72'=>'Sarthe','73'=>'Savoie','74'=>'Haute-Savoie','75'=>'Paris',
    '76'=>'Seine-Maritime','77'=>'Seine-et-Marne','78'=>'Yvelines','79'=>'Deux-Sèvres',
    '80'=>'Somme','81'=>'Tarn','82'=>'Tarn-et-Garonne','83'=>'Var','84'=>'Vaucluse',
    '85'=>'Vendée','86'=>'Vienne','87'=>'Haute-Vienne','88'=>'Vosges','89'=>'Yonne',
    '90'=>'Territoire de Belfort','91'=>'Essonne','92'=>'Hauts-de-Seine',
    '93'=>'Seine-Saint-Denis','94'=>'Val-de-Marne','95'=>'Val-d’Oise',
    '971'=>'Guadeloupe','972'=>'Martinique','973'=>'Guyane','974'=>'La Réunion',
    '976'=>'Mayotte'
];

// ── Paramètres ────────────────────────────────────────────────────────────────

$mode = strtolower(trim($_GET['mode'] ?? 'arrondissements'));
if ($mode === 'communes') $mode = 'villes';

$ville_key = strtolower(trim($_GET['ville'] ?? 'paris'));
if (!isset($ARRONDISSEMENT_CITIES[$ville_key])) {
    $ville_key = 'paris';
}
$ville_cfg = $ARRONDISSEMENT_CITIES[$ville_key];

$cp_filter      = trim($_GET['cp'] ?? '');
$dep_filter     = normalizeDep($_GET['dep'] ?? '');
$code_commune   = trim($_GET['code_commune'] ?? '');
$commune_filter = trim($_GET['commune'] ?? '');

$type_local = isset($_GET['type_local']) && $_GET['type_local'] !== ''
    ? trim($_GET['type_local'])
    : '';

$annee_min = isset($_GET['annee_min']) ? (int)$_GET['annee_min'] : 2015;
$annee_max = isset($_GET['annee_max']) ? (int)$_GET['annee_max'] : 2025;

$pieces = isset($_GET['pieces']) && $_GET['pieces'] !== ''
    ? (int)$_GET['pieces']
    : null;

if ($annee_max < $annee_min) {
    [$annee_min, $annee_max] = [$annee_max, $annee_min];
}

// ── Cache ────────────────────────────────────────────────────────────────────

$cacheTtl = 21600;

$cacheKey = 'pm2v4_' . md5(json_encode([
    'mode'         => $mode,
    'ville'        => $ville_key,
    'cp'           => $cp_filter,
    'dep'          => $dep_filter,
    'code_commune' => $code_commune,
    'commune'      => $commune_filter,
    'type_local'   => $type_local,
    'annee_min'    => $annee_min,
    'annee_max'    => $annee_max,
    'pieces'       => $pieces,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$useCache = function_exists('apcu_enabled') && apcu_enabled();

if ($useCache) {
    $cached = apcu_fetch($cacheKey, $found);
    if ($found) {
        send_json_string($cached, $cacheTtl, 'apcu');
    }
}

$cachedFile = cache_get('prix_m2', $cacheKey, $cacheTtl);
if ($cachedFile !== null) {
    send_json_string($cachedFile, $cacheTtl, 'file');
}

// ── PDO / schéma ──────────────────────────────────────────────────────────────

try {
    require_once __DIR__ . '/../config.php';
    $pdo      = get_pdo();
    $tableDvf = db_table_dvf();
} catch (Throwable $e) {
    fail($e->getMessage(), 500);
}

$hasCodeDepartement = columnExists($pdo, $tableDvf, 'code_departement');
$hasCodeCommune     = columnExists($pdo, $tableDvf, 'code_commune');

$depExpr        = departmentExpr($hasCodeDepartement, $hasCodeCommune);
$communeKeyExpr = $hasCodeCommune ? 'code_commune' : "CONCAT(code_postal, '|', nom_commune)";

// ── Mode départements ────────────────────────────────────────────────────────

if ($mode === 'departements') {
    $where  = [];
    $params = [];

    addCommonFilters($where, $params, $type_local, $annee_min, $annee_max, $pieces);

    $sql = "SELECT {$depExpr} AS dep, COUNT(*) AS count_rows
            FROM {$tableDvf}
            WHERE " . implode(' AND ', $where) . "
            GROUP BY dep
            HAVING dep IS NOT NULL AND dep <> ''
            ORDER BY dep ASC";

    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        fail($e->getMessage(), 500);
    }

    $departements = [];

    foreach ($rows as $r) {
        $dep = normalizeDep((string)$r['dep']);
        $departements[] = [
            'dep'   => $dep,
            'label' => $DEPARTEMENT_LABELS[$dep] ?? ('Département ' . $dep),
            'count' => (int)$r['count_rows'],
        ];
    }

    $out = json_encode([
        'ok'           => true,
        'mode'         => 'departements',
        'filters'      => compact('type_local', 'annee_min', 'annee_max', 'pieces'),
        'departements' => $departements,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($useCache) apcu_store($cacheKey, $out, $cacheTtl);
    cache_set('prix_m2', $cacheKey, $out);
    send_json_string($out, $cacheTtl);
}

// ── WHERE selon mode ─────────────────────────────────────────────────────────

$where  = [];
$params = [];

addCommonFilters($where, $params, $type_local, $annee_min, $annee_max, $pieces);

$selectExtra = '';

if ($mode === 'arrondissements') {
    $where[] = 'code_postal BETWEEN :cp_from AND :cp_to';
    $params[':cp_from'] = $ville_cfg['cp_from'];
    $params[':cp_to']   = $ville_cfg['cp_to'];

    $selectExtra = ', code_postal, nom_commune';

} elseif ($mode === 'villes') {
    if ($dep_filter === '') {
        fail('Paramètre requis: dep');
    }

    $where[] = "{$depExpr} = :dep";
    $params[':dep'] = $dep_filter;

    $selectExtra = ", {$communeKeyExpr} AS commune_key, code_postal, nom_commune"
                 . ($hasCodeCommune ? ', code_commune' : '');

} elseif ($mode === 'rues') {
    $selectExtra = ', adresse_nom_voie, code_postal, nom_commune'
                 . ($hasCodeCommune ? ', code_commune' : '');

    // Fonctionnement historique : Paris / Lyon / Marseille via CP d’arrondissement.
    if ($cp_filter !== '') {
        $where[] = 'code_postal = :cp';
        $params[':cp'] = $cp_filter;

    // Fonctionnement France : ville sélectionnée via code_commune.
    } elseif ($code_commune !== '' && $hasCodeCommune) {
        $where[] = 'code_commune = :code_commune';
        $params[':code_commune'] = $code_commune;

    // Fallback si pas de code_commune.
    } elseif ($dep_filter !== '' && $commune_filter !== '') {
        $where[] = "{$depExpr} = :dep";
        $where[] = 'nom_commune = :commune';
        $params[':dep'] = $dep_filter;
        $params[':commune'] = $commune_filter;

    } else {
        fail('Paramètre requis: cp, ou code_commune, ou dep + commune');
    }

} elseif ($mode === 'evolution') {
    // date_mutation est déjà sélectionné dans fetchRows().
    if ($cp_filter !== '') {
        $where[] = 'code_postal = :cp';
        $params[':cp'] = $cp_filter;

    } elseif ($code_commune !== '' && $hasCodeCommune) {
        $where[] = 'code_commune = :code_commune';
        $params[':code_commune'] = $code_commune;

    } elseif ($dep_filter !== '') {
        $where[] = "{$depExpr} = :dep";
        $params[':dep'] = $dep_filter;

    } else {
        $where[] = 'code_postal BETWEEN :cp_from AND :cp_to';
        $params[':cp_from'] = $ville_cfg['cp_from'];
        $params[':cp_to']   = $ville_cfg['cp_to'];
    }

} else {
    fail('Mode non géré: ' . $mode);
}

try {
    $rows = fetchRows($pdo, $tableDvf, $selectExtra, $where, $params);
} catch (Throwable $e) {
    fail($e->getMessage(), 500);
}

// ── Buckets statistiques ─────────────────────────────────────────────────────

$buckets = [];
$meta    = [];

foreach ($rows as $r) {
    $prixM2 = priceSqmFromRow($r);
    if ($prixM2 === null) continue;

    if ($mode === 'evolution') {
        $annee = (int)substr((string)$r['date_mutation'], 0, 4);
        $buckets[$annee][] = $prixM2;

    } elseif ($mode === 'rues') {
        $voie = trim((string)($r['adresse_nom_voie'] ?? ''));
        if ($voie === '') continue;

        $buckets[$voie][] = $prixM2;
        $meta[$voie] = [
            'voie'         => $voie,
            'cp'           => $r['code_postal'] ?? null,
            'commune'      => $r['nom_commune'] ?? null,
            'code_commune' => $r['code_commune'] ?? null,
        ];

    } elseif ($mode === 'villes') {
        $key = (string)($r['commune_key'] ?? ($r['nom_commune'] ?? ''));
        if ($key === '') continue;

        $buckets[$key][] = $prixM2;
        $meta[$key] = [
            'code_commune' => $r['code_commune'] ?? null,
            'commune'      => $r['nom_commune'] ?? null,
            'cp'           => $r['code_postal'] ?? null,
        ];

    } else {
        $cp = (string)($r['code_postal'] ?? '');
        if ($cp === '') continue;

        $buckets[$cp][] = $prixM2;
        $meta[$cp] = (string)($r['nom_commune'] ?? $cp);
    }
}

// ── Réponse JSON ─────────────────────────────────────────────────────────────

if ($mode === 'evolution') {
    $result = [];

    ksort($buckets, SORT_NUMERIC);

    foreach ($buckets as $annee => $vals) {
        $s = statsBlock($vals);
        if ($s['count'] < 10) continue;

        $result[] = array_merge(['annee' => (int)$annee], $s);
    }

    $out = json_encode([
        'ok'           => true,
        'mode'         => 'evolution',
        'ville'        => $ville_key,
        'dep'          => $dep_filter ?: null,
        'cp'           => $cp_filter ?: null,
        'code_commune' => $code_commune ?: null,
        'filters'      => compact('type_local', 'annee_min', 'annee_max', 'pieces'),
        'evolution'    => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} elseif ($mode === 'rues') {
    $result = [];

    foreach ($buckets as $voie => $vals) {
        $s = statsBlock($vals);
        if ($s['count'] < 3) continue;

        $result[] = array_merge($meta[$voie], $s);
    }

    usort($result, static fn($a, $b) => ($b['median'] ?? 0) <=> ($a['median'] ?? 0));

    $out = json_encode([
        'ok'           => true,
        'mode'         => 'rues',
        'ville'        => $ville_key,
        'cp'           => $cp_filter ?: null,
        'dep'          => $dep_filter ?: null,
        'code_commune' => $code_commune ?: null,
        'commune'      => $commune_filter ?: null,
        'filters'      => compact('type_local', 'annee_min', 'annee_max', 'pieces'),
        'rues'         => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} elseif ($mode === 'villes') {
    $result = [];

    foreach ($buckets as $key => $vals) {
        $s = statsBlock($vals);
        if ($s['count'] < 5) continue;

        $result[] = array_merge($meta[$key], $s);
    }

    usort($result, static function ($a, $b) {
        return strnatcasecmp((string)($a['commune'] ?? ''), (string)($b['commune'] ?? ''));
    });

    $out = json_encode([
        'ok'        => true,
        'mode'      => 'villes',
        'dep'       => $dep_filter,
        'dep_label' => $DEPARTEMENT_LABELS[$dep_filter] ?? ('Département ' . $dep_filter),
        'filters'   => compact('type_local', 'annee_min', 'annee_max', 'pieces'),
        'villes'    => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    $result = [];

    ksort($buckets, SORT_STRING);

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
        'ok'          => true,
        'mode'        => 'arrondissements',
        'ville'       => $ville_key,
        'ville_label' => $ville_cfg['label'],
        'filters'     => compact('type_local', 'annee_min', 'annee_max', 'pieces'),
        'data'        => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if ($useCache) {
    apcu_store($cacheKey, $out, $cacheTtl);
}

cache_set('prix_m2', $cacheKey, $out);
send_json_string($out, $cacheTtl);
