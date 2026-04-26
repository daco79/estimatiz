<?php
/**
 * includes/nav.php — Navigation principale partagée
 * $navActive : 'accueil' | 'estimer' | 'prix' | 'ventes' | 'methodologie' | 'faq' | 'apropos' | 'contact' | 'donnees'
 * $navRoot   : '' (racine) ou '../' si page dans un sous-dossier
 */
$navActive = $navActive ?? '';
$navRoot   = $navRoot   ?? '';

function navClass(string $key, string $active, string $extra = ''): string {
    $cls = $extra;
    if ($key === $active) $cls .= ($cls ? ' ' : '') . 'active';
    return $cls ? 'class="' . $cls . '"' : '';
}
?>
<nav class="sitenav" role="navigation" aria-label="Navigation principale">
  <div class="sitenav-inner">

    <!-- Logo -->
    <a class="sitenav-logo" href="<?= $navRoot ?>." title="Estimatiz — Accueil">
      <svg class="sitenav-logo-icon" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
        <path d="M60 10 L20 46 V110 H100 V46 Z" fill="none" stroke="#1E3A8A" stroke-width="7" stroke-linejoin="round"/>
        <rect x="52" y="68" width="16" height="42" fill="#10B981" rx="3"/>
        <rect x="28" y="76" width="13" height="34" fill="#1E3A8A" rx="3"/>
        <rect x="79" y="82" width="11" height="28" fill="#10B981" rx="3"/>
        <path d="M60 42c-13 0-23 10-23 23 0 17 23 42 23 42s23-25 23-42c0-13-10-23-23-23z" fill="#1E3A8A"/>
        <circle cx="60" cy="65" r="7" fill="#fff"/>
      </svg>
      <div class="sitenav-logo-text">
        <span class="sitenav-logo-name">Estimatiz</span>
        <span class="sitenav-logo-tag">Précision&nbsp;•&nbsp;Transparence&nbsp;•&nbsp;Data</span>
      </div>
    </a>

    <!-- Liens desktop -->
    <ul class="sitenav-links" role="list">
      <li><a href="<?= $navRoot ?>." <?= navClass('accueil', $navActive) ?>>Accueil</a></li>
      <li><a href="<?= $navRoot ?>estimation" <?= navClass('estimer', $navActive, 'sitenav-cta') ?>>Estimer un bien</a></li>
      <li><a href="<?= $navRoot ?>prix-m2" <?= navClass('prix', $navActive) ?>>Prix au m²</a></li>
      <li><a href="<?= $navRoot ?>ventes" <?= navClass('ventes', $navActive) ?>>Dernières ventes</a></li>
      <li class="nav-dropdown">
        <a href="#" <?= in_array($navActive, ['methodologie','faq','donnees','apropos','contact','mentions','confidentialite']) ? 'class="active"' : '' ?>>À propos</a>
        <div class="nav-dropdown-menu">
          <a href="<?= $navRoot ?>methodologie" <?= navClass('methodologie', $navActive) ?>>Méthodologie</a>
          <a href="<?= $navRoot ?>donnees"      <?= navClass('donnees',      $navActive) ?>>Données utilisées</a>
          <hr>
          <a href="<?= $navRoot ?>faq"          <?= navClass('faq',          $navActive) ?>>FAQ</a>
          <a href="<?= $navRoot ?>a-propos"     <?= navClass('apropos',      $navActive) ?>>À propos</a>
          <a href="<?= $navRoot ?>contact"      <?= navClass('contact',      $navActive) ?>>Contact</a>
          <hr>
          <a href="<?= $navRoot ?>mentions-legales"  <?= navClass('mentions',       $navActive) ?>>Mentions légales</a>
          <a href="<?= $navRoot ?>confidentialite"   <?= navClass('confidentialite',$navActive) ?>>Confidentialité</a>
        </div>
      </li>
    </ul>

    <!-- Hamburger -->
    <button class="sitenav-burger" id="navBurger" aria-label="Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

  </div>

  <!-- Menu mobile -->
  <div class="sitenav-mobile" id="navMobile">
    <a href="<?= $navRoot ?>." <?= navClass('accueil', $navActive) ?>>Accueil</a>
    <a href="<?= $navRoot ?>estimation" <?= navClass('estimer', $navActive, 'mob-cta') ?>>Estimer un bien</a>
    <a href="<?= $navRoot ?>prix-m2" <?= navClass('prix', $navActive) ?>>Prix au m²</a>
    <a href="<?= $navRoot ?>ventes" <?= navClass('ventes', $navActive) ?>>Dernières ventes</a>
    <span class="mob-sep">À propos</span>
    <a href="<?= $navRoot ?>methodologie" <?= navClass('methodologie', $navActive) ?>>Méthodologie</a>
    <a href="<?= $navRoot ?>donnees"      <?= navClass('donnees',      $navActive) ?>>Données utilisées</a>
    <a href="<?= $navRoot ?>faq"          <?= navClass('faq',          $navActive) ?>>FAQ</a>
    <a href="<?= $navRoot ?>a-propos"     <?= navClass('apropos',      $navActive) ?>>À propos</a>
    <a href="<?= $navRoot ?>contact"      <?= navClass('contact',      $navActive) ?>>Contact</a>
    <span class="mob-sep">Légal</span>
    <a href="<?= $navRoot ?>mentions-legales"  <?= navClass('mentions',       $navActive) ?>>Mentions légales</a>
    <a href="<?= $navRoot ?>confidentialite"   <?= navClass('confidentialite',$navActive) ?>>Confidentialité</a>
  </div>
</nav>

<script>
(function(){
  // Hamburger mobile
  var burger = document.getElementById('navBurger');
  var mobile = document.getElementById('navMobile');
  if (burger && mobile) {
    burger.addEventListener('click', function(e){
      e.stopPropagation();
      var open = mobile.classList.toggle('open');
      burger.classList.toggle('open', open);
      burger.setAttribute('aria-expanded', open);
    });
  }

  // Dropdown "À propos" — toggle au clic
  var dropdowns = document.querySelectorAll('.nav-dropdown');
  dropdowns.forEach(function(dd) {
    var trigger = dd.querySelector(':scope > a');
    var menu    = dd.querySelector('.nav-dropdown-menu');
    if (!trigger || !menu) return;
    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var isOpen = menu.classList.toggle('open');
      trigger.setAttribute('aria-expanded', isOpen);
    });
  });

  // Fermer tout si clic en dehors
  document.addEventListener('click', function(e){
    if (!e.target.closest('.sitenav')) {
      if (mobile) { mobile.classList.remove('open'); burger.classList.remove('open'); burger.setAttribute('aria-expanded', false); }
    }
    if (!e.target.closest('.nav-dropdown')) {
      document.querySelectorAll('.nav-dropdown-menu.open').forEach(function(m){ m.classList.remove('open'); });
    }
  });
})();
</script>
