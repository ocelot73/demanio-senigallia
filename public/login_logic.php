<?php
// /public/login_logic.php
session_start();
require_once __DIR__ . '/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: login_form.php?error=Username e password sono obbligatori.");
        exit;
    }

    if (isset(VALID_USERS[$username]) && VALID_USERS[$username] === $password) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['first_load_after_login'] = true;
        
        // Usa un percorso relativo che funzionerà correttamente con la configurazione Nginx
        header("Location: index.php?page=concessioni");
        exit;
        
    } else {
        header("Location: login_form.php?error=Credenziali non valide.");
        exit;
    }
} else {
    header("Location: login_form.php");
    exit;
}
?>
