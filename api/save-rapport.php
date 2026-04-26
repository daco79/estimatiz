<?php
/**
 * api/save-rapport.php
 * Reçoit en POST JSON les données sélectionnées et génère un rapport HTML statique.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'Méthode non autorisée']));
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || !isset($data['rows']) || !is_array($data['rows'])) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Données invalides']));
}

$rows       = $data['rows'];
$label      = trim($data['label']    ?? 'Rapport');
$surface    = isset($data['surface'])    && is_numeric($data['surface'])    ? (float)$data['surface']    : null;
$pieces     = isset($data['pieces'])     && is_numeric($data['pieces'])     ? (int)$data['pieces']       : null;
$surfaceMin = isset($data['surfaceMin']) && is_numeric($data['surfaceMin']) ? (float)$data['surfaceMin'] : null;
$surfaceMax = isset($data['surfaceMax']) && is_numeric($data['surfaceMax']) ? (float)$data['surfaceMax'] : null;
$est        = $data['estimation'] ?? null;
$suggestion = $data['suggestion'] ?? [];

// ── Génère un slug et un nom de fichier unique ─────────────────────────────
function rapport_slugify(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str) ?: $str;
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
}

$cp   = preg_replace('/[^0-9]/', '', $suggestion['cp']   ?? '');
$voie = rapport_slugify($suggestion['voie'] ?? $label);
$base = ($cp ? $cp . '-' : '') . ($voie ?: 'adresse');
$base = substr($base, 0, 60);
$hash = substr(md5($base . microtime(true) . rand()), 0, 6);
$filename = $base . '-' . $hash . '.html';

$rapportsDir = dirname(__DIR__) . '/rapports';
if (!is_dir($rapportsDir)) {
    mkdir($rapportsDir, 0755, true);
}
$filepath = $rapportsDir . '/' . $filename;

// ── Helpers formatage ──────────────────────────────────────────────────────
function rapport_euro(float $n): string {
    return number_format($n, 0, ',', '&nbsp;') . '&nbsp;€';
}
function rapport_date(string $d): string {
    try { return (new DateTime($d))->format('d/m/Y'); } catch (Exception $e) { return $d; }
}

// ── Date du rapport ────────────────────────────────────────────────────────
$today  = (new DateTime())->format('d/m/Y');
$year_  = (new DateTime())->format('Y');

// ── Meta sous-titre ────────────────────────────────────────────────────────
$metaParts = [];
if ($surface)    $metaParts[] = "Surface : <b>{$surface} m²</b>";
if ($pieces)     $metaParts[] = "Pièces : <b>{$pieces}</b>";
if ($surfaceMin || $surfaceMax) $metaParts[] = "Filtre surface : <b>" . ($surfaceMin ?: '…') . "–" . ($surfaceMax ?: '…') . " m²</b>";
$metaParts[] = count($rows) . " vente" . (count($rows) > 1 ? "s" : "") . " sélectionnée" . (count($rows) > 1 ? "s" : "");
$metaHtml = implode('&emsp;·&emsp;', $metaParts);

// ── Estimation HTML ────────────────────────────────────────────────────────
$estHtml = '';
if ($est && isset($est['p50'])) {
    $confColor = $est['conf'] >= 75 ? '#047857' : ($est['conf'] >= 50 ? '#d97706' : '#b91c1c');
    $confLabel = $est['conf'] >= 75 ? 'Élevée'  : ($est['conf'] >= 50 ? 'Modérée'  : 'Faible');
    if ($surface) {
        $lowVal  = rapport_euro($est['p20'] * $surface);
        $midVal  = rapport_euro($est['p50'] * $surface);
        $highVal = rapport_euro($est['p80'] * $surface);
        $lowM2   = rapport_euro($est['p20']) . '/m²';
        $midM2   = rapport_euro($est['p50']) . '/m²';
        $highM2  = rapport_euro($est['p80']) . '/m²';
    } else {
        $lowVal  = rapport_euro($est['p20']) . '/m²';
        $midVal  = rapport_euro($est['p50']) . '/m²';
        $highVal = rapport_euro($est['p80']) . '/m²';
        $lowM2 = $midM2 = $highM2 = '';
    }
    $estHtml = "
    <div class=\"est-box\">
      <div class=\"est-box-hd\">
        <span class=\"est-title\">Estimation" . ($surface ? " pour {$surface} m²" : " — Prix au m²") . "</span>
        <span style=\"font-size:12px;font-weight:600;color:{$confColor}\">Confiance {$est['conf']}% — {$confLabel}</span>
      </div>
      <div class=\"est-cols\">
        <div class=\"est-col low\"><div class=\"lbl\">Basse</div><div class=\"val\">{$lowVal}</div>" . ($lowM2 ? "<div class=\"val-m2\">{$lowM2}</div>" : "") . "</div>
        <div class=\"est-col mid\"><div class=\"lbl\">Médiane</div><div class=\"val\">{$midVal}</div>" . ($midM2 ? "<div class=\"val-m2\">{$midM2}</div>" : "") . "</div>
        <div class=\"est-col high\"><div class=\"lbl\">Haute</div><div class=\"val\">{$highVal}</div>" . ($highM2 ? "<div class=\"val-m2\">{$highM2}</div>" : "") . "</div>
      </div>
    </div>";
}

// ── Lignes du tableau ──────────────────────────────────────────────────────
$tbodyHtml = '';
foreach ($rows as $r) {
    $adresse = htmlspecialchars($r['adresse'] ?? '', ENT_QUOTES, 'UTF-8');
    $valeur  = isset($r['valeur_fonciere']) && is_numeric($r['valeur_fonciere'])
               ? rapport_euro((float)$r['valeur_fonciere']) : '—';
    $surf    = isset($r['surface'])  && $r['surface']  !== null ? htmlspecialchars($r['surface'], ENT_QUOTES, 'UTF-8') . ' m²' : '—';
    $pm2     = isset($r['prix_m2'])  && is_numeric($r['prix_m2']) ? rapport_euro((float)$r['prix_m2']) . '/m²' : '—';
    $date    = isset($r['date_mutation']) && $r['date_mutation'] ? rapport_date($r['date_mutation']) : '—';
    $pieces_ = isset($r['nb_pieces']) && $r['nb_pieces'] !== null ? $r['nb_pieces'] . ' p.' : '—';
    $tbodyHtml .= "
      <tr>
        <td>{$adresse}</td>
        <td class=\"num\">{$valeur}</td>
        <td class=\"num\">{$surf}</td>
        <td class=\"num\">{$pm2}</td>
        <td class=\"ctr\">{$date}</td>
        <td class=\"ctr\">{$pieces_}</td>
      </tr>";
}

// ── URL publique du rapport ────────────────────────────────────────────────
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'estimatiz.fr';
// Détermine le chemin de base (ex: "" sur prod, "/estimatiz" en local)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api/save-rapport.php'; // /estimatiz/api/save-rapport.php
$basePath   = rtrim(dirname(dirname($scriptName)), '/');           // /estimatiz ou ''
$baseUrl    = $protocol . '://' . $host . $basePath;
$rapportUrl = $baseUrl . '/rapports/' . $filename;
$siteUrl    = $baseUrl . '/';

// ── Génération HTML ────────────────────────────────────────────────────────
$labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

$html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Rapport — {$labelEsc} | Estimatiz</title>
  <meta name="description" content="Rapport d'estimation immobilière pour {$labelEsc}. Généré le {$today} via Estimatiz à partir des données DVF officielles."/>
  <meta name="robots" content="index, follow"/>
  <meta property="og:title" content="Rapport — {$labelEsc} | Estimatiz"/>
  <meta property="og:description" content="Estimation immobilière pour {$labelEsc} basée sur les ventes DVF officielles."/>
  <meta property="og:type" content="article"/>
  <link rel="icon" type="image/x-icon" href="/favicon.ico"/>
  <link rel="stylesheet" href="{$basePath}/assets/css/site.css"/>
  <style>
    /* ── Base ── */
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111827}
    /* ── Nav ── */
    .sitenav{background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:200;box-shadow:0 2px 8px rgba(0,0,0,.06)}
    .sitenav-inner{max-width:1100px;margin:0 auto;padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;gap:16px}
    .sitenav-logo{display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:inherit;flex-shrink:0}
    .sitenav-logo-icon{width:38px;height:38px;flex-shrink:0}
    .sitenav-logo-text{display:flex;flex-direction:column;line-height:1.2}
    .sitenav-logo-name{font-size:18px;font-weight:800;color:#1E3A8A}
    .sitenav-logo-tag{font-size:10px;color:#6B7280;letter-spacing:.03em}
    .sitenav-logo:hover .sitenav-logo-name{color:#1e40af}
    .sitenav-links{display:flex;align-items:center;gap:2px;list-style:none;margin:0;padding:0}
    .sitenav-links a{display:inline-block;padding:6px 11px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;border-radius:8px;white-space:nowrap}
    .sitenav-links a:hover{background:#f3f4f6;color:#111827}
    .sitenav-cta{background:#1E3A8A!important;color:#fff!important;border-radius:10px!important;padding:7px 14px!important}
    .sitenav-cta:hover{background:#1e40af!important}
    @media(max-width:768px){.sitenav-links{display:none}.sitenav-inner{padding:0 16px}}
    /* ── Footer ── */
    .site-footer{background:#111827;color:rgba(255,255,255,.7);padding:48px 24px 20px;font-size:14px;margin-top:auto}
    .footer-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:32px;padding-bottom:32px;border-bottom:1px solid rgba(255,255,255,.1)}
    .footer-brand{font-size:18px;font-weight:800;color:#fff;margin-bottom:8px}
    .footer-tag{font-size:13px;color:rgba(255,255,255,.6);line-height:1.6;margin:0}
    .footer-col h4{font-size:13px;font-weight:700;color:#fff;margin:0 0 12px;text-transform:uppercase;letter-spacing:.04em}
    .footer-col ul{list-style:none;margin:0;padding:0}
    .footer-col li{margin-bottom:8px}
    .footer-col a{color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:color .15s}
    .footer-col a:hover{color:#10B981}
    .footer-bottom{max-width:1100px;margin:0 auto;padding-top:16px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:rgba(255,255,255,.5);flex-wrap:wrap;gap:8px}
    .footer-bottom a{color:rgba(255,255,255,.7)}
    .footer-bottom a:hover{color:#fff}
    @media(max-width:768px){.footer-inner{grid-template-columns:1fr 1fr;gap:24px}.footer-bottom{flex-direction:column;text-align:center}}
    @media(max-width:480px){.footer-inner{grid-template-columns:1fr}}
    /* ── Page ── */
    body { background: #F3F4F6; }
    .wrap { max-width: 980px; margin: 0 auto; padding: 28px 20px 0; }
    /* ── Bandeau rapport ── */
    .rpt-banner {
      background: #1E3A8A; color: #fff;
      font-size: 13px; font-weight: 600;
      padding: 10px 20px; text-align: center;
      display: flex; align-items: center; justify-content: center; gap: 16px;
    }
    .rpt-banner a { color: #10B981; text-decoration: none; font-weight: 700; }
    .rpt-banner a:hover { text-decoration: underline; }
    /* ── Carte rapport ── */
    .rpt-card {
      background: #fff; border-radius: 16px;
      padding: 24px; margin-bottom: 20px;
      box-shadow: 0 2px 12px rgba(0,0,0,.06);
    }
    .rpt-card-top {
      display: flex; align-items: center; justify-content: space-between;
      padding-bottom: 16px; border-bottom: 2px solid #1E3A8A; margin-bottom: 16px;
      flex-wrap: wrap; gap: 12px;
    }
    .rpt-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .rpt-logo svg { width: 44px; height: 44px; flex-shrink: 0; }
    .rpt-brand-name { display: block; font-size: 20px; font-weight: 800; color: #1E3A8A; line-height: 1.1; }
    .rpt-brand-tag  { display: block; font-size: 10px; color: #6B7280; margin-top: 2px; }
    .rpt-date { text-align: right; font-size: 12px; color: #6B7280; }
    .rpt-date strong { display: block; font-size: 14px; color: #111827; font-weight: 700; margin-bottom: 2px; }
    .btn-print { padding: 10px 20px; background: #1E3A8A; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; margin-top: 14px; }
    .btn-print:hover { background: #1e40af; }
    .rpt-info {
      background: #f0fdf4; border: 1px solid #a7f3d0;
      border-radius: 10px; padding: 16px 20px; text-align: center;
    }
    .rpt-title { font-size: 18px; font-weight: 800; color: #111827; margin: 0 0 6px; }
    .rpt-meta  { font-size: 13px; color: #4B5563; margin: 0; line-height: 1.8; }
    /* ── Estimation ── */
    .est-box { background: #fff; border: 1px solid #a7f3d0; border-radius: 14px; padding: 18px 22px; margin-bottom: 18px; box-shadow: 0 2px 8px rgba(16,185,129,.08); }
    .est-box-hd { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .est-title { font-size: 13px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: .05em; }
    .est-cols { display: flex; gap: 10px; }
    .est-col { flex: 1; text-align: center; padding: 14px 10px; border-radius: 12px; }
    .est-col.low  { background: #eff6ff; }
    .est-col.mid  { background: #1E3A8A; color: #fff; }
    .est-col.high { background: #eff6ff; }
    .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; opacity: .6; margin-bottom: 6px; }
    .est-col.mid .lbl { opacity: .75; color: #bfdbfe; }
    .val     { font-size: 22px; font-weight: 800; line-height: 1; }
    .val-m2  { font-size: 12px; font-weight: 600; opacity: .65; margin-top: 4px; }
    /* ── Table ── */
    .tbl-wrap { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.06); margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #f9fafb; }
    th { padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #374151; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
    th.num, td.num { text-align: right; }
    th.ctr, td.ctr { text-align: center; }
    td { padding: 9px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f9fafb; }
    /* ── Footer site ── */
    .site-footer { margin-top: 0; }
    /* ── Print ── */
    @media print {
      .rpt-banner, .site-footer, .btn-print { display: none !important; }
      body { background: #fff; }
      .wrap { padding: 0; max-width: 100%; }
      .rpt-card, .est-box, .tbl-wrap { box-shadow: none !important; border-radius: 4px !important; }
      .val { font-size: 16px !important; }
      td, th { font-size: 10px !important; padding: 4px 6px !important; }
    }
    @media (max-width: 640px) {
      .est-cols { flex-direction: column; }
      .rpt-card-top { flex-direction: column; }
      .rpt-date { text-align: left; }
    }
  </style>
</head>
<body>

  <!-- Bandeau "rapport partageable" -->
  <div class="rpt-banner">
    Rapport Estimatiz — Partageable &emsp;·&emsp;
    <a href="{$siteUrl}estimation">Faire ma propre estimation →</a>
  </div>

  <!-- Nav du site -->
  <nav class="sitenav" role="navigation" aria-label="Navigation principale">
    <div class="sitenav-inner">
      <a class="sitenav-logo" href="{$siteUrl}" title="Estimatiz — Accueil">
        <svg class="sitenav-logo-icon" width="38" height="38" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
          <path d="M60 10 L20 46 V110 H100 V46 Z" fill="none" stroke="#1E3A8A" stroke-width="7" stroke-linejoin="round"/>
          <rect x="52" y="68" width="16" height="42" fill="#10B981" rx="3"/>
          <rect x="28" y="76" width="13" height="34" fill="#1E3A8A" rx="3"/>
          <rect x="79" y="82" width="11" height="28" fill="#10B981" rx="3"/>
          <path d="M60 42c-13 0-23 10-23 23 0 17 23 42 23 42s23-25 23-42c0-13-10-23-23-23z" fill="#1E3A8A"/>
          <circle cx="60" cy="65" r="7" fill="#fff"/>
        </svg>
        <div class="sitenav-logo-text">
          <span class="sitenav-logo-name">Estimatiz</span>
          <span class="sitenav-logo-tag">Précision&nbsp;•&nbsp;Transparence&nbsp;•&nbsp;Data</span>
        </div>
      </a>
      <ul class="sitenav-links" role="list">
        <li><a href="{$siteUrl}">Accueil</a></li>
        <li><a href="{$siteUrl}estimation" class="sitenav-cta">Estimer un bien</a></li>
        <li><a href="{$siteUrl}prix-m2">Prix au m²</a></li>
        <li><a href="{$siteUrl}ventes">Dernières ventes</a></li>
      </ul>
    </div>
  </nav>

  <div class="wrap">

    <!-- Carte en-tête rapport -->
    <div class="rpt-card">
      <div class="rpt-card-top">
        <a class="rpt-logo" href="{$siteUrl}">
          <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(10,5)">
              <path d="M50 8 L16 38 V92 H84 V38 Z" fill="none" stroke="#1E3A8A" stroke-width="7" stroke-linejoin="round"/>
              <rect x="43" y="62" width="14" height="30" fill="#10B981" rx="3"/>
              <rect x="23" y="68" width="11" height="24" fill="#1E3A8A" rx="3"/>
              <rect x="66" y="72" width="10" height="20" fill="#10B981" rx="3"/>
              <path d="M50 38c-10 0-18 8-18 18 0 14 18 34 18 34s18-20 18-34c0-10-8-18-18-18z" fill="#1E3A8A"/>
              <circle cx="50" cy="56" r="5" fill="#fff"/>
            </g>
          </svg>
          <div>
            <span class="rpt-brand-name">Estimatiz</span>
            <span class="rpt-brand-tag">Rapport d'estimation immobilière</span>
          </div>
        </a>
        <div class="rpt-date">
          <strong>Généré le {$today}</strong>
          Source : DVF officielle
        </div>
      </div>
      <div class="rpt-info">
        <p class="rpt-title">{$labelEsc}</p>
        <p class="rpt-meta">{$metaHtml}</p>
      </div>
      <div style="text-align:right">
        <button class="btn-print" onclick="window.print()">
          🖨 Imprimer / PDF
        </button>
      </div>
    </div>

    {$estHtml}

    <!-- Tableau des ventes -->
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Adresse</th>
            <th class="num">Valeur foncière</th>
            <th class="num">Surface</th>
            <th class="num">€/m²</th>
            <th class="ctr">Date</th>
            <th class="ctr">Pièces</th>
          </tr>
        </thead>
        <tbody>{$tbodyHtml}</tbody>
      </table>
    </div>

  </div><!-- /.wrap -->

  <!-- Footer du site -->
  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-col">
        <div class="footer-brand">Estimatiz</div>
        <p class="footer-tag">Estimation immobilière indépendante, fondée sur les ventes réelles publiées par l'État.</p>
      </div>
      <div class="footer-col">
        <h4>Outils</h4>
        <ul>
          <li><a href="{$siteUrl}estimation">Estimer un bien</a></li>
          <li><a href="{$siteUrl}prix-m2">Prix au m²</a></li>
          <li><a href="{$siteUrl}ventes">Dernières ventes</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Comprendre</h4>
        <ul>
          <li><a href="{$siteUrl}methodologie">Méthodologie</a></li>
          <li><a href="{$siteUrl}donnees">Données utilisées</a></li>
          <li><a href="{$siteUrl}faq">FAQ</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Estimatiz</h4>
        <ul>
          <li><a href="{$siteUrl}a-propos">À propos</a></li>
          <li><a href="{$siteUrl}contact">Contact</a></li>
          <li><a href="{$siteUrl}mentions-legales">Mentions légales</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; France 2014–2025 &nbsp;|&nbsp; Licence Ouverte Etalab</span>
      <span>© {$year_} Estimatiz</span>
    </div>
  </footer>

</body>
</html>
HTML;

file_put_contents($filepath, $html);

echo json_encode([
    'ok'       => true,
    'url'      => $rapportUrl,
    'filename' => $filename,
], JSON_UNESCAPED_UNICODE);
