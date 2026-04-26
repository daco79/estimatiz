<?php $navActive = 'donnees'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Données utilisées – Estimatiz</title>
  <!-- SEO enhancements -->
  <meta name="description" content="Découvrez les données DVF utilisées par Estimatiz, leur composition, leur couverture et la façon dont elles sont traitées et mises à jour." />
  <link rel="icon" type="image/x-icon" href="/favicon.ico" />
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32x32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16x16.png" />
  <link rel="canonical" href="https://www.estimatiz.fr/donnees" />
  <!-- Open Graph tags -->
  <meta property="og:title" content="Données utilisées – Estimatiz" />
  <meta property="og:description" content="Découvrez les données DVF utilisées par Estimatiz, leur composition, leur couverture et la façon dont elles sont traitées et mises à jour." />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="https://www.estimatiz.fr/donnees" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />
  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
    {"@type":"ListItem","position":1,"name":"Accueil","item":"https://www.estimatiz.fr/"},
    {"@type":"ListItem","position":2,"name":"Données utilisées","item":"https://www.estimatiz.fr/donnees"}
  ]}
  </script>
  <link rel="stylesheet" href="assets/css/site.css" />
  <?php include 'includes/content-style.php'; ?>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <div class="page-hero">
    <h1>Données utilisées</h1>
    <p>Tout ce que vous devez savoir sur la source des données, leur couverture et leurs éventuelles limites.</p>
  </div>

  <div class="content">

    <div class="c-section">
      <h2>La source : DVF</h2>
      <p>Estimatiz est fondé sur les <strong>Demandes de Valeurs Foncières (DVF)</strong>, un jeu de données publié par la Direction Générale des Finances Publiques (DGFiP) en open data sur <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">data.gouv.fr</a>.</p>
      <p>Ces données recensent toutes les mutations immobilières (ventes) enregistrées par les services de publicité foncière depuis 2014. Chaque ligne correspond à une vente réelle, avec son prix, sa surface, son adresse et sa date.</p>
      <div class="c-info">
        <strong>Données officielles</strong>
        Les prix DVF sont issus des actes notariés transmis à l'administration fiscale. Ce sont les prix réellement payés, pas des prix affichés ou estimés.
      </div>
    </div>

    <div class="c-section">
      <h2>Ce que contient chaque vente</h2>
      <div class="c-cards">
        <div class="c-card">
          <div class="c-card-icon">📍</div>
          <h3>Adresse complète</h3>
          <p>Numéro, type et nom de voie, code postal, commune, code voie et section cadastrale.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">💶</div>
          <h3>Prix de vente</h3>
          <p>Valeur foncière déclarée lors de la mutation, en euros.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">📐</div>
          <h3>Surface</h3>
          <p>Surface Carrez (prioritaire) et/ou surface réelle bâtie, en m².</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">🚪</div>
          <h3>Nombre de pièces</h3>
          <p>Nombre de pièces principales déclaré lors de la vente.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">📅</div>
          <h3>Date de mutation</h3>
          <p>Date effective de la transaction immobilière.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">🏠</div>
          <h3>Type de bien</h3>
          <p>Appartement, maison, local commercial, dépendance, terrain.</p>
        </div>
      </div>
    </div>

    <div class="c-section">
      <h2>Couverture actuelle</h2>
      <p>La base de données importée dans Estimatiz couvre actuellement :</p>
      <div class="c-stat-grid">
        <div class="c-stat"><div class="c-stat-val">13 millions</div><div class="c-stat-lbl">transactions importées</div></div>
        <div class="c-stat"><div class="c-stat-val">2014–2025</div><div class="c-stat-lbl">années couvertes</div></div>
        <div class="c-stat"><div class="c-stat-val">France entière</div><div class="c-stat-lbl">zone couverte</div></div>
        <div class="c-stat"><div class="c-stat-val">101</div><div class="c-stat-lbl">départements</div></div>
      </div>
      <div class="c-warn" style="margin-top:20px;">
        <strong>Couverture France entière.</strong>
        101 départements couverts (métropole et DOM) — 13 millions de transactions de 2014 à 2025.
      </div>
    </div>

    <div class="c-section">
      <h2>Traitement des données</h2>
      <p>Les fichiers DVF bruts sont traités avant import pour garantir la qualité des données :</p>
      <ul>
        <li><strong>Filtre géographique</strong> : les 101 départements métropolitains et DOM sont conservés ; les lignes sans code postal valide sont écartées.</li>
        <li><strong>Dédoublonnage</strong> : le format DVF génère plusieurs lignes par vente quand plusieurs locaux sont vendus ensemble (appartement + cave, parking…). Les doublons sont détectés et supprimés, en conservant la ligne la plus informative.</li>
        <li><strong>Validation</strong> : les lignes sans surface (ni Carrez ni réelle) sont exclues. Les valeurs mal formatées sont normalisées.</li>
        <li><strong>Import par lots</strong> : les données sont insérées par lots de 2 000 lignes pour garantir la stabilité.</li>
      </ul>
    </div>

    <div class="c-section">
      <h2>Mise à jour des données</h2>
      <p>La DGFiP publie une mise à jour des fichiers DVF <strong>deux fois par an</strong> (généralement en mai et en novembre). Estimatiz met à jour sa base à chaque nouvelle publication.</p>
      <p>Les données 2025 sont partielles (transactions jusqu'à la dernière publication disponible). Les données des années antérieures sont complètes.</p>
    </div>

    <div class="c-section">
      <h2>Pourquoi certaines adresses ou ventes manquent-elles ?</h2>
      <ul>
        <li><strong>Adresse non trouvée en autocomplétion</strong> : l'adresse n'existe pas dans les données DVF (pas de vente enregistrée dans cette rue). Essayez avec le nom de la rue seul pour un périmètre plus large.</li>
        <li><strong>Peu de ventes disponibles</strong> : certaines rues ont très peu de transactions sur la période. L'algorithme élargit alors automatiquement le périmètre.</li>
        <li><strong>Ventes non présentes</strong> : les donations, successions, échanges et certaines cessions ne figurent pas dans DVF. Seules les ventes classiques sont couvertes.</li>
        <li><strong>Délai de publication</strong> : les ventes très récentes (moins de 6 mois) peuvent ne pas encore être dans les fichiers publiés.</li>
      </ul>
    </div>

  </div>

  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; France 2014–2025
  </footer>

</body>
</html>