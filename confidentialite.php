<?php $navActive = 'confidentialite'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Politique de confidentialité — Estimatiz</title>
  <meta name="description" content="Politique de confidentialité d'Estimatiz : quelles données nous collectons, comment elles sont utilisées, vos droits RGPD et nos engagements." />

  <link rel="icon" type="image/x-icon" href="/favicon.ico" />
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32x32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16x16.png" />
  <link rel="canonical" href="https://www.estimatiz.fr/confidentialite" />

  <meta property="og:title" content="Politique de confidentialité — Estimatiz" />
  <meta property="og:description" content="Quelles données nous collectons, comment elles sont utilisées, vos droits RGPD." />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="https://www.estimatiz.fr/confidentialite" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />

  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
    {"@type":"ListItem","position":1,"name":"Accueil","item":"https://www.estimatiz.fr/"},
    {"@type":"ListItem","position":2,"name":"Politique de confidentialité","item":"https://www.estimatiz.fr/confidentialite"}
  ]}
  </script>

  <?php
    $seoTwitterTitle = "Politique de confidentialité — Estimatiz";
    $seoTwitterDesc  = "Données collectées, utilisation, droits RGPD.";
    include 'includes/seo-extras.php';
  ?>

  <link rel="stylesheet" href="assets/css/site.css" />
  <?php include 'includes/content-style.php'; ?>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <div class="page-hero">
    <h1>Politique de confidentialité</h1>
    <p>Comment Estimatiz traite vos données personnelles, conformément au RGPD.</p>
  </div>

  <main class="content">

    <div class="c-section">
      <h2>Notre engagement</h2>
      <p>Estimatiz a été conçu autour d'un principe simple : <strong>respecter la vie privée des utilisateurs</strong>. Nous ne collectons que le strict nécessaire au fonctionnement du service, et nous ne revendons jamais aucune donnée.</p>
    </div>

    <div class="c-section">
      <h2>Données collectées</h2>

      <h3>Lors de l'utilisation du service</h3>
      <p>Quand vous utilisez Estimatiz pour estimer un bien, les informations suivantes transitent par le serveur :</p>
      <ul>
        <li>L'<strong>adresse saisie</strong> (rue, numéro, code postal, ville)</li>
        <li>La <strong>surface</strong>, le <strong>nombre de pièces</strong> et le <strong>type de bien</strong> que vous renseignez</li>
        <li>Des <strong>logs techniques</strong> standards (adresse IP, user-agent, date) gérés par notre hébergeur O2switch</li>
      </ul>
      <p>L'adresse saisie est utilisée uniquement pour effectuer la recherche dans la base DVF et n'est <strong>pas associée à votre identité</strong>. Aucun compte utilisateur n'est requis.</p>

      <h3>Stockage local (sessionStorage)</h3>
      <p>Estimatiz utilise le <strong>sessionStorage</strong> de votre navigateur pour conserver les résultats d'estimation pendant la durée de votre visite. Ces informations restent sur votre appareil et sont effacées automatiquement à la fermeture de l'onglet.</p>

      <h3>Page de contact</h3>
      <p>Si vous nous écrivez via la page de contact, votre nom et votre email ne sont utilisés que pour vous répondre. Ils ne sont ni stockés en base, ni partagés.</p>
    </div>

    <div class="c-section">
      <h2>Cookies</h2>
      <p>Estimatiz <strong>ne dépose aucun cookie de tracking publicitaire</strong> ni d'analytics intrusif. Le site fonctionne sans Google Analytics, sans Facebook Pixel et sans services tiers de profilage.</p>
      <!-- Si vous ajoutez Plausible ou Matomo, mentionnez-le ici -->
    </div>

    <div class="c-section">
      <h2>Données DVF (sources)</h2>
      <p>Les données affichées (ventes immobilières, prix, surfaces) proviennent du jeu de données public <strong>DVF (Demandes de Valeurs Foncières)</strong> publié par la DGFiP sous Licence Ouverte. Ces données sont déjà <strong>anonymisées à la source</strong> par l'État (ni nom du vendeur, ni nom de l'acheteur).</p>
    </div>

    <div class="c-section">
      <h2>Vos droits (RGPD)</h2>
      <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez d'un droit :</p>
      <ul>
        <li>d'<strong>accès</strong> aux données vous concernant ;</li>
        <li>de <strong>rectification</strong> des données inexactes ;</li>
        <li>d'<strong>effacement</strong> ("droit à l'oubli") ;</li>
        <li>d'<strong>opposition</strong> au traitement ;</li>
        <li>à la <strong>portabilité</strong> de vos données.</li>
      </ul>
      <p>Pour exercer ces droits, contactez-nous via la <a href="/contact">page de contact</a>. Nous répondrons sous 30 jours maximum.</p>
      <p>Vous pouvez également déposer une réclamation auprès de la <a href="https://www.cnil.fr/" target="_blank" rel="noopener">CNIL</a>.</p>
    </div>

    <div class="c-section">
      <h2>Hébergement</h2>
      <p>Le site est hébergé par <strong>O2switch</strong>, hébergeur français basé à Clermont-Ferrand. Toutes les données restent dans l'Union européenne.</p>
    </div>

    <div class="c-section">
      <h2>Modifications</h2>
      <p>Cette politique peut être mise à jour pour refléter des évolutions du service. La date de dernière mise à jour est indiquée ci-dessous.</p>
      <p><em>Dernière mise à jour : 26 avril 2026.</em></p>
    </div>

  </main>

  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; France 2014–2025 &nbsp;|&nbsp;
    <a href="/mentions-legales">Mentions légales</a> &nbsp;|&nbsp;
    <a href="/confidentialite">Confidentialité</a>
  </footer>
</body>
</html>
