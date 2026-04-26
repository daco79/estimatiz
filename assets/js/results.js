/* ── results.js ── */
(function () {
  'use strict';

  function init() {
    /* PDF */
    const btnPdf = document.getElementById('btnGenPdf');
    if (btnPdf) btnPdf.addEventListener('click', () => window.print());

    /* Surface → recharge la page avec surface=X dans l'URL */
    const surfaceInput = document.getElementById('surfaceInput');
    const btnSurface   = document.getElementById('btnApplySurface');
    if (surfaceInput && btnSurface) {
      btnSurface.addEventListener('click', () => {
        const v = parseFloat(surfaceInput.value);
        if (!v || !Number.isFinite(v) || v <= 0) return;
        const p = new URLSearchParams(location.search);
        p.set('surface', Math.round(v));
        location.href = 'results?' + p.toString();
      });
      surfaceInput.addEventListener('keydown', e => { if (e.key === 'Enter') btnSurface.click(); });
    }

    /* En-tête impression */
    const printUrl  = document.getElementById('printUrl');
    const printDate = document.getElementById('printDateLabel');
    if (printUrl)  printUrl.textContent  = window.location.origin + '/';
    if (printDate) printDate.textContent = new Date().toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: '2-digit' });
    const printFooterLeft = document.getElementById('printFooterLeft');
    if (printFooterLeft) printFooterLeft.textContent = 'Estimatiz · Généré le ' + new Date().toLocaleDateString('fr-FR');
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
