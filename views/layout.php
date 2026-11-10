<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Kütüphane Ödünç Sistemi') ?> | Kütüphane Ödünç Sistemi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --burgundy: #4A0E17;
            --burgundy-soft: #681D29;
            --cherry-blossom: #E2B6B8;
            --cherry-light: #F2DADC;
            --moss: #5A5831;
            --moss-muted: #848157;
            --sky-blue: #BBC8CE;
            --sky-light: #D9E2E6;
            --sand: #ECE4DA;
            --sand-light: #F6F0E6;
            --sand-card: #FAF6EE;
            --ink: #2C1810;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--sand);
            color: var(--burgundy);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        h1, h2, h3, h4, .brand-logo, .editorial-heading {
            font-family: 'Cormorant Garamond', Georgia, serif;
            color: var(--burgundy);
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* 1. Üst Moss Yeşili İnce Duyuru ve Rol Çubuğu (2. görseldeki en üst şerit) */
        .top-moss-bar {
            background-color: var(--moss);
            color: var(--sand-light);
            padding: 8px 28px;
            font-size: 12.5px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            letter-spacing: 0.3px;
        }
        .top-moss-bar .role-list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }
        .top-moss-bar .role-label {
            color: var(--cherry-blossom);
            font-weight: 600;
            margin-right: 4px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.8px;
        }
        .role-pill {
            color: var(--sand-light);
            text-decoration: none;
            padding: 3px 10px;
            border: 1px solid rgba(236, 228, 218, 0.35);
            font-size: 12px;
            transition: all 0.2s ease;
            background: transparent;
            cursor: pointer;
        }
        .role-pill:hover, .role-pill.active {
            background-color: var(--cherry-blossom);
            color: var(--burgundy);
            border-color: var(--cherry-blossom);
            font-weight: 600;
        }
        .role-pill.reset-btn {
            border-color: var(--cherry-blossom);
            color: var(--cherry-blossom);
        }
        .role-pill.reset-btn:hover {
            background-color: var(--burgundy);
            color: var(--sand-light);
            border-color: var(--burgundy);
        }

        /* 2. Ana Krem (Sand) Üst Menü Çubuğu (2. görseldeki orta navigasyon) */
        .site-header {
            background-color: var(--sand-light);
            padding: 20px 42px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .brand-logo {
            font-size: 30px;
            font-weight: 700;
            color: var(--burgundy);
            text-decoration: none;
            line-height: 1.1;
            letter-spacing: -0.2px;
        }
        .brand-sub {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: var(--moss);
            margin-top: 3px;
            font-weight: 600;
        }
        .main-nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 22px;
        }
        .main-nav a {
            text-decoration: none;
            color: var(--burgundy);
            font-size: 14px;
            font-weight: 500;
            padding-bottom: 3px;
            border-bottom: 1.5px solid transparent;
            transition: all 0.2s ease;
        }
        .main-nav a:hover, .main-nav a.active {
            border-bottom-color: var(--burgundy);
            color: var(--burgundy);
        }
        .main-nav a.staff-link {
            color: var(--moss);
            font-weight: 600;
        }
        .main-nav a.staff-link:hover {
            border-bottom-color: var(--moss);
        }
        .header-user {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 13px;
            color: var(--moss);
        }
        .header-user strong {
            color: var(--burgundy);
        }
        .notif-count {
            display: inline-block;
            background-color: var(--burgundy);
            color: var(--sand-light);
            font-size: 11px;
            padding: 1px 6px;
            margin-left: 4px;
            font-weight: 600;
        }

        /* 3. İkinci görseldeki Burgundy & Sky Blue Şerit Sınır (Trim Border) */
        .trim-border {
            height: 9px;
            width: 100%;
            background: repeating-linear-gradient(
                90deg,
                var(--burgundy) 0px,
                var(--burgundy) 14px,
                var(--sand-light) 14px,
                var(--sand-light) 22px,
                var(--sky-blue) 22px,
                var(--sky-blue) 36px,
                var(--sand-light) 36px,
                var(--sand-light) 44px
            );
            border-top: 1px solid rgba(74, 14, 23, 0.18);
            border-bottom: 1px solid rgba(74, 14, 23, 0.18);
        }

        /* 4. İkinci görseldeki dikey çizgili (Moss / Sky Blue / Sand) arka plan tuvali ve ortadaki Krem Kart */
        .striped-stage {
            flex: 1;
            padding: 44px 24px 60px 24px;
            background: repeating-linear-gradient(
                90deg,
                #8C8861 0px,
                #8C8861 48px,
                var(--sand) 48px,
                var(--sand) 64px,
                var(--sky-blue) 64px,
                var(--sky-blue) 88px,
                var(--sand) 88px,
                var(--sand) 104px
            );
        }
        .cream-sheet {
            max-width: 1080px;
            margin: 0 auto;
            background-color: var(--sand-light);
            border: 1px solid rgba(90, 88, 49, 0.35);
            box-shadow: 0 18px 40px rgba(44, 24, 16, 0.12);
            padding: 40px 46px;
        }
        .sheet-header {
            border-bottom: 1px solid rgba(74, 14, 23, 0.2);
            padding-bottom: 16px;
            margin-bottom: 28px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
        }
        .sheet-title {
            margin: 0;
            font-size: 32px;
            color: var(--burgundy);
        }
        .sheet-meta {
            font-size: 13px;
            color: var(--moss);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* Bildirim Kutuları */
        .alert {
            padding: 14px 18px;
            margin-bottom: 24px;
            border: 1px solid var(--burgundy);
            font-size: 14px;
            font-weight: 500;
        }
        .alert-success {
            background-color: var(--cherry-light);
            color: var(--burgundy);
            border-color: var(--burgundy);
        }
        .alert-error {
            background-color: var(--burgundy);
            color: var(--sand-light);
            border-color: var(--burgundy);
        }

        /* Tablolar */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0 26px 0;
            background-color: var(--sand-card);
            border: 1px solid rgba(74, 14, 23, 0.25);
        }
        th, td {
            border-bottom: 1px solid rgba(74, 14, 23, 0.15);
            padding: 13px 16px;
            text-align: left;
            font-size: 14px;
            color: var(--burgundy);
        }
        th {
            background-color: var(--cherry-blossom);
            color: var(--burgundy);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.3px;
            border-bottom: 1.5px solid var(--burgundy);
        }
        tr:hover td {
            background-color: rgba(226, 182, 184, 0.22);
        }

        /* İkinci görseldeki çizgili form alanları ve çerçeveli butonlar */
        label {
            display: block;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--moss);
            margin-bottom: 6px;
            font-weight: 600;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
            width: 100%;
            padding: 9px 4px;
            background: transparent;
            border: none;
            border-bottom: 1.5px solid var(--moss);
            color: var(--burgundy);
            font-family: 'DM Sans', sans-serif;
            font-size: 14.5px;
            transition: border-color 0.2s ease;
            border-radius: 0;
        }
        input:focus, select:focus {
            outline: none;
            border-bottom-color: var(--burgundy);
        }

        .btn {
            display: inline-block;
            padding: 8px 20px;
            border: 1.5px solid var(--burgundy);
            background-color: transparent;
            color: var(--burgundy);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.4px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn:hover {
            background-color: var(--cherry-blossom);
            color: var(--burgundy);
        }
        .btn-primary {
            background-color: var(--burgundy);
            color: var(--sand-light);
            border-color: var(--burgundy);
        }
        .btn-primary:hover {
            background-color: var(--moss);
            border-color: var(--moss);
            color: var(--sand-light);
        }
        .btn-danger {
            border-color: var(--burgundy);
            background-color: var(--cherry-light);
            color: var(--burgundy);
        }
        .btn-danger:hover {
            background-color: var(--burgundy);
            color: var(--sand-light);
        }

        /* Durum Etiketleri (Paletten renklerle) */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
            border: 1px solid transparent;
        }
        .status-ok {
            background-color: var(--moss);
            color: var(--sand-light);
        }
        .status-warn {
            background-color: var(--sky-blue);
            color: var(--burgundy);
            border-color: var(--moss);
        }
        .status-err {
            background-color: var(--cherry-blossom);
            color: var(--burgundy);
            border-color: var(--burgundy);
        }

        .card-box {
            background-color: var(--sand-card);
            border: 1px solid rgba(90, 88, 49, 0.35);
            padding: 22px 24px;
            margin-bottom: 22px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12.5px;
            background: rgba(187, 200, 206, 0.35);
            padding: 2px 6px;
            color: var(--burgundy);
        }
        a {
            color: var(--burgundy);
        }
        @media (max-width: 768px) {
            .site-header { padding: 16px 20px; }
            .cream-sheet { padding: 24px 20px; }
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Üst Moss Yeşili Şerit: Hızlı Hesap Geçişi ve Demo Sıfırlama -->
<div class="top-moss-bar">
    <div class="role-list">
        <span class="role-label">Hızlı Hesap Geçişi:</span>
        <?php foreach (($allUsersForBar ?? []) as $uBar): ?>
            <?php if (!$uBar->active) continue; ?>
            <?php
            $rTag = match ($uBar->role) {
                'librarian' => 'Görevli',
                'admin' => 'Yönetici',
                default => 'Üye',
            };
            ?>
            <a class="role-pill <?= ($currentUser && $currentUser->id === $uBar->id) ? 'active' : '' ?>"
               href="?r=auth/quick-switch&user_id=<?= (int) $uBar->id ?>">
                <?= htmlspecialchars($uBar->name) ?> (<?= htmlspecialchars($rTag) ?>)
            </a>
        <?php endforeach; ?>
    </div>
    <div>
        <form method="POST" action="?r=admin/reset-demo" style="display:inline;" onsubmit="return confirm('Tüm JSON verileri ilk başlangıç durumuna sıfırlansın mı?');">
            <button type="submit" class="role-pill reset-btn">Verileri Sıfırla</button>
        </form>
    </div>
</div>

<!-- Ana Krem Başlık ve Navigasyon (2. Görseldeki Düzen) -->
<header class="site-header">
    <div>
        <a href="?r=catalog" class="brand-logo">kütüphane &amp; öneri</a>
        <span class="brand-sub">Ödünç Sistemi ve Öneri Motoru</span>
    </div>

    <?php if ($currentUser !== null): ?>
        <nav class="main-nav">
            <a href="?r=catalog">katalog</a>
            <a href="?r=my-loans">ödünçlerim</a>
            <a href="?r=my-reservations">rezervasyonlarım</a>
            <a href="?r=my-fines">cezalarım</a>
            <a href="?r=notifications">
                bildirimlerim
                <?php if (($unreadNotificationCount ?? 0) > 0): ?>
                    <span class="notif-count"><?= (int) $unreadNotificationCount ?></span>
                <?php endif; ?>
            </a>

            <?php if (in_array($currentUser->role, ['librarian', 'admin'], true)): ?>
                <a href="?r=librarian/loans" class="staff-link">ödünç &amp; iade</a>
                <a href="?r=librarian/reservations" class="staff-link">rezervasyon sırası</a>
                <a href="?r=librarian/books" class="staff-link">kitap yönetimi</a>
                <a href="?r=librarian/fines" class="staff-link">ceza tahsilat</a>
                <a href="?r=librarian/reports" class="staff-link">raporlar</a>
            <?php endif; ?>

            <?php if ($currentUser->role === 'admin' || $currentUser->role === 'librarian'): ?>
                <a href="?r=admin" class="staff-link">yönetici</a>
            <?php endif; ?>
        </nav>

        <div class="header-user">
            <span>
                <strong><?= htmlspecialchars($currentUser->name) ?></strong>
                (<?= htmlspecialchars($currentUser->memberNumber) ?>)
            </span>
            <a href="?r=logout" class="btn" style="padding: 5px 14px; font-size: 12px;">çıkış</a>
        </div>
    <?php else: ?>
        <nav class="main-nav">
            <a href="?r=login">giriş yap</a>
            <a href="?r=register">kaydol</a>
        </nav>
    <?php endif; ?>
</header>

<div class="trim-border"></div>

<!-- Dikey Çizgili Sahne ve Ortadaki Krem Sayfa Kartı -->
<div class="striped-stage">
    <main class="cream-sheet">
        <div class="sheet-header">
            <h1 class="sheet-title"><?= htmlspecialchars($pageTitle ?? 'Kütüphane Ödünç Sistemi') ?></h1>
            <div class="sheet-meta">
                <?php if ($currentUser !== null): ?>
                    <?php
                    $roleText = match ($currentUser->role) {
                        'librarian' => 'Kütüphane Görevlisi',
                        'admin' => 'Sistem Yöneticisi',
                        default => 'Üye Paneli',
                    };
                    ?>
                    <?= htmlspecialchars($roleText) ?> &mdash; <?= htmlspecialchars($currentUser->memberNumber) ?>
                <?php else: ?>
                    Kullanıcı Oturumu
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success"><?= htmlspecialchars((string) $flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-error">Hata alanı: <?= htmlspecialchars((string) $flashError) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>

</body>
</html>