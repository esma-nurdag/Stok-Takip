<?php

require_once 'functions.php';

$urunler = $_SESSION['urunler'];
$sayac   = sayaclariHesapla($urunler);
$kategoriler = kategorileriGetir();

$kategoriAnaliz = [];
foreach ($kategoriler as $kat) {
    $kategoriAnaliz[$kat] = [
        'cesit'   => 0,
        'adet'    => 0,
        'maliyet' => 0.0,
        'deger'   => 0.0,
        'kar'     => 0.0,
    ];
}

$kritikUrunler = [];
foreach ($urunler as $u) {
    $kat        = $u['kategori'];
    $adet       = (int) $u['adet'];
    $kritik     = (int) ($u['kritik_stok'] ?? KRITIK_STOK);
    $alis       = (float) ($u['alis_fiyati'] ?? 0);
    $satis      = (float) $u['fiyat'];

    if (!isset($kategoriAnaliz[$kat])) {
        $kategoriAnaliz[$kat] = ['cesit' => 0, 'adet' => 0, 'maliyet' => 0.0, 'deger' => 0.0, 'kar' => 0.0];
    }

    $kategoriAnaliz[$kat]['cesit']++;
    $kategoriAnaliz[$kat]['adet']    += $adet;
    $kategoriAnaliz[$kat]['maliyet'] += $adet * $alis;
    $kategoriAnaliz[$kat]['deger']   += $adet * $satis;
    $kategoriAnaliz[$kat]['kar']     += $adet * ($satis - $alis);

    if ($adet <= $kritik) {
        $kritikUrunler[] = $u;
    }
}

$degerSirali = $urunler;
usort($degerSirali, fn($a, $b) => ($b['adet'] * $b['fiyat']) <=> ($a['adet'] * $a['fiyat']));
$enDegerliBes = array_slice($degerSirali, 0, 5);

$aktifSayfa = 'raporlar';
$heroBaslik   = 'Raporlar & Analiz';
$heroAltMetin = 'Maliyet-kâr dökümü, kritik stok sipariş listesi ve en değerli ürünlerin özet raporu.';
require 'header.php';
?>

<h2>📊 Raporlar, Finansal Analiz ve Grafiksel Dağılım</h2>

<section class="sayaclar">
    <div class="kart maliyet">
        <span>Toplam Stok Alış Maliyeti</span>
        <strong><?= fiyatFormatla($sayac['maliyet']) ?></strong>
        <small><?= $sayac['toplam_adet'] ?> birim ürün için</small>
    </div>
    <div class="kart deger">
        <span>Toplam Stok Satış Değeri</span>
        <strong><?= fiyatFormatla($sayac['deger']) ?></strong>
        <small>Tüm stok satıldığında ciro</small>
    </div>
    <div class="kart kar">
        <span>Beklenen Net Brüt Kâr</span>
        <strong><?= fiyatFormatla($sayac['net_kar']) ?></strong>
        <small>Satış Değeri − Alış Maliyeti</small>
    </div>
    <div class="kart stokta">
        <span>İçerdiği Toplam KDV</span>
        <strong><?= fiyatFormatla($sayac['kdv_toplam']) ?></strong>
        <small>Ürün KDV oranlarına göre</small>
    </div>
</section>

