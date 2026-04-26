<?php
/**
 * includes/seo-extras.php — Métadonnées SEO partagées (Phase 1)
 *
 * À inclure dans le <head> de toutes les pages publiques, juste après
 * le bloc meta/og existant.
 *
 * Variables optionnelles (à définir AVANT l'include si on veut surcharger) :
 *   $seoTwitterTitle  : titre pour Twitter (sinon repris du <title>)
 *   $seoTwitterDesc   : description pour Twitter (sinon meta description)
 *   $seoTwitterImage  : URL d'image custom (sinon og-estimatiz.png)
 *
 * Sortie : Twitter Cards, dimensions OG image, theme-color, Organization JSON-LD.
 */
?>
<!-- SEO extras (partagés) -->
<meta name="theme-color" content="#1E3A8A" />
<meta name="author" content="Estimatiz" />
<meta name="robots" content="index, follow, max-image-preview:large" />
<meta property="og:site_name" content="Estimatiz" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="Estimatiz — Estimation immobilière basée sur les ventes DVF" />

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?= htmlspecialchars($seoTwitterTitle ?? 'Estimatiz – Estimation immobilière en ligne gratuite', ENT_QUOTES, 'UTF-8') ?>" />
<meta name="twitter:description" content="<?= htmlspecialchars($seoTwitterDesc ?? 'Estimez votre bien à partir des ventes DVF officielles. Prix au m², fourchette basse, médiane et haute pour toute la France.', ENT_QUOTES, 'UTF-8') ?>" />
<meta name="twitter:image" content="<?= htmlspecialchars($seoTwitterImage ?? 'https://www.estimatiz.fr/assets/img/og-estimatiz.png', ENT_QUOTES, 'UTF-8') ?>" />

<!-- Organization (sitewide) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Estimatiz",
  "url": "https://www.estimatiz.fr/",
  "logo": "https://www.estimatiz.fr/assets/img/og-estimatiz.png",
  "description": "Outil d'estimation immobilière indépendant basé sur les ventes réelles publiées par l'État (données DVF / DGFiP) — France entière, 2014–2025.",
  "areaServed": {
    "@type": "Country",
    "name": "France"
  },
  "knowsAbout": [
    "Estimation immobilière",
    "Prix au m²",
    "Données DVF",
    "Marché immobilier français"
  ]
}
</script>
