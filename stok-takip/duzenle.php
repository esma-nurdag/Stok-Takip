<?php

require_once 'functions.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bulunan = urunBul($id);

if ($bulunan === null) {
    mesajKaydet('hata', 'Düzenlenecek ürün bulunamadı.');
    header('Location: index.php');
    exit;
}

$indeks = $bulunan['indeks'];
$mevcutUrun = $bulunan['urun'];
$mevcutKategoriler = kategorileriGetir();
$yonetici = yoneticiMi();
$hatalar = [];

$veri = [
    'kod'         => $mevcutUrun['kod'] ?? '',
    'ad'          => $mevcutUrun['ad'],
    'kategori'    => $mevcutUrun['kategori'],
    'adet'        => (string) $mevcutUrun['adet'],
    'kritik_stok' => (string) ($mevcutUrun['kritik_stok'] ?? KRITIK_STOK),
    'alis_fiyati' => number_format((float) ($mevcutUrun['alis_fiyati'] ?? 0), 2, ',', ''),
    'fiyat'       => number_format((float) $mevcutUrun['fiyat'], 2, ',', ''),
    'kdv'         => (string) ($mevcutUrun['kdv'] ?? 20),
    'tedarikci'   => $mevcutUrun['tedarikci'] ?? '',
    'aciklama'    => $mevcutUrun['aciklama'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfDogrula($_POST['csrf_token'] ?? null)) {
        $hatalar['genel'] = 'Güvenlik doğrulaması (CSRF) başarısız oldu.';
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
        $veri['tedarikci']   = trim($_POST['tedarikci'] ?? '');
        $veri['aciklama']    = trim($_POST['aciklama'] ?? '');

        if ($yonetici) {
            $veri['alis_fiyati'] = trim($_POST['alis_fiyati'] ?? '0');
            $veri['fiyat']       = trim($_POST['fiyat'] ?? '');
            $veri['kdv']         = trim($_POST['kdv'] ?? '20');
        }

        $hatalar = urunDogrula($veri, $id);

        $eskiGorsel = $mevcutUrun['gorsel'] ?? '';
        $yeniGorsel = $eskiGorsel;
        if (!empty($_POST['gorsel_sil'])) {
            $yeniGorsel = '';
        }

        if (empty($hatalar) && isset($_FILES['gorsel']) && $_FILES['gorsel']['error'] !== UPLOAD_ERR_NO_FILE) {
            $gorselSonuc = gorselYukle($_FILES['gorsel']);
            if ($gorselSonuc['hata'] !== null) {
                $hatalar['gorsel'] = $gorselSonuc['hata'];
            } else {
                $yeniGorsel = $gorselSonuc['yol'];
            }
        }

        if (empty($hatalar)) {

            if ($eskiGorsel !== '' && $eskiGorsel !== $yeniGorsel) {
                gorselDosyasiSil($eskiGorsel);
            }

            $kayitliKategori = kategoriYoksaEkle($veri['kategori']);
            $eskiAdet  = (int) $mevcutUrun['adet'];
            $yeniAdet  = (int) $veri['adet'];
            $eskiFiyat = (float) $mevcutUrun['fiyat'];
            $yeniFiyat = (float) str_replace(',', '.', $veri['fiyat']);

            $_SESSION['urunler'][$indeks] = [
                'id'          => $id,
                'kod'         => $veri['kod'],
                'ad'          => $veri['ad'],
                'kategori'    => $kayitliKategori,
                'adet'        => $yeniAdet,
                'kritik_stok' => (int) $veri['kritik_stok'],
                'alis_fiyati' => (float) str_replace(',', '.', $veri['alis_fiyati']),
                'fiyat'       => $yeniFiyat,
                'kdv'         => (int) $veri['kdv'],
                'tedarikci'   => $veri['tedarikci'],
                'gorsel'      => $yeniGorsel,
                'aciklama'    => $veri['aciklama'],
                'guncelleme'  => date('Y-m-d H:i'),
            ];

            $degisiklikDetay = sprintf(
                'Adet: %d → %d | Satış Fiyatı: %s → %s',
                $eskiAdet,
                $yeniAdet,
                fiyatFormatla($eskiFiyat),
                fiyatFormatla($yeniFiyat)
            );
            hareketKaydet($veri['ad'], 'GUNCELLENDI', $degisiklikDetay);

            mesajKaydet('basarili', '"' . $veri['ad'] . '" ürün bilgileri güncellendi.');
            header('Location: index.php');
            exit;
        }
    }
}

$aktifSayfa = 'liste';
$heroBaslik   = 'Ürün Düzenle';
$heroAltMetin = 'Seçili ürünün ad, kod, kategori, fiyat ve stok bilgilerini güncelleyin.';
require 'header.php';
?>

