<div style="max-width: 440px; margin: 12px auto 24px auto;">
    <div class="card-box" style="padding: 36px 38px; background-color: var(--sand-light);">
        <h2 style="margin: 0 0 8px 0; text-align: center; font-size: 30px;">yeni üye kaydı</h2>
        <p style="text-align: center; color: var(--moss); margin: 0 0 24px 0; font-size: 13.5px;">
            Kayıt tamamlandığında otomatik olarak kendi hesabınızla oturum açılır.
        </p>

        <form method="POST" action="?r=register">
            <div style="margin-bottom: 20px;">
                <label>ad soyad</label>
                <input type="text" name="name" placeholder="Adınız ve Soyadınız" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label>e-posta adresi</label>
                <input type="email" name="email" placeholder="ornek@kutuphane.edu.tr" required>
            </div>
            <div style="margin-bottom: 28px;">
                <label>şifre</label>
                <input type="password" name="password" placeholder="En az 4 karakter" required>
            </div>
            <div style="text-align: center;">
                <button type="submit" class="btn btn-primary" style="min-width: 180px;">kaydı tamamla</button>
            </div>
        </form>

        <div style="margin-top: 22px; text-align: center;">
            <a href="?r=login" style="font-size: 13px;">Giriş ekranına dön</a>
        </div>
    </div>
</div>