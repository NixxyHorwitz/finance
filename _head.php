<?php
$pageTitle = $pageTitle ?? 'Neofinance';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="description" content="Neofinance - Catatan keuangan pribadi yang cepat dan modern">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* RESET & ROOT */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg:       #F7F7F5;
  --surface:  #FFFFFF;
  --text:     #0D0D0D;
  --muted:    #777;
  --border:   #0D0D0D;
  --line:     #E8E8E8;
  --yellow:   #FFD60A;
  --yellow-d: #E6C200;
  --coral:    #FF4D4D;
  --coral-l:  #FFF0F0;
  --mint:     #00C896;
  --mint-l:   #E6FFF8;
  --blue:     #3A56E8;
  --blue-l:   #EEF1FF;
  --purple:   #7B2FBE;
  --purple-l: #F5EEFF;
  --orange:   #FF6B35;
  --sh:       4px 4px 0 #0D0D0D;
  --sh-s:     2px 2px 0 #0D0D0D;
  --sh-l:     6px 6px 0 #0D0D0D;
  --sh-xl:    8px 8px 0 #0D0D0D;
  --r:        14px;
  --r-s:      9px;
  --r-xs:     5px;
  --nav-h:    64px;
  --fab-size: 66px;
}
html { font-size: 14px; scroll-behavior: smooth; }
body {
  font-family: 'Inter', system-ui, sans-serif;
  background: var(--bg); color: var(--text);
  -webkit-font-smoothing: antialiased;
  overscroll-behavior: none;
}

/* LAYOUT */
.wrap      { max-width: 600px; margin: 0 auto; padding: 0.875rem 0.875rem calc(var(--nav-h) + 2.5rem); }
.page-wrap { max-width: 600px; margin: 0 auto; padding: 0.875rem 0.875rem calc(var(--nav-h) + 2rem); }
.center-wrap { min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.3rem;
  font-family: inherit; font-weight: 800; font-size: 0.82rem; line-height: 1;
  border: 2px solid var(--border); border-radius: var(--r-s);
  cursor: pointer; transition: transform 0.08s, box-shadow 0.08s;
  text-decoration: none; white-space: nowrap; user-select: none;
  padding: 0.55rem 0.9rem; box-shadow: var(--sh-s);
  background: var(--yellow); color: var(--text);
  -webkit-tap-highlight-color: transparent;
}
.btn:hover  { transform: translate(-1px,-1px); box-shadow: var(--sh); }
.btn:active { transform: translate(2px,2px); box-shadow: none; }
.btn:disabled { opacity:.5; pointer-events:none; }
.btn-ghost  { background: var(--surface); }
.btn-danger { background: var(--coral); color: #fff; border-color: var(--border); }
.btn-blue   { background: var(--blue); color: #fff; }
.btn-full   { width: 100%; }
.btn-xs     { font-size: 0.7rem; padding: 0.3rem 0.6rem; box-shadow: 1px 1px 0 #0D0D0D; }
.btn-xs:hover { box-shadow: 2px 2px 0 #0D0D0D; }
.btn-icon   { padding: 0.38rem 0.5rem; }
.btn-lg     { font-size: 1rem; padding: 0.9rem 1.5rem; font-weight: 900; box-shadow: var(--sh); letter-spacing:-0.01em; }

/* FORM */
.fgroup { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.75rem; }
.fgroup label { font-size: 0.63rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; }
input[type=text], input[type=password], input[type=number], input[type=search], textarea {
  font-family: inherit; font-size: 0.9rem; padding: 0.6rem 0.8rem;
  border: 2px solid var(--border); border-radius: var(--r-s);
  background: var(--surface); color: var(--text);
  width: 100%; outline: none; transition: box-shadow 0.12s, transform 0.08s;
  -webkit-appearance: none; appearance: none;
}
input:focus, textarea:focus { box-shadow: 3px 3px 0 var(--yellow); transform: translate(-1px,-1px); }
input::placeholder { color: var(--muted); font-weight: 500; }

/* ALERT */
.alert { border: 2px solid var(--border); border-radius: var(--r-s); padding: 0.5rem 0.8rem; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.75rem; display: none; box-shadow: var(--sh-s); }
.alert.show { display: block; }
.alert-err { background: #FFE5E5; }
.alert-ok  { background: #E5FFF5; }

/* AUTH */
.auth-box   { width: 100%; max-width: 340px; }
.auth-title { font-size: 1.6rem; font-weight: 900; letter-spacing: -0.05em; margin-bottom: 0.2rem; }
.auth-sub   { font-size: 0.82rem; color: var(--muted); margin-bottom: 1.5rem; }

/* APP HEADER */
.app-header  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.brand       { font-size: 1.2rem; font-weight: 900; letter-spacing: -0.05em; }
.brand span  { color: var(--blue); }
.header-sub  { font-size: 0.68rem; color: var(--muted); font-weight: 600; }
.page-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.875rem; border-bottom: 2px solid var(--border); }
.page-title  { font-size: 1.1rem; font-weight: 900; letter-spacing: -0.04em; }

/* BALANCE HERO */
.balance-hero {
  background: linear-gradient(135deg, var(--blue) 0%, var(--purple) 60%, #9B3DD8 100%);
  border: 2px solid var(--border); border-radius: var(--r); box-shadow: var(--sh);
  padding: 1.4rem 1.25rem; color: #fff; margin-bottom: 1rem; position: relative; overflow: hidden;
}
.balance-hero::before { content:''; position:absolute; top:-50px; right:-50px; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,0.07); pointer-events:none; }
.balance-hero::after  { content:''; position:absolute; bottom:-30px; left:20px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.05); pointer-events:none; }
.balance-label { font-size:0.62rem; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; opacity:0.65; margin-bottom:0.3rem; }
.balance-amt   { font-size:2rem; font-weight:900; letter-spacing:-0.05em; line-height:1; }
.balance-row   { display:flex; align-items:center; justify-content:space-between; position:relative; z-index:1; }
.eye-btn { width:36px; height:36px; background:rgba(255,255,255,0.15); border:2px solid rgba(255,255,255,0.25); border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:17px; transition:all 0.15s; -webkit-tap-highlight-color:transparent; }
.eye-btn:hover { background:rgba(255,255,255,0.28); transform:scale(1.05); }
.balance-stats { display:flex; gap:0.75rem; margin-top:1rem; position:relative; z-index:1; }
.stat-item { flex:1; background:rgba(255,255,255,0.1); border-radius:10px; padding:0.6rem 0.75rem; border:1px solid rgba(255,255,255,0.15); }
.stat-label { font-size:0.58rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; opacity:0.6; margin-bottom:0.2rem; }
.stat-val   { font-size:0.92rem; font-weight:900; }

/* SECTION HEADER */
.sec-head  { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.6rem; }
.sec-label { font-size:0.65rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); }
.sec-link  { font-size:0.65rem; font-weight:800; color:var(--blue); text-decoration:none; letter-spacing:0.02em; }
.sec-link:hover { text-decoration:underline; }

/* WALLET GRID */
.wallet-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:0.6rem; margin-bottom:1rem; }
@media (max-width:360px) { .wallet-grid { grid-template-columns:repeat(2,1fr); } }
.wallet-card {
  background:var(--surface); border:2px solid var(--border); border-radius:var(--r);
  box-shadow:var(--sh-s); padding:0.8rem 0.65rem 0.65rem; position:relative;
  cursor:pointer; transition:transform 0.1s, box-shadow 0.1s;
  -webkit-tap-highlight-color:transparent;
}
.wallet-card:hover  { transform:translate(-2px,-2px); box-shadow:4px 4px 0 #0D0D0D; }
.wallet-card:active { transform:translate(2px,2px); box-shadow:none; }
.wallet-card.dana      { background:linear-gradient(135deg,#E8F3FF,#CCE4FF); }
.wallet-card.gopay     { background:linear-gradient(135deg,#E8FFF5,#C5FFE5); }
.wallet-card.shopeepay { background:linear-gradient(135deg,#FFF2EC,#FFD9C5); }
.wallet-card.savings   { background:linear-gradient(135deg,#FFFCE6,#FFF3A0); }
.wallet-card.jago      { background:linear-gradient(135deg,#E5FFF2,#A8FFCE); }
.wallet-card.cash      { background:linear-gradient(135deg,#F0EDFF,#DDD6FF); }
.wallet-icon  { width:32px; height:32px; border-radius:9px; object-fit:cover; border:2px solid rgba(0,0,0,0.08); margin-bottom:0.5rem; display:block; }
.wallet-emoji { width:32px; height:32px; border-radius:9px; border:2px solid rgba(0,0,0,0.08); margin-bottom:0.5rem; display:flex; align-items:center; justify-content:center; font-size:17px; }
.wallet-name  { font-size:0.58rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#555; margin-bottom:0.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wallet-bal   { font-size:0.85rem; font-weight:900; letter-spacing:-0.03em; }
.wallet-edit-btn { position:absolute; top:5px; right:5px; width:22px; height:22px; display:flex; align-items:center; justify-content:center; font-size:10px; cursor:pointer; background:rgba(255,255,255,0.8); border:1.5px solid rgba(0,0,0,0.15); border-radius:5px; box-shadow:1px 1px 0 rgba(0,0,0,0.12); transition:transform 0.08s; }
.wallet-edit-btn:hover { transform:translate(1px,1px); box-shadow:none; }

/* CHART */
.chart-box { background:var(--surface); border:2px solid var(--border); border-radius:var(--r); box-shadow:var(--sh-s); padding:0.875rem; height:220px; margin-bottom:1rem; }

/* TX LIST */
.tx-wrap  { background:var(--surface); border:2px solid var(--border); border-radius:var(--r); box-shadow:var(--sh-s); overflow:hidden; margin-bottom:1rem; }
.tx-item  { display:flex; align-items:center; gap:0.65rem; padding:0.7rem 0.875rem; border-bottom:1.5px solid var(--line); transition:background 0.08s; }
.tx-item:last-child { border-bottom:none; }
.tx-item:hover { background:#FAFAF8; }
.tx-icon  { width:36px; height:36px; flex-shrink:0; border-radius:10px; border:2px solid rgba(0,0,0,0.07); display:flex; align-items:center; justify-content:center; font-size:16px; }
.tx-icon.income   { background:var(--mint-l); }
.tx-icon.expense  { background:var(--coral-l); }
.tx-icon.transfer { background:var(--blue-l); }
.tx-body  { flex:1; min-width:0; }
.tx-desc  { font-size:0.85rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tx-meta  { font-size:0.67rem; color:var(--muted); margin-top:2px; font-weight:500; }
.tx-badge { display:inline-block; margin-top:3px; padding:0.1rem 0.45rem; border:1.5px solid var(--border); border-radius:100px; font-size:0.58rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; background:var(--bg); box-shadow:1px 1px 0 var(--border); }
.tx-right { display:flex; align-items:center; gap:0.4rem; flex-shrink:0; }
.tx-amt   { font-size:0.88rem; font-weight:900; text-align:right; letter-spacing:-0.02em; }
.tx-amt.income  { color:var(--mint); }
.tx-amt.expense { color:var(--coral); }

/* BOTTOM NAV */
.bottom-nav {
  position:fixed; bottom:0; left:0; right:0; z-index:90;
  height:var(--nav-h); background:var(--surface);
  border-top:2px solid var(--border);
  display:flex; align-items:center; justify-content:space-around;
  padding:0 0.5rem 0;
  box-shadow:0 -4px 0 var(--border);
}
.nav-item   { display:flex; flex-direction:column; align-items:center; gap:0.2rem; cursor:pointer; flex:1; padding:0.5rem 0.3rem; color:var(--muted); text-decoration:none; transition:color 0.12s; -webkit-tap-highlight-color:transparent; }
.nav-item.active { color:var(--blue); }
.nav-item.active .nav-icon { transform:scale(1.1); }
.nav-icon  { font-size:21px; line-height:1; transition:transform 0.15s; }
.nav-label { font-size:0.56rem; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; }

/* FAB — pops out of navbar */
.nav-fab {
  flex:none !important;
  width:var(--fab-size);
  height:var(--fab-size);
  background:var(--yellow);
  border:2px solid var(--border);
  border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:28px; font-weight:900; color:var(--text);
  box-shadow:var(--sh);
  cursor:pointer; flex-shrink:0;
  margin-bottom:calc(var(--fab-size) / 2 + 4px);
  transition:transform 0.12s, box-shadow 0.12s;
  -webkit-tap-highlight-color:transparent;
  position:relative; z-index:2;
  text-decoration:none;
}
.nav-fab:hover  { transform:translate(-2px,-2px) scale(1.04); box-shadow:var(--sh-l); }
.nav-fab:active { transform:translate(3px,3px) scale(0.95); box-shadow:none; }

/* OVERLAYS & MODALS */
.overlay { position:fixed; inset:0; z-index:200; background:rgba(0,0,0,0.55); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); display:none; align-items:flex-end; justify-content:center; }
.overlay.open { display:flex; }
@media (min-width:520px) { .overlay { align-items:center; padding:1rem; } }
.modal { width:100%; max-width:480px; max-height:94dvh; overflow-y:auto; background:var(--surface); border:2px solid var(--border); border-radius:var(--r) var(--r) 0 0; box-shadow:0 -6px 0 var(--border); animation:slideUp .22s cubic-bezier(.16,1,.3,1); }
@keyframes slideUp { from{transform:translateY(40px);opacity:0;} to{transform:translateY(0);opacity:1;} }
@media (min-width:520px) { .modal { border-radius:var(--r); box-shadow:var(--sh-l); animation:popIn .15s ease; } @keyframes popIn { from{transform:scale(.93);opacity:0;} to{transform:scale(1);opacity:1;} } }
.modal-handle { width:40px; height:4px; background:var(--line); border-radius:2px; margin:0.75rem auto 0.5rem; }
.modal-head   { display:flex; align-items:center; justify-content:space-between; padding:0 1.1rem 0.75rem; border-bottom:2px solid var(--line); margin-bottom:0.875rem; }
.modal-title  { font-size:0.95rem; font-weight:900; letter-spacing:-0.02em; }
.modal-close  { width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:13px; cursor:pointer; border:2px solid var(--border); border-radius:7px; background:var(--bg); box-shadow:2px 2px 0 var(--border); transition:transform 0.08s; }
.modal-close:hover { transform:translate(1px,1px); box-shadow:none; }
.modal-body { padding:0 1.1rem 1.25rem; }

/* TYPE TABS */
.type-tabs { display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.45rem; margin-bottom:1rem; }
.type-tab  { display:flex; flex-direction:column; align-items:center; gap:0.3rem; padding:0.7rem 0.3rem; border:2px solid var(--border); border-radius:var(--r-s); background:var(--bg); cursor:pointer; font-family:inherit; font-size:0.72rem; font-weight:800; color:var(--muted); box-shadow:var(--sh-s); transition:all 0.12s; -webkit-tap-highlight-color:transparent; }
.type-tab .tab-icon { font-size:22px; }
.type-tab.active-expense  { background:var(--coral); color:#fff; box-shadow:2px 2px 0 var(--border); transform:translate(-1px,-1px); }
.type-tab.active-income   { background:var(--mint);  color:var(--text); box-shadow:2px 2px 0 var(--border); transform:translate(-1px,-1px); }
.type-tab.active-transfer { background:var(--blue);  color:#fff; box-shadow:2px 2px 0 var(--border); transform:translate(-1px,-1px); }

/* WALLET SELECTOR */
.wallet-selector-label { font-size:0.63rem; font-weight:800; text-transform:uppercase; letter-spacing:0.07em; margin-bottom:0.4rem; }
.wallet-selector { display:flex; gap:0.45rem; overflow-x:auto; padding-bottom:0.3rem; margin-bottom:0.875rem; scrollbar-width:none; }
.wallet-selector::-webkit-scrollbar { display:none; }
.wsel-item { flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:0.3rem; padding:0.55rem 0.7rem; border:2px solid var(--border); border-radius:var(--r-s); background:var(--bg); cursor:pointer; font-size:0.7rem; font-weight:700; transition:all 0.12s; box-shadow:var(--sh-s); -webkit-tap-highlight-color:transparent; min-width:66px; }
.wsel-item img, .wsel-item .wsel-emoji { width:28px; height:28px; border-radius:7px; object-fit:cover; border:1.5px solid rgba(0,0,0,0.1); flex-shrink:0; }
.wsel-item .wsel-emoji { display:flex; align-items:center; justify-content:center; font-size:16px; }
.wsel-name { font-size:0.72rem; font-weight:800; }
.wsel-bal  { font-size:0.62rem; font-weight:700; color:var(--muted); margin-top:1px; }
.wsel-item.selected { background:var(--yellow); transform:translate(-1px,-1px); box-shadow:3px 3px 0 var(--border); }
.wsel-item.selected .wsel-bal { color:var(--text); }

/* AMOUNT DISPLAY */
.amount-display { text-align:center; padding:0.875rem 1rem 0.6rem; background:var(--bg); border:2px solid var(--border); border-radius:var(--r-s); margin-bottom:0.75rem; box-shadow:inset 2px 2px 0 rgba(0,0,0,0.04); }
.amount-prefix  { font-size:0.78rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; }
.amount-value   { font-size:2.2rem; font-weight:900; letter-spacing:-0.05em; line-height:1.1; min-height:2.6rem; }
.amount-value.placeholder { color:var(--muted); }

/* QUICK AMOUNTS */
.quick-amounts { display:flex; gap:0.35rem; flex-wrap:wrap; margin-bottom:0.75rem; }
.quick-btn { padding:0.38rem 0.65rem; border:2px solid var(--border); border-radius:100px; background:var(--surface); font-family:inherit; font-size:0.72rem; font-weight:800; cursor:pointer; box-shadow:1px 1px 0 var(--border); transition:all 0.08s; color:var(--text); -webkit-tap-highlight-color:transparent; }
.quick-btn:hover  { background:var(--yellow); transform:translate(-1px,-1px); box-shadow:2px 2px 0 var(--border); }
.quick-btn:active { transform:translate(1px,1px); box-shadow:none; }

/* NUMPAD */
.numpad { display:grid; grid-template-columns:repeat(3,1fr); gap:0.5rem; margin-bottom:0.875rem; }
.nk { display:flex; align-items:center; justify-content:center; height:56px; font-family:inherit; font-size:1.15rem; font-weight:800; border:2px solid var(--border); border-radius:var(--r-s); background:var(--surface); cursor:pointer; box-shadow:var(--sh-s); transition:all 0.08s; user-select:none; -webkit-tap-highlight-color:transparent; }
.nk:hover  { transform:translate(-1px,-1px); box-shadow:3px 3px 0 var(--border); }
.nk:active { transform:translate(2px,2px); box-shadow:none; background:var(--yellow); }
.nk-0   { grid-column:span 2; }
.nk-del { background:var(--coral-l); font-size:1.25rem; }
.nk-del:active { background:var(--coral); color:#fff; }

/* SPINNER & EMPTY */
.spinner { display:none; align-items:center; justify-content:center; padding:2.5rem 1rem; font-size:0.8rem; color:var(--muted); font-weight:600; gap:0.5rem; }
.spinner.show { display:flex; }
.empty      { text-align:center; padding:3rem 1rem; color:var(--muted); }
.empty-icon { font-size:2.8rem; margin-bottom:0.5rem; }
.empty-txt  { font-size:0.88rem; font-weight:700; }
.empty-sub  { font-size:0.75rem; margin-top:0.25rem; }

/* TOAST */
.toast-wrap { position:fixed; bottom:calc(var(--nav-h) + 1.25rem); left:50%; transform:translateX(-50%); z-index:999; display:flex; flex-direction:column; align-items:center; gap:0.4rem; pointer-events:none; }
.toast { background:var(--text); color:#fff; padding:0.5rem 1.1rem; border-radius:100px; font-size:0.78rem; font-weight:700; animation:toastUp .2s ease; white-space:nowrap; border:2px solid rgba(255,255,255,0.12); box-shadow:2px 2px 0 rgba(0,0,0,0.3); }
.toast.err { background:var(--coral); }
.toast.ok  { background:var(--mint); color:var(--text); }
@keyframes toastUp { from{opacity:0;transform:translateY(12px);} to{opacity:1;transform:translateY(0);} }

/* SETTINGS PAGE */
.settings-section { margin-bottom:1.25rem; }
.settings-section-title { font-size:0.63rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); margin-bottom:0.5rem; }
.settings-card { background:var(--surface); border:2px solid var(--border); border-radius:var(--r); box-shadow:var(--sh-s); overflow:hidden; }
.settings-item { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.875rem 1rem; border-bottom:1.5px solid var(--line); }
.settings-item:last-child { border-bottom:none; }
.settings-item-left { display:flex; align-items:center; gap:0.65rem; flex:1; min-width:0; }
.settings-icon { width:38px; height:38px; border-radius:10px; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; box-shadow:var(--sh-s); }
.settings-label { font-size:0.85rem; font-weight:800; }
.settings-sub   { font-size:0.7rem; color:var(--muted); margin-top:1px; }
/* Toggle */
.toggle { position:relative; width:46px; height:26px; flex-shrink:0; }
.toggle input { opacity:0; width:0; height:0; position:absolute; }
.toggle-slider { position:absolute; inset:0; background:var(--line); border:2px solid var(--border); border-radius:100px; cursor:pointer; transition:background 0.2s; box-shadow:inset 0 1px 3px rgba(0,0,0,0.1); }
.toggle-slider::before { content:''; position:absolute; width:16px; height:16px; left:3px; top:50%; transform:translateY(-50%); background:var(--muted); border-radius:50%; transition:all 0.2s; }
.toggle input:checked + .toggle-slider { background:var(--mint); }
.toggle input:checked + .toggle-slider::before { transform:translateY(-50%) translateX(20px); background:var(--text); }
/* Inline edit */
.inline-edit { padding:0.75rem 1rem; background:var(--bg); border-top:1.5px solid var(--line); display:none; }
.inline-edit.open { display:block; }
.inline-edit-row { display:flex; gap:0.5rem; }
/* Wallet settings */
.wallet-settings-item { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1rem; border-bottom:1.5px solid var(--line); }
.wallet-settings-item:last-child { border-bottom:none; }
.wallet-settings-info { flex:1; min-width:0; }
.wallet-settings-name { font-size:0.85rem; font-weight:800; }
.wallet-settings-bal  { font-size:0.72rem; color:var(--muted); margin-top:1px; }
.wallet-settings-actions { display:flex; gap:0.35rem; }

/* CHART TABS */
.chart-tabs { display:flex; gap:4px; }
.chart-tab  { padding:0.3rem 0.7rem; border:2px solid var(--border); border-radius:100px; font-family:inherit; font-size:0.68rem; font-weight:800; cursor:pointer; background:var(--surface); color:var(--muted); transition:all 0.1s; -webkit-tap-highlight-color:transparent; }
.chart-tab.active { background:var(--blue); color:#fff; box-shadow:1px 1px 0 var(--border); }

/* UTILS */
.mb-1 { margin-bottom:0.6rem; }
.mb-2 { margin-bottom:1rem; }
.sec-card { background:var(--surface); border:2px solid var(--border); border-radius:var(--r); box-shadow:var(--sh-s); overflow:hidden; }

/* FLOW DIAGRAM */
.flow-total-card { background:linear-gradient(135deg,#FF4D4D,#FF7A35); border:2px solid #0D0D0D; border-radius:var(--r); box-shadow:var(--sh); padding:1.1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; color:#fff; }
.flow-total-label { font-size:0.6rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; opacity:0.75; margin-bottom:0.25rem; }
.flow-total-amt   { font-size:1.6rem; font-weight:900; letter-spacing:-0.05em; line-height:1; }
.flow-wrap { position:relative; display:grid; grid-template-columns:1fr 48px 1fr; align-items:start; min-height:200px; margin-bottom:1rem; }
.flow-col-left  { display:flex; flex-direction:column; gap:0.55rem; }
.flow-col-right { display:flex; flex-direction:column; gap:0.55rem; }
.flow-svg-mid   { position:relative; align-self:stretch; }
.flow-svg-mid svg { position:absolute; top:0; left:0; width:100%; height:100%; overflow:visible; pointer-events:none; }
.flow-wallet-node { display:flex; flex-direction:column; align-items:center; gap:0.2rem; padding:0.6rem 0.5rem; background:#EEF1FF; border:2px solid #3A56E8; border-radius:100px; box-shadow:2px 2px 0 #3A56E8; text-align:center; transition:transform 0.1s; position:relative; z-index:2; }
.flow-wallet-node:hover { transform:translate(-1px,-1px); }
.flow-wn-icon { font-size:18px; line-height:1; }
.flow-wn-name { font-size:0.56rem; font-weight:900; color:#3A56E8; text-transform:uppercase; letter-spacing:0.04em; }
.flow-wn-amt  { font-size:0.72rem; font-weight:900; color:#3A56E8; }
.flow-wn-pct  { font-size:0.56rem; font-weight:700; color:#3A56E8; opacity:0.6; }
.flow-cat-node { display:flex; flex-direction:column; align-items:center; gap:0.2rem; padding:0.6rem 0.5rem; background:#FFFBE6; border:2px solid #C9A800; border-radius:9px; box-shadow:2px 2px 0 #0D0D0D; text-align:center; cursor:pointer; transition:all 0.12s; position:relative; z-index:2; -webkit-tap-highlight-color:transparent; }
.flow-cat-node:hover  { transform:translate(-1px,-1px); box-shadow:3px 3px 0 #0D0D0D; }
.flow-cat-node:active { transform:translate(1px,1px); box-shadow:none; }
.flow-cat-node.active { background:#FFD60A; border-color:#0D0D0D; transform:translate(-1px,-1px); }
.flow-cn-emoji { font-size:18px; line-height:1; }
.flow-cn-name  { font-size:0.56rem; font-weight:900; text-transform:uppercase; letter-spacing:0.04em; color:#7A5C00; }
.flow-cn-amt   { font-size:0.72rem; font-weight:900; }
.flow-cn-pct   { font-size:0.56rem; font-weight:700; color:var(--muted); }
.mini-eye-btn { width:26px; height:26px; display:flex; align-items:center; justify-content:center; font-size:14px; cursor:pointer; background:var(--bg); border:2px solid var(--border); border-radius:50%; box-shadow:1px 1px 0 var(--border); transition:transform 0.08s; flex-shrink:0; -webkit-tap-highlight-color:transparent; }
.mini-eye-btn:hover { transform:translate(1px,1px); box-shadow:none; }
.cat-detail-panel { background:var(--surface); border:2px solid var(--border); border-radius:var(--r); box-shadow:var(--sh-s); overflow:hidden; margin-bottom:1rem; animation:slideDown .2s ease; }
@keyframes slideDown { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.cat-detail-head { display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1rem; background:var(--yellow); border-bottom:2px solid var(--border); }
.cat-detail-title { font-size:0.85rem; font-weight:900; }
.cat-detail-sub   { font-size:0.68rem; color:#555; margin-top:1px; }

/* HISTORY PAGE */
.hist-filters { display:flex; gap:0.45rem; flex-wrap:wrap; margin-bottom:0.75rem; }
.hist-search  { position:relative; margin-bottom:0.75rem; }
.hist-search input { padding-left:2.2rem; }
.hist-search-icon { position:absolute; left:0.65rem; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; pointer-events:none; }
.hist-filter-btn { padding:0.3rem 0.7rem; border:2px solid var(--border); border-radius:100px; font-family:inherit; font-size:0.68rem; font-weight:800; cursor:pointer; background:var(--surface); color:var(--muted); transition:all 0.1s; -webkit-tap-highlight-color:transparent; }
.hist-filter-btn.active { background:var(--blue); color:#fff; box-shadow:1px 1px 0 var(--border); }
.hist-summary { display:flex; gap:0.5rem; align-items:center; justify-content:space-between; padding:0.65rem 0.875rem; background:var(--surface); border:2px solid var(--border); border-radius:var(--r-s); box-shadow:var(--sh-s); margin-bottom:0.75rem; font-size:0.72rem; font-weight:700; }
.hist-summary-total { color:var(--muted); }
.hist-summary-income { color:var(--mint); }
.hist-summary-expense { color:var(--coral); }
.hist-date-group { font-size:0.62rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:var(--muted); padding:0.5rem 0.875rem; background:var(--bg); border-bottom:1.5px solid var(--line); border-top:1.5px solid var(--line); }
.hist-date-group:first-child { border-top:none; }
.hist-pagination { display:flex; align-items:center; justify-content:center; gap:0.4rem; padding:1rem 0 0; flex-wrap:wrap; }
.hist-page-btn { width:32px; height:32px; display:flex; align-items:center; justify-content:center; border:2px solid var(--border); border-radius:var(--r-xs); font-family:inherit; font-size:0.75rem; font-weight:800; cursor:pointer; background:var(--surface); box-shadow:1px 1px 0 var(--border); transition:all 0.08s; -webkit-tap-highlight-color:transparent; }
.hist-page-btn.active { background:var(--yellow); transform:translate(-1px,-1px); box-shadow:2px 2px 0 var(--border); }
.hist-page-btn:hover:not(.active) { transform:translate(-1px,-1px); box-shadow:2px 2px 0 var(--border); }
.hist-page-btn:disabled { opacity:0.4; pointer-events:none; }
</style>