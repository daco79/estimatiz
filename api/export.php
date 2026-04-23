<?php
// === api/export.php ===
// Exporte le "fichier de sortie V3" sous différents formats : json | csv | xls | pdf
// - Sans modification de schéma : conversions à la volée (STR_TO_DATE, REPLACE).
// - Surface indicative = Carrez prioritaire, sinon SRB.
// - Fallback de scope: street -> section -> commune (seuil nmin, défaut 10).
// - CSV/XLS : exportent la table des comparables (l'JSON reste le format canonique complet).
// - PDF : si Dompdf est présent (composer), on génère un résumé. Sinon, message clair (501).

declare(strict_types=1);
header('Cache-Control: no-store');

function fail($msg, $code=400) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

try {
  require_once __DIR__ . '/../config.php';
  if (!function_exists('get_pdo')) fail("La fonction get_pdo() est introuvable dans config.php");
  $pdo = get_pdo();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
  fail("Erreur de connexion: " . $e->getMessage(), 500);
}

// ---- Params ----
$code_voie = $_GET['code_voie'] ?? null;
$commune   = $_GET['commune']   ?? null;
$section   = $_GET['section']   ?? null;
$type_local = $_GET['type_local'] ?? 'Appartement';
$pieces     = isset($_GET['pieces']) && $_GET['pieces']!=='' ? (int)$_GET['pieces'] : null;
$annee_min  = isset($_GET['annee_min']) ? (int)$_GET['annee_min'] : 2010;
$annee_max  = isset($_GET['annee_max']) ? (int)$_GET['annee_max'] : 2099;
$nmin = isset($_GET['nmin']) ? max(1, (int)$_GET['nmin']) : 10;
$surface_subject = isset($_GET['surface']) && $_GET['surface']!=='' ? floatval(str_replace(',', '.', $_GET['surface'])) : null;

$surface_min = isset($_GET['surface_min']) && $_GET['surface_min']!=='' ? floatval(str_replace(',', '.', $_GET['surface_min'])) : null;
$surface_max = isset($_GET['surface_max']) && $_GET['surface_max']!=='' ? floatval(str_replace(',', '.', $_GET['surface_max'])) : null;

$format = strtolower($_GET['format'] ?? 'json');
if (!in_array($format, ['json','csv','xls','xlsx','pdf'], true)) $format='json';
if ($format==='xlsx') $format='xls'; // compat: on sert un HTML/Excel lisible par Excel

if ($annee_max < $annee_min) { $t=$annee_min; $annee_min=$annee_max; $annee_max=$t; }
if (!$code_voie && !$section && !$commune) {
  if (!$commune) fail("Veuillez fournir au minimum code_voie, ou section, ou commune.");
}

// ---- Helpers ----
function percentile(array $values, float $p) : ?float {
  $n=count($values); if ($n===0) return null;
  sort($values, SORT_NUMERIC);
  if ($n===1) return (float)$values[0];
  $pos = ($n - 1) * $p;
  $lo = (int)floor($pos);
  $hi = (int)ceil($pos);
  if ($lo === $hi) return (float)$values[$lo];
  $w = $pos - $lo;
  return (float)($values[$lo]*(1-$w) + $values[$hi]*$w);
}

function commune_condition(string $commune) : array {
  $c = strtolower(trim($commune));
  if (strpos($c, 'paris') === 0 || strpos($c, 'lyon') === 0 || strpos($c, 'marseille') === 0) {
    $prefix = explode(' ', $commune)[0];
    return ['`Commune` LIKE :commune', [':commune', $prefix . '%']];
  }
  return ['`Commune` = :commune', [':commune', $commune]];
}

