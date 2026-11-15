<?php

require_once 'functions.php';

$hata = '';
$kullanicilar = kullanicilariGetir();
$yeniForm = [
    'ad_soyad'      => '',
    'kullanici_adi' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfDogrula($_POST['csrf_token'] ?? null)) {
        $hata = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        $islem = $_POST['islem'] ?? '';

        if ($islem === 'personel_ekle') {
            if (!yoneticiMi()) {
                $hata = 'Yeni depo görevlisi eklemek için Yönetici oturumu açık olmalıdır.';
            } else {
                $yeniAdSoyad = trim($_POST['yeni_ad_soyad'] ?? '');
                $yeniKadi    = mb_strtolower(trim($_POST['yeni_kullanici_adi'] ?? ''), 'UTF-8');
                $yeniSifre   = trim($_POST['yeni_sifre'] ?? '');

                $yeniForm['ad_soyad']      = $yeniAdSoyad;
                $yeniForm['kullanici_adi'] = $yeniKadi;

                if (mb_strlen($yeniAdSoyad, 'UTF-8') < 2 || mb_strlen($yeniAdSoyad, 'UTF-8') > 60) {
                    $hata = 'Ad soyad 2 ile 60 karakter arasında olmalıdır.';
                } elseif (mb_strlen($yeniKadi, 'UTF-8') < 2 || mb_strlen($yeniKadi, 'UTF-8') > 30 || preg_match('/\s/u', $yeniKadi)) {
                    $hata = 'Kullanıcı adı boşluksuz 2 ile 30 karakter arasında olmalıdır.';
                } elseif (mb_strlen($yeniSifre, 'UTF-8') < 3 || mb_strlen($yeniSifre, 'UTF-8') > 40) {
                    $hata = 'Şifre en az 3, en fazla 40 karakter olmalıdır.';
                } else {
                    foreach ($kullanicilar as $mevcut) {
                        if (mb_strtolower((string) ($mevcut['kullanici_adi'] ?? ''), 'UTF-8') === $yeniKadi) {
                            $hata = 'Bu kullanıcı adı (@' . $yeniKadi . ') zaten kayıtlı.';
                            break;
                        }
                    }
                }

                if ($hata === '') {
                    $_SESSION['kullanicilar'][] = [
                        'kullanici_adi' => $yeniKadi,
                        'sifre'         => $yeniSifre,
                        'ad_soyad'      => $yeniAdSoyad,
                        'rol'           => 'personel',
                    ];
                    hareketKaydet($yeniAdSoyad, 'KULLANICI', 'Yeni depo görevlisi kaydedildi (@' . $yeniKadi . ').');
                    mesajKaydet('basarili', '"' . $yeniAdSoyad . '" (@' . $yeniKadi . ') depo görevlisi olarak kaydedildi.');
                    header('Location: login.php');
                    exit;
                }
            }
        }

        elseif ($islem === 'personel_sil') {
            if (!yoneticiMi()) {
                $hata = 'Kullanıcı silmek için Yönetici oturumu açık olmalıdır.';
            } else {
                $silinecekKadi = trim($_POST['silinecek_kullanici_adi'] ?? '');
                if (in_array($silinecekKadi, ['admin', 'personel'], true)) {
                    $hata = 'Varsayılan demo hesapları silinemez.';
                } else {
                    $bulunanAd = $silinecekKadi;
                    $yeniListe = [];
                    $silindi   = false;
                    foreach ($kullanicilar as $k) {
                        if (($k['kullanici_adi'] ?? '') === $silinecekKadi) {
                            $bulunanAd = $k['ad_soyad'] ?? $silinecekKadi;
                            $silindi   = true;
                            continue;
                        }
                        $yeniListe[] = $k;
                    }

                    if ($silindi) {
                        $_SESSION['kullanicilar'] = array_values($yeniListe);
                        if ((aktifKullanici()['kullanici_adi'] ?? '') === $silinecekKadi) {
                            $_SESSION['kullanici'] = varsayilanKullanicilar()[0];
                        }
                        hareketKaydet($bulunanAd, 'KULLANICI', 'Depo görevlisi kaydı silindi (@' . $silinecekKadi . ').');
                        mesajKaydet('basarili', '"' . $bulunanAd . '" (@' . $silinecekKadi . ') kaydı silindi.');
                        header('Location: login.php');
                        exit;
                    }
                    $hata = 'Silinecek kullanıcı bulunamadı.';
                }
            }
        }

        else {
            $kadi  = trim($_POST['kullanici_adi'] ?? '');
            $sifre = trim($_POST['sifre'] ?? '');

            $girisBasarili = false;
            foreach ($kullanicilar as $hesap) {
                if (($hesap['kullanici_adi'] ?? '') === $kadi && ($hesap['sifre'] ?? '') === $sifre) {
                    $_SESSION['kullanici'] = $hesap;
                    $girisBasarili = true;
                    hareketKaydet('Oturum', 'SISTEM', $hesap['ad_soyad'] . ' (' . $hesap['rol'] . ') hesabına geçiş yapıldı.');
                    mesajKaydet('basarili', 'Hoş geldiniz, ' . $hesap['ad_soyad'] . ' (' . ($hesap['rol'] === 'admin' ? 'Yönetici' : 'Personel') . ').');
                    header('Location: index.php');
                    exit;
                }
            }

            if (!$girisBasarili) {
                $hata = 'Kullanıcı adı veya şifre hatalı.';
            }
        }
    }
}

