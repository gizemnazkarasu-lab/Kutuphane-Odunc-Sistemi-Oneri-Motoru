<div class="grid-2">
    <div class="card-box">
        <h3 style="margin-top:0;">Sistem Kuralları ve Ceza Politikası Ayarları (US-10)</h3>
        <form method="POST" action="?r=admin/settings">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                <div>
                    <label style="display:block;font-weight:600;">Ödünç Süresi (Gün)</label>
                    <input type="number" name="loan_duration_days" value="<?= (int) ($settings['loanDurationDays'] ?? 14) ?>" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;">Günlük Gecikme Cezası (TL)</label>
                    <input type="text" name="daily_fine_tl" value="<?= number_format(((int) ($settings['dailyFineRateInKurus'] ?? 500)) / 100, 2, '.', '') ?>" required>
                </div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="display:block;font-weight:600;">Ceza Üst Sınırı (TL)</label>
                    <input type="text" name="max_fine_tl" value="<?= number_format(((int) ($settings['maximumFineInKurus'] ?? 10000)) / 100, 2, '.', '') ?>" required>
                </div>
                <div>
                    <label style="display:block;font-weight:600;">Hafta Sonu Politikası (Strategy)</label>
                    <select name="fine_policy">
                        <option value="calendar" <?= ($settings['finePolicy'] ?? 'calendar') === 'calendar' ? 'selected' : '' ?>>
                            CalendarDayFinePolicy (Hafta sonu dahil)
                        </option>
                        <option value="weekday" <?= ($settings['finePolicy'] ?? 'calendar') === 'weekday' ? 'selected' : '' ?>>
                            WeekdayFinePolicy (Hafta sonu hariç)
                        </option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
        </form>
    </div>

    <div class="card-box">
        <h3 style="margin-top:0;">Veri Yedekleme ve Yeni Kullanıcı Oluşturma (US-09 / US-12)</h3>
        <form method="POST" action="?r=admin/backup" style="margin-bottom: 14px;">
            <button type="submit" class="btn btn-primary">JSON Veritabanı Yedeği Oluştur (US-12)</button>
            <?php if (!empty($backups)): ?>
                <span style="font-size:12.5px; margin-left:8px;">Son yedek: <code><?= htmlspecialchars($backups[0]) ?></code></span>
            <?php endif; ?>
        </form>

        <hr style="border:0; border-top:1px solid #cbd5e1; margin:12px 0;">

        <form method="POST" action="?r=admin/users/create">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; margin-bottom:8px;">
                <input type="text" name="name" placeholder="Ad Soyad" required>
                <input type="email" name="email" placeholder="E-posta" required>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap:8px;">
                <input type="password" name="password" placeholder="Şifre" required>
                <select name="role">
                    <option value="member">Üye (member)</option>
                    <option value="librarian">Kütüphaneci (librarian)</option>
                    <option value="admin">Sistem Yöneticisi (admin)</option>
                </select>
                <button type="submit" class="btn btn-primary">Hesap Ekle</button></div><div style="margin-top:8px;"><label style="font-size:13px;cursor:pointer;"><input type="checkbox" name="login_as_new" value="1" checked> Oluşturduktan sonra hemen bu yeni üyenin hesabıyla giriş yap</label>
            </div>
        </form>
    </div>
</div>

<h3>Kullanıcı Hesapları, Rol ve Yetki Yönetimi (US-05 / US-09 / FR-10)</h3>
<table>
    <thead>
        <tr>
            <th>Üye No</th>
            <th>Ad Soyad</th>
            <th>E-posta</th>
            <th>Rol / Yetki</th>
            <th>Hesap Durumu</th>
            <th>Kaydet</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <form method="POST" action="?r=admin/users/update">
                    <input type="hidden" name="target_user_id" value="<?= (int) $u->id ?>">
                    <td><code><?= htmlspecialchars($u->memberNumber) ?></code></td>
                    <td><strong><?= htmlspecialchars($u->name) ?></strong></td>
                    <td><?= htmlspecialchars($u->email) ?></td>
                    <td>
                        <select name="role">
                            <option value="member" <?= $u->role === 'member' ? 'selected' : '' ?>>Üye (member)</option>
                            <option value="librarian" <?= $u->role === 'librarian' ? 'selected' : '' ?>>Kütüphane Görevlisi (librarian)</option>
                            <option value="admin" <?= $u->role === 'admin' ? 'selected' : '' ?>>Sistem Yöneticisi (admin)</option>
                        </select>
                    </td>
                    <td>
                        <select name="active">
                            <option value="1" <?= $u->active ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= !$u->active ? 'selected' : '' ?>>Devre Dışı (Pasif)</option>
                        </select>
                    </td>
                    <td>
                        <button type="submit" class="btn">Güncelle</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Önemli İşlem Kayıtları / Audit Log (US-11)</h3>
<?php if (empty($auditLogs)): ?>
    <div class="card-box">Sistemde görüntülenecek işlem kaydı bulunamadı.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Kullanıcı</th>
                <th>İşlem Türü</th>
                <th>Açıklama</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($auditLogs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($log['createdAt'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($log['actorName'] ?? '')) ?></td>
                    <td><code><?= htmlspecialchars((string) ($log['action'] ?? '')) ?></code></td>
                    <td><?= htmlspecialchars((string) ($log['details'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>