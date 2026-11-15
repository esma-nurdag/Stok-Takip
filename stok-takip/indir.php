<?php

require_once 'functions.php';

$seciliKategori = trim($_GET['kategori'] ?? '');
$seciliDurum    = trim($_GET['durum'] ?? '');
$aramaMetni     = trim($_GET['ara'] ?? '');
$seciliSirala   = trim($_GET['sirala'] ?? '');
$format         = strtolower(trim($_GET['format'] ?? 'xlsx'));

$urunler = urunleriFiltrele($_SESSION['urunler'] ?? [], $seciliKategori, $seciliDurum, $aramaMetni, $seciliSirala);

while (ob_get_level() > 0) {
    ob_end_clean();
}

$basliklar = [
    'Sıra',
    'Stok Kodu',
    'Ürün Adı',
    'Kategori',
    'Tedarikçi',
    'Stok Adedi',
    'Kritik Sınır',
    'Stok Durumu',
    'Alış Maliyeti (TL)',
    'Satış Fiyatı (TL)',
    'KDV (%)',
    'Toplam Satış Değeri (TL)',
    'Beklenen Net Kâr (TL)',
    'Açıklama',
];

if ($format === 'csv') {
    $bellek = fopen('php://temp', 'r+');
    fwrite($bellek, "\xEF\xBB\xBF");
    fputcsv($bellek, $basliklar, ';', '"', '\\');

    foreach ($urunler as $u) {
        $adet   = (int) ($u['adet'] ?? 0);
        $kritik = (int) ($u['kritik_stok'] ?? KRITIK_STOK);
        $alis   = (float) ($u['alis_fiyati'] ?? 0);
        $satis  = (float) ($u['fiyat'] ?? 0);

        fputcsv($bellek, [
            $u['id'] ?? '',
            $u['kod'] ?? '',
            $u['ad'] ?? '',
            $u['kategori'] ?? '',
            $u['tedarikci'] ?? '',
            $adet,
            $kritik,
            stokDurumu($adet, $kritik),
            number_format($alis, 2, ',', '.'),
            number_format($satis, 2, ',', '.'),
            (int) ($u['kdv'] ?? 20),
            number_format($adet * $satis, 2, ',', '.'),
            number_format($adet * ($satis - $alis), 2, ',', '.'),
            $u['aciklama'] ?? '',
        ], ';', '"', '\\');
    }

    rewind($bellek);
    $csvIcerik = stream_get_contents($bellek);
    fclose($bellek);

    $dosyaAdi = 'stok-listesi-' . date('Y-m-d_H-i') . '.csv';
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($csvIcerik));
    echo $csvIcerik;
    exit;
}

function xlsxSutunHarfi(int $indeks): string
{
    $harf = '';
    $n = $indeks;
    while ($n >= 0) {
        $harf = chr(65 + ($n % 26)) . $harf;
        $n = intdiv($n, 26) - 1;
    }
    return $harf;
}

function xlsxXmlTemizle(string $deger): string
{
    $deger = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $deger) ?? '';
    return htmlspecialchars($deger, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsxZipOlustur(array $dosyalar): string
{
    $veriBloklari   = '';
    $merkezDizin    = '';
    $ofset          = 0;
    $kayitSayisi    = 0;

    foreach ($dosyalar as $ad => $icerik) {
        $crc       = crc32($icerik);
        $hamBoyut  = strlen($icerik);
        $sikisik   = gzdeflate($icerik, 6);
        $sikBoyut  = strlen($sikisik);

        $yerelBaslik = pack(
            'VvvvvvVVVvv',
            0x04034b50,
            20,
            0x0800,
            8,
            0,
            0x5421,
            $crc,
            $sikBoyut,
            $hamBoyut,
            strlen($ad),
            0
        );

        $veriBloklari .= $yerelBaslik . $ad . $sikisik;

        $merkezDizin .= pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014b50,
            20,
            20,
            0x0800,
            8,
            0,
            0x5421,
            $crc,
            $sikBoyut,
            $hamBoyut,
            strlen($ad),
            0,
            0,
            0,
            0,
            32,
            $ofset
        ) . $ad;

        $ofset += strlen($yerelBaslik) + strlen($ad) + $sikBoyut;
        $kayitSayisi++;
    }

    $sonKayit = pack(
        'VvvvvVVv',
        0x06054b50,
        0,
        0,
        $kayitSayisi,
        $kayitSayisi,
        strlen($merkezDizin),
        $ofset,
        0
    );

    return $veriBloklari . $merkezDizin . $sonKayit;
}

