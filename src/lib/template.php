<?php
// /src/lib/template.php

if (!function_exists('build_current_url')) {
    /**
     * Costruisce un URL preservando i parametri correnti e sovrascrivendo quelli passati.
     */
    function build_current_url(array $params = [], ?string $base = null): string {
        $base_url = $base ?: (APP_URL . '/index.php');
        $query_params = $_GET;

        // assicura 'page' se già noto in sessione
        if (!isset($query_params['page']) && isset($_SESSION['current_page_key'])) {
            $query_params['page'] = $_SESSION['current_page_key'];
        }
        return $base_url . '?' . http_build_query(array_merge($query_params, $params));
    }
}

/**
 * Render globale: espone $currentPageKey e i dati del controller ai template.
 */
function render_page($pageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS) {
    extract($data); // espone $records, $columns, ecc.
    $currentPageKey = $pageKey;
    require_once __DIR__ . '/../../templates/layouts/main.php';
}
