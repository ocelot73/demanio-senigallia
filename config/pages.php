<?php
// config/pages.php

/**
 * ==========================================================
 * Definizione Viste e Pagine
 * ==========================================================
 */
$PAGES = [
    'concessioni' => ['label' => 'Concessioni', 'table' => 'concessioni_unione_v', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'icon' => 'fas fa-umbrella-beach', 'title' => 'Tabella concessioni demaniali marittime - SID'],
    'stampa_unione' => ['label' => 'Stampa Unione', 'table' => 'stampa_unione_v', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'icon' => 'fas fa-file-word', 'title' => 'Tabella per stampa unione Microsoft Word'],
    'protocollo_batch' => ['label' => 'Protocollo Batch', 'table' => 'invio_massivo_v', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'icon' => 'fas fa-paper-plane', 'title' => 'Elenco per protocollo batch da file tramite JEnte'],
    'protocolli_canoni' => ['label' => 'Elenco Protocolli', 'table' => 'protocolli_canoni_inviati_v', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'icon' => 'fas fa-list', 'title' => 'Tabella con riepilogo n. protocolli inviati'],
    'calcolo_canoni' => ['label' => 'Calcolo Canoni', 'table' => 'calcolo_canoni_v', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'icon' => 'fas fa-calculator', 'title' => 'Calcolo Canoni Demaniali Marittimi'],
    'report_protocolli_regione' => ['label' => 'Report Regione', 'table' => 'report_canoni_imposta_reg_v', 'view' => 'concessioni', 'controller' => 'concessioni_controller.php', 'icon' => 'fas fa-chart-line', 'title' => 'Report Protocolli Demanio Regione'],
    'scadenzario' => ['label' => 'Scadenzario Solleciti', 'view' => 'scadenzario', 'controller' => 'solleciti_controller.php', 'icon' => 'fas fa-calendar-alt', 'title' => 'Scadenzario Pagamento Canoni'],
    'importa' => ['label' => 'Importa Dati SID', 'view' => 'importa', 'icon' => 'fas fa-upload', 'title' => 'Importazione Dati Demaniali (SID)'],
    'modifica_concessioni' => [
        'label' => 'Modifica Concessioni',
        'url'   => 'https://sit.comune.senigallia.an.it/demanio/admin.php?pgsql=localhost&username=demanio&db=area11&ns=demanio&select=concessioni&columns%5B0%5D%5Bfun%5D=&columns%5B0%5D%5Bcol%5D=&where%5B0%5D%5Bcol%5D=cessata&where%5B0%5D%5Bop%5D=IS+NULL&where%5B0%5D%5Bval%5D=&where%5B01%5D%5Bcol%5D=&where%5B01%5D%5Bop%5D=%3D&where%5B01%5D%5Bval%5D=&order%5B0%5D=denominazione+ditta+concessionario&order%5B01%5D=&limit=300&text_length=300',
        'title' => 'Modifica la tabella delle concessioni in una nuova scheda',
        'icon'  => 'fas fa-edit'
    ],
];

// Definizione del gruppo di sottomenu "Canoni"
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
