<?php
// /public/index.php

ini_set('display_errors', 1); // <-- RIGA AGGIUNTA PER DEBUG
error_reporting(E_ALL);      // <-- RIGA AGGIUNTA PER DEBUG

session_start();

// 1. Caricamento Iniziale
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/pages.php';
require_once __DIR__ . '/../src/lib/database.php';
require_once __DIR__ . '/../src/lib/template.php';
require_once __DIR__ . '/../src/lib/request_handler.php';

// 2. Gestione Richieste AJAX
handle_ajax_request($FIELD_HELP);

// 3. Gestione Azioni (da bottoni e link)
$currentPageKey = $_GET['page'] ?? $_SESSION['current_page_key'] ?? 'concessioni';
$redirect_url = APP_URL . '/index.php?page=' . $currentPageKey;

// Logica per i filtri rapidi (SOLO per la pagina concessioni)
if ($currentPageKey === 'concessioni' && isset($_GET['filter_type'])) {
    $filter_type = $_GET['filter_type'];
    $new_filters = [];
    switch ($filter_type) {
        case 'verifica_not_null_pec_null':
            $new_filters['verifica'] = 'NOT_NULL';
            $new_filters['pec inviata'] = 'NULL';
            break;
        case 'verifica_not_null_pec_not_null':
            $new_filters['verifica'] = 'NOT_NULL';
            $new_filters['pec inviata'] = 'NOT_NULL';
            break;
        case 'verifica_null_pec_null':
            $new_filters['verifica'] = 'NULL';
            $new_filters['pec inviata'] = 'NULL';
            break;
    }
    $_SESSION['column_filters'] = $new_filters;
    header('Location: ' . $redirect_url
