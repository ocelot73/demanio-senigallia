<?php // /templates/partials/modals.php ?>

<!-- Modale dettaglio viste materializzate -->
<div id="detailModal" class="modal" style="display:none;">
    <div class="modal-content">
        <button class="modal-close" data-close="detailModal" title="Chiudi" type="button">&times;</button>
        <div class="modal-grid">
            <aside class="modal-nav">
                <ul id="detailMenu"></ul>
            </aside>
            <section class="modal-body">
                <div id="detailHeader" class="detail-header"></div>
                <div id="detailContent" class="detail-content"></div>
            </section>
        </div>
    </div>
</div>

<!-- Modale modifica concessione -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content modal-xl">
        <button class="modal-close" data-close="editModal" title="Chiudi" type="button">&times;</button>
        <header class="modal-header">
            <h3><i class="fa-regular fa-pen-to-square"></i> Modifica Concessione</h3>
            <small id="editSubtitle" class="subtitle"></small>
        </header>
        <div id="editAccordion" class="accordion"></div>
        <footer class="modal-footer">
            <button id="btnSaveEdit" class="btn btn-primary" type="button"><i class="fa-solid fa-floppy-disk"></i> Salva</button>
            <button class="btn" data-close="editModal" type="button"><i class="fa-solid fa-xmark"></i> Annulla</button>
        </footer>
    </div>
</div>
