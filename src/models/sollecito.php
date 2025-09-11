<?php
// /src/models/sollecito.php

/**
 * Recupera i solleciti come eventi per FullCalendar.
 */
function get_solleciti_as_events_model($conn, $start, $end) {
    $sql = "
        SELECT 
            s.id,
            s.data_scadenza as start,
            s.stato,
            s.tipo_sollecito,
            c.denominazione_ditta_concessionario,
            rp.importo_richiesto
        FROM demanio.solleciti s
        JOIN demanio.richieste_pagamento rp ON s.id_richiesta = rp.id
        JOIN demanio.concessioni_unione_v c ON rp.idf24 = c.idf24
        WHERE s.data_scadenza BETWEEN $1 AND $2
    ";
    
    $result = db_query($conn, $sql, [$start, $end]);
    $events = [];
    
    while ($row = pg_fetch_assoc($result)) {
        $color = '#3b82f6'; // Blu (Inviato)
        $is_overdue = $row['start'] < date('Y-m-d') && $row['stato'] !== 'Pagato';

        if ($row['stato'] === 'Pagato') {
            $color = '#2E7D32'; // Verde
        } elseif ($is_overdue) {
            $color = '#ef4444'; // Rosso (Scaduto)
        }
        
        $title = $row['denominazione_ditta_concessionario'] . ' - ' . $row['tipo_sollecito'];

        $events[] = [
            'id'    => $row['id'],
            'title' => $title,
            'start' => $row['start'],
            'color' => $color,
            'extendedProps' => [ // Dati extra per la modale
                'importo' => number_format($row['importo_richiesto'], 2, ',', '.'),
                'stato' => $is_overdue ? 'SCADUTO' : $row['stato']
            ]
        ];
    }
    return $events;
}
