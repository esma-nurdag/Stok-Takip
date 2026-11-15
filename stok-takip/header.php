<?php

$aktifKullanici = aktifKullanici();
$kullaniciAd    = $aktifKullanici['ad_soyad'] ?? 'Mağaza Yöneticisi';
$kullaniciRol   = $aktifKullanici['rol'] ?? 'admin';

$kelimeler = preg_split('/\s+/u', trim($kullaniciAd)) ?: [];
$basHarfler = '';
foreach (array_slice($kelimeler, 0, 2) as $k) {
    $basHarfler .= mb_strtoupper(mb_substr($k, 0, 1, 'UTF-8'), 'UTF-8');
}
if ($basHarfler === '') {
    $basHarfler = 'MY';
}

$kritikSayisi = 0;
foreach (($_SESSION['urunler'] ?? []) as $u) {
    $kritik = (int) ($u['kritik_stok'] ?? KRITIK_STOK);
    if ((int) ($u['adet'] ?? 0) <= $kritik) {
        $kritikSayisi++;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok-Takip — AI Stok Takip ve Envanter Operasyon Platformu</title>
    <meta name="description" content="Gerçek zamanlı stok takibi, maliyet/kâr analizi, kategori ve hareket yönetimi sunan akıllı envanter operasyon platformu.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=4">
    <script>

        (function() {
            const kayitliTema = localStorage.getItem('recurra_tema');
            if (kayitliTema === 'karanlik') {
                document.documentElement.classList.add('dark-mode');
            } else {
                document.documentElement.classList.remove('dark-mode');
            }
        })();
    </script>
</head>
<body>
    <div class="recurra-shell">
        <header>

            <div class="header-ust">
                <div class="marka-alani">
                    <a href="index.php" class="marka-logo">
                        <span class="logo-ikon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="3"/>
                                <path d="M16 12h5"/>
                                <circle cx="16.5" cy="12" r="1" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="logo-metin">Stok-Takip</span>
                    </a>

                    <div class="baglam-kapsul">
                        <a href="index.php" class="baglam-item <?= in_array(($aktifSayfa ?? ''), ['liste', 'raporlar'], true) ? 'aktif' : '' ?>">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="4.5" cy="4.5" r="2"/>
                                <circle cx="11.5" cy="4.5" r="2"/>
                                <circle cx="4.5" cy="11.5" r="2"/>
                                <circle cx="11.5" cy="11.5" r="2"/>
                            </svg>
                            Envanter
                        </a>
                        <a href="kategoriler.php" class="baglam-item <?= ($aktifSayfa ?? '') === 'kategoriler' ? 'aktif' : '' ?>">
                            Kategoriler ▾
                        </a>
                    </div>
                </div>

                <form method="get" action="index.php" class="ust-arama-formu" role="search">
                    <span class="ust-arama-ikon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </span>
                    <input type="text" id="ust-barkod-arama" name="ara" value="<?= e($_GET['ara'] ?? '') ?>" placeholder="Ürün, barkod veya tedarikçi ara...">
                    <button type="submit" class="ai-sparkle-btn" title="AI Envanter Arama">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L14.2 9.2L21.5 11.5L14.2 13.8L12 21L9.8 13.8L2.5 11.5L9.8 9.2L12 2Z"/>
                        </svg>
                    </button>
                </form>

                <div class="header-araclar">
                    <button type="button" id="temaBtn" class="ikon-btn" onclick="temaDegistir()" title="Tema Değiştir">
                        🌙
                    </button>

                    <button type="button" class="ikon-btn" onclick="window.print()" title="Stok Listesini Yazdır">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                    </button>

                    <a href="raporlar.php#kritik-liste" class="ikon-btn" title="<?= $kritikSayisi > 0 ? $kritikSayisi . ' kritik stok uyarısı' : 'Stok Bildirimleri' ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <?php if ($kritikSayisi > 0): ?>
                            <span class="bildirim-nokta"></span>
                        <?php endif; ?>
                    </a>

                    <?php if (yoneticiMi()): ?>
                        <form method="post" action="sifirla.php" class="satir-ici-form"
                              onsubmit="return confirm('Tüm ürünleri ve geçmişi başlangıçtaki örnek verilere sıfırlamak istediğinize emin misiniz?');">
                            <?= csrfInput() ?>
                            <button type="submit" class="ikon-btn tehlike" title="Örnek Verilere Sıfırla">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                </svg>
                            </button>
                        </form>
                    <?php endif; ?>

                    <a href="login.php" class="kullanici-profil" title="Kullanıcı Girişi & Rol Değiştir">
                        <span class="profil-avatar">
                            <?= e($basHarfler) ?>
                            <span class="profil-durum-nokta <?= $kullaniciRol === 'admin' ? 'admin' : 'personel' ?>"></span>
                        </span>
                        <span class="profil-bilgi">
                            <span class="profil-ad"><?= e($kullaniciAd) ?></span>
                            <span class="profil-alt"><?= $kullaniciRol === 'admin' ? 'Yönetici • admin@stoktakip.io' : 'Personel • depo@stoktakip.io' ?></span>
                        </span>
                        <span class="profil-ok">▾</span>
                    </a>
                </div>
            </div>

            <div class="alt-nav-satiri">
                <nav class="sekme-nav" aria-label="Ana Sayfalar">
                    <a href="index.php" class="<?= ($aktifSayfa ?? '') === 'liste' ? 'aktif' : '' ?>">Genel Bakış</a>
                    <a href="raporlar.php" class="<?= ($aktifSayfa ?? '') === 'raporlar' ? 'aktif' : '' ?>">Raporlar</a>
                    <a href="kategoriler.php" class="<?= ($aktifSayfa ?? '') === 'kategoriler' ? 'aktif' : '' ?>">Kategoriler</a>
                    <a href="hareketler.php" class="<?= ($aktifSayfa ?? '') === 'hareketler' ? 'aktif' : '' ?>">Hareketler</a>
                </nav>

                <a href="ekle.php" class="btn-yeni-kayit">+ Yeni Kayıt</a>
            </div>

            <?php if (($aktifSayfa ?? '') !== 'liste' && !empty($heroBaslik)): ?>
                <div class="sayfa-baslik-serit">
                    <div>
                        <h1><?= e($heroBaslik) ?></h1>
                        <?php if (!empty($heroAltMetin)): ?>
                            <p><?= e($heroAltMetin) ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="index.php" class="hap-btn">← Genel Bakışa Dön</a>
                </div>
            <?php endif; ?>
        </header>
        <main>
