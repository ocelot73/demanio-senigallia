<?php
// /public/index.php

ini_set('display_errors', 1);
// Abilita la visualizzazione degli errori per il debug
error_reporting(E_ALL);

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

// --- CORREZIONE: Applica i filtri rapidi SOLO se la pagina è 'concessioni' ---
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
    header('Location: ' . $redirect_url);
    exit;
}

// --- CORREZIONE: Aggiunta la logica per i bottoni dell'interfaccia ---
if (isset($_GET['reset_view'])) {
    unset($_SESSION['hidden_columns'], $_SESSION['column_filters'], $_SESSION['column_order'], $_SESSION['column_widths'], $_SESSION['full_view']);
    header('Location: ' . $redirect_url);
    exit
