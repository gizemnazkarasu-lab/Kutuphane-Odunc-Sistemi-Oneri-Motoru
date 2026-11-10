<h2>İade Tarihi Geçmiş (Geciken) Kitaplar (US-07)</h2>
<?php if (empty($overdueLoans)): ?>
    <div class="card-box">Sistemde iade tarihi geçmiş herhangi bir kitap bulunmamaktadır.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Ödünç #</th>
                <th>Üye</th>
                <th>Kitap & Kopya</th>
                <th>Son Teslim Tarihi</th>
                <th>Gecikme Süresi</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($overdueLoans as $item): ?>
                <tr>
                    <td>#<?= (int) $item['loan']->id ?></td>
                    <td><?= htmlspecialchars($item['user'] ? ($item['user']->name . ' - ' . $item['user']->email) : 'Üye') ?></td>
                    <td><strong><?= htmlspecialchars($item['book'] ? $item['book']->title : 'Kitap') ?></strong> (<?= htmlspecialchars($item['copy'] ? $item['copy']->inventoryCode : '-') ?>)</td>
                    <td><?= htmlspecialchars($item['loan']->dueAt->format('d.m.Y')) ?></td>
                    <td><span class="status-badge status-err"><?= (int) $item['daysLate'] ?> gün gecikti</span></td>
                    <td><a class="btn" href="?r=librarian/loans">İade Ekranına Git</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="grid-2" style="margin-top: 24px;">
    <div>
        <h3>Üye Bilgileri Sorgulama ve Görüntüleme (US-06)</h3>
        <form method="GET" action="" style="display:flex; gap:8px; margin-bottom:12px;">
            <input type="hidden" name="r" value="librarian/reports">
            <input type="text" name="member_q" value="<?= htmlspecialchars($memberSearch ?? '') ?>" placeholder="Üye adı, e-posta veya UYE-0001...">
            <button type="submit" class="btn btn-primary">Üye Ara</button>
        </form>
        <?php if (empty($members)): ?>
            <div class="card-box">Aranan kriterlere uygun üye kaydı bulunamadı.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Üye No</th>
                        <th>Ad Soyad & E-posta</th>
                        <th>Aktif Ödünç</th>
                        <th>Toplam Ödünç</th>
                        <th>Rezervasyon</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($m['user']->memberNumber) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($m['user']->name) ?></strong><br>
                                <small><?= htmlspecialchars($m['user']->email) ?></small>
                            </td>
                            <td><?= (int) $m['activeLoans'] ?> / 5</td>
                            <td><?= (int) $m['totalLoans'] ?></td>
                            <td><?= (int) $m['reservations'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div>
        <h3>En Çok Ödünç Alınan Kitaplar</h3>
        <table>
            <thead>
                <tr>
                    <th>Kitap Adı</th>
                    <th>ISBN</th>
                    <th>Toplam Ödünç</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mostBorrowed as $mb): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($mb['bookTitle']) ?></strong></td>
                        <td><?= htmlspecialchars($mb['isbn']) ?></td>
                        <td><?= (int) $mb['borrowCount'] ?> kez</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>