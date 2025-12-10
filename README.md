# Yatay Gecis Basvuru Platformu

PHP ve MySQL uzerinde kurgulanan bu platform, baska universitelerden yatay gecis yapmak isteyen ogrencilerin basvuru sureclerini dijital olarak toplamayi, memur ve bolum onaylarindan gecirmeyi ve ust yonetim tarafindan takip edilmeyi saglar.

## Ozellikler
- Rol bazli erisim (Ogrenci, Memur, Bolum Baskani, Yonetici, Ust Yonetici)
- Zorunlu ve opsiyonel tum belgeler icin yukleme ve guvenli indirme
- Basvuru durum akisi: memur onayi ardindan bolum baskanina otomatik bildirim
- Durum loglari ve tek tikla basvuru belgesi indirme
- Ogrenci ve personel icin ayri kayit ekranlari, personel icin onay bekleme sureci
- Modern ve resmi arayuz, responsive tasarim
- Ust yoneticiler panelden dogrudan yeni kullanici olusturabilir
- Ust yoneticiler icin ileri duzey yetkiler: kullanici bilgisi duzenleme/silme, basvuruyu geri Alma ve detaylarini guncelleme, tam veri temizleme

## Kurulum
1. Depoyu sunucunuza kopyalayin.
2. `database.sql` dosyasini MySQL uzerinde calistirarak `yatay_gecis` veritabanini olusturun.
3. `config.php` dosyasindaki veritabani bilgileri asagidaki gibi ayarlidir:
   - Host: `localhost`
   - Kullanici: `mert`
   - Parola: `22446868`
   - Veritabani: `yatay_gecis`
4. Web sunucusunun kok dizinini `public/` klasorune yonlendirin veya sanal host olusturun.
5. `public/uploads` klasorunun yazma iznine sahip oldugundan emin olun.

## Ilk Kullanici
Platforma ilk kez erisip personel hesaplarini onaylayabilmek icin yonetici hesabi gerekli. Asagidaki SQL komutu ile dogrudan bir ust yonetici ekleyebilirsiniz:

```sql
INSERT INTO users (full_name, email, phone, department, role, status, password_hash)
VALUES ('Sistem Yonetici', 'admin@okul.edu.tr', '0000000000', 'Rektorluk', 'executive', 'active', PASSWORD('GucLuSifre!'));
```

Parolalar PHP uzerinde `password_hash` ile olusturuluyor. Yukaridaki ornek MySQL `PASSWORD()` fonksiyonunu kullanir; tercihen PHP veya `php -r "echo password_hash('GucLuSifre!', PASSWORD_DEFAULT);"` komutu ile olusan degeri ekleyin.

## Calistirma
- Tarayicidan `index.php` uzerinden sisteme erisin.
- Ogrenciler `Ogrenci Kaydi`, personeller `Personel Kaydi` sayfasindan kayit olur.
- Personel hesaplari `Yonetici` veya `Ust Yonetici` tarafindan `Kullanici Yonetimi` ekranindan aktif edilir.
- Ogrenciler "Yeni Basvuru" formunu doldurup belgelerini yukler. Memurlar siradaki basvuruyu onayladiginda otomatik olarak bolum baskanina gider.

### Onay ve Yetki Notlari
- Memur kullanicilar (ve gerekli durumlarda yoneticiler) tek adimda onay verip istege bagli aciklama ekleyebilir; ust yonetici gerekirse onayi geri alabilir.
- Bolum baskanlari yalnizca kendi bolumlerine ait ve memur tarafindan onaylanmis basvurulari goruntuler.
- Yonetici rolleri hem kullanici hesaplarini onaylayabilir hem de memurun yerine basvurulari onaylayip reddedebilir.
- Ust yoneticiler tum islemlere ek olarak kullanici olusturma, bilgilerini guncelleme, hesap silme, basvuru durumunu geri alma ve basvuru alanlarini dogrudan duzenleme yetkisine sahiptir.

### Ust Yonetim Araclari
`executive_tools.php` sayfasi yalnizca ust yoneticilere aciktir. Bu sayfa uzerinden:
- Tum basvuru, belge ve durum loglari silinebilir.
- Sistem uploads klasoru temizlenir.
- Ust yonetici haric tum kullanicilar kaldirilir; boylece sifir bir kurulum elde edilir.
- Ayrica `view_application.php` uzerindeki form sayesinde basvuru icerikleri duzenlenebilir ve gerekiyorsa onay kaldirilip memur asamasina geri dondurulebilir.

## Dizinyapisi
```
config.php          // Veritabani ve cekirdek ayarlar
public/             // Uygulama girisi (assets, view ve is mantigi)
src/                // Yardimci fonksiyonlar ve rol tanimlari
database.sql        // Sema ve temel tablolar
```

## Guvenlik Notlari
- Tum dosya yuklemeleri `public/uploads` klasorune benzersiz isimlerle kaydedilir.
- Belgeler sadece yetkili roller tarafindan `download_document.php` uzerinden indirilebilir.
- Oturum acmis kullanicilarin rollerine gore sayfa erisimi `require_roles` ile sinirlanir.

## Gelistirme
- Stil duzenlemeleri `public/assets/css/style.css` uzerinden yapilabilir.
- Yeni evrak tipleri `DOCUMENT_REQUIREMENTS` sabitine eklenerek tanimlanabilir.
- Onay akisi `helpers.php` icindeki `next_status_after_approval` ve `can_transition_status` fonksiyonlari ile uyarlanabilir.