<div class="rapor-bolum">
    <h3>📈 Kategori Bazlı Stok Değeri Dağılımı</h3>
    <div class="tablo-kutu">
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Ürün Çeşidi</th>
                    <th>Birim Adet</th>
                    <th>Alış Maliyeti</th>
                    <th>Satış Değeri</th>
                    <th>Net Kâr</th>
                    <th>Pay (%) &amp; Görsel Dağılım</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategoriAnaliz as $katAdi => $analiz): ?>
                    <?php
                    $yuzde = $sayac['deger'] > 0 ? round(($analiz['deger'] / $sayac['deger']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?= kategoriIkonu($katAdi) ?> <?= e($katAdi) ?></strong></td>
                        <td><?= $analiz['cesit'] ?></td>
                        <td><?= $analiz['adet'] ?></td>
                        <td><?= fiyatFormatla($analiz['maliyet']) ?></td>
                        <td><strong><?= fiyatFormatla($analiz['deger']) ?></strong></td>
                        <td class="kar-metin"><?= fiyatFormatla($analiz['kar']) ?></td>
                        <td style="min-width: 190px;">
                            <div class="bar-kapsayici">
                                <div class="bar-dolgu" style="width: <?= min(100, $yuzde) ?>%;"></div>
                            </div>
                            <small>%<?= $yuzde ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="rapor-bolum" id="kritik-liste">
    <h3>🚨 Acil Sipariş Verilmesi Gereken Ürünler (Kritik &amp; Tükenenler)</h3>
    <div class="tablo-kutu">
        <table>
            <thead>
                <tr>
                    <th>Kod / Ürün Adı</th>
                    <th>Kategori &amp; Tedarikçi</th>
                    <th>Mevcut Adet</th>
                    <th>Kritik Sınır</th>
                    <th>Önerilen Sipariş</th>
                    <th>Tahmini Sipariş Maliyeti</th>
                    <th class="yazdirma-gizle">Hızlı Tedarik</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($kritikUrunler) === 0): ?>
                    <tr><td colspan="7" class="bos">Harika! Kritik stok sınırının altında veya tükenen ürün yok.</td></tr>
                <?php endif; ?>

                <?php
                $toplamSiparisMaliyeti = 0.0;
                foreach ($kritikUrunler as $ku):
                    $mevcutAdet  = (int) $ku['adet'];
                    $kritikSinir = (int) ($ku['kritik_stok'] ?? KRITIK_STOK);

                    $hedefStok       = max(10, $kritikSinir * 2);
                    $onerilenSiparis = max(1, $hedefStok - $mevcutAdet);
                    $alisBirim       = (float) ($ku['alis_fiyati'] ?? 0);
                    $tahminiMaliyet  = $onerilenSiparis * $alisBirim;
                    $toplamSiparisMaliyeti += $tahminiMaliyet;
                    $durum = stokDurumu($mevcutAdet, $kritikSinir);
                ?>
                    <tr class="satir-<?= durumSinifi($durum) ?>">
                        <td>
                            <strong><?= e($ku['ad']) ?></strong>
                            <div class="kod-etiket"><?= e($ku['kod'] ?? '') ?></div>
                        </td>
                        <td>
                            <div><?= e($ku['kategori']) ?></div>
                            <?php if (!empty($ku['tedarikci'])): ?>
                                <div class="aciklama">🏢 <?= e($ku['tedarikci']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="rozet <?= durumSinifi($durum) ?>"><?= $mevcutAdet ?> adet (<?= $durum ?>)</span></td>
                        <td>≤ <?= $kritikSinir ?> adet</td>
                        <td><strong>+<?= $onerilenSiparis ?> adet</strong></td>
                        <td><?= fiyatFormatla($tahminiMaliyet) ?></td>
                        <td class="yazdirma-gizle">
                            <form method="post" action="stok_guncelle.php" class="satir-ici-form">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= $ku['id'] ?>">
                                <input type="hidden" name="degisim" value="<?= $onerilenSiparis ?>">
                                <button type="submit" class="btn kucuk yesil">+<?= $onerilenSiparis ?> Stok Ekle</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (count($kritikUrunler) > 0): ?>
                <tfoot>
                    <tr>
                        <th colspan="5" style="text-align: right;">Toplam Tahmini Tedarik Maliyeti:</th>
                        <th colspan="2"><?= fiyatFormatla($toplamSiparisMaliyeti) ?></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="rapor-bolum">
    <h3>🏆 En Yüksek Stok Değerine Sahip İlk 5 Ürün</h3>
    <div class="tablo-kutu">
        <table>
            <thead>
                <tr>
                    <th>Sıra</th>
                    <th>Kod / Ürün Adı</th>
                    <th>Kategori</th>
                    <th>Adet × Satış Fiyatı</th>
                    <th>Toplam Değer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enDegerliBes as $sira => $u): ?>
                    <tr>
                        <td><strong>#<?= $sira + 1 ?></strong></td>
                        <td><strong><?= e($u['ad']) ?></strong> <span class="kod-etiket"><?= e($u['kod'] ?? '') ?></span></td>
                        <td><?= e($u['kategori']) ?></td>
                        <td><?= (int) $u['adet'] ?> × <?= fiyatFormatla((float) $u['fiyat']) ?></td>
                        <td><strong><?= fiyatFormatla($u['adet'] * $u['fiyat']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
