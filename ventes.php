<?php
$navActive = 'ventes';
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
  <title>Dernières ventes immobilières – Estimatiz</title>
  <meta name="description" content="Consultez les dernières ventes immobilières en France (DVF 2014–2025). Filtrez par type de bien, département, nombre de pièces et surface." />
  <link rel="canonical" href="https://www.estimatiz.fr/ventes" />
  <meta property="og:title" content="Dernières ventes immobilières – Estimatiz" />
  <meta property="og:description" content="Consultez les dernières ventes immobilières en France (DVF 2014–2025). Filtrez par type de bien, département, nombre de pièces et surface." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.estimatiz.fr/ventes" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />
  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
    {"@type":"ListItem","position":1,"name":"Accueil","item":"https://www.estimatiz.fr/"},
    {"@type":"ListItem","position":2,"name":"Dernières ventes","item":"https://www.estimatiz.fr/ventes"}
  ]}
  </script>
  <link rel="stylesheet" href="assets/css/site.css"/>
  <style>
    :root{ --c1:#1E3A8A; --c2:#10B981; --c4:#F3F4F6; }
    *{ box-sizing:border-box; }
    body{ margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; background:var(--c4); color:#111827; }

    /* ── Hero ── */
    .hero{ background:linear-gradient(135deg,#1E3A8A 0%,#1e40af 60%,#1d4ed8 100%); color:#fff; padding:44px 24px 36px; text-align:center; }
    .hero h1{ font-size:28px; font-weight:800; margin:0 0 8px; }
    .hero p{ font-size:15px; color:rgba(255,255,255,.82); max-width:520px; margin:0 auto; line-height:1.6; }

    /* ── Filtres ── */
    .filters-bar{ background:#fff; border-bottom:1px solid #e5e7eb; padding:14px 24px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .filters-bar label{ font-size:13px; font-weight:600; color:#374151; white-space:nowrap; }
    .filters-bar select, .filters-bar input[type=number]{
      padding:7px 10px; font-size:13px; border:1px solid #d1d5db; border-radius:8px;
      background:#fff; color:#111827; font-family:inherit;
    }
    .filter-group{ display:flex; align-items:center; gap:6px; }
    .sep{ color:#d1d5db; }
    .btn-filter{ padding:8px 16px; font-size:13px; font-weight:700; border:none; border-radius:8px; background:var(--c1); color:#fff; cursor:pointer; }
    .btn-filter:hover{ background:#1e40af; }
    #fDep{ min-width:180px; }
    .surf-input{ width:72px; }

    /* ── Wrap ── */
    .wrap{ max-width:1100px; margin:0 auto; padding:28px 20px 60px; }

    /* ── Compteur ── */
    .result-info{ font-size:13px; color:#6B7280; margin-bottom:16px; min-height:20px; }

    /* ── Grille de cartes ── */
    .cards{ display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }

    /* ── Carte vente ── */
    .card{ background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:16px 18px; box-shadow:0 1px 6px rgba(0,0,0,.05); display:flex; flex-direction:column; gap:10px; transition:box-shadow .15s,border-color .15s; }
    .card:hover{ box-shadow:0 4px 16px rgba(0,0,0,.1); border-color:#bfdbfe; }

    .card-top{ display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .type-badge{ font-size:11px; font-weight:700; padding:3px 9px; border-radius:20px; white-space:nowrap; }
    .type-appt{ background:#eff6ff; color:#1d4ed8; }
    .type-mais{ background:#f0fdf4; color:#166534; }
    .type-other{ background:#f3f4f6; color:#374151; }
    .card-date{ font-size:12px; color:#9CA3AF; }

    .card-adresse{ font-size:14px; font-weight:700; color:#111827; line-height:1.3; }
    .card-commune{ font-size:13px; color:#6B7280; margin-top:1px; }

    .card-stats{ display:flex; gap:16px; flex-wrap:wrap; padding-top:8px; border-top:1px solid #f3f4f6; }
    .stat{ display:flex; flex-direction:column; }
    .stat-val{ font-size:16px; font-weight:800; color:#111827; }
    .stat-val.prix{ color:var(--c1); }
    .stat-lbl{ font-size:10px; color:#9CA3AF; text-transform:uppercase; letter-spacing:.04em; margin-top:1px; }

    /* ── Sentinel / loader ── */
    #sentinel{ height:40px; display:flex; align-items:center; justify-content:center; margin-top:24px; }
    .spin{ display:inline-block; width:22px; height:22px; border:3px solid #e5e7eb; border-top-color:var(--c1); border-radius:50%; animation:spin .7s linear infinite; }
    @keyframes spin{ to{ transform:rotate(360deg); } }
    .end-msg{ font-size:13px; color:#9CA3AF; text-align:center; padding:24px 0; }

    /* ── Empty / error ── */
    .msg-box{ background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:40px 24px; text-align:center; color:#6B7280; font-size:14px; }

    /* ── Mobile ── */
    @media(max-width:640px){
      .filters-bar{ padding:12px 14px; }
      .hero{ padding:32px 16px 24px; }
      .hero h1{ font-size:22px; }
      .cards{ grid-template-columns:1fr; }
      #fDep{ min-width:120px; }
    }

    /* ── Footer ── */
    footer{ background:#111827; color:rgba(255,255,255,.6); text-align:center; padding:24px; font-size:13px; }
    footer a{ color:rgba(255,255,255,.8); text-decoration:none; }
  </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <div class="hero">
    <h1>Dernières ventes</h1>
    <p>Transactions immobilières enregistrées au DVF — France entière, 2014–2025.</p>
  </div>

  <div class="filters-bar">
    <div class="filter-group">
      <label for="fType">Type</label>
      <select id="fType">
        <option value="">Tous</option>
        <option value="Appartement">Appartement</option>
        <option value="Maison">Maison</option>
      </select>
    </div>
    <div class="filter-group">
      <label for="fDep">Département</label>
      <select id="fDep">
        <option value="">France entière</option>
        <?php foreach ($depts as $code => $label): ?>
        <option value="<?= $code ?>"><?= $code ?> – <?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
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
      <label>Surface</label>
      <input class="surf-input" type="number" id="fSurfMin" placeholder="min" min="5" max="1000" step="1"/>
      <span class="sep">–</span>
      <input class="surf-input" type="number" id="fSurfMax" placeholder="max" min="5" max="1000" step="1"/>
      <span style="font-size:12px;color:#6B7280;">m²</span>
    </div>
    <div class="filter-group">
      <label>Période</label>
      <select id="fAnneeMin">
        <?php for($y=2014;$y<=2025;$y++) echo "<option value='$y'" . ($y===2020?' selected':'') . ">$y</option>"; ?>
      </select>
      <span class="sep">→</span>
      <select id="fAnneeMax">
        <?php for($y=2014;$y<=2025;$y++) echo "<option value='$y'" . ($y===2025?' selected':'') . ">$y</option>"; ?>
      </select>
    </div>
    <button class="btn-filter" id="btnAppliquer">Appliquer</button>
  </div>

  <div class="wrap">
    <div class="result-info" id="resultInfo"></div>
    <div class="cards" id="cardList"></div>
    <div id="sentinel"><span class="spin"></span></div>
  </div>

  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; France 2014–2025
  </footer>

<script>
const fmt  = v => v == null ? '—' : new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' €';
const fmtK = v => v == null ? '—' : new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' €/m²';
const numFr = n => new Intl.NumberFormat('fr-FR').format(n);

const MOIS = ['jan.','fév.','mar.','avr.','mai','juin','juil.','août','sep.','oct.','nov.','déc.'];
function fmtDate(s) {
  if (!s) return '—';
  const [y, m, d] = s.split('-');
  return `${parseInt(d)} ${MOIS[parseInt(m)-1]} ${y}`;
}

function typeBadge(t) {
  if (!t) return `<span class="type-badge type-other">—</span>`;
  if (t === 'Appartement') return `<span class="type-badge type-appt">Appartement</span>`;
  if (t === 'Maison')      return `<span class="type-badge type-mais">Maison</span>`;
  return `<span class="type-badge type-other">${t}</span>`;
}

function buildCard(v) {
  const piecesStr = v.pieces ? ` · ${v.pieces} p.` : '';
  const surfStr   = v.surface ? numFr(v.surface) + ' m²' : '—';
  const srcLabel  = v.surf_src === 'carrez' ? 'Carrez' : 'Surface';

  return `<div class="card">
    <div class="card-top">
      ${typeBadge(v.type)}${piecesStr ? `<span style="font-size:12px;color:#6B7280;">${v.pieces} pièce${v.pieces>1?'s':''}</span>` : ''}
      <span class="card-date">${fmtDate(v.date)}</span>
    </div>
    <div>
      <div class="card-adresse">${v.adresse || '—'}</div>
      <div class="card-commune">${v.commune || '—'}${v.cp ? ' <span style="color:#9CA3AF;">' + v.cp + '</span>' : ''}</div>
    </div>
    <div class="card-stats">
      <div class="stat">
        <span class="stat-val">${fmt(v.valeur)}</span>
        <span class="stat-lbl">Prix de vente</span>
      </div>
      <div class="stat">
        <span class="stat-val">${surfStr}</span>
        <span class="stat-lbl">${srcLabel}</span>
      </div>
      ${v.prix_m2 ? `<div class="stat">
        <span class="stat-val prix">${fmtK(v.prix_m2)}</span>
        <span class="stat-lbl">Prix /m²</span>
      </div>` : ''}
    </div>
  </div>`;
}

// ── État ──────────────────────────────────────────────────────────────────────

let nextCursor = null;
let loading    = false;
let hasMore    = true;
let totalLoaded = 0;
let observer   = null;

// ── Params ────────────────────────────────────────────────────────────────────

function getParams(cursor) {
  const p = new URLSearchParams();
  const type     = document.getElementById('fType').value;
  const dep      = document.getElementById('fDep').value;
  const pieces   = document.getElementById('fPieces').value;
  const surfMin  = document.getElementById('fSurfMin').value;
  const surfMax  = document.getElementById('fSurfMax').value;
  const anneeMin = document.getElementById('fAnneeMin').value;
  const anneeMax = document.getElementById('fAnneeMax').value;

  p.set('limit', '20');
  if (type)     p.set('type_local', type);
  if (dep)      p.set('dep', dep);
  if (pieces)   p.set('pieces', pieces);
  if (surfMin)  p.set('surface_min', surfMin);
  if (surfMax)  p.set('surface_max', surfMax);
  if (anneeMin) p.set('annee_min', anneeMin);
  if (anneeMax) p.set('annee_max', anneeMax);
  if (cursor)   p.set('cursor', cursor);
  return p;
}

// ── Chargement ────────────────────────────────────────────────────────────────

async function loadMore() {
  if (loading || !hasMore) return;
  loading = true;

  const sentinel = document.getElementById('sentinel');
  sentinel.innerHTML = '<span class="spin"></span>';

  try {
    const r = await fetch('api/ventes.php?' + getParams(nextCursor), { headers:{ Accept:'application/json' } });
    const d = await r.json();

    if (!d.ok) { sentinel.innerHTML = `<span style="color:#b91c1c;font-size:13px;">${d.error}</span>`; return; }

    const list = document.getElementById('cardList');

    if (d.ventes.length === 0 && totalLoaded === 0) {
      list.innerHTML = '<div class="msg-box" style="grid-column:1/-1;">Aucune vente trouvée pour ces critères.</div>';
      sentinel.innerHTML = '';
      return;
    }

    list.insertAdjacentHTML('beforeend', d.ventes.map(buildCard).join(''));
    totalLoaded += d.ventes.length;
    nextCursor   = d.next_cursor;
    hasMore      = d.has_more;

    document.getElementById('resultInfo').textContent = numFr(totalLoaded) + ' vente' + (totalLoaded > 1 ? 's' : '') + ' chargée' + (totalLoaded > 1 ? 's' : '');

    if (!hasMore) {
      if (observer) observer.disconnect();
      sentinel.innerHTML = `<p class="end-msg">— Fin des résultats —</p>`;
    } else {
      sentinel.innerHTML = '';
    }
  } catch(e) {
    sentinel.innerHTML = '<span style="color:#b91c1c;font-size:13px;">Erreur de chargement.</span>';
  }

  loading = false;
}

// ── Reset + IntersectionObserver ─────────────────────────────────────────────

function reset() {
  nextCursor  = null;
  loading     = false;
  hasMore     = true;
  totalLoaded = 0;
  document.getElementById('cardList').innerHTML  = '';
  document.getElementById('resultInfo').textContent = '';
  document.getElementById('sentinel').innerHTML  = '<span class="spin"></span>';

  if (observer) observer.disconnect();
  observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) loadMore();
  }, { rootMargin: '200px' });
  observer.observe(document.getElementById('sentinel'));
}

// ── Init ──────────────────────────────────────────────────────────────────────

document.getElementById('btnAppliquer').addEventListener('click', reset);
reset();
</script>
</body>
</html>
