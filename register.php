<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Neobrutalism Finance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container flex items-center justify-center" style="height: 100vh;">
        <div class="neo-box" style="width: 100%; max-width: 400px;">
            <h1 class="text-3xl text-bold text-center mb-6" style="color: var(--primary);">REGISTER</h1>
            
            <div id="msgBox" class="neo-box mb-4" style="display: none; background-color: var(--secondary); padding: 0.75rem; border: 2px solid #000;">
                <p id="msgText" class="text-bold"></p>
            </div>

            <form id="registerForm" class="flex-col gap-4 flex">
                <div>
                    <label class="text-bold mb-2" style="display: block;">Username</label>
                    <input type="text" name="username" class="neo-input" placeholder="Enter username" required>
                </div>
                <div>
                    <label class="text-bold mb-2" style="display: block;">Password</label>
                    <input type="password" name="password" class="neo-input" placeholder="Enter password" required>
                </div>
                <button type="submit" class="neo-btn mt-4" id="submitBtn" style="width: 100%;">REGISTER</button>
            </form>

            <p class="text-center mt-6 text-bold">
                Already have an account? <a href="login.php" style="color: var(--secondary);">Login here</a>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const msgBox = document.getElementById('msgBox');
            btn.disabled = true;
            btn.innerText = 'REGISTERING...';
            
            const formData = new FormData(e.target);
            try {
                const res = await fetch('auth_actions.php?action=register', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    window.location.href = 'login.php?registered=1';
                } else {
                    msgBox.style.display = 'block';
                    document.getElementById('msgText').innerText = data.message;
                    btn.disabled = false;
                    btn.innerText = 'REGISTER';
                }
            } catch (err) {
                msgBox.style.display = 'block';
                document.getElementById('msgText').innerText = "An error occurred";
                btn.disabled = false;
                btn.innerText = 'REGISTER';
            }
        });
    </script>
</body>
</html>
