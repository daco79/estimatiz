<?php
$navActive = 'prix';
$depts = [
    '01'=>'Ain','02'=>'Aisne','03'=>'Allier','04'=>'Alpes-de-Haute-Provence',
    '05'=>'Hautes-Alpes','06'=>'Alpes-Maritimes','07'=>'Ardèche','08'=>'Ardennes',
    '09'=>'Ariège','10'=>'Aube','11'=>'Aude','12'=>'Aveyron','13'=>'Bouches-du-Rhône',
    '14'=>'Calvados','15'=>'Cantal','16'=>'Charente','17'=>'Charente-Maritime',
    '18'=>'Cher','19'=>'Corrèze','2A'=>'Corse-du-Sud','2B'=>'Haute-Corse',
    '21'=>'Côte-d\'Or','22'=>'Côtes-d\'Armor','23'=>'Creuse','24'=>'Dordogne',
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
    '93'=>'Seine-Saint-Denis','94'=>'Val-de-Marne','95'=>'Val-d\'Oise',
    '971'=>'Guadeloupe','972'=>'Martinique','973'=>'Guyane','974'=>'La Réunion',
    '976'=>'Mayotte',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <!-- SEO meta tags -->
  <meta name="description" content="Consultez l’évolution des prix au m² dans les grandes villes et départements de France grâce aux ventes officielles DVF 2014–2025. Filtrez par ville, département, type de bien et période."/>
  <link rel="canonical" href="https://www.estimatiz.fr/prix-m2.php"/>
  <meta property="og:locale" content="fr_FR"/>
  <meta property="og:type" content="website"/>
  <meta property="og:title" content="Prix au m² – Estimatiz"/>
  <meta property="og:description" content="Consultez l’évolution des prix au m² dans les grandes villes et départements de France grâce aux ventes officielles DVF 2014–2025. Filtrez par ville, département, type de bien et période."/>
  <meta property="og:url" content="https://www.estimatiz.fr/prix-m2.php"/>
  <title>Prix au m² – Estimatiz</title>
  <link rel="stylesheet" href="assets/css/site.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    :root{ --c1:#1E3A8A; --c2:#10B981; --c4:#F3F4F6; }
    *{ box-sizing:border-box; }
    body{ margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; background:var(--c4); color:#111827; }

    /* ── Hero ── */
    .hero{ background:linear-gradient(135deg,#1E3A8A 0%,#1e40af 60%,#1d4ed8 100%); color:#fff; padding:44px 24px 36px; text-align:center; }
    .hero h1{ font-size:28px; font-weight:800; margin:0 0 8px; }
    .hero p{ font-size:15px; color:rgba(255,255,255,.82); max-width:520px; margin:0 auto; line-height:1.6; }

    /* ── Graphique évolution ── */
    .chart-section{ background:#fff; border-bottom:1px solid #e5e7eb; padding:28px 24px 20px; }
    .chart-header{ display:flex; align-items:baseline; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
    .chart-title{ font-size:16px; font-weight:800; color:#111827; margin:0; }
    .chart-sub{ font-size:12px; color:#6B7280; margin:0; }
    .chart-container{ position:relative; height:220px; }
    .chart-loading{ display:flex; align-items:center; justify-content:center; height:220px; color:#6B7280; font-size:13px; }

    /* ── Filtres ── */
    .filters-bar{ background:#fff; border-bottom:1px solid #e5e7eb; padding:14px 24px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filters-bar label{ font-size:13px; font-weight:600; color:#374151; white-space:nowrap; }
    .filters-bar select{ padding:7px 10px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#111827; font-family:inherit; }
    .filters-bar .filter-group{ display:flex; align-items:center; gap:6px; }
    .filters-bar .sep{ color:#d1d5db; }
    .btn-filter{ padding:8px 16px; font-size:13px; font-weight:700; border:none; border-radius:8px; background:var(--c1); color:#fff; cursor:pointer; }
    .btn-filter:hover{ background:#1e40af; }

    /* ── Mode toggle ── */
    .mode-toggle{ display:flex; border:1px solid #d1d5db; border-radius:9px; overflow:hidden; }
    .mode-btn{ padding:6px 14px; font-size:13px; font-weight:600; border:none; background:#fff; color:#6B7280; cursor:pointer; transition:background .15s,color .15s; white-space:nowrap; }
    .mode-btn:not(:last-child){ border-right:1px solid #d1d5db; }
    .mode-btn.active{ background:var(--c1); color:#fff; }
    .mode-btn:not(.active):hover{ background:#f3f4f6; color:#374151; }

    /* ── Dep select (plus large) ── */
    #fDep{ min-width:200px; }

    /* ── Wrap ── */
    .wrap{ max-width:1000px; margin:0 auto; padding:32px 20px 60px; }

    /* ── Status ── */
    .status-bar{ padding:14px 18px; border-radius:12px; font-size:14px; margin-bottom:20px; display:none; }
    .status-bar.visible{ display:block; }
    .status-bar.loading{ background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .status-bar.error  { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .status-bar.info   { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
    .spin{ display:inline-block; width:13px; height:13px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
    @keyframes spin{ to{ transform:rotate(360deg); } }

    /* ── Résumé stats ── */
    .summary{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
    .sum-card{ background:#fff; border-radius:12px; padding:16px; border:1px solid #e5e7eb; text-align:center; }
    .sum-val{ font-size:22px; font-weight:800; color:var(--c1); }
    .sum-lbl{ font-size:11px; color:#6B7280; margin-top:3px; }

    /* ── Tableau ── */
    .tbl-wrap{ background:#fff; border-radius:16px; border:1px solid #e5e7eb; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    .tbl-wrap table{ width:100%; border-collapse:collapse; }
    thead th{
      padding:12px 14px; font-size:12px; font-weight:700; color:#6B7280;
      text-transform:uppercase; letter-spacing:.05em; text-align:left;
      background:#f9fafb; border-bottom:1px solid #e5e7eb; cursor:pointer; user-select:none; white-space:nowrap;
    }
    thead th:hover{ background:#f3f4f6; color:#374151; }
    thead th.sorted{ color:var(--c1); }
    thead th .sort-icon{ margin-left:4px; opacity:.5; }
    thead th.sorted .sort-icon{ opacity:1; }
    tbody tr{ border-bottom:1px solid #f3f4f6; cursor:pointer; transition:background .1s; }
    tbody tr:last-child{ border-bottom:none; }
    tbody tr:hover{ background:#f9fafb; }
    tbody tr.expanded{ background:#eff6ff; }
    tbody td{ padding:13px 14px; font-size:14px; color:#111827; }
    .arr-badge{ display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; background:var(--c1); color:#fff; border-radius:10px; font-size:13px; font-weight:800; }
    .arr-name{ font-size:13px; color:#6B7280; margin-top:1px; }
    .prix-med{ font-size:17px; font-weight:800; color:#111827; }

    /* Barre fourchette */
    .range-bar{ display:flex; align-items:center; gap:6px; }
    .range-val{ font-size:12px; color:#6B7280; white-space:nowrap; }
    .range-track{ flex:1; height:6px; background:#e5e7eb; border-radius:3px; min-width:60px; position:relative; overflow:hidden; }
    .range-fill{ position:absolute; top:0; height:100%; background:var(--c2); border-radius:3px; }

    .count-pill{ display:inline-block; background:#f3f4f6; color:#374151; font-size:12px; font-weight:600; padding:3px 8px; border-radius:20px; }
    .btn-rues{ display:inline-flex; align-items:center; gap:5px; padding:5px 11px; font-size:12px; font-weight:700; color:var(--c1); background:#eff6ff; border:1px solid #bfdbfe; border-radius:20px; white-space:nowrap; transition:background .15s,color .15s; }
    tr:hover .btn-rues{ background:var(--c1); color:#fff; border-color:var(--c1); }
    tr.expanded .btn-rues{ background:var(--c1); color:#fff; border-color:var(--c1); }
    .btn-rues .arr{ transition:transform .2s; display:inline-block; }
    tr.expanded .btn-rues .arr{ transform:rotate(180deg); }
    .tbl-hint{ font-size:12px; color:#6B7280; margin-bottom:10px; }

    /* ── Drill rues ── */
    .drill-row td{ padding:0 !important; background:#f0f7ff; border-bottom:1px solid #bfdbfe !important; }
    .drill-inner{ padding:16px 20px 20px; }
    .drill-title{ font-size:13px; font-weight:700; color:var(--c1); margin:0 0 8px; }
    .drill-search{ display:block; padding:6px 10px; font-size:13px; border:1px solid #bfdbfe; border-radius:8px; background:#fff; width:100%; max-width:280px; font-family:inherit; color:#111827; margin-bottom:8px; }
    .drill-search:focus{ outline:none; border-color:var(--c1); }
    .drill-scroll{ max-height:280px; overflow-y:auto; border:1px solid #dbeafe; border-radius:10px; background:#fff; }
    .drill-table{ width:100%; border-collapse:collapse; }
    .drill-table thead{ position:sticky; top:0; z-index:1; }
    .drill-table th{ font-size:11px; font-weight:700; color:#6B7280; text-transform:uppercase; padding:8px 12px; text-align:left; border-bottom:1px solid #dbeafe; background:#f0f7ff; cursor:pointer; user-select:none; white-space:nowrap; }
    .drill-table th:hover{ color:var(--c1); }
    .drill-table th.dsorted{ color:var(--c1); }
    .drill-table td{ font-size:13px; padding:8px 12px; border-bottom:1px solid #e8f0fe; color:#111827; }
    .drill-table tbody tr:last-child td{ border-bottom:none; }
    .drill-table tbody tr:hover{ background:#e8f0fe; }
    .drill-count{ font-size:12px; color:#6B7280; font-weight:400; margin-left:6px; }
    .drill-loading{ color:#6B7280; font-size:13px; padding:12px 0; }

    /* ── Mobile ── */
    @media(max-width:700px){
      .summary{ grid-template-columns:repeat(2,1fr); }
      .filters-bar{ padding:12px 14px; }
      .hide-mob{ display:none; }
      .wrap{ padding:20px 12px 48px; }
      .hero{ padding:32px 16px 24px; }
      .hero h1{ font-size:22px; }
      #fDep{ min-width:140px; }
    }

    /* ── Footer ── */
    footer{ background:#111827; color:rgba(255,255,255,.6); text-align:center; padding:24px; font-size:13px; }
    footer a{ color:rgba(255,255,255,.8); text-decoration:none; }
  </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <div class="hero">
    <h1>Prix au m² en France</h1>
    <p>Statistiques fondées sur les ventes officielles DVF 2014–2025. Explorez par grande ville ou par département.</p>
  </div>

  <!-- Graphique évolution -->
  <div class="chart-section">
    <div class="chart-header">
      <div>
        <p class="chart-title" id="chartTitle">Évolution du prix au m²</p>
        <p class="chart-sub">Médiane annuelle · zone ombrée = fourchette P20–P80</p>
      </div>
    </div>
    <div class="chart-container">
      <div class="chart-loading" id="chartLoading"><span class="spin"></span>&nbsp; Chargement…</div>
      <canvas id="evoChart" style="display:none;"></canvas>
    </div>
  </div>

  <!-- Filtres -->
  <div class="filters-bar">
    <div class="filter-group">
      <div class="mode-toggle" id="modeToggle">
        <button class="mode-btn active" data-mode="grandes-villes">Grandes villes</button>
        <button class="mode-btn" data-mode="france">France entière</button>
      </div>
    </div>
    <div class="filter-group" id="fgVille">
      <label for="fVille">Ville</label>
      <select id="fVille">
        <option value="paris">Paris</option>
        <option value="lyon">Lyon</option>
        <option value="marseille">Marseille</option>
      </select>
    </div>
    <div class="filter-group" id="fgDep" style="display:none;">
      <label for="fDep">Département</label>
      <select id="fDep">
        <option value="">— Choisir —</option>
        <?php foreach ($depts as $code => $label): ?>
        <option value="<?= $code ?>"><?= $code ?> – <?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label for="fType">Type</label>
      <select id="fType">
        <option value="Appartement">Appartement</option>
        <option value="Maison">Maison</option>
        <option value="">Tous</option>
      </select>
    </div>
    <div class="filter-group">
      <label for="fPieces">Pièces</label>
      <select id="fPieces">
        <option value="">Toutes</option>
        <option value="1">1 p.</option>
        <option value="2">2 p.</option>
        <option value="3">3 p.</option>
        <option value="4">4 p.</option>
        <option value="5">5 p.</option>
      </select>
    </div>
    <div class="filter-group">
      <label>Période</label>
      <select id="fAnneeMin">
        <?php for($y=2014;$y<=2025;$y++) echo "<option value='$y'" . ($y===2015?' selected':'') . ">$y</option>"; ?>
      </select>
      <span class="sep">→</span>
      <select id="fAnneeMax">
        <?php for($y=2014;$y<=2025;$y++) echo "<option value='$y'" . ($y===2025?' selected':'') . ">$y</option>"; ?>
      </select>
    </div>
    <button class="btn-filter" id="btnLoad">Actualiser</button>
  </div>

  <div class="wrap">
    <div class="status-bar loading visible" id="statusBar"><span class="spin"></span> Chargement…</div>

    <div class="summary" id="summaryBox" style="display:none;">
      <div class="sum-card"><div class="sum-val" id="sumMedian">—</div><div class="sum-lbl" id="sumMedianLbl">Prix médian</div></div>
      <div class="sum-card"><div class="sum-val" id="sumMin">—</div><div class="sum-lbl" id="sumMinLbl">Le moins cher</div></div>
      <div class="sum-card"><div class="sum-val" id="sumMax">—</div><div class="sum-lbl" id="sumMaxLbl">Le plus cher</div></div>
      <div class="sum-card"><div class="sum-val" id="sumCount">—</div><div class="sum-lbl">Ventes analysées</div></div>
    </div>

    <p class="tbl-hint" id="tblHint" style="display:none;">Cliquez sur une ligne pour voir le détail par rue.</p>
    <div class="tbl-wrap" id="tblWrap" style="display:none;">
      <table>
        <thead id="tblHead"></thead>
        <tbody id="tblBody"></tbody>
      </table>
    </div>
  </div>

  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; France 2014–2025
  </footer>

<script src="assets/js/utils.js"></script>
<script>
const fmt  = v => v == null ? '—' : new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' €';
const fmtK = v => v == null ? '—' : new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' €/m²';
const numFr = n => new Intl.NumberFormat('fr-FR').format(n);
const arrName = n => n === 1 ? '1er' : n + 'e';
const VILLE_LABELS = { paris: 'Paris', lyon: 'Lyon', marseille: 'Marseille' };

let currentMode = 'grandes-villes';
let allData     = [];
let sortCol     = 'median';
let sortAsc     = false;
let expandedKey = null;

// ── Mode toggle ───────────────────────────────────────────────────────────────

document.getElementById('modeToggle').addEventListener('click', e => {
  const btn = e.target.closest('.mode-btn');
  if (!btn) return;
  const mode = btn.dataset.mode;
  if (mode === currentMode) return;

  currentMode = mode;
  document.querySelectorAll('.mode-btn').forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
  document.getElementById('fgVille').style.display = mode === 'grandes-villes' ? '' : 'none';
  document.getElementById('fgDep').style.display   = mode === 'france'         ? '' : 'none';

  allData = [];
  expandedKey = null;
  document.getElementById('summaryBox').style.display = 'none';
  document.getElementById('tblWrap').style.display    = 'none';

  if (mode === 'france' && !document.getElementById('fDep').value) {
    showStatus('Sélectionnez un département puis cliquez sur Actualiser.', 'info');
    document.getElementById('chartLoading').innerHTML = 'Sélectionnez un département.';
    document.getElementById('evoChart').style.display = 'none';
  } else {
    loadChart();
    load();
  }
});

// ── Paramètres communs ────────────────────────────────────────────────────────

function getCommonParams() {
  const type     = document.getElementById('fType').value;
  const pieces   = document.getElementById('fPieces').value;
  const anneeMin = document.getElementById('fAnneeMin').value;
  const anneeMax = document.getElementById('fAnneeMax').value;
  const p = new URLSearchParams({ annee_min: anneeMin, annee_max: anneeMax });
  if (type)   p.set('type_local', type);
  if (pieces) p.set('pieces', pieces);
  return p;
}

// ── Chargement principal ──────────────────────────────────────────────────────

async function load() {
  showStatus('Chargement…', 'loading');
  document.getElementById('summaryBox').style.display = 'none';
  document.getElementById('tblHint').style.display    = 'none';
  document.getElementById('tblWrap').style.display    = 'none';
  expandedKey = null;

  if (currentMode === 'grandes-villes') {
    await loadArrondissements();
  } else {
    const dep = document.getElementById('fDep').value;
    if (!dep) { showStatus('Sélectionnez un département.', 'error'); return; }
    await loadVilles(dep);
  }
}

// ── Mode Grandes villes → arrondissements ─────────────────────────────────────

async function loadArrondissements() {
  const ville  = document.getElementById('fVille').value;
  const params = getCommonParams();
  params.set('ville', ville);

  try {
    const r = await fetch('api/prix-m2.php?' + params, { headers: { Accept: 'application/json' } });
    const d = await r.json();
    if (!d.ok || !d.data?.length) { showStatus('Aucune donnée pour ces critères.', 'error'); return; }
    allData = d.data;
    hideStatus();
    renderSummary('arrondissements');
    renderTableHead('arrondissements');
    renderTable('arrondissements');
  } catch(e) {
    showStatus('Erreur réseau.', 'error');
  }
}

// ── Mode France → villes d'un département ────────────────────────────────────

async function loadVilles(dep) {
  const params = getCommonParams();
  params.set('mode', 'villes');
  params.set('dep', dep);

  try {
    const r = await fetch('api/prix-m2.php?' + params, { headers: { Accept: 'application/json' } });
    const d = await r.json();
    if (!d.ok || !d.villes?.length) { showStatus('Aucune donnée pour ce département.', 'error'); return; }
    allData = d.villes;
    hideStatus();
    renderSummary('villes');
    renderTableHead('villes');
    renderTable('villes');
  } catch(e) {
    showStatus('Erreur réseau.', 'error');
  }
}

// ── Résumé ────────────────────────────────────────────────────────────────────

function renderSummary(tableMode) {
  const medians    = allData.map(d => d.median).filter(Boolean);
  const globalMed  = medians.length ? Math.round(medians.reduce((a,b)=>a+b,0) / medians.length) : null;
  const sorted     = [...allData].sort((a,b) => (a.median ?? 0) - (b.median ?? 0));
  const totalCount = allData.reduce((s,d) => s + (d.count || 0), 0);

  document.getElementById('sumMedian').textContent = fmtK(globalMed);
  document.getElementById('sumCount').textContent  = numFr(totalCount);
  document.getElementById('sumMin').textContent    = sorted[0]     ? fmtK(sorted[0].median)     : '—';
  document.getElementById('sumMax').textContent    = sorted.at(-1) ? fmtK(sorted.at(-1).median) : '—';

  if (tableMode === 'arrondissements') {
    const lbl = VILLE_LABELS[document.getElementById('fVille').value] || 'ville';
    document.getElementById('sumMedianLbl').textContent = `Prix médian ${lbl}`;
    document.getElementById('sumMinLbl').textContent    = 'Arrondissement le - cher';
    document.getElementById('sumMaxLbl').textContent    = 'Arrondissement le + cher';
  } else {
    const sel = document.getElementById('fDep');
    const lbl = sel.options[sel.selectedIndex]?.text || 'département';
    document.getElementById('sumMedianLbl').textContent = `Prix médian ${lbl}`;
    document.getElementById('sumMinLbl').textContent    = 'Ville la moins chère';
    document.getElementById('sumMaxLbl').textContent    = 'Ville la plus chère';
  }

  document.getElementById('summaryBox').style.display = 'grid';
}

// ── En-tête tableau ───────────────────────────────────────────────────────────

function renderTableHead(tableMode) {
  const head = document.getElementById('tblHead');
  sortCol = 'median';
  sortAsc = false;

  if (tableMode === 'arrondissements') {
    head.innerHTML = `<tr>
      <th data-col="arr">Arrondissement <span class="sort-icon">↕</span></th>
      <th data-col="median" class="sorted">Prix médian /m² <span class="sort-icon">↓</span></th>
      <th data-col="p20" class="hide-mob">Fourchette P20–P80 <span class="sort-icon">↕</span></th>
      <th data-col="count">Ventes <span class="sort-icon">↕</span></th>
      <th></th>
    </tr>`;
  } else {
    head.innerHTML = `<tr>
      <th data-col="commune">Commune <span class="sort-icon">↕</span></th>
      <th data-col="median" class="sorted">Prix médian /m² <span class="sort-icon">↓</span></th>
      <th data-col="p20" class="hide-mob">Fourchette P20–P80 <span class="sort-icon">↕</span></th>
      <th data-col="count">Ventes <span class="sort-icon">↕</span></th>
      <th></th>
    </tr>`;
  }

  document.querySelectorAll('#tblHead th[data-col]').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (sortCol === col) sortAsc = !sortAsc;
      else { sortCol = col; sortAsc = col === 'arr' || col === 'commune'; }
      expandedKey = null;
      renderTable(tableMode);
    });
  });
}

// ── Tableau ───────────────────────────────────────────────────────────────────

function renderTable(tableMode) {
  const isArr = tableMode === 'arrondissements';

  const sorted = [...allData].sort((a, b) => {
    const va = a[sortCol] ?? (sortCol === 'commune' ? '' : 0);
    const vb = b[sortCol] ?? (sortCol === 'commune' ? '' : 0);
    if (typeof va === 'string') return sortAsc ? va.localeCompare(vb, 'fr') : vb.localeCompare(va, 'fr');
    return sortAsc ? va - vb : vb - va;
  });

  const allP20    = allData.map(d=>d.p20).filter(Boolean);
  const allP80    = allData.map(d=>d.p80).filter(Boolean);
  const globalMin = allP20.length ? Math.min(...allP20) : 0;
  const globalMax = allP80.length ? Math.max(...allP80) : 1;
  const range     = globalMax - globalMin || 1;

  const tbody = document.getElementById('tblBody');
  tbody.innerHTML = '';

  sorted.forEach(d => {
    const key = isArr ? d.cp : (d.code_commune || d.commune);
    const tr  = document.createElement('tr');
    tr.dataset.key = key;
    if (key === expandedKey) tr.classList.add('expanded');

    const barLeft  = Math.max(0, ((d.p20 - globalMin) / range) * 100);
    const barWidth = Math.max(2, ((d.p80 - d.p20)    / range) * 100);

    const rangeCell = `<div class="range-bar">
      <span class="range-val">${fmtK(d.p20)}</span>
      <div class="range-track"><div class="range-fill" style="left:${barLeft.toFixed(1)}%;width:${barWidth.toFixed(1)}%"></div></div>
      <span class="range-val">${fmtK(d.p80)}</span>
    </div>`;

    if (isArr) {
      const ville = document.getElementById('fVille').value;
      const vLbl  = VILLE_LABELS[ville] || ville;
      tr.innerHTML = `
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <div class="arr-badge">${d.arr}</div>
            <div>
              <div style="font-weight:700;">${vLbl} ${arrName(d.arr)}</div>
              <div class="arr-name">${d.commune}</div>
            </div>
          </div>
        </td>
        <td><span class="prix-med">${fmtK(d.median)}</span></td>
        <td class="hide-mob">${rangeCell}</td>
        <td><span class="count-pill">${numFr(d.count)}</span></td>
        <td><span class="btn-rues">Rues <span class="arr">⌄</span></span></td>`;
    } else {
      tr.innerHTML = `
        <td style="font-weight:600;">${d.commune ?? '—'}</td>
        <td><span class="prix-med">${fmtK(d.median)}</span></td>
        <td class="hide-mob">${rangeCell}</td>
        <td><span class="count-pill">${numFr(d.count)}</span></td>
        <td><span class="btn-rues">Rues <span class="arr">⌄</span></span></td>`;
    }

    const drillData = isArr
      ? { type:'cp', cp: d.cp, ville: document.getElementById('fVille').value, label: `${VILLE_LABELS[document.getElementById('fVille').value] || ''} ${arrName(d.arr)}` }
      : { type:'commune', code_commune: d.code_commune, commune: d.commune, dep: document.getElementById('fDep').value, label: d.commune ?? '?' };

    tr.addEventListener('click', () => toggleDrill(key, drillData, tr, tableMode));
    tbody.appendChild(tr);

    if (key === expandedKey) {
      tbody.appendChild(buildDrillRow(key, drillData));
    }
  });

  document.getElementById('tblHint').style.display = 'block';
  document.getElementById('tblWrap').style.display = 'block';
  updateSortHeaders();
}

// ── Drill-down rues ───────────────────────────────────────────────────────────

function toggleDrill(key, drillData, tr, tableMode) {
  const tbody = document.getElementById('tblBody');
  const existing = tbody.querySelector(`[data-drill-for="${CSS.escape(key)}"]`);
  if (existing) {
    existing.remove();
    tr.classList.remove('expanded');
    expandedKey = null;
    return;
  }
  tbody.querySelectorAll('.drill-row').forEach(r => r.remove());
  tbody.querySelectorAll('tr.expanded').forEach(r => r.classList.remove('expanded'));
  tr.classList.add('expanded');
  expandedKey = key;
  const drillTr = buildDrillRow(key, drillData);
  tr.insertAdjacentElement('afterend', drillTr);
}

function buildDrillRow(key, drillData) {
  const tr = document.createElement('tr');
  tr.classList.add('drill-row');
  tr.dataset.drillFor = key;
  tr.innerHTML = `<td colspan="5"><div class="drill-inner">
    <div class="drill-title">Rues — ${drillData.label}</div>
    <div class="drill-loading"><span class="spin"></span> Chargement…</div>
  </div></td>`;
  loadDrillRues(drillData, tr);
  return tr;
}

async function loadDrillRues(drillData, tr) {
  const type     = document.getElementById('fType').value;
  const pieces   = document.getElementById('fPieces').value;
  const anneeMin = document.getElementById('fAnneeMin').value;
  const anneeMax = document.getElementById('fAnneeMax').value;

  const params = new URLSearchParams({ mode: 'rues', annee_min: anneeMin, annee_max: anneeMax });
  if (type)   params.set('type_local', type);
  if (pieces) params.set('pieces', pieces);

  if (drillData.type === 'cp') {
    params.set('ville', drillData.ville);
    params.set('cp', drillData.cp);
  } else if (drillData.code_commune) {
    params.set('code_commune', drillData.code_commune);
  } else {
    params.set('dep', drillData.dep);
    params.set('commune', drillData.commune);
  }

  try {
    const r = await fetch('api/prix-m2.php?' + params, { headers: { Accept: 'application/json' } });
    const d = await r.json();
    const inner = tr.querySelector('.drill-inner');
    if (!d.ok || !d.rues?.length) {
      inner.innerHTML = `<div class="drill-title">${drillData.label}</div><p style="color:#6B7280;font-size:13px;">Pas assez de données pour afficher les rues.</p>`;
      return;
    }
    renderDrillContent(inner, d.rues, drillData.label);
  } catch(e) {
    tr.querySelector('.drill-inner').innerHTML = `<p style="color:#b91c1c;font-size:13px;">Erreur lors du chargement.</p>`;
  }
}

function renderDrillContent(inner, allRues, title) {
  let drillSearch = '';
  let drillSort   = 'median';
  let drillAsc    = false;

  function draw() {
    const q    = drillSearch.toLowerCase().trim();
    let list   = q ? allRues.filter(x => x.voie.toLowerCase().includes(q)) : allRues.slice();
    list.sort((a, b) => {
      const va = a[drillSort] ?? (drillSort === 'voie' ? '' : 0);
      const vb = b[drillSort] ?? (drillSort === 'voie' ? '' : 0);
      if (typeof va === 'string') return drillAsc ? va.localeCompare(vb, 'fr') : vb.localeCompare(va, 'fr');
      return drillAsc ? va - vb : vb - va;
    });

    inner.querySelector('.drill-count').textContent =
      q ? `${list.length}/${allRues.length} rues` : `${allRues.length} rue${allRues.length > 1 ? 's' : ''}`;

    inner.querySelector('.drill-table tbody').innerHTML = list.map(rue => `
      <tr>
        <td>${rue.voie}</td>
        <td style="font-weight:700;">${fmtK(rue.median)}</td>
        <td class="hide-mob">${fmtK(rue.p20)} – ${fmtK(rue.p80)}</td>
        <td>${numFr(rue.count)} vente${rue.count > 1 ? 's' : ''}</td>
      </tr>`).join('');

    inner.querySelectorAll('thead th[data-dcol]').forEach(th => {
      const col = th.dataset.dcol;
      th.classList.toggle('dsorted', col === drillSort);
      const ic = th.querySelector('.dsort-icon');
      if (ic) ic.textContent = col !== drillSort ? '↕' : (drillAsc ? '↑' : '↓');
    });
  }

  inner.innerHTML = `
    <div class="drill-title">${title} <span class="drill-count"></span></div>
    <input class="drill-search" type="search" placeholder="Filtrer une rue…" />
    <div class="drill-scroll">
      <table class="drill-table">
        <thead><tr>
          <th data-dcol="voie">Rue <span class="dsort-icon">↕</span></th>
          <th data-dcol="median">Médiane /m² <span class="dsort-icon">↕</span></th>
          <th class="hide-mob" data-dcol="p20">Fourchette P20–P80 <span class="dsort-icon">↕</span></th>
          <th data-dcol="count">Ventes <span class="dsort-icon">↕</span></th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>`;

  draw();

  inner.querySelector('.drill-search').addEventListener('input', function() {
    drillSearch = this.value;
    draw();
  });
  inner.querySelectorAll('thead th[data-dcol]').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.dcol;
      if (drillSort === col) drillAsc = !drillAsc;
      else { drillSort = col; drillAsc = col === 'voie'; }
      draw();
    });
  });
}

// ── Sort headers ─────────────────────────────────────────────────────────────

function updateSortHeaders() {
  document.querySelectorAll('#tblHead th[data-col]').forEach(th => {
    const col = th.dataset.col;
    th.classList.toggle('sorted', col === sortCol);
    const icon = th.querySelector('.sort-icon');
    if (icon) icon.textContent = col !== sortCol ? '↕' : (sortAsc ? '↑' : '↓');
  });
}

// ── UI helpers ────────────────────────────────────────────────────────────────

function showStatus(msg, type) {
  const el = document.getElementById('statusBar');
  el.className = `status-bar ${type} visible`;
  el.innerHTML = type === 'loading' ? `<span class="spin"></span> ${msg}` : msg;
}
function hideStatus() {
  document.getElementById('statusBar').classList.remove('visible');
}

// ── Graphique évolution ───────────────────────────────────────────────────────

let evoChart = null;

async function loadChart() {
  const type   = document.getElementById('fType').value;
  const pieces = document.getElementById('fPieces').value;

  document.getElementById('chartLoading').innerHTML = '<span class="spin"></span>&nbsp; Chargement…';
  document.getElementById('chartLoading').style.display = 'flex';
  document.getElementById('evoChart').style.display     = 'none';

  const params = new URLSearchParams({ mode: 'evolution', annee_min: 2014, annee_max: 2025 });
  if (type)   params.set('type_local', type);
  if (pieces) params.set('pieces', pieces);

  if (currentMode === 'grandes-villes') {
    const ville = document.getElementById('fVille').value;
    params.set('ville', ville);
    document.getElementById('chartTitle').textContent = `Évolution du prix au m² — ${VILLE_LABELS[ville] || ville}`;
  } else {
    const dep = document.getElementById('fDep').value;
    if (!dep) return;
    params.set('dep', dep);
    const sel = document.getElementById('fDep');
    document.getElementById('chartTitle').textContent = `Évolution du prix au m² — ${sel.options[sel.selectedIndex]?.text || dep}`;
  }

  try {
    const r = await fetch('api/prix-m2.php?' + params, { headers: { Accept: 'application/json' } });
    const d = await r.json();
    if (!d.ok || !d.evolution?.length) {
      document.getElementById('chartLoading').innerHTML = 'Pas de données pour le graphique.';
      return;
    }

    const data    = d.evolution;
    const labels  = data.map(e => e.annee);
    const medians = data.map(e => e.median);
    const p20s    = data.map(e => e.p20);
    const p80s    = data.map(e => e.p80);

    document.getElementById('chartLoading').style.display = 'none';
    const canvas = document.getElementById('evoChart');
    canvas.style.display = 'block';

    if (evoChart) evoChart.destroy();

    evoChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [
          { label:'P80',  data:p80s, borderColor:'transparent', backgroundColor:'rgba(16,185,129,.12)', pointRadius:0, fill:'+1', tension:0.35 },
          { label:'Médiane €/m²', data:medians, borderColor:'#1E3A8A', backgroundColor:'rgba(30,58,138,.08)', borderWidth:2.5, pointRadius:4, pointBackgroundColor:'#1E3A8A', pointHoverRadius:6, tension:0.35, fill:false },
          { label:'P20',  data:p20s, borderColor:'transparent', backgroundColor:'rgba(16,185,129,.12)', pointRadius:0, fill:'-1', tension:0.35 },
        ]
      },
      options: {
        responsive:true, maintainAspectRatio:false,
        interaction:{ mode:'index', intersect:false },
        plugins:{
          legend:{ display:false },
          tooltip:{ callbacks:{
            title: ctx => 'Année ' + ctx[0].label,
            label: ctx => {
              const map = { 'Médiane €/m²':'  Médiane', 'P80':'  P80', 'P20':'  P20' };
              return (map[ctx.dataset.label] ?? ctx.dataset.label) + ' : ' + numFr(ctx.parsed.y) + ' €/m²';
            }
          }}
        },
        scales:{
          x:{ grid:{color:'#f3f4f6'}, ticks:{font:{size:12},color:'#6B7280'} },
          y:{ grid:{color:'#f3f4f6'}, ticks:{font:{size:12},color:'#6B7280', callback:v=>numFr(v)+' €'} }
        }
      }
    });
  } catch(e) {
    document.getElementById('chartLoading').innerHTML = 'Impossible de charger le graphique.';
  }
}

// ── Init ─────────────────────────────────────────────────────────────────────

document.getElementById('btnLoad').addEventListener('click', () => { loadChart(); load(); });
loadChart();
load();
</script>
</body>
</html>
