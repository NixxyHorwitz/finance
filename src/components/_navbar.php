<?php
/**
 * Shared bottom navigation bar.
 * Usage: Set $navPage before including:
 *   $navPage = 'home' | 'history' | 'flow' | 'settings'
 *   include 'src/components/_navbar.php';
 */
$navPage = $navPage ?? '';
?>
<nav class="bottom-nav">
  <a class="nav-item <?= $navPage==='home'     ? 'active' : '' ?>" href="/">
    <div class="nav-icon">&#x1F3E0;</div>
    <div class="nav-label">Beranda</div>
  </a>
  <a class="nav-item <?= $navPage==='history'  ? 'active' : '' ?>" href="history">
    <div class="nav-icon">&#x1F4CB;</div>
    <div class="nav-label">Riwayat</div>
  </a>
  <div class="nav-fab" onclick="openAddModal()">&#xFF0B;</div>
  <a class="nav-item <?= $navPage==='flow'     ? 'active' : '' ?>" href="flow">
    <div class="nav-icon">&#x1F4CA;</div>
    <div class="nav-label">Aliran</div>
  </a>
  <a class="nav-item <?= $navPage==='settings' ? 'active' : '' ?>" href="settings">
    <div class="nav-icon">&#x2699;&#xFE0F;</div>
    <div class="nav-label">Pengaturan</div>
  </a>
</nav>
