# NowPlast — Plastik Enjeksiyon Firma Sitesi

PHP 8 + MySQL (veya SQLite) ile çalışan kurumsal site ve yönetim paneli. Framework veya Composer gerektirmez; dosyaları hostinge yüklemeniz yeterlidir.

## Gereksinimler

- PHP 8.0 veya üzeri
- PHP eklentileri: `pdo_sqlite` (yerel deneme) veya `pdo_mysql` (sunucu), `mbstring`
- Apache + `mod_rewrite` (hostinglerin neredeyse hepsinde hazırdır). Yerelde PHP'nin kendi sunucusu yeterli.

## Yerelde çalıştırma (bilgisayarınızda deneme)

1. Repoyu indirin (`git clone …` veya GitHub'da **Code → Download ZIP**).
2. Proje klasöründe şu komutu çalıştırın:
   ```bash
   php -S localhost:8000 index.php
   ```
3. Tarayıcıda `http://localhost:8000` adresini açın. Kurulum ekranı otomatik gelir; yönetici kullanıcı adı ve şifresini belirleyin. İsterseniz **örnek içerik yükle** seçeneğini işaretli bırakın.
4. Yönetim paneli: `http://localhost:8000/admin/`

Yerelde SQLite kullanılır, ayrıca veritabanı kurmanız gerekmez. Veritabanı `data/site.sqlite` dosyasında oluşur ve GitHub'a gönderilmez.

> **Windows'ta "could not find driver" hatası alırsanız:** PHP klasöründeki `php.ini-development` dosyasını `php.ini` adıyla kopyalayın. İçinde `;extension_dir = "ext"`, `;extension=pdo_sqlite` ve `;extension=mbstring` satırlarının başındaki `;` işaretini silin, sunucuyu yeniden başlatın.

## Hostinge kurulum (cPanel)

1. Tüm dosyaları `public_html` klasörüne (veya bir alt klasöre) yükleyin.
2. cPanel'den bir **MySQL veritabanı ve kullanıcısı** oluşturun.
3. `config.local.example.php` dosyasını **`config.local.php`** adıyla kopyalayın ve veritabanı bilgilerinizi yazın. `config.php` dosyasını değiştirmeyin.
4. `siteniz.com/install.php` adresini açıp yönetici hesabını oluşturun.
5. Kurulumdan sonra güvenlik için `install.php` dosyasını silin.
6. Yönetim paneli: `siteniz.com/admin/`

`uploads/` ve `data/` klasörlerinin yazılabilir olması gerekir (genellikle 755).

## GitHub'a gönderilmeyen dosyalar

`.gitignore` sayesinde şunlar repoya girmez:

- `config.local.php`: veritabanı şifreleri
- `data/`: veritabanı, kurulum kilidi, giriş denemesi kayıtları
- `uploads/`: admin'den yüklenen dosyalar. `uploads/demo/` (örnek görseller) ve `uploads/brand/` (logo, favicon) repoya dahildir.

Bu yüzden siteyi indiren herkes kendi yönetici hesabıyla temiz bir kurulum yapar. Sizin ürün, mesaj ve şifreleriniz paylaşılmaz.

## Site yapısı

| Adres | İçerik |
|---|---|
| `/` | Anasayfa: slider, ürün grupları, hakkımızda + rakamlar, üretim süreci, sektörler, öne çıkan ürünler, belgeler, referanslar, haberler, iletişim formu + harita |
| `/urunler` | Tüm ürünler, filtre (sektör, hammadde, hacim) ve sıralama ile |
| `/urunler/{kategori}` | Kategori: alt gruplar + ürün listesi (alt kategorilerin ürünleri dahil) |
| `/urun/{ürün}` | Ürün detayı: galeri, teknik özellikler, numune/teklif talebi, WhatsApp, PDF föy, benzer ürünler |
| `/sektorler`, `/sektor/{sektör}` | Sektöre göre ürünler |
| `/haberler`, `/haber/{haber}` | Haberler & duyurular |
| `/iletisim` | İletişim bilgileri, form, büyük harita, yol tarifi |
| `/ara?q=` | Ürün ve sayfa araması |
| `/{sayfa}` | Admin'den eklenen içerik sayfaları (Hakkımızda, KVKK…) |
| `/katalog` | Yüklenen PDF e-katalog |
| `/sitemap.xml`, `/robots.txt` | SEO |

Ürün gruplama örnek sitelerle aynı mantıkta: **ana grup → şekil alt grupları** (Yuvarlak / Kare / Dikdörtgen / Oval) ağacı, buna ek olarak her ürün birden fazla **sektöre** bağlanabilir (Boldipack'teki gibi).

## Yönetim paneli

- **Ürünler:** Kod, hacim, ölçüler, gramaj, hammadde, baskı, kapak, koli adedi, serbest ek özellikler, ana görsel + galeri, PDF föy, sektörler, öne çıkan/yeni etiketi, SEO alanları. Sürükle-bırak sıralama, kategoriye göre filtre, kopyalama, tek tıkla yayına alma/kaldırma.
- **Kategoriler:** İç içe ağaç. Sürükle-bırak ile hem sıralama hem başka kategorinin altına taşıma.
- **Menüler:** Üst menü ve alt menü ayrı ayrı. Sürükle-bırak ile sıralama ve alt menü oluşturma. Bağlantı türleri: sabit sayfa, içerik sayfası, kategori, sektör, özel URL. Bir menü öğesinin altına kategoriler (mega menü) veya sektörler otomatik eklenebilir.
- **Sayfalar, Slider, Haberler, Belgeler, Referanslar, Sektörler**
- **Mesajlar & Talepler:** İletişim, numune ve teklif formları tek kutuda. Okunmamış sayacı, ürün bilgisi, e-posta/WhatsApp ile yanıt, Excel (CSV) dışa aktarma.
- **Site ayarları:** Logo, tema rengi, iletişim bilgileri, **harita** (enlem/boylam veya Google embed adresi), sosyal medya, anasayfa metinleri ve rakamları, e-katalog PDF, Google Analytics.
- **Yöneticiler & Profil:** Birden fazla yönetici, şifre değiştirme.

## Güvenlik

Parolalar hashlenerek saklanır. Tüm formlarda CSRF koruması ve veritabanında parametreli sorgular kullanılır. Hatalı girişler IP başına sınırlanır (5 deneme / 15 dk). Görsel yüklemeleri içerik türüne göre doğrulanır, `uploads/` klasöründe PHP çalıştırılamaz. Formlarda bot koruması (gizli alan + zaman kontrolü) vardır.

## Demo görseller

`uploads/demo/` içindeki SVG çizimler yalnızca yer tutucudur. Admin panelinden gerçek ürün fotoğraflarını yüklediğinizde otomatik olarak değişir. Panel, demo görsel kullanan ürünleri "Yapılacaklar" kutusunda listeler.
