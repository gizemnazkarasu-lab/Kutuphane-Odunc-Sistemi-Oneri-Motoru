<h2>Ceza ve Ödeme Kaydı Yönetimi</h2>

<?php if (empty($rows)): ?>
    <div class="card-box">Sistemde kayıtlı gecikme cezası bulunmamaktadır.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Ceza #</th>
                <th>Üye</th>
                <th>Kitap & Kopya</th>
                <th>Toplam Ceza</th>
                <th>Ödenen</th>
                <th>Kalan Borç</th>
                <th>Tahsilat Kaydı Oluştur</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $fine = $row['fine'];
                $member = $row['member'];
                $book = $row['book'];
                $copy = $row['copy'];
                ?>
                <tr>
                    <td>#<?= (int) $fine->id ?></td>
                    <td><?= htmlspecialchars($member ? ($member->name . ' (' . $member->memberNumber . ')') : 'Üye') ?></td>
                    <td><?= htmlspecialchars(($book ? $book->title : 'Kitap') . ' (' . ($copy ? $copy->inventoryCode : '-') . ')') ?></td>
                    <td><?= number_format($fine->amountInKurus / 100, 2, ',', '.') ?> TL</td>
                    <td><?= number_format($row['paidInKurus'] / 100, 2, ',', '.') ?> TL</td>
                    <td><strong><?= number_format($row['remainingInKurus'] / 100, 2, ',', '.') ?> TL</strong></td>
                    <td>
                        <?php if ($row['remainingInKurus'] > 0): ?>
                            <form method="POST" action="?r=librarian/fines/pay" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="fine_id" value="<?= (int) $fine->id ?>">
                                <input type="text" name="amount_tl" value="<?= number_format($row['remainingInKurus'] / 100, 2, '.', '') ?>" style="width:100px;">
                                <button type="submit" class="btn btn-primary">Ödeme Kaydet (TL)</button>
                            </form>
                        <?php else: ?>
                            <span class="status-badge status-ok">Tamamı Ödendi</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>