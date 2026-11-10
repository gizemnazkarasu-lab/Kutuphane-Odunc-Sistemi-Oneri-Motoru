<h2>Sistem İçi Bildirimlerim (Observer Deseni — FR-12)</h2>

<?php if (empty($notifications)): ?>
    <div class="card-box">Henüz sistem içi bildiriminiz bulunmamaktadır. Rezervasyon sırasındaki bir kitap iade edildiğinde burada bildirim oluşturulur.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Bildirim Mesajı</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $n): ?>
                <tr>
                    <td><?= htmlspecialchars($n->createdAt->format('d.m.Y H:i')) ?></td>
                    <td><strong><?= htmlspecialchars($n->message) ?></strong></td>
                    <td>
                        <?php if ($n->readAt === null): ?>
                            <span class="status-badge status-warn">Yeni / Okunmadı</span>
                        <?php else: ?>
                            <span class="status-badge status-ok">Okundu (<?= htmlspecialchars($n->readAt->format('d.m.Y H:i')) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn" href="?r=my-reservations">Rezervasyonuma Git</a>
                        <?php if ($n->readAt === null): ?>
                            <form method="POST" action="?r=notifications/read" style="display:inline;">
                                <input type="hidden" name="notification_id" value="<?= (int) $n->id ?>">
                                <button type="submit" class="btn btn-primary">Okundu İşaretle</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>