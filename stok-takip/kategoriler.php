<?php

require_once 'functions.php';

$hatalar = [];
$yeniKategoriAdi = '';
$topluMetin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfDogrula($_POST['csrf_token'] ?? null)) {
        mesajKaydet('hata', 'Güvenlik doğrulaması başarısız oldu.');
        header('Location: kategoriler.php');
        exit;
    }

    $islem = $_POST['islem'] ?? '';
    $geriDon = $_POST['geri_don'] ?? 'kategoriler.php';
    if (!in_array($geriDon, ['kategoriler.php', 'index.php', 'ekle.php'], true)) {
        $geriDon = 'kategoriler.php';
    }

    if ($islem === 'ekle') {
        $yeniKategoriAdi = trim($_POST['kategori_adi'] ?? '');
        $mevcutlar = kategorileriGetir();

        if ($yeniKategoriAdi === '') {
            $hatalar['kategori_adi'] = 'Kategori adı boş bırakılamaz.';
        } elseif (mb_strlen($yeniKategoriAdi) < 2 || mb_strlen($yeniKategoriAdi) > 40) {
            $hatalar['kategori_adi'] = 'Kategori adı 2 ile 40 karakter arasında olmalıdır.';
        } else {
            foreach ($mevcutlar as $kat) {
                if (mb_strtolower($kat, 'UTF-8') === mb_strtolower($yeniKategoriAdi, 'UTF-8')) {
                    $hatalar['kategori_adi'] = 'Bu kategori zaten mevcut.';
                    break;
                }
            }
        }

        if (empty($hatalar)) {
            kategoriYoksaEkle($yeniKategoriAdi);
            mesajKaydet('basarili', '"' . $yeniKategoriAdi . '" kategorisi başarıyla eklendi.');
            header('Location: ' . $geriDon);
            exit;
        } elseif ($geriDon !== 'kategoriler.php') {
            mesajKaydet('hata', $hatalar['kategori_adi']);
            header('Location: ' . $geriDon);
            exit;
        }
    }

    if ($islem === 'disaridan_aktar') {
        $topluMetin = trim($_POST['toplu_kategoriler'] ?? '');
        $dosyaIcerigi = '';

        if (isset($_FILES['kategori_dosyasi']) && $_FILES['kategori_dosyasi']['error'] === UPLOAD_ERR_OK) {
            $uzanti = strtolower(pathinfo($_FILES['kategori_dosyasi']['name'], PATHINFO_EXTENSION));
            if (!in_array($uzanti, ['txt', 'csv', 'json'], true)) {
                $hatalar['toplu'] = 'Sadece .txt, .csv veya .json uzantılı dosya yükleyebilirsiniz.';
            } else {
                $hamIcerik = (string) file_get_contents($_FILES['kategori_dosyasi']['tmp_name']);

                $hamIcerik = preg_replace('/^\xEF\xBB\xBF/', '', $hamIcerik);

                if ($uzanti === 'json') {
                    $jsonDizi = json_decode($hamIcerik, true);
                    if (is_array($jsonDizi)) {
                        $gecici = [];
                        foreach ($jsonDizi as $eleman) {
                            if (is_string($eleman)) {
                                $gecici[] = $eleman;
                            } elseif (is_array($eleman) && isset($eleman['ad'])) {
                                $gecici[] = (string) $eleman['ad'];
                            } elseif (is_array($eleman) && isset($eleman['kategori'])) {
                                $gecici[] = (string) $eleman['kategori'];
                            }
                        }
                        $dosyaIcerigi = implode("\n", $gecici);
                    }
                } else {
                    $dosyaIcerigi = $hamIcerik;
                }
            }
        }

        $birlesikMetin = trim($topluMetin . "\n" . $dosyaIcerigi);

        if (empty($hatalar)) {
            if ($birlesikMetin === '') {
                $hatalar['toplu'] = 'Lütfen eklenecek kategorileri yazın veya dışarıdan bir dosya (.txt, .csv, .json) seçin.';
            } else {

                $adaylar = preg_split('/[,;\r\n]+/', $birlesikMetin) ?: [];
                $eklenenSayisi = 0;
                $atlananSayisi = 0;

                foreach ($adaylar as $aday) {
                    $temizAd = trim($aday, " \t\n\r\0\x0B\"'");
                    if ($temizAd === '' || mb_strlen($temizAd) < 2 || mb_strlen($temizAd) > 40) {
                        continue;
                    }

                    $mevcutMu = false;
                    foreach (kategorileriGetir() as $mevcut) {
                        if (mb_strtolower($mevcut, 'UTF-8') === mb_strtolower($temizAd, 'UTF-8')) {
                            $mevcutMu = true;
                            break;
                        }
                    }

                    if ($mevcutMu) {
                        $atlananSayisi++;
                    } else {
                        $_SESSION['kategoriler'][] = $temizAd;
                        $eklenenSayisi++;
                    }
                }

                if ($eklenenSayisi > 0) {
                    hareketKaydet('Toplu Kategori', 'KATEGORI', 'Dışarıdan ' . $eklenenSayisi . ' yeni kategori aktarıldı.');
                    yerelVeriyiKaydet();
                    mesajKaydet(
                        'basarili',
                        sprintf('Dışarıdan %d yeni kategori başarıyla eklendi.%s', $eklenenSayisi, $atlananSayisi > 0 ? " ($atlananSayisi mevcut kategori atlandı)" : '')
                    );
                    header('Location: kategoriler.php');
                    exit;
                } else {
                    $hatalar['toplu'] = 'Yeni bir kategori eklenemedi (Girilen kategoriler zaten mevcut veya geçersiz).';
                }
            }
        }
    }

    if ($islem === 'sil') {
        if (!yoneticiMi()) {
            mesajKaydet('hata', 'Kategori silmek için Yönetici yetkisi gereklidir.');
            header('Location: kategoriler.php');
            exit;
        }

        $silinecek = trim($_POST['kategori_adi'] ?? '');
        $urunSayisi = 0;
        foreach ($_SESSION['urunler'] as $u) {
            if ($u['kategori'] === $silinecek) {
                $urunSayisi++;
            }
        }

        if ($urunSayisi > 0) {
            mesajKaydet('hata', '"' . $silinecek . '" kategorisinde ' . $urunSayisi . ' adet ürün bulunduğu için silinemez.');
        } else {
            $_SESSION['kategoriler'] = array_values(array_filter(
                $_SESSION['kategoriler'],
                fn($k) => $k !== $silinecek
            ));
            hareketKaydet($silinecek, 'KATEGORI', 'Kategori silindi: ' . $silinecek);
            yerelVeriyiKaydet();
            mesajKaydet('basarili', '"' . $silinecek . '" kategorisi silindi.');
        }

        header('Location: kategoriler.php');
        exit;
    }
}

