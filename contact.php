<?php $navActive = 'contact'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Contact – Estimatiz</title>
  <!-- SEO enhancements -->
  <meta name="description" content="Contactez l'équipe Estimatiz pour signaler une adresse introuvable, une estimation incohérente, un bug ou une suggestion." />
  <link rel="canonical" href="https://www.estimatiz.fr/contact" />
  <!-- Open Graph tags -->
  <meta property="og:title" content="Contact – Estimatiz" />
  <meta property="og:description" content="Contactez l'équipe Estimatiz pour signaler une adresse introuvable, une estimation incohérente, un bug ou une suggestion." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.estimatiz.fr/contact" />
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:image" content="https://www.estimatiz.fr/assets/img/og-estimatiz.png" />
  <link rel="stylesheet" href="assets/css/site.css" />
  <?php include 'includes/content-style.php'; ?>
</head>
<body>
<?php include 'includes/nav.php'; ?>

  <div class="page-hero">
    <h1>Nous contacter</h1>
    <p>Une question, une erreur à signaler ou une suggestion ? Écrivez-nous, nous lisons tous les messages.</p>
  </div>

  <div class="content">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:32px; align-items:start;">
      <!-- Formulaire -->
      <div>
        <div class="c-form" id="contactForm">
          <div class="c-form-row">
            <div>
              <label for="fname">Prénom</label>
              <input type="text" id="fname" placeholder="Jean" autocomplete="given-name"/>
            </div>
            <div>
              <label for="lname">Nom</label>
              <input type="text" id="lname" placeholder="Dupont" autocomplete="family-name"/>
            </div>
          </div>
          <label for="femail">Email</label>
          <input type="email" id="femail" placeholder="jean.dupont@email.com" autocomplete="email"/>
          <label for="fsujet">Sujet</label>
          <select id="fsujet">
            <option value="">Choisissez un sujet…</option>
            <option value="adresse">Adresse introuvable</option>
            <option value="estimation">Estimation incohérente</option>
            <option value="bug">Bug ou erreur technique</option>
            <option value="suggestion">Suggestion de fonctionnalité</option>
            <option value="ville">Demande d'ajout d'une ville</option>
            <option value="autre">Autre</option>
          </select>
          <label for="fmessage">Message</label>
          <textarea id="fmessage" placeholder="Décrivez votre question ou votre retour en détail…"></textarea>
          <button class="c-btn" id="btnSend" type="button">Envoyer le message</button>
          <div class="c-success" id="formSuccess">
            ✓ Message envoyé ! Nous vous répondrons dans les meilleurs délais.
          </div>
        </div>
      </div>
      <!-- Infos -->
      <div>
        <div class="c-section" style="margin-bottom:24px;">
          <h2>Pour quoi nous contacter ?</h2>
          <ul>
            <li><strong>Adresse introuvable</strong> — Nous pouvons vérifier si la rue est dans notre base et expliquer pourquoi elle n'apparaît pas.</li>
            <li><strong>Estimation incohérente</strong> — Si une estimation vous semble très éloignée du marché, dites-le nous avec l'adresse concernée.</li>
            <li><strong>Bug ou anomalie</strong> — Décrivez le problème et votre navigateur, nous corrigerons rapidement.</li>
            <li><strong>Suggestion</strong> — Fonctionnalité manquante, ville à couvrir, amélioration de l'interface… tout retour est bienvenu.</li>
          </ul>
        </div>
        <div class="c-info">
          <strong>Temps de réponse</strong>
          Nous répondons généralement sous 48 heures ouvrées.
        </div>
      </div>
    </div>
  </div>
  <footer>
    Estimatiz — Données <a href="https://www.data.gouv.fr/fr/datasets/demandes-de-valeurs-foncieres/" target="_blank" rel="noopener">DVF · data.gouv.fr</a> &nbsp;|&nbsp; Paris 2014–2025
  </footer>
  <script>
  document.getElementById('btnSend').addEventListener('click', function() {
    const email   = document.getElementById('femail').value.trim();
    const sujet   = document.getElementById('fsujet').value;
    const message = document.getElementById('fmessage').value.trim();
    if (!email || !message || !sujet) {
      alert('Merci de remplir au minimum votre email, le sujet et le message.');
      return;
    }
    // Fallback mailto (à remplacer par un vrai backend si disponible)
    const fn    = document.getElementById('fname').value.trim();
    const ln    = document.getElementById('lname').value.trim();
    const nom   = [fn, ln].filter(Boolean).join(' ') || 'Visiteur';
    const corps = encodeURIComponent(`De : ${nom} <${email}>\nSujet : ${sujet}\n\n${message}`);
    window.location.href = `mailto:contact@estimatiz.fr?subject=${encodeURIComponent('[Estimatiz] ' + sujet)}&body=${corps}`;
    document.getElementById('formSuccess').style.display = 'block';
    document.getElementById('btnSend').disabled = true;
    document.getElementById('btnSend').style.opacity = '.6';
  });
  </script>
  <style>
    @media(max-width:700px){
      .content > div[style*="grid"]{ grid-template-columns:1fr !important; }
    }
  </style>
</body>
</html>