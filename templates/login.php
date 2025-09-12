<?php // /templates/login.php ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Accesso Riservato</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=titillium-web:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>Accesso Riservato</h1>
        <?php if (!empty($login_error)): ?>
            <div class="error-message"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="post" class="login-form">
            <input type="hidden" name="login" value="1">
            <label>Username</label>
            <input type="text" name="username" required autofocus>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn-primary">Entra</button>
        </form>
        <p class="login-note">Comune di Senigallia – Servizio Demanio</p>
    </div>
</body>
</html>