$kullanicilar      = kullanicilariGetir();
$mevcutKullanici   = aktifKullanici();
$yoneticiAktif     = yoneticiMi();
$mesaj             = mesajAl();
$tumHareketler     = $_SESSION['hareketler'] ?? [];
$toplamKayitSayisi = count($tumHareketler);
$sonKayitlar       = array_slice($tumHareketler, 0, 4);

$aktifSayfa   = 'login';
$heroBaslik   = 'Kullanıcı Girişi & Rol Değiştirme';
$heroAltMetin = 'Yetkilerinize uygun şekilde yönetici veya personel olarak oturum açın; yönetici hesabı ile yeni depo görevlisi kaydedebilirsiniz.';
require 'header.php';
?>

<?php if ($mesaj): ?>
    <div class="mesaj <?= e($mesaj['tur']) ?>" style="margin-top: 16px;"><?= e($mesaj['metin']) ?></div>
<?php endif; ?>

<?php if ($hata !== ''): ?>
    <div class="mesaj hata" style="margin-top: 16px;"><?= e($hata) ?></div>
<?php endif; ?>

<section class="sayaclar login-sayaclar">
    <div class="kart">
        <span>Aktif Kullanıcı</span>
        <strong><?= e($mevcutKullanici['ad_soyad']) ?></strong>
        <small>@<?= e($mevcutKullanici['kullanici_adi']) ?></small>
    </div>
    <div class="kart <?= $yoneticiAktif ? 'stokta' : 'azaldi' ?>">
        <span>Mevcut Yetki Rolü</span>
        <strong><?= $yoneticiAktif ? 'Yönetici (Admin)' : 'Depo Görevlisi' ?></strong>
        <small><?= $yoneticiAktif ? 'Tüm sistem modülleri açık' : 'Operasyonel stok yetkisi' ?></small>
    </div>
    <div class="kart <?= $yoneticiAktif ? 'stokta' : 'azaldi' ?>">
        <span>Kayıtlı Kullanıcılar</span>
        <strong><?= count($kullanicilar) ?> Hesap</strong>
        <small><?= $yoneticiAktif ? 'Yeni depo görevlisi eklenebilir' : 'Personel ekleme için yönetici gerekli' ?></small>
    </div>
    <div class="kart">
        <span>Güvenlik &amp; Veri Durumu</span>
        <strong>CSRF + JSON Aktif</strong>
        <small><?= $toplamKayitSayisi ?> denetim kaydı tutuluyor</small>
    </div>
</section>

<div class="login-recurra-grid">

    <section class="recurra-card">
        <div class="kart-ust-baslik">
            <h3>Hızlı Rol Değiştir <span class="kart-alt-etiket"><?= count($kullanicilar) ?> Hesap</span></h3>
            <span class="rozet stokta">Canlı Oturum</span>
        </div>

        <div class="hesap-gecis-listesi">
            <?php foreach ($kullanicilar as $hesap): ?>
                <?php
                $kadi      = (string) ($hesap['kullanici_adi'] ?? '');
                $adSoyad   = (string) ($hesap['ad_soyad'] ?? $kadi);
                $rol       = (string) ($hesap['rol'] ?? 'personel');
                $adminMi   = $rol === 'admin';
                $aktifMi   = ($mevcutKullanici['kullanici_adi'] ?? '') === $kadi;
                $varsayilanMi = in_array($kadi, ['admin', 'personel'], true);

                $kelimeler = preg_split('/\s+/u', trim($adSoyad)) ?: [];
                $harfler   = '';
                foreach (array_slice($kelimeler, 0, 2) as $kl) {
                    $harfler .= mb_strtoupper(mb_substr($kl, 0, 1, 'UTF-8'), 'UTF-8');
                }
                if ($harfler === '') {
                    $harfler = $adminMi ? 'MY' : 'DG';
                }
                ?>
                <div class="hesap-gecis-kart <?= $aktifMi ? 'aktif-hesap' : '' ?>">
                    <div class="hesap-gecis-ust">
                        <div class="hesap-kimlik">
                            <span class="hesap-avatar <?= $adminMi ? '' : 'personel' ?>"><?= e($harfler) ?></span>
                            <div>
                                <strong><?= e($adSoyad) ?></strong>
                                <small><?= e($kadi) ?> / <?= e((string) ($hesap['sifre'] ?? '')) ?></small>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <?php if ($aktifMi): ?>
                                <span class="rozet stokta">Aktif</span>
                            <?php else: ?>
                                <span class="kategori-etiket"><?= $adminMi ? 'Tam Yetki' : 'Personel' ?></span>
                            <?php endif; ?>

                            <?php if ($yoneticiAktif && !$varsayilanMi): ?>
                                <form method="post" action="login.php" class="satir-ici-form"
                                      onsubmit="return confirm('Bu depo görevlisi kaydını silmek istediğinize emin misiniz?');">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="islem" value="personel_sil">
                                    <input type="hidden" name="silinecek_kullanici_adi" value="<?= e($kadi) ?>">
                                    <button type="submit" class="btn sil" title="Görevliyi Sil">🗑️</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="uc-metrik-kutu">
                        <?php if ($adminMi): ?>
                            <div>
                                <small>Fiyat &amp; KDV</small>
                                <strong class="metrik-yesil">Açık</strong>
                            </div>
                            <div>
                                <small>Silme</small>
                                <strong class="metrik-yesil">Yetkili</strong>
                            </div>
                            <div>
                                <small>Personel Ekle</small>
                                <strong class="metrik-mavi">Yetkili</strong>
                            </div>
                        <?php else: ?>
                            <div>
                                <small>Stok +/-</small>
                                <strong class="metrik-yesil">Açık</strong>
                            </div>
                            <div>
                                <small>Fiyat &amp; KDV</small>
                                <strong style="color: var(--warn-orange);">Kilitli</strong>
                            </div>
                            <div>
                                <small>Silme</small>
                                <strong style="color: var(--danger-red);">Kısıtlı</strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="login.php">
                        <?= csrfInput() ?>
                        <input type="hidden" name="kullanici_adi" value="<?= e($kadi) ?>">
                        <input type="hidden" name="sifre" value="<?= e((string) ($hesap['sifre'] ?? '')) ?>">
                        <button type="submit" class="btn <?= $adminMi ? '' : 'yesil' ?> tam-genislik">
                            <?= $adminMi ? '👑 Yönetici Olarak Geçiş Yap' : '👷 ' . e($adSoyad) . ' Olarak Geçiş Yap' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="login-orta-sutun">

        <section class="recurra-card">
            <div class="kart-ust-baslik">
                <h3>Oturum Aç <span class="kart-alt-etiket">Kimlik Doğrulama</span></h3>
                <span class="kategori-etiket">CSRF Korumalı</span>
            </div>

            <div class="siparis-rota-kutu">
                <div class="rota-satir">
                    <span class="rota-ikon mavi" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <div class="rota-metin">
                        <small>Şu Anki Oturum</small>
                        <strong><?= e($mevcutKullanici['ad_soyad']) ?> (<?= $yoneticiAktif ? 'Yönetici' : 'Personel' ?>)</strong>
                    </div>
                </div>
            </div>

            <form method="post" action="login.php" novalidate>
                <?= csrfInput() ?>
                <div class="alan">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <input type="text" id="kullanici_adi" name="kullanici_adi" value="<?= e($_POST['kullanici_adi'] ?? '') ?>" placeholder="admin veya personel" autocomplete="username" required>
                </div>
                <div class="alan">
                    <label for="sifre">Şifre</label>
                    <input type="password" id="sifre" name="sifre" placeholder="1234" autocomplete="current-password" required>
                </div>

                <div class="hizli-doldur-grup">
                    <span class="hizli-doldur-etiket">Hızlı Doldur:</span>
                    <?php foreach ($kullanicilar as $h): ?>
                        <button type="button" class="hap-btn" onclick="hesapDoldur('<?= e((string) $h['kullanici_adi']) ?>', '<?= e((string) $h['sifre']) ?>')">
                            <?= e((string) $h['kullanici_adi']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="login-aksiyonlar">
                    <button type="submit" class="btn">Giriş Yap</button>
                    <a href="index.php" class="btn gri">Ana Sayfaya Dön</a>
                    <a href="logout.php" class="btn gri">Varsayılana Dön</a>
                </div>
            </form>
        </section>

        <section class="recurra-card">
            <div class="kart-ust-baslik">
                <h3>Depo Görevlisi Kaydet <span class="kart-alt-etiket">Yönetici İşlemi</span></h3>
                <span class="rozet <?= $yoneticiAktif ? 'stokta' : 'azaldi' ?>">
                    <?= $yoneticiAktif ? 'Yetki Açık' : 'Yönetici Gerekli' ?>
                </span>
            </div>

            <?php if ($yoneticiAktif): ?>
                <form method="post" action="login.php" novalidate>
                    <?= csrfInput() ?>
                    <input type="hidden" name="islem" value="personel_ekle">

                    <div class="alan">
                        <label for="yeni_ad_soyad">Görevli Adı Soyadı *</label>
                        <input type="text" id="yeni_ad_soyad" name="yeni_ad_soyad" value="<?= e($yeniForm['ad_soyad']) ?>" placeholder="Örn: Ahmet Yılmaz" required>
                    </div>

                    <div class="form-satir-iki">
                        <div class="alan">
                            <label for="yeni_kullanici_adi">Kullanıcı Adı *</label>
                            <input type="text" id="yeni_kullanici_adi" name="yeni_kullanici_adi" value="<?= e($yeniForm['kullanici_adi']) ?>" placeholder="Örn: ahmet" required>
                        </div>

                        <div class="alan">
                            <label for="yeni_sifre">Şifre *</label>
                            <input type="password" id="yeni_sifre" name="yeni_sifre" placeholder="En az 3 karakter" required>
                        </div>
                    </div>

                    <button type="submit" class="btn yesil tam-genislik">➕ Yeni Depo Görevlisini Kaydet</button>
                    <div class="aciklama">Kaydedilen görevli otomatik olarak <strong>Personel</strong> rolüyle <code>data/veritabani.json</code> dosyasına yazılır.</div>
                </form>
            <?php else: ?>
                <div class="hesap-gecis-kart">
                    <p class="aciklama" style="margin: 0 0 12px;">
                        🔒 Yeni depo görevlisi hesabı oluşturmak için <strong>Yönetici (admin)</strong> oturumunda olmanız gerekir.
                    </p>
                    <form method="post" action="login.php">
                        <?= csrfInput() ?>
                        <input type="hidden" name="kullanici_adi" value="admin">
                        <input type="hidden" name="sifre" value="1234">
                        <button type="submit" class="btn tam-genislik">👑 Yönetici Hesabına Geç</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="login-sag-sutun">

        <section class="recurra-card">
            <div class="kart-ust-baslik">
                <h3>Rol &amp; Yetki Matrisi <span class="kart-alt-etiket">Erişim Özeti</span></h3>
            </div>

            <div class="yetki-matris-liste">
                <div class="yetki-matris-satir">
                    <strong>Stok Güncelleme (+/-)</strong>
                    <div class="yetki-matris-rozetler">
                        <span class="rozet stokta">Yönetici</span>
                        <span class="rozet stokta">Personel</span>
                    </div>
                </div>
                <div class="yetki-matris-satir">
                    <strong>Ürün &amp; Kategori Ekleme</strong>
                    <div class="yetki-matris-rozetler">
                        <span class="rozet stokta">Yönetici</span>
                        <span class="rozet stokta">Personel</span>
                    </div>
                </div>
                <div class="yetki-matris-satir">
                    <strong>Depo Görevlisi Kaydetme</strong>
                    <div class="yetki-matris-rozetler">
                        <span class="rozet stokta">Yönetici</span>
                        <span class="rozet tukendi">Yok</span>
                    </div>
                </div>
                <div class="yetki-matris-satir">
                    <strong>Fiyat &amp; KDV Düzenleme</strong>
                    <div class="yetki-matris-rozetler">
                        <span class="rozet stokta">Yönetici</span>
                        <span class="rozet azaldi">Kilitli</span>
                    </div>
                </div>
                <div class="yetki-matris-satir">
                    <strong>Ürün &amp; Kategori Silme</strong>
                    <div class="yetki-matris-rozetler">
                        <span class="rozet stokta">Yönetici</span>
                        <span class="rozet tukendi">Yok</span>
                    </div>
                </div>
                <div class="yetki-matris-satir">
                    <strong>Veritabanı Sıfırlama</strong>
                    <div class="yetki-matris-rozetler">
                        <span class="rozet stokta">Yönetici</span>
                        <span class="rozet tukendi">Yok</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="recurra-card">
            <div class="kart-ust-baslik">
                <h3>Son Sistem Kayıtları <span class="kart-alt-etiket">Denetim İzi</span></h3>
                <a href="hareketler.php" class="hap-btn">Tümü →</a>
            </div>

            <div class="oturum-log-liste">
                <?php if (empty($sonKayitlar)): ?>
                    <p class="aciklama" style="margin: 0;">Henüz kayıtlı oturum veya stok hareketi bulunmuyor.</p>
                <?php else: ?>
                    <?php foreach ($sonKayitlar as $kayit): ?>
                        <?php
                        $logSinif = match ($kayit['tur'] ?? '') {
                            'EKLENDI', 'STOK_ARTIS', 'KULLANICI' => 'stokta',
                            'GUNCELLENDI', 'KATEGORI', 'STOK_AZALIS' => 'azaldi',
                            'SILINDI', 'SIFIRLANDI' => 'tukendi',
                            default => 'stokta',
                        };
                        ?>
                        <div class="oturum-log-oge">
                            <div class="oturum-log-ust">
                                <strong><?= e($kayit['urun_ad'] ?? 'Sistem') ?></strong>
                                <span class="rozet <?= $logSinif ?>"><?= e($kayit['tur'] ?? 'SISTEM') ?></span>
                            </div>
                            <div class="oturum-log-detay"><?= e($kayit['detay'] ?? '') ?></div>
                            <div class="oturum-log-zaman">👤 <?= e($kayit['kullanici'] ?? 'Sistem') ?> • <?= e($kayit['tarih'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script>
    function hesapDoldur(kadi, sifre) {
        const kadiInput = document.getElementById('kullanici_adi');
        const sifreInput = document.getElementById('sifre');
        if (kadiInput) kadiInput.value = kadi;
        if (sifreInput) sifreInput.value = sifre;
        if (kadiInput) kadiInput.focus();
    }
</script>

<?php require 'footer.php'; ?>
