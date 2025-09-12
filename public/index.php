<?php
// /public/index.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// 1) Bootstrap
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';
require_once __DIR__ . '/../src/lib/request_handler.php';

// ***** INIZIO CODICE CORRETTO/AGGIUNTO *****
// 2) GESTIONE RICHIESTE AJAX
// Controlla se è una richiesta AJAX (POST con 'action') e la gestisce immediatamente,
// replicando il comportamento del file index.php originale che gestiva tutto in un unico punto.
if (!empty($_POST['action'])) {
    handle_ajax_request($FIELD_HELP);
    // Lo script termina qui per le chiamate AJAX, dopo aver inviato una risposta JSON.
    exit;
}
// ***** FINE CODICE CORRETTO/AGGIUNTO *****


// 3) Determinazione pagina corrente
$currentPageKey = $_GET['page'] ?? ($_SESSION['current_page_key'] ?? 'concessioni');
if (!array_key_exists($currentPageKey, $PAGES)) {
    $currentPageKey = 'concessioni';
}
$_SESSION['current_page_key'] = $currentPageKey;
$pageConfig = $PAGES[$currentPageKey];

// Utility redirect URL per la pagina corrente
$base_redirect_url = strtok(APP_URL . $_SERVER['REQUEST_URI'], '?');


// 4) Azioni GET che mutano lo stato della vista (logica originale preservata)
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}
if (isset($_GET['reset_view'])) {
    unset($_SESSION['hidden_columns'], $_SESSION['column_filters'], $_SESSION['column_order'], $_SESSION['column_widths'], $_SESSION['full_view']);
