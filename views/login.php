<div style="max-width: 440px; margin: 12px auto 24px auto;">
    <div class="card-box" style="padding: 36px 38px; background-color: var(--sand-light);">
        <h2 style="margin: 0 0 24px 0; text-align: center; font-size: 30px;">sisteme giriş</h2>

        <form method="POST" action="?r=login">
            <div style="margin-bottom: 22px;">
                <label>kullanıcı adı, üye no veya e-posta</label>
                <input type="text" name="credential" placeholder="UYE-0001 veya gulsum@kutuphane.edu.tr" required>
            </div>

            <div style="margin-bottom: 28px;">
                <label>şifre</label>
                <input type="password" name="password" placeholder="Şifrenizi giriniz" required>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn btn-primary" style="min-width: 160px;">giriş yap</button>
            </div>
        </form>

        <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid rgba(90, 88, 49, 0.25); text-align: center;">
            <p style="margin: 0 0 12px 0; font-size: 13.5px; color: var(--moss);">Hesabınız yok mu?</p>
            <a href="?r=register" class="btn">kaydol</a>
        </div>
    </div>

    <div class="card-box" style="font-size: 13px;">
        <strong style="display:block; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.6px; color:var(--moss);">
            Kayıtlı Kullanıcı Hesapları (Şifre: 123456)
        </strong>
        <ul style="margin: 0; padding-left: 18px;">
            <?php foreach ($demoUsers as $u): ?>
                <li style="margin-bottom: 6px;">
                    <code><?= htmlspecialchars($u->memberNumber) ?></code>
                    <strong><?= htmlspecialchars($u->name) ?></strong>
                    (<?= htmlspecialchars($u->email) ?> &mdash; <?= htmlspecialchars($u->role) ?>)
                    <a href="?r=auth/quick-switch&user_id=<?= (int) $u->id ?>" style="margin-left:6px; font-weight:600;">[Giriş]</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>