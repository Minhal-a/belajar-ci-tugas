<?php if (!empty($transactions)) : ?>
    <?php foreach ($transactions as $item) : ?>
        <!-- Status Modal Begin -->
        <div class="modal fade" id="statusModal-<?= $item['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Status Transaksi #<?= $item['id'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <?= form_open('pembelian/status/' . $item['id']) ?>
                    <?= csrf_field() ?>

                    <div class="modal-body">
                        <div class="mb-3">
                            <?= form_label('Status', 'status') ?>
                            <?= form_dropdown('status', [
                                '0' => 'Belum Selesai',
                                '1' => 'Sudah Selesai'
                            ], $item['status'], ['class' => 'form-control', 'id' => 'status']) ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <?= form_submit('submit', 'Simpan', ['class' => 'btn btn-primary']) ?>
                    </div>

                    <?= form_close() ?>
                </div>
            </div>
        </div>
        <!-- Status Modal End -->
    <?php endforeach; ?>
<?php endif; ?>