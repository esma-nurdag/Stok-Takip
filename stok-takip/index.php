<?php

require_once 'functions.php';

$seciliKategori = trim($_GET['kategori'] ?? '');
$seciliDurum    = trim($_GET['durum'] ?? '');
$aramaMetni     = trim($_GET['ara'] ?? '');
$seciliSirala   = trim($_GET['sirala'] ?? '');
$sayfa          = max(1, (int) ($_GET['sayfa'] ?? 1));
$sayfaBasi      = (int) ($_GET['limit'] ?? 10);
if (!in_array($sayfaBasi, [5, 10, 25, 100], true)) {
    $sayfaBasi = 10;
}

$tumUrunler = $_SESSION['urunler'];
$sayac      = sayaclariHesapla($tumUrunler);
$filtrelenenUrunler = urunleriFiltrele($tumUrunler, $seciliKategori, $seciliDurum, $aramaMetni, $seciliSirala);

$toplamKayit = count($filtrelenenUrunler);
$toplamSayfa = max(1, (int) ceil($toplamKayit / $sayfaBasi));
if ($sayfa > $toplamSayfa) {
    $sayfa = $toplamSayfa;
}
$baslangic = ($sayfa - 1) * $sayfaBasi;
$urunler   = array_slice($filtrelenenUrunler, $baslangic, $sayfaBasi);

$mesaj = mesajAl();
$mevcutKategoriler = kategorileriGetir();
$filtreAktifMi = ($seciliKategori !== '' || $seciliDurum !== '' || $aramaMetni !== '' || $seciliSirala !== '' || $sayfaBasi !== 10);

$indirParametreleri = http_build_query(array_filter([
    'ara'      => $aramaMetni,
    'kategori' => $seciliKategori,
    'durum'    => $seciliDurum,
    'sirala'   => $seciliSirala,
]));
$indirAdresi = 'indir.php' . ($indirParametreleri !== '' ? '?' . $indirParametreleri : '');

$oneCikanUrun = $urunler[0] ?? ($tumUrunler[0] ?? null);
$oneCikanGM = 0.0;
if ($oneCikanUrun !== null) {
    $alis = (float) ($oneCikanUrun['alis_fiyati'] ?? 0);
    $satis = (float) ($oneCikanUrun['fiyat'] ?? 0);
    if ($alis > 0) {
        $oneCikanGM = round((($satis - $alis) / $alis) * 100, 1);
    } elseif ($satis > 0) {
        $oneCikanGM = 100.0;
    }
}

$sevkiyatHazirUrunler = array_values(array_filter($tumUrunler, fn($u) => (int) ($u['adet'] ?? 0) > 0));
$sevkiyatHazirUrunler = array_slice($sevkiyatHazirUrunler, 0, 3);

$kritikOneriUrunler = [];
$ortaStokUrunler    = [];
$yeniOneriUrunler   = [];

foreach ($tumUrunler as $u) {
    $adet   = (int) ($u['adet'] ?? 0);
    $kritik = (int) ($u['kritik_stok'] ?? KRITIK_STOK);
    if ($adet <= $kritik) {
        $kritikOneriUrunler[] = $u;
    } elseif ($adet <= $kritik * 2) {
        $ortaStokUrunler[] = $u;
    } else {
        $yeniOneriUrunler[] = $u;
    }
}
if (empty($yeniOneriUrunler)) {
    $yeniOneriUrunler = array_slice($tumUrunler, 0, 3);
} else {
    $yeniOneriUrunler = array_slice($yeniOneriUrunler, 0, 3);
}

$aktifSayfa = 'liste';
require 'header.php';
?>

<?php if ($mesaj): ?>
    <div class="mesaj <?= e($mesaj['tur']) ?>"><?= e($mesaj['metin']) ?></div>
<?php endif; ?>

<?php if ($sayac['Tükendi'] > 0 || $sayac['Azaldı'] > 0): ?>
    <div class="kritik-uyari-bandi">
        <div>
            <strong>⚠️ Stok Uyarısı:</strong>
            <?php if ($sayac['Tükendi'] > 0): ?>
                <span><strong><?= $sayac['Tükendi'] ?></strong> ürün tamamen tükendi!</span>
            <?php endif; ?>
            <?php if ($sayac['Azaldı'] > 0): ?>
                <span><strong><?= $sayac['Azaldı'] ?></strong> ürün kritik stok sınırının altında.</span>
            <?php endif; ?>
        </div>
        <a href="raporlar.php#kritik-liste" class="btn kucuk">Sipariş Listesini Gör →</a>
    </div>
