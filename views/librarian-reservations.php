<div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
    <form method="GET" action="" style="display:flex; gap:12px; align-items:flex-end; flex:1; min-width:300px;">
        <input type="hidden" name="r" value="librarian/reservations">
        <div style="flex:1;">
            <label style="display:block;font-weight:600;margin-bottom:6px;">Kitap seç</label>
            <select name="book_id">
                <option value="0">Tüm Kitapların Rezervasyon Kuyruğu</option>
                <?php foreach ($books as $b): ?>
                    <option value="<?= (int) $b->id ?>" <?= ((int) $selectedBookId === $b->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b->title) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Sırayı Görüntüle</button>
    </form>

    <form method="POST" action="?r=librarian/reservations/expire">
        <input type="hidden" name="simulate_days_forward" value="4">
        <button type="submit" class="btn" title="3 gün içinde teslim alınmayan ayrılmış kopyaları düşürüp sıradaki üyeye devreder">
            3 Gün Süresi Dolan Ayırmaları Sıradaki Üyeye Aktar (Simülasyon)
        </button>
    </form>
</div>

<?php if (empty($activeQueueRows)): ?>
    <div class="card-box">
        Mesaj alanı: Bu kitap için bekleyen rezervasyon yok.
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Sıra</th>
                <th>Kitap</th>
                <th>Üye</th>
                <th>Kopya</th>
                <th>Son alma zamanı</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activeQueueRows as $row): ?>
                <?php
                $res = $row['reservation'];
                $book = $row['book'];
                $member = $row['member'];
                $copy = $row['copy'];
                ?>
                <tr>
                    <td><strong><?= (int) $row['rank'] ?></strong></td>
                    <td><?= htmlspecialchars($book ? $book->title : 'Kitap') ?></td>
                    <td><?= htmlspecialchars($member ? ($member->name . ' (' . $member->memberNumber . ')') : 'Üye') ?></td>
                    <td><?= htmlspecialchars($copy ? $copy->inventoryCode : '—') ?></td>
                    <td><?= htmlspecialchars($res->expiresAt ? $res->expiresAt->format('d.m.Y H:i') : '—') ?></td>
                    <td>
                        <?php if ($res->status === 'allocated'): ?>
                            <span class="status-badge status-ok">Ayrıldı (3 Gün)</span>
                        <?php else: ?>
                            <span class="status-badge status-warn">Bekliyor</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($res->status === 'allocated' && $res->allocatedCopyId !== null && $member !== null): ?>
                            <form method="POST" action="?r=loans/borrow" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= (int) $member->id ?>">
                                <input type="hidden" name="copy_id" value="<?= (int) $res->allocatedCopyId ?>">
                                <input type="hidden" name="return_route" value="librarian/reservations">
                                <button type="submit" class="btn btn-primary">Üyeye Teslim Et (Ödünç Ver)</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="?r=reservations/cancel" style="display:inline;">
                            <input type="hidden" name="reservation_id" value="<?= (int) $res->id ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $res->userId ?>">
                            <input type="hidden" name="return_route" value="librarian/reservations">
                            <button type="submit" class="btn btn-danger">İptal</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p style="font-weight: 600;">Üç gün içinde alınmazsa kopya sıradaki uygun üyeye ayrılır.</p>