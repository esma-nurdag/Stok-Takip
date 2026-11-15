<?php

require_once 'functions.php';

$hatalar = [];
$mevcutKategoriler = kategorileriGetir();
$onerilenKod = 'URN-' . str_pad((string) ($_SESSION['sonraki_id'] ?? 1), 3, '0', STR_PAD_LEFT);

$veri = [
    'kod'         => $onerilenKod,
    'ad'          => '',
    'kategori'    => '',
    'adet'        => '',
    'kritik_stok' => (string) KRITIK_STOK,
    'alis_fiyati' => '0',
    'fiyat'       => '',
    'kdv'         => '20',
    'tedarikci'   => '',
    'aciklama'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfDogrula($_POST['csrf_token'] ?? null)) {
        $hatalar['genel'] = 'Güvenlik doğrulaması (CSRF) başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $secilenKategori = trim($_POST['kategori'] ?? '');
        $yeniKategori    = trim($_POST['yeni_kategori'] ?? '');

        if ($yeniKategori !== '' || $secilenKategori === '__yeni__') {
            $nihaiKategori = $yeniKategori;
        } else {
            $nihaiKategori = $secilenKategori;
        }

        $veri['kod']         = strtoupper(trim($_POST['kod'] ?? ''));
        $veri['ad']          = trim($_POST['ad'] ?? '');
        $veri['kategori']    = $nihaiKategori;
        $veri['adet']        = trim($_POST['adet'] ?? '');
        $veri['kritik_stok'] = trim($_POST['kritik_stok'] ?? (string) KRITIK_STOK);
        $veri['alis_fiyati'] = trim($_POST['alis_fiyati'] ?? '0');
        $veri['fiyat']       = trim($_POST['fiyat'] ?? '');
        $veri['kdv']         = trim($_POST['kdv'] ?? '20');
        $veri['tedarikci']   = trim($_POST['tedarikci'] ?? '');
        $veri['aciklama']    = trim($_POST['aciklama'] ?? '');

        $hatalar = urunDogrula($veri);

        $yuklenenGorsel = '';
        if (empty($hatalar) && isset($_FILES['gorsel'])) {
            $gorselSonuc = gorselYukle($_FILES['gorsel']);
            if ($gorselSonuc['hata'] !== null) {
                $hatalar['gorsel'] = $gorselSonuc['hata'];
            } else {
                $yuklenenGorsel = $gorselSonuc['yol'];
            }
        }

        if (empty($hatalar)) {

            $kayitliKategori = kategoriYoksaEkle($veri['kategori']);

            $yeniUrun = [
                'id'          => $_SESSION['sonraki_id'],
                'kod'         => $veri['kod'],
                'ad'          => $veri['ad'],
                'kategori'    => $kayitliKategori,
                'adet'        => (int) $veri['adet'],
                'kritik_stok' => (int) $veri['kritik_stok'],
                'alis_fiyati' => (float) str_replace(',', '.', $veri['alis_fiyati']),
                'fiyat'       => (float) str_replace(',', '.', $veri['fiyat']),
                'kdv'         => (int) $veri['kdv'],
                'tedarikci'   => $veri['tedarikci'],
                'gorsel'      => $yuklenenGorsel,
                'aciklama'    => $veri['aciklama'],
                'guncelleme'  => date('Y-m-d H:i'),
            ];

            $_SESSION['urunler'][] = $yeniUrun;
            $_SESSION['sonraki_id']++;

            hareketKaydet(
                $yeniUrun['ad'],
                'EKLENDI',
                sprintf(
                    'Kod: %s | Başlangıç stoğu: %d adet | Satış Fiyatı: %s',
                    $yeniUrun['kod'],
                    $yeniUrun['adet'],
                    fiyatFormatla($yeniUrun['fiyat'])
                )
            );

            mesajKaydet('basarili', '"' . $veri['ad'] . '" başarıyla eklendi ve yerel veritabanına kaydedildi.');

            header('Location: index.php');
            exit;
        }
    }
}

$aktifSayfa = 'ekle';
$heroBaslik   = 'Yeni Ürün Ekle';
$heroAltMetin = 'Barkod, kategori, fiyat ve stok bilgilerini girerek envantere yeni bir ürün kaydedin.';
require 'header.php';
?>

<h2>➕ Yeni Ürün Ekle</h2>

<?php if (!empty($hatalar)): ?>
    <div class="mesaj hata">
        <?= isset($hatalar['genel']) ? e($hatalar['genel']) : 'Ürün eklenemedi. Lütfen işaretli alanları kontrol edin.' ?>
    </div>
<?php endif; ?>

