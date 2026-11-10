<div class="grid-2">
    <div class="card-box">
        <h3 style="margin-top:0;">Ödünç Verme İşlemi</h3>
        <form method="POST" action="?r=loans/borrow">
            <input type="hidden" name="return_route" value="librarian/loans">
            <div style="margin-bottom: 12px;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Üye Seçiniz</label>
                <select name="user_id" required>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m->id ?>">
                            <?= htmlspecialchars($m->memberNumber . ' — ' . $m->name . ' (' . $m->role . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display:block;font-weight:600;margin-bottom:4px;">Müsait / Ayrılmış Fiziksel Kopya Seçiniz</label>
                <select name="copy_id" required>
                    <?php foreach ($borrowableCopies as $bc): ?>
                        <?php
                        $c = $bc['copy'];
                        $b = $bc['book'];
                        $au = $bc['allocatedUser'];
                        ?>
                        <option value="<?= (int) $c->id ?>">
                            <?= htmlspecialchars($c->inventoryCode . ' — ' . ($b ? $b->title : 'Kitap')) ?>
                            <?= $au ? ' [3 Gün Ayrıldı: ' . htmlspecialchars($au->name) . ']' : ' [Müsait]' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Ödünç Ver (14 Gün)</button>
        </form>
    </div>

    <div class="card-box">
        <h3 style="margin-top:0;">İade ve Otomatik Rezervasyon Ayırma Kuralları</h3>
        <ul style="margin:0; padding-left:18px; font-size:13.5px;">
            <li>İade alındığında gecikme varsa seçili ceza politikasına (Strategy) göre kuruş bazında ceza hesaplanır.</li>
            <li>İade edilen kitabın bekleme sırasında (Rezervasyon kuyruğu) üye varsa, kopya otomatik olarak <strong>sıradaki 1. üyeye 3 gün süreyle ayrılır</strong> (State: <code>WaitingState → AllocatedState</code>).</li>
            <li>İlgili üyeye anında <strong>sistem içi bildirim</strong> gönderilir (Observer: <code>EventDispatcher → ReservationNotificationListener</code>).</li>
        </ul>
    </div>
</div>

<h3>Aktif Ödünçteki Kitaplar (İade Alınabilir)</h3>
<table>
    <thead>
        <tr>
            <th>Ödünç #</th>
            <th>Üye</th>
            <th>Kitap & Envanter Kodu</th>
            <th>Ödünç Tarihi</th>
            <th>Son Teslim Tarihi</th>
            <th>Durum</th>
            <th>Rezervasyon Sırası Bekleyen</th>
            <th>İade İşlemi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($activeLoanRows as $row): ?>
            <?php
            $loan = $row['loan'];
            $member = $row['member'];
            $book = $row['book'];
            $copy = $row['copy'];
            $waitingUser = $row['waitingUser'];
            ?>
            <tr>
                <td>#<?= (int) $loan->id ?></td>
                <td><?= htmlspecialchars($member ? ($member->name . ' (' . $member->memberNumber . ')') : 'Üye') ?></td>
                <td>
                    <strong><?= htmlspecialchars($book ? $book->title : 'Kitap') ?></strong>
                    (<code><?= htmlspecialchars($copy ? $copy->inventoryCode : '-') ?></code>)
                </td>
                <td><?= htmlspecialchars($loan->borrowedAt->format('d.m.Y')) ?></td>
                <td><?= htmlspecialchars($loan->dueAt->format('d.m.Y')) ?></td>
                <td>
                    <?php if ($row['isOverdue']): ?>
                        <span class="status-badge status-err">Gecikmiş</span>
                    <?php else: ?>
                        <span class="status-badge status-ok">Aktif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($waitingUser !== null): ?>
                        <span class="status-badge status-warn">Sıradaki: <?= htmlspecialchars($waitingUser->name) ?></span>
                    <?php else: ?>
                        Bekleyen yok
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <form method="POST" action="?r=loans/return" style="display:inline;">
                            <input type="hidden" name="loan_id" value="<?= (int) $loan->id ?>">
                            <input type="hidden" name="return_route" value="librarian/loans">
                            <button type="submit" class="btn btn-primary">Normal İade Al</button>
                        </form>
                        <form method="POST" action="?r=loans/return" style="display:inline;">
                            <input type="hidden" name="loan_id" value="<?= (int) $loan->id ?>">
                            <input type="hidden" name="simulate_days_late" value="5">
                            <input type="hidden" name="return_route" value="librarian/loans">
                            <button type="submit" class="btn" title="Sunumda ceza hesabını göstermek için +5 gün gecikmeli iade simülasyonu">+5 Gün Gecikmeli İade (Demo)</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>