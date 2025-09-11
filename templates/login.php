<?php // /templates/login.php ?>
<!DOCTYPE html><html lang="it" class="login-page"><head><meta charset="UTF-8"><title>Login - Gestione Demanio</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" type="image/webp">
<link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=titillium-web:400,600,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head><body>
<div class="login-card">
    <img src="https://www.comune.senigallia.an.it/wp-content/uploads/2024/07/Senigallia-Stemma.webp" alt="Logo Comune di Senigallia" class="login-logo">
    <h1>Accesso Riservato</h1><p>Gestione Concessioni Demaniali</p>
    <?php if (isset($login_error)): ?><div class="error-message"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
    <form method="POST"><input type="hidden" name="login" value="1">
        <div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" required autocomplete="username"></div>
        <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
        <button type="submit" class="btn-login">Accedi</button>
    </form>
</div>
</body></html>
