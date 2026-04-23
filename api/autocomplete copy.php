<?php
/**
 * api/autocomplete.php — V3.0 (DVF_France / V6)
 * Adapté pour la nouvelle structure :
 *  - adresse_nom_voie  : type + nom fusionnés ("RUE DE RIVOLI", "AV FOCH")
 *  - nom_commune       : accentué ("Paris 8e Arrondissement")
 *  - code_commune      : INSEE 5 cars ("75108")
 *  - adresse_code_voie : FANTOIR 4 cars (identique à l'ancien Code voie)
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, must-revalidate');

const LABEL_SEP = ' — ';
const CACHE_TTL = 300;

// ======= ENTRÉES
$qRaw     = isset($_GET['q'])       ? trim((string)$_GET['q'])      : '';
$limit    = isset($_GET['limit'])   ? (int)$_GET['limit']           : 20;
$communeQ = isset($_GET['commune']) ? trim((string)$_GET['commune']) : '';
if ($limit <= 0 || $limit > 50) $limit = 20;
if ($qRaw === '') { echo json_encode([]); exit; }

// ======= CACHE SERVEUR (APCu)
$_cacheKey = 'acV6_' . md5($qRaw . '|' . $communeQ . '|' . $limit);
$_useCache = function_exists('apcu_enabled') && apcu_enabled();
if ($_useCache) {
    $cached = apcu_fetch($_cacheKey, $found);
    if ($found) { echo $cached; exit; }
}

// ======= OUTILS
function stripAccents(string $s): string {
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    return $t === false ? $s : $t;
}
function norm(string $s): string {
    $s = stripAccents(mb_strtolower($s, 'UTF-8'));
    $s = preg_replace('~[.\'\-]~u', '', $s);
    $s = preg_replace('~\s+~u', ' ', trim($s));
    return $s;
}
function likeWrap(string $s): string   { $s = str_replace(['%','_'],['\\%','\\_'],$s); return '%'.$s.'%'; }
function prefixWrap(string $s): string { $s = str_replace(['%','_'],['\\%','\\_'],$s); return $s.'%'; }

function parseNumeroBtq(string $s): array {
    if (preg_match('~^(\d{1,5})\s*([a-zA-Z]{1,6})?$~u', $s, $m)) {
        $no  = $m[1];
        $btq = isset($m[2]) ? mb_strtolower($m[2], 'UTF-8') : '';
        if ($btq !== '' && !in_array($btq, ['bis','ter','quater','a','b','c','d','e'], true)) $btq = $btq[0];
        return [$no, $btq];
    }
    return ['', ''];
}
function detectCp(string $s): string {
    return preg_match('~\b(\d{5})\b~', $s, $m) ? $m[1] : '';
}
function romanToInt(string $r): int {
    $map = ['M'=>1000,'CM'=>900,'D'=>500,'CD'=>400,'C'=>100,'XC'=>90,'L'=>50,'XL'=>40,'X'=>10,'IX'=>9,'V'=>5,'IV'=>4,'I'=>1];
    $R = strtoupper($r); $i = 0; $v = 0;
    while ($i < strlen($R)) {
        if ($i+1 < strlen($R) && isset($map[$R[$i].$R[$i+1]])) { $v += $map[$R[$i].$R[$i+1]]; $i += 2; }
        else { $v += $map[$R[$i]] ?? 0; $i++; }
    }
    return $v;
}

// ======= MAPPING TYPE DE VOIE : saisie utilisateur → abréviation DB
// La DB stocke ex: "AV FOCH", "BD HAUSSMANN", "RUE DE RIVOLI", "CHE DE LA CROIX"
$typeUserToDb = [
    'rue'=>'RUE','r'=>'RUE',
    'avenue'=>'AV','av'=>'AV','ave'=>'AV','aven'=>'AV',
    'boulevard'=>'BD','bd'=>'BD','boul'=>'BD','boulev'=>'BD','bld'=>'BD',
    'chemin'=>'CHE','chem'=>'CHE','che'=>'CHE',
    'route'=>'RTE','rte'=>'RTE','rt'=>'RTE',
    'allee'=>'ALL','allée'=>'ALL','all'=>'ALL',
    'impasse'=>'IMP','imp'=>'IMP',
    'place'=>'PL','pl'=>'PL',
    'residence'=>'RES','résidence'=>'RES','res'=>'RES',
    'square'=>'SQ','sq'=>'SQ',
    'quai'=>'QUAI','q'=>'QUAI',
    'cours'=>'CRS','crs'=>'CRS',
    'passage'=>'PAS','pas'=>'PAS','pass'=>'PAS','psg'=>'PAS',
    'voie'=>'VC','vc'=>'VC',
    'sentier'=>'SEN','sen'=>'SEN',
    'cite'=>'CITE','cité'=>'CITE','cte'=>'CITE',
    'villa'=>'VLA','vla'=>'VLA',
    'promenade'=>'PROM','prom'=>'PROM',
    'lotissement'=>'LOT','lot'=>'LOT',
    'domaine'=>'DOM','dom'=>'DOM',
    'hameau'=>'HAM','ham'=>'HAM',
    'parvis'=>'PRV','prv'=>'PRV',
    'esplanade'=>'ESPL','espl'=>'ESPL',
    'cour'=>'COUR',
    'grande rue'=>'GRANDE RUE','gd rue'=>'GRANDE RUE','grande'=>'GRANDE RUE',
];

// Abréviations dans la partie non-type (saint→ST etc.)
$voieWordNorm = [
    'saint'=>'ST','sainte'=>'STE',
    'general'=>'GAL','generale'=>'GAL',
    'marechal'=>'MAL',
    'docteur'=>'DR',
    'president'=>'PDT',
    'faubourg'=>'FBG','fg'=>'FBG',
];

function normalizeVoieTokens(array $tokens, array $wordNorm): array {
    $out = [];
    foreach ($tokens as $tok) {
        $k = norm($tok);
        $out[] = isset($wordNorm[$k]) ? $wordNorm[$k] : mb_strtoupper($tok, 'UTF-8');
    }
    return $out;
}

// Détecte "paris 8", "lyon 3", "marseille 5" — retourne commune_like pour nom_commune
// et code_commune exact pour filtre rapide
function detectVilleArrondissement(array $tokensNoAccent): array {
    $out = ['commune_like' => '', 'code_commune' => '', 'cp' => ''];
    $n = count($tokensNoAccent);
    if ($n < 2) return $out;
    for ($i = 0; $i < $n - 1; $i++) {
        $city = mb_strtolower($tokensNoAccent[$i], 'UTF-8');
        $next = $tokensNoAccent[$i + 1];
        $arr  = null;
        if (preg_match('~^\d{1,2}$~', $next))               $arr = (int)$next;
        elseif (preg_match('~^(i|v|x|l|c|d|m)+$~i', $next)) $arr = romanToInt($next);
        if (!$arr || $arr < 1) continue;
        if ($city === 'paris' && $arr <= 20) return [
            'commune_like' => 'Paris ' . $arr . '%',
            'code_commune' => '751' . str_pad((string)$arr, 2, '0', STR_PAD_LEFT),
            'cp'           => '75'  . str_pad((string)$arr, 3, '0', STR_PAD_LEFT),
        ];
        if ($city === 'lyon' && $arr <= 9) return [
            'commune_like' => 'Lyon ' . $arr . '%',
            'code_commune' => '6938' . $arr,
            'cp'           => '6900' . $arr,
        ];
        if ($city === 'marseille' && $arr <= 16) return [
            'commune_like' => 'Marseille ' . $arr . '%',
            'code_commune' => '132' . str_pad((string)$arr, 2, '0', STR_PAD_LEFT),
            'cp'           => '130' . str_pad((string)$arr, 2, '0', STR_PAD_LEFT),
        ];
    }
    return $out;
}

function runQuery(PDO $pdo, string $sql, array $params): array {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// ======= PARSING DE LA SAISIE

$rawNoAccent = stripAccents($qRaw);

// Commune explicite via virgule : "2 rue de rivoli, paris"
$communePartsAccented = [];
$communeExplicit = false;
$lastCommaPos = mb_strrpos($qRaw, ',');
if ($lastCommaPos !== false) {
    $leftPart  = trim(mb_substr($qRaw, 0, $lastCommaPos));
    $rightPart = trim(mb_substr($qRaw, $lastCommaPos + 1));
    $rawNoAccent = stripAccents($leftPart);
    if ($rightPart !== '') {
        $communePartsAccented = preg_split('~\s+~u', $rightPart) ?: [];
        $communeExplicit = true;
    }
}

$tokensNoAccent = preg_split('~\s+~u', trim($rawNoAccent)) ?: [];

// n° + btq en tête
$no_voie = ''; $btq = '';
if (!empty($tokensNoAccent)) {
    [$no, $b] = parseNumeroBtq($tokensNoAccent[0]);
    if ($no !== '') { $no_voie = $no; $btq = $b; array_shift($tokensNoAccent); }
}

// Paramètre communeQ prioritaire
if ($communeQ !== '') {
    $communePartsAccented = preg_split('~\s+~u', $communeQ) ?: [];
    $communeExplicit = true;
}

// CP dans la saisie
$cpQuery      = detectCp($qRaw);
$code_commune = '';
$commune_like = '';

if (!$communeExplicit) {
    // Tente de détecter "paris 8", "lyon 3"...
    $detect = detectVilleArrondissement($tokensNoAccent);
    if ($detect['commune_like'] !== '') {
        $commune_like = $detect['commune_like'];
        $code_commune = $detect['code_commune'];
        if ($cpQuery === '') $cpQuery = $detect['cp'];
        // Retire ville+arrondissement des tokens voie
        $tokensNoAccent = array_values(array_filter($tokensNoAccent, function ($t) {
            return !preg_match('~^\d{1,2}$~', $t)
                && !in_array(mb_strtolower($t, 'UTF-8'), ['paris','lyon','marseille'], true)
                && !preg_match('~^(i|v|x|l|c|d|m)+$~i', $t);
        }));
    } elseif ($cpQuery === '') {
        // Ville seule en fin de saisie : "2 rue de rivoli paris"
        static $knownCities = ['paris','lyon','marseille','bordeaux','toulouse',
                               'nantes','lille','nice','strasbourg','montpellier','rennes'];
        $lastTok = !empty($tokensNoAccent) ? mb_strtolower(end($tokensNoAccent), 'UTF-8') : '';
        if (in_array($lastTok, $knownCities, true)) {
            $communePartsAccented = [mb_strtoupper($lastTok, 'UTF-8')];
            array_pop($tokensNoAccent);
            $commune_like = ucfirst($lastTok) . '%';
        }
    }
} else {
    $communeStr   = trim(implode(' ', $communePartsAccented));
    $commune_like = $communeStr . '%';
}

// Commune via parts sans arrondissement
if ($commune_like === '' && !empty($communePartsAccented)) {
    $commune_like = trim(implode(' ', $communePartsAccented)) . '%';
}

// ======= CONSTRUCTION DU VOIE SEARCH
// Extraire le type de voie (1 ou 2 tokens)
$voieTokens = $tokensNoAccent;
$typePrefix = '';
for ($len = 2; $len >= 1; $len--) {
    $slice = norm(implode(' ', array_slice($voieTokens, 0, $len)));
    if (isset($typeUserToDb[$slice])) {
        $typePrefix = $typeUserToDb[$slice];
        $voieTokens = array_slice($voieTokens, $len);
        break;
    }
}

// Normalise les mots restants (saint→ST etc.)
$voieRest   = normalizeVoieTokens($voieTokens, $voieWordNorm);
$voieSearch = trim(($typePrefix !== '' ? $typePrefix . ' ' : '') . implode(' ', $voieRest));
$voieKeyword = trim(implode(' ', $voieRest)); // pour le fallback %LIKE%
// Tokens bruts sans normalisation (pour gérer SAINT vs ST incohérence)
$voieKeywordRaw = trim(implode(' ', array_map('mb_strtoupper', $voieTokens)));

// ======= PDO
require_once __DIR__ . '/../config.php';
try {
    $pdo = get_pdo();
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([]);
    exit;
}
$tableDvf = db_table_dvf();

// ======= DÉDOUBLONNAGE
$results = [];
$push = function (array $r) use (&$results) {
    $key = ($r['adresse_code_voie'] ?? '') . '|'
         . ($r['code_postal'] ?? '') . '|'
         . ($r['nom_commune'] ?? '') . '|'
         . ($r['adresse_nom_voie'] ?? '');
    if (!isset($results[$key])) $results[$key] = $r;
};

$sel = "SELECT adresse_code_voie, code_postal, nom_commune, adresse_nom_voie,
               MIN(SUBSTRING(id_parcelle, 9, 2)) AS section
        FROM {$tableDvf} WHERE 1=1";
$grp = " GROUP BY adresse_code_voie, code_postal, nom_commune, adresse_nom_voie";

// Ajoute les filtres commune (code_commune exact prioritaire)
$addCommune = function (string &$sql, array &$params) use ($commune_like, $code_commune, $cpQuery) {
    if ($code_commune !== '') {
        $sql .= " AND code_commune = ?";
        $params[] = $code_commune;
    } elseif ($commune_like !== '') {
        $sql .= " AND LOWER(nom_commune) LIKE LOWER(?)";
        $params[] = $commune_like;
    }
    if ($cpQuery !== '') {
        $sql .= " AND code_postal = ?";
        $params[] = $cpQuery;
    }
};

$hasCommune = ($commune_like !== '' || $code_commune !== '' || $cpQuery !== '');

// ======= Palier S : split suffix (voie…ville) — sans commune explicite
// La partie "commune" doit avoir ≥ 3 caractères pour éviter "DE" → Decines
if (!$hasCommune && $voieSearch !== '') {
    $tok = preg_split('~\s+~u', $voieSearch) ?: [];
    $n   = count($tok);
    for ($k = min(3, $n - 1); $k >= 1; $k--) {
        $voieP = trim(implode(' ', array_slice($tok, 0, $n - $k)));
        $commC = trim(implode(' ', array_slice($tok, $n - $k)));
        if ($voieP === '' || mb_strlen($commC) < 3) continue;
        $sql = $sel; $p = [];
        $sql .= " AND adresse_nom_voie LIKE ?"; $p[] = prefixWrap($voieP);
        $sql .= " AND LOWER(nom_commune) LIKE LOWER(?)"; $p[] = prefixWrap($commC);
        if ($no_voie !== '') { $sql .= " AND (adresse_numero = ? OR adresse_numero LIKE ?)"; $p[] = $no_voie; $p[] = likeWrap($no_voie); }
        $sql .= $grp . " ORDER BY LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
        foreach (runQuery($pdo, $sql, $p) as $r) { $push($r); if (count($results) >= $limit) break 2; }
    }
}

// ======= Palier A : prefix voie + commune
// Si pas de commune précisée, prioriser Paris (75xxx) dans les résultats
if (count($results) < $limit && $voieSearch !== '') {
    $sql = $sel; $p = [];
    $sql .= " AND adresse_nom_voie LIKE ?"; $p[] = prefixWrap($voieSearch);
    if ($no_voie !== '') { $sql .= " AND (adresse_numero = ? OR adresse_numero LIKE ?)"; $p[] = $no_voie; $p[] = likeWrap($no_voie); }
    $addCommune($sql, $p);
    if ($cpQuery !== '') {
        $sql .= $grp . " ORDER BY CASE WHEN code_postal = ? THEN 0 ELSE 1 END, LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
        $p[] = $cpQuery;
    } else {
        // Sans commune précisée : Paris en premier
        $sql .= $grp . " ORDER BY CASE WHEN code_postal LIKE '75%' THEN 0 ELSE 1 END, LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
    }
    foreach (runQuery($pdo, $sql, $p) as $r) { $push($r); if (count($results) >= $limit) break; }
}

// ======= Palier A2 : prefix voie sans commune (élargit la recherche)
if (count($results) < $limit && $voieSearch !== '' && $hasCommune) {
    $sql = $sel; $p = [];
    $sql .= " AND adresse_nom_voie LIKE ?"; $p[] = prefixWrap($voieSearch);
    if ($no_voie !== '') { $sql .= " AND (adresse_numero = ? OR adresse_numero LIKE ?)"; $p[] = $no_voie; $p[] = likeWrap($no_voie); }
    $sql .= $grp . " ORDER BY LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
    foreach (runQuery($pdo, $sql, $p) as $r) { $push($r); if (count($results) >= $limit) break; }
}

// ======= Palier B : prefix sans commune (quand pas de commune du tout)
// Paris (75xxx) prioritaire dans le tri
if (count($results) < $limit && $voieSearch !== '' && !$hasCommune) {
    $sql = $sel; $p = [];
    $sql .= " AND adresse_nom_voie LIKE ?"; $p[] = prefixWrap($voieSearch);
    if ($no_voie !== '') { $sql .= " AND (adresse_numero = ? OR adresse_numero LIKE ?)"; $p[] = $no_voie; $p[] = likeWrap($no_voie); }
    $sql .= $grp . " ORDER BY CASE WHEN code_postal LIKE '75%' THEN 0 ELSE 1 END, LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
    foreach (runQuery($pdo, $sql, $p) as $r) { $push($r); if (count($results) >= $limit) break; }
}

// ======= Palier B2 : %keyword% avec commune (gère SAINT/ST incohérences)
if (count($results) < $limit && ($voieKeyword !== '' || $voieSearch !== '')) {
    $kw = $voieKeyword !== '' ? $voieKeyword : $voieSearch;
    $sql = $sel; $p = [];
    $sql .= " AND adresse_nom_voie LIKE ?"; $p[] = likeWrap($kw);
    if ($no_voie !== '') { $sql .= " AND (adresse_numero = ? OR adresse_numero LIKE ?)"; $p[] = $no_voie; $p[] = likeWrap($no_voie); }
    $addCommune($sql, $p);
    $sql .= $grp . " ORDER BY LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
    foreach (runQuery($pdo, $sql, $p) as $r) { $push($r); if (count($results) >= $limit) break; }
}

// ======= Palier B2b : %keyword brut% (SAINT au lieu de ST, variante non normalisée)
if (count($results) < $limit && $voieKeywordRaw !== '' && $voieKeywordRaw !== $voieKeyword) {
    $sql = $sel; $p = [];
    $sql .= " AND adresse_nom_voie LIKE ?"; $p[] = likeWrap($voieKeywordRaw);
    if ($no_voie !== '') { $sql .= " AND (adresse_numero = ? OR adresse_numero LIKE ?)"; $p[] = $no_voie; $p[] = likeWrap($no_voie); }
    $addCommune($sql, $p);
    $sql .= $grp . " ORDER BY LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
    foreach (runQuery($pdo, $sql, $p) as $r) { $push($r); if (count($results) >= $limit) break; }
}

// ======= Palier C : commune / CP seuls
if (count($results) < $limit && $voieSearch === '' && $hasCommune) {
    $sql = $sel; $p = [];
    $addCommune($sql, $p);
    $sql .= $grp . " ORDER BY LENGTH(adresse_nom_voie) ASC LIMIT " . (int)$limit;
    foreach (runQuery($pdo, $sql, $p) as $r) { $push($r); if (count($results) >= $limit) break; }
}

// ======= SORTIE
$out = [];
foreach ($results as $r) {
    $nomVoie = (string)($r['adresse_nom_voie'] ?? '');
    $cp      = (string)($r['code_postal'] ?? '');
    $com     = (string)($r['nom_commune'] ?? '');
    $sec     = (string)($r['section'] ?? '');
    $cv      = (string)($r['adresse_code_voie'] ?? '');

    $leftParts = [];
    if ($no_voie !== '') $leftParts[] = $no_voie . ($btq ? ' ' . $btq : '');
    if ($nomVoie !== '') $leftParts[] = $nomVoie;

    $left  = trim(implode(' ', $leftParts));
    $right = trim($cp . ' ' . $com);
    $label = $left !== '' ? $left . LABEL_SEP . $right : $right;

    $out[] = [
        'label'           => $label,
        'code_voie'       => $cv,
        'commune'         => $com,
        'cp'              => $cp,
        'adresse_nom_voie'=> $nomVoie,
        'voie'            => $nomVoie,   // compat descendante
        'type_voie'       => '',         // compat descendante (fusionné dans adresse_nom_voie)
        'no_voie'         => $no_voie,
        'btq'             => $btq,
        'section'         => $sec,
    ];
    if (count($out) >= $limit) break;
}

// Résultat vide : renvoie la saisie brute pour ne pas bloquer l'UI
if (empty($out)) {
    $out[] = [
        'label'           => $qRaw,
        'code_voie'       => '',
        'commune'         => $commune_like !== '' ? rtrim($commune_like, '%') : trim(implode(' ', $communePartsAccented)),
        'cp'              => $cpQuery,
        'adresse_nom_voie'=> $voieSearch,
        'voie'            => $voieSearch,
        'type_voie'       => '',
        'no_voie'         => $no_voie,
        'btq'             => $btq,
        'section'         => '',
    ];
}

$json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($_useCache) apcu_store($_cacheKey, $json, CACHE_TTL);
echo $json;