<h2>✏️ Ürün Düzenle: <?= e($mevcutUrun['ad']) ?> (#<?= $id ?>)</h2>

<?php if (!empty($hatalar)): ?>
    <div class="mesaj hata">
        <?= isset($hatalar['genel']) ? e($hatalar['genel']) : 'Güncelleme yapılamadı. Lütfen işaretli alanları kontrol edin.' ?>
    </div>
<?php endif; ?>

<div class="form-kutu genis">
    <form method="post" action="duzenle.php?id=<?= $id ?>" enctype="multipart/form-data" novalidate>
        <?= csrfInput() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-satir-iki">
            <div class="alan <?= isset($hatalar['kod']) ? 'hatali' : '' ?>">
                <label for="kod">Stok Kodu / Barkod (SKU) *</label>
                <input type="text" id="kod" name="kod" value="<?= e($veri['kod']) ?>">
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
                <input type="text" id="ad" name="ad" value="<?= e($veri['ad']) ?>">
                <?php if (isset($hatalar['ad'])): ?><div class="hata-metin"><?= e($hatalar['ad']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['tedarikci']) ? 'hatali' : '' ?>">
                <label for="tedarikci">Tedarikçi Firma</label>
                <input type="text" id="tedarikci" name="tedarikci" value="<?= e($veri['tedarikci']) ?>" placeholder="Örn: TeknoMerkez Bilişim A.Ş.">
                <?php if (isset($hatalar['tedarikci'])): ?><div class="hata-metin"><?= e($hatalar['tedarikci']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="form-satir-iki">
            <div class="alan <?= isset($hatalar['adet']) ? 'hatali' : '' ?>">
                <label for="adet">Stok Adedi *</label>
                <input type="number" id="adet" name="adet" value="<?= e($veri['adet']) ?>" min="0" step="1">
                <?php if (isset($hatalar['adet'])): ?><div class="hata-metin"><?= e($hatalar['adet']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['kritik_stok']) ? 'hatali' : '' ?>">
                <label for="kritik_stok">Kritik Stok Uyarı Sınırı *</label>
                <input type="number" id="kritik_stok" name="kritik_stok" value="<?= e($veri['kritik_stok']) ?>" min="0" step="1">
                <?php if (isset($hatalar['kritik_stok'])): ?><div class="hata-metin"><?= e($hatalar['kritik_stok']) ?></div><?php endif; ?>
            </div>
        </div>

        <?php if (!$yonetici): ?>
            <div class="aciklama" style="margin-bottom: 10px;">
                🔒 <strong>Yetki Bilgisi:</strong> Personel hesabıyla ürün adı, stok ve tedarikçi bilgileri güncellenebilir; alış/satış fiyatları ve KDV yalnızca Yönetici tarafından değiştirilebilir.
            </div>
        <?php endif; ?>

        <div class="form-satir-uc">
            <div class="alan <?= isset($hatalar['alis_fiyati']) ? 'hatali' : '' ?>">
                <label for="alis_fiyati">Birim Alış Maliyeti (₺)</label>
                <input type="text" id="alis_fiyati" name="alis_fiyati" value="<?= e($veri['alis_fiyati']) ?>" <?= !$yonetici ? 'readonly style="opacity:0.7;cursor:not-allowed;"' : '' ?>>
                <?php if (isset($hatalar['alis_fiyati'])): ?><div class="hata-metin"><?= e($hatalar['alis_fiyati']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['fiyat']) ? 'hatali' : '' ?>">
                <label for="fiyat">Birim Satış Fiyatı (₺) *</label>
                <input type="text" id="fiyat" name="fiyat" value="<?= e($veri['fiyat']) ?>" <?= !$yonetici ? 'readonly style="opacity:0.7;cursor:not-allowed;"' : '' ?>>
                <?php if (isset($hatalar['fiyat'])): ?><div class="hata-metin"><?= e($hatalar['fiyat']) ?></div><?php endif; ?>
            </div>

            <div class="alan <?= isset($hatalar['kdv']) ? 'hatali' : '' ?>">
                <label for="kdv">KDV Oranı (%)</label>
                <select id="kdv" name="kdv" <?= !$yonetici ? 'disabled style="opacity:0.7;cursor:not-allowed;"' : '' ?>>
                    <?php foreach ([1, 10, 20, 0] as $oran): ?>
                        <option value="<?= $oran ?>" <?= (int) $veri['kdv'] === $oran ? 'selected' : '' ?>>%<?= $oran ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($hatalar['kdv'])): ?><div class="hata-metin"><?= e($hatalar['kdv']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="alan <?= isset($hatalar['gorsel']) ? 'hatali' : '' ?>">
            <label for="gorsel">Ürün Görseli</label>
            <?php if (!empty($mevcutUrun['gorsel']) && file_exists(__DIR__ . '/' . $mevcutUrun['gorsel'])): ?>
                <div class="mevcut-gorsel-kutu">
                    <img src="<?= e($mevcutUrun['gorsel']) ?>" alt="Mevcut Görsel" class="urun-resim buyuk">
                    <label class="checkbox-etiket">
                        <input type="checkbox" name="gorsel_sil" value="1"> Mevcut görseli kaldır
                    </label>
                </div>
            <?php endif; ?>
            <input type="file" id="gorsel" name="gorsel" accept=".jpg,.jpeg,.png,.webp,.gif">
            <?php if (isset($hatalar['gorsel'])): ?><div class="hata-metin"><?= e($hatalar['gorsel']) ?></div><?php endif; ?>
        </div>

        <div class="alan <?= isset($hatalar['aciklama']) ? 'hatali' : '' ?>">
            <label for="aciklama">Açıklama</label>
            <textarea id="aciklama" name="aciklama" rows="3"><?= e($veri['aciklama']) ?></textarea>
            <?php if (isset($hatalar['aciklama'])): ?><div class="hata-metin"><?= e($hatalar['aciklama']) ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn">💾 Değişiklikleri Kaydet</button>
        <a href="index.php" class="btn gri">İptal</a>
    </form>
</div>

<?php require 'footer.php'; ?>
