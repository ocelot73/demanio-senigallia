<?php
// =====================================================================
// FILE: /src/lib/request_handler.php (COMPLETO E INTEGRATO)
// =====================================================================

function handle_ajax_request(&$FIELD_HELP) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        return;
    }
    
    $action = $_POST['action'];
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'error' => 'Azione non gestita.'];
    $conn = null;
    try {
        $conn = get_db_connection();
        // Include i nuovi modelli solo per le azioni pertinenti
        if (in_array($action, ['get_contabilita_tab', 'get_dettaglio_anno', 'save_sollecito'])) {
            require_once __DIR__ . '/../models/contabilita_model.php';
        }
        // !!! CORREZIONE: Usa il modello sollecito.php che ha la logica dei colori !!!
        if ($action === 'get_calendar_events') {
            require_once __DIR__ . '/../models/sollecito.php';
        }

        switch ($action) {
            // --- LOGICA ESISTENTE (PRESERVATA) ---
            case 'get_sid_details':
                $idf24 = $_POST['idf24'] ?? null;
                if (!$idf24) throw new Exception('ID F24 non fornito.');
                $detail_views = get_detail_views_config();
                $details_data = [];
                foreach ($detail_views as $key => $config) {
                    $view_name_str = $config['view'];
                    $view_name_ident = pg_escape_identifier($conn, $view_name_str);
                    $idf24_safe = pg_escape_string($conn, $idf24);
                    $query = "SELECT * FROM {$view_name_ident} WHERE idf24 = '{$idf24_safe}'";
                    $result = @pg_query($conn, $query);
                    $data = []; $query_error = null;
                    if ($result) { while ($row = pg_fetch_assoc($result)) { unset($row['geom'], $row['geom_p']); $data[] = $row; }
                    } else { $query_error = "Errore nella vista: " . $view_name_str; }
                    $full_view_name = DB_SCHEMA . '.' . $view_name_str;
                    $full_view_name_lit = pg_escape_literal($conn, $full_view_name);
                    $comment = null;
                    $cres = @pg_query($conn, "SELECT obj_description({$full_view_name_lit}::regclass, 'pg_class') AS comment");
                    if ($cres) { $crow = pg_fetch_assoc($cres); if ($crow && isset($crow['comment'])) $comment = $crow['comment']; @pg_free_result($cres); }
                    $details_data[$key] = [ 'label' => $config['label'], 'short_label' => $config['short_label'] ?? null, 'icon' => $config['icon'] ?? 'fas fa-question-circle', 'comment' => $comment, 'data' => $data, 'count' => count($data), 'error' => $query_error ];
                }
                $response = $details_data;
                break;
            case 'get_concessione_edit':
                $idf24 = $_POST['idf24'] ?? null;
                if ($idf24 === null) throw new Exception('ID F24 non fornito.');
                $meta_res = pg_query_params($conn, "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = $1 AND table_name = 'concessioni' ORDER BY ordinal_position", [DB_SCHEMA]);
                if (!$meta_res) throw new Exception('Impossibile leggere le colonne di demanio.concessioni.');
                $columns = [];
                while ($m = pg_fetch_assoc($meta_res)) { if ($m['column_name'] === 'geom') continue;
                $columns[] = [ 'name' => $m['column_name'], 'data_type' => $m['data_type'], 'ui_type' => map_pg_type_to_ui($m['data_type']), 'nullable' => strtolower($m['is_nullable'] ?? '') === 'yes' ]; }
                $row_res = pg_query_params($conn, "SELECT * FROM demanio.concessioni WHERE idf24::text = $1 LIMIT 1", [strval($idf24)]);
                $values = [];
                if ($row_res && pg_num_rows($row_res) > 0) { $values = pg_fetch_assoc($row_res); unset($values['geom']);
                } else { foreach ($columns as $c) { $values[$c['name']] = null; } }
                $latest_fmt = 'n/d';
                $ts_res = @pg_query_params($conn, "SELECT to_char(max(operation_time) AT TIME ZONE 'Europe/Rome', 'DD/MM/YYYY HH24:MI') AS fmt FROM demanio.concessioni_log_v WHERE idf24::text = $1", [strval($idf24)]);
                if ($ts_res && pg_num_rows($ts_res) > 0) { $ts_row = pg_fetch_assoc($ts_res); if ($ts_row && !empty($ts_row['fmt'])) $latest_fmt = $ts_row['fmt']; }
                $response = ['columns' => $columns, 'values' => $values, 'idf24' => $idf24, 'last_operation_time_fmt' => $latest_fmt, 'success' => true];
                break;
            case 'save_concessione_edit':
                $original_idf24 = $_POST['original_idf24'] ?? null;
                $updates = json_decode($_POST['updates'] ?? '{}', true) ?: [];
                if ($original_idf24 === null) throw new Exception('ID F24 originale non fornito.');
                $meta_res = pg_query_params($conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = $1 AND table_name = 'concessioni'", [DB_SCHEMA]);
                if (!$meta_res) throw new Exception('Impossibile leggere i metadati della tabella concessioni.');
                $types = [];
                while ($row = pg_fetch_assoc($meta_res)) { $types[$row['column_name']] = $row['data_type']; }
                $set = [];
                $params = []; $idx = 1;
                foreach ($updates as $col => $val) {
                    if (!isset($types[$col]) || $col === 'geom') continue;
                    $val_norm = normalize_value_for_db($val, $types[$col]);
                    if ($val_norm === null) { $set[] = pg_escape_identifier($conn, $col) . " = NULL";
                    } else { $set[] = pg_escape_identifier($conn, $col) . " = $" . ($idx); $params[] = $val_norm; $idx++; }
                }
                if (empty($set)) { $response = ['success' => true, 'message' => 'Nessuna modifica da salvare.']; break; }
                $params[] = strval($original_idf24);
                $sql = "UPDATE demanio.concessioni SET " . implode(', ', $set) . " WHERE idf24::text = $" . $idx;
                $res = @pg_query_params($conn, $sql, $params);
                if (!$res || pg_affected_rows($res) === 0) {
                     $insert_cols = [];
                     $insert_vals = []; $insert_params = []; $p = 1; $all_insert_data = array_merge(['idf24' => $updates['idf24'] ?? $original_idf24], $updates);
                     foreach ($all_insert_data as $col => $val) {
                        if (!isset($types[$col]) || $col === 'geom') continue;
                        $val_norm = normalize_value_for_db($val, $types[$col]);
                        $insert_cols[] = pg_escape_identifier($conn, $col);
                        if ($val_norm === null) { $insert_vals[] = 'NULL';
                        } else { $insert_vals[] = '$' . $p; $insert_params[] = $val_norm; $p++; }
                    }
                    if (count($insert_cols) > 0) {
                        $ins_sql = "INSERT INTO demanio.concessioni (" . implode(', ', $insert_cols) . ") VALUES (" . implode(', ', $insert_vals) . ")";
                        $ins_res = @pg_query_params($conn, $ins_sql, $insert_params);
                        if (!$ins_res) throw new Exception('Errore durante INSERT: ' . pg_last_error($conn));
                    }
                }
                $new_idf24 = $updates['idf24'] ?? $original_idf24;
                $latest_fmt = 'n/d';
                $ts_res = @pg_query_params($conn, "SELECT to_char(max(operation_time) AT TIME ZONE 'Europe/Rome', 'DD/MM/YYYY HH24:MI') AS fmt FROM demanio.concessioni_log_v WHERE idf24::text = $1", [strval($new_idf24)]);
                if ($ts_res && pg_num_rows($ts_res) > 0) { $ts_row = pg_fetch_assoc($ts_res); if ($ts_row && !empty($ts_row['fmt'])) $latest_fmt = $ts_row['fmt']; }
                $response = ['success' => true, 'last_operation_time_fmt' => $latest_fmt];
                break;
            case 'set_filter': if (isset($_POST['set_filter'])) { if (empty($_POST['filter_value'])) unset($_SESSION['column_filters'][$_POST['set_filter']]); else $_SESSION['column_filters'][$_POST['set_filter']] = $_POST['filter_value']; } $response = ['success' => true]; break;
            case 'toggle_column': if (isset($_POST['toggle_column'])) { $hidden = $_SESSION['hidden_columns'] ?? []; if (($key = array_search($_POST['toggle_column'], $hidden)) !== false) unset($hidden[$key]);
            else $hidden[] = $_POST['toggle_column']; $_SESSION['hidden_columns'] = array_values($hidden); } $response = ['success' => true]; break;
            case 'save_column_order': if (isset($_POST['column_order']) && is_array($_POST['column_order'])) $_SESSION['column_order'] = $_POST['column_order']; $response = ['success' => true]; break;
            case 'save_column_widths': if (isset($_POST['column_widths']) && is_array($_POST['column_widths'])) $_SESSION['column_widths'] = $_POST['column_widths']; $response = ['success' => true]; break;
            
            // --- NUOVA LOGICA PER GESTIONE CONTABILE ---
            case 'get_contabilita_tab':
                $idf24 = $_POST['idf24'] ?? null;
                if (!$idf24) throw new Exception('IDF24 non fornito.');
                $data['estrattoconto'] = get_estrattoconto_model($conn, $idf24);
                $data['data_sincronizzazione'] = get_data_sincronizzazione_model($conn, $idf24);
                ob_start();
                require __DIR__ . '/../../templates/partials/contabilita_tab.php';
                $html = ob_get_clean();
                $response = ['success' => true, 'html' => $html];
                break;
            case 'get_dettaglio_anno':
                $idf24 = $_POST['idf24'] ?? null;
                $anno = $_POST['anno'] ?? null;
                $id_canone = $_POST['id_canone'] ?? null;
                if (!$idf24 || !$anno || !$id_canone) throw new Exception('Dati mancanti per caricare il dettaglio.');
                $response['success'] = true;
                $response['rate_sid'] = get_dettaglio_rate_sid_model($conn, $idf24, $anno);
                $response['solleciti'] = get_solleciti_model($conn, $id_canone);
                break;
            case 'save_sollecito':
                $data = [
                    'id_canone_annuale' => filter_input(INPUT_POST, 'id_canone_annuale', FILTER_VALIDATE_INT),
                    'livello' => $_POST['livello'] ?? '',
                    'data_invio' => $_POST['data_invio'] ?? '',
                    'giorni' => filter_input(INPUT_POST, 'giorni', FILTER_VALIDATE_INT),
                    'protocollo' => $_POST['protocollo'] ?? '',
                    'importo' => filter_input(INPUT_POST, 'importo', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
                ];
                if (in_array(false, $data, true)) throw new Exception('Dati per il sollecito non validi o mancanti.');
                $id = save_sollecito_model($conn, $data);
                if ($id) { $response = ['success' => true, 'message' => 'Sollecito salvato con successo.'];
                } else { throw new Exception('Salvataggio del sollecito fallito.'); }
                break;
            
            // !!! CORREZIONE: Usa il modello get_solleciti_as_events_model !!!
            case 'get_calendar_events':
                $start = $_POST['start'] ?? date('Y-m-01');
                $end = $_POST['end'] ?? date('Y-m-t');
                // Questa funzione è più completa e include la logica dei colori
                $response = get_solleciti_as_events_model($conn, $start, $end);
                break;

            case 'run_manual_sync':
                // Usa la funzione custom db_query per coerenza
                $sync_result = db_query($conn, "SELECT demanio.sincronizza_dati_contabili();", []);
                if ($sync_result) {
                    $message = pg_fetch_result($sync_result, 0, 0);
                    $response = ['success' => true, 'message' => $message];
                } else {
                    throw new Exception("Esecuzione della funzione di sincronizzazione fallita.");
                }
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        $response = ['success' => false, 'error' => $e->getMessage()];
    } finally {
        if ($conn) pg_close($conn);
    }
    
    // FullCalendar si aspetta un array JSON, non un oggetto {success: ..., data: ...}
    if ($action === 'get_calendar_events') {
        echo json_encode($response);
    } else {
        echo json_encode($response);
    }
    exit;
}

function get_detail_views_config() {
    return [ 'sintesi_atti' => ['label' => 'SINTESI ATTI', 'view' => 'sintesi_atti_mv', 'icon' => 'fas fa-file-invoice'], 'atti_amministrativi' => ['label' => 'ATTI AMMINISTRATIVI', 'view' => 'mv_atti_amministrativi', 'icon' => 'fas fa-landmark'], 'soggetti' => ['label' => 'SOGGETTI', 'view' => 'mv_rel_atti_soggetti', 'icon' => 'fas fa-users'], 'zd_oggetti' => ['label' => 'ZONE D.M. E OGGETTI', 'view' => 'mv_zd_oggetti_superficie', 'icon' => 'fas fa-map-marked-alt'], 'oggetti_punti' => ['label' => 'OGGETTI', 'view' => 'mv_oggetti', 'icon' => 'fas fa-map-marker-alt'], 'integrazioni' => ['label' => 'INTEGRAZIONI', 'view' => 'mv_integrazioni', 'icon' => 'fas fa-puzzle-piece'], 'rate_canone' => ['label' => 'RATE CANONE', 'view' => 'mv_rate_canone', 'icon' => 'fas fa-receipt'], 'amministrazioni' => ['label' => 'AMMINISTRAZIONI', 'view' => 'mv_amministrazioni', 'icon' => 'fas fa-building-columns'], 'stagionalita' => ['label' => 'STAGIONALITA\'', 'view' => 'mv_stagionalita', 'icon' => 'fas fa-calendar-alt'], 'rel_amministrazioni' => ['label' => 'REL. ATTI AMMINISTRAZ.', 'short_label' => 'REL. ATTI AMMINISTRAZ.', 'view' => 'mv_rel_atti_amministrazioni', 'icon' => 'fas fa-sitemap'], 'rel_pdf' => ['label' => 'REL. ATTI PDF', 'view' => 'mv_rel_atti_pdf', 'icon' => 'fas fa-file-pdf'], 'aggiornamenti' => ['label' => 'RIFERIMENTI', 'view' => 'atti_aggiornamenti_mv', 'icon' => 'fas fa-history'], 'documenti' => ['label' => 'DOCUMENTI', 'view' => 'mv_documenti', 'icon' => 'fas fa-folder-open'], 'occupazioni' => ['label' => 'OCCUPAZIONI', 'view' => 'mv_occupazioni', 'icon' => 'fas fa-draw-polygon'], 'deroghe' => ['label' => 'DEROGHE SCADENZE ATTI', 'view' => 'mv_deroghe_scadenze_atti', 'icon' => 'fas fa-calendar-check'], 'planimetrie' => ['label' => 'PLANIMETRIE RICHIESTE', 'view' => 'mv_planimetrie_richieste', 'icon' => 'fas fa-drafting-compass'], 'contestazioni' => ['label' => 'CONTESTAZIONI', 'view' => 'mv_contestazioni', 'icon' => 'fas fa-exclamation-triangle'] ];
}
function map_pg_type_to_ui($t) {
    $t = strtolower($t);
    if ($t === 'boolean' || $t === 'bool') return 'boolean';
    if ($t === 'date') return 'date';
    if (str_contains($t, 'timestamp')) return 'datetime';
    if (in_array($t, ['int2','int4','int8','smallint','integer','bigint','numeric','decimal','real','double precision','float4','float8'])) return 'number';
    return 'text';
}
function normalize_value_for_db($v, $type) {
    $type = strtolower($type);
    if ($v === '' || $v === null || (is_string($v) && strtoupper($v) === 'NULL')) return null;
    if ($type === 'boolean' || $type === 'bool') {
        $v_lower = strtolower(trim((string)$v));
        return in_array($v_lower, ['1','t','true','vero','yes','y','on']) ? 't' : 'f';
    } elseif (in_array($type, ['int2','int4','int8','smallint','integer','bigint','numeric','decimal','real','double precision','float4','float8'])) {
        $v_clean = str_replace(['.', ' '], ['', ''], (string)$v);
        $v_clean = str_replace(',', '.', $v_clean);
        return is_numeric($v_clean) ? $v_clean : null;
    }
    return $v;
}
?>
