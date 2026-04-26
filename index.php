<?php $navActive = 'accueil'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Estimatiz – Estimation immobilière en ligne gratuite | Estimer son appartement</title>
  <!-- SEO enhancements -->
  <meta name="description" content="Estimez votre appartement ou maison en ligne gratuitement. Estimation immobilière basée sur les ventes DVF officielles — prix au m², fourchette basse, médiane et haute pour toute la France." />
  <link rel="icon" type="image/x-icon" href="/favicon.ico" />
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32x32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16x16.png" />
  <link rel="canonical" href="https://www.estimatiz.fr/" />
  <!-- Open Graph tags -->
  <meta property="og:title" content="Estimatiz – Estimation immobilière en ligne gratuite" />
  <meta property="og:description" content="Estimez votre appartement ou maison en ligne gratuitement. Basé sur les ventes DVF officielles — prix au m², fourchette basse, médiane et haute pour toute la France." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.estimatiz.fr/" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Estimatiz",
    "url": "https://www.estimatiz.fr/",
    "description": "Outil d'estimation immobilière gratuit basé sur les ventes réelles DVF — France entière 2014–2025.",
    "inLanguage": "fr-FR",
    "potentialAction": {
      "@type": "SearchAction",
      "target": {
        "@type": "EntryPoint",
        "urlTemplate": "https://www.estimatiz.fr/estimation?q={search_term_string}"
      },
      "query-input": "required name=search_term_string"
    }
  }
  </script>
  <link rel="stylesheet" href="assets/css/site.css" />
  <style>
    :root{ --c1:#1E3A8A; --c2:#10B981; --c3:#111827; --c4:#F3F4F6; }
    *{ box-sizing:border-box; }
    body{ margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; background:var(--c4); color:#111827; }

    /* ── Hero ── */
    .hero{ background:linear-gradient(135deg,#1E3A8A 0%,#1e40af 60%,#1d4ed8 100%); color:#fff; padding:64px 24px 56px; text-align:center; }
    .hero h1{ font-size:36px; font-weight:800; margin:0 0 12px; line-height:1.2; }
    .hero p{ font-size:18px; color:rgba(255,255,255,.85); max-width:580px; margin:0 auto 36px; line-height:1.6; }

    /* ── Carte recherche ── */
    .search-card{ background:#fff; border-radius:20px; box-shadow:0 8px 32px rgba(0,0,0,.18); padding:28px; max-width:640px; margin:0 auto; }
    .searchBox{ display:flex; flex-direction:column; gap:14px; position:relative; }
    .searchBox input[type=text]{ padding:14px 16px; font-size:16px; border:1px solid #d1d5db; border-radius:12px; width:100%; }
    .searchBox input[type=text].is-selected{ border-color:var(--c2); box-shadow:0 0 0 3px rgba(16,185,129,.15); }
    .searchBox button{ padding:14px 20px; font-size:16px; font-weight:600; border:none; border-radius:12px; background:var(--c2); color:#fff; cursor:pointer; }
    .searchBox button:hover{ background:#0ea371; }
    button:disabled{ opacity:.65; cursor:not-allowed; }
    .suggestions{ position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #d1d5db; border-radius:12px; margin-top:4px; max-height:220px; overflow-y:auto; z-index:10; text-align:left; display:none; }
    .suggestions div{ padding:8px 12px; cursor:pointer; border-radius:8px; color:#111827; font-size:14px; }
    .suggestions div:hover{ background:#f3f4f6; }
    .search-tips{ display:flex; gap:10px; margin-top:14px; }
    .tip{ flex:1; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; font-size:12px; color:#374151; line-height:1.5; }
    .tip strong{ display:block; font-size:12px; font-weight:700; color:#1E3A8A; margin-bottom:2px; }
    .tip .ex{ color:#6B7280; font-style:italic; }
    .helper{ margin-top:10px; font-size:13px; color:#4B5563; text-align:left; }
    .status{ display:none; margin-top:12px; padding:12px 14px; border-radius:12px; font-size:14px; text-align:left; }
    .status.is-visible{ display:block; }
    .status.info   { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .status.success{ background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    .status.error  { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    @keyframes spin{ to{ transform:rotate(360deg); } }

    /* ── Section principale ── */
    .section{ max-width:1100px; margin:0 auto; padding:56px 24px; }
    .section-title{ font-size:24px; font-weight:800; color:#111827; margin:0 0 6px; }
    .section-sub{ font-size:15px; color:#6B7280; margin:0 0 32px; }

    /* ── Cartes fonctionnalités ── */
    .features{ display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
    .feat-card{ background:#fff; border-radius:16px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,.06); border:1px solid #e5e7eb; transition:box-shadow .2s,transform .2s; }
    .feat-card:hover{ box-shadow:0 6px 24px rgba(0,0,0,.1); transform:translateY(-2px); }
    .feat-card.clickable{ cursor:pointer; text-decoration:none; color:inherit; display:block; }
    .feat-card.soon{ opacity:.65; }
    .feat-icon{ font-size:32px; margin-bottom:12px; }
    .feat-title{ font-size:16px; font-weight:700; color:#111827; margin:0 0 6px; }
    .feat-desc{ font-size:13px; color:#6B7280; line-height:1.6; margin:0; }
    .feat-badge{ display:inline-block; margin-top:10px; font-size:11px; font-weight:700; padding:3px 8px; border-radius:20px; background:#f0fdf4; color:#10B981; border:1px solid #a7f3d0; }
    .feat-badge.soon{ background:#f9fafb; color:#9CA3AF; border-color:#e5e7eb; }

    /* ── Comment ça marche ── */
    .steps{ display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
    .step{ text-align:center; }
    .step-num{ width:44px; height:44px; border-radius:50%; background:var(--c1); color:#fff; font-size:18px; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
    .step-title{ font-size:15px; font-weight:700; margin:0 0 6px; }
    .step-desc{ font-size:13px; color:#6B7280; line-height:1.6; margin:0; }
    .step-arrow{ display:none; }

    /* ── Stats ── */
    .stats-bar{ background:#1E3A8A; color:#fff; padding:36px 24px; }
    .stats-inner{ max-width:1100px; margin:0 auto; display:grid; grid-template-columns:repeat(4,1fr); gap:16px; text-align:center; }
    .stat-val{ font-size:28px; font-weight:800; color:#10B981; }
    .stat-lbl{ font-size:12px; color:rgba(255,255,255,.75); margin-top:4px; }

    /* ── Footer ── */
    footer{ background:#111827; color:rgba(255,255,255,.6); text-align:center; padding:24px; font-size:13px; }
    footer a{ color:rgba(255,255,255,.8); text-decoration:none; }
    footer a:hover{ color:#fff; }

    /* ── Mobile ── */
    @media(max-width:768px){
      .hero{ padding:40px 16px 36px; }
      .hero h1{ font-size:26px; }
      .hero p{ font-size:15px; }
      .features{ grid-template-columns:1fr; }
      .steps{ grid-template-columns:1fr; }
      .stats-inner{ grid-template-columns:repeat(2,1fr); }
      .search-tips{ flex-direction:column; gap:8px; }
      .section{ padding:36px 16px; }
    }
  </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>


  <!-- Hero -->
  <section class="hero">
    <h1>Estimation immobilière en ligne,<br>gratuite et basée sur les ventes réelles</h1>
    <p>Estimez votre appartement ou votre maison en quelques secondes. Fondé sur les ventes DVF officielles — des données publiques, objectives et vérifiables.</p>
    <div class="search-card">
      <div class="searchBox">
        <input id="addressInput" type="text" placeholder="Ex : 12 rue de Rivoli, Paris  ou  rue de Rivoli, Paris" autocomplete="off" />
        <div id="suggestions" class="suggestions"></div>
        <button type="button" id="btnContinuer" disabled>Estimer cette adresse &rarr;</button>
      </div>
      <div class="search-tips">
        <div class="tip">
          <strong>📍 Adresse précise</strong>
          Numéro + rue + ville<br>
          <span class="ex">12 rue de Rivoli, Paris</span><br>
          → ventes au plus proche de votre bien
        </div>
        <div class="tip">
          <strong>🏘️ Rue entière</strong>
          Nom de rue + ville (sans numéro)<br>
          <span class="ex">rue de Rivoli, Paris</span><br>
          → échantillon plus large, plus de références
        </div>
      </div>
      <p class="helper" id="addressHelper">Choisissez une adresse dans la liste pour continuer.</p>
      <div id="formStatus" class="status info">Commencez par saisir une adresse.</div>
    </div>
  </section>

  <!-- Stats -->
  <div class="stats-bar">
    <div class="stats-inner">
      <div><div class="stat-val">13 millions</div><div class="stat-lbl">transactions analysées</div></div>
      <div><div class="stat-val">2014–2025</div><div class="stat-lbl">données DVF couvertes</div></div>
      <div><div class="stat-val">101</div><div class="stat-lbl">départements couverts</div></div>
      <div><div class="stat-val">100%</div><div class="stat-lbl">données officielles</div></div>
    </div>
  </div>

  <!-- Fonctionnalités -->
  <section class="section">
    <p class="section-title">Ce que vous pouvez faire</p>
    <p class="section-sub">Des outils fondés sur les données DVF de la Direction Générale des Finances Publiques.</p>
    <div class="features">

      <a class="feat-card clickable" href="estimation">
        <div class="feat-icon">🏠</div>
        <div class="feat-title">Estimer un bien</div>
        <p class="feat-desc">Obtenez une fourchette basse / médiane / haute basée sur les ventes réelles autour de votre adresse.</p>
        <span class="feat-badge">Disponible</span>
      </a>

      <a class="feat-card clickable" href="prix-m2">
        <div class="feat-icon">📊</div>
        <div class="feat-title">Prix au m²</div>
        <p class="feat-desc">Consultez les prix moyens et médians par arrondissement, quartier ou rue, avec filtres par période.</p>
        <span class="feat-badge">Disponible</span>
      </a>

      <a class="feat-card clickable" href="ventes">
        <div class="feat-icon">📋</div>
        <div class="feat-title">Dernières ventes</div>
        <p class="feat-desc">Parcourez les transactions récentes autour d'une adresse : prix, surface, date et type de bien.</p>
        <span class="feat-badge">Disponible</span>
      </a>

    </div>
  </section>

  <!-- Comment ça marche -->
  <section class="section" style="background:#fff; max-width:100%; padding-left:0; padding-right:0;">
    <div style="max-width:1100px; margin:0 auto; padding:56px 24px;">
      <p class="section-title" style="text-align:center;">Comment ça marche ?</p>
      <p class="section-sub" style="text-align:center;">Une estimation en 3 étapes, transparente et reproductible.</p>
      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-title">Saisissez votre adresse</div>
          <p class="step-desc">Entrez une adresse précise ou le nom d'une rue. L'autocomplétion vous guide vers les données disponibles.</p>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-title">Affinez les critères</div>
          <p class="step-desc">Renseignez la surface du bien et ajustez les filtres : surface min/max des comparables, nombre de pièces.</p>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-title">Analysez les résultats</div>
          <p class="step-desc">Visualisez les ventes comparables et votre estimation P20/médiane/P80. Exportez en PDF en un clic.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Bloc SEO -->
  <section class="section">
    <div style="max-width:800px;">
      <h2 class="section-title">Quelle valeur pour votre appartement ou votre maison ?</h2>
      <p style="font-size:15px;color:#374151;line-height:1.8;margin:16px 0;">
        L'<strong>estimation immobilière en ligne</strong> permet à tout propriétaire ou acheteur de connaître la valeur d'un bien sans passer par une agence. Estimatiz calcule une <strong>estimation de votre bien</strong> à partir des <strong>ventes immobilières réelles</strong> enregistrées dans votre rue ou votre quartier — pas d'algorithme opaque, pas de données commerciales.
      </p>
      <p style="font-size:15px;color:#374151;line-height:1.8;margin:16px 0;">
        Que vous souhaitiez <strong>estimer votre appartement</strong>, évaluer une maison avant une vente ou simplement suivre l'évolution des prix dans votre secteur, Estimatiz vous donne accès aux mêmes données que les professionnels de l'immobilier : les <strong>Demandes de Valeurs Foncières (DVF)</strong> publiées par l'État, issues des actes notariés.
      </p>
      <p style="font-size:15px;color:#374151;line-height:1.8;margin:16px 0;">
        Pour chaque <strong>estimation en ligne</strong>, vous obtenez une fourchette basse, médiane et haute basée sur les transactions comparables, accompagnée d'un indice de confiance et de la liste des ventes utilisées. Idéal pour préparer une négociation, fixer un prix de vente ou vérifier une offre d'achat.
      </p>
      <p style="margin-top:24px;">
        <a href="estimation" style="display:inline-block;background:#1E3A8A;color:#fff;padding:13px 28px;border-radius:12px;font-weight:700;text-decoration:none;font-size:15px;">Estimer mon bien gratuitement →</a>
      </p>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; France 2014–2025
  </footer>

  <script src="assets/js/utils.js"></script>
  <script>
    const input         = document.getElementById('addressInput');
    const suggestions   = document.getElementById('suggestions');
    const btnContinuer  = document.getElementById('btnContinuer');
    const formStatus    = document.getElementById('formStatus');
    const addressHelper = document.getElementById('addressHelper');

    let selectedSuggestion = null;
    const acCache = {};

    function showStatus(message, type = 'info') {
      EstimatizUtils.setStatus(formStatus, message, type);
    }

    function setSelectionState(isSelected) {
      input.classList.toggle('is-selected', isSelected);
      btnContinuer.disabled = !isSelected;
      addressHelper.textContent = isSelected
        ? "Adresse sélectionnée. Cliquez sur Estimer pour continuer."
        : "Choisissez une adresse dans la liste pour continuer.";
    }

    function buildEstimationUrl(s) {
      return 'estimation?' + new URLSearchParams({
        label:     s.label     || '',
        code_voie: s.code_voie || '',
        commune:   s.commune   || '',
        cp:        s.cp        || '',
        type_voie: s.type_voie || '',
        voie:      s.voie      || '',
        no_voie:   s.no_voie   || '',
        btq:       s.btq       || '',
        section:   s.section   || '',
      }).toString();
    }

    async function fetchSuggestions(q) {
      const r = await fetch('api/autocomplete.php?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    }

    function render(items) {
      suggestions.innerHTML = '';
      if (!items || !items.length) {
        suggestions.style.display = 'none';
        showStatus("Aucune adresse trouvée. Essayez avec plus d'informations.", 'error');
        return;
      }
      items.forEach(it => {
        const div = document.createElement('div');
        div.textContent = it.label;
        div.onclick = () => {
          input.value = it.label;
          selectedSuggestion = it;
          suggestions.style.display = 'none';
          setSelectionState(true);
          showStatus('Adresse sélectionnée. Cliquez sur Estimer pour continuer.', 'success');
        };
        suggestions.appendChild(div);
      });
      suggestions.style.display = 'block';
      showStatus(`${items.length} adresse${items.length > 1 ? 's' : ''} trouvée${items.length > 1 ? 's' : ''}. Sélectionnez celle qui vous correspond.`, 'info');
    }

    let t;
    input.addEventListener('input', e => {
      const v = e.target.value.trim();
      selectedSuggestion = null;
      setSelectionState(false);
      clearTimeout(t);
      if (v.length < 2) {
        suggestions.style.display = 'none';
        showStatus(v.length === 0 ? 'Commencez par saisir une adresse.' : 'Ajoutez encore quelques caractères pour lancer la recherche.', 'info');
        return;
      }
      t = setTimeout(async () => {
        try {
          if (acCache[v]) { render(acCache[v]); return; }
          const items = await fetchSuggestions(v);
          acCache[v] = items;
          render(items);
        } catch(err) {
          console.error(err);
          suggestions.style.display = 'none';
          showStatus('Impossible de récupérer les suggestions pour le moment.', 'error');
        }
      }, 250);
    });

    document.addEventListener('click', e => {
      if (!e.target.closest('.searchBox')) suggestions.style.display = 'none';
    });

    btnContinuer.addEventListener('click', () => {
      if (!selectedSuggestion) {
        showStatus("Sélectionnez d'abord une adresse dans la liste.", 'error');
        return;
      }
      window.location.href = buildEstimationUrl(selectedSuggestion);
    });

    setSelectionState(false);
    showStatus('Commencez par saisir une adresse.', 'info');
  </script>
</body>
</html>