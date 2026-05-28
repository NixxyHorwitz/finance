<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar – Neofinance</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="page-center">
    <div class="auth-box">
      <p class="auth-title">Neofinance</p>
      <p class="auth-sub">Buat akun baru</p>

      <div id="alert" class="alert alert-danger"></div>

      <form id="registerForm">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" placeholder="pilih username" autocomplete="username" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="buat password" autocomplete="new-password" required>
        </div>
        <button type="submit" id="submitBtn" class="btn btn-full" style="margin-top:0.5rem;">Daftar</button>
      </form>

      <p style="text-align:center;margin-top:1rem;font-size:0.82rem;color:var(--muted);">
        Sudah punya akun? <a href="login.php" style="color:var(--text);font-weight:700;">Masuk</a>
      </p>
    </div>
  </div>

  <script>
    document.getElementById('registerForm').addEventListener('submit', async e => {
      e.preventDefault();
      const btn = document.getElementById('submitBtn');
      const alert = document.getElementById('alert');
      alert.classList.remove('show');
      btn.disabled = true;
      btn.textContent = 'Mendaftar...';
      const fd = new FormData(e.target);
      try {
        const res = await fetch('auth_actions.php?action=register', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          location.href = 'login.php?registered=1';
        } else {
          alert.textContent = data.message;
          alert.classList.add('show');
          btn.disabled = false;
          btn.textContent = 'Daftar';
        }
      } catch {
        alert.textContent = 'Terjadi kesalahan koneksi.';
        alert.classList.add('show');
        btn.disabled = false;
        btn.textContent = 'Daftar';
      }
    });
  </script>
</body>
</html>
