<h2>Gecikme Cezalarım ve Ödemelerim (US-04 / FR-06)</h2>

<div class="card-box">
    <strong>Toplam Kalan Ceza Borcunuz:</strong>
    <span style="font-size: 18px; font-weight: 800; margin-left: 8px; color: <?= $totalRemainingInKurus > 0 ? '#991b1b' : '#166534' ?>;">
        <?= number_format($totalRemainingInKurus / 100, 2, ',', '.') ?> TL
    </span>
</div>

<?php if (empty($rows)): ?>
    <div class="card-box">Gecikme cezanız bulunmamaktadır.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Ceza #</th>
                <th>Kitap & Kopya</th>
                <th>Son Teslim Tarihi</th>
                <th>İade Tarihi</th>
                <th>Hesaplanan Ceza</th>
                <th>Ödenen</th>
                <th>Kalan Borç</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $fine = $row['fine'];
                $loan = $row['loan'];
                $book = $row['book'];
                $copy = $row['copy'];
                ?>
                <tr>
                    <td>#<?= (int) $fine->id ?></td>
                    <td><?= htmlspecialchars(($book ? $book->title : 'Kitap') . ' (' . ($copy ? $copy->inventoryCode : '-') . ')') ?></td>
                    <td><?= htmlspecialchars($loan->dueAt->format('d.m.Y')) ?></td>
                    <td><?= htmlspecialchars($loan->returnedAt?->format('d.m.Y') ?? '-') ?></td>
                    <td><?= number_format($fine->amountInKurus / 100, 2, ',', '.') ?> TL</td>
                    <td><?= number_format($row['paidInKurus'] / 100, 2, ',', '.') ?> TL</td>
                    <td><strong><?= number_format($row['remainingInKurus'] / 100, 2, ',', '.') ?> TL</strong></td>
                    <td>
                        <?php if ($row['remainingInKurus'] <= 0): ?>
                            <span class="status-badge status-ok">Ödendi</span>
                        <?php else: ?>
                            <span class="status-badge status-err">Ödeme Bekliyor</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>