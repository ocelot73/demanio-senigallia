<?php // /templates/partials/modals.php ?>
<div class="modal-overlay" id="detailsModal">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 id="modalTitle">Dettagli SID</h2>
                <div id="modalSubtitle" class="modal-subtitle"></div>
            </div>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <nav class="modal-nav" id="modalNav"></nav>
            <div class="modal-content" id="modalContent"></div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 id="editTitle">Modifica Concessione</h2>
                <div id="editSubtitle" class="modal-subtitle"></div>
            </div>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-content" id="editModalContent">
            <div id="editAlert" style="display:none;"></div>
            <form id="editForm"></form>
        </div>
        <div class="modal-footer">
            <button class="btn" type="button" id="editCancelBtn">Annulla</button>
            <button class="btn" type="button" id="editSaveContinueBtn"><i class="fas fa-save"></i> Salva e continua</button>
            <button class="btn btn-primary" type="button" id="editSaveExitBtn"><i class="fas fa-check"></i> Salva ed esci</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="eventDetailsModal">
     <div class="modal-container" style="max-width: 600px; height: auto;">
        <div class="modal-header">
            <h2 id="eventDetailsTitle">Dettagli Scadenza</h2>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-content" id="eventDetailsContent">
            <!-- Contenuto popolato da JS -->
        </div>
    </div>
</div>
