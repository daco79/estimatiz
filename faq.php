<?php $navActive = 'faq'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>FAQ – Estimatiz</title>
  <!-- SEO enhancements -->
  <meta name="description" content="Questions fréquentes sur Estimatiz : recherche d'adresse, méthode d’estimation, indice de confiance et limites." />
  <link rel="canonical" href="https://www.estimatiz.fr/faq" />
  <!-- Open Graph tags -->
  <meta property="og:title" content="FAQ – Estimatiz" />
  <meta property="og:description" content="Questions fréquentes sur Estimatiz : recherche d'adresse, méthode d’estimation, indice de confiance et limites." />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="https://www.estimatiz.fr/faq" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
      {
        "@type": "Question",
        "name": "Pourquoi mon adresse n'est-elle pas trouvée ?",
        "acceptedAnswer": { "@type": "Answer", "text": "L'autocomplétion est fondée sur les adresses présentes dans la base DVF. Si une adresse n'apparaît pas, c'est qu'aucune vente n'a été enregistrée à cette adresse précise depuis 2014. Essayez de saisir uniquement le nom de la rue et la ville (sans numéro)." }
      },
      {
        "@type": "Question",
        "name": "Quelle est la différence entre une adresse précise et le nom de rue seul ?",
        "acceptedAnswer": { "@type": "Answer", "text": "Adresse précise (numéro + rue) : les ventes retenues sont celles au plus proche de votre numéro. Nom de rue seul : toutes les ventes de la rue sont utilisées, l'échantillon est plus large et l'estimation plus robuste statistiquement." }
      },
      {
        "@type": "Question",
        "name": "Pourquoi l'estimation est-elle une fourchette et non un prix unique ?",
        "acceptedAnswer": { "@type": "Answer", "text": "Le marché immobilier n'est pas homogène. Estimatiz propose trois valeurs : une estimation basse (P20), une valeur médiane (P50) et une estimation haute (P80). Ces percentiles reflètent la réalité du marché." }
      },
      {
        "@type": "Question",
        "name": "Qu'est-ce que l'indice de confiance ?",
        "acceptedAnswer": { "@type": "Answer", "text": "L'indice de confiance mesure la fiabilité statistique de l'estimation. Il tient compte du nombre de ventes comparables trouvées et de la dispersion des prix. Un indice inférieur à 40 % indique un échantillon réduit ou un marché très hétérogène." }
      },
      {
        "@type": "Question",
        "name": "Les données sont-elles officielles ?",
        "acceptedAnswer": { "@type": "Answer", "text": "Oui. Les données DVF sont publiées par la Direction Générale des Finances Publiques (DGFiP) sur data.gouv.fr. Elles sont issues des actes notariés transmis à l'administration fiscale lors de chaque vente." }
      },
      {
        "@type": "Question",
        "name": "Estimatiz remplace-t-il une expertise immobilière professionnelle ?",
        "acceptedAnswer": { "@type": "Answer", "text": "Non. Estimatiz est un outil d'aide à la décision fondé sur des données statistiques. Il ne tient pas compte de l'état du bien, des travaux, de l'étage ou de l'exposition. Pour une vente ou un achat important, une expertise professionnelle reste indispensable." }
      },
      {
        "@type": "Question",
        "name": "Puis-je utiliser Estimatiz pour une démarche fiscale ou juridique ?",
        "acceptedAnswer": { "@type": "Answer", "text": "Les estimations produites par Estimatiz sont des indicateurs statistiques et ne constituent pas une expertise immobilière certifiée. Elles ne peuvent pas être utilisées comme pièce justificative dans une procédure fiscale, successorale ou juridique." }
      }
    ]
  }
  </script>
  <link rel="stylesheet" href="assets/css/site.css" />
  <?php include 'includes/content-style.php'; ?>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <div class="page-hero">
    <h1>Questions fréquentes</h1>
    <p>Tout ce que vous voulez savoir sur le fonctionnement d'Estimatiz et l'interprétation des résultats.</p>
  </div>

  <div class="content">

    <div class="c-section">
      <h2>Recherche d'adresse</h2>
      <div class="faq-list">

        <details class="faq-item">
          <summary>Pourquoi mon adresse n'est-elle pas trouvée ?</summary>
          <div class="faq-answer">
            <p>L'autocomplétion est fondée sur les adresses présentes dans la base DVF. Si une adresse n'apparaît pas, c'est qu'aucune vente n'a été enregistrée à cette adresse précise depuis 2014.</p>
            <p>Essayez de saisir <strong>uniquement le nom de la rue et la ville</strong> (sans numéro). Vous obtiendrez un échantillon plus large couvrant l'ensemble de la rue.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>Quelle est la différence entre une adresse précise et le nom de rue seul ?</summary>
          <div class="faq-answer">
            <p><strong>Adresse précise</strong> (numéro + rue) : les ventes retenues sont celles au plus proche de votre numéro. L'estimation est plus ciblée mais peut reposer sur un échantillon réduit.</p>
            <p><strong>Nom de rue seul</strong> : toutes les ventes de la rue sont utilisées. L'échantillon est plus large et l'estimation plus robuste statistiquement, mais moins précise géographiquement.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>Puis-je chercher par arrondissement ou par quartier ?</summary>
          <div class="faq-answer">
            <p>Pas directement pour l'instant. La recherche fonctionne par adresse ou par rue. Une fonctionnalité "Prix au m² par arrondissement" est en cours de développement.</p>
          </div>
        </details>

      </div>
    </div>

    <div class="c-section">
      <h2>Estimation et résultats</h2>
      <div class="faq-list">

        <details class="faq-item">
          <summary>Pourquoi l'estimation est-elle une fourchette et non un prix unique ?</summary>
          <div class="faq-answer">
            <p>Le marché immobilier n'est pas homogène : deux appartements dans la même rue peuvent se vendre à des prix très différents selon leur étage, leur état, leur exposition ou leur orientation.</p>
            <p>Estimatiz propose donc trois valeurs : une <strong>estimation basse (P20)</strong>, une <strong>valeur médiane (P50)</strong> et une <strong>estimation haute (P80)</strong>. Ces percentiles reflètent la réalité du marché et vous permettent de vous situer dans la fourchette.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>Qu'est-ce que l'indice de confiance ?</summary>
          <div class="faq-answer">
            <p>L'indice de confiance mesure la fiabilité statistique de l'estimation. Il tient compte de deux facteurs :</p>
            <p>— Le <strong>nombre de ventes comparables</strong> trouvées : plus il y en a, plus la confiance est élevée.<br>
            — La <strong>dispersion des prix</strong> : un marché homogène donne une confiance plus élevée.</p>
            <p>Un indice inférieur à 40 % indique un échantillon réduit ou un marché très hétérogène. L'estimation reste indicative mais doit être interprétée avec prudence.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>Pourquoi certains prix dans le tableau semblent-ils très bas ou très hauts ?</summary>
          <div class="faq-answer">
            <p>Plusieurs raisons possibles :</p>
            <p>— Une <strong>vente entre proches</strong> (cession familiale, donation déguisée) à un prix sous le marché.<br>
            — Un bien vendu avec une <strong>servitude, un bail en cours</strong> ou dans un état dégradé.<br>
            — Une <strong>erreur de saisie</strong> dans les données DVF (surface incorrecte → prix au m² aberrant).<br>
            — Un bien atypique (cave, parking, lot de lots) mal identifié.</p>
            <p>Vous pouvez <strong>décocher les lignes aberrantes</strong> dans le tableau : l'estimation est recalculée en temps réel sur les seules lignes cochées.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>À quelle date sont les ventes affichées ?</summary>
          <div class="faq-answer">
            <p>Les ventes affichées couvrent la période <strong>2014 à 2025</strong> (selon la dernière mise à jour DVF disponible). Toutes les ventes de la rue (ou de l'adresse) sont affichées par défaut, sans filtre temporel.</p>
            <p>Un filtre par plage d'années est prévu dans une prochaine version.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>Les données sont-elles officielles ?</summary>
          <div class="faq-answer">
            <p>Oui. Les données DVF sont publiées par la <strong>Direction Générale des Finances Publiques (DGFiP)</strong> sur data.gouv.fr. Elles sont issues des actes notariés transmis à l'administration fiscale lors de chaque vente.</p>
            <p>Ce sont des données publiques, gratuites et régulièrement mises à jour.</p>
          </div>
        </details>

      </div>
    </div>

    <div class="c-section">
      <h2>Limites et usage</h2>
      <div class="faq-list">

        <details class="faq-item">
          <summary>Estimatiz remplace-t-il une expertise immobilière professionnelle ?</summary>
          <div class="faq-answer">
            <p>Non. Estimatiz est un <strong>outil d'aide à la décision</strong> fondé sur des données statistiques. Il ne tient pas compte de l'état du bien, des travaux réalisés ou à prévoir, de l'étage, de l'exposition, de la luminosité, du voisinage immédiat, ni des tendances de marché en temps réel.</p>
            <p>Pour une vente ou un achat important, une expertise d'un professionnel (agent immobilier, notaire, expert foncier) reste indispensable.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>Quelles zones géographiques sont couvertes ?</summary>
          <div class="faq-answer">
            <p>Estimatiz couvre la <strong>France entière</strong> : métropole et DOM (Guadeloupe, Martinique, Guyane, La Réunion, Mayotte). Les données DVF représentent <strong>13 millions de transactions</strong> de 2014 à 2025, réparties sur 101 départements.</p>
            <p>Seules les zones à très faible densité de transactions (communes rurales isolées) peuvent donner des estimations moins fiables, faute de comparables suffisants.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary>Puis-je utiliser Estimatiz pour une démarche fiscale ou juridique ?</summary>
          <div class="faq-answer">
            <p>Les estimations produites par Estimatiz sont des <strong>indicateurs statistiques</strong> et ne constituent pas une expertise immobilière certifiée. Elles ne peuvent pas être utilisées comme pièce justificative dans une procédure fiscale, successorale ou juridique.</p>
          </div>
        </details>

      </div>
    </div>

    <div class="c-section">
      <h2>Vous n'avez pas trouvé votre réponse ?</h2>
      <p>Contactez-nous via la <a href="contact">page Contact</a>. Nous répondons dans les meilleurs délais.</p>
    </div>

  </div>

  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; France 2014–2025
  </footer>

</body>
</html>