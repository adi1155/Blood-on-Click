<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blood on Click — Every Drop Counts</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Outfit:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --ink:         #080709;
  --ink-2:       #101013;
  --ink-3:       #18181d;
  --border:      rgba(255,255,255,0.06);
  --border-red:  rgba(200,30,30,0.35);
  --red:         #bf1b1b;
  --red-bright:  #e83535;
  --red-glow:    rgba(191,27,27,0.22);
  --red-pale:    rgba(191,27,27,0.08);
  --cream:       #f0ece3;
  --cream-dim:   #b8b2a8;
  --cream-muted: #6a6560;
  --font-display: 'Playfair Display', Georgia, serif;
  --font-body:    'Outfit', sans-serif;
  --font-mono:    'DM Mono', monospace;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 16px; scroll-behavior: smooth; }
body { background: var(--ink); color: var(--cream); font-family: var(--font-body); font-weight: 400; line-height: 1.65; overflow-x: hidden; }
body::before { content: ''; position: fixed; inset: 0; opacity: 0.025; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E"); pointer-events: none; z-index: 1000; }
::-webkit-scrollbar { width: 4px; } ::-webkit-scrollbar-track { background: var(--ink); } ::-webkit-scrollbar-thumb { background: var(--red); border-radius: 2px; }

/* NAV */
.nav { position: fixed; top: 0; left: 0; right: 0; z-index: 900; display: flex; align-items: center; padding: 0 3rem; height: 72px; transition: background 0.4s, border-color 0.4s; }
.nav.scrolled { background: rgba(8,7,9,0.92); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border); }
.nav-brand { font-family: var(--font-display); font-size: 1.5rem; font-weight: 900; color: var(--cream); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.nav-brand em { color: var(--red-bright); font-style: normal; }
.nav-drop { display: inline-block; width: 10px; height: 13px; background: var(--red-bright); border-radius: 50% 50% 50% 50% / 40% 40% 60% 60%; animation: dropPulse 3s ease-in-out infinite; }
@keyframes dropPulse { 0%,100% { transform: scale(1); opacity:1; } 50% { transform: scale(1.15); opacity:0.85; } }
.nav-links { display: flex; gap: 0.15rem; flex: 1; justify-content: center; }
.nav-links a { color: var(--cream-dim); text-decoration: none; font-size: 0.875rem; font-weight: 500; padding: 0.4rem 1rem; border-radius: 6px; transition: all 0.2s; }
.nav-links a:hover { color: var(--cream); background: var(--red-pale); }
.nav-cta { display: flex; gap: 0.6rem; align-items: center; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; font-family: var(--font-body); font-weight: 600; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: all 0.22s; white-space: nowrap; }
.btn-ghost { font-size: 0.875rem; padding: 0.5rem 1.1rem; color: var(--cream-dim); background: transparent; border: 1px solid var(--border); }
.btn-ghost:hover { color: var(--cream); border-color: rgba(255,255,255,0.2); }
.btn-red { font-size: 0.9rem; padding: 0.55rem 1.3rem; background: var(--red); color: #fff; }
.btn-red:hover { background: var(--red-bright); box-shadow: 0 0 28px var(--red-glow); transform: translateY(-1px); }
.btn-red-lg { font-size: 1rem; padding: 0.9rem 2.2rem; background: var(--red); color: #fff; border-radius: 10px; }
.btn-red-lg:hover { background: var(--red-bright); box-shadow: 0 6px 40px var(--red-glow); transform: translateY(-2px); }
.btn-outline-lg { font-size: 1rem; padding: 0.88rem 2.2rem; background: transparent; color: var(--cream); border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; }
.btn-outline-lg:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.35); }

/* HERO */
.hero { position: relative; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; padding: 140px 3rem 6rem; overflow: hidden; }
.hero-light-1 { position: absolute; top: -10%; right: -5%; width: 700px; height: 700px; background: radial-gradient(circle, rgba(180,20,20,0.18) 0%, transparent 65%); pointer-events: none; }
.hero-light-2 { position: absolute; bottom: 0%; left: -8%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(150,10,10,0.1) 0%, transparent 65%); pointer-events: none; }
.hero-drop-bg { position: absolute; right: 6%; top: 50%; transform: translateY(-50%); width: 380px; height: 460px; background: linear-gradient(160deg, rgba(180,20,20,0.22) 0%, rgba(100,5,5,0.08) 80%); border-radius: 50% 50% 50% 50% / 40% 40% 60% 60%; border: 1px solid rgba(180,20,20,0.25); animation: dropFloat 6s ease-in-out infinite; pointer-events: none; }
@keyframes dropFloat { 0%,100% { transform: translateY(-50%) scale(1); } 50% { transform: translateY(-53%) scale(1.02); } }
.hero::after { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px); background-size: 60px 60px; pointer-events: none; }
.hero-tag { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.72rem; font-weight: 600; font-family: var(--font-mono); letter-spacing: 0.12em; text-transform: uppercase; color: var(--red-bright); background: rgba(191,27,27,0.1); border: 1px solid var(--border-red); padding: 0.35rem 0.9rem; border-radius: 20px; margin-bottom: 2.5rem; animation: fadeUp 0.6s ease both; }
.hero-tag-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--red-bright); animation: blink 1.4s ease-in-out infinite; }
@keyframes blink { 0%,100% { opacity:1; } 50% { opacity:0.2; } }
.hero-headline { font-family: var(--font-display); font-size: clamp(3.5rem, 7vw, 7rem); font-weight: 900; line-height: 0.95; letter-spacing: -0.025em; max-width: 780px; margin-bottom: 1.75rem; }
.hero-headline .line { display: block; overflow: hidden; }
.hero-headline .line span { display: block; animation: slideUp 0.8s cubic-bezier(0.16,1,0.3,1) both; }
.hero-headline .line:nth-child(1) span { animation-delay: 0.1s; }
.hero-headline .line:nth-child(2) span { animation-delay: 0.22s; }
.hero-headline .line:nth-child(3) span { animation-delay: 0.34s; }
.hero-headline .red { color: var(--red-bright); font-style: italic; }
@keyframes slideUp { from { transform: translateY(110%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.hero-sub { max-width: 520px; font-size: 1.1rem; font-weight: 300; color: var(--cream-dim); line-height: 1.7; margin-bottom: 3rem; animation: fadeUp 0.8s 0.5s ease both; }
.hero-sub strong { color: var(--cream); font-weight: 500; }
.hero-actions { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; animation: fadeUp 0.8s 0.65s ease both; margin-bottom: 5rem; }
.hero-note { font-size: 0.78rem; color: var(--cream-muted); display: flex; align-items: center; gap: 0.4rem; }
.hero-stats { display: flex; gap: 0; border-top: 1px solid var(--border); padding-top: 2.5rem; animation: fadeUp 0.8s 0.8s ease both; }
.hero-stat { flex: 1; padding-right: 2.5rem; border-right: 1px solid var(--border); margin-right: 2.5rem; }
.hero-stat:last-child { border-right: none; margin-right: 0; }
.hero-stat-val { font-family: var(--font-display); font-size: 2.5rem; font-weight: 700; color: var(--red-bright); line-height: 1; margin-bottom: 0.2rem; }
.hero-stat-label { font-size: 0.8rem; color: var(--cream-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.06em; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* TICKER */
.ticker-strip { background: var(--red); overflow: hidden; padding: 0.7rem 0; display: flex; }
.ticker-inner { display: flex; animation: ticker 22s linear infinite; white-space: nowrap; }
.ticker-item { display: inline-flex; align-items: center; gap: 1rem; padding: 0 2.5rem; font-size: 0.78rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.9); flex-shrink: 0; }
@keyframes ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }

/* SECTIONS */
section { position: relative; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 3rem; }
.section-eyebrow { font-family: var(--font-mono); font-size: 0.7rem; font-weight: 500; letter-spacing: 0.14em; text-transform: uppercase; color: var(--red-bright); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.6rem; }
.section-eyebrow::before { content: ''; display: block; width: 24px; height: 1px; background: var(--red-bright); }
.section-title { font-family: var(--font-display); font-size: clamp(2.2rem, 4vw, 3.5rem); font-weight: 900; line-height: 1.1; letter-spacing: -0.02em; margin-bottom: 1rem; }
.section-title em { font-style: italic; color: var(--red-bright); }
.section-lead { font-size: 1.05rem; font-weight: 300; color: var(--cream-dim); max-width: 560px; line-height: 1.75; }
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.16,1,0.3,1); }
.reveal.visible { opacity: 1; transform: translateY(0); }

/* HOW IT WORKS */
.how-section { padding: 8rem 0; background: var(--ink-2); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.how-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; align-items: center; margin-top: 5rem; }
.how-steps { display: flex; flex-direction: column; gap: 0; }
.how-step { display: flex; gap: 1.75rem; padding: 2rem 0; border-bottom: 1px solid var(--border); transition: all 0.3s; }
.how-step:first-child { padding-top: 0; }
.how-step:last-child { border-bottom: none; padding-bottom: 0; }
.how-step:hover .step-num { background: var(--red); color: #fff; border-color: var(--red); }
.step-num { flex-shrink: 0; width: 44px; height: 44px; border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: var(--font-mono); font-size: 0.85rem; font-weight: 500; color: var(--cream-dim); transition: all 0.3s; background: var(--ink-3); }
.step-body h3 { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; margin-bottom: 0.4rem; margin-top: 0.5rem; }
.step-body p { font-size: 0.9rem; color: var(--cream-dim); line-height: 1.7; }

/* BLOOD GROUPS */
.blood-section { padding: 8rem 0; overflow: hidden; }
.blood-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--border); border: 1px solid var(--border); border-radius: 18px; overflow: hidden; margin-top: 4rem; }
.blood-cell { background: var(--ink-2); padding: 2rem; display: flex; flex-direction: column; gap: 0.5rem; transition: all 0.25s; cursor: default; position: relative; overflow: hidden; }
.blood-cell::before { content: ''; position: absolute; inset: 0; background: linear-gradient(145deg, var(--red-pale) 0%, transparent 60%); opacity: 0; transition: opacity 0.3s; }
.blood-cell:hover { background: var(--ink-3); }
.blood-cell:hover::before { opacity: 1; }
.blood-cell:hover .blood-letter { color: var(--red-bright); }
.blood-letter { font-family: var(--font-display); font-size: 3rem; font-weight: 900; line-height: 1; color: var(--cream); transition: color 0.3s; letter-spacing: -0.02em; }
.blood-type-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--cream-muted); }
.blood-compat { margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.72rem; color: var(--cream-muted); }
.blood-compat span { color: var(--cream-dim); }

/* IMPACT */
.impact-section { padding: 8rem 0; background: var(--ink-2); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.impact-layout { display: grid; grid-template-columns: 1fr 1.2fr; gap: 5rem; align-items: center; margin-top: 5rem; }
.impact-nums { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--border); border: 1px solid var(--border); border-radius: 18px; overflow: hidden; }
.impact-num-cell { background: var(--ink-3); padding: 2.5rem 2rem; transition: background 0.25s; }
.impact-num-cell:hover { background: #1e1016; }
.impact-num-val { font-family: var(--font-display); font-size: 3rem; font-weight: 900; color: var(--red-bright); line-height: 1; margin-bottom: 0.4rem; }
.impact-num-label { font-size: 0.82rem; font-weight: 500; color: var(--cream-dim); line-height: 1.4; }
.impact-points { display: flex; flex-direction: column; gap: 2rem; }
.impact-point { display: flex; gap: 1.25rem; align-items: flex-start; }
.impact-icon { flex-shrink: 0; width: 44px; height: 44px; background: var(--red-pale); border: 1px solid var(--border-red); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.impact-point h4 { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; margin-bottom: 0.2rem; }
.impact-point p { font-size: 0.875rem; color: var(--cream-dim); line-height: 1.65; }

/* WHO IS IT FOR */
.roles-section { padding: 8rem 0; }
.roles-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 4rem; }
.role-card { background: var(--ink-2); border: 1px solid var(--border); border-radius: 18px; padding: 2.5rem 2rem; transition: all 0.3s; position: relative; overflow: hidden; }
.role-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; transition: opacity 0.3s; }
.role-card.admin-card::before { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.role-card.bank-card::before  { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
.role-card.donor-card::before { background: linear-gradient(90deg,#10b981,#34d399); }
.role-card:hover { border-color: var(--border-red); transform: translateY(-4px); }
.role-icon { font-size: 2.5rem; margin-bottom: 1.25rem; }
.role-card h3 { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; }
.role-card p { font-size: 0.88rem; color: var(--cream-dim); line-height: 1.7; margin-bottom: 1.25rem; }
.role-features { list-style: none; display: flex; flex-direction: column; gap: 0.4rem; }
.role-features li { font-size: 0.82rem; color: var(--cream-dim); display: flex; align-items: flex-start; gap: 0.5rem; }
.role-features li::before { content: '✓'; color: var(--red-bright); font-weight: 700; flex-shrink: 0; }

/* CTA */
.cta-section { padding: 0 0 8rem; }
.cta-block { background: linear-gradient(135deg, #200808 0%, #150505 40%, #0f0f13 100%); border: 1px solid var(--border-red); border-radius: 24px; padding: 5rem; text-align: center; position: relative; overflow: hidden; }
.cta-block::before { content: ''; position: absolute; top: -50%; left: 50%; transform: translateX(-50%); width: 600px; height: 400px; background: radial-gradient(ellipse, rgba(180,20,20,0.2) 0%, transparent 65%); pointer-events: none; }
.cta-block h2 { font-family: var(--font-display); font-size: clamp(2rem, 5vw, 4rem); font-weight: 900; line-height: 1.1; letter-spacing: -0.02em; margin-bottom: 1.25rem; position: relative; z-index: 1; }
.cta-block h2 em { color: var(--red-bright); font-style: italic; }
.cta-block p { font-size: 1.05rem; color: var(--cream-dim); max-width: 500px; margin: 0 auto 2.5rem; font-weight: 300; line-height: 1.7; position: relative; z-index: 1; }
.cta-btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; position: relative; z-index: 1; }

/* FAQ */
.faq-section { padding: 8rem 0; background: var(--ink-2); border-top: 1px solid var(--border); }
.faq-layout { display: grid; grid-template-columns: 1fr 1.4fr; gap: 5rem; align-items: start; margin-top: 4rem; }
.faq-list { display: flex; flex-direction: column; gap: 0; }
.faq-item { border-bottom: 1px solid var(--border); }
.faq-q { width: 100%; background: none; border: none; text-align: left; color: var(--cream); font-family: var(--font-body); font-size: 0.95rem; font-weight: 500; padding: 1.25rem 0; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 1rem; transition: color 0.2s; }
.faq-q:hover { color: var(--red-bright); }
.faq-q.open { color: var(--red-bright); }
.faq-chevron { flex-shrink: 0; width: 20px; height: 20px; border: 1px solid var(--border); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: var(--cream-muted); transition: all 0.3s; }
.faq-q.open .faq-chevron { background: var(--red-pale); border-color: var(--border-red); color: var(--red-bright); transform: rotate(180deg); }
.faq-a { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1); }
.faq-a-inner { padding-bottom: 1.25rem; font-size: 0.875rem; color: var(--cream-dim); line-height: 1.75; }

/* FOOTER */
.footer { background: var(--ink); border-top: 1px solid var(--border); padding: 4rem 0 2rem; }
.footer-grid { display: grid; grid-template-columns: 1.8fr 1fr 1fr 1fr; gap: 3rem; margin-bottom: 4rem; }
.footer-brand h3 { font-family: var(--font-display); font-size: 1.5rem; font-weight: 900; margin-bottom: 0.75rem; }
.footer-brand h3 em { color: var(--red-bright); font-style: normal; }
.footer-brand p { font-size: 0.85rem; color: var(--cream-muted); line-height: 1.7; max-width: 280px; margin-bottom: 1.5rem; }
.footer-col h4 { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--cream-dim); margin-bottom: 1.1rem; }
.footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }
.footer-col ul li a { font-size: 0.85rem; color: var(--cream-muted); text-decoration: none; transition: color 0.2s; }
.footer-col ul li a:hover { color: var(--cream); }
.footer-bottom { padding-top: 2rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
.footer-bottom p { font-size: 0.78rem; color: var(--cream-muted); }

/* RESPONSIVE */
@media (max-width: 900px) {
  .hero { padding: 120px 1.5rem 4rem; }
  .hero-drop-bg { display: none; }
  .hero-stats { flex-direction: column; gap: 1.5rem; }
  .hero-stat { border-right: none; margin-right: 0; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; }
  .hero-stat:last-child { border-bottom: none; }
  .container { padding: 0 1.5rem; }
  .how-grid, .impact-layout, .faq-layout { grid-template-columns: 1fr; gap: 3rem; }
  .blood-grid { grid-template-columns: repeat(2,1fr); }
  .roles-grid { grid-template-columns: 1fr; }
  .footer-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
  .cta-block { padding: 3rem 1.5rem; }
  .nav { padding: 0 1.5rem; }
  .nav-links { display: none; }
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="navbar">
  <a href="#" class="nav-brand"><span class="nav-drop"></span>Blood<em>on</em>Click</a>
  <div class="nav-links">
    <a href="#how">How It Works</a>
    <a href="#who">Who It's For</a>
    <a href="#blood-groups">Blood Groups</a>
    <a href="#faq">FAQ</a>
  </div>
  <div class="nav-cta">
    <a href="/boc/login.php" class="btn btn-ghost">Sign In</a>
    <a href="/boc/signup.php" class="btn btn-red">Get Started →</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-light-1"></div>
  <div class="hero-light-2"></div>
  <div class="hero-drop-bg"></div>
  <div class="hero-tag"><span class="hero-tag-dot"></span>Pakistan's First Multi-Role Blood Network</div>
  <h1 class="hero-headline">
    <span class="line"><span>One click.</span></span>
    <span class="line"><span>One <em>drop</em>.</span></span>
    <span class="line"><span>One life saved.</span></span>
  </h1>
  <p class="hero-sub"><strong>Blood on Click</strong> connects blood banks, donors, and seekers on one unified platform — with real-time stock monitoring, instant notifications, and smart search across every city in Pakistan.</p>
  <div class="hero-actions">
    <a href="signup.php" class="btn btn-red-lg">Register Now</a>
    <a href="search.php" class="btn btn-outline-lg">Find Blood →</a>
    <span class="hero-note">🔒 Free · Secure · Verified</span>
  </div>
  <div class="hero-stats">
    <div class="hero-stat"><div class="hero-stat-val" data-count="12000">0</div><div class="hero-stat-label">Active Donors</div></div>
    <div class="hero-stat"><div class="hero-stat-val" data-count="8">0</div><div class="hero-stat-label">Blood Types</div></div>
    <div class="hero-stat"><div class="hero-stat-val" data-count="47">0</div><div class="hero-stat-label">Cities Served</div></div>
    <div class="hero-stat"><div class="hero-stat-val" data-count="3">0</div><div class="hero-stat-label">Avg Response (min)</div></div>
  </div>
</section>

<!-- TICKER -->
<div class="ticker-strip" aria-hidden="true">
  <div class="ticker-inner">
    <span class="ticker-item">⬟ Every 2 Seconds Someone Needs Blood ◆</span>
    <span class="ticker-item">⬟ One Donation Saves Up To 3 Lives ◆</span>
    <span class="ticker-item">⬟ O− Universal Donor — Rare &amp; Vital ◆</span>
    <span class="ticker-item">⬟ Real-Time Stock Monitoring ◆</span>
    <span class="ticker-item">⬟ Instant Donor Notifications ◆</span>
    <span class="ticker-item">⬟ Blood Cannot Be Manufactured ◆</span>
    <span class="ticker-item">⬟ Every 2 Seconds Someone Needs Blood ◆</span>
    <span class="ticker-item">⬟ One Donation Saves Up To 3 Lives ◆</span>
    <span class="ticker-item">⬟ O− Universal Donor — Rare &amp; Vital ◆</span>
    <span class="ticker-item">⬟ Real-Time Stock Monitoring ◆</span>
    <span class="ticker-item">⬟ Instant Donor Notifications ◆</span>
    <span class="ticker-item">⬟ Blood Cannot Be Manufactured ◆</span>
  </div>
</div>

<!-- HOW IT WORKS -->
<section class="how-section" id="how">
  <div class="container">
    <div class="reveal">
      <div class="section-eyebrow">How It Works</div>
      <h2 class="section-title">Three roles.<br><em>One platform.</em></h2>
      <p class="section-lead">Blood on Click unifies blood banks, donors, and seekers in a single intelligent system — with notifications, assessments, stock management, and smart search.</p>
    </div>
    <div class="how-grid">
      <div class="how-steps reveal">
        <div class="how-step"><div class="step-num">01</div><div class="step-body"><h3>Register Your Role</h3><p>Sign up as a Donor, Blood Seeker, or Blood Bank staff. Each role gets its own dashboard with tailored features and access controls.</p></div></div>
        <div class="how-step"><div class="step-num">02</div><div class="step-body"><h3>Search & Connect</h3><p>Seekers find donors or banks using blood group and city filters. Color-coded blood group icons make it instant to identify compatibility.</p></div></div>
        <div class="how-step"><div class="step-num">03</div><div class="step-body"><h3>Notify & Act</h3><p>Blood banks send real-time alerts when stock is low. Donors receive targeted notifications. Medical assessments are tracked and shared securely.</p></div></div>
        <div class="how-step"><div class="step-num">04</div><div class="step-body"><h3>Monitor Progress</h3><p>Track donation history, medical reports, stock levels, and fulfilled requests — all in one place with a complete audit trail.</p></div></div>
      </div>
      <div class="reveal" style="background:linear-gradient(145deg,#1a0d0d,#18181d);border:1px solid var(--border-red);border-radius:18px;padding:2.5rem;display:flex;flex-direction:column;gap:1rem;">
        <div style="font-size:.7rem;font-family:var(--font-mono);letter-spacing:.1em;text-transform:uppercase;color:var(--red-bright);margin-bottom:.5rem;">Live System Activity</div>
        <div style="background:var(--ink-3);border-radius:12px;padding:1.25rem;">
          <div style="font-size:.75rem;color:var(--cream-muted);margin-bottom:.5rem;">🩸 Donation Request</div>
          <div style="font-weight:600;font-size:.95rem;">O+ needed at Services Hospital, Lahore</div>
          <div style="font-size:.75rem;color:var(--cream-muted);margin-top:.25rem;">2 units · Critical · 3 donors notified</div>
        </div>
        <div style="background:var(--ink-3);border-radius:12px;padding:1.25rem;">
          <div style="font-size:.75rem;color:var(--cream-muted);margin-bottom:.5rem;">📉 Stock Alert</div>
          <div style="font-weight:600;font-size:.95rem;">AB− critically low at Jinnah Hospital</div>
          <div style="font-size:.75rem;color:var(--cream-muted);margin-top:.25rem;">Only 2 units remaining · Alert broadcast sent</div>
        </div>
        <div style="background:var(--ink-3);border-radius:12px;padding:1.25rem;">
          <div style="font-size:.75rem;color:var(--cream-muted);margin-bottom:.5rem;">✓ Assessment Complete</div>
          <div style="font-weight:600;font-size:.95rem;">Sarah Johnson — A+ · Cleared for donation</div>
          <div style="font-size:.75rem;color:var(--cream-muted);margin-top:.25rem;">Hb: 13.5 · BP: 118/76 · All screenings negative</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WHO IS IT FOR -->
<section class="roles-section" id="who">
  <div class="container">
    <div class="reveal" style="text-align:center;max-width:620px;margin:0 auto;">
      <div class="section-eyebrow" style="justify-content:center;">Three Roles</div>
      <h2 class="section-title">Built for <em>everyone</em><br>in the blood chain</h2>
    </div>
    <div class="roles-grid reveal">
      <div class="role-card admin-card">
        <div class="role-icon">👑</div>
        <h3>Admin</h3>
        <p>Full system control — manage users, conduct medical assessments, send notifications, recommend blood banks, and oversee all activity.</p>
        <ul class="role-features">
          <li>Manage all user accounts</li>
          <li>Conduct &amp; record medical assessments</li>
          <li>Broadcast system notifications</li>
          <li>Recommend blood banks to seekers</li>
          <li>View complete audit trails</li>
        </ul>
      </div>
      <div class="role-card bank-card">
        <div class="role-icon">🏥</div>
        <h3>Blood Bank</h3>
        <p>Monitor blood stock in real-time, respond to incoming requests, send targeted donor alerts, and manage all transfusion records.</p>
        <ul class="role-features">
          <li>Real-time stock level dashboard</li>
          <li>Auto low-stock alerts to donors</li>
          <li>Manage incoming blood requests</li>
          <li>Send targeted notifications</li>
          <li>Track donation history</li>
        </ul>
      </div>
      <div class="role-card donor-card">
        <div class="role-icon">🩸</div>
        <h3>Donor &amp; Seeker</h3>
        <p>Donors track their health reports and donation history. Seekers find matching donors with color-coded blood group icons instantly.</p>
        <ul class="role-features">
          <li>Donation history &amp; health reports</li>
          <li>Receive urgent donation alerts</li>
          <li>Find nearby blood banks</li>
          <li>Color-coded donor search map</li>
          <li>Post &amp; manage blood requests</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- BLOOD GROUPS -->
<section style="padding:8rem 0;background:var(--ink-2);border-top:1px solid var(--border);" id="blood-groups">
  <div class="container">
    <div class="reveal" style="text-align:center;max-width:600px;margin:0 auto;">
      <div class="section-eyebrow" style="justify-content:center;">Blood Types</div>
      <h2 class="section-title">All 8 blood groups,<br><em>one platform</em></h2>
    </div>
    <div class="blood-grid reveal">
      <div class="blood-cell"><div class="blood-letter">A+</div><div class="blood-type-label">Type A Positive</div><div class="blood-compat">Donates to: <span>A+, AB+</span></div></div>
      <div class="blood-cell"><div class="blood-letter">A−</div><div class="blood-type-label">Type A Negative</div><div class="blood-compat">Donates to: <span>A+, A−, AB+, AB−</span></div></div>
      <div class="blood-cell"><div class="blood-letter">B+</div><div class="blood-type-label">Type B Positive</div><div class="blood-compat">Donates to: <span>B+, AB+</span></div></div>
      <div class="blood-cell"><div class="blood-letter">B−</div><div class="blood-type-label">Type B Negative</div><div class="blood-compat">Donates to: <span>B+, B−, AB+, AB−</span></div></div>
      <div class="blood-cell"><div class="blood-letter">AB+</div><div class="blood-type-label">Type AB Positive</div><div class="blood-compat">Donates to: <span>AB+ only</span></div></div>
      <div class="blood-cell"><div class="blood-letter">AB−</div><div class="blood-type-label">Type AB Negative</div><div class="blood-compat">Donates to: <span>All AB types</span></div></div>
      <div class="blood-cell"><div class="blood-letter">O+</div><div class="blood-type-label">Type O Positive</div><div class="blood-compat">Donates to: <span>All positive types</span></div></div>
      <div class="blood-cell" style="background:linear-gradient(145deg,#180a0a,var(--ink-3));border-color:rgba(180,20,20,0.3);">
        <div class="blood-letter" style="color:var(--red-bright);">O−</div>
        <div class="blood-type-label">Universal Donor ★</div>
        <div class="blood-compat">Donates to: <span>Everyone</span></div>
      </div>
    </div>
  </div>
</section>

<!-- IMPACT -->
<section class="impact-section">
  <div class="container">
    <div class="impact-layout">
      <div class="reveal">
        <div class="section-eyebrow">Why It Matters</div>
        <h2 class="section-title">Your blood is<br>someone's <em>lifeline</em></h2>
        <p class="section-lead" style="margin-bottom:2.5rem;">The need for blood never stops. Our platform ensures the right blood reaches the right person at the right time — across every city.</p>
        <div class="impact-points">
          <div class="impact-point"><div class="impact-icon">🏥</div><div><h4>Emergency Surgeries</h4><p>Accident and trauma patients require immediate transfusions. Our system routes urgent requests to available donors within minutes.</p></div></div>
          <div class="impact-point"><div class="impact-icon">👶</div><div><h4>Chronic Patients</h4><p>Cancer and thalassemia patients need regular transfusions. Blood banks can schedule and notify donors ahead of time.</p></div></div>
          <div class="impact-point"><div class="impact-icon">🤱</div><div><h4>Maternal Health</h4><p>Postpartum hemorrhage is a leading cause of maternal death. Our network ensures help is always within reach.</p></div></div>
        </div>
      </div>
      <div class="impact-nums reveal">
        <div class="impact-num-cell"><div class="impact-num-val" data-count="3">0</div><div class="impact-num-label">Lives saved per donation</div></div>
        <div class="impact-num-cell"><div class="impact-num-val" data-count="450">0</div><div class="impact-num-label">ml per session</div></div>
        <div class="impact-num-cell"><div class="impact-num-val" data-count="90">0</div><div class="impact-num-label">Days until next donation</div></div>
        <div class="impact-num-cell"><div class="impact-num-val" data-count="42">0</div><div class="impact-num-label">Days blood can be stored</div></div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="container">
    <div class="cta-block reveal">
      <h2>Be the reason<br>someone <em>survives</em> today</h2>
      <p>Join as a donor, register your blood bank, or find life-saving blood — all in one place.</p>
      <div class="cta-btns">
        <a href="signup.php" class="btn btn-red-lg">Register Now</a>
        <a href="search.php" class="btn btn-outline-lg">Find Blood</a>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="faq-section" id="faq">
  <div class="container">
    <div class="faq-layout">
      <div class="reveal">
        <div class="section-eyebrow">FAQ</div>
        <h2 class="section-title">Questions<br><em>answered</em></h2>
        <p class="section-lead">Everything you need to know before getting started.</p>
        <div style="margin-top:2.5rem;"><a href="signup.php" class="btn btn-red">Register Today →</a></div>
      </div>
      <div class="faq-list reveal">
        <div class="faq-item"><button class="faq-q" onclick="toggleFaq(this)">Who can register on this platform?<span class="faq-chevron">▾</span></button><div class="faq-a"><div class="faq-a-inner">Anyone 18+ can register as a Donor or Blood Seeker. Blood bank staff can register under the Blood Bank role with their institution details. Admins are set up by the system administrator.</div></div></div>
        <div class="faq-item"><button class="faq-q" onclick="toggleFaq(this)">How does real-time stock monitoring work?<span class="faq-chevron">▾</span></button><div class="faq-a"><div class="faq-a-inner">Blood bank staff update unit counts directly from their dashboard. When any blood group drops below the alert threshold, the system automatically sends notifications to matching donors in the same city.</div></div></div>
        <div class="faq-item"><button class="faq-q" onclick="toggleFaq(this)">What are medical assessments?<span class="faq-chevron">▾</span></button><div class="faq-a"><div class="faq-a-inner">After each donation, an admin records the donor's vital signs (hemoglobin, blood pressure, pulse, weight, temperature) and disease screening results (HIV, Hepatitis B/C, Syphilis, Malaria). Donors can view all their reports anytime.</div></div></div>
        <div class="faq-item"><button class="faq-q" onclick="toggleFaq(this)">How do seekers find donors?<span class="faq-chevron">▾</span></button><div class="faq-a"><div class="faq-a-inner">Seekers can search by blood group and city. Results are displayed with color-coded blood group icons — each blood type has a unique color for instant visual identification. Available donors show contact information directly.</div></div></div>
        <div class="faq-item"><button class="faq-q" onclick="toggleFaq(this)">Is my personal data private?<span class="faq-chevron">▾</span></button><div class="faq-a"><div class="faq-a-inner">Yes. All passwords are bcrypt-hashed. Contact information is only visible to logged-in users. Data is never sold or shared externally. You remain in full control of your profile and availability status at all times.</div></div></div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <h3>Blood<em>on</em>Click</h3>
        <p>Pakistan's multi-role blood donor management platform — connecting banks, donors, and seekers in one intelligent system.</p>
      </div>
      <div class="footer-col"><h4>Platform</h4><ul><li><a href="signup.php">Register</a></li><li><a href="login.php">Sign In</a></li><li><a href="search.php">Find Blood</a></li></ul></div>
      <div class="footer-col"><h4>Roles</h4><ul><li><a href="signup.php">Donor</a></li><li><a href="signup.php">Blood Seeker</a></li><li><a href="signup.php">Blood Bank</a></li></ul></div>
      <div class="footer-col"><h4>Contact</h4><ul><li><a href="#">Emergency: 1122</a></li><li><a href="#">Lahore, Pakistan</a></li><li><a href="#">Available 24/7</a></li></ul></div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 Blood on Click v2. All rights reserved.</p>
      <p style="font-size:.78rem;color:var(--cream-muted);">Built with purpose, not profit.</p>
    </div>
  </div>
</footer>

<script>
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => { if (window.scrollY > 40) navbar.classList.add('scrolled'); else navbar.classList.remove('scrolled'); }, { passive: true });

const reveals = document.querySelectorAll('.reveal');
const obs = new IntersectionObserver((entries) => { entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } }); }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
reveals.forEach(el => obs.observe(el));

function animateCount(el, target, duration = 1800) {
  let start = null;
  const step = (ts) => { if (!start) start = ts; const p = Math.min((ts - start) / duration, 1); const ease = 1 - Math.pow(1 - p, 3); el.textContent = Math.floor(ease * target).toLocaleString(); if (p < 1) requestAnimationFrame(step); };
  requestAnimationFrame(step);
}
setTimeout(() => { document.querySelectorAll('.hero-stat-val[data-count]').forEach(el => animateCount(el, parseInt(el.dataset.count), 2000)); }, 600);
const impObs = new IntersectionObserver((entries) => { entries.forEach(e => { if (e.isIntersecting) { animateCount(e.target, parseInt(e.target.dataset.count), 1600); impObs.unobserve(e.target); } }); }, { threshold: 0.5 });
document.querySelectorAll('.impact-num-val[data-count]').forEach(el => impObs.observe(el));

function toggleFaq(btn) {
  const ans = btn.nextElementSibling; const isOpen = btn.classList.contains('open');
  document.querySelectorAll('.faq-q.open').forEach(q => { q.classList.remove('open'); q.nextElementSibling.style.maxHeight = null; });
  if (!isOpen) { btn.classList.add('open'); ans.style.maxHeight = ans.scrollHeight + 'px'; }
}
document.querySelector('.faq-q')?.click();
document.querySelectorAll('a[href^="#"]').forEach(a => { a.addEventListener('click', e => { const id = a.getAttribute('href').slice(1); if (!id) return; const el = document.getElementById(id); if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); } }); });
</script>
</body>
</html>
