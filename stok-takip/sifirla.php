<?php

require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrfDogrula($_POST['csrf_token'] ?? null) || !yoneticiMi()) {
    mesajKaydet('hata', 'Sıfırlama işlemi için Yönetici yetkisi gereklidir.');
    header('Location: index.php');
    exit;
}

verileriSifirla();
mesajKaydet('basarili', 'Tüm veriler başlangıçtaki 6 örnek ürüne sıfırlandı.');

header('Location: index.php');
exit;
