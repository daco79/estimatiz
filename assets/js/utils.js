(function(window) {
  function formatEuro(n) {
    try {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0
      }).format(n);
    } catch {
      return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' €';
    }
  }

  function formatNumber(n) {
    return new Intl.NumberFormat('fr-FR').format(n);
  }

  function parseDateToTS(s) {
    if (!s) return 0;
    const str = String(s).trim();
    let m = str.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) return Date.UTC(+m[1], +m[2] - 1, +m[3]);
    m = str.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (m) return Date.UTC(+m[3], +m[2] - 1, +m[1]);
    const ts = Date.parse(str);
    return Number.isFinite(ts) ? ts : 0;
  }

  function formatDateFR(ts, fallback = '') {
    if (!ts) return fallback;
    try {
      return new Date(ts).toLocaleDateString('fr-FR');
    } catch {
      return fallback;
    }
  }

  function normalizeNum(raw) {
    if (raw == null) return NaN;
    const s = String(raw)
      .trim()
      .replace(/[\s\u00A0\u202F]/g, '')
      .replace(',', '.')
      .replace(/[^0-9.]/g, '');
    const n = Number(s);
    return Number.isFinite(n) ? n : NaN;
  }

  function percentileArr(arr, p) {
    if (!arr.length) return null;
    const sorted = [...arr].sort((a, b) => a - b);
    const n = sorted.length;
    if (n === 1) return sorted[0];
    const pos = (n - 1) * p;
    const lo = Math.floor(pos);
    const hi = Math.ceil(pos);
    if (lo === hi) return sorted[lo];
    return sorted[lo] * (1 - (pos - lo)) + sorted[hi] * (pos - lo);
  }

  function filterIQR(arr, k = 1.5) {
    if (arr.length < 4) return arr;
    const q1 = percentileArr(arr, 0.25);
    const q3 = percentileArr(arr, 0.75);
    const iqr = q3 - q1;
    if (iqr <= 0) return arr;
    return arr.filter(v => v >= q1 - k * iqr && v <= q3 + k * iqr);
  }

  function setStatus(el, message, type = 'info', html = false) {
    if (!el) return;
    el.className = `status is-visible ${type}`;
    if (html) el.innerHTML = message;
    else el.textContent = message;
  }

  function setLoading(btn, loading, labelIdle) {
    if (!btn) return;
    btn.disabled = loading;
    btn.innerHTML = loading ? '<span class="btn-loading"></span>Chargement...' : labelIdle;
  }

  window.EstimatizUtils = {
    formatEuro,
    formatNumber,
    parseDateToTS,
    formatDateFR,
    normalizeNum,
    percentileArr,
    filterIQR,
    setStatus,
    setLoading
  };

  window.formatEuro = window.formatEuro || formatEuro;
  window.parseDateToTS = window.parseDateToTS || parseDateToTS;
  window.formatDateFR = window.formatDateFR || formatDateFR;
  window.normalizeNum = window.normalizeNum || normalizeNum;
  window.percentileArr = window.percentileArr || percentileArr;
  window.filterIQR = window.filterIQR || filterIQR;
})(window);