<?php endif; ?>

<section class="sayaclar ana-sayaclar">
    <a href="<?= e(filtreAdresi($seciliKategori, '', $aramaMetni, $seciliSirala)) ?>" class="kart <?= $seciliDurum === '' ? 'secili' : '' ?>">
        <span>Toplam Ürün Çeşidi</span>
        <strong id="sayac-toplam"><?= $sayac['toplam'] ?></strong>
        <small id="sayac-toplam-adet">Toplam <?= $sayac['toplam_adet'] ?> adet birim</small>
    </a>
    <a href="<?= e(filtreAdresi($seciliKategori, 'Stokta', $aramaMetni, $seciliSirala)) ?>" class="kart stokta <?= $seciliDurum === 'Stokta' ? 'secili' : '' ?>">
        <span>Stokta</span>
        <strong id="sayac-stokta"><?= $sayac['Stokta'] ?></strong>
        <small>Yeterli stok</small>
    </a>
    <a href="<?= e(filtreAdresi($seciliKategori, 'Azaldı', $aramaMetni, $seciliSirala)) ?>" class="kart azaldi <?= $seciliDurum === 'Azaldı' ? 'secili' : '' ?>">
        <span>Azalan (Kritik)</span>
        <strong id="sayac-azaldi"><?= $sayac['Azaldı'] ?></strong>
        <small>Sipariş gerekli</small>
    </a>
    <a href="<?= e(filtreAdresi($seciliKategori, 'Tükendi', $aramaMetni, $seciliSirala)) ?>" class="kart tukendi <?= $seciliDurum === 'Tükendi' ? 'secili' : '' ?>">
        <span>Tükenen</span>
        <strong id="sayac-tukendi"><?= $sayac['Tükendi'] ?></strong>
        <small>Stokta 0 adet</small>
    </a>
    <div class="kart maliyet">
        <span>Toplam Alış Maliyeti</span>
        <strong id="sayac-maliyet"><?= fiyatFormatla($sayac['maliyet']) ?></strong>
        <small>Depo maliyeti</small>
    </div>
    <div class="kart deger">
        <span>Toplam Satış Değeri</span>
        <strong id="sayac-deger"><?= fiyatFormatla($sayac['deger']) ?></strong>
        <small>KDV dahil değer</small>
    </div>
    <div class="kart kar">
        <span>Beklenen Net Kâr</span>
        <strong id="sayac-kar"><?= fiyatFormatla($sayac['net_kar']) ?></strong>
        <small id="sayac-kar-oran">
            <?php
            $karMarji = $sayac['maliyet'] > 0 ? round(($sayac['net_kar'] / $sayac['maliyet']) * 100, 1) : 0;
            ?>
            +%<?= $karMarji ?> kâr oranı
        </small>
    </div>
</section>

