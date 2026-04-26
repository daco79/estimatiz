<?php
/**
 * api/save-rapport-seo.php
 * Génère une page HTML statique SEO-optimisée dans rapports/automatique/{year}/.
 * Appelé par le programme de génération automatique (generate_results.py).
 * Ne pas utiliser pour les rapports manuels (voir api/save-rapport.php).
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

// ── Génère un slug et un nom de fichier ────────────────────────────────────
function seo_slugify(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str) ?: $str;
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
}

$cp     = preg_replace('/[^0-9]/', '', $suggestion['cp']     ?? '');
$voie   = seo_slugify($suggestion['voie']   ?? $label);
$numero = preg_replace('/[^0-9]/', '', $suggestion['numero'] ?? '');

if ($numero) {
    $base     = $numero . '-' . ($voie ?: 'adresse') . ($cp ? '-' . $cp : '');
    $base     = substr($base, 0, 60);
    $filename = $base . '.html';
} else {
    $base     = ($cp ? $cp . '-' : '') . ($voie ?: 'adresse');
    $base     = substr($base, 0, 60);
    $hash     = substr(md5($base . microtime(true) . rand()), 0, 6);
    $filename = $base . '-' . $hash . '.html';
}

// ── Dossier de destination : rapports/automatique/{year}/ ──────────────────
$year_      = (new DateTime())->format('Y');
$autoDir    = dirname(__DIR__) . '/rapports/automatique/' . $year_;
if (!is_dir($autoDir)) {
    mkdir($autoDir, 0755, true);
}
$filepath = $autoDir . '/' . $filename;

// ── Helpers formatage ──────────────────────────────────────────────────────
function seo_euro(float $n): string {
    return number_format($n, 0, ',', '&nbsp;') . '&nbsp;€';
}
function seo_date(string $d): string {
    try { return (new DateTime($d))->format('d/m/Y'); } catch (Exception $e) { return $d; }
}

// ── Dates ──────────────────────────────────────────────────────────────────
$today   = (new DateTime())->format('d/m/Y');
$dateIso = (new DateTime())->format('Y-m-d');

// ── Meta sous-titre (carte rapport) ────────────────────────────────────────
$metaParts = [];
if ($surface)    $metaParts[] = "Surface : <b>{$surface} m²</b>";
if ($pieces)     $metaParts[] = "Pièces : <b>{$pieces}</b>";
if ($surfaceMin || $surfaceMax) $metaParts[] = "Filtre surface : <b>" . ($surfaceMin ?: '…') . "–" . ($surfaceMax ?: '…') . " m²</b>";
$metaParts[] = count($rows) . " vente" . (count($rows) > 1 ? "s" : "") . " analysée" . (count($rows) > 1 ? "s" : "");
$metaHtml = implode('&emsp;·&emsp;', $metaParts);

// ── Bloc estimation ────────────────────────────────────────────────────────
$estHtml = '';
if ($est && isset($est['p50'])) {
    $confColor = $est['conf'] >= 75 ? '#047857' : ($est['conf'] >= 50 ? '#d97706' : '#b91c1c');
    $confLabel = $est['conf'] >= 75 ? 'Élevée'  : ($est['conf'] >= 50 ? 'Modérée'  : 'Faible');
    if ($surface) {
        $lowVal  = seo_euro($est['p20'] * $surface);
        $midVal  = seo_euro($est['p50'] * $surface);
        $highVal = seo_euro($est['p80'] * $surface);
        $lowM2   = seo_euro($est['p20']) . '/m²';
        $midM2   = seo_euro($est['p50']) . '/m²';
        $highM2  = seo_euro($est['p80']) . '/m²';
    } else {
        $lowVal  = seo_euro($est['p20']) . '/m²';
        $midVal  = seo_euro($est['p50']) . '/m²';
        $highVal = seo_euro($est['p80']) . '/m²';
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
               ? seo_euro((float)$r['valeur_fonciere']) : '—';
    $surf    = isset($r['surface'])  && $r['surface']  !== null ? htmlspecialchars($r['surface'], ENT_QUOTES, 'UTF-8') . ' m²' : '—';
    $pm2     = isset($r['prix_m2'])  && is_numeric($r['prix_m2']) ? seo_euro((float)$r['prix_m2']) . '/m²' : '—';
    $date    = isset($r['date_mutation']) && $r['date_mutation'] ? seo_date($r['date_mutation']) : '—';
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

// ── URL publique ───────────────────────────────────────────────────────────
$protocol   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'estimatiz.fr';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api/save-rapport-seo.php';
$basePath   = rtrim(dirname(dirname($scriptName)), '/');
$baseUrl    = $protocol . '://' . $host . $basePath;
$rapportUrl = $baseUrl . '/rapports/automatique/' . $year_ . '/' . $filename;
$siteUrl    = $baseUrl . '/';

// ── Données SEO ────────────────────────────────────────────────────────────
$labelEsc   = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
$communeRaw = $suggestion['commune'] ?? '';
$commune    = htmlspecialchars($communeRaw, ENT_QUOTES, 'UTF-8');
$cpEsc      = htmlspecialchars($cp, ENT_QUOTES, 'UTF-8');
$nTrans     = count($rows);
$pluralS    = $nTrans > 1 ? 's' : '';

// --- meta description ---
$mdParts = ["Estimation immobilière gratuite en ligne pour {$label}."];
if ($est && isset($est['p50'])) {
    $mdParts[] = 'Prix médian estimé : ' . number_format((float)$est['p50'], 0, ',', ' ') . ' €/m².';
    if ($surface) {
        $mdParts[] = 'Valeur estimée : ' . number_format($est['p50'] * $surface, 0, ',', ' ') . ' €.';
    }
}
$mdParts[]    = $nTrans . ' vente' . $pluralS . ' DVF analysée' . $pluralS . '.';
$metaDescText = implode(' ', $mdParts);
$metaDescEsc  = htmlspecialchars($metaDescText, ENT_QUOTES, 'UTF-8');

// --- stats agrégées pour section marché ---
$allPm2  = array_values(array_filter(array_column($rows, 'prix_m2'), 'is_numeric'));
$avgPm2  = $allPm2 ? (float)(array_sum($allPm2) / count($allPm2)) : null;
$dates   = array_values(array_filter(array_column($rows, 'date_mutation')));
sort($dates);
$dateMin = $dates ? seo_date(reset($dates)) : null;
$dateMax = $dates ? seo_date(end($dates))   : null;

// --- HTML section marché ---
$marcheSectionHtml = '';
if ($communeRaw) {
    $loc  = "<strong>{$commune}</strong>" . ($cp ? "&nbsp;({$cpEsc})" : '');
    $body = '';
    if ($avgPm2) {
        $avgFmt = number_format((int)round($avgPm2), 0, ',', '&nbsp;');
        $body  .= "<p>Le prix moyen au m² à {$loc} ressort à <strong>{$avgFmt}&nbsp;€/m²</strong> sur la base des {$nTrans}&nbsp;vente{$pluralS} analysée{$pluralS} dans ce rapport";
        if ($dateMin && $dateMax && $dateMin !== $dateMax) {
            $body .= " (transactions du {$dateMin} au {$dateMax})";
        } elseif ($dateMax) {
            $body .= " (transaction du {$dateMax})";
        }
        $body .= ".</p>";
    }
    if ($est && isset($est['p50'])) {
        $p20f  = number_format((int)round((float)$est['p20']), 0, ',', '&nbsp;');
        $p50f  = number_format((int)round((float)$est['p50']), 0, ',', '&nbsp;');
        $p80f  = number_format((int)round((float)$est['p80']), 0, ',', '&nbsp;');
        $body .= "<p>L'analyse statistique positionne le <em>prix de marché</em> entre <strong>{$p20f}&nbsp;€/m²</strong> (P20) et <strong>{$p80f}&nbsp;€/m²</strong> (P80), avec une valeur médiane de <strong>{$p50f}&nbsp;€/m²</strong>. Cette fourchette reflète les <em>transactions immobilières récentes</em> dans le secteur.</p>";
    }
    $body .= "<p>Source&nbsp;: <strong>DVF</strong> (Demandes de Valeurs Foncières), publiée en open data par la DGFiP — données officielles de l'ensemble des mutations immobilières en France depuis 2014.</p>";
    $marcheSectionHtml = "<section class=\"rpt-section rpt-market\">\n      <h2 class=\"rpt-section-title\">Prix du marché immobilier à {$commune}</h2>\n      {$body}\n    </section>";
}

// --- JSON-LD ---
$jld = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil',                'item' => $siteUrl],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Estimation immobilière', 'item' => $siteUrl . 'estimation'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $label,                   'item' => $rapportUrl],
            ],
        ],
        [
            '@type'         => 'Article',
            'headline'      => "Estimation immobilière — {$label}",
            'description'   => $metaDescText,
            'url'           => $rapportUrl,
            'datePublished' => $dateIso,
            'dateModified'  => $dateIso,
            'author'        => ['@type' => 'Organization', 'name' => 'Estimatiz', 'url' => $siteUrl],
            'publisher'     => ['@type' => 'Organization', 'name' => 'Estimatiz', 'url' => $siteUrl],
            'about'         => ['@type' => 'Place', 'name' => $label, 'addressLocality' => $communeRaw, 'postalCode' => $cp, 'addressCountry' => 'FR'],
        ],
        [
            '@type'      => 'FAQPage',
            'mainEntity' => [
                [
                    '@type'          => 'Question',
                    'name'           => 'Comment est calculée cette estimation immobilière ?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => "L'estimation immobilière est calculée à partir des transactions réelles de la base DVF (Demandes de Valeurs Foncières) publiée par l'État. Nous analysons les ventes comparables dans le secteur pour établir une fourchette de prix (P20 — médiane — P80) représentative du marché immobilier local."],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => "D'où viennent les données de transactions immobilières ?",
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => "Les données proviennent de la base DVF (Demandes de Valeurs Foncières) publiée en open data par la DGFiP. Elles recensent toutes les mutations immobilières enregistrées en France depuis 2014."],
                ],
                [
                    '@type'          => 'Question',
                    'name'           => 'Comment obtenir une estimation personnalisée de mon bien immobilier ?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => "Rendez-vous sur Estimatiz.fr pour obtenir gratuitement une estimation en ligne du prix de votre bien immobilier. Saisissez l'adresse, renseignez la surface et le nombre de pièces, et obtenez instantanément une estimation basée sur les ventes DVF réelles de votre secteur."],
                ],
            ],
        ],
    ],
];
$jsonLdJson = json_encode($jld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Estimation immobilière — {$labelEsc} | Prix au m² et ventes récentes | Estimatiz</title>
  <meta name="description" content="{$metaDescEsc}"/>
  <meta name="robots" content="index, follow"/>
  <link rel="canonical" href="{$rapportUrl}"/>
  <meta property="og:title"       content="Estimation immobilière — {$labelEsc} | Estimatiz"/>
  <meta property="og:description" content="{$metaDescEsc}"/>
  <meta property="og:type"        content="article"/>
  <meta property="og:url"         content="{$rapportUrl}"/>
  <meta property="og:site_name"   content="Estimatiz"/>
  <meta name="twitter:card"        content="summary"/>
  <meta name="twitter:title"       content="Estimation immobilière — {$labelEsc} | Estimatiz"/>
  <meta name="twitter:description" content="{$metaDescEsc}"/>
  <script type="application/ld+json">{$jsonLdJson}</script>
  <link rel="icon" type="image/x-icon" href="{$basePath}/favicon.ico"/>
  <link rel="stylesheet" href="{$basePath}/assets/css/site.css"/>
  <style>
    /* ── Base ── */
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111827;background:#F3F4F6}
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
    /* ── En-tête H1 ── */
    .rpt-header { margin-bottom: 20px; }
    .rpt-h1 { font-size: 24px; font-weight: 800; color: #111827; margin: 0 0 10px; line-height: 1.25; }
    .rpt-intro { font-size: 14px; color: #4B5563; margin: 0; line-height: 1.75; }
    /* ── Sections ── */
    .rpt-section { margin-bottom: 24px; }
    .rpt-section-title { font-size: 18px; font-weight: 800; color: #1E3A8A; margin: 0 0 12px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
    .rpt-section-intro { font-size: 13px; color: #4B5563; margin: 0 0 14px; line-height: 1.7; }
    /* ── Marché & FAQ ── */
    .rpt-market, .rpt-faq { background: #fff; border-radius: 14px; padding: 20px 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
    .rpt-market p, .rpt-faq p { font-size: 13px; color: #374151; line-height: 1.75; margin: 0 0 12px; }
    .rpt-market p:last-child, .rpt-faq p:last-child { margin-bottom: 0; }
    .faq-item { padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
    .faq-item:last-child { border-bottom: none; padding-bottom: 0; }
    .faq-q { font-size: 14px; font-weight: 700; color: #111827; margin: 0 0 5px; }
    .faq-a { font-size: 13px; color: #4B5563; margin: 0; line-height: 1.7; }
    /* ── CTA ── */
    .rpt-cta { background: #1E3A8A; border-radius: 16px; padding: 28px; text-align: center; margin-bottom: 24px; }
    .rpt-cta h2 { color: #fff; font-size: 18px; font-weight: 800; margin: 0 0 10px; }
    .rpt-cta p { color: #bfdbfe; font-size: 14px; margin: 0 0 18px; line-height: 1.6; }
    .btn-cta { display: inline-block; background: #10B981; color: #fff; font-weight: 700; font-size: 15px; padding: 12px 28px; border-radius: 10px; text-decoration: none; }
    .btn-cta:hover { background: #059669; }
    .est-note { font-size: 12px; color: #6B7280; margin: 6px 0 0; font-style: italic; }
    /* ── Bandeau ── */
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
    .rpt-info {
      background: #f0fdf4; border: 1px solid #a7f3d0;
      border-radius: 10px; padding: 16px 20px; text-align: center;
    }
    .rpt-title { font-size: 18px; font-weight: 800; color: #111827; margin: 0 0 6px; }
    .rpt-meta  { font-size: 13px; color: #4B5563; margin: 0; line-height: 1.8; }
    /* ── Estimation ── */
    .est-box { background: #fff; border: 1px solid #a7f3d0; border-radius: 14px; padding: 18px 22px; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(16,185,129,.08); }
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
    .tbl-wrap { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.06); margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #f9fafb; }
    th { padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #374151; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
    th.num, td.num { text-align: right; }
    th.ctr, td.ctr { text-align: center; }
    td { padding: 9px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f9fafb; }
    /* ── Footer ── */
    .site-footer { margin-top: 0; }
    /* ── Print ── */
    @media print {
      .rpt-banner, .site-footer, .rpt-cta, .rpt-faq, .rpt-market, .rpt-header { display: none !important; }
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
      .rpt-h1 { font-size: 19px; }
    }
  </style>
</head>
<body>

  <div class="rpt-banner">
    Rapport d'estimation immobilière &emsp;·&emsp;
    <a href="{$siteUrl}estimation">Obtenir gratuitement mon estimation en ligne →</a>
  </div>

  <nav class="sitenav" role="navigation" aria-label="Navigation principale">
    <div class="sitenav-inner">
      <a class="sitenav-logo" href="{$siteUrl}" title="Estimatiz — Estimation immobilière gratuite en ligne">
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

  <main>
  <div class="wrap">

    <div class="rpt-header">
      <h1 class="rpt-h1">Estimation immobilière — {$labelEsc}</h1>
      <p class="rpt-intro">
        Retrouvez ci-dessous l'<strong>estimation immobilière gratuite en ligne</strong> de
        <strong>{$labelEsc}</strong>, calculée à partir de <strong>{$nTrans}&nbsp;transaction{$pluralS}
        immobilière{$pluralS}</strong> enregistrée{$pluralS} dans la base <strong>DVF officielle</strong>.
        Ce rapport présente le <em>prix au m² estimé</em>, la fourchette de valorisation ainsi que
        les <em>ventes récentes</em> à proximité de ce bien.
      </p>
    </div>

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
    </div>

    <section class="rpt-section">
      <h2 class="rpt-section-title">Résultat de l'estimation immobilière</h2>
      <p class="rpt-section-intro">
        L'<strong>estimation immobilière</strong> est établie par analyse statistique des
        <em>transactions immobilières récentes</em> les plus comparables dans le secteur.
        Les valeurs P20, médiane (P50) et P80 délimitent la fourchette de prix du marché.
      </p>
      {$estHtml}
      <p class="est-note">P20 = 20&nbsp;% des ventes comparables sont inférieures à cette valeur&nbsp;; P80 = 20&nbsp;% lui sont supérieures&nbsp;; médiane = valeur centrale du marché.</p>
    </section>

    <section class="rpt-section">
      <h2 class="rpt-section-title">Transactions immobilières récentes — {$commune}</h2>
      <p class="rpt-section-intro">
        Le tableau ci-dessous liste les <strong>ventes immobilières</strong> retenues pour cette analyse.
        Ces données sont issues de la base <strong>DVF</strong> (Demandes de Valeurs Foncières),
        source officielle de l'État français recensant l'ensemble des <em>mutations immobilières</em>.
      </p>
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
    </section>

    {$marcheSectionHtml}

    <section class="rpt-section rpt-faq">
      <h2 class="rpt-section-title">Questions fréquentes sur l'estimation immobilière</h2>
      <div class="faq-item">
        <p class="faq-q">Comment est calculée cette estimation immobilière ?</p>
        <p class="faq-a">L'<strong>estimation immobilière</strong> est calculée à partir des transactions réelles de la base <strong>DVF</strong> publiée par l'État. Nous analysons les ventes comparables dans le secteur pour établir une fourchette de prix (P20 — médiane — P80) représentative du marché immobilier local.</p>
      </div>
      <div class="faq-item">
        <p class="faq-q">D'où viennent les données de transactions immobilières ?</p>
        <p class="faq-a">Les données proviennent de la base <strong>DVF</strong> publiée en open data par la DGFiP. Elles recensent toutes les <em>mutations immobilières</em> enregistrées en France depuis 2014, soit plusieurs millions de <em>transactions immobilières</em>.</p>
      </div>
      <div class="faq-item">
        <p class="faq-q">Comment obtenir une estimation personnalisée de mon bien immobilier ?</p>
        <p class="faq-a">Rendez-vous sur <a href="{$siteUrl}estimation">Estimatiz.fr</a> pour <strong>obtenir gratuitement une estimation en ligne du prix de votre bien immobilier</strong>. Saisissez l'adresse, renseignez la surface et le nombre de pièces, et obtenez instantanément une estimation basée sur les ventes DVF réelles de votre secteur.</p>
      </div>
    </section>

    <section class="rpt-cta">
      <h2>Obtenez gratuitement une estimation en ligne de votre bien immobilier</h2>
      <p>Estimatiz calcule la valeur de votre bien à partir des ventes réelles enregistrées par l'État.<br>
        Estimation immobilière gratuite, indépendante et fondée sur les données DVF officielles.</p>
      <a href="{$siteUrl}estimation" class="btn-cta">Estimer mon bien gratuitement →</a>
    </section>

  </div>
  </main>

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
    'path'     => 'rapports/automatique/' . $year_ . '/' . $filename,
], JSON_UNESCAPED_UNICODE);
