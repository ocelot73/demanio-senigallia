<?php
// /config/config.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * ==========================================================
 * Configurazione Database
 * ==========================================================
 */
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'area11');
define('DB_USER', 'demanio');
define('DB_PASS', 'demanio60019!');
define('DB_SCHEMA', 'demanio');

/**
 * ==========================================================
 * Configurazione Applicazione
 * ==========================================================
 */
define('APP_NAME', 'Gestione Demanio');
// !!! CORREZIONE CRITICA: INSERISCI QUI L'URL COMPLETO DELLA CARTELLA PUBLIC !!!
define('APP_URL', 'https://sit.comune.senigallia.an.it/demanio-senigallia/public');
define('RECORDS_PER_PAGE', 35);

date_default_timezone_set('Europe/Rome');

/**
 * ==========================================================
 * Configurazione HELP (da file originale)
 * ==========================================================
 */
$FIELD_HELP = [
    'idf24' => [
        'label'   => 'idf24',
        'title'   => 'ID Concessione',
        'content' => '<p>Codice univoco della pratica (modificabile con cautela).</p>',
        'hint'    => 'Es. 2025000012',
        'examples'=> ['2023000456','2024000100'],
    ],
    // ... INCOLLA QUI IL RESTO DELL'ARRAY $FIELD_HELP DAL TUO VECCHIO FILE index.php ...
];
