<div style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">
    Aktif ödünç: <?= (int) $activeCount ?> / 5 &nbsp;&nbsp;|&nbsp;&nbsp; Aktif kayıtlar / Geçmiş
</div>

<h3>Aktif ÖdünçAldığım Kitaplar</h3>
<?php if (empty($activeLoans)): ?>
    <div class="card-box">Şu anda üzerinizde aktif ödünç kitap bulunmamaktadır. <a href="?r=catalog">Katalogdan kitap ödünç alabilirsiniz.</a></div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Kitap</th>
                <th>Kopya Kodu</th>
                <th>Teslim tarihi</th>
                <th>Durum</th>
                <th>Uzatma</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activeLoans as $item): ?>
                <?php
                $loan = $item['loan'];
                $book = $item['book'];
                $copy = $item['copy'];
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($book ? $book->title : 'Kitap') ?></strong>
                    </td>
                    <td><?= htmlspecialchars($copy ? $copy->inventoryCode : '-') ?></td>
                    <td><?= htmlspecialchars($loan->dueAt->format('d.m.Y')) ?></td>
                    <td>
                        <?php if ($item['isOverdue']): ?>
                            <span class="status-badge status-err">Gecikmiş</span>
                        <?php else: ?>
                            <span class="status-badge status-ok">Aktif</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $loan->renewalCount ?> / 1</td>
                    <td>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                            <?php if ($item['canRenew']): ?>
                                <form method="POST" action="?r=loans/renew" style="display:inline;">
                                    <input type="hidden" name="loan_id" value="<?= (int) $loan->id ?>">
                                    <input type="hidden" name="return_route" value="my-loans">
                                    <button type="submit" class="btn">Süreyi Uzat (+14 Gün)</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#64748b;font-weight:600;font-size:13px;">
                                    Uzatılamaz
                                    <?php if ($loan->renewalCount >= 1): ?>
                                        (Hak kullanıldı)
                                    <?php elseif ($item['isOverdue']): ?>
                                        (Gecikmiş)
                                    <?php elseif ($item['hasWaitingQueue']): ?>
                                        (Sırada üye var)
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>

                            <form method="POST" action="?r=loans/return" style="display:inline;">
                                <input type="hidden" name="loan_id" value="<?= (int) $loan->id ?>">
                                <input type="hidden" name="return_route" value="my-loans">
                                <button type="submit" class="btn btn-primary">İade Et</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p style="font-weight: 600; color: #334155;">
    Not: Bir kitap en fazla 1 kez (14 gün) uzatılabilir. İade edildiğinde sırada bekleyen üye varsa kopya otomatik olarak sıradaki ilk üyeye 3 günlüğüne ayrılır.
</p>

<h3 style="margin-top: 28px;">Geçmiş Ödünç Kayıtlarım</h3>
<?php if (empty($historyLoans)): ?>
    <div class="card-box">Geçmiş ödünç kaydınız bulunmamaktadır.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Kitap</th>
                <th>Kopya</th>
                <th>Ödünç Tarihi</th>
                <th>Son Teslim</th>
                <th>İade Tarihi</th>
                <th>Oluşan Ceza</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historyLoans as $item): ?>
                <?php
                $loan = $item['loan'];
                $book = $item['book'];
                $copy = $item['copy'];
                $fine = $item['fine'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($book ? $book->title : 'Kitap') ?></td>
                    <td><?= htmlspecialchars($copy ? $copy->inventoryCode : '-') ?></td>
                    <td><?= htmlspecialchars($loan->borrowedAt->format('d.m.Y')) ?></td>
                    <td><?= htmlspecialchars($loan->dueAt->format('d.m.Y')) ?></td>
                    <td><?= htmlspecialchars($loan->returnedAt?->format('d.m.Y H:i') ?? '-') ?></td>
                    <td>
                        <?php if ($fine !== null): ?>
                            <span class="status-badge status-err"><?= number_format($fine->amountInKurus / 100, 2, ',', '.') ?> TL</span>
                        <?php else: ?>
                            0,00 TL
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>