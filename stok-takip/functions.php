<?php

session_start();
date_default_timezone_set('Europe/Istanbul');

const VERI_DOSYASI = __DIR__ . '/data/veritabani.json';
const GORSEL_KLASORU = __DIR__ . '/assets/uploads/';

const KATEGORILER = ['Elektronik', 'Kırtasiye', 'Gıda', 'Temizlik', 'Giyim'];

const KRITIK_STOK = 5;

function ornekUrunler(): array
{
    return [
        [
            'id'          => 1,
            'kod'         => 'ELK-001',
            'ad'          => 'Kablosuz Mouse',
            'kategori'    => 'Elektronik',
            'adet'        => 25,
            'kritik_stok' => 5,
            'alis_fiyati' => 210.00,
            'fiyat'       => 349.90,
            'kdv'         => 20,
            'tedarikci'   => 'TeknoMerkez Bilişim A.Ş.',
            'gorsel'      => '',
            'aciklama'    => '2.4 GHz, pil dahil',
            'guncelleme'  => '2026-11-08 10:00',
        ],
        [
            'id'          => 2,
            'kod'         => 'KRT-001',
            'ad'          => 'A4 Fotokopi Kağıdı',
            'kategori'    => 'Kırtasiye',
            'adet'        => 4,
            'kritik_stok' => 10,
            'alis_fiyati' => 130.00,
            'fiyat'       => 189.50,
            'kdv'         => 10,
            'tedarikci'   => 'OfisKağıt Dağıtım Ltd.',
            'gorsel'      => '',
            'aciklama'    => '500 yaprak, 80 gr',
            'guncelleme'  => '2026-11-08 10:15',
        ],
        [
            'id'          => 3,
            'kod'         => 'GDA-001',
            'ad'          => 'Filtre Kahve 500g',
            'kategori'    => 'Gıda',
            'adet'        => 0,
            'kritik_stok' => 5,
            'alis_fiyati' => 185.00,
            'fiyat'       => 275.00,
            'kdv'         => 1,
            'tedarikci'   => 'Anadolu Kahve Kavurma',
            'gorsel'      => '',
            'aciklama'    => 'Orta kavrulmuş, %100 Arabica',
            'guncelleme'  => '2026-11-08 11:00',
        ],
        [
            'id'          => 4,
            'kod'         => 'TMZ-001',
            'ad'          => 'Bulaşık Deterjanı',
            'kategori'    => 'Temizlik',
            'adet'        => 12,
            'kritik_stok' => 5,
            'alis_fiyati' => 55.00,
            'fiyat'       => 89.90,
            'kdv'         => 20,
            'tedarikci'   => 'PakHijyen Kimya',
            'gorsel'      => '',
            'aciklama'    => '1,5 litre limon kokulu',
            'guncelleme'  => '2026-11-08 11:30',
        ],
        [
            'id'          => 5,
            'kod'         => 'ELK-002',
            'ad'          => 'USB-C Şarj Kablosu',
            'kategori'    => 'Elektronik',
            'adet'        => 3,
            'kritik_stok' => 5,
            'alis_fiyati' => 65.00,
            'fiyat'       => 129.00,
            'kdv'         => 20,
            'tedarikci'   => 'TeknoMerkez Bilişim A.Ş.',
            'gorsel'      => '',
            'aciklama'    => '1 metre, 60W hızlı şarj destekli',
            'guncelleme'  => '2026-11-08 12:00',
        ],
        [
            'id'          => 6,
            'kod'         => 'GYM-001',
            'ad'          => 'Pamuklu Tişört',
            'kategori'    => 'Giyim',
            'adet'        => 18,
            'kritik_stok' => 6,
            'alis_fiyati' => 140.00,
            'fiyat'       => 249.99,
            'kdv'         => 10,
            'tedarikci'   => 'Ege Tekstil Toptan',
            'gorsel'      => '',
            'aciklama'    => 'M beden, %100 pamuk beyaz',
            'guncelleme'  => '2026-11-08 12:30',
        ],
    ];
}

function varsayilanKullanicilar(): array
{
    return [
        [
            'kullanici_adi' => 'admin',
            'sifre'         => '1234',
            'ad_soyad'      => 'Mağaza Yöneticisi',
            'rol'           => 'admin',
        ],
        [
            'kullanici_adi' => 'personel',
            'sifre'         => '1234',
            'ad_soyad'      => 'Depo Görevlisi',
            'rol'           => 'personel',
        ],
    ];
}

function ornekHareketler(): array
{
    return [
        [
            'id'        => 1,
            'tarih'     => '2026-11-08 10:00:00',
            'kullanici' => 'Mağaza Yöneticisi',
            'urun_ad'   => 'Sistem Başlangıç Verileri',
            'tur'       => 'SISTEM',
            'detay'     => '6 adet örnek ürün yerel veri tabanına yüklendi.',
        ],
    ];
}

function varsayilanVeritabani(): array
{
    return [
        'urunler'            => ornekUrunler(),
        'kategoriler'        => KATEGORILER,
        'hareketler'         => ornekHareketler(),
        'kullanicilar'       => varsayilanKullanicilar(),
        'sonraki_id'         => 7,
        'sonraki_hareket_id' => 2,
    ];
}

function yerelVeriyiYukle(): void
{
    $klasor = dirname(VERI_DOSYASI);
    if (!is_dir($klasor)) {
        mkdir($klasor, 0777, true);
    }
    if (!is_dir(GORSEL_KLASORU)) {
        mkdir(GORSEL_KLASORU, 0777, true);
    }

    if (!file_exists(VERI_DOSYASI)) {
        $varsayilan = varsayilanVeritabani();
        file_put_contents(
            VERI_DOSYASI,
            json_encode($varsayilan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    $icerik = file_get_contents(VERI_DOSYASI);
    $veri = json_decode((string) $icerik, true);

    if (!is_array($veri) || !isset($veri['urunler'])) {
        $veri = varsayilanVeritabani();
    }

    $ornekTedarikciler = [
        1 => 'TeknoMerkez Bilişim A.Ş.',
        2 => 'OfisKağıt Dağıtım Ltd.',
        3 => 'Anadolu Kahve Kavurma',
        4 => 'PakHijyen Kimya',
        5 => 'TeknoMerkez Bilişim A.Ş.',
        6 => 'Ege Tekstil Toptan',
    ];
    foreach ($veri['urunler'] as &$u) {
        $uid = (int) ($u['id'] ?? 0);
        $u['kod']         = $u['kod'] ?? ('URN-' . str_pad((string) $uid, 3, '0', STR_PAD_LEFT));
        $u['kritik_stok'] = isset($u['kritik_stok']) ? (int) $u['kritik_stok'] : KRITIK_STOK;
        $u['alis_fiyati'] = isset($u['alis_fiyati']) ? (float) $u['alis_fiyati'] : round($u['fiyat'] * 0.65, 2);
        $u['kdv']         = isset($u['kdv']) ? (int) $u['kdv'] : 20;
        $u['tedarikci']   = $u['tedarikci'] ?? ($ornekTedarikciler[$uid] ?? '');
        $u['gorsel']      = $u['gorsel'] ?? '';
        $u['guncelleme']  = $u['guncelleme'] ?? date('Y-m-d H:i');
    }
    unset($u);

    $_SESSION['urunler']            = $veri['urunler'];
    $_SESSION['kategoriler']        = $veri['kategoriler'] ?? KATEGORILER;
    $_SESSION['hareketler']         = $veri['hareketler'] ?? ornekHareketler();
    $_SESSION['sonraki_id']         = $veri['sonraki_id'] ?? 7;
    $_SESSION['sonraki_hareket_id'] = $veri['sonraki_hareket_id'] ?? 2;

    $kayitliKullanicilar = is_array($veri['kullanicilar'] ?? null) ? $veri['kullanicilar'] : [];
    $mevcutAdlar = [];
    foreach ($kayitliKullanicilar as $kk) {
        if (!empty($kk['kullanici_adi'])) {
            $mevcutAdlar[mb_strtolower((string) $kk['kullanici_adi'], 'UTF-8')] = true;
        }
    }
    foreach (varsayilanKullanicilar() as $vk) {
        $anahtar = mb_strtolower($vk['kullanici_adi'], 'UTF-8');
        if (!isset($mevcutAdlar[$anahtar])) {
            $kayitliKullanicilar[] = $vk;
        }
    }
    $_SESSION['kullanicilar'] = array_values($kayitliKullanicilar);
}

function yerelVeriyiKaydet(): void
{
    $klasor = dirname(VERI_DOSYASI);
    if (!is_dir($klasor)) {
        mkdir($klasor, 0777, true);
    }

    $veri = [
        'urunler'            => array_values($_SESSION['urunler'] ?? []),
        'kategoriler'        => array_values($_SESSION['kategoriler'] ?? KATEGORILER),
        'hareketler'         => array_values($_SESSION['hareketler'] ?? []),
        'kullanicilar'       => array_values($_SESSION['kullanicilar'] ?? varsayilanKullanicilar()),
        'sonraki_id'         => $_SESSION['sonraki_id'] ?? 1,
        'sonraki_hareket_id' => $_SESSION['sonraki_hareket_id'] ?? 1,
    ];

    file_put_contents(
        VERI_DOSYASI,
        json_encode($veri, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function verileriSifirla(): void
{
    $varsayilan = varsayilanVeritabani();
    $_SESSION['urunler']            = $varsayilan['urunler'];
    $_SESSION['kategoriler']        = $varsayilan['kategoriler'];
    $_SESSION['hareketler']         = $varsayilan['hareketler'];
    $_SESSION['kullanicilar']       = $varsayilan['kullanicilar'];
    $_SESSION['sonraki_id']         = $varsayilan['sonraki_id'];
    $_SESSION['sonraki_hareket_id'] = $varsayilan['sonraki_hareket_id'];
    hareketKaydet('Sistem', 'SIFIRLANDI', 'Tüm veriler varsayılan örnek ürünlere sıfırlandı.');
    yerelVeriyiKaydet();
}

yerelVeriyiYukle();

if (
    !isset($_SESSION['kullanici']) ||
    !is_array($_SESSION['kullanici']) ||
    empty($_SESSION['kullanici']['rol']) ||
    empty($_SESSION['kullanici']['ad_soyad'])
) {
    $_SESSION['kullanici'] = varsayilanKullanicilar()[0];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

function aktifKullanici(): array
{
    $varsayilan = varsayilanKullanicilar()[0];
    $oturumKullanici = is_array($_SESSION['kullanici'] ?? null) ? $_SESSION['kullanici'] : [];

    return [
        'kullanici_adi' => $oturumKullanici['kullanici_adi'] ?? $varsayilan['kullanici_adi'],
        'ad_soyad'      => $oturumKullanici['ad_soyad'] ?? $varsayilan['ad_soyad'],
        'rol'           => $oturumKullanici['rol'] ?? $varsayilan['rol'],
    ];
}

function yoneticiMi(): bool
{
    return (aktifKullanici()['rol'] ?? '') === 'admin';
}

function kullanicilariGetir(): array
{
    return $_SESSION['kullanicilar'] ?? varsayilanKullanicilar();
}

function kategorileriGetir(): array
{
    return $_SESSION['kategoriler'] ?? KATEGORILER;
}

function kategoriYoksaEkle(string $kategoriAdi): string
{
    $kategoriAdi = trim($kategoriAdi);
    if ($kategoriAdi === '') {
        return '';
    }

    $mevcutlar = kategorileriGetir();
    foreach ($mevcutlar as $kat) {
        if (mb_strtolower($kat, 'UTF-8') === mb_strtolower($kategoriAdi, 'UTF-8')) {
            return $kat;
        }
    }

    $_SESSION['kategoriler'][] = $kategoriAdi;
    hareketKaydet($kategoriAdi, 'KATEGORI', 'Dışarıdan yeni kategori eklendi: ' . $kategoriAdi);
    yerelVeriyiKaydet();
    return $kategoriAdi;
}

function csrfInput(): string
{
    $token = e($_SESSION['csrf_token'] ?? '');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrfDogrula(?string $gelenToken): bool
{
    if (empty($gelenToken) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $gelenToken);
}

function hareketKaydet(string $urunAd, string $tur, string $detay): void
{
    $kullanici = aktifKullanici()['ad_soyad'] ?? 'Sistem';
    $yeniHareket = [
        'id'        => $_SESSION['sonraki_hareket_id'] ?? 1,
        'tarih'     => date('Y-m-d H:i:s'),
        'kullanici' => $kullanici,
        'urun_ad'   => $urunAd,
        'tur'       => $tur,
        'detay'     => $detay,
    ];

    array_unshift($_SESSION['hareketler'], $yeniHareket);
    $_SESSION['sonraki_hareket_id'] = ($_SESSION['sonraki_hareket_id'] ?? 1) + 1;
    yerelVeriyiKaydet();
}

function e($metin): string
{
    return htmlspecialchars((string) $metin, ENT_QUOTES, 'UTF-8');
}

function stokDurumu(int $adet, int $kritikSinir = KRITIK_STOK): string
{
    if ($adet <= 0) {
        return 'Tükendi';
    } elseif ($adet <= $kritikSinir) {
        return 'Azaldı';
    }
    return 'Stokta';
}

function durumSinifi(string $durum): string
{
    $siniflar = ['Stokta' => 'stokta', 'Azaldı' => 'azaldi', 'Tükendi' => 'tukendi'];
    return $siniflar[$durum] ?? 'stokta';
}

function fiyatFormatla(float $fiyat): string
{
    return number_format($fiyat, 2, ',', '.') . ' ₺';
}

function kategoriIkonu(string $kategori): string
{
    $ikonlar = [
        'Elektronik' => '🔌',
        'Kırtasiye'  => '📎',
        'Gıda'       => '☕',
        'Temizlik'   => '🧼',
        'Giyim'      => '👕',
    ];
    return $ikonlar[$kategori] ?? '📦';
}

function urunBul(int $id): ?array
{
    foreach ($_SESSION['urunler'] as $indeks => $urun) {
        if ((int) $urun['id'] === $id) {
            return ['indeks' => $indeks, 'urun' => $urun];
        }
    }
    return null;
}

function filtreAdresi(string $kategori, string $durum, string $ara = '', string $sirala = ''): string
{
    $parametreler = [];
    if ($ara !== '') {
        $parametreler['ara'] = $ara;
    }
    if ($kategori !== '') {
        $parametreler['kategori'] = $kategori;
    }
    if ($durum !== '') {
        $parametreler['durum'] = $durum;
    }
    if ($sirala !== '') {
        $parametreler['sirala'] = $sirala;
    }
    $sorgu = http_build_query($parametreler);
    return 'index.php' . ($sorgu !== '' ? '?' . $sorgu : '');
}

function turkceKarsilastir(string $a, string $b): int
{
    $harita = [
        'Ç' => 'C~', 'ç' => 'c~',
        'Ğ' => 'G~', 'ğ' => 'g~',
        'I' => 'H~', 'ı' => 'h~',
        'İ' => 'I~', 'i' => 'i~',
        'Ö' => 'O~', 'ö' => 'o~',
        'Ş' => 'S~', 'ş' => 's~',
        'Ü' => 'U~', 'ü' => 'u~',
    ];
    $aDuz = strtr(mb_strtolower($a, 'UTF-8'), $harita);
    $bDuz = strtr(mb_strtolower($b, 'UTF-8'), $harita);
    return strcmp($aDuz, $bDuz);
}

function urunleriFiltrele(
    array $urunler,
    string $kategori = '',
    string $durum = '',
    string $ara = '',
    string $sirala = ''
): array {
    $sonuc = [];
    $araKucuk = mb_strtolower(trim($ara), 'UTF-8');

    foreach ($urunler as $urun) {
        $kritik = (int) ($urun['kritik_stok'] ?? KRITIK_STOK);
        $urunDurumu = stokDurumu((int) $urun['adet'], $kritik);

        if ($kategori !== '' && $urun['kategori'] !== $kategori) {
            continue;
        }
        if ($durum !== '' && $urunDurumu !== $durum) {
            continue;
        }
        if ($araKucuk !== '') {
            $metin = mb_strtolower(
                ($urun['kod'] ?? '') . ' ' . $urun['ad'] . ' ' . ($urun['aciklama'] ?? '') . ' ' . $urun['kategori'] . ' ' . ($urun['tedarikci'] ?? ''),
                'UTF-8'
            );
            if (mb_strpos($metin, $araKucuk) === false) {
                continue;
            }
        }
        $sonuc[] = $urun;
    }

    if ($sirala !== '') {
        usort($sonuc, function (array $a, array $b) use ($sirala): int {
            return match ($sirala) {
                'ad_asc'     => turkceKarsilastir($a['ad'], $b['ad']),
                'ad_desc'    => turkceKarsilastir($b['ad'], $a['ad']),
                'fiyat_asc'  => $a['fiyat'] <=> $b['fiyat'],
                'fiyat_desc' => $b['fiyat'] <=> $a['fiyat'],
                'adet_asc'   => $a['adet'] <=> $b['adet'],
                'adet_desc'  => $b['adet'] <=> $a['adet'],
                'deger_desc' => ($b['adet'] * $b['fiyat']) <=> ($a['adet'] * $a['fiyat']),
                'kar_desc'   => (($b['fiyat'] - ($b['alis_fiyati'] ?? 0)) * $b['adet']) <=> (($a['fiyat'] - ($a['alis_fiyati'] ?? 0)) * $a['adet']),
                default      => $a['id'] <=> $b['id'],
            };
        });
    }

    return $sonuc;
}

function sayaclariHesapla(array $urunler): array
{
    $sayac = [
        'toplam'       => count($urunler),
        'toplam_adet'  => 0,
        'Stokta'       => 0,
        'Azaldı'       => 0,
        'Tükendi'      => 0,
        'maliyet'      => 0.0,
        'deger'        => 0.0,
        'net_kar'      => 0.0,
        'kdv_toplam'   => 0.0,
    ];

    foreach ($urunler as $urun) {
        $adet       = (int) $urun['adet'];
        $kritik     = (int) ($urun['kritik_stok'] ?? KRITIK_STOK);
        $satisFiyat = (float) $urun['fiyat'];
        $alisFiyat  = (float) ($urun['alis_fiyati'] ?? ($satisFiyat * 0.65));
        $kdvOran    = (int) ($urun['kdv'] ?? 20);

        $durum = stokDurumu($adet, $kritik);
        $sayac[$durum]++;

        $sayac['toplam_adet'] += $adet;
        $sayac['maliyet']     += $adet * $alisFiyat;
        $sayac['deger']       += $adet * $satisFiyat;
        $sayac['net_kar']     += $adet * ($satisFiyat - $alisFiyat);
        $sayac['kdv_toplam']  += $adet * ($satisFiyat * ($kdvOran / (100 + $kdvOran)));
    }

    return $sayac;
}

function urunDogrula(array $veri, ?int $duzenlenenId = null): array
{
    $hatalar = [];
    $mevcutKategoriler = kategorileriGetir();

    $kod = trim($veri['kod'] ?? '');
    if ($kod === '') {
        $hatalar['kod'] = 'Stok kodu (barkod) boş bırakılamaz.';
    } elseif (mb_strlen($kod) < 2 || mb_strlen($kod) > 30) {
        $hatalar['kod'] = 'Stok kodu 2 ile 30 karakter arasında olmalıdır.';
    } else {
        foreach ($_SESSION['urunler'] as $u) {
            if ($duzenlenenId !== null && (int) $u['id'] === $duzenlenenId) {
                continue;
            }
            if (mb_strtolower($u['kod'] ?? '', 'UTF-8') === mb_strtolower($kod, 'UTF-8')) {
                $hatalar['kod'] = 'Bu stok kodu (' . $kod . ') başka bir üründe zaten kullanılıyor.';
                break;
            }
        }
    }

    if ($veri['ad'] === '') {
        $hatalar['ad'] = 'Ürün adı boş bırakılamaz.';
    } elseif (mb_strlen($veri['ad']) < 2) {
        $hatalar['ad'] = 'Ürün adı en az 2 karakter olmalıdır.';
    } elseif (mb_strlen($veri['ad']) > 100) {
        $hatalar['ad'] = 'Ürün adı en fazla 100 karakter olabilir.';
    }

    if ($veri['kategori'] === '' || $veri['kategori'] === '__yeni__') {
        $hatalar['kategori'] = 'Lütfen bir kategori seçin veya yeni kategori adı yazın.';
    } elseif (mb_strlen($veri['kategori']) < 2 || mb_strlen($veri['kategori']) > 40) {
        $hatalar['kategori'] = 'Kategori adı 2 ile 40 karakter arasında olmalıdır.';
    }

    if ($veri['adet'] === '') {
        $hatalar['adet'] = 'Adet boş bırakılamaz.';
    } elseif (filter_var($veri['adet'], FILTER_VALIDATE_INT) === false || (int) $veri['adet'] < 0) {
        $hatalar['adet'] = 'Adet 0 veya daha büyük bir tam sayı olmalıdır.';
    }

    $kritik = $veri['kritik_stok'] ?? (string) KRITIK_STOK;
    if ($kritik === '' || filter_var($kritik, FILTER_VALIDATE_INT) === false || (int) $kritik < 0) {
        $hatalar['kritik_stok'] = 'Kritik stok sınırı 0 veya daha büyük bir tam sayı olmalıdır.';
    }

    $alisFiyati = str_replace(',', '.', $veri['alis_fiyati'] ?? '0');
    if ($alisFiyati === '' || !is_numeric($alisFiyati) || (float) $alisFiyati < 0) {
        $hatalar['alis_fiyati'] = 'Alış maliyeti 0 veya daha büyük bir sayı olmalıdır.';
    }

    $fiyat = str_replace(',', '.', $veri['fiyat']);
    if ($veri['fiyat'] === '') {
        $hatalar['fiyat'] = 'Satış fiyatı boş bırakılamaz.';
    } elseif (!is_numeric($fiyat) || (float) $fiyat <= 0) {
        $hatalar['fiyat'] = 'Satış fiyatı 0\'dan büyük bir sayı olmalıdır.';
    }

    $kdv = $veri['kdv'] ?? '20';
    if (!in_array((int) $kdv, [0, 1, 10, 20], true)) {
        $hatalar['kdv'] = 'Geçersiz KDV oranı seçildi.';
    }

    if (mb_strlen($veri['tedarikci'] ?? '') > 80) {
        $hatalar['tedarikci'] = 'Tedarikçi adı en fazla 80 karakter olabilir.';
    }

    if (mb_strlen($veri['aciklama']) > 255) {
        $hatalar['aciklama'] = 'Açıklama en fazla 255 karakter olabilir.';
    }

    return $hatalar;
}

function gorselDosyasiSil(?string $gorselYolu): void
{
    if (empty($gorselYolu)) {
        return;
    }
    if (strpos($gorselYolu, 'assets/uploads/') !== 0) {
        return;
    }
    $tamYol = __DIR__ . '/' . $gorselYolu;
    if (is_file($tamYol)) {
        @unlink($tamYol);
    }
}

function guvenliGeriAdres(string $varsayilan = 'index.php'): string
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref === '') {
        return $varsayilan;
    }
    $parcalar = parse_url($ref);
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!empty($parcalar['host']) && $parcalar['host'] !== $host) {
        return $varsayilan;
    }
    $dosya = basename($parcalar['path'] ?? '');
    $izinliler = ['index.php', 'raporlar.php', 'kategoriler.php', 'hareketler.php'];
    if (!in_array($dosya, $izinliler, true)) {
        return $varsayilan;
    }
    $sorgu = isset($parcalar['query']) && $parcalar['query'] !== '' ? '?' . $parcalar['query'] : '';
    return $dosya . $sorgu;
}

function gorselYukle(array $dosya): array
{
    if (empty($dosya['name']) || $dosya['error'] === UPLOAD_ERR_NO_FILE) {
        return ['hata' => null, 'yol' => ''];
    }

    if ($dosya['error'] !== UPLOAD_ERR_OK) {
        return ['hata' => 'Görsel yüklenirken bir hata oluştu.', 'yol' => ''];
    }

    if ($dosya['size'] > 2 * 1024 * 1024) {
        return ['hata' => 'Görsel boyutu en fazla 2 MB olabilir.', 'yol' => ''];
    }

    $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
    $izinliUzantilar = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($uzanti, $izinliUzantilar, true)) {
        return ['hata' => 'Sadece JPG, PNG, WEBP veya GIF formatında görsel yüklenebilir.', 'yol' => ''];
    }

    if (!is_dir(GORSEL_KLASORU)) {
        mkdir(GORSEL_KLASORU, 0777, true);
    }

    $yeniAd = 'urun_' . uniqid() . '.' . $uzanti;
    $hedefYol = GORSEL_KLASORU . $yeniAd;

    if (!move_uploaded_file($dosya['tmp_name'], $hedefYol)) {
        return ['hata' => 'Görsel klasöre kaydedilemedi.', 'yol' => ''];
    }

    return ['hata' => null, 'yol' => 'assets/uploads/' . $yeniAd];
}

function mesajKaydet(string $tur, string $metin): void
{
    $_SESSION['mesaj'] = ['tur' => $tur, 'metin' => $metin];
}

function mesajAl(): ?array
{
    if (!isset($_SESSION['mesaj'])) {
        return null;
    }
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
    return $mesaj;
}
