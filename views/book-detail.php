<?php
$book = $details['book'];
$availableCopies = $details['availableCopies'];
$availableCount = $details['availableCount'];
?>

<div style="display: flex; gap: 32px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 28px;">
    <div style="width: 165px; height: 225px; background-color: var(--cherry-blossom); border: 1.5px solid var(--burgundy); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 16px;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; color: var(--burgundy); margin-bottom: 10px; border-bottom: 1px solid var(--burgundy); padding-bottom: 4px;">
            Kütüphane Nüshası
        </div>
        <div style="font-family: 'Cormorant Garamond', Georgia, serif; font-size: 20px; font-weight: 700; color: var(--burgundy); line-height: 1.2;">
            <?= htmlspecialchars($book->title) ?>
        </div>
        <div style="font-size: 12px; color: var(--moss); margin-top: 10px;">
            <?= htmlspecialchars($details['authors']) ?>
        </div>
    </div>

    <div style="flex: 1; min-width: 280px;">
        <h2 style="margin: 0 0 12px 0; font-size: 28px;"><?= htmlspecialchars($book->title) ?></h2>
        <p style="margin: 5px 0;"><strong>Yazar:</strong> <?= htmlspecialchars($details['authors']) ?></p>
        <p style="margin: 5px 0;"><strong>Kategori:</strong> <?= htmlspecialchars($details['category']) ?></p>
        <p style="margin: 5px 0;"><strong>ISBN:</strong> <?= htmlspecialchars($book->isbn) ?></p>
        <p style="margin: 5px 0 18px 0;"><strong>Müsait kopya:</strong> <?= (int) $availableCount ?></p>

        <?php if ($availableCount > 0): ?>
            <?php $firstAvailableCopy = $availableCopies[0]; ?>
            <form method="POST" action="?r=loans/borrow" style="display:inline-block; margin-right: 10px;">
                <input type="hidden" name="user_id" value="<?= (int) $currentUser->id ?>">
                <input type="hidden" name="copy_id" value="<?= (int) $firstAvailableCopy->id ?>">
                <input type="hidden" name="return_route" value="books/show&id=<?= (int) $book->id ?>">
                <button type="submit" class="btn btn-primary">
                    Uygun Kopyayı Ödünç Al (<?= htmlspecialchars($firstAvailableCopy->inventoryCode) ?>)
                </button>
            </form>
        <?php else: ?>
            <?php if ($myReservation === null): ?>
                <form method="POST" action="?r=reservations/create" style="display:inline-block;">
                    <input type="hidden" name="book_id" value="<?= (int) $book->id ?>">
                    <input type="hidden" name="return_route" value="books/show&id=<?= (int) $book->id ?>">
                    <button type="submit" class="btn btn-primary">Rezervasyon Oluştur</button>
                </form>
            <?php elseif ($myReservation->status === 'allocated' && $myReservation->allocatedCopyId !== null): ?>
                <div class="card-box" style="background-color: var(--cherry-light); border-color: var(--burgundy); margin-top: 8px;">
                    <strong>Sıra Sizde:</strong> Bu kitabın bir kopyası <strong><?= htmlspecialchars($myReservation->expiresAt?->format('d.m.Y H:i') ?? '') ?></strong> tarihine kadar (3 gün) sizin için ayrılmıştır.
                    <form method="POST" action="?r=loans/borrow" style="margin-top: 10px;">
                        <input type="hidden" name="user_id" value="<?= (int) $currentUser->id ?>">
                        <input type="hidden" name="copy_id" value="<?= (int) $myReservation->allocatedCopyId ?>">
                        <input type="hidden" name="return_route" value="my-loans">
                        <button type="submit" class="btn btn-primary">Ayrılan Kopyayı Teslim Al</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: 16px; font-weight: 500; color: var(--moss);">
            <?php if ($myReservation !== null): ?>
                Mesaj alanı: Rezervasyon sıranız <?= (int) $myQueuePosition ?>. / Zaten sıradasınız.
                (Durum: <strong><?= $myReservation->status === 'allocated' ? 'Adınıza 3 Günlüğüne Ayrıldı' : 'Bekliyor' ?></strong>)
            <?php elseif ($availableCount === 0): ?>
                Mesaj alanı: Müsait kopya bulunmamaktadır. Rezervasyon oluşturarak bekleme sırasına girebilirsiniz.
            <?php else: ?>
                Mesaj alanı: Kitap ödünç almaya uygundur (14 gün süre, 1 kez uzatma hakkı).
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card-box">
    <strong>Fiziksel Kopyalar ve Durumları:</strong>
    <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
        <?php foreach ($details['copies'] as $copy): ?>
            <?php
            $statusLabel = match ($copy->status) {
                'available' => 'Müsait',
                'allocated' => 'Rezervasyona Ayrıldı (3 Gün)',
                default => 'Ödünçte',
            };
            $badgeClass = match ($copy->status) {
                'available' => 'status-ok',
                'allocated' => 'status-warn',
                default => 'status-err',
            };
            ?>
            <span class="status-badge <?= $badgeClass ?>">
                <?= htmlspecialchars($copy->inventoryCode) ?> &mdash; <?= $statusLabel ?>
            </span>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($bookQueue)): ?>
        <div style="margin-top: 14px; font-size: 13.5px; color: var(--burgundy);">
            <strong>Rezervasyon Sırası (<?= count($bookQueue) ?> üye):</strong>
            <?php foreach ($bookQueue as $idx => $qItem): ?>
                <span style="margin-right: 14px;">
                    <?= ($idx + 1) ?>. <?= htmlspecialchars($qItem['user'] ? $qItem['user']->name : 'Üye') ?>
                    (<?= $qItem['reservation']->status === 'allocated' ? 'Kopya Ayrıldı' : 'Bekliyor' ?>)
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<h3 style="margin-top: 32px; margin-bottom: 8px; font-size: 24px;">Bunu okuyanlar şunu da okudu</h3>
<p style="margin-top: 0; font-size: 13.5px; color: var(--moss);">
    Bu kitabı ödünç alan benzersiz üyelerin okuduğu diğer kitaplar ortak ödünç sayısına göre sıralanmaktadır:
</p>

<?php if (empty($recommendations)): ?>
    <div class="card-box">Bu kitap için henüz ortak ödünç geçmişi bulunmamaktadır.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Önerilen kitap</th>
                <th>Yazar</th>
                <th>Ortak okur sayısı</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recommendations as $rec): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($rec['book']->title) ?></strong></td>
                    <td><?= htmlspecialchars($rec['authors']) ?></td>
                    <td><span class="status-badge status-ok"><?= (int) $rec['sharedReaderCount'] ?> ortak okur</span></td>
                    <td>
                        <a class="btn" href="?r=books/show&id=<?= (int) $rec['book']->id ?>">Detayı Gör</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>