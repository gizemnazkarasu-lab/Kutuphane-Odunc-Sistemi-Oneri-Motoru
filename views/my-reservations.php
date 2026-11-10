<h2>Rezervasyonlarım ve Bekleme Sırası Durumu</h2>

<?php if (empty($rows)): ?>
    <div class="card-box">
        Henüz bir rezervasyon kaydınız bulunmamaktadır. Ödünçte olan bir kitabın detay sayfasından bekleme sırasına katılabilirsiniz.
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Kitap</th>
                <th>Oluşturulma Zamanı</th>
                <th>Sıra Durumu</th>
                <th>Ayrılan Kopya</th>
                <th>Son Teslim Alma Zamanı (3 Gün)</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $res = $row['reservation'];
                $book = $row['book'];
                $copy = $row['copy'];
                $pos = $row['position'];

                $statusText = match ($res->status) {
                    'waiting' => 'Bekliyor',
                    'allocated' => 'Adınıza Ayrıldı (3 Gün)',
                    'completed' => 'Tamamlandı (Teslim Alındı)',
                    'expired' => 'Süresi Doldu (3 Gün İçinde Alınmadı)',
                    'cancelled' => 'İptal Edildi',
                    default => $res->status,
                };
                $badgeClass = match ($res->status) {
                    'allocated', 'completed' => 'status-ok',
                    'waiting' => 'status-warn',
                    default => 'status-err',
                };
                ?>
                <tr>
                    <td>
                        <strong><a href="?r=books/show&id=<?= (int) $res->bookId ?>"><?= htmlspecialchars($book ? $book->title : 'Kitap') ?></a></strong>
                    </td>
                    <td><?= htmlspecialchars($res->createdAt->format('d.m.Y H:i')) ?></td>
                    <td><?= $pos !== null ? "Sıra #{$pos}" : '—' ?></td>
                    <td><?= htmlspecialchars($copy ? $copy->inventoryCode : '—') ?></td>
                    <td><?= htmlspecialchars($res->expiresAt ? $res->expiresAt->format('d.m.Y H:i') : '—') ?></td>
                    <td><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                    <td>
                        <?php if ($res->status === 'allocated' && $res->allocatedCopyId !== null): ?>
                            <form method="POST" action="?r=loans/borrow" style="display:inline-block; margin-right:6px;">
                                <input type="hidden" name="user_id" value="<?= (int) $currentUser->id ?>">
                                <input type="hidden" name="copy_id" value="<?= (int) $res->allocatedCopyId ?>">
                                <input type="hidden" name="return_route" value="my-loans">
                                <button type="submit" class="btn btn-primary">Kitabı Teslim Al</button>
                            </form>
                        <?php endif; ?>

                        <?php if (in_array($res->status, ['waiting', 'allocated'], true)): ?>
                            <form method="POST" action="?r=reservations/cancel" style="display:inline-block;">
                                <input type="hidden" name="reservation_id" value="<?= (int) $res->id ?>">
                                <input type="hidden" name="return_route" value="my-reservations">
                                <button type="submit" class="btn btn-danger">İptal Et</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>