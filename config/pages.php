<?php
// /config/pages.php

/**
 * ==========================================================
 * Definizione Pagine e Viste dell'Applicazione
 * ==========================================================
 * 'key' => ['label', 'view', 'controller', 'table' (opzionale), 'icon', 'title']
 */
$PAGES = [
    'concessioni' => ['label' => 'Concessioni', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'table' => 'concessioni_unione_v', 'icon' => 'fas fa-umbrella-beach', 'title' => 'Tabella concessioni demaniali marittime - SID'],
    'stampa_unione' => ['label' => 'Stampa Unione', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'table' => 'stampa_unione_v', 'icon' => 'fas fa-file-word', 'title' => 'Tabella per stampa unione Microsoft Word'],
    'protocollo_batch' => ['label' => 'Protocollo Batch', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'table' => 'invio_massivo_v', 'icon' => 'fas fa-paper-plane', 'title' => 'Elenco per protocollo batch da file tramite JEnte'],
    'protocolli_canoni' => ['label' => 'Elenco Protocolli', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'table' => 'protocolli_canoni_inviati_v', 'icon' => 'fas fa-list', 'title' => 'Tabella con riepilogo n. protocolli inviati'],
    'calcolo_canoni' => ['label' => 'Calcolo Canoni', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'table' => 'calcolo_canoni_v', 'icon' => 'fas fa-calculator', 'title' => 'Calcolo Canoni Demaniali Marittimi'],
    'report_protocolli_regione' => ['label' => 'Report Regione', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'table' => 'report_canoni_imposta_reg_v', 'icon' => 'fas fa-chart-line', 'title' => 'Report Protocolli Demanio Regione'],
    'scadenzario' => ['label' => 'Scadenzario Solleciti', 'view' => 'scadenzario', 'controller' => 'solleciti_controller.php', 'icon' => 'fas fa-calendar-alt', 'title' => 'Scadenzario per il pagamento dei canoni'],
    'importa' => ['label' => 'Importa Dati SID', 'view' => 'importa', 'controller' => 'importa_controller.php', 'icon' => 'fas fa-upload', 'title' => 'Importazione Dati Demaniali (SID)'],
    'modifica_concessioni' => [
        'label' => 'Modifica Concessioni',
        'url'   => 'https://sit.comune.senigallia.an.it/demanio/admin.php?pgsql=localhost&username=demanio&db=area11&ns=demanio&select=concessioni&columns%5B0%5D%5Bfun%5D=&columns%5B0%5D%5Bcol%5D=&where%5B0%5D%5Bcol%5D=cessata&where%5B0%5D%5Bop%5D=IS+NULL&where%5B0%5D%5Bval%5D=&where%5B01%5D%5Bcol%5D=&where%5B01%5D%5Bop%5D=%3D&where%5B01%5D%5Bval%5D=&order%5B0%5D=denominazione+ditta+concessionario&order%5B01%5D=&limit=300&text_length=300', 
        'title' => 'Modifica la tabella delle concessioni in una nuova scheda',
        'icon'  => 'fas fa-edit'
    ],
];

/**
 * ==========================================================
 * Definizione Gruppi di Menu
 * ==========================================================
 */
$MENU_GROUPS = [
    'Canoni' => [
        'icon' => 'fas fa-file-invoice-dollar',
        'pages' => [
            'stampa_unione',
            'protocollo_batch',
            'protocolli_canoni',
            'calcolo_canoni',
            'report_protocolli_regione'
        ]
    ]
];
