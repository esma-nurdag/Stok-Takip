<?php

require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['islem'] ?? '') === 'temizle') {
    if (csrfDogrula($_POST['csrf_token'] ?? null) && yoneticiMi()) {
        $_SESSION['hareketler'] = [];
        hareketKaydet('Sistem', 'SISTEM', 'Stok hareket geçmişi yönetici tarafından temizlendi.');
        yerelVeriyiKaydet();
        mesajKaydet('basarili', 'Stok hareket geçmişi temizlendi.');
    }
    header('Location: hareketler.php');
    exit;
}

$seciliTur   = trim($_GET['tur'] ?? '');
$seciliTarih = trim($_GET['tarih'] ?? '');
$arama       = mb_strtolower(trim($_GET['ara'] ?? ''), 'UTF-8');

$tumHareketler = $_SESSION['hareketler'] ?? [];
$hareketler = [];

foreach ($tumHareketler as $h) {
    if ($seciliTur !== '' && ($h['tur'] ?? '') !== $seciliTur) {
        continue;
    }
    if ($seciliTarih !== '' && strpos($h['tarih'] ?? '', $seciliTarih) !== 0) {
        continue;
    }
    if ($arama !== '') {
        $metin = mb_strtolower(($h['urun_ad'] ?? '') . ' ' . ($h['detay'] ?? '') . ' ' . ($h['kullanici'] ?? ''), 'UTF-8');
        if (mb_strpos($metin, $arama) === false) {
            continue;
        }
    }
    $hareketler[] = $h;
}

$mesaj = mesajAl();
$aktifSayfa = 'hareketler';
$heroBaslik   = 'Stok Hareketleri';
$heroAltMetin = 'Ürün gir-çıkan kayıtları: her stok değişiminin zaman çizelgesi halinde dökümü.';
require 'header.php';
?>

<div class="baslik-satiri">
    <h2>🕒 Stok Hareket Geçmişi (Denetim Kayıtları)</h2>
    <?php if (yoneticiMi() && count($tumHareketler) > 1): ?>
        <form method="post" action="hareketler.php" class="yazdirma-gizle"
              onsubmit="return confirm('Tüm hareket geçmişini temizlemek istediğinize emin misiniz?');">
            <?= csrfInput() ?>
            <input type="hidden" name="islem" value="temizle">
            <button type="submit" class="btn sil">🧹 Geçmişi Temizle</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($mesaj): ?>
    <div class="mesaj <?= e($mesaj['tur']) ?>"><?= e($mesaj['metin']) ?></div>
<?php endif; ?>

<form method="get" action="hareketler.php" class="filtre">
    <label class="filtre-genis">Ürün veya Detay Ara
        <input type="text" name="ara" value="<?= e($_GET['ara'] ?? '') ?>" placeholder="Ürün adı, kullanıcı veya işlem detayı...">
    </label>
    <label>İşlem Türü
        <select name="tur">
            <option value="">Tümü</option>
            <?php foreach (['EKLENDI', 'GUNCELLENDI', 'STOK_ARTIS', 'STOK_AZALIS', 'SILINDI', 'KATEGORI', 'SIFIRLANDI', 'SISTEM'] as $t): ?>
                <option value="<?= $t ?>" <?= $seciliTur === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Tarih
        <input type="date" name="tarih" value="<?= e($seciliTarih) ?>">
    </label>
    <button type="submit" class="btn">Filtrele</button>
    <a href="hareketler.php" class="btn gri">Temizle</a>
</form>

<div class="tablo-kutu">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tarih / Saat</th>
                <th>Kullanıcı</th>
                <th>Ürün / Konu</th>
                <th>İşlem Türü</th>
                <th>Açıklama / Değişiklik Detayı</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($hareketler) === 0): ?>
                <tr><td colspan="6" class="bos">Kayıtlı stok hareketi bulunamadı.</td></tr>
            <?php endif; ?>

            <?php foreach ($hareketler as $h): ?>
                <?php
                $rozetSinif = match ($h['tur'] ?? '') {
                    'EKLENDI', 'STOK_ARTIS' => 'stokta',
                    'GUNCELLENDI', 'KATEGORI', 'STOK_AZALIS' => 'azaldi',
                    'SILINDI', 'SIFIRLANDI' => 'tukendi',
                    default => 'stokta',
                };
                ?>
                <tr>
                    <td>#<?= (int) ($h['id'] ?? 0) ?></td>
                    <td><?= e($h['tarih'] ?? '') ?></td>
                    <td>👤 <?= e($h['kullanici'] ?? 'Sistem') ?></td>
                    <td><strong><?= e($h['urun_ad'] ?? '') ?></strong></td>
                    <td><span class="rozet <?= $rozetSinif ?>"><?= e($h['tur'] ?? '') ?></span></td>
                    <td><?= e($h['detay'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'footer.php'; ?>
