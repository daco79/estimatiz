<?php
/**
 * includes/footer.php — Footer enrichi (Phase SEO 1)
 *
 * À inclure en bas de chaque page, juste avant </body>.
 * Remplace les <footer>...</footer> simples actuels.
 *
 * Usage :
 *   <?php include 'includes/footer.php'; ?>
 *
 * Si la page est dans un sous-dossier, définir $navRoot avant l'include
 * (déjà géré par nav.php).
 */
$footerRoot = $navRoot ?? '';
?>
<footer class="site-footer">
  <div class="footer-inner">

    <div class="footer-col">
      <div class="footer-brand">Estimatiz</div>
      <p class="footer-tag">Estimation immobilière indépendante, fondée sur les ventes réelles publiées par l'État.</p>
    </div>

    <div class="footer-col">
      <h4>Outils</h4>
      <ul>
        <li><a href="<?= $footerRoot ?>estimation">Estimer un bien</a></li>
        <li><a href="<?= $footerRoot ?>prix-m2">Prix au m²</a></li>
        <li><a href="<?= $footerRoot ?>ventes">Dernières ventes</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Comprendre</h4>
      <ul>
        <li><a href="<?= $footerRoot ?>methodologie">Méthodologie</a></li>
        <li><a href="<?= $footerRoot ?>donnees">Données utilisées</a></li>
        <li><a href="<?= $footerRoot ?>faq">FAQ</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Estimatiz</h4>
      <ul>
        <li><a href="<?= $footerRoot ?>a-propos">À propos</a></li>
        <li><a href="<?= $footerRoot ?>contact">Contact</a></li>
        <li><a href="<?= $footerRoot ?>mentions-legales">Mentions légales</a></li>
        <li><a href="<?= $footerRoot ?>confidentialite">Confidentialité</a></li>
      </ul>
    </div>

  </div>

  <div class="footer-bottom">
    <span>
      Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a>
      &nbsp;|&nbsp; France 2014–2025
      &nbsp;|&nbsp; Licence Ouverte Etalab
    </span>
    <span>© <?= date('Y') ?> Estimatiz</span>
  </div>
</footer>

<style>
.site-footer{
  background:#111827;
  color:rgba(255,255,255,.7);
  padding:48px 24px 20px;
  font-size:14px;
  margin-top:auto;
}
.footer-inner{
  max-width:1100px;
  margin:0 auto;
  display:grid;
  grid-template-columns:1.5fr 1fr 1fr 1fr;
  gap:32px;
  padding-bottom:32px;
  border-bottom:1px solid rgba(255,255,255,.1);
}
.footer-brand{
  font-size:18px;
  font-weight:800;
  color:#fff;
  margin-bottom:8px;
}
.footer-tag{
  font-size:13px;
  color:rgba(255,255,255,.6);
  line-height:1.6;
  margin:0;
}
.footer-col h4{
  font-size:13px;
  font-weight:700;
  color:#fff;
  margin:0 0 12px;
  text-transform:uppercase;
  letter-spacing:.04em;
}
.footer-col ul{
  list-style:none;
  margin:0;
  padding:0;
}
.footer-col li{
  margin-bottom:8px;
}
.footer-col a{
  color:rgba(255,255,255,.7);
  text-decoration:none;
  font-size:13px;
  transition:color .15s;
}
.footer-col a:hover{
  color:#10B981;
}
.footer-bottom{
  max-width:1100px;
  margin:0 auto;
  padding-top:16px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-size:12px;
  color:rgba(255,255,255,.5);
  flex-wrap:wrap;
  gap:8px;
}
.footer-bottom a{
  color:rgba(255,255,255,.7);
}
.footer-bottom a:hover{
  color:#fff;
}
@media(max-width:768px){
  .footer-inner{
    grid-template-columns:1fr 1fr;
    gap:24px;
  }
  .footer-bottom{
    flex-direction:column;
    text-align:center;
  }
}
@media(max-width:480px){
  .footer-inner{
    grid-template-columns:1fr;
  }
}
</style>