$mevcutKategoriler = kategorileriGetir();
$mesaj = mesajAl();

$kategoriIstatistik = [];
foreach ($mevcutKategoriler as $kat) {
    $kategoriIstatistik[$kat] = [
        'cesit' => 0,
        'adet'  => 0,
        'deger' => 0.0,
    ];
}
foreach ($_SESSION['urunler'] as $u) {
    $kat = $u['kategori'];
    if (!isset($kategoriIstatistik[$kat])) {
        $kategoriIstatistik[$kat] = ['cesit' => 0, 'adet' => 0, 'deger' => 0.0];
    }
    $kategoriIstatistik[$kat]['cesit']++;
    $kategoriIstatistik[$kat]['adet']  += (int) $u['adet'];
    $kategoriIstatistik[$kat]['deger'] += (int) $u['adet'] * (float) $u['fiyat'];
}

$aktifSayfa = 'kategoriler';
$heroBaslik   = 'Kategori Yönetimi';
$heroAltMetin = 'Kategorileri ekleyin, düzenleyin ve ürünlerinize otomatik ikon etiketleri atayın.';
require 'header.php';
?>

<h2>🏷️ Kategori Yönetimi &amp; Dışarıdan Kategori Aktarma</h2>

<?php if ($mesaj): ?>
    <div class="mesaj <?= e($mesaj['tur']) ?>"><?= e($mesaj['metin']) ?></div>
