<?php
// api/ventes.php — Dernières ventes DVF
// Keyset pagination : cursor=DATE_ID (ex: 2025-06-15_12345)
// Filtres : type_local, dep, code_commune, cp, annee_min, annee_max, pieces, surface_min, surface_max

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizeDep(string $dep): string {
    $dep = strtoupper(trim($dep));
    if ($dep === '') return '';
    if (preg_match('~^\d$~', $dep)) return '0' . $dep;
    return $dep;
}

try {
    require_once __DIR__ . '/../config.php';
    $pdo      = get_pdo();
    $tableDvf = db_table_dvf();
} catch (Throwable $e) {
    fail($e->getMessage(), 500);
}

// ── Paramètres ────────────────────────────────────────────────────────────────

$limit       = min(40, max(1, (int)($_GET['limit'] ?? 20)));
$cursor      = trim($_GET['cursor'] ?? '');          // "2025-06-15_12345"
$type_local  = trim($_GET['type_local']  ?? '');
$dep         = normalizeDep($_GET['dep'] ?? '');
$code_commune= trim($_GET['code_commune'] ?? '');
$cp          = trim($_GET['cp'] ?? '');
$annee_min   = isset($_GET['annee_min']) && $_GET['annee_min'] !== '' ? (int)$_GET['annee_min'] : null;
$annee_max   = isset($_GET['annee_max']) && $_GET['annee_max'] !== '' ? (int)$_GET['annee_max'] : null;
$pieces      = isset($_GET['pieces'])    && $_GET['pieces']    !== '' ? (int)$_GET['pieces']    : null;
$surf_min    = isset($_GET['surface_min']) && $_GET['surface_min'] !== '' ? (float)$_GET['surface_min'] : null;
$surf_max    = isset($_GET['surface_max']) && $_GET['surface_max'] !== '' ? (float)$_GET['surface_max'] : null;

// ── WHERE ─────────────────────────────────────────────────────────────────────

$where  = ['valeur_fonciere IS NOT NULL', 'valeur_fonciere > 0'];
$params = [];

// Keyset cursor
if ($cursor !== '') {
    $parts = explode('_', $cursor, 2);
    if (count($parts) === 2 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $parts[0]) && ctype_digit($parts[1])) {
        $where[]            = '(date_mutation < :c_date OR (date_mutation = :c_date2 AND id < :c_id))';
        $params[':c_date']  = $parts[0];
        $params[':c_date2'] = $parts[0];
        $params[':c_id']    = (int)$parts[1];
    }
}

if ($type_local !== '') {
    $where[]              = 'type_local = :type_local';
    $params[':type_local'] = $type_local;
}
if ($code_commune !== '') {
    $where[]                 = 'code_commune = :code_commune';
    $params[':code_commune'] = $code_commune;
} elseif ($cp !== '') {
    $where[]      = 'code_postal = :cp';
    $params[':cp'] = $cp;
} elseif ($dep !== '') {
    $where[]       = 'LEFT(code_commune, 2) = :dep';
    $params[':dep'] = $dep;
}
if ($annee_min !== null) {
    $where[]             = 'YEAR(date_mutation) >= :annee_min';
    $params[':annee_min'] = $annee_min;
}
if ($annee_max !== null) {
    $where[]             = 'YEAR(date_mutation) <= :annee_max';
    $params[':annee_max'] = $annee_max;
}
if ($pieces !== null) {
    $where[]           = 'nombre_pieces_principales = :pieces';
    $params[':pieces'] = $pieces;
}
if ($surf_min !== null) {
    $where[]            = 'COALESCE(lot1_surface_carrez, surface_reelle_bati) >= :surf_min';
    $params[':surf_min'] = $surf_min;
}
if ($surf_max !== null) {
    $where[]            = 'COALESCE(lot1_surface_carrez, surface_reelle_bati) <= :surf_max';
    $params[':surf_max'] = $surf_max;
}

$whereSQL = implode(' AND ', $where);

// ── Requête ───────────────────────────────────────────────────────────────────

$sql = "SELECT id, date_mutation, adresse_numero, adresse_suffixe, adresse_nom_voie,
               nom_commune, code_commune, code_postal,
               valeur_fonciere,
               lot1_surface_carrez AS carrez,
               surface_reelle_bati AS reelle,
               type_local, nombre_pieces_principales
        FROM {$tableDvf}
        WHERE {$whereSQL}
        ORDER BY date_mutation DESC, id DESC
        LIMIT " . ($limit + 1);  // +1 pour détecter has_more

try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    fail($e->getMessage(), 500);
}

// ── Formatage ─────────────────────────────────────────────────────────────────

$has_more = count($rows) > $limit;
if ($has_more) array_pop($rows);

$ventes     = [];
$next_cursor = null;

foreach ($rows as $r) {
    $surf    = $r['carrez'] !== null ? (float)$r['carrez'] : ($r['reelle'] !== null ? (float)$r['reelle'] : null);
    $valeur  = $r['valeur_fonciere'] !== null ? (float)$r['valeur_fonciere'] : null;
    $prix_m2 = ($surf && $surf >= 5 && $valeur) ? round($valeur / $surf) : null;

    $adresse = trim(($r['adresse_numero'] ?? '') . ($r['adresse_suffixe'] ? $r['adresse_suffixe'] : '') . ' ' . ($r['adresse_nom_voie'] ?? ''));

    $ventes[] = [
        'id'       => (int)$r['id'],
        'date'     => $r['date_mutation'],
        'adresse'  => $adresse,
        'commune'  => $r['nom_commune'],
        'cp'       => $r['code_postal'],
        'valeur'   => $valeur,
        'surface'  => $surf ? round($surf, 1) : null,
        'surf_src' => $r['carrez'] !== null ? 'carrez' : 'reelle',
        'prix_m2'  => $prix_m2,
        'type'     => $r['type_local'],
        'pieces'   => $r['nombre_pieces_principales'] !== null ? (int)$r['nombre_pieces_principales'] : null,
    ];

    $next_cursor = $r['date_mutation'] . '_' . $r['id'];
}

echo json_encode([
    'ok'          => true,
    'ventes'      => $ventes,
    'has_more'    => $has_more,
    'next_cursor' => $has_more ? $next_cursor : null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
