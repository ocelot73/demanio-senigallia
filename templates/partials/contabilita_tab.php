<?php // /templates/partials/contabilita_tab.php ?>
<h3 class="section-title">Estratto Conto Annuale</h3>
<p class="subtitle">Riepilogo dei totali richiesti e versati come da registrazioni SID. Ultima sincronizzazione: <?= $data_sincronizzazione ?></p>
<div class="table-responsive">
    <table class="styled-table">
        <thead>
            <tr>
                <th>Anno</th>
                <th class="text-right">Richiesto da SID</th>
                <th class="text-right">Versato a SID</th>
                <th class="text-right">Conguaglio Anno Prec.</th>
                <th class="text-right">Saldo Finale</th>
                <th class="text-center">Dettagli</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($estrattoconto)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 2rem;">Nessun dato contabile trovato. Eseguire una sincronizzazione dalla pagina "Importa Dati SID" o "Sincronizza Pagamenti".</td></tr>
            <?php else: ?>
                <?php foreach ($estrattoconto as $riga): ?>
                <tr class="estrattoconto-row" data-id-canone="<?= $riga['id_canone_annuale'] ?>" data-anno="<?= $riga['anno_competenza'] ?>" data-saldo="<?= $riga['saldo_finale'] ?>">
                    <td><strong><?= $riga['anno_competenza'] ?></strong></td>
                    <td class="text-right">€ <?= number_format($riga['importo_richiesto_da_sid'], 2, ',', '.') ?></td>
                    <td class="text-right">€ <?= number_format($riga['importo_versato_da_sid'], 2, ',', '.') ?></td>
                    <td class="text-right <?= $riga['conguaglio_da_anno_prec'] < 0 ? 'saldo-negativo' : '' ?>">€ <?= number_format($riga['conguaglio_da_anno_prec'], 2, ',', '.') ?></td>
                    <td class="text-right <?= $riga['saldo_finale'] == 0 ? '' : ($riga['saldo_finale'] < 0 ? 'saldo-negativo' : 'saldo-positivo') ?>">
                        <strong>€ <?= number_format($riga['saldo_finale'], 2, ',', '.') ?></strong>
                    </td>
                    <td class="text-center"><i class="fas fa-chevron-down details-arrow"></i></td>
                </tr>
                <tr class="dettaglio-row" id="dettaglio-<?= $riga['id_canone_annuale'] ?>" style="display:none;"><td colspan="6"></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
