# GENIOSTOK — Ürün ve Stok Takip Sistemi

| Bilgi | Açıklama |
|---|---|
| **Ders** | İnternet Programcılığı I (PHP) |
| **Öğrenci** | Ad Soyad — Öğrenci No |
| **Çalışma süresi** | 21 Ekim – 8 Kasım 2026 (3 hafta) |
| **Çalışma biçimi** | Bireysel |

---

## 1. Projenin Adı ve Amacı

**GENIOSTOK**, küçük bir mağazanın ürünlerini kaydetmek, stok durumunu takip etmek ve stok hareketlerini izlemek için PHP ile geliştirilmiş bir **kayıt ve takip sistemidir**.

Kullanıcı ürün ekleyebilir, silebilir, düzenleyebilir, ürünleri kategori ve stok durumuna göre filtreleyebilir ve özet sayaçlardan stoğun genel durumunu görebilir.

Veriler `$_SESSION` içinde tutulur ve ayrıca `data/veritabani.json` dosyasına kaydedilir. Bu sayede sayfa yenilendiğinde ya da tarayıcı kapatıldığında veriler kaybolmaz. MySQL gerekmez.

---

## 2. Projede Bulunan Özellikler

### Zorunlu özellikler

| Madde | Özellik | Nerede / Nasıl |
|---|---|---|
| 3.1 | Ana sayfa | `index.php`: uygulama adı, açıklama, üst menü (`header.php`) ve ürün tablosu |
| 3.2 | Kayıt listeleme | `foreach ($urunler as $urun)` döngüsü ile listelenir. Her üründe stok kodu, ad, açıklama, kategori, adet, fiyat ve durum alanları vardır. Başlangıçta 6 örnek ürün bulunur. |
| 3.3 | Yeni kayıt ekleme | `ekle.php`: HTML formu `method="post"` ile gönderilir, PHP `$_POST` ile alır ve ürünü `$_SESSION['urunler']` dizisine ekler |
| 3.4 | Veri doğrulama | `functions.php` → `urunDogrula()`: boş alanları, negatif adetleri, geçersiz fiyatları ve aynı stok kodunu kontrol eder; hatalar kullanıcıya listelenir |
| 3.5 | Kayıt silme | Tablodaki **Sil** butonu `method="post"` ile `sil.php` dosyasına ürün ID'sini gönderir; ürün diziden çıkarılır |
| 3.6 | Filtreleme | `index.php` içindeki form `method="get"` ile çalışır: kategori, stok durumu (Stokta / Azaldı / Tükendi), kelime arama ve sıralama |
| 3.7 | Özet bilgiler | Toplam ürün, stoktaki, azalan ve tükenen ürün sayaçları; toplam maliyet, satış değeri ve beklenen kâr |
| 3.8 | Güvenli çıktı | Kullanıcı metinleri `e()` fonksiyonundan geçirilir. Bu fonksiyon `htmlspecialchars($metin, ENT_QUOTES, 'UTF-8')` çağırır |

### Ek özellikler

- **Ürün düzenleme** (`duzenle.php`) ve tablodan **hızlı stok artırma/azaltma** (`stok_guncelle.php`)
- **Kategori yönetimi** (`kategoriler.php`): tek tek veya `.txt` / `.csv` / `.json` dosyasından toplu kategori ekleme
- **Stok hareket geçmişi** (`hareketler.php`): yapılan her işlem tarih ve kullanıcı bilgisiyle kaydedilir
- **Raporlar** (`raporlar.php`): kategori dağılımı ve acil sipariş listesi
- **Kullanıcı girişi ve roller** (`login.php`): Yönetici ve Personel
- **Excel'e aktarma** (`indir.php`)
- **Gece / gündüz modu**
- **Verileri sıfırlama** (`sifirla.php`)

### Kullanılan PHP temelleri

- **Diziler:** ürünler, kategoriler ve hareketler dizilerde tutulur
- **Döngüler:** `foreach` ile listeleme, sayaç hesaplama ve filtreleme
- **Koşullar:** `if / else` ile doğrulama, filtre ve stok durumu belirleme
- **Kullanıcı tanımlı fonksiyonlar:** `functions.php` içinde; örnek: `urunDogrula()`, `e()`, `kategorileriGetir()`, `yerelVeriyiKaydet()`, `hareketKaydet()`
- **GET / POST:** filtreleme GET ile, ekleme / silme / düzenleme POST ile yapılır
- **`$_SESSION`:** verilerin sayfalar arasında korunması

