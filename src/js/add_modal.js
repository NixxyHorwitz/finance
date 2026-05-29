/**
 * add_modal.js — Self-contained Add Transaction Modal
 * Works on any page. Loads wallets from API on each open.
 */

(function () {
  'use strict';

  const LS_VIS    = 'nf_bal_vis';
  const LS_KEYPAD = 'nf_keypad';
  const iMap = {
    dana: 'public/dana.png', gopay: 'public/gopay.png',
    shopeepay: 'public/shopeepay.png', jago: 'public/jago.png'
  };

  let _wallets       = [];
  let _amountRaw     = '';
  let _currentType   = 'EXPENSE';
  let _modalBalVis   = true;
  let _useKeypad     = true;

  // ── Utils ─────────────────────────────────────────
  const fmt  = n => Number(n).toLocaleString('id-ID');
  const fmtS = n => {
    if (n >= 1e9) return 'Rp ' + (n/1e9).toLocaleString('id-ID', {maximumFractionDigits:1}) + 'M';
    if (n >= 1e6) return 'Rp ' + (n/1e6).toLocaleString('id-ID', {maximumFractionDigits:1}) + 'jt';
    if (n >= 1e3) return 'Rp ' + (n/1e3).toLocaleString('id-ID', {maximumFractionDigits:0}) + 'rb';
    return 'Rp ' + n;
  };
  const esc  = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const $    = id => document.getElementById(id);

  function readPrefs() {
    const vis = localStorage.getItem(LS_VIS);
    const kp  = localStorage.getItem(LS_KEYPAD);
    _modalBalVis = (typeof window._globalBalVis !== 'undefined')
      ? window._globalBalVis
      : (vis === null ? true : vis === '1');
    _useKeypad = kp === null ? true : kp === '1';
  }

  function applyKeypadMode() {
    if ($('numpadWrap'))    $('numpadWrap').style.display    = _useKeypad ? 'block' : 'none';
    if ($('nativeAmtWrap')) $('nativeAmtWrap').style.display = _useKeypad ? 'none'  : 'block';
  }

  // ── Amount ────────────────────────────────────────
  function updateAmtDisplay() {
    const el  = $('amountDisplay'); if (!el) return;
    const v   = parseInt(_amountRaw || '0', 10);
    el.textContent = v ? fmt(v) : '0';
    el.classList.toggle('placeholder', !v);
    const na = $('nativeAmt'); if (na) na.value = _amountRaw || '';
  }

  window.addNumpad    = n => { if (_amountRaw.length >= 13 || (!_amountRaw && n === 0)) return; _amountRaw += String(n); updateAmtDisplay(); };
  window.addNumpadDel = ()  => { _amountRaw = _amountRaw.slice(0, -1); updateAmtDisplay(); };
  window.addQuickAmt  = n  => { _amountRaw = String(parseInt(_amountRaw || '0', 10) + n); updateAmtDisplay(); };
  window.addSyncNative= v  => { _amountRaw = v ? String(parseInt(v,10)||0) : ''; updateAmtDisplay(); };

  // ── Type ──────────────────────────────────────────
  window.setAddType = function(btn) {
    _currentType = btn.dataset.type;
    document.querySelectorAll('.type-tab').forEach(t =>
      t.classList.remove('active-expense', 'active-income', 'active-transfer')
    );
    btn.classList.add('active-' + _currentType.toLowerCase());
    $('toWalletBlock').style.display = _currentType === 'TRANSFER' ? 'block' : 'none';
  };

  // ── Eye toggle ────────────────────────────────────
  window.toggleModalBal = function() {
    _modalBalVis = !_modalBalVis;
    $('modalEyeBtn').textContent = _modalBalVis ? '\uD83D\uDC41' : '\uD83D\uDE48';
    renderWalletSelectors();
  };

  // ── Wallet selectors ──────────────────────────────
  function renderWalletSelectors() {
    const make = (cid, hid, def = 0) => {
      const c = $(cid); if (!c) return;
      c.innerHTML = _wallets.map((w, i) => {
        const n = w.name.toLowerCase();
        let icon = '';
        for (const k of Object.keys(iMap)) {
          if (n.includes(k)) { icon = `<img src="${iMap[k]}" class="wsel-emoji" alt="${esc(w.name)}">`;  break; }
        }
        if (!icon) {
          const e  = n.includes('saving') ? '\uD83D\uDC37' : '\uD83D\uDCB5';
          icon = `<div class="wsel-emoji">${e}</div>`;
        }
        const balStr = (w.balance !== undefined && _modalBalVis)
          ? `<span class="wsel-bal">${fmtS(w.balance)}</span>`
          : (w.balance !== undefined ? `<span class="wsel-bal">\u2022\u2022\u2022\u2022</span>` : '');
        return `<div class="wsel-item${i === def ? ' selected' : ''}"
          onclick="addSelectWallet('${cid}','${hid}','${w.id}',this)">
          ${icon}<span class="wsel-name">${esc(w.name)}</span>${balStr}
        </div>`;
      }).join('');
      if (_wallets[def]) $(hid).value = _wallets[def].id;
    };
    make('fromWalletSel', 'selectedFromWallet', 0);
    make('toWalletSel', 'selectedToWallet', _wallets.length > 1 ? 1 : 0);
  }

  window.addSelectWallet = (cid, hid, wId, el) => {
    document.querySelectorAll(`#${cid} .wsel-item`).forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    $(hid).value = wId;
  };

  // ── Open / Close ──────────────────────────────────
  window.openAddModal = async function() {
    readPrefs();
    _amountRaw   = '';
    _currentType = 'EXPENSE';

    // Reset UI
    updateAmtDisplay();
    if ($('descInput')) $('descInput').value = '';
    if ($('addAlert'))  $('addAlert').classList.remove('show');
    if ($('nativeAmt')) $('nativeAmt').value = '';
    document.querySelectorAll('.type-tab').forEach((t, i) => {
      t.classList.remove('active-expense', 'active-income', 'active-transfer');
      if (i === 0) t.classList.add('active-expense');
    });
    if ($('toWalletBlock')) $('toWalletBlock').style.display = 'none';
    if ($('modalEyeBtn'))   $('modalEyeBtn').textContent = _modalBalVis ? '\uD83D\uDC41' : '\uD83D\uDE48';
    applyKeypadMode();

    // Load fresh wallet data
    try {
      const res = await fetch('src/api/data.php');
      const d   = await res.json();
      if (d.success) _wallets = d.wallets.map(w => ({...w, balance: parseFloat(w.balance)}));
    } catch {}

    renderWalletSelectors();
    $('addOverlay').classList.add('open');
  };

  window.closeAddModal = function() {
    $('addOverlay').classList.remove('open');
  };

  // ── Submit ────────────────────────────────────────
  window.submitRecord = async function() {
    const alertEl = $('addAlert'); alertEl.classList.remove('show');
    const btn     = $('addSubmitBtn');
    const amount  = parseInt(_amountRaw || '0', 10);
    const desc    = $('descInput').value.trim();
    const fromW   = $('selectedFromWallet').value;
    const toW     = $('selectedToWallet')?.value;

    if (!amount) { alertEl.textContent = 'Jumlah harus > 0.'; alertEl.classList.add('show'); return; }
    if (!desc)   { alertEl.textContent = 'Keterangan wajib diisi.'; alertEl.classList.add('show'); return; }
    if (_currentType === 'TRANSFER' && fromW === toW) {
      alertEl.textContent = 'Pilih dompet tujuan berbeda.'; alertEl.classList.add('show'); return;
    }

    btn.disabled = true; btn.textContent = 'Menyimpan\u2026';
    const fd = new FormData();
    fd.append('type', _currentType); fd.append('amount', amount);
    fd.append('description', desc); fd.append('walletId', fromW);
    if (_currentType === 'TRANSFER') fd.append('relatedWalletId', toW);

    try {
      const res = await fetch('src/actions/transaction.php?action=add_transaction', { method: 'POST', body: fd });
      const d   = await res.json();
      if (d.success) {
        closeAddModal();
        if (typeof window._afterRecordSaved === 'function') window._afterRecordSaved();
        else window.location.reload();
      } else {
        alertEl.textContent = d.message; alertEl.classList.add('show');
      }
    } catch { alertEl.textContent = 'Koneksi gagal.'; alertEl.classList.add('show'); }
    btn.disabled = false; btn.textContent = '\uD83D\uDCBE Simpan Transaksi';
  };

  // ── Auto-open via sessionStorage ──────────────────
  if (sessionStorage.getItem('openAdd') === '1') {
    sessionStorage.removeItem('openAdd');
    document.addEventListener('DOMContentLoaded', () => setTimeout(openAddModal, 350));
  }

})();
