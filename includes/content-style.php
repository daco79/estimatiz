<style>
  /* ── Reset & base ── */
  :root{ --c1:#1E3A8A; --c2:#10B981; --c3:#111827; --c4:#F3F4F6; }
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; background:var(--c4); color:#111827; }

  /* ── Page header ── */
  .page-hero{ background:linear-gradient(135deg,#1E3A8A 0%,#1e40af 60%,#1d4ed8 100%); color:#fff; padding:48px 24px 40px; text-align:center; }
  .page-hero h1{ font-size:30px; font-weight:800; margin:0 0 10px; }
  .page-hero p{ font-size:16px; color:rgba(255,255,255,.82); max-width:560px; margin:0 auto; line-height:1.6; }

  /* ── Contenu ── */
  .content{ max-width:860px; margin:0 auto; padding:48px 24px 64px; }

  /* ── Sections ── */
  .c-section{ margin-bottom:40px; }
  .c-section h2{ font-size:20px; font-weight:800; color:var(--c1); margin:0 0 14px; padding-bottom:10px; border-bottom:2px solid #e5e7eb; }
  .c-section h3{ font-size:15px; font-weight:700; color:#111827; margin:20px 0 6px; }
  .c-section p{ font-size:14px; color:#374151; line-height:1.75; margin:0 0 10px; }
  .c-section ul, .c-section ol{ font-size:14px; color:#374151; line-height:1.8; padding-left:20px; margin:0 0 10px; }
  .c-section li{ margin-bottom:4px; }

  /* ── Cards ── */
  .c-cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px; margin-top:16px; }
  .c-card{ background:#fff; border-radius:14px; padding:20px; border:1px solid #e5e7eb; box-shadow:0 2px 8px rgba(0,0,0,.05); }
  .c-card-icon{ font-size:28px; margin-bottom:10px; }
  .c-card h3{ font-size:14px; font-weight:700; margin:0 0 6px; color:#111827; }
  .c-card p{ font-size:13px; color:#6B7280; margin:0; line-height:1.6; }

  /* ── Bloc info ── */
  .c-info{ background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:16px 18px; margin:16px 0; font-size:14px; color:#1d4ed8; line-height:1.6; }
  .c-info strong{ display:block; margin-bottom:4px; }
  .c-warn{ background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:16px 18px; margin:16px 0; font-size:14px; color:#92400e; line-height:1.6; }

  /* ── Étapes ── */
  .c-steps{ display:flex; flex-direction:column; gap:16px; margin-top:16px; }
  .c-step{ display:flex; gap:16px; align-items:flex-start; }
  .c-step-num{ width:32px; height:32px; border-radius:50%; background:var(--c1); color:#fff; font-size:14px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px; }
  .c-step-body h3{ font-size:14px; font-weight:700; margin:0 0 4px; }
  .c-step-body p{ font-size:13px; color:#6B7280; margin:0; line-height:1.6; }

  /* ── FAQ accordéon ── */
  .faq-list{ display:flex; flex-direction:column; gap:10px; margin-top:16px; }
  details.faq-item{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
  details.faq-item[open]{ border-color:#bfdbfe; }
  details.faq-item summary{ padding:16px 18px; font-size:14px; font-weight:700; color:#111827; cursor:pointer; list-style:none; display:flex; justify-content:space-between; align-items:center; gap:12px; }
  details.faq-item summary::-webkit-details-marker{ display:none; }
  details.faq-item summary::after{ content:'＋'; font-size:18px; color:#9CA3AF; flex-shrink:0; }
  details.faq-item[open] summary::after{ content:'－'; color:var(--c1); }
  details.faq-item[open] summary{ color:var(--c1); }
  .faq-answer{ padding:0 18px 16px; font-size:14px; color:#374151; line-height:1.75; }
  .faq-answer p{ margin:0 0 8px; }
  .faq-answer p:last-child{ margin:0; }

  /* ── Formulaire contact ── */
  .c-form{ background:#fff; border-radius:16px; padding:28px; border:1px solid #e5e7eb; box-shadow:0 2px 12px rgba(0,0,0,.06); }
  .c-form label{ display:block; font-size:13px; font-weight:700; color:#374151; margin-bottom:6px; margin-top:16px; }
  .c-form label:first-child{ margin-top:0; }
  .c-form input, .c-form select, .c-form textarea{ width:100%; padding:11px 14px; font-size:14px; border:1px solid #d1d5db; border-radius:10px; font-family:inherit; color:#111827; background:#fff; }
  .c-form input:focus, .c-form select:focus, .c-form textarea:focus{ outline:none; border-color:#1E3A8A; box-shadow:0 0 0 3px rgba(30,58,138,.1); }
  .c-form textarea{ min-height:130px; resize:vertical; }
  .c-form-row{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .c-btn{ display:inline-block; margin-top:20px; padding:13px 28px; font-size:15px; font-weight:700; background:var(--c1); color:#fff; border:none; border-radius:12px; cursor:pointer; font-family:inherit; }
  .c-btn:hover{ background:#1e40af; }
  .c-success{ display:none; margin-top:16px; padding:14px 16px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; font-size:14px; color:#047857; font-weight:600; }

  /* ── Stat chips ── */
  .c-stat-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-top:16px; }
  .c-stat{ background:#fff; border-radius:12px; padding:18px; text-align:center; border:1px solid #e5e7eb; }
  .c-stat-val{ font-size:24px; font-weight:800; color:var(--c1); }
  .c-stat-lbl{ font-size:12px; color:#6B7280; margin-top:4px; }

  /* ── Footer ── */
  footer{ background:#111827; color:rgba(255,255,255,.6); text-align:center; padding:24px; font-size:13px; }
  footer a{ color:rgba(255,255,255,.8); text-decoration:none; }
  footer a:hover{ color:#fff; }

  /* ── Mobile ── */
  @media(max-width:640px){
    .page-hero{ padding:36px 16px 28px; }
    .page-hero h1{ font-size:24px; }
    .content{ padding:32px 16px 48px; }
    .c-form-row{ grid-template-columns:1fr; }
    .c-step{ flex-direction:column; gap:8px; }
  }
</style>
