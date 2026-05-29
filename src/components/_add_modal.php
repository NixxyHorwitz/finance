<?php
/**
 * Add Transaction Modal — shared HTML component.
 * Requires add_modal.js to be loaded on the page.
 * Works standalone: loads wallet data via API on open.
 */
?>
<div class="overlay" id="addOverlay" onclick="if(event.target===this)closeAddModal()">
  <div class="modal" id="addModal">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <span class="modal-title">&#x270F;&#xFE0F; Catat Transaksi</span>
      <span class="modal-close" onclick="closeAddModal()">&#x2715;</span>
    </div>
    <div class="modal-body">
      <div id="addAlert" class="alert alert-err"></div>

      <!-- Type Tabs -->
      <div class="type-tabs mb-1">
        <button class="type-tab active-expense" data-type="EXPENSE" onclick="setAddType(this)">
          <span class="tab-icon">&#x1F4B8;</span>Keluar
        </button>
        <button class="type-tab" data-type="INCOME" onclick="setAddType(this)">
          <span class="tab-icon">&#x1F4B0;</span>Masuk
        </button>
        <button class="type-tab" data-type="TRANSFER" onclick="setAddType(this)">
          <span class="tab-icon">&#x2194;&#xFE0F;</span>Transfer
        </button>
      </div>

      <!-- Wallet From -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.4rem">
        <div class="wallet-selector-label" style="margin-bottom:0">Dari Dompet</div>
        <div class="mini-eye-btn" id="modalEyeBtn" onclick="toggleModalBal()" title="Toggle saldo">&#x1F441;</div>
      </div>
      <div class="wallet-selector" id="fromWalletSel"></div>
      <input type="hidden" id="selectedFromWallet">

      <!-- Wallet To (Transfer only) -->
      <div id="toWalletBlock" style="display:none">
        <div class="wallet-selector-label">Ke Dompet</div>
        <div class="wallet-selector" id="toWalletSel"></div>
        <input type="hidden" id="selectedToWallet">
      </div>

      <!-- Amount display -->
      <div class="amount-display mb-1">
        <div class="amount-prefix">Rp</div>
        <div class="amount-value placeholder" id="amountDisplay">0</div>
      </div>

      <!-- Quick amounts -->
      <div class="quick-amounts mb-1">
        <button class="quick-btn" onclick="addQuickAmt(10000)">+10rb</button>
        <button class="quick-btn" onclick="addQuickAmt(20000)">+20rb</button>
        <button class="quick-btn" onclick="addQuickAmt(50000)">+50rb</button>
        <button class="quick-btn" onclick="addQuickAmt(100000)">+100rb</button>
        <button class="quick-btn" onclick="addQuickAmt(200000)">+200rb</button>
        <button class="quick-btn" onclick="addQuickAmt(500000)">+500rb</button>
      </div>

      <!-- Numpad (keypad mode) -->
      <div id="numpadWrap">
        <div class="numpad">
          <button class="nk" onclick="addNumpad(1)">1</button>
          <button class="nk" onclick="addNumpad(2)">2</button>
          <button class="nk" onclick="addNumpad(3)">3</button>
          <button class="nk" onclick="addNumpad(4)">4</button>
          <button class="nk" onclick="addNumpad(5)">5</button>
          <button class="nk" onclick="addNumpad(6)">6</button>
          <button class="nk" onclick="addNumpad(7)">7</button>
          <button class="nk" onclick="addNumpad(8)">8</button>
          <button class="nk" onclick="addNumpad(9)">9</button>
          <button class="nk nk-0" onclick="addNumpad(0)">0</button>
          <button class="nk nk-del" onclick="addNumpadDel()">&#x232B;</button>
        </div>
      </div>

      <!-- Native input (non-keypad mode) -->
      <div id="nativeAmtWrap" style="display:none">
        <div class="fgroup mb-1">
          <label>Jumlah (Rp)</label>
          <input type="number" id="nativeAmt" min="0" step="1000" placeholder="0"
            oninput="addSyncNative(this.value)">
        </div>
      </div>

      <!-- Description -->
      <div class="fgroup mb-1">
        <label>Keterangan
          <span style="font-weight:500;text-transform:none;letter-spacing:0;color:var(--muted)">
            (auto-kategori &#x1FAA4;)
          </span>
        </label>
        <input type="text" id="descInput" placeholder="contoh: makan siang, bensin&#x2026;">
      </div>

      <button class="btn btn-full btn-lg" id="addSubmitBtn" onclick="submitRecord()">
        &#x1F4BE; Simpan Transaksi
      </button>
    </div>
  </div>
</div>
