<div class="grid-2">
    <div class="card-box">
        <h3 style="margin-top:0;">Yeni Kitap ve Kopya Ekle (FR-08)</h3>
        <form method="POST" action="?r=librarian/books/store">
            <div style="margin-bottom:10px;">
                <label style="display:block;font-weight:600;">Kitap Adı</label>
                <input type="text" name="title" placeholder="Örn: Çalıkuşu" required>
            </div>
            <div style="margin-bottom:10px;">
                <label style="display:block;font-weight:600;">Yazar Adı</label>
                <input type="text" name="author_name" placeholder="Örn: Reşat Nuri Güntekin" required>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr 100px; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="display:block;font-weight:600;">ISBN</label>
                    <input type="text" name="isbn" placeholder="978-975-..." required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;">Kategori</label>
                    <select name="category_id">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-weight:600;">Kopya Sayısı</label>
                    <input type="number" name="initial_copies" value="2" min="1" max="10" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Kitabı Kataloğa Ekle</button>
        </form>
    </div>

    <div class="card-box">
        <h3 style="margin-top:0;">Mevcut Kitaba Fiziksel Kopya Ekle</h3>
        <form method="POST" action="?r=librarian/books/add-copy">
            <div style="margin-bottom:10px;">
                <label style="display:block;font-weight:600;">Kitap Seçin</label>
                <select name="book_id" required>
                    <?php foreach ($booksWithCopies as $d): ?>
                        <option value="<?= (int) $d['book']->id ?>"><?= htmlspecialchars($d['book']->title) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-weight:600;">Envanter Kodu</label>
                <input type="text" name="inventory_code" placeholder="Örn: K-025" required>
            </div>
            <button type="submit" class="btn btn-primary">Fiziksel Kopya Ekle</button>
        </form>
    </div>
</div>

<h3>Katalogdaki Kitaplar ve Kopyaları (US-08)</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Kitap</th>
            <th>Yazar</th>
            <th>Kategori</th>
            <th>ISBN</th>
            <th>Fiziksel Kopyalar</th>
            <th>İşlem</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($booksWithCopies as $d): ?>
            <?php $b = $d['book']; ?>
            <tr>
                <td>#<?= (int) $b->id ?></td>
                <td><strong><?= htmlspecialchars($b->title) ?></strong></td>
                <td><?= htmlspecialchars($d['authors']) ?></td>
                <td><?= htmlspecialchars($d['category']) ?></td>
                <td><?= htmlspecialchars($b->isbn) ?></td>
                <td>
                    <?php foreach ($d['copies'] as $c): ?>
                        <span class="status-badge <?= $c->status === 'available' ? 'status-ok' : ($c->status === 'allocated' ? 'status-warn' : 'status-err') ?>" style="margin:2px;">
                            <?= htmlspecialchars($c->inventoryCode) ?> (<?= htmlspecialchars($c->status) ?>)
                        </span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <form method="POST" action="?r=librarian/books/delete" onsubmit="return confirm('Bu kitabı katalogdan kaldırmak istediğinize emin misiniz?');">
                        <input type="hidden" name="book_id" value="<?= (int) $b->id ?>">
                        <button type="submit" class="btn btn-danger">Kaydı Kaldır</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>