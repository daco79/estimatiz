<?php $navActive = 'estimer'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estimatiz – Estimer un bien</title>
  <!-- SEO enhancements -->
  <meta name="description" content="Estimez votre bien immobilier en quelques clics en utilisant les ventes réelles et les données DVF. Ajustez la surface et les critères pour obtenir une estimation personnalisée." />
  <link rel="canonical" href="https://www.estimatiz.fr/estimation" />
  <!-- Open Graph tags -->
  <meta property="og:title" content="Estimatiz – Estimer un bien" />
  <meta property="og:description" content="Estimez votre bien immobilier en quelques clics en utilisant les ventes réelles et les données DVF. Ajustez la surface et les critères pour obtenir une estimation personnalisée." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.estimatiz.fr/estimation" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />
  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
    {"@type":"ListItem","position":1,"name":"Accueil","item":"https://www.estimatiz.fr/"},
    {"@type":"ListItem","position":2,"name":"Estimer un bien","item":"https://www.estimatiz.fr/estimation"}
  ]}
  </script>
  <link rel="stylesheet" href="assets/css/site.css" />
  <style>
    :root{ --c1:#1E3A8A; --c2:#10B981; --c3:#111827; --c4:#E5E7EB; }
    *{ box-sizing:border-box; }
    body{ margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; background:var(--c4); color:#111827; min-height:100vh; }

    /* ── Hero recherche ── */
    .search-hero{ background:linear-gradient(135deg,#1E3A8A 0%,#1e40af 60%,#1d4ed8 100%); color:#fff; padding:36px 24px 32px; text-align:center; }
    .search-hero h1{ font-size:22px; font-weight:800; margin:0 0 8px; }
    .search-card{ background:#fff; border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,.18); padding:18px 20px; max-width:560px; margin:0 auto; }
    .srch-box{ display:flex; flex-direction:column; gap:10px; position:relative; }
    .srch-box input{ padding:11px 14px; font-size:15px; border:1px solid #d1d5db; border-radius:10px; width:100%; font-family:inherit; }
    .srch-box input:focus{ outline:none; border-color:var(--c1); box-shadow:0 0 0 3px rgba(30,58,138,.12); }
    .srch-box input.is-selected{ border-color:var(--c2); box-shadow:0 0 0 3px rgba(16,185,129,.15); }
    .srch-box button{ padding:11px 18px; font-size:15px; font-weight:600; border:none; border-radius:10px; background:var(--c2); color:#fff; cursor:pointer; font-family:inherit; }
    .srch-box button:hover{ background:#0ea371; }
    .srch-suggestions{ position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #d1d5db; border-radius:10px; margin-top:4px; max-height:220px; overflow-y:auto; z-index:20; text-align:left; display:none; box-shadow:0 4px 16px rgba(0,0,0,.1); }
    .srch-suggestions div{ padding:8px 12px; cursor:pointer; border-radius:8px; color:#111827; font-size:14px; }
    .srch-suggestions div:hover{ background:#f3f4f6; }
    .srch-hint{ display:none; margin-top:8px; padding:8px 12px; border-radius:8px; font-size:13px; text-align:left; }
    .srch-hint.is-visible{ display:block; }
    .srch-hint.info   { background:#eff6ff; color:#1d4ed8; }
    .srch-hint.success{ background:#ecfdf5; color:#047857; }
    .srch-hint.error  { background:#fef2f2; color:#b91c1c; }

    /* ── Bandeau adresse + lien retour ── */
    .addr-bar{ background:#fff; border-bottom:1px solid #e5e7eb; padding:0 24px; height:44px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .addr-bar-label{ font-size:13px; font-weight:600; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .nav-back{ font-size:12px; font-weight:600; color:#6B7280; text-decoration:none; display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:8px; border:1px solid #e5e7eb; background:#f9fafb; white-space:nowrap; flex-shrink:0; }
    .nav-back:hover{ background:#f3f4f6; color:#111827; }

    /* ── Wrap ── */
    .wrap{ max-width:720px; margin:0 auto; padding:28px 20px 48px; }

    /* ── Card ── */
    .card{ background:#fff; border-radius:20px; box-shadow:0 6px 24px rgba(0,0,0,.08); padding:24px; }

    /* ── Status ── */
    .status{ display:none; margin-bottom:16px; padding:12px 14px; border-radius:12px; font-size:14px; }
    .status.is-visible{ display:block; }
    .status.info{ background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .status.success{ background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    .status.error{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .loading-spin{ display:inline-block; width:13px; height:13px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
    @keyframes spin{ to{ transform:rotate(360deg); } }

    /* ── Blocs ── */
    .section-block{ border-radius:14px; padding:16px 18px; margin-bottom:14px; }
    .section-block.bien{ background:#f0fdf4; border:1px solid #a7f3d0; }
    .section-block.filtres{ background:#eff6ff; border:1px solid #bfdbfe; }
    .section-title{ font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin:0 0 3px; }
    .section-block.bien    .section-title{ color:#047857; }
    .section-block.filtres .section-title{ color:#1d4ed8; }
    .section-desc{ font-size:12px; color:#6B7280; margin:0 0 12px; }

    /* ── Rows ── */
    .row{ display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-top:10px; }
    label{ font-size:14px; color:#374151; }
    select, input[type=number]{ padding:12px 14px; border-radius:12px; border:1px solid #d1d5db; min-width:200px; font-size:14px; background:#fff; }
    .note{ margin-top:8px; font-size:12px; color:#6B7280; }

    /* ── Boutons ── */
    .btn-reset{ padding:10px 14px; border-radius:10px; border:1px solid #bfdbfe; background:#fff; color:#1d4ed8; cursor:pointer; font-size:13px; font-weight:600; }
    .btn-reset:hover{ background:#eff6ff; }
    .btn-visualiser{ width:100%; padding:16px 20px; font-size:17px; font-weight:700; border:none; border-radius:14px; background:var(--c2); color:#fff; cursor:pointer; box-shadow:0 4px 14px rgba(16,185,129,.35); display:none; margin-top:20px; }
    .btn-visualiser:hover{ background:#0ea371; }
    button:disabled{ opacity:.65; cursor:not-allowed; }
    .btn-loading{ display:inline-block; width:14px; height:14px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }

    /* ── Mobile ── */
    @media (max-width:600px){
      .search-hero{ padding:24px 16px 20px; }
      .search-hero h1{ font-size:18px; }
      .search-card{ padding:14px 16px; }
      .wrap{ padding:16px 12px 40px; }
      .section-block{ padding:14px; }
      .row{ flex-direction:column; align-items:stretch; gap:6px; }
      label{ font-size:13px; }
      select, input[type=number]{ min-width:unset; width:100%; padding:11px 12px; }
      .btn-reset{ width:100%; text-align:center; }
      .btn-visualiser{ font-size:15px; padding:14px; }
    }
  </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <!-- ── Barre de recherche ── -->
  <section class="search-hero">
    <h1>Estimer votre bien immobilier</h1>
    <div class="search-card">
      <div class="srch-box">
        <input id="srchInput" type="text" placeholder="Ex : 12 rue de Rivoli, Paris" autocomplete="off" />
        <div id="srchSuggestions" class="srch-suggestions"></div>
        <button type="button" id="srchBtn" disabled>Estimer cette adresse &rarr;</button>
      </div>
      <div id="srchHint" class="srch-hint info is-visible">Saisissez une adresse pour commencer.</div>
    </div>
  </section>

  <!-- Bandeau adresse -->
  <div class="addr-bar">
    <span class="addr-bar-label">📍 <span id="addrBarLabel">Chargement…</span></span>
    <a class="nav-back" href="index.php">&#8592; Accueil</a>
  </div>

  <div class="wrap">
    <div class="card">
      <div id="formStatus" class="status"></div>
      <!-- Bloc 1 : Votre bien -->
      <div class="section-block bien">
        <p class="section-title">Votre bien</p>
        <p class="section-desc">Surface du logement que vous souhaitez estimer.</p>
        <div class="row">
          <label for="surfaceInput">Surface (m²) :</label>
          <input id="surfaceInput" type="number" inputmode="decimal" min="5" max="500" step="1" placeholder="ex : 65" />
        </div>
        <p class="note" id="surfaceHint">Surface médiane de la rue : n.c.</p>
      </div>
      <!-- Bloc 2 : Filtres des comparables -->
      <div class="section-block filtres">
        <p class="section-title">Filtres des ventes comparables</p>
        <p class="section-desc">Nous rechercherons dans la base DVF les ventes correspondant à ces critères pour calculer l'estimation.</p>
        <div class="row">
          <label for="surfaceMinInput">Surface min (m²) :</label>
          <input id="surfaceMinInput" type="number" inputmode="decimal" min="5" max="500" step="1" placeholder="ex : 35" />
        </div>
        <div class="row">
          <label for="surfaceMaxInput">Surface max (m²) :</label>
          <input id="surfaceMaxInput" type="number" inputmode="decimal" min="5" max="500" step="1" placeholder="ex : 45" />
        </div>
        <div class="row">
          <label for="piecesInput">Nombre de pièces :</label>
          <select id="piecesInput">
            <option value="">Tous</option>
            <option value="1">1 pièce</option>
            <option value="2">2 pièces</option>
            <option value="3">3 pièces</option>
            <option value="4">4 pièces</option>
            <option value="5">5 pièces</option>
            <option value="6">6 pièces et +</option>
          </select>
        </div>
        <div class="row" style="justify-content:flex-start; margin-top:14px;">
          <button type="button" id="btnReset" class="btn-reset">Réinitialiser les filtres</button>
        </div>
      </div>
      <!-- Bouton Visualiser -->
      <button type="button" id="btnVisualiser" class="btn-visualiser">Visualiser les résultats</button>
    </div>
  </div>

  <script src="assets/js/utils.js"></script>
  <script>
    /* ── Autocomplete barre de recherche ── */
    (function() {
      const inp  = document.getElementById('srchInput');
      const sugg = document.getElementById('srchSuggestions');
      const btn  = document.getElementById('srchBtn');
      const hint = document.getElementById('srchHint');
      let selected = null, t;
      function setHint(msg, type) { hint.className = `srch-hint is-visible ${type}`; hint.textContent = msg; }
      inp.addEventListener('input', () => {
        const v = inp.value.trim();
        selected = null; btn.disabled = true; inp.classList.remove('is-selected');
        clearTimeout(t);
        if (v.length < 2) {
          sugg.style.display = 'none';
          setHint(v.length === 0 ? 'Saisissez une adresse pour commencer.' : 'Ajoutez encore quelques caractères…', 'info');
          return;
        }
        t = setTimeout(async () => {
          try {
            setHint('Recherche en cours…', 'info');
            const data = await fetch('api/autocomplete.php?q=' + encodeURIComponent(v), { headers: { Accept: 'application/json' } }).then(r => r.json());
            sugg.innerHTML = '';
            if (!data?.length) { sugg.style.display = 'none'; setHint('Aucune adresse trouvée.', 'error'); return; }
            data.forEach(it => {
              const d = document.createElement('div');
              d.textContent = it.label;
              d.addEventListener('click', () => {
                inp.value = it.label; selected = it; sugg.style.display = 'none';
                inp.classList.add('is-selected'); btn.disabled = false;
                setHint('Adresse sélectionnée. Cliquez sur Estimer pour continuer.', 'success');
              });
              sugg.appendChild(d);
            });
            sugg.style.display = 'block';
            setHint(`${data.length} adresse${data.length > 1 ? 's' : ''} trouvée${data.length > 1 ? 's' : ''}. Sélectionnez celle qui vous correspond.`, 'info');
          } catch { sugg.style.display = 'none'; setHint('Impossible de récupérer les suggestions.', 'error'); }
        }, 250);
      });
      document.addEventListener('click', e => { if (!e.target.closest('.srch-box')) sugg.style.display = 'none'; });
      btn.addEventListener('click', () => {
        if (!selected) return;
        const p = new URLSearchParams({
          label: selected.label || '', code_voie: selected.code_voie || '',
          commune: selected.commune || '', cp: selected.cp || '',
          type_voie: selected.type_voie || '', voie: selected.voie || '',
          no_voie: selected.no_voie || '', btq: selected.btq || '', section: selected.section || ''
        });
        window.location.href = 'estimation?' + p;
      });
    })();

    /* ── Formulaire estimation ── */
    const params = new URLSearchParams(location.search);
    const suggestion = {
      label:     params.get('label')     || '',
      code_voie: params.get('code_voie') || '',
      commune:   params.get('commune')   || '',
      cp:        params.get('cp')        || '',
      type_voie: params.get('type_voie') || '',
      voie:      params.get('voie')      || '',
      no_voie:   params.get('no_voie')   || '',
      btq:       params.get('btq')       || '',
      section:   params.get('section')   || '',
    };
    if (!suggestion.code_voie && !suggestion.commune) {
      document.getElementById('addrBarLabel').textContent = 'Aucune adresse sélectionnée.';
      document.getElementById('formStatus').className = 'status is-visible info';
      document.getElementById('formStatus').textContent = 'Utilisez la barre de recherche ci-dessus pour sélectionner une adresse.';
    } else {
      // Pré-remplir la barre de recherche avec l'adresse courante
      document.getElementById('srchInput').value = suggestion.label;
      document.title = `Estimatiz – ${suggestion.label}`;
      document.getElementById('addrBarLabel').textContent = suggestion.label;
      const formStatus      = document.getElementById('formStatus');
      const surfaceInput    = document.getElementById('surfaceInput');
      const surfaceHint     = document.getElementById('surfaceHint');
      const surfaceMinInput = document.getElementById('surfaceMinInput');
      const surfaceMaxInput = document.getElementById('surfaceMaxInput');
      const piecesInput     = document.getElementById('piecesInput');
      const btnReset        = document.getElementById('btnReset');
      const btnVisualiser   = document.getElementById('btnVisualiser');
      function showStatus(message, type = 'info') {
        EstimatizUtils.setStatus(formStatus, message, type, true);
      }
      function setLoading(btn, loading, labelIdle) {
        EstimatizUtils.setLoading(btn, loading, labelIdle);
      }
      async function fetchSurface(pieces = '') {
        const p = new URLSearchParams({
          code_voie: suggestion.code_voie, commune: suggestion.commune,
          no_voie: suggestion.no_voie, btq: suggestion.btq,
          type_voie: suggestion.type_voie, voie: suggestion.voie,
          cp: suggestion.cp, section: suggestion.section,
        });
        if (pieces) p.set('pieces', pieces);
        const r = await fetch('api/surface.php?' + p, { headers: { Accept: 'application/json' } });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      }
      function applySurfaceData(data) {
        if (data?.ok && data.surface) {
          surfaceInput.value = data.surface;
          if (data.stats && Number.isFinite(data.stats.p20) && Number.isFinite(data.stats.p80)) {
            surfaceMinInput.value = Math.round(data.stats.p20);
            surfaceMaxInput.value = Math.round(data.stats.p80);
          }
          surfaceHint.textContent = `Surface médiane de la rue : ${data.surface} m²`;
          btnVisualiser.style.display = 'block';
          showStatus('Surface indicative récupérée. Ajustez les filtres si besoin puis visualisez les résultats.', 'success');
        } else {
          surfaceHint.textContent = 'Surface médiane de la rue : n.c.';
          btnVisualiser.style.display = 'block';
          showStatus('Aucune surface indicative trouvée pour cette adresse. Saisissez une surface manuellement.', 'info');
        }
      }
      btnReset.addEventListener('click', () => {
        surfaceInput.value = ''; surfaceMinInput.value = ''; surfaceMaxInput.value = ''; piecesInput.value = '';
        surfaceHint.textContent = 'Surface médiane de la rue : n.c.';
        showStatus('Filtres réinitialisés.', 'info');
      });
      btnVisualiser.addEventListener('click', async () => {
        const surface = (surfaceInput.value || '').trim();
        let surfaceMin = (surfaceMinInput.value || '').trim();
        let surfaceMax = (surfaceMaxInput.value || '').trim();
        const pieces   = piecesInput.value;
        if (surfaceMin && surfaceMax && Number(surfaceMin) > Number(surfaceMax)) {
          [surfaceMin, surfaceMax] = [surfaceMax, surfaceMin];
        }
        setLoading(btnVisualiser, true, 'Visualiser les résultats');
        const baseParams = new URLSearchParams({
          code_voie: suggestion.code_voie, commune: suggestion.commune,
          section: suggestion.section, type_voie: suggestion.type_voie,
          voie: suggestion.voie, no_voie: suggestion.no_voie, btq: suggestion.btq,
          surface_min: surfaceMin, surface_max: surfaceMax,
        });
        if (pieces) baseParams.set('pieces', pieces);
        let mutData;
        try {
          mutData = await fetch('api/mutations.php?' + baseParams, { headers: { Accept: 'application/json' } }).then(r => r.json());
        } catch(e) {
          console.error(e);
          showStatus('Impossible de récupérer les données. Vérifiez votre connexion.', 'error');
          setLoading(btnVisualiser, false, 'Visualiser les résultats');
          return;
        }
        const rows = Array.isArray(mutData?.rows) ? mutData.rows : [];
        if (!rows.length) {
          showStatus("Aucune mutation trouvée pour ces critères. Essayez d'élargir les filtres.", 'error');
          setLoading(btnVisualiser, false, 'Visualiser les résultats');
          return;
        }
        sessionStorage.setItem('estimatiz_results', JSON.stringify({
          label: suggestion.label, surface, surfaceMin, surfaceMax, pieces, mutData, suggestion,
        }));
        showStatus(`${rows.length} résultat${rows.length > 1 ? 's' : ''} trouvé${rows.length > 1 ? 's' : ''}. Ouverture…`, 'success');
        window.location.href = 'results';
      });
      /* ── Restauration si retour depuis results.php ── */
      const _saved = sessionStorage.getItem('estimatiz_results');
      if (_saved) {
        try {
          const _d = JSON.parse(_saved);
          if (_d.suggestion && _d.suggestion.code_voie === suggestion.code_voie && _d.suggestion.commune === suggestion.commune) {
            if (_d.surface)    surfaceInput.value    = _d.surface;
            if (_d.surfaceMin) surfaceMinInput.value = _d.surfaceMin;
            if (_d.surfaceMax) surfaceMaxInput.value = _d.surfaceMax;
            if (_d.pieces)     piecesInput.value     = _d.pieces;
            surfaceHint.textContent = _d.surface ? `Surface médiane de la rue : ${_d.surface} m²` : 'Surface médiane de la rue : n.c.';
            btnVisualiser.style.display = 'block';
            showStatus("Formulaire restauré. Modifiez les filtres ou relancez l'estimation.", 'success');
          } else {
            fetchSurface().then(applySurfaceData).catch(() => applySurfaceData(null));
          }
        } catch {
          fetchSurface().then(applySurfaceData).catch(() => applySurfaceData(null));
        }
      } else {
        fetchSurface().then(applySurfaceData).catch(() => applySurfaceData(null));
      }
    }
  </script>
</body>
</html>