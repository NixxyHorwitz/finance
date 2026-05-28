<?php
// _head.php – shared <head> with fully inlined CSS
// Usage: include '_head.php'; with $pageTitle set before including
$pageTitle = $pageTitle ?? 'Neofinance';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:      #f5f4f0;
  --surface: #ffffff;
  --text:    #111111;
  --muted:   #888888;
  --border:  #111111;
  --line:    #ebebeb;
  --yellow:  #f5c842;
  --red:     #ff4d4d;
  --green:   #22c55e;
  --blue:    #3b82f6;
  --shadow:  3px 3px 0 #111;
  --shadow-s:2px 2px 0 #111;
  --r: 10px;
  --r-s: 6px;
}

html { font-size: 14px; }
body {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  min-height: 100dvh;
}

/* Layout */
.wrap { max-width: 600px; margin: 0 auto; padding: 0.875rem 0.875rem 5rem; }
.center-wrap { min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 1.25rem; }

/* Typography */
.brand { font-size: 1.1rem; font-weight: 900; letter-spacing: -0.03em; }
.section-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }

/* Buttons */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.3rem;
  font-family: inherit; font-weight: 700; font-size: 0.8rem; line-height: 1;
  border: 2px solid var(--border); border-radius: var(--r-s);
  cursor: pointer; transition: transform 0.08s, box-shadow 0.08s;
  text-decoration: none; white-space: nowrap; user-select: none;
  padding: 0.5rem 0.875rem; box-shadow: var(--shadow-s);
  background: var(--yellow); color: var(--text);
}
.btn:hover  { transform: translate(1px,1px); box-shadow: 1px 1px 0 #111; }
.btn:active { transform: translate(2px,2px); box-shadow: none; }
.btn:disabled { opacity:.55; pointer-events:none; }
.btn-ghost  { background: var(--surface); }
.btn-danger { background: var(--red); color: #fff; }
.btn-full   { width: 100%; }
.btn-xs     { font-size: 0.7rem; padding: 0.3rem 0.55rem; box-shadow: 1px 1px 0 #111; }
.btn-icon   { padding: 0.38rem 0.55rem; }

/* Form */
.fgroup { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.7rem; }
.fgroup label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
input, select, textarea {
  font-family: inherit; font-size: 0.875rem;
  padding: 0.55rem 0.75rem;
  border: 2px solid var(--border); border-radius: var(--r-s);
  background: var(--surface); color: var(--text);
  width: 100%; outline: none; transition: box-shadow 0.12s;
  -webkit-appearance: none; appearance: none;
}
input:focus, select:focus { box-shadow: 3px 3px 0 var(--yellow); }
input::placeholder { color: var(--muted); }

/* Alert */
.alert {
  border: 2px solid var(--border); border-radius: var(--r-s);
  padding: 0.5rem 0.75rem; font-size: 0.8rem; font-weight: 600;
  margin-bottom: 0.75rem; display: none;
}
.alert.show { display: block; }
.alert-err { background: #fee2e2; }
.alert-ok  { background: #dcfce7; }

/* Auth */
.auth-box { width: 100%; max-width: 340px; }
.auth-title { font-size: 1.4rem; font-weight: 900; letter-spacing: -0.04em; margin-bottom: 0.2rem; }
.auth-sub   { font-size: 0.8rem; color: var(--muted); margin-bottom: 1.25rem; }

/* App Header */
.app-header {
  display: flex; align-items: center; justify-content: space-between;
  padding-bottom: 0.75rem; margin-bottom: 0.875rem;
  border-bottom: 2px solid var(--border);
}
.header-info { font-size: 0.7rem; color: var(--muted); font-weight: 500; }

/* Balance Hero */
.balance-card {
  background: var(--yellow); border: 2px solid var(--border);
  border-radius: var(--r); box-shadow: var(--shadow);
  padding: 1rem 1.1rem;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 0.875rem;
}
.balance-label { font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #666; margin-bottom: 0.2rem; }
.balance-amt   { font-size: 1.5rem; font-weight: 900; letter-spacing: -0.04em; line-height: 1; }

/* Wallet Grid */
.wallet-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.55rem; margin-bottom: 0.875rem; }
@media (max-width: 380px) { .wallet-grid { grid-template-columns: repeat(2, 1fr); } }

.wallet-card {
  background: var(--surface); border: 2px solid var(--border);
  border-radius: var(--r); box-shadow: var(--shadow-s);
  padding: 0.65rem 0.6rem 0.55rem; position: relative;
  transition: transform 0.1s, box-shadow 0.1s;
}
.wallet-card:hover { transform: translate(-1px,-1px); box-shadow: 3px 3px 0 #111; }
.wallet-icon { width: 28px; height: 28px; border-radius: 7px; object-fit: cover; border: 1.5px solid var(--line); margin-bottom: 0.45rem; display: block; }
.wallet-emoji { width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid var(--line); margin-bottom: 0.45rem; display: flex; align-items: center; justify-content: center; font-size: 15px; }
.wallet-name { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); margin-bottom: 0.15rem; }
.wallet-bal  { font-size: 0.82rem; font-weight: 800; letter-spacing: -0.02em; word-break: break-all; }
.wallet-edit-btn {
  position: absolute; top: 5px; right: 5px; width: 20px; height: 20px;
  display: flex; align-items: center; justify-content: center; font-size: 9px;
  cursor: pointer; background: var(--bg); border: 1.5px solid var(--border);
  border-radius: 4px; box-shadow: 1px 1px 0 #111; transition: transform 0.08s;
}
.wallet-edit-btn:hover { transform: translate(1px,1px); box-shadow: none; }

/* Row Head */
.row-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.55rem; }

/* Chart */
.chart-box {
  background: var(--surface); border: 2px solid var(--border);
  border-radius: var(--r); box-shadow: var(--shadow-s);
  padding: 0.75rem; height: 200px; margin-bottom: 0.875rem;
}

/* TX List */
.tx-wrap { background: var(--surface); border: 2px solid var(--border); border-radius: var(--r); box-shadow: var(--shadow-s); overflow: hidden; }
.tx-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.875rem; border-bottom: 1px solid var(--line); }
.tx-item:last-child { border-bottom: none; }
.tx-icon { width: 32px; height: 32px; flex-shrink: 0; border-radius: 8px; border: 1.5px solid var(--line); display: flex; align-items: center; justify-content: center; font-size: 14px; }
.tx-icon.income   { background: #dcfce7; }
.tx-icon.expense  { background: #fee2e2; }
.tx-icon.transfer { background: #dbeafe; }
.tx-body { flex: 1; min-width: 0; }
.tx-desc { font-size: 0.82rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tx-meta { font-size: 0.68rem; color: var(--muted); margin-top: 1px; }
.tx-cat { display: inline-block; margin-top: 2px; padding: 0.1rem 0.4rem; border: 1.5px solid var(--border); border-radius: 4px; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; background: var(--bg); }
.tx-right { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
.tx-amt { font-size: 0.82rem; font-weight: 700; text-align: right; }
.tx-amt.income  { color: var(--green); }
.tx-amt.expense { color: var(--red); }

/* Overlay & Modal */
.overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); display: none; align-items: flex-end; justify-content: center; }
.overlay.open { display: flex; }
@media (min-width: 500px) { .overlay { align-items: center; padding: 1rem; } }

.modal {
  width: 100%; max-width: 460px; max-height: 90dvh; overflow-y: auto;
  background: var(--surface); border: 2px solid var(--border);
  border-radius: var(--r) var(--r) 0 0; box-shadow: 0 -3px 0 #111;
  padding: 1.1rem; animation: slideUp .2s ease;
}
@keyframes slideUp { from { transform: translateY(24px); opacity:0; } to { transform: translateY(0); opacity:1; } }
@media (min-width: 500px) {
  .modal { border-radius: var(--r); box-shadow: var(--shadow); animation: popIn .15s ease; }
  @keyframes popIn { from { transform: scale(.96); opacity:0; } to { transform: scale(1); opacity:1; } }
}

.modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.875rem; padding-bottom: 0.65rem; border-bottom: 1.5px solid var(--line); }
.modal-title { font-size: 0.95rem; font-weight: 800; }
.modal-close { width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 13px; cursor: pointer; border: 1.5px solid var(--border); border-radius: 5px; background: var(--bg); box-shadow: 1px 1px 0 #111; transition: transform 0.08s; }
.modal-close:hover { transform: translate(1px,1px); box-shadow: none; }

/* Tabs */
.tabs { display: flex; gap: 3px; background: var(--bg); border: 2px solid var(--border); border-radius: var(--r-s); padding: 3px; margin-bottom: 0.8rem; }
.tab { flex: 1; padding: 0.42rem 0.25rem; font-family: inherit; font-size: 0.75rem; font-weight: 700; border: none; border-radius: 4px; cursor: pointer; background: transparent; color: var(--muted); transition: all .12s; }
.tab.active { background: var(--yellow); color: var(--text); border: 1.5px solid #111; box-shadow: 1px 1px 0 #111; }

/* Spinner & Empty */
.spinner { display: none; align-items: center; justify-content: center; padding: 2.5rem; font-size: 0.8rem; color: var(--muted); font-weight: 600; }
.spinner.show { display: flex; }
.empty { text-align: center; padding: 2.5rem 1rem; color: var(--muted); font-size: 0.8rem; font-weight: 600; }

/* Toast */
.toast-container { position: fixed; bottom: 1.25rem; left: 50%; transform: translateX(-50%); z-index: 999; display: flex; flex-direction: column; align-items: center; gap: 0.4rem; pointer-events: none; }
.toast { background: var(--text); color: #fff; padding: 0.5rem 1rem; border-radius: 100px; font-size: 0.78rem; font-weight: 600; animation: toastIn .2s ease; white-space: nowrap; }
.toast.err { background: var(--red); }
@keyframes toastIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

/* Utils */
.mb-1 { margin-bottom: 0.55rem; }
.mb-2 { margin-bottom: 0.875rem; }
</style>
