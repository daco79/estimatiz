<?php $navActive = 'methodologie'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Méthodologie – Estimatiz</title>
  <link rel="stylesheet" href="assets/css/site.css" />
  <?php include 'includes/content-style.php'; ?>
</head>
<body>
<?php include 'includes/nav.php'; ?>


  <div class="page-hero">
    <h1>Méthodologie</h1>
    <p>Comment Estimatiz calcule-t-il une estimation ? Transparence complète sur les données, l'algorithme et ses limites.</p>
  </div>

  <div class="content">

    <div class="c-section">
      <h2>1. Source des données</h2>
      <p>Estimatiz utilise exclusivement les données <strong>DVF — Demandes de Valeurs Foncières</strong>, publiées par la Direction Générale des Finances Publiques (DGFiP) sur <a href="https://www.data.gouv.fr" target="_blank" rel="noopener">data.gouv.fr</a>.</p>
      <p>Ces données recensent l'ensemble des transactions immobilières enregistrées en France depuis 2014, issues des actes notariés. Elles constituent la source de référence la plus fiable et exhaustive disponible pour l'estimation immobilière.</p>
      <div class="c-info">
        <strong>Données officielles et publiques</strong>
        Les prix DVF sont ceux déclarés à l'administration fiscale lors de chaque vente. Ils ne sont pas des estimations ou des prix affichés, mais les prix réellement payés.
      </div>
    </div>

    <div class="c-section">
      <h2>2. Recherche des comparables</h2>
      <p>Pour chaque estimation, Estimatiz recherche les ventes passées les plus proches de votre bien selon une stratégie par paliers successifs :</p>
      <div class="c-steps">
        <div class="c-step">
          <div class="c-step-num">1</div>
          <div class="c-step-body">
            <h3>Même rue, même nombre de pièces</h3>
            <p>Recherche prioritaire : ventes dans la même rue avec exactement le même nombre de pièces que le bien à estimer.</p>
          </div>
        </div>
        <div class="c-step">
          <div class="c-step-num">2</div>
          <div class="c-step-body">
            <h3>Même rue, ±1 pièce</h3>
            <p>Si l'échantillon est insuffisant, élargissement aux biens de ±1 pièce dans la même rue.</p>
          </div>
        </div>
        <div class="c-step">
          <div class="c-step-num">3</div>
          <div class="c-step-body">
            <h3>Section cadastrale</h3>
            <p>Élargissement à la section cadastrale (quartier) si la rue ne fournit pas assez de ventes.</p>
          </div>
        </div>
        <div class="c-step">
          <div class="c-step-num">4</div>
          <div class="c-step-body">
            <h3>Commune entière</h3>
            <p>En dernier recours, toutes les ventes de la commune sont utilisées pour garantir un résultat.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="c-section">
      <h2>3. Filtrage des valeurs aberrantes (IQR)</h2>
      <p>Les données DVF contiennent parfois des ventes atypiques : parkings vendus avec un appartement, quotes-parts de terrains, erreurs de saisie. Pour les exclure automatiquement, Estimatiz applique un <strong>filtre interquartile (IQR 1.5×)</strong> sur le prix au m².</p>
      <p>Concrètement : les ventes dont le prix au m² sort de l'intervalle <code>[Q1 − 1.5×IQR, Q3 + 1.5×IQR]</code> sont écartées du calcul. Ce filtre statistique standard élimine les valeurs extrêmes tout en conservant la diversité du marché.</p>
    </div>

    <div class="c-section">
      <h2>4. Calcul de l'estimation</h2>
      <p>Sur les comparables retenus après filtrage, Estimatiz calcule les <strong>percentiles du prix au m²</strong>, puis les multiplie par la surface du bien :</p>
      <div class="c-cards">
        <div class="c-card">
          <div class="c-card-icon">📉</div>
          <h3>Estimation basse (P20)</h3>
          <p>20 % des ventes comparables ont un prix au m² inférieur à ce seuil. Valeur prudente, marché défavorable.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">⚖️</div>
          <h3>Estimation médiane (P50)</h3>
          <p>La moitié des ventes comparables sont au-dessus, l'autre moitié en dessous. Valeur de référence centrale.</p>
        </div>
        <div class="c-card">
          <div class="c-card-icon">📈</div>
          <h3>Estimation haute (P80)</h3>
          <p>80 % des ventes comparables ont un prix au m² inférieur à ce seuil. Valeur optimiste, bien de standing.</p>
        </div>
      </div>
    </div>

    <div class="c-section">
      <h2>5. Indice de confiance</h2>
      <p>Chaque estimation est accompagnée d'un <strong>indice de confiance (0 à 100 %)</strong> calculé à partir de deux critères :</p>
      <ul>
        <li><strong>Taille de l'échantillon</strong> : plus il y a de ventes comparables, plus la confiance est élevée.</li>
        <li><strong>Dispersion des prix</strong> : un marché homogène (faible écart-type) donne une confiance plus élevée qu'un marché très hétérogène.</li>
      </ul>
      <p>Un indice faible (moins de 40 %) indique que les données disponibles sont rares ou très dispersées : l'estimation doit alors être interprétée avec prudence.</p>
    </div>

    <div class="c-section">
      <h2>6. Limites de la méthode</h2>
      <div class="c-warn">
        <strong>Estimatiz ne remplace pas une expertise immobilière professionnelle.</strong>
        Les estimations produites sont des indications statistiques fondées sur des données passées. Elles ne tiennent pas compte de l'état du bien, des travaux, de l'étage, de l'exposition, de la vue, ni de l'évolution récente du marché.
      </div>
      <ul>
        <li>Les données DVF ont un délai de publication de plusieurs mois.</li>
        <li>Les biens atypiques (lofts, hôtels particuliers, rez-de-jardin…) peuvent être mal représentés.</li>
        <li>Les ventes entre proches (donations, successions) peuvent biaiser les prix.</li>
        <li>La couverture actuelle est limitée à <strong>Paris intramuros (75)</strong>.</li>
      </ul>
    </div>

  </div>

  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; Paris 2014–2025
  </footer>

</body>
</html>
