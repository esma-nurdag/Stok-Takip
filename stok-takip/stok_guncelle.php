<?php

require_once 'functions.php';

$ajaxMi = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrfDogrula($_POST['csrf_token'] ?? null)) {
    if ($ajaxMi) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['basarili' => false, 'mesaj' => 'Güvenlik doğrulaması başarısız oldu.']);
        exit;
    }
    mesajKaydet('hata', 'Güvenlik doğrulaması başarısız oldu.');
    header('Location: index.php');
    exit;
}

$id      = (int) ($_POST['id'] ?? 0);
$degisim = (int) ($_POST['degisim'] ?? 0);

$bulunan = urunBul($id);
if ($bulunan === null || $degisim === 0) {
    if ($ajaxMi) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['basarili' => false, 'mesaj' => 'Geçersiz stok güncelleme isteği.']);
        exit;
    }
    mesajKaydet('hata', 'Geçersiz stok güncelleme isteği.');
    header('Location: index.php');
    exit;
}

$indeks   = $bulunan['indeks'];
$urun     = $bulunan['urun'];
$eskiAdet = (int) $urun['adet'];
$yeniAdet = max(0, $eskiAdet + $degisim);
$gercekDegisim = $yeniAdet - $eskiAdet;

$_SESSION['urunler'][$indeks]['adet']       = $yeniAdet;
$_SESSION['urunler'][$indeks]['guncelleme'] = date('Y-m-d H:i');

$islemTuru = $degisim > 0 ? 'STOK_ARTIS' : 'STOK_AZALIS';
$detay     = sprintf('Stok adedi %d → %d olarak güncellendi (%+d).', $eskiAdet, $yeniAdet, $gercekDegisim);

hareketKaydet($urun['ad'], $islemTuru, $detay);

$bildirimMetni = '"' . $urun['ad'] . '" stoğu güncellendi (' . $eskiAdet . ' → ' . $yeniAdet . ').';

if ($ajaxMi) {
    $kritikSinir = (int) ($urun['kritik_stok'] ?? KRITIK_STOK);
    $yeniDurum   = stokDurumu($yeniAdet, $kritikSinir);
    $durumSinif  = durumSinifi($yeniDurum);
    $alisFiyati  = (float) ($urun['alis_fiyati'] ?? 0);
    $satisFiyati = (float) $urun['fiyat'];
    $satirDeger  = $yeniAdet * $satisFiyati;
    $satirKar    = $yeniAdet * ($satisFiyati - $alisFiyati);
    $sayac       = sayaclariHesapla($_SESSION['urunler']);
    $karMarji    = $sayac['maliyet'] > 0 ? round(($sayac['net_kar'] / $sayac['maliyet']) * 100, 1) : 0;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'basarili'   => true,
        'mesaj'      => $bildirimMetni,
        'urun'       => [
            'id'         => $id,
            'adet'       => $yeniAdet,
            'durum'      => $yeniDurum,
            'durumSinif' => $durumSinif,
            'satirDeger' => fiyatFormatla($satirDeger),
            'satirKar'   => 'Net Kâr: ' . fiyatFormatla($satirKar),
        ],
        'sayac'      => [
            'toplam'      => $sayac['toplam'],
            'toplam_adet' => $sayac['toplam_adet'],
            'Stokta'      => $sayac['Stokta'],
            'Azaldi'      => $sayac['Azaldı'],
            'Tukendi'     => $sayac['Tükendi'],
            'maliyet'     => fiyatFormatla($sayac['maliyet']),
            'deger'       => fiyatFormatla($sayac['deger']),
            'net_kar'     => fiyatFormatla($sayac['net_kar']),
            'kar_marji'   => $karMarji,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

mesajKaydet('basarili', $bildirimMetni);

header('Location: ' . guvenliGeriAdres('index.php'));
exit;