<?php endif; ?>

<div class="iki-sutun-duzen">
    <div>

        <div class="form-kutu" style="margin-bottom: 18px;">
            <h3>➕ Hızlı Kategori Ekle</h3>
            <form method="post" action="kategoriler.php" novalidate>
                <?= csrfInput() ?>
                <input type="hidden" name="islem" value="ekle">
                <div class="alan <?= isset($hatalar['kategori_adi']) ? 'hatali' : '' ?>">
                    <label for="kategori_adi">Kategori Adı *</label>
                    <input type="text" id="kategori_adi" name="kategori_adi" value="<?= e($yeniKategoriAdi) ?>" placeholder="Örn: Kozmetik, Oyuncak, Hırdavat">
                    <?php if (isset($hatalar['kategori_adi'])): ?>
                        <div class="hata-metin"><?= e($hatalar['kategori_adi']) ?></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn tam-genislik">➕ Kategori Ekle</button>
            </form>
        </div>

        <div class="form-kutu">
            <h3>📂 Dışarıdan Toplu Kategori Aktar</h3>
            <p class="aciklama" style="margin-top: 0;">Birden fazla kategoriyi virgülle/alt alta yazarak veya bilgisayarınızdaki <code>.txt</code>, <code>.csv</code>, <code>.json</code> dosyasından dışarıdan içeri aktarabilirsiniz.</p>
            <form method="post" action="kategoriler.php" enctype="multipart/form-data" novalidate>
                <?= csrfInput() ?>
                <input type="hidden" name="islem" value="disaridan_aktar">

                <div class="alan <?= isset($hatalar['toplu']) ? 'hatali' : '' ?>">
                    <label for="toplu_kategoriler">Kategorileri Yazın (Virgül veya Satır İle Ayırın)</label>
                    <textarea id="toplu_kategoriler" name="toplu_kategoriler" rows="3" placeholder="Örn: Kozmetik, Mobilya, Spor Malzemeleri, Otomotiv"><?= e($topluMetin) ?></textarea>
                </div>

                <div class="alan">
                    <label for="kategori_dosyasi">Veya Dışarıdan Dosya Seçin (.txt, .csv, .json)</label>
                    <input type="file" id="kategori_dosyasi" name="kategori_dosyasi" accept=".txt,.csv,.json">
                    <?php if (isset($hatalar['toplu'])): ?>
                        <div class="hata-metin"><?= e($hatalar['toplu']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn yesil tam-genislik">📥 Dışarıdan Kategorileri Aktar</button>
            </form>
        </div>
    </div>

    <div class="tablo-kutu">
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Ürün Çeşidi</th>
                    <th>Toplam Stok Adedi</th>
                    <th>Toplam Satış Değeri</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategoriIstatistik as $katAdi => $ist): ?>
                    <tr>
                        <td>
                            <strong><?= kategoriIkonu($katAdi) ?> <?= e($katAdi) ?></strong>
                        </td>
                        <td><?= $ist['cesit'] ?> ürün</td>
                        <td><?= $ist['adet'] ?> adet</td>
                        <td><?= fiyatFormatla($ist['deger']) ?></td>
                        <td>
                            <div class="islem-grubu">
                                <a href="index.php?kategori=<?= urlencode($katAdi) ?>" class="btn kucuk duzenle">Ürünleri Gör</a>
                                <?php if (yoneticiMi() && $ist['cesit'] === 0): ?>
                                    <form method="post" action="kategoriler.php" class="satir-ici-form"
                                          onsubmit="return confirm('Bu boş kategoriyi silmek istediğinize emin misiniz?');">
                                        <?= csrfInput() ?>
                                        <input type="hidden" name="islem" value="sil">
                                        <input type="hidden" name="kategori_adi" value="<?= e($katAdi) ?>">
                                        <button type="submit" class="btn sil">Sil</button>
                                    </form>
                                <?php elseif ($ist['cesit'] > 0): ?>
                                    <span class="aciklama" title="İçinde ürün bulunan kategori silinemez">🔒 Dolu</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