function fetch_rows(PDO $pdo, string $scope, array $b) : array {
  $w=[]; $p=[];
  $w[]="`Type local` = :type_local"; $p[':type_local']=$b['type_local'];
  if ($b['pieces']!==null){ $w[]="`Nombre pieces principales` = :pieces"; $p[':pieces']=$b['pieces']; }
  $w[]="YEAR(STR_TO_DATE(`Date mutation`, '%d/%m/%Y')) BETWEEN :ymin AND :ymax"; $p[':ymin']=$b['annee_min']; $p[':ymax']=$b['annee_max'];
  $w[]="REPLACE(`Valeur fonciere`, ',', '.') + 0 >= 10000"; // exclure erreurs de données
  if ($scope==='street'){
    $w[]="`Code voie` = :code_voie"; $p[':code_voie']=$b['code_voie'];
    if(!empty($b['commune'])){ [$cw,$cv]=commune_condition($b['commune']); $w[]=$cw; $p[$cv[0]]=$cv[1]; }
  } elseif ($scope==='section') {
    $w[]="`Section` = :section"; $p[':section']=$b['section'];
    if(!empty($b['commune'])){ [$cw,$cv]=commune_condition($b['commune']); $w[]=$cw; $p[$cv[0]]=$cv[1]; }
  } else {
    [$cw,$cv]=commune_condition($b['commune']); $w[]=$cw; $p[$cv[0]]=$cv[1];
  }
  if ($b['surface_min']!==null){ $w[]="(NULLIF(REPLACE(`Surface reelle bati`, ',', '.'), '') + 0) >= :smin"; $p[':smin']=$b['surface_min']; }
  if ($b['surface_max']!==null){ $w[]="(NULLIF(REPLACE(`Surface reelle bati`, ',', '.'), '') + 0) <= :smax"; $p[':smax']=$b['surface_max']; }

  $table = $b['table'];
  $sql="
    SELECT
      STR_TO_DATE(`Date mutation`, '%d/%m/%Y') AS dt,
      DATE_FORMAT(STR_TO_DATE(`Date mutation`, '%d/%m/%Y'), '%Y-%m-%d') AS date_iso,
      `Date mutation` AS date_raw,
      REPLACE(`Valeur fonciere`, ',', '.') + 0 AS valeur,
      NULLIF(REPLACE(`Surface Carrez du 1er lot`, ',', '.'), '') + 0 AS carrez,
      NULLIF(REPLACE(`Surface reelle bati`, ',', '.'), '') + 0 AS srb,
      `No voie` AS no_voie, `B/T/Q` AS btq, `Type de voie` AS type_voie, `Voie` AS voie,
      `Code voie` AS code_voie, `Commune` AS commune, `Section` AS section,
      `Nombre pieces principales` AS pieces, `Nature mutation` AS nature
    FROM {$table}
    WHERE ".implode(' AND ', $w)."
    ORDER BY STR_TO_DATE(`Date mutation`, '%d/%m/%Y') DESC
  ";
  $st=$pdo->prepare($sql); $st->execute($p);
  $rows=$st->fetchAll(PDO::FETCH_ASSOC);
  $out=[];
  foreach($rows as $r){
    $val = floatval($r['valeur']);
    $carrez = floatval($r['carrez']);
    $srb = floatval($r['srb']);
    $surf = $carrez>0 ? $carrez : $srb;
    if ($val>0 && $surf>0){
      $adresse = trim(($r['no_voie']!==null && $r['no_voie']!=='') ? $r['no_voie'].' ' : '');
      if ($r['btq']!==null && $r['btq']!=='') $adresse .= $r['btq'].' ';
      if ($r['type_voie']!==null && $r['type_voie']!=='') $adresse .= $r['type_voie'].' ';
      $adresse .= ($r['voie']??'');
      $out[] = [
        'date' => $r['date_raw'],
        'date_iso' => $r['date_iso'],
        'prix' => round($val),
        'surface_indic' => round($surf,2),
        'prix_m2' => round($val/$surf,2),
        'surface_carrez' => $carrez>0 ? round($carrez,2) : null,
        'surface_srb' => $srb>0 ? round($srb,2) : null,
        'adresse' => $adresse,
        'commune' => $r['commune'],
        'code_voie' => $r['code_voie'],
        'section' => $r['section'],
        'pieces' => is_numeric($r['pieces']) ? (int)$r['pieces'] : null,
        'nature' => $r['nature']
      ];
    }
  }
  return $out;
}

$binds = [
  'type_local'=>$type_local, 'pieces'=>$pieces,
  'annee_min'=>$annee_min, 'annee_max'=>$annee_max,
  'code_voie'=>$code_voie, 'section'=>$section, 'commune'=>$commune,
  'surface_min'=>$surface_min, 'surface_max'=>$surface_max,
  'table'=>db_table_dvf()
];

// Récupère chaque scope une seule fois et garde les résultats pour le fallback
$cache = [];
if ($code_voie) $cache['street']  = fetch_rows($pdo, 'street',  $binds);
if ($section)   $cache['section'] = fetch_rows($pdo, 'section', $binds);
if ($commune)   $cache['commune'] = fetch_rows($pdo, 'commune', $binds);

$scope = null; $rows = [];
if (isset($cache['street'])  && count($cache['street'])  >= $nmin) { $scope='street';  $rows=$cache['street']; }
if (!$scope && isset($cache['section']) && count($cache['section']) >= $nmin) { $scope='section'; $rows=$cache['section']; }
if (!$scope && isset($cache['commune']) && (count($cache['commune']) >= $nmin || (!$code_voie && !$section))) { $scope='commune'; $rows=$cache['commune']; }
if (!$scope) {
  foreach ($cache as $s => $r) { if (count($r) > count($rows)) { $rows=$r; $scope=$s; } }
  if (!$scope) $scope = 'fallback';
}

