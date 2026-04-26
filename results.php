<?php $navActive = 'estimer'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Résultats d'estimation immobilière | Estimatiz</title>
  <meta name="description" content="Résultats détaillés de votre estimation immobilière : valeurs basse, médiane et haute, ventes comparables, indice de confiance. Données issues des DVF officielles." />

  <!-- noindex temporaire : page alimentée par sessionStorage, non indexable en l'état -->
  <meta name="robots" content="noindex, nofollow" />

  <link rel="icon" type="image/x-icon" href="/favicon.ico" />
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32x32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16x16.png" />
  <link rel="canonical" href="https://www.estimatiz.fr/results" />

  <!-- Open Graph -->
  <meta property="og:title" content="Résultats d'estimation immobilière — Estimatiz" />
  <meta property="og:description" content="Résultats personnalisés d'estimation basés sur les ventes DVF officielles : valeurs basse, médiane, haute et comparables." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.estimatiz.fr/results" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />

  <?php
    $seoTwitterTitle = "Résultats d'estimation immobilière — Estimatiz";
    $seoTwitterDesc  = "Estimation personnalisée à partir des ventes DVF officielles.";
    include 'includes/seo-extras.php';
  ?>

  <link rel="stylesheet" href="assets/css/site.css" />
  <style>
    :root{ --c1:#1E3A8A; --c2:#10B981; --c3:#111827; --c4:#E5E7EB; }
    *{ box-sizing:border-box; }
    body{ margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; background:var(--c4); color:#111827; }
    .wrap{ max-width:1000px; margin:0 auto; padding:28px 20px; }
    /* Header */
    .page-hdr{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .page-hdr h1{ font-size:20px; margin:0; color:var(--c1); }
    .page-hdr .sub{ font-size:13px; color:#6B7280; margin-top:3px; }
    /* Bloc estimation */
    .est-box{ background:#f0fdf4; border:1px solid #a7f3d0; border-radius:14px; padding:16px 20px; margin-bottom:18px; }
    .est-box h2{ font-size:14px; font-weight:700; color:#047857; margin:0 0 12px; text-transform:uppercase; letter-spacing:.05em; }
    .est-cols{ display:flex; gap:10px; flex-wrap:wrap; }
    .est-col{ flex:1; min-width:130px; text-align:center; padding:12px 8px; border-radius:10px; }
    .est-col.low{ background:#eff6ff; }
    .est-col.mid{ background:var(--c1); color:#fff; }
    .est-col.high{ background:#eff6ff; }
    .est-col .lbl{ font-size:11px; text-transform:uppercase; letter-spacing:.05em; opacity:.65; margin-bottom:4px; }
    .est-col.mid .lbl{ opacity:.8; color:#bfdbfe; }
    .est-col .val{ font-size:22px; font-weight:800; }
    .est-meta{ margin-top:10px; font-size:12px; color:#6B7280; display:flex; flex-wrap:wrap; gap:14px; }
    .est-meta .conf{ font-weight:600; }
    /* Stats */
    .stats-bar{ background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:10px 16px; margin-bottom:16px; font-size:13px; color:#4B5563; display:flex; flex-wrap:wrap; gap:16px; align-items:center; }
    .stats-bar b{ color:#111827; }
    /* Contrôles table */
    .tbl-ctrl{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:10px; }
    .tbl-ctrl .sel-info{ font-size:13px; color:#6B7280; }
    .tbl-ctrl .btn-group{ display:flex; gap:8px; flex-wrap:wrap; }
    button{ cursor:pointer; border:none; border-radius:10px; padding:9px 16px; font-size:13px; font-weight:600; }
    button:disabled{ opacity:.55; cursor:not-allowed; }
    .btn-all{ background:#f3f4f6; color:#111827; border:1px solid #d1d5db; }
    .btn-none{ background:#f3f4f6; color:#111827; border:1px solid #d1d5db; }
    .btn-pdf{ background:var(--c2); color:#fff; font-size:15px; padding:12px 28px; box-shadow:0 3px 10px rgba(16,185,129,.3); }
    /* Modal rapport */
    .modal-overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:100; align-items:center; justify-content:center; }
    .modal-overlay.open{ display:flex; }
    .modal{ background:#fff; border-radius:20px; padding:28px 24px; max-width:480px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,.2); }
    .modal h3{ margin:0 0 6px; font-size:18px; color:var(--c1); }
    .modal p{ margin:0 0 16px; font-size:13px; color:#6B7280; }
    .modal-url{ display:flex; gap:8px; }
    .modal-url input{ flex:1; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:13px; color:#111827; background:#f9fafb; }
    .btn-copy{ padding:10px 16px; background:var(--c1); color:#fff; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
    .btn-copy:hover{ background:#1e40af; }
    .btn-copy.copied{ background:var(--c2); }
    .modal-actions{ display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
    .btn-open{ flex:1; padding:10px; background:#f3f4f6; color:#111827; border:1px solid #d1d5db; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; text-align:center; text-decoration:none; display:inline-block; }
    .btn-open:hover{ background:#e5e7eb; }
    .btn-modal-close{ flex:1; padding:10px; background:#fff; color:#6B7280; border:1px solid #e5e7eb; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; }
    .modal-loading{ text-align:center; padding:8px 0; font-size:14px; color:#6B7280; }
    .btn-back{ background:#fff; color:var(--c1); border:1.5px solid var(--c1); font-size:13px; padding:9px 16px; display:inline-flex; align-items:center; gap:6px; text-decoration:none; border-radius:10px; font-weight:600; cursor:pointer; }
    .btn-back:hover{ background:#eff6ff; }
    /* Table */
    .tbl-wrap{ background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    table{ width:100%; border-collapse:collapse; }
    thead tr{ background:#f9fafb; }
    th{ padding:10px 12px; text-align:left; font-size:12px; font-weight:600; color:#374151; border-bottom:2px solid #e5e7eb; white-space:nowrap; }
    th.num, td.num{ text-align:right; }
    th.ctr, td.ctr{ text-align:center; }
    td{ padding:9px 12px; border-bottom:1px solid #f3f4f6; font-size:13px; vertical-align:middle; }
    tr:last-child td{ border-bottom:none; }
    tr.selected td{ background:#f0fdf4; }
    tr:hover td{ background:#f9fafb; }
    tr.selected:hover td{ background:#dcfce7; }
    td.cb-cell{ width:36px; }
    input[type=checkbox]{ width:16px; height:16px; cursor:pointer; accent-color:var(--c2); }
    /* Vide */
    .empty{ text-align:center; padding:40px; color:#9CA3AF; font-size:14px; }
    /* Loading */
    .loading-wrap{ text-align:center; padding:60px; color:#6B7280; font-size:15px; }
    /* Entête impression (caché à l'écran) */
    .print-hdr{ display:none; margin-bottom:16px; }
    /* Ligne supérieure : logo + date */
    .print-hdr-top{ display:flex; align-items:center; justify-content:space-between; padding-bottom:10px; border-bottom:2px solid var(--c1); margin-bottom:10px; }
    .print-logo-area{ display:flex; align-items:center; gap:10px; }
    .print-logo-area svg{ width:48px; height:48px; flex-shrink:0; }
    .print-brand-name{ display:block; font-size:20px; font-weight:800; color:var(--c1); line-height:1.1; }
    .print-brand-tag{ display:block; font-size:10px; color:#6B7280; margin-top:2px; }
    .print-brand-url{ display:block; font-size:10px; color:var(--c2); font-weight:600; margin-top:1px; }
    .print-date-box{ text-align:right; font-size:11px; color:#6B7280; }
    .print-date-box strong{ display:block; font-size:12px; color:#111827; }
    /* Ligne inférieure : adresse + filtres */
    .print-hdr-info{ background:#f0fdf4; border:1px solid #a7f3d0; border-radius:6px; padding:14px 18px; margin-top:18px; text-align:center; }
    .print-report-title{ font-size:14px; font-weight:700; color:#111827; margin:0 0 6px; }
    .print-report-meta{ font-size:11px; color:#4B5563; margin:0; line-height:1.8; }
    .print-report-meta span{ margin:0 8px; }
    /* Pied de page impression */
    .print-footer{ display:none; margin-top:14px; font-size:10px; color:#9CA3AF; border-top:1px solid #e5e7eb; padding-top:6px; display:flex; justify-content:space-between; }
    /* ── Mobile ── */
    @media (max-width:640px) {
      .wrap{ padding:14px 12px; }
      .page-hdr{ flex-direction:column; align-items:flex-start; gap:10px; }
      .page-hdr .btn-pdf-wrap{ width:100%; }
      .btn-pdf{ width:100%; text-align:center; font-size:14px; padding:12px 16px; }
      .btn-back{ font-size:12px; padding:8px 12px; }
      .est-cols{ flex-direction:column; }
      .est-col{ min-width:unset; }
      .tbl-ctrl{ flex-direction:column; align-items:flex-start; gap:8px; }
      .tbl-scroll{ overflow-x:auto; -webkit-overflow-scrolling:touch; border-radius:14px; }
      .tbl-wrap{ border-radius:0; box-shadow:none; overflow:visible; }
      table{ min-width:560px; }
      .stats-bar{ gap:10px; font-size:12px; }
    }
    /* ── Impression ── */
    @media print {
      @page{ size:A4; margin:14mm; }
      /* Masquer l'UI */
      .page-hdr, .tbl-ctrl, .stats-bar, .btn-back, .btn-pdf-wrap{ display:none !important; }
      /* Montrer l'entête et le pied de page print */
      .print-hdr{ display:block !important; }
      .print-footer{ display:flex !important; }
      /* Fond blanc, sans ombre */
      body{ background:#fff !important; }
      .wrap{ padding:0 !important; max-width:100% !important; }
      .tbl-scroll{ overflow:visible !important; }
      .tbl-wrap{ box-shadow:none !important; border-radius:4px !important; overflow:visible !important; }
      .est-box{ border-radius:4px !important; margin-top:20px !important; margin-bottom:10px !important; }
      .est-box h2{ text-align:center !important; }
      .est-col .val{ font-size:16px !important; }
      /* Masquer les lignes non sélectionnées et la colonne checkbox */
      tbody tr:not(.selected){ display:none !important; }
      .cb-cell{ display:none !important; }
      /* Styles table print */
      td, th{ font-size:11px !important; padding:5px 7px !important; }
    }
  </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>
<div class="wrap">
  <!-- Entête visible uniquement à l'impression -->
  <div class="print-hdr" id="printHdr">
    <!-- Logo + identité + date -->
    <div class="print-hdr-top">
      <div class="print-logo-area">
        <!-- Logo SVG Estimatiz -->
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
          <span class="print-brand-name">Estimatiz</span>
          <span class="print-brand-tag">Précision • Transparence • Data</span>
          <span class="print-brand-url" id="printUrl"></span>
        </div>
      </div>
      <div class="print-date-box">
        <strong id="printDateLabel"></strong>
        Rapport d'estimation immobilière
      </div>
    </div>
    <!-- Adresse + filtres -->
    <div class="print-hdr-info">
      <p class="print-report-title" id="printTitle"></p>
      <p class="print-report-meta" id="printMeta"></p>
    </div>
  </div>
  <div class="page-hdr">
    <div>
      <a class="btn-back" id="btnBack" href="estimation">&#8592; Modifier l'estimation</a>
      <h1 id="pageTitle" style="margin-top:10px">Résultats</h1>
      <div class="sub" id="pageSub"></div>
    </div>
    <div class="btn-pdf-wrap">
      <button class="btn-pdf" id="btnGenPdf" disabled>Générer le rapport</button>
    </div>
  </div>
  <div id="estBox" class="est-box" style="display:none">
    <h2>Estimation du bien</h2>
    <div class="est-cols" id="estCols"></div>
    <div class="est-meta" id="estMeta"></div>
  </div>
  <div id="statsBar" class="stats-bar" style="display:none"></div>
  <div class="tbl-ctrl">
    <div class="sel-info" id="selInfo">0 sélectionné</div>
    <div class="btn-group">
      <button class="btn-all" id="btnAll">Tout sélectionner</button>
      <button class="btn-none" id="btnNone">Tout désélectionner</button>
    </div>
  </div>
  <div class="tbl-scroll">
    <div class="tbl-wrap">
      <div class="loading-wrap" id="loadingMsg">Chargement des données…</div>
      <table id="tbl" style="display:none">
        <thead>
          <tr>
            <th class="cb-cell"><input type="checkbox" id="chkAll" title="Tout cocher/décocher"/></th>
            <th>Adresse</th>
            <th class="num">Valeur foncière</th>
            <th class="num">Surface</th>
            <th class="num">€/m²</th>
            <th class="ctr">Date</th>
            <th class="ctr">Pièces</th>
            <th>Lot(s)</th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
      <div class="empty" id="emptyMsg" style="display:none">Aucune mutation trouvée pour ces critères.</div>
    </div>
  </div>
  <!-- Modal rapport partageable -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal">
      <h3>Rapport généré ✓</h3>
      <p>Copiez ce lien pour partager votre rapport :</p>
      <div class="modal-url">
        <input type="text" id="modalUrlInput" readonly />
        <button class="btn-copy" id="btnCopy">Copier</button>
      </div>
      <div class="modal-actions">
        <a class="btn-open" id="btnOpenRapport" href="#" target="_blank">Ouvrir le rapport</a>
        <button class="btn-modal-close" id="btnModalClose">Fermer</button>
      </div>
    </div>
  </div>

  <!-- Pied de page visible uniquement à l'impression -->
  <div class="print-footer" id="printFooter">
    <span id="printFooterLeft"></span>
    <span>Source : Demandes de Valeurs Foncières (DVF) — data.gouv.fr</span>
  </div>
</div>
<script src="assets/js/utils.js"></script>
<script>
  /* ---- Chargement des données ---- */
  const stored = sessionStorage.getItem('estimatiz_results');
  if (!stored) {
    document.getElementById('loadingMsg').textContent = 'Aucune donnée. Retournez sur la page principale et cliquez "Visualiser les résultats".';
    throw new Error('No data');
  }
  const { label, surface, surfaceMin, surfaceMax, pieces, mutData, suggestion } = JSON.parse(stored);
  /* ---- Lien retour vers estimation.php ---- */
  if (suggestion) {
    const backParams = new URLSearchParams({
      label:     suggestion.label     || '',
      code_voie: suggestion.code_voie || '',
      commune:   suggestion.commune   || '',
      cp:        suggestion.cp        || '',
      type_voie: suggestion.type_voie || '',
      voie:      suggestion.voie      || '',
      no_voie:   suggestion.no_voie   || '',
      btq:       suggestion.btq       || '',
      section:   suggestion.section   || '',
    });
    document.getElementById('btnBack').href = 'estimation?' + backParams.toString();
  }
  const allRows = Array.isArray(mutData?.rows) ? mutData.rows : [];
  /* ---- Header écran ---- */
  document.title = `Estimatiz – ${label}`;
  document.getElementById('pageTitle').textContent = label;
  const subParts = [];
  if (surface)    subParts.push(`Surface : ${surface} m²`);
  if (pieces)     subParts.push(`${pieces} pièce${pieces > 1 ? 's' : ''}`);
  if (surfaceMin || surfaceMax) subParts.push(`Filtre surface : ${surfaceMin||'…'}–${surfaceMax||'…'} m²`);
  document.getElementById('pageSub').textContent = subParts.join('  ·  ');
  /* ---- Entête et pied de page impression ---- */
  const dateStr  = new Date().toLocaleDateString('fr-FR', { year:'numeric', month:'long', day:'2-digit' });
  const siteUrl  = window.location.origin + window.location.pathname.replace('results', '');
  document.getElementById('printDateLabel').textContent = dateStr;
  document.getElementById('printUrl').textContent       = siteUrl;
  document.getElementById('printTitle').textContent     = label;
  document.getElementById('printMeta').innerHTML = [
    surface    ? `<span>Surface estimée : <b>${surface} m²</b></span>` : null,
    pieces     ? `<span>Nombre de pièces : <b>${pieces}</b></span>` : null,
    (surfaceMin || surfaceMax) ? `<span>Filtre surface comparables : <b>${surfaceMin||'…'} – ${surfaceMax||'…'} m²</b></span>` : null,
  ].filter(Boolean).join('');
  document.getElementById('printFooterLeft').textContent = `Estimatiz · Généré le ${dateStr}`;
  /* ---- Mise à jour dynamique de l'estimation ---- */
  const surfaceNum = parseFloat(surface);
  window._currentEst = null;
  function updateEstimation(selectedRows) {
    const box = document.getElementById('estBox');
    if (!selectedRows.length || !Number.isFinite(surfaceNum)) {
      box.style.display = 'none';
      return;
    }
    const raw    = selectedRows.map(r => parseFloat(r.prix_m2)).filter(v => Number.isFinite(v) && v > 0);
    const values = filterIQR(raw).length >= 3 ? filterIQR(raw) : raw;
    if (!values.length) { box.style.display = 'none'; return; }
    const p20 = percentileArr(values, 0.20);
    const p50 = percentileArr(values, 0.50);
    const p80 = percentileArr(values, 0.80);
    const low  = Math.round(p20 * surfaceNum);
    const mid  = Math.round(p50 * surfaceNum);
    const high = Math.round(p80 * surfaceNum);
    // Score de confiance simplifié (dispersion + taille échantillon)
    const q1 = percentileArr(values, 0.25), q3 = percentileArr(values, 0.75);
    const disp  = p50 > 0 ? (q3 - q1) / p50 : 1;
    const confN = Math.min(1, values.length / 20);
    const confD = Math.max(0, Math.min(1, 1 - disp));
    const conf  = Math.round((0.6 * confN + 0.4 * confD) * 100);
    const confColor = conf >= 75 ? '#047857' : conf >= 50 ? '#d97706' : '#b91c1c';
    const confLabel = conf >= 75 ? 'Élevée'  : conf >= 50 ? 'Modérée'  : 'Faible';
    window._currentEst = { low, mid, high, p20: Math.round(p20), p50: Math.round(p50), p80: Math.round(p80), conf, confColor, confLabel, count: values.length };
    document.getElementById('estCols').innerHTML = `
      <div class="est-col low"><div class="lbl">Basse</div><div class="val">${formatEuro(low)}</div></div>
      <div class="est-col mid"><div class="lbl">Médiane</div><div class="val">${formatEuro(mid)}</div></div>
      <div class="est-col high"><div class="lbl">Haute</div><div class="val">${formatEuro(high)}</div></div>`;
    document.getElementById('estMeta').innerHTML =
      `<span class="conf" style="color:${confColor}">Indice de confiance : ${conf}% — ${confLabel}</span>` +
      `<span>€/m² médian : ${formatEuro(Math.round(p50))}</span>` +
      `<span>P20–P80 : ${formatEuro(Math.round(p20))} – ${formatEuro(Math.round(p80))}</span>` +
      `<span style="color:#9CA3AF">${values.length} vente${values.length>1?'s':''} retenue${values.length>1?'s':''}</span>`;
    box.style.display = 'block';
  }
  /* ---- Stats ---- */
  const stats = mutData?.stats;
  if (stats) {
    document.getElementById('statsBar').innerHTML =
      `<span><b>${stats.count}</b> vente${stats.count>1?'s':''} comparable${stats.count>1?'s':''}</span>` +
      (stats.median  ? `<span>Médiane : <b>${stats.median} €/m²</b></span>` : '') +
      (stats.p20 && stats.p80 ? `<span>P20–P80 : <b>${stats.p20}–${stats.p80} €/m²</b></span>` : '') +
      (stats.date_min ? `<span>Période : <b>${stats.date_min} → ${stats.date_max||'?'}</b></span>` : '');
    document.getElementById('statsBar').style.display = 'flex';
  }
  /* ---- Tableau ---- */
  document.getElementById('loadingMsg').style.display = 'none';
  if (!allRows.length) {
    document.getElementById('emptyMsg').style.display = 'block';
  } else {
    document.getElementById('tbl').style.display = 'table';
    const enriched = allRows.map((r, i) => {
      const valNum = normalizeNum(r.valeur_fonciere);
      const ts     = parseDateToTS(r.date_mutation);
      return { ...r, _i: i, _valNum: valNum, _ts: ts };
    }).sort((a, b) => b._ts - a._ts);
    const tbody = document.getElementById('tbody');
    enriched.forEach(r => {
      const tr = document.createElement('tr');
      tr.dataset.idx = r._i;
      tr.innerHTML = `
        <td class="cb-cell"><input type="checkbox" class="row-chk" checked/></td>
        <td>${r.adresse ?? ''}</td>
        <td class="num">${Number.isFinite(r._valNum) ? formatEuro(r._valNum) : (r.valeur_fonciere ?? '')}</td>
        <td class="num">${r.surface != null ? r.surface + ' m²' : ''}</td>
        <td class="num">${r.prix_m2 != null ? formatEuro(r.prix_m2) + '/m²' : ''}</td>
        <td class="ctr">${formatDateFR(r._ts, r.date_mutation || '')}</td>
        <td class="ctr">${r.nb_pieces != null ? r.nb_pieces + ' p.' : ''}</td>
        <td>${Array.isArray(r.lots_array) && r.lots_array.length ? r.lots_array.join(', ') : ''}</td>`;
      tr.classList.add('selected');
      tbody.appendChild(tr);
    });
    /* ---- Gestion sélection ---- */
    function getSelectedRows() {
      const rows = [];
      document.querySelectorAll('.row-chk:checked').forEach(chk => {
        const idx = parseInt(chk.closest('tr').dataset.idx, 10);
        const row = enriched.find(r => r._i === idx);
        if (row) rows.push(row);
      });
      return rows;
    }
    function updateSelInfo() {
      const total    = document.querySelectorAll('.row-chk').length;
      const selected = document.querySelectorAll('.row-chk:checked').length;
      document.getElementById('selInfo').textContent = `${selected} / ${total} sélectionné${selected > 1 ? 's' : ''}`;
      document.getElementById('btnGenPdf').disabled = selected === 0;
      document.getElementById('chkAll').checked       = selected === total;
      document.getElementById('chkAll').indeterminate = selected > 0 && selected < total;
      updateEstimation(getSelectedRows());
    }
    tbody.addEventListener('change', e => {
      if (e.target.classList.contains('row-chk')) {
        e.target.closest('tr').classList.toggle('selected', e.target.checked);
        updateSelInfo();
      }
    });
    tbody.addEventListener('click', e => {
      const tr = e.target.closest('tr');
      if (!tr || e.target.type === 'checkbox') return;
      const chk = tr.querySelector('.row-chk');
      chk.checked = !chk.checked;
      tr.classList.toggle('selected', chk.checked);
      updateSelInfo();
    });
    document.getElementById('chkAll').addEventListener('change', e => {
      document.querySelectorAll('.row-chk').forEach(chk => {
        chk.checked = e.target.checked;
        chk.closest('tr').classList.toggle('selected', e.target.checked);
      });
      updateSelInfo();
    });
    document.getElementById('btnAll').addEventListener('click', () => {
      document.querySelectorAll('.row-chk').forEach(chk => {
        chk.checked = true; chk.closest('tr').classList.add('selected');
      });
      updateSelInfo();
    });
    document.getElementById('btnNone').addEventListener('click', () => {
      document.querySelectorAll('.row-chk').forEach(chk => {
        chk.checked = false; chk.closest('tr').classList.remove('selected');
      });
      updateSelInfo();
    });
    updateSelInfo();
    /* ---- Génération rapport partageable ---- */
    const modalOverlay  = document.getElementById('modalOverlay');
    const modalUrlInput = document.getElementById('modalUrlInput');
    const btnCopy       = document.getElementById('btnCopy');
    const btnOpen       = document.getElementById('btnOpenRapport');
    const btnClose      = document.getElementById('btnModalClose');

    document.getElementById('btnGenPdf').addEventListener('click', async () => {
      const selected = getSelectedRows();
      if (!selected.length) return;
      const btn = document.getElementById('btnGenPdf');
      btn.disabled = true;
      btn.textContent = 'Génération…';
      try {
        const payload = {
          label, surface, surfaceMin, surfaceMax, pieces,
          suggestion,
          estimation: window._currentEst,
          rows: selected.map(r => ({
            adresse:        r.adresse,
            valeur_fonciere: r.valeur_fonciere,
            surface:         r.surface,
            prix_m2:         r.prix_m2,
            nb_pieces:       r.nb_pieces,
            date_mutation:   r.date_mutation,
          })),
        };
        const res  = await fetch('api/save-rapport', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Erreur serveur');
        modalUrlInput.value = data.url;
        btnOpen.href = data.url;
        modalOverlay.classList.add('open');
      } catch (e) {
        alert('Erreur lors de la génération du rapport : ' + e.message);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Générer le rapport';
      }
    });

    btnCopy.addEventListener('click', () => {
      navigator.clipboard.writeText(modalUrlInput.value).then(() => {
        btnCopy.textContent = 'Copié !';
        btnCopy.classList.add('copied');
        setTimeout(() => { btnCopy.textContent = 'Copier'; btnCopy.classList.remove('copied'); }, 2000);
      });
    });
    btnClose.addEventListener('click', () => modalOverlay.classList.remove('open'));
    modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) modalOverlay.classList.remove('open'); });
  }
</script>
</body>
</html>