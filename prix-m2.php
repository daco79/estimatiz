<?php $navActive = 'prix'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Prix au m² – Estimatiz</title>
  <!-- SEO enhancements -->
  <meta name="description" content="Consultez l’évolution du prix au m² à Paris et par arrondissement grâce aux données DVF. Filtrez par type de bien, nombre de pièces et période." />
  <link rel="canonical" href="https://www.estimatiz.fr/prix-m2.php" />
  <!-- Open Graph tags -->
  <meta property="og:title" content="Prix au m² – Estimatiz" />
  <meta property="og:description" content="Consultez l’évolution du prix au m² à Paris et par arrondissement grâce aux données DVF. Filtrez par type de bien, nombre de pièces et période." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.estimatiz.fr/prix-m2.php" />
  <meta property="og:locale" content="fr_FR" />
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
    .filters-bar select, .filters-bar input[type=number]{
      padding:7px 10px; font-size:13px; border:1px solid #d1d5db; border-radius:8px;
      background:#fff; color:#111827; font-family:inherit;
    }
    .filters-bar .filter-group{ display:flex; align-items:center; gap:6px; }
    .filters-bar .sep{ color:#d1d5db; }
    .btn-filter{ padding:8px 16px; font-size:13px; font-weight:700; border:none; border-radius:8px; background:var(--c1); color:#fff; cursor:pointer; }
    .btn-filter:hover{ background:#1e40af; }
    /* ── Wrap ── */
    .wrap{ max-width:1000px; margin:0 auto; padding:32px 20px 60px; }
    /* ── Status ── */
    .status-bar{ padding:14px 18px; border-radius:12px; font-size:14px; margin-bottom:20px; display:none; }
    .status-bar.visible{ display:block; }
    .status-bar.loading{ background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .status-bar.error  { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .spin{ display:inline-block; width:13px; height:13px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
    @keyframes spin{ to{ transform:rotate(360deg); } }
    /* ── Résumé stats ── */
    .summary{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
    .sum-card{ background:#fff; border-radius:12px; padding:16px; border:1px solid #e5e7eb; text-align:center; }
    .sum-val{ font-size:22px; font-weight:800; color:var(--c1); }
    .sum-lbl{ font-size:11px; color:#6B7280; margin-top:3px; }
    /* ── Tableau arrondissements ── */
    .tbl-wrap{ background:#fff; border-radius:16px; border:1px solid #e5e7eb; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    .tbl-wrap table{ width:100%; border-collapse:collapse; }
    thead th{
      padding:12px 14px; font-size:12px; font-weight:700; color:#6B7280;
      text-transform:uppercase; letter-spacing:.05em; text-align:left;
      background:#f9fafb; border-bottom:1px solid #e5e7eb; cursor:pointer; user-select:none;
      white-space:nowrap;
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
    .prix-unit{ font-size:12px; color:#6B7280; font-weight:400; }
    /* Barre fourchette */
    .range-bar{ display:flex; align-items:center; gap:6px; }
    .range-val{ font-size:12px; color:#6B7280; white-space:nowrap; }
    .range-track{ flex:1; height:6px; background:#e5e7eb; border-radius:3px; min-width:60px; position:relative; overflow:hidden; }
    .range-fill{ position:absolute; top:0; height:100%; background:var(--c2); border-radius:3px; }
    .count-pill{ display:inline-block; background:#f3f4f6; color:#374151; font-size:12px; font-weight:600; padding:3px 8px; border-radius:20px; }
    .expand-icon{ color:#9CA3AF; font-size:16px; transition:transform .2s; }
    tr.expanded .expand-icon{ transform:rotate(180deg); color:var(--c1); }
    /* ── Détail rues ── */
    .drill-row td{ padding:0 !important; background:#f0f7ff; border-bottom:1px solid #bfdbfe !important; }
    .drill-inner{ padding:16px 20px 20px; }
    .drill-header{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:12px; }
    .drill-title{ font-size:13px; font-weight:700; color:var(--c1); margin:0 0 8px; }
    .drill-search{ display:block; padding:6px 10px; font-size:13px; border:1px solid #bfdbfe; border-radius:8px; background:#fff; width:100%; max-width:280px; font-family:inherit; color:#111827; margin-bottom:8px; }
    .drill-search:focus{ outline:none; border-color:var(--c1); }
    .drill-scroll{ max-height:280px; overflow-y:auto; border:1px solid #dbeafe; border-radius:10px; background:#fff; }
    .drill-table{ width:100%; border-collapse:collapse; }
    .drill-table thead{ position:sticky; top:0; z-index:1; }
    .drill-table th{
      font-size:11px; font-weight:700; color:#6B7280; text-transform:uppercase;
      padding:8px 12px; text-align:left; border-bottom:1px solid #dbeafe;
      background:#f0f7ff; cursor:pointer; user-select:none; white-space:nowrap;
    }
    .drill-table th:hover{ color:var(--c1); }
    .drill-table th.dsorted{ color:var(--c1); }
    .drill-table th .si{ margin-left:3px; opacity:.5; }
    .drill-table th.dsorted .si{ opacity:1; }
    .drill-table td{ font-size:13px; padding:8px 12px; border-bottom:1px solid #e8f0fe; color:#111827; }
    .drill-table tbody tr:last-child td{ border-bottom:none; }
    .drill-table tbody tr:hover{ background:#e8f0fe; }
    .drill-empty{ padding:16px 12px; font-size:13px; color:#6B7280; text-align:center; }
    .drill-count{ font-size:12px; color:#6B7280; }
    .drill-loading{ color:#6B7280; font-size:13px; padding:12px 0; }
    /* ── Mobile ── */
    @media(max-width:700px){
      .summary{ grid-template-columns:repeat(2,1fr); }
      .filters-bar{ padding:12px 14px; }
      .hide-mob{ display:none; }
      .wrap{ padding:20px 12px 48px; }
      .hero{ padding:32px 16px 24px; }
      .hero h1{ font-size:22px; }
    }
    /* ── Footer ── */
    footer{ background:#111827; color:rgba(255,255,255,.6); text-align:center; padding:24px; font-size:13px; }
    footer a{ color:rgba(255,255,255,.8); text-decoration:none; }
  </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>
  <div class="hero">
    <h1>Prix au m² à Paris</h1>
    <p>Statistiques fondées sur les ventes officielles DVF. Cliquez sur un arrondissement pour voir les rues.</p>
  </div>
  <!-- Graphique évolution -->
  <div class="chart-section">
    <div class="chart-header">
      <div>
        <p class="chart-title">Évolution du prix au m²</p>
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
      <label for="fVille">Ville</label>
      <select id="fVille">
        <option value="paris">Paris</option>
        <!-- Autres villes à venir -->
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
    <div class="status-bar loading visible" id="statusBar">
      <span class="spin"></span> Chargement des données…
    </div>
    <!-- Résumé -->
    <div class="summary" id="summaryBox" style="display:none;">
      <div class="sum-card"><div class="sum-val" id="sumMedian">—</div><div class="sum-lbl">Prix médian Paris</div></div>
      <div class="sum-card"><div class="sum-val" id="sumMin">—</div><div class="sum-lbl">Arrondissement le - cher</div></div>
      <div class="sum-card"><div class="sum-val" id="sumMax">—</div><div class="sum-lbl">Arrondissement le + cher</div></div>
      <div class="sum-card"><div class="sum-val" id="sumCount">—</div><div class="sum-lbl">Ventes analysées</div></div>
    </div>
    <!-- Tableau -->
    <div class="tbl-wrap" id="tblWrap" style="display:none;">
      <table>
        <thead>
          <tr>
            <th data-col="arr">Arrondissement <span class="sort-icon">↕</span></th>
            <th data-col="median" class="sorted">Prix médian /m² <span class="sort-icon">↓</span></th>
            <th data-col="p20" class="hide-mob">Fourchette P20–P80 <span class="sort-icon">↕</span></th>
            <th data-col="count">Ventes <span class="sort-icon">↕</span></th>
            <th></th>
          </tr>
        </thead>
        <tbody id="tblBody"></tbody>
      </table>
    </div>
  </div>
  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; Paris 2014–2025
  </footer>
  <script src="assets/js/utils.js"></script>
  <script>
  const fmt  = v => v == null ? '—' : new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' €';
  const fmtK = v => v == null ? '—' : new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' €/m²';
  const arrName = n => n === 1 ? '1er' : n + 'e';
  let allData = [];
  let sortCol = 'median';
  let sortAsc = false;
  let expandedCp = null;
  // ── Chargement données ────────────────────────────────────────────────────────
  async function load() {
    const ville    = document.getElementById('fVille').value;
    const type     = document.getElementById('fType').value;
    const pieces   = document.getElementById('fPieces').value;
    const anneeMin = document.getElementById('fAnneeMin').value;
    const anneeMax = document.getElementById('fAnneeMax').value;
    showStatus('Chargement des données…', 'loading');
    document.getElementById('summaryBox').style.display = 'none';
    document.getElementById('tblWrap').style.display    = 'none';
    expandedCp = null;
    const params = new URLSearchParams({ ville, annee_min: anneeMin, annee_max: anneeMax });
    if (type)   params.set('type_local', type);
    if (pieces) params.set('pieces', pieces);
    try {
      const r = await fetch('api/prix-m2.php?' + params, { headers: { Accept: 'application/json' } });
      const d = await r.json();
      if (!d.ok || !d.data?.length) { showStatus('Aucune donnée pour ces critères.', 'error'); return; }
      allData = d.data;
      hideStatus();
      renderSummary();
      renderTable();
    } catch(e) {
      showStatus('Erreur lors du chargement. Vérifiez que le serveur est actif.', 'error');
    }
  }
  // ── Résumé ────────────────────────────────────────────────────────────────────
  function renderSummary() {
    const medians = allData.map(d => d.median).filter(Boolean);
    const globalMedian = medians.length ? Math.round(medians.reduce((a,b)=>a+b,0)/medians.length) : null;
    const sorted = [...allData].sort((a,b) => a.median - b.median);
    const totalCount = allData.reduce((s,d) => s + d.count, 0);
    document.getElementById('sumMedian').textContent = fmtK(globalMedian);
    document.getElementById('sumMin').textContent    = sorted[0]  ? fmtK(sorted[0].median)  : '—';
    document.getElementById('sumMax').textContent    = sorted.at(-1) ? fmtK(sorted.at(-1).median) : '—';
    document.getElementById('sumCount').textContent  = new Intl.NumberFormat('fr-FR').format(totalCount);
    document.getElementById('summaryBox').style.display = 'grid';
  }
  // ── Tableau ───────────────────────────────────────────────────────────────────
  function renderTable() {
    const sorted = [...allData].sort((a,b) => {
      const va = a[sortCol] ?? 0, vb = b[sortCol] ?? 0;
      return sortAsc ? va - vb : vb - va;
    });
    // Fourchette globale pour la barre proportionnelle
    const allP20 = allData.map(d=>d.p20).filter(Boolean);
    const allP80 = allData.map(d=>d.p80).filter(Boolean);
    const globalMin = Math.min(...allP20);
    const globalMax = Math.max(...allP80);
    const range = globalMax - globalMin || 1;
    const tbody = document.getElementById('tblBody');
    tbody.innerHTML = '';
    sorted.forEach(d => {
      const tr = document.createElement('tr');
      tr.dataset.cp = d.cp;
      if (d.cp === expandedCp) tr.classList.add('expanded');
      // Barre proportionnelle
      const barLeft  = Math.max(0, ((d.p20 - globalMin) / range) * 100);
      const barWidth = Math.max(2, (((d.p80 - d.p20)) / range) * 100);
      tr.innerHTML = `
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <div class="arr-badge">${d.arr}</div>
            <div>
              <div style="font-weight:700;">Paris ${arrName(d.arr)}</div>
              <div class="arr-name">${d.commune}</div>
            </div>
          </div>
        </td>
        <td><span class="prix-med">${fmtK(d.median)}</span></td>
        <td class="hide-mob">
          <div class="range-bar">
            <span class="range-val">${fmtK(d.p20)}</span>
            <div class="range-track"><div class="range-fill" style="left:${barLeft.toFixed(1)}%;width:${barWidth.toFixed(1)}%"></div></div>
            <span class="range-val">${fmtK(d.p80)}</span>
          </div>
        </td>
        <td><span class="count-pill">${new Intl.NumberFormat('fr-FR').format(d.count)}</span></td>
        <td><span class="expand-icon">⌄</span></td>
      `;
      tr.addEventListener('click', () => toggleDrill(d.cp, tr));
      tbody.appendChild(tr);
      // Ligne drill déjà ouverte
      if (d.cp === expandedCp) {
        const drillTr = buildDrillRow(d.cp);
        tbody.appendChild(drillTr);
      }
    });
    document.getElementById('tblWrap').style.display = 'block';
    updateSortHeaders();
  }
  // ── Drill-down rues ───────────────────────────────────────────────────────────
  function buildDrillRow(cp) {
    const tr = document.createElement('tr');
    tr.classList.add('drill-row');
    tr.dataset.drillFor = cp;
    tr.innerHTML = `<td colspan="5"><div class="drill-inner"><div class="drill-title">Rues — ${cp}</div><div class="drill-loading"><span class="spin"></span> Chargement…</div></div></td>`;
    loadDrill(cp, tr);
    return tr;
  }
  async function loadDrill(cp, tr) {
    const type     = document.getElementById('fType').value;
    const pieces   = document.getElementById('fPieces').value;
    const anneeMin = document.getElementById('fAnneeMin').value;
    const anneeMax = document.getElementById('fAnneeMax').value;
    const ville    = document.getElementById('fVille').value;
    const params = new URLSearchParams({ mode: 'rues', ville, cp, annee_min: anneeMin, annee_max: anneeMax });
    if (type)   params.set('type_local', type);
    if (pieces) params.set('pieces', pieces);
    try {
      const r = await fetch('api/prix-m2.php?' + params, { headers: { Accept: 'application/json' } });
      const d = await r.json();
      const inner = tr.querySelector('.drill-inner');
      const arr   = allData.find(x => x.cp === cp)?.arr ?? '?';
      const title = `Rues — Paris ${arrName(arr)}`;
      if (!d.ok || !d.rues?.length) {
        inner.innerHTML = `<div class="drill-title">${title}</div><p style="color:#6B7280;font-size:13px;">Pas assez de données pour afficher les rues.</p>`;
        return;
      }
      let allRues   = d.rues;         // tableau complet
      let drillSearch = '';
      let drillSort   = 'median';
      let drillAsc    = false;
      function renderDrillTable() {
        const q = drillSearch.toLowerCase().trim();
        let list = q ? allRues.filter(x => x.voie.toLowerCase().includes(q)) : allRues.slice();
        list.sort((a, b) => {
          const va = a[drillSort] ?? 0;
          const vb = b[drillSort] ?? 0;
          if (typeof va === 'string') return drillAsc ? va.localeCompare(vb) : vb.localeCompare(va);
          return drillAsc ? va - vb : vb - va;
        });
        const rows = list.map(rue => `
          <tr>
            <td>${rue.voie}</td>
            <td style="font-weight:700;">${fmtK(rue.median)}</td>
            <td class="hide-mob">${fmtK(rue.p20)} – ${fmtK(rue.p80)}</td>
            <td>${rue.count} vente${rue.count>1?'s':''}</td>
          </tr>`).join('');
        const arrow = col => col !== drillSort ? '↕' : (drillAsc ? '↑' : '↓');
        const thCls = col => `style="cursor:pointer;user-select:none;" data-dcol="${col}"`;
        inner.querySelector('.drill-count').textContent =
          q ? `${list.length} rue${list.length>1?'s':''} sur ${allRues.length}` : `${allRues.length} rue${allRues.length>1?'s':''}`;
        inner.querySelector('.drill-table tbody').innerHTML = rows;
        inner.querySelectorAll('thead th[data-dcol]').forEach(th => {
          const col = th.dataset.dcol;
          th.classList.toggle('sorted', col === drillSort);
          const ic = th.querySelector('.dsort-icon');
          if (ic) ic.textContent = arrow(col);
        });
      }
      const arrow2 = col => col !== drillSort ? '↕' : (drillAsc ? '↑' : '↓');
      inner.innerHTML = `
        <div class="drill-title">${title} <span class="drill-count"></span></div>
        <input class="drill-search" type="search" placeholder="Filtrer une rue…" />
        <div class="drill-scroll">
          <table class="drill-table">
            <thead><tr>
              <th data-dcol="voie">Rue <span class="dsort-icon">↕</span></th>
              <th data-dcol="median">Médiane /m² <span class="dsort-icon">↕</span></th>
              <th class="hide-mob" data-dcol="p20">Fourchette <span class="dsort-icon">↕</span></th>
              <th data-dcol="count">Ventes <span class="dsort-icon">↕</span></th>
            </tr></thead>
            <tbody></tbody>
          </table>
        </div>`;
      renderDrillTable();
      // Search
      inner.querySelector('.drill-search').addEventListener('input', function() {
        drillSearch = this.value;
        renderDrillTable();
      });
      // Sort headers
      inner.querySelectorAll('thead th[data-dcol]').forEach(th => {
        th.addEventListener('click', () => {
          const col = th.dataset.dcol;
          if (drillSort === col) drillAsc = !drillAsc;
          else { drillSort = col; drillAsc = col === 'voie'; }
          renderDrillTable();
        });
      });
    } catch(e) {
      tr.querySelector('.drill-inner').innerHTML = `<p style="color:#b91c1c;font-size:13px;">Erreur lors du chargement des rues.</p>`;
    }
  }
  function toggleDrill(cp, tr) {
    const tbody = document.getElementById('tblBody');
    // Fermer si déjà ouvert
    const existing = tbody.querySelector(`[data-drill-for="${cp}"]`);
    if (existing) {
      existing.remove();
      tr.classList.remove('expanded');
      expandedCp = null;
      return;
    }
    // Fermer tout autre drill ouvert
    tbody.querySelectorAll('.drill-row').forEach(r => r.remove());
    tbody.querySelectorAll('tr.expanded').forEach(r => r.classList.remove('expanded'));
    // Ouvrir
    tr.classList.add('expanded');
    expandedCp = cp;
    const drillTr = buildDrillRow(cp);
    tr.insertAdjacentElement('afterend', drillTr);
  }
  // ── Tri ───────────────────────────────────────────────────────────────────────
  function updateSortHeaders() {
    document.querySelectorAll('thead th[data-col]').forEach(th => {
      const col = th.dataset.col;
      th.classList.toggle('sorted', col === sortCol);
      const icon = th.querySelector('.sort-icon');
      if (icon) icon.textContent = col !== sortCol ? '↕' : (sortAsc ? '↑' : '↓');
    });
  }
  document.querySelectorAll('thead th[data-col]').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (sortCol === col) sortAsc = !sortAsc;
      else { sortCol = col; sortAsc = col === 'arr'; }
      expandedCp = null;
      renderTable();
    });
  });
  // ── Helpers UI ────────────────────────────────────────────────────────────────
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
    const ville    = document.getElementById('fVille').value;
    const type     = document.getElementById('fType').value;
    const pieces   = document.getElementById('fPieces').value;
    document.getElementById('chartLoading').style.display = 'flex';
    document.getElementById('evoChart').style.display     = 'none';
    const params = new URLSearchParams({ mode: 'evolution', ville, annee_min: 2014, annee_max: 2025 });
    if (type)   params.set('type_local', type);
    if (pieces) params.set('pieces', pieces);
    try {
      const r = await fetch('api/prix-m2.php?' + params, { headers: { Accept: 'application/json' } });
      const d = await r.json();
      if (!d.ok || !d.evolution?.length) return;
      const data = d.evolution;
      const labels = data.map(e => e.annee);
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
            {
              // Zone P20–P80 (partie haute : P80)
              label: 'P80',
              data: p80s,
              borderColor: 'transparent',
              backgroundColor: 'rgba(16,185,129,.12)',
              pointRadius: 0,
              fill: '+1',
              tension: 0.35,
            },
            {
              // Médiane
              label: 'Médiane €/m²',
              data: medians,
              borderColor: '#1E3A8A',
              backgroundColor: 'rgba(30,58,138,.08)',
              borderWidth: 2.5,
              pointRadius: 4,
              pointBackgroundColor: '#1E3A8A',
              pointHoverRadius: 6,
              tension: 0.35,
              fill: false,
            },
            {
              // Zone P20–P80 (partie basse : P20)
              label: 'P20',
              data: p20s,
              borderColor: 'transparent',
              backgroundColor: 'rgba(16,185,129,.12)',
              pointRadius: 0,
              fill: '-1',
              tension: 0.35,
            },
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: ctx => 'Année ' + ctx[0].label,
                label: ctx => {
                  const map = { 'Médiane €/m²': '  Médiane', 'P80': '  P80', 'P20': '  P20' };
                  const lbl = map[ctx.dataset.label] ?? ctx.dataset.label;
                  return lbl + ' : ' + new Intl.NumberFormat('fr-FR').format(ctx.parsed.y) + ' €/m²';
                }
              }
            }
          },
          scales: {
            x: {
              grid: { color: '#f3f4f6' },
              ticks: { font: { size: 12 }, color: '#6B7280' }
            },
            y: {
              grid: { color: '#f3f4f6' },
              ticks: {
                font: { size: 12 }, color: '#6B7280',
                callback: v => new Intl.NumberFormat('fr-FR').format(v) + ' €'
              }
            }
          }
        }
      });
    } catch(e) {
      document.getElementById('chartLoading').innerHTML = 'Impossible de charger le graphique.';
    }
  }
  // ── Init ──────────────────────────────────────────────────────────────────────
  document.getElementById('btnLoad').addEventListener('click', () => { loadChart(); load(); });
  loadChart();
  load();
  </script>
</body>
</html>