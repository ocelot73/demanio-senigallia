<?php // /templates/login.php ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - Gestione Demanio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" type="image/webp">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=titillium-web:400,600,700&display=swap" rel="stylesheet">
    <style>
        :root { --font-family: 'Titillium Web', sans-serif; --primary-color: #3b82f6; --primary-color-dark: #2563eb; }
        body { font-family: var(--font-family); margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .login-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 24px; padding: 48px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); width: 100%; max-width: 420px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.2); }
        .login-logo { width: 70px; height: auto; margin-bottom: 24px; }
        .login-card h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; color: #1e293b; }
        .login-card p { font-size: 16px; color: #475569; margin: 0 0 32px 0; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #334155; }
        .form-group input { width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 12px; box-sizing: border-box; font-family: var(--font-family); font-size: 16px; transition: all 0.2s ease; background: #f8fafc; }
        .form-group input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); }
        .btn-login { width: 100%; padding: 16px; background: var(--primary-color); color: white; border: none; border-radius: 12px; cursor: pointer; font-size: 18px; font-family: var(--font-family); font-weight: 700; transition: all 0.2s ease; }
        .btn-login:hover { background: var(--primary-color-dark); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }
        .error-message { color: #dc2626; background: #fee2e2; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fecaca; }

        html.dark-theme { --primary-color: #5fa5ff; --primary-color-dark: #3b82f6; }
        html.dark-theme body { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
        html.dark-theme .login-card { background: rgba(30, 41, 59, 0.9); border: 1px solid rgba(51, 65, 85, 0.5); }
        html.dark-theme .login-card h1, html.dark-theme .form-group label { color: #f1f5f9; }
        html.dark-theme .login-card p { color: #94a3b8; }
        html.dark-theme .form-group input { background: #334155; border-color: #475569; color: #f1f5f9; }
        html.dark-theme .form-group input:-webkit-autofill { -webkit-box-shadow: 0 0 0 30px #334155 inset !important; -webkit-text-fill-color: #f1f5f9 !important; }
        html.dark-theme .form-group input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(95, 165, 255, 0.3); }
        html.dark-theme .error-message { color: #f87171; background: #450a0a; border-color: #7f1d1d; }
    </style>
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
</head>
<body>
    <div class="login-card">
        <img src="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" alt="Logo Comune di Senigallia" class="login-logo">
        <h1>Accesso Riservato</h1>
        <p>Gestione Concessioni Demaniali</p>
        <?php if (!empty($login_error)): ?>
            <div class="error-message"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= APP_URL ?>/index.php">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Accedi</button>
        </form>
    </div>
</body>
</html>
