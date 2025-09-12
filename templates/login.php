<?php // /templates/login.php ?>
<!DOCTYPE html>
<html lang="it" class="login-page">
<head>
    <meta charset="UTF-8">
    <title>Login - Gestione Demanio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=titillium-web:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root{
            --font-family: 'Titillium Web', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --color-bg: #f0f4f8;
            --color-surface: #ffffff;
            --color-border: #e2e8f0;
            --color-text-primary: #0f172a;
            --color-text-secondary: #475569;
            --color-primary: #3b82f6;
            --color-primary-hover: #2563eb;
        }
        *{ box-sizing: border-box; }
        html,body{ height:100%; margin:0; font-family: var(--font-family); color: var(--color-text-primary); }
        body{
            display:flex; align-items:center; justify-content:center;
            background: radial-gradient(1200px 600px at 20% 20%, #e8f0ff 0%, transparent 55%),
                        radial-gradient(900px 700px at 80% 30%, #f4fff5 0%, transparent 60%),
                        linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        }
        .login-card{
            width: 100%; max-width: 420px; background: var(--color-surface);
            border: 1px solid var(--color-border); border-radius: 16px; padding: 28px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, .08);
        }
        .login-header{ display:flex; align-items:center; gap:12px; margin-bottom: 14px; }
        .login-header img{ width:40px; height:40px; border-radius: 8px; }
        .login-header .title{ font-size: 1.4rem; font-weight: 700; }
        .login-subtitle{ font-size:.95rem; color: var(--color-text-secondary); margin-bottom: 18px; }
        .form-group{ margin-bottom: 14px; }
        label{ font-weight:600; font-size:.9rem; display:block; margin-bottom:6px; }
        input[type="text"], input[type="password"]{
            width:100%; padding:10px 12px; border:1px solid var(--color-border); border-radius:8px; font-size:1rem;
        }
        .btn-primary{
            display:inline-flex; gap:8px; align-items:center; justify-content:center; width:100%;
            background: var(--color-primary); color:#fff; border:none; padding:10px 14px; border-radius:10px;
            font-weight:700; cursor:pointer; transition: .2s ease;
        }
        .btn-primary:hover{ background: var(--color-primary-hover); }
        .helper{ font-size:.85rem; color: var(--color-text-secondary); margin-top:10px; text-align:center;}
        .error{ color:#b91c1c; margin-bottom:10px; font-weight:600; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" alt="Comune di Senigallia">
            <div class="title">Gestione Demanio – Accesso</div>
        </div>
        <div class="login-subtitle">Inserisci le credenziali per accedere.</div>

        <?php if (!empty($login_error ?? '')): ?>
            <div class="error"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= APP_URL ?>/index.php">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label>Utente</label>
                <input type="text" name="username" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" autocomplete="current-password" required>
            </div>
            <button class="btn-primary" type="submit">
                <i class="fas fa-right-to-bracket"></i> Entra
            </button>
        </form>

        <div class="helper">Comune di Senigallia – U.O. Demanio</div>
    </div>
</body>
</html>