---

## 3. Klasör Yapısı

```text
stok-takip/
├── index.php           Ana sayfa: sayaçlar, filtre formu, ürün tablosu
├── ekle.php            Yeni ürün ekleme (POST)
├── duzenle.php         Ürün düzenleme (POST)
├── sil.php             Ürün silme (POST)
├── stok_guncelle.php   Hızlı stok artırma / azaltma
├── kategoriler.php     Kategori yönetimi
├── hareketler.php      Stok hareket geçmişi
├── raporlar.php        Raporlar
├── indir.php           Excel'e aktarma
├── login.php           Kullanıcı girişi
├── logout.php          Çıkış
├── sifirla.php         Verileri başlangıç haline döndürme
├── functions.php       Oturum, veri kaydetme ve yardımcı fonksiyonlar
├── header.php          Ortak üst kısım ve menü
├── footer.php          Ortak alt kısım
├── data/
│   └── veritabani.json Verilerin kalıcı olarak saklandığı dosya
├── assets/
│   ├── style.css       Tasarım dosyası
│   └── uploads/        Yüklenen ürün görselleri
├── ekran-goruntuleri/  Ekran görüntüleri
└── README.md           Bu dosya
```

---

## 4. XAMPP Üzerinde Çalıştırma

1. **XAMPP Control Panel**'i açın ve **Apache**'yi başlatın (MySQL gerekmez).
2. `stok-takip` klasörünü `C:\xampp\htdocs\` içine koyun.
3. Tarayıcıda açın: `http://localhost/stok-takip/`
4. Giriş bilgileri:
   - Yönetici: `admin` / `1234`
   - Personel: `personel` / `1234`

> Not: `data/` klasörünün yazılabilir olması gerekir. Verileri ilk haline döndürmek için `sifirla.php` kullanılabilir.

---

## 5. Bilinen Eksikler ve Sonraki Adımlar

- Veriler şu an JSON dosyası ve `$_SESSION` ile tutuluyor; sonraki aşamada **MySQL + PDO**'ya taşınacak.
- Kullanıcı şifreleri basit tutuldu; ileride `password_hash()` ile şifrelenecek.
- Planlanan: tedarikçi takibi ve barkod ile ürün arama.

---

## 6. Ekran Görüntüleri

`ekran-goruntuleri/` klasöründe:

1. `01_ana_sayfa_ve_listeleme.png` — Ana sayfa, sayaçlar ve ürün listesi
2. `02_yeni_urun_ekleme_ve_dogrulama.png` — Ekleme formu ve hata mesajları
3. `03_filtreleme_ve_silme.png` — Filtreleme ve silme işlemi

---

## 7. Yapay Zekâ Kullanımı

**Kullanılan araçlar:** Claude (Anthropic), Google Gemini

**Kullanım amaçları:**
- Proje dosya yapısının planlanması
- PHP kodlarının oluşturulması ve hataların bulunması
- Veri doğrulama ve güvenlik fonksiyonlarının yazılması
- Arayüz (CSS) tasarımı
- Kodların açıklanması ve sunuma hazırlık

**Örnek istem:**
> "İnternet Programcılığı I dersi için PHP ile ürün ve stok takip sistemi geliştir. Veriler $_SESSION ve JSON dosyasında tutulsun. foreach ile listeleme, POST ile ekleme ve silme, GET ile kategori ve durum filtreleme, özet sayaçlar ve htmlspecialchars() ile güvenli çıktı olsun."

**Kendi katkım:**
- Proje konusunu ve ürün alanlarını (stok kodu, alış/satış fiyatı, KDV, kritik stok sınırı) belirledim.
- Kategori ekleme akışını ve ek özellikleri kurguladım.
- Uygulamayı farklı senaryolarla (boş form, hatalı fiyat, aynı stok kodu, silme, filtreleme) test ettim.
- Kodları inceleyip sadeleştirdim, gereksiz yorum satırlarını kaldırdım ve sunuma hazırlandım.
