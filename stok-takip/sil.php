<?php

require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrfDogrula($_POST['csrf_token'] ?? null)) {
    mesajKaydet('hata', 'Güvenlik doğrulaması (CSRF) başarısız oldu.');
    header('Location: index.php');
    exit;
}

if (!yoneticiMi()) {
    mesajKaydet('hata', 'Ürün silme işlemi için Yönetici yetkisi gereklidir.');
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$silindi = false;
$silinenAd = '';
$silinenKod = '';
$silinenGorsel = '';

foreach ($_SESSION['urunler'] as $sira => $urun) {
    if ((int) $urun['id'] === $id) {
        $silinenAd     = $urun['ad'];
        $silinenKod    = $urun['kod'] ?? '';
        $silinenGorsel = $urun['gorsel'] ?? '';
        unset($_SESSION['urunler'][$sira]);
        $silindi = true;
        break;
    }
}

if ($silindi) {

    gorselDosyasiSil($silinenGorsel);

    $_SESSION['urunler'] = array_values($_SESSION['urunler']);
    hareketKaydet($silinenAd, 'SILINDI', 'Ürün envanterden silindi (Kod: ' . $silinenKod . ').');
    mesajKaydet('basarili', '"' . $silinenAd . '" başarıyla silindi.');
} else {
    mesajKaydet('hata', 'Silinecek ürün bulunamadı.');
}

header('Location: index.php');
exit;