$satirlarXml = '';
$satirNo = 1;

$satirlarXml .= '<row r="1">';
foreach ($basliklar as $colIdx => $baslik) {
    $ref = xlsxSutunHarfi($colIdx) . '1';
    $satirlarXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . xlsxXmlTemizle($baslik) . '</t></is></c>';
}
$satirlarXml .= '</row>';

foreach ($urunler as $u) {
    $satirNo++;
    $adet   = (int) ($u['adet'] ?? 0);
    $kritik = (int) ($u['kritik_stok'] ?? KRITIK_STOK);
    $alis   = (float) ($u['alis_fiyati'] ?? 0);
    $satis  = (float) ($u['fiyat'] ?? 0);

    $hucreler = [
        ['tur' => 'n', 'deger' => (int) ($u['id'] ?? 0)],
        ['tur' => 's', 'deger' => (string) ($u['kod'] ?? '')],
        ['tur' => 's', 'deger' => (string) ($u['ad'] ?? '')],
        ['tur' => 's', 'deger' => (string) ($u['kategori'] ?? '')],
        ['tur' => 's', 'deger' => (string) ($u['tedarikci'] ?? '')],
        ['tur' => 'n', 'deger' => $adet],
        ['tur' => 'n', 'deger' => $kritik],
        ['tur' => 's', 'deger' => stokDurumu($adet, $kritik)],
        ['tur' => 'n', 'deger' => round($alis, 2)],
        ['tur' => 'n', 'deger' => round($satis, 2)],
        ['tur' => 'n', 'deger' => (int) ($u['kdv'] ?? 20)],
        ['tur' => 'n', 'deger' => round($adet * $satis, 2)],
        ['tur' => 'n', 'deger' => round($adet * ($satis - $alis), 2)],
        ['tur' => 's', 'deger' => (string) ($u['aciklama'] ?? '')],
    ];

    $satirlarXml .= '<row r="' . $satirNo . '">';
    foreach ($hucreler as $colIdx => $h) {
        $ref = xlsxSutunHarfi($colIdx) . $satirNo;
        if ($h['tur'] === 'n') {
            $satirlarXml .= '<c r="' . $ref . '" t="n"><v>' . $h['deger'] . '</v></c>';
        } else {
            $satirlarXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . xlsxXmlTemizle((string) $h['deger']) . '</t></is></c>';
        }
    }
    $satirlarXml .= '</row>';
}

$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<sheetData>' . $satirlarXml . '</sheetData>'
    . '</worksheet>';

$contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '</Types>';

$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Stok Listesi" sheetId="1" r:id="rId1"/></sheets>'
    . '</workbook>';

$workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '</Relationships>';

$xlsxIcerik = xlsxZipOlustur([
    '[Content_Types].xml'         => $contentTypesXml,
    '_rels/.rels'                 => $relsXml,
    'xl/workbook.xml'             => $workbookXml,
    'xl/_rels/workbook.xml.rels'  => $workbookRelsXml,
    'xl/worksheets/sheet1.xml'    => $sheetXml,
]);

$dosyaAdi = 'stok-listesi-' . date('Y-m-d_H-i') . '.xlsx';

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . strlen($xlsxIcerik));

echo $xlsxIcerik;
exit;