<div class="form-kutu genis">
    <form method="post" action="ekle.php" enctype="multipart/form-data" novalidate>
        <?= csrfInput() ?>

        <div class="form-satir-iki">
            <div class="alan <?= isset($hatalar['kod']) ? 'hatali' : '' ?>">
                <label for="kod">Stok Kodu / Barkod (SKU) *</label>
                <input type="text" id="kod" name="kod" value="<?= e($veri['kod']) ?>" placeholder="Örn: ELK-003">
                <?php if (isset($hatalar['kod'])): ?><div class="hata-metin"><?= e($hatalar['kod']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['kategori']) ? 'hatali' : '' ?>">
                <label for="kategori">Kategori *</label>
                <?php
                $listedekiKategoriMi = $veri['kategori'] === '' || in_array($veri['kategori'], $mevcutKategoriler, true);
                ?>
                <select id="kategori" name="kategori" onchange="yeniKategoriKutusuGoster(this.value)">
                    <option value="">-- Listeden Seçiniz --</option>
                    <?php foreach ($mevcutKategoriler as $kategori): ?>
                        <option value="<?= e($kategori) ?>" <?= $veri['kategori'] === $kategori ? 'selected' : '' ?>>
                            <?= e($kategori) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="__yeni__" <?= !$listedekiKategoriMi ? 'selected' : '' ?>>➕ Dışarıdan Yeni Kategori Ekle...</option>
                </select>

                <div id="yeni_kategori_kapsayici" style="margin-top: 8px; display: <?= !$listedekiKategoriMi ? 'block' : 'none' ?>;">
                    <input type="text" id="yeni_kategori" name="yeni_kategori"
                           value="<?= !$listedekiKategoriMi ? e($veri['kategori']) : '' ?>"
                           placeholder="Yeni kategori adını yazın (Örn: Kozmetik, Oyuncak)">
                    <div class="aciklama">Yazdığınız kategori otomatik olarak sisteme kaydedilir.</div>
                </div>
                <?php if (isset($hatalar['kategori'])): ?><div class="hata-metin"><?= e($hatalar['kategori']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="form-satir-iki">
            <div class="alan <?= isset($hatalar['ad']) ? 'hatali' : '' ?>">
                <label for="ad">Ürün Adı *</label>
                <input type="text" id="ad" name="ad" value="<?= e($veri['ad']) ?>" placeholder="Örn: Kablosuz Mekanik Klavye">
                <?php if (isset($hatalar['ad'])): ?><div class="hata-metin"><?= e($hatalar['ad']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['tedarikci']) ? 'hatali' : '' ?>">
                <label for="tedarikci">Tedarikçi Firma (İsteğe Bağlı)</label>
                <input type="text" id="tedarikci" name="tedarikci" value="<?= e($veri['tedarikci']) ?>" placeholder="Örn: TeknoMerkez Bilişim A.Ş.">
                <?php if (isset($hatalar['tedarikci'])): ?><div class="hata-metin"><?= e($hatalar['tedarikci']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="form-satir-iki">
            <div class="alan <?= isset($hatalar['adet']) ? 'hatali' : '' ?>">
                <label for="adet">Stok Adedi *</label>
                <input type="number" id="adet" name="adet" value="<?= e($veri['adet']) ?>" min="0" step="1" placeholder="Örn: 10">
                <?php if (isset($hatalar['adet'])): ?><div class="hata-metin"><?= e($hatalar['adet']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['kritik_stok']) ? 'hatali' : '' ?>">
                <label for="kritik_stok">Kritik Stok Uyarı Sınırı *</label>
                <input type="number" id="kritik_stok" name="kritik_stok" value="<?= e($veri['kritik_stok']) ?>" min="0" step="1">
                <div class="aciklama">Bu sayının altına düştüğünde "Azaldı" uyarısı verir.</div>
                <?php if (isset($hatalar['kritik_stok'])): ?><div class="hata-metin"><?= e($hatalar['kritik_stok']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="form-satir-uc">
            <div class="alan <?= isset($hatalar['alis_fiyati']) ? 'hatali' : '' ?>">
                <label for="alis_fiyati">Birim Alış Maliyeti (₺)</label>
                <input type="text" id="alis_fiyati" name="alis_fiyati" value="<?= e($veri['alis_fiyati']) ?>" placeholder="Örn: 95,00">
                <?php if (isset($hatalar['alis_fiyati'])): ?><div class="hata-metin"><?= e($hatalar['alis_fiyati']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['fiyat']) ? 'hatali' : '' ?>">
                <label for="fiyat">Birim Satış Fiyatı (₺) *</label>
                <input type="text" id="fiyat" name="fiyat" value="<?= e($veri['fiyat']) ?>" placeholder="Örn: 149,90">
                <?php if (isset($hatalar['fiyat'])): ?><div class="hata-metin"><?= e($hatalar['fiyat']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['kdv']) ? 'hatali' : '' ?>">
                <label for="kdv">KDV Oranı (%)</label>
                <select id="kdv" name="kdv">
                    <?php foreach ([1, 10, 20, 0] as $oran): ?>
                        <option value="<?= $oran ?>" <?= (int) $veri['kdv'] === $oran ? 'selected' : '' ?>>%<?= $oran ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($hatalar['kdv'])): ?><div class="hata-metin"><?= e($hatalar['kdv']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="alan <?= isset($hatalar['gorsel']) ? 'hatali' : '' ?>">
            <label for="gorsel">Ürün Görseli (İsteğe Bağlı — JPG, PNG, WEBP, Maks. 2MB)</label>
            <input type="file" id="gorsel" name="gorsel" accept=".jpg,.jpeg,.png,.webp,.gif">
            <?php if (isset($hatalar['gorsel'])): ?><div class="hata-metin"><?= e($hatalar['gorsel']) ?></div><?php endif; ?>
        </div>

        <div class="alan <?= isset($hatalar['aciklama']) ? 'hatali' : '' ?>">
            <label for="aciklama">Açıklama (İsteğe Bağlı)</label>
            <textarea id="aciklama" name="aciklama" rows="3" placeholder="Örn: Siyah, Türkçe Q klavye, 2 yıl garantili"><?= e($veri['aciklama']) ?></textarea>
            <?php if (isset($hatalar['aciklama'])): ?><div class="hata-metin"><?= e($hatalar['aciklama']) ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn">💾 Kaydet</button>
        <a href="index.php" class="btn gri">Vazgeç</a>
    </form>
</div>

<?php require 'footer.php'; ?>