// ---- Stats (global) & estimate ----
$vals = array_map(function($r) {
  return $r['prix_m2'];
}, $rows);
sort($vals, SORT_NUMERIC);

// IQR filter for estimate
function iqr_bounds(array $v, float $k=1.5){ 
  $q1 = percentile($v,0.25); $q3 = percentile($v,0.75);
  if ($q1===null || $q3===null) return [null,null];
  $iqr = $q3 - $q1; if ($iqr<=0) return [$q1,$q3];
  return [$q1 - $k*$iqr, $q3 + $k*$iqr];
}
[$lo,$hi] = iqr_bounds($vals,1.5);
$vals_est = $vals;
if ($lo!==null && $hi!==null){
  $vals_est = array_values(array_filter($vals, function($x) use ($lo, $hi) {
    return $x >= $lo && $x <= $hi;
  }));
}
$p20 = percentile($vals_est,0.20);
$p50 = percentile($vals_est,0.50);
$p80 = percentile($vals_est,0.80);

$estimate = ['low'=>null,'mid'=>null,'high'=>null,'confidence'=>null];
if ($surface_subject && $p20!==null && $p50!==null && $p80!==null){
  $estimate['low']  = round($p20 * $surface_subject);
  $estimate['mid']  = round($p50 * $surface_subject);
  $estimate['high'] = round($p80 * $surface_subject);
}
$conf=null;
if ($p50!==null && count($vals_est)>0){
  $q1 = percentile($vals_est,0.25); $q3 = percentile($vals_est,0.75);
  if ($p50>0 && $q1!==null && $q3!==null){
    $disp = ($q3 - $q1)/$p50;
    $conf_n = min(1.0, count($vals_est) / max(10.0, $nmin*2.0));
    $conf_d = max(0.0, min(1.0, 1.0 - $disp));
    $conf = round(0.6*$conf_n + 0.4*$conf_d, 2);
  }
}
$estimate['confidence'] = $conf;

// ---- Timeseries ----
$byYear=[];
foreach($rows as $r){
  $y = (int)substr($r['date_iso'],0,4);
  if ($y < $annee_min || $y > $annee_max) continue;
  $byYear[$y][] = $r['prix_m2'];
}
$years = range($annee_min,$annee_max);
$ts = ['years'=>[],'median'=>[],'p20'=>[],'p80'=>[],'count'=>[]];
foreach($years as $y){
  $ts['years'][]=$y;
  $values = $byYear[$y] ?? [];
  sort($values,SORT_NUMERIC);
  if (count($values)===0){
    $ts['median'][]=null; $ts['p20'][]=null; $ts['p80'][]=null; $ts['count'][]=0;
  } else {
    $ts['median'][]=round((float)percentile($values,0.50),2);
    $ts['p20'][]=round((float)percentile($values,0.20),2);
    $ts['p80'][]=round((float)percentile($values,0.80),2);
    $ts['count'][]=count($values);
  }
}

// ---- Distribution (histogram on vals_est) ----
$bins=[];
if (count($vals_est)>0){
  $min = $vals_est[0];
  $max = $vals_est[count($vals_est)-1];
  if ($max>$min){
    $k = min(20, max(8, (int)ceil(log10(count($vals_est)+1)*6))); // nb bins adaptatif
    $width = ($max - $min) / $k;
    for($i=0;$i<$k;$i++){ $edge = $min + $i*$width; $bins[]=['bin'=>round($edge,2),'n'=>0]; }
    foreach($vals_est as $v){
      $idx = (int)floor(($v - $min) / $width);
      if ($idx >= $k) $idx = $k-1;
      if ($idx < 0) $idx = 0;
      $bins[$idx]['n'] += 1;
    }
  } else {
    $bins[] = ['bin'=>round($min,2),'n'=>count($vals_est)];
  }
}

// ---- Canonical JSON ----
$out = [
  'ok'=>true,
  'meta'=>[
    'generated_at'=>gmdate('c'),
    'version'=>'3.0',
    'scope'=>$scope,
    'filters'=>[
      'type_local'=>$type_local, 'pieces'=>$pieces,
      'annees'=>[$annee_min,$annee_max], 'code_voie'=>$code_voie, 'section'=>$section, 'commune'=>$commune,
      'surface_min'=>$surface_min, 'surface_max'=>$surface_max
    ]
  ],
  'subject'=>[ 'surface'=>$surface_subject, 'type_local'=>$type_local, 'pieces'=>$pieces ],
  'stats'=>[
    'price_sqm'=>[
      'p20'=>$p20!==null?round($p20,2):null,
      'median'=>$p50!==null?round($p50,2):null,
      'p80'=>$p80!==null?round($p80,2):null
    ],
    'count'=>count($vals)
  ],
  'estimate'=>$estimate,
  'timeseries'=>$ts,
  'distribution_bins'=>$bins,
  'top_comps'=>array_slice($rows,0,10),
  'comps'=>$rows
];

