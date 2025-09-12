<?php
// /src/lib/template.php

/**
 * Costruisce un URL preservando i parametri correnti (GET) e sovrascrivendo
 * quelli passati in $params. Replica la logica dell’index monolitico.
 */
if (!function_exists('build_current_url')) {
    function build_current_url(array $params = [], ?string $base = null): string {
        // Base: index pubblico
        $base_url = $base ?: (APP_URL . '/index.php');

        // Parametri correnti
        $query_params = $_GET;

        // Assicura il parametro 'page' (chiave della pagina corrente)
        if (!isset($query_params['page']) && isset($_SESSION['current_page_key'])) {
            $query_params['page'] = $_SESSION['current_page_key'];
        }

        // Merge e build
        return $base_url . '?' . http_build_query(array_merge($query_params, $params));
    }
}

/**
 * Render globale: espone $currentPageKey e i dati del controller ai template.
 */
function render_page($pageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS) {
    // Estrae i dati ($records, $columns, …) per le viste
    extract($data);

    // Rende disponibile la chiave pagina per il layout/partials
    $currentPageKey = $pageKey;

    // Layout principale (include la view specifica)
    require_once __DIR__ . '/../../templates/layouts/main.php';
}