<div class="recurra-grid">

    <div class="recurra-sol-sutun">

        <section class="recurra-card reporting-kart">
            <div class="kart-ust-baslik">
                <h2>Stok Raporu <span class="kart-alt-etiket">Envanter &amp; Performans</span></h2>
                <div class="kart-ust-araclar">
                    <button type="button" class="hap-btn" onclick="document.getElementById('hizli-kategori-kutu').classList.toggle('gizli')">
                        🏷️ + Kategori
                    </button>
                    <a href="<?= e($indirAdresi) ?>" class="hap-btn" title="Listeyi Excel (.xlsx) dosyası olarak indir">📥 Excel İndir</a>
                    <button type="button" class="ikon-btn kucuk" onclick="document.getElementById('filtre-formu').classList.toggle('gizli')" title="Filtre ve Sıralama Panelini Aç/Kapat">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form id="filtre-formu" method="get" action="index.php" class="filtre <?= $filtreAktifMi ? '' : 'gizli' ?>">
                <label class="filtre-genis">Barkod / Ürün Arama (Kısayol: <code>/</code>)
                    <input type="text" id="barkod-arama" name="ara" value="<?= e($aramaMetni) ?>" placeholder="Barkod okutun veya ürün/tedarikçi yazın (Örn: ELK-001)...">
                </label>

                <label>Kategori
                    <select name="kategori">
                        <option value="">Tümü</option>
                        <?php foreach ($mevcutKategoriler as $kategori): ?>
                            <option value="<?= e($kategori) ?>" <?= $seciliKategori === $kategori ? 'selected' : '' ?>>
                                <?= e($kategori) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Stok Durumu
                    <select name="durum">
                        <option value="">Tümü</option>
                        <?php foreach (['Stokta', 'Azaldı', 'Tükendi'] as $durum): ?>
                            <option value="<?= $durum ?>" <?= $seciliDurum === $durum ? 'selected' : '' ?>><?= $durum ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Sıralama
                    <select name="sirala">
                        <option value="" <?= $seciliSirala === '' ? 'selected' : '' ?>>Varsayılan (# ID)</option>
                        <option value="ad_asc" <?= $seciliSirala === 'ad_asc' ? 'selected' : '' ?>>Ürün Adı (A → Z)</option>
                        <option value="ad_desc" <?= $seciliSirala === 'ad_desc' ? 'selected' : '' ?>>Ürün Adı (Z → A)</option>
                        <option value="fiyat_asc" <?= $seciliSirala === 'fiyat_asc' ? 'selected' : '' ?>>Fiyat (En Düşük)</option>
                        <option value="fiyat_desc" <?= $seciliSirala === 'fiyat_desc' ? 'selected' : '' ?>>Fiyat (En Yüksek)</option>
                        <option value="adet_asc" <?= $seciliSirala === 'adet_asc' ? 'selected' : '' ?>>Stok Adedi (Azdan Çoğa)</option>
                        <option value="adet_desc" <?= $seciliSirala === 'adet_desc' ? 'selected' : '' ?>>Stok Adedi (Çoktan Aza)</option>
                        <option value="deger_desc" <?= $seciliSirala === 'deger_desc' ? 'selected' : '' ?>>Toplam Değer (En Yüksek)</option>
                        <option value="kar_desc" <?= $seciliSirala === 'kar_desc' ? 'selected' : '' ?>>Net Kâr (En Yüksek)</option>
                    </select>
                </label>

                <label>Sayfa Başı
                    <select name="limit">
                        <?php foreach ([5, 10, 25, 100] as $lim): ?>
                            <option value="<?= $lim ?>" <?= $sayfaBasi === $lim ? 'selected' : '' ?>><?= $lim ?> kayıt</option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="filtre-butonlar">
                    <button type="submit" class="btn">🔍 Uygula</button>
                    <a href="index.php" class="btn gri">Temizle</a>
                </div>
            </form>

            <form id="hizli-kategori-kutu" method="post" action="kategoriler.php" class="filtre gizli yazdirma-gizle">
                <?= csrfInput() ?>
                <input type="hidden" name="islem" value="ekle">
                <input type="hidden" name="geri_don" value="index.php">
                <label class="filtre-genis">Dışarıdan Yeni Kategori Adı
                    <input type="text" name="kategori_adi" placeholder="Örn: Kozmetik, Oyuncak, Hırdavat, Mobilya..." required>
                </label>
                <div class="filtre-butonlar">
                    <button type="submit" class="btn yesil">➕ Kategoriyi Kaydet</button>
                    <a href="kategoriler.php" class="btn gri">📂 Toplu / Dosyadan Aktar</a>
                </div>
            </form>

            <?php if ($seciliKategori !== '' || $seciliDurum !== '' || $aramaMetni !== '' || $seciliSirala !== ''): ?>
                <p class="aciklama" style="margin-top: 0; margin-bottom: 10px;">
                    🔎 Filtreyle eşleşen <strong><?= $toplamKayit ?></strong> ürün bulundu.
                    (Sayfa <?= $sayfa ?> / <?= $toplamSayfa ?>)
                </p>
            <?php endif; ?>

            <div class="tablo-kutu">
                <table>
                    <thead>
                        <tr>
                            <th>Ürün &amp; Kategori</th>
                            <th>Stok Adedi ↓</th>
                            <th>Fiyat &amp; Değer</th>
                            <th>Trend</th>
                            <th>Durum</th>
                            <th>QM</th>
                            <th class="yazdirma-gizle">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($urunler) === 0): ?>
                            <tr><td colspan="7" class="bos">Gösterilecek ürün bulunamadı.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($urunler as $urun): ?>
                            <?php
                            $kritikSinir = (int) ($urun['kritik_stok'] ?? KRITIK_STOK);
                            $durum       = stokDurumu((int) $urun['adet'], $kritikSinir);
                            $durumSinif  = durumSinifi($durum);
                            $alisFiyati  = (float) ($urun['alis_fiyati'] ?? 0);
                            $satisFiyati = (float) $urun['fiyat'];
                            $satirDeger  = $urun['adet'] * $satisFiyati;
                            $satirKar    = $urun['adet'] * ($satisFiyati - $alisFiyati);
                            $marjYuzde   = $alisFiyati > 0 ? round((($satisFiyati - $alisFiyati) / $alisFiyati) * 100, 1) : 0;

                            $oran = $kritikSinir > 0 ? min(1, ((int) $urun['adet']) / ($kritikSinir * 3)) : 1;
                            $doluSayisi = $durum === 'Tükendi' ? 1 : max(2, (int) round($oran * 7));
                            $cubukYukseklikleri = [9, 13, 11, 17, 14, 19, 12];
                            if (((int) $urun['id']) % 2 === 0) {
                                $cubukYukseklikleri = [12, 16, 10, 19, 15, 13, 17];
                            }
                            ?>
                            <tr class="satir-<?= $durumSinif ?>" data-urun-id="<?= $urun['id'] ?>">
                                <td>
                                    <div class="urun-hucre">
                                        <?php if (!empty($urun['gorsel']) && file_exists(__DIR__ . '/' . $urun['gorsel'])): ?>
                                            <img src="<?= e($urun['gorsel']) ?>" alt="<?= e($urun['ad']) ?>" class="urun-resim">
                                        <?php else: ?>
                                            <div class="urun-ikon"><?= kategoriIkonu($urun['kategori']) ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= e($urun['ad']) ?></strong>
                                            <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-top:2px;">
                                                <span class="kod-etiket">#<?= $urun['id'] ?> • <?= e($urun['kod'] ?? '') ?></span>
                                                <span class="kategori-etiket"><?= e($urun['kategori']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="hizli-stok">
                                        <form method="post" action="stok_guncelle.php" class="satir-ici-form yazdirma-gizle">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="id" value="<?= $urun['id'] ?>">
                                            <input type="hidden" name="degisim" value="-1">
                                            <button type="submit" class="stok-btn eksi" title="1 Azalt" <?= $urun['adet'] <= 0 ? 'disabled' : '' ?>>−</button>
                                        </form>

                                        <strong class="adet-sayi"><?= $urun['adet'] ?></strong>

                                        <form method="post" action="stok_guncelle.php" class="satir-ici-form yazdirma-gizle">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="id" value="<?= $urun['id'] ?>">
                                            <input type="hidden" name="degisim" value="1">
                                            <button type="submit" class="stok-btn arti" title="1 Artır">+</button>
                                        </form>

                                        <button type="button" class="stok-btn toplu-btn yazdirma-gizle" title="Toplu Stok Giriş / Çıkış (+10, -5 vb.)" onclick="topluStokKutusuAc(<?= $urun['id'] ?>)">±N</button>
                                    </div>
                                    <form id="toplu-stok-<?= $urun['id'] ?>" method="post" action="stok_guncelle.php" class="toplu-stok-form gizli yazdirma-gizle">
                                        <?= csrfInput() ?>
                                        <input type="hidden" name="id" value="<?= $urun['id'] ?>">
                                        <input type="number" name="degisim" value="10" step="1" class="toplu-stok-input" title="Eklenecek (+) veya düşülecek (-) adet">
                                        <button type="submit" class="btn kucuk yesil">Uygula</button>
                                    </form>
                                    <div class="aciklama">Kritik: ≤ <?= $kritikSinir ?></div>
                                </td>
                                <td>
                                    <div class="fiyat-satir">
                                        <strong class="satir-deger"><?= fiyatFormatla($satirDeger) ?></strong>
                                        <span class="kdv-rozet">%<?= (int) ($urun['kdv'] ?? 20) ?></span>
                                    </div>
                                    <div class="aciklama">Birim: <?= fiyatFormatla($satisFiyati) ?></div>
                                    <div class="aciklama kar-metin satir-kar">Net Kâr: <?= fiyatFormatla($satirKar) ?></div>
                                </td>
                                <td>
                                    <span class="trend-deger <?= $durumSinif ?>">
                                        <?= $durum === 'Stokta' ? '↗ +' . $marjYuzde . '%' : ($durum === 'Azaldı' ? '↘ -' . max(1, $kritikSinir - (int) $urun['adet']) . ' br' : '↘ 0 br') ?>
                                    </span>
                                </td>
                                <td><span class="rozet durum-rozet <?= $durumSinif ?>"><?= $durum ?></span></td>
                                <td>
                                    <div class="qm-grafik <?= $durumSinif ?>" aria-label="Stok doluluk göstergesi">
                                        <?php foreach ($cubukYukseklikleri as $i => $yukseklik): ?>
                                            <span class="qm-cubuk <?= $i < $doluSayisi ? 'dolu' : '' ?>" style="height: <?= $yukseklik ?>px;"></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="yazdirma-gizle">
                                    <div class="islem-grubu">
                                        <a href="duzenle.php?id=<?= $urun['id'] ?>" class="btn kucuk duzenle" title="Düzenle">✏️</a>
                                        <?php if (yoneticiMi()): ?>
                                            <form method="post" action="sil.php" class="satir-ici-form"
                                                  onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="id" value="<?= $urun['id'] ?>">
                                                <button type="submit" class="btn sil" title="Sil">🗑️</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($toplamSayfa > 1): ?>
                <div class="sayfalama yazdirma-gizle">
                    <?php for ($s = 1; $s <= $toplamSayfa; $s++): ?>
                        <?php
                        $sayfaParam = http_build_query(array_filter([
                            'ara'      => $aramaMetni,
                            'kategori' => $seciliKategori,
                            'durum'    => $seciliDurum,
                            'sirala'   => $seciliSirala,
                            'limit'    => $sayfaBasi !== 10 ? $sayfaBasi : '',
                            'sayfa'    => $s,
                        ]));
                        ?>
                        <a href="index.php?<?= e($sayfaParam) ?>" class="sayfa-link <?= $s === $sayfa ? 'aktif' : '' ?>">
                            <?= $s ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="recurra-alt-ikili">

            <section class="recurra-card">
                <div class="kart-ust-baslik">
                    <h3>Yeni Sipariş <span class="kart-alt-etiket">Hızlı Tedarik</span></h3>
                    <a href="ekle.php" class="hap-btn">+ Yeni Ekle</a>
                </div>

                <?php if ($oneCikanUrun !== null): ?>
                    <div class="siparis-rota-kutu">
                        <div class="rota-satir">
                            <span class="rota-ikon mavi" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                            <div class="rota-metin">
                                <small>Tedarikçi</small>
                                <strong><?= e($oneCikanUrun['tedarikci'] ?: 'TeknoMerkez Bilişim A.Ş.') ?></strong>
                            </div>
                        </div>

                        <span class="rota-degistir-btn" aria-hidden="true">⇅</span>

                        <div class="rota-satir">
                            <span class="rota-ikon kirmizi" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </span>
                            <div class="rota-metin">
                                <small>Teslimat Noktası</small>
                                <strong>Merkez Depo • <?= e($oneCikanUrun['kategori']) ?> Bölümü</strong>
                            </div>
                        </div>
                    </div>

                    <div class="secili-urun-kart">
                        <div class="secili-urun-ust">
                            <div class="secili-urun-baslik">
                                <span class="turuncu-kutu-ikon">📦</span>
                                <span><?= e($oneCikanUrun['ad']) ?></span>
                            </div>
                            <a href="duzenle.php?id=<?= (int) $oneCikanUrun['id'] ?>" class="ikon-btn kucuk" title="Ürünü Düzenle">•••</a>
                        </div>

                        <div class="uc-metrik-kutu">
                            <div>
                                <small>Birim Fiyat</small>
                                <strong><?= fiyatFormatla((float) $oneCikanUrun['fiyat']) ?></strong>
                            </div>
                            <div>
                                <small>Kâr Marjı</small>
                                <strong class="metrik-yesil">▲ <?= $oneCikanGM ?>%</strong>
                            </div>
                            <div>
                                <small>Mevcut Stok</small>
                                <strong class="metrik-mavi"><?= (int) $oneCikanUrun['adet'] ?></strong>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="aciklama">Henüz görüntülenecek ürün bulunmuyor.</p>
                <?php endif; ?>
            </section>

            <section class="recurra-card">
                <div class="kart-ust-baslik">
                    <h3>Sevkiyata Hazır <span class="kart-alt-etiket">Hazır Stok</span></h3>
                    <a href="hareketler.php" class="ikon-btn kucuk" title="Tüm Stok Hareketleri">•••</a>
                </div>

                <?php if (count($sevkiyatHazirUrunler) === 0): ?>
                    <p class="aciklama">Şu anda sevkiyata hazır stoklu ürün bulunmuyor.</p>
                <?php else: ?>
                    <div class="sevkiyat-listesi">
                        <?php foreach ($sevkiyatHazirUrunler as $idx => $su): ?>
                            <div class="sevkiyat-oge <?= $idx === 0 ? 'acik' : '' ?>">
                                <button type="button" class="sevkiyat-baslik" onclick="hazirUrunSec(this)">
                                    <span class="sevkiyat-kod">
                                        <span>📦</span>
                                        <span>Ürün #<?= e($su['kod'] ?: $su['id']) ?></span>
                                    </span>
                                    <span class="sevkiyat-ok">›</span>
                                </button>

                                <div class="sevkiyat-govde">
                                    <div class="sevkiyat-urun-ad"><?= e($su['ad']) ?></div>
                                    <div class="sevkiyat-urun-alt">
                                        <?= e($su['kategori']) ?><?= !empty($su['aciklama']) ? ' • ' . e($su['aciklama']) : '' ?>
                                    </div>

                                    <div class="dort-metrik-izgara">
                                        <div>
                                            <small>Kritik Sınır</small>
                                            <strong><?= (int) ($su['kritik_stok'] ?? KRITIK_STOK) ?></strong>
                                        </div>
                                        <div>
                                            <small>Depo Stoğu</small>
                                            <strong><?= (int) $su['adet'] ?></strong>
                                        </div>
                                        <div>
                                            <small>Satış Fiyatı</small>
                                            <strong><?= fiyatFormatla((float) $su['fiyat']) ?></strong>
                                        </div>
                                        <div>
                                            <small>Alış Maliyeti</small>
                                            <strong><?= fiyatFormatla((float) ($su['alis_fiyati'] ?? 0)) ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <aside class="recurra-sag-sutun">
        <section class="recurra-card onerilen-kart">
            <div class="kart-ust-baslik">
                <h3>Önerilen Ürünler <span class="kart-alt-etiket">AI Önerileri</span></h3>
                <a href="raporlar.php#kritik-liste" class="ikon-btn kucuk" title="Kritik Stok Raporuna Git">•••</a>
            </div>

            <div class="akordiyon-listesi">

                <div class="akordiyon-grup <?= count($kritikOneriUrunler) > 0 && count($yeniOneriUrunler) === 0 ? 'acik' : '' ?>">
                    <button type="button" class="akordiyon-baslik" onclick="akordiyonAcKapat(this)">
                        <span class="akordiyon-baslik-sol">
                            <span>Yeniden Sipariş (Acil)</span>
                            <span class="akordiyon-sayac"><?= count($kritikOneriUrunler) ?></span>
                        </span>
                        <span class="akordiyon-ok">⌄</span>
                    </button>
                    <div class="akordiyon-icerik">
                        <?php if (empty($kritikOneriUrunler)): ?>
                            <div class="oneri-urun-kart">
                                <small class="aciklama">Kritik stok sınırının altında ürün bulunmuyor.</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($kritikOneriUrunler as $ku): ?>
                                <?php
                                $kSinir = (int) ($ku['kritik_stok'] ?? KRITIK_STOK);
                                $onerilen = max(5, ($kSinir * 2) - (int) $ku['adet']);
                                ?>
                                <div class="oneri-urun-kart">
                                    <div class="oneri-urun-ust">
                                        <div>
                                            <strong><?= e($ku['ad']) ?></strong>
                                            <small>Kritik: <?= $kSinir ?>, Stok: <?= (int) $ku['adet'] ?></small>
                                        </div>
                                        <form method="post" action="stok_guncelle.php" class="satir-ici-form">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="id" value="<?= (int) $ku['id'] ?>">
                                            <input type="hidden" name="degisim" value="<?= $onerilen ?>">
                                            <button type="submit" class="btn kucuk yesil">+<?= $onerilen ?></button>
                                        </form>
                                    </div>
                                    <div class="oneri-metrik-kutu">
                                        <div>
                                            <small>Alış Maliyeti</small>
                                            <strong><?= fiyatFormatla((float) ($ku['alis_fiyati'] ?? 0)) ?></strong>
                                        </div>
                                        <div class="sag">
                                            <small>Önerilen</small>
                                            <strong class="metrik-mavi">+<?= $onerilen ?></strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="akordiyon-grup">
                    <button type="button" class="akordiyon-baslik" onclick="akordiyonAcKapat(this)">
                        <span class="akordiyon-baslik-sol">
                            <span>Düşük / Orta Stok</span>
                            <span class="akordiyon-sayac"><?= count($ortaStokUrunler) ?></span>
                        </span>
                        <span class="akordiyon-ok">⌄</span>
                    </button>
                    <div class="akordiyon-icerik">
                        <?php if (empty($ortaStokUrunler)): ?>
                            <div class="oneri-urun-kart">
                                <small class="aciklama">Orta stok seviyesinde ürün bulunmuyor.</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($ortaStokUrunler as $mu): ?>
                                <div class="oneri-urun-kart">
                                    <div class="oneri-urun-ust">
                                        <div>
                                            <strong><?= e($mu['ad']) ?></strong>
                                            <small><?= e($mu['kategori']) ?> • Stok: <?= (int) $mu['adet'] ?></small>
                                        </div>
                                        <a href="duzenle.php?id=<?= (int) $mu['id'] ?>" class="ikon-btn kucuk" title="Ürünü Düzenle">•••</a>
                                    </div>
                                    <div class="oneri-metrik-kutu">
                                        <div>
                                            <small>Birim Fiyat</small>
                                            <strong><?= fiyatFormatla((float) $mu['fiyat']) ?></strong>
                                        </div>
                                        <div class="sag">
                                            <small>Mevcut Adet</small>
                                            <strong class="metrik-mavi"><?= (int) $mu['adet'] ?></strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="akordiyon-grup acik">
                    <button type="button" class="akordiyon-baslik" onclick="akordiyonAcKapat(this)">
                        <span class="akordiyon-baslik-sol">
                            <span>Öne Çıkanlar</span>
                            <span class="akordiyon-sayac"><?= count($yeniOneriUrunler) ?></span>
                        </span>
                        <span class="akordiyon-ok">⌄</span>
                    </button>
                    <div class="akordiyon-icerik">
                        <?php foreach ($yeniOneriUrunler as $ou): ?>
                            <?php $oKritik = (int) ($ou['kritik_stok'] ?? KRITIK_STOK); ?>
                            <div class="oneri-urun-kart">
                                <div class="oneri-urun-ust">
                                    <div>
                                        <strong><?= e($ou['ad']) ?></strong>
                                        <small>Kritik: <?= $oKritik ?>, Stok: <?= (int) $ou['adet'] ?></small>
                                    </div>
                                    <a href="duzenle.php?id=<?= (int) $ou['id'] ?>" class="ikon-btn kucuk" title="Ürünü Düzenle">•••</a>
                                </div>
                                <div class="oneri-metrik-kutu">
                                    <div>
                                        <small>Birim Fiyat</small>
                                        <strong><?= fiyatFormatla((float) $ou['fiyat']) ?></strong>
                                    </div>
                                    <div class="sag">
                                        <small>Mevcut Adet</small>
                                        <strong class="metrik-mavi"><?= (int) $ou['adet'] ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </aside>
</div>

<?php require 'footer.php'; ?>