// ---- Output ----
$basename = 'estimatiz_v3_' . ($commune ?: ($section ?: ($code_voie ?: 'export'))) . '_' . date('Ymd_His');

if ($format==='json'){
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$basename.'.json"');
  echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
  exit;
}

if ($format==='csv'){
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$basename.'.csv"');
  $outf = fopen('php://output','w');
  // Header
  fputcsv($outf, ['date','adresse','commune','pieces','surface_indic','prix','prix_m2','nature'], ';');
  foreach($rows as $r){
    fputcsv($outf, [
      $r['date'],$r['adresse'],$r['commune'],$r['pieces'],
      $r['surface_indic'],$r['prix'],$r['prix_m2'],$r['nature']
    ], ';');
  }
  fclose($outf);
  exit;
}

if ($format==='xls'){
  header('Content-Type: application/vnd.ms-excel; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$basename.'.xls"');
  echo "<table border='1'>";
  echo "<tr><th colspan='8' style='font-weight:bold'>Estimatiz V3 — Comparables</th></tr>";
  echo "<tr><th>Date</th><th>Adresse</th><th>Commune</th><th>Pièces</th><th>Surface (m²)</th><th>Prix</th><th>€/m²</th><th>Nature</th></tr>";
  foreach($rows as $r){
    echo "<tr>";
    echo "<td>".htmlspecialchars($r['date'])."</td>";
    echo "<td>".htmlspecialchars($r['adresse'])."</td>";
    echo "<td>".htmlspecialchars($r['commune'])."</td>";
    echo "<td>".htmlspecialchars((string)$r['pieces'])."</td>";
    echo "<td>".htmlspecialchars((string)$r['surface_indic'])."</td>";
    echo "<td>".htmlspecialchars((string)$r['prix'])."</td>";
    echo "<td>".htmlspecialchars((string)$r['prix_m2'])."</td>";
    echo "<td>".htmlspecialchars((string)$r['nature'])."</td>";
    echo "</tr>";
  }
  echo "</table>";
  exit;
}

if ($format==='pdf'){
  // PDF summary if Dompdf available
  if (!class_exists('\\Dompdf\\Dompdf')) {
    http_response_code(501);
    header('Content-Type: text/html; charset=utf-8');
    echo "<html><body><h3>Export PDF non activé</h3><p>Pour activer l'export PDF, installez <code>dompdf/dompdf</code> via Composer puis assurez-vous que l'autoload est inclus dans <code>config.php</code>.<br>En attendant, utilisez l'export XLS/CSV ou <em>Imprimer → Enregistrer au format PDF</em> depuis le navigateur.</p></body></html>";
    exit;
  }
  $dompdf = new \Dompdf\Dompdf();
  $h = "<h2>Estimatiz V3 — Résumé</h2>";
  $h .= "<p><b>Scope :</b> ".htmlspecialchars($scope)." — <b>Années :</b> ".$annee_min."–".$annee_max."</p>";
  $h .= "<p><b>€/m² :</b> p20 ".number_format((float)$out['stats']['price_sqm']['p20'],0,',',' ')." · médiane ".number_format((float)$out['stats']['price_sqm']['median'],0,',',' ')." · p80 ".number_format((float)$out['stats']['price_sqm']['p80'],0,',',' ')."</p>";
  if ($estimate['low']!==null){
    $h .= "<p><b>Estimation :</b> ".number_format($estimate['low'],0,',',' ')." € / ".number_format($estimate['mid'],0,',',' ')." € / ".number_format($estimate['high'],0,',',' ')." € (confiance ".(int)round($estimate['confidence']*100)."%)</p>";
  }
  $h .= "<h3>Top comparables</h3><table border='1' cellpadding='4' cellspacing='0'><tr><th>Date</th><th>Adresse</th><th>Surf</th><th>Prix</th><th>€/m²</th></tr>";
  foreach (array_slice($rows,0,12) as $r){
    $h .= "<tr><td>".htmlspecialchars($r['date'])."</td><td>".htmlspecialchars($r['adresse'])."</td><td>".htmlspecialchars((string)$r['surface_indic'])."</td><td>".number_format((float)$r['prix'],0,',',' ')." €</td><td>".number_format((float)$r['prix_m2'],0,',',' ')." €/m²</td></tr>";
  }
  $h .= "</table>";
  $dompdf->loadHtml($h);
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  $dompdf->stream($basename.'.pdf', ['Attachment'=>true]);
  exit;
}

// Fallback (shouldn't happen)
fail('Format non géré', 400);
?>
