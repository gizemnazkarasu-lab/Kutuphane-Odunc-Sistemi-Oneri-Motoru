<form method="GET" action="">
    <input type="hidden" name="r" value="catalog">
    <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 16px; align-items: end; margin-bottom: 20px;">
        <div>
            <label style="display:block;font-weight:600;margin-bottom:6px;">Kitap veya yazar ara</label>
            <input type="text" name="q" value="<?= htmlspecialchars($query ?? '') ?>" placeholder="Kitap adı, yazar veya ISBN...">
        </div>
        <div>
            <label style="display:block;font-weight:600;margin-bottom:6px;">Kategori</label>
            <select name="category_id">
                <option value="0">Tüm Kategoriler</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat->id ?>" <?= ((int) ($selectedCategoryId ?? 0) === $cat->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 9px 26px;">Ara</button>
        </div>
    </div>
</form>

<?php if (empty($catalog['items'])): ?>
    <div class="card-box" style="margin-top: 20px; font-weight: 600;">
        Sonuç alanı: Aramanıza uygun kitap bulunamadı.
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Kitap</th>
                <th>Yazar</th>
                <th>Kategori</th>
                <th>Müsait kopya</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($catalog['items'] as $row): ?>
                <?php $book = $row['book']; ?>
                <tr>
                    <td><strong><?= htmlspecialchars($book->title) ?></strong></td>
                    <td><?= htmlspecialchars($row['authors']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td>
                        <?php if ($row['availableCount'] > 0): ?>
                            <span class="status-badge status-ok"><?= (int) $row['availableCount'] ?></span>
                        <?php else: ?>
                            <span class="status-badge status-err">0 (Ödünçte / Rezervasyona Uygun)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn" href="?r=books/show&id=<?= (int) $book->id ?>">Detayı Gör</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
        <div>
            <?php if ($catalog['page'] > 1): ?>
                <a class="btn" href="?r=catalog&q=<?= urlencode((string) $query) ?>&category_id=<?= (int) $selectedCategoryId ?>&page=<?= (int) ($catalog['page'] - 1) ?>">Önceki</a>
            <?php else: ?>
                <span class="btn" style="opacity:0.4;cursor:default;">Önceki</span>
            <?php endif; ?>
        </div>
        <div style="font-size:14px;font-weight:600;">
            Sayfa <?= (int) $catalog['page'] ?> / <?= (int) $catalog['totalPages'] ?> (Toplam <?= (int) $catalog['total'] ?> kitap)
        </div>
        <div>
            <?php if ($catalog['page'] < $catalog['totalPages']): ?>
                <a class="btn" href="?r=catalog&q=<?= urlencode((string) $query) ?>&category_id=<?= (int) $selectedCategoryId ?>&page=<?= (int) ($catalog['page'] + 1) ?>">Sonraki</a>
            <?php else: ?>
                <span class="btn" style="opacity:0.4;cursor:default;">Sonraki</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>