<?php $navActive = 'apropos'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>À propos – Estimatiz</title>
  <!-- SEO enhancements -->
  <meta name="description" content="Découvrez qui se cache derrière Estimatiz, ses valeurs et sa mission. Un outil transparent pour rendre l’estimation immobilière accessible à tous." />
  <link rel="canonical" href="https://www.estimatiz.fr/a-propos.php" />
  <!-- Open Graph tags -->
  <meta property="og:title" content="À propos – Estimatiz" />
  <meta property="og:description" content="Découvrez qui se cache derrière Estimatiz, ses valeurs et sa mission. Un outil transparent pour rendre l’estimation immobilière accessible à tous." />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="https://www.estimatiz.fr/a-propos.php" />
  <meta property="og:locale" content="fr_FR" />
  <link rel="stylesheet" href="assets/css/site.css" />
  <?php include 'includes/content-style.php'; ?>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <div class="page-hero">
    <h1>À propos d'Estimatiz</h1>
    <p>Un outil indépendant, fondé sur des données publiques, pour rendre l'estimation immobilière accessible à tous.</p>
  </div>

  <div class="content">

    <div class="c-section">
      <h2>Le projet</h2>
      <p>Estimatiz est né d'un constat simple : <strong>les données immobilières existent, elles sont publiques, mais elles sont difficiles d'accès</strong>. Les fichiers DVF publiés par l'État contiennent des centaines de milliers de ventes réelles, mais leur exploitation nécessite des compétences techniques que la plupart des particuliers n't ont pas.</p>
      <p>L'objectif d'Estimatiz est de rendre ces données lisibles et utiles : pas d'algorithme boîte noire, pas d'estimation venue de nulle part — juste les ventes réelles, filtrées et présentées clairement.</p>
    </div>

    <div class="c-section">
      <h2>Nos valeurs</h2>
      <div class="c-cards">
        <div class="c-card">
          <div class="c-card-icon">🎯</div>
          <h3>Précision</h3>
          <p>Chaque estimation repose sur des ventes réelles, géographiquement proches et filtrées statistiquement. Pas d'approximation hasardeuse.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">🔍</div>
          <h3>Transparence</h3>
          <p>La méthodologie est entièrement documentée. Vous savez exactement quelles ventes ont servi à calculer votre estimation et pourquoi.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">📊</div>
          <h3>Data</h3>
          <p>Les données DVF sont officielles, publiques et issues des actes notariés. Aucune donnée propriétaire, aucun conflit d'intérêts.</p>
        </div>
      </div>
    </div>

    <div class="c-section">
      <h2>Ce qu'Estimatiz n'est pas</h2>
      <ul>
        <li>Estimatiz <strong>n'est pas une agence immobilière</strong> et ne propose aucune mise en relation avec des acheteurs ou vendeurs.</li>
        <li>Estimatiz <strong>ne vend pas de données</strong> et n'utilise aucune donnée propriétaire ou commerciale.</li>
        <li>Estimatiz <strong>ne remplace pas un expert</strong> : les estimations sont des indicateurs statistiques, pas des expertises certifiées.</li>
      </ul>
    </div>

    <div class="c-section">
      <h2>Couverture et évolution</h2>
      <p>La version actuelle couvre <strong>Paris intramuros (75001–75020)</strong> avec les données DVF de 2014 à 2025.</p>
      <p>Les développements prévus incluent :</p>
      <ul>
        <li>Extension à la petite couronne (92, 93, 94) et aux grandes villes (Lyon, Marseille, Bordeaux…)</li>
        <li>Visualisation cartographique des ventes</li>
        <li>Évolution des prix dans le temps par rue ou arrondissement</li>
        <li>Comparaison entre deux zones</li>
      </ul>
    </div>

    <div class="c-section">
      <h2>Contact & retours</h2>
      <p>Estimatiz est un projet en constante évolution. Vos retours sont précieux : adresse introuvable, estimation qui semble incohérente, fonctionnalité souhaitée — tout est utile.</p>
      <p><a href="contact.php">→ Nous contacter</a></p>
    </div>

  </div>

  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; Paris 2014–2025
  </footer>

</body>
</html>