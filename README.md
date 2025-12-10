# Yatay Gecis Basvuru Platformu

PHP ve MySQL uzerinde kurgulanan bu platform, baska universitelerden yatay gecis yapmak isteyen ogrencilerin basvuru sureclerini dijital olarak toplamayi, memur ve bolum onaylarindan gecirmeyi ve ust yonetim tarafindan takip edilmeyi saglar.

## Ozellikler
- Rol bazli erisim (Ogrenci, Memur, Bolum Baskani, Yonetici, Ust Yonetici)
- Zorunlu ve opsiyonel tum belgeler icin yukleme ve guvenli indirme
- Basvuru durum akisi (Memur → Bolum Baskani → Yonetici → Ust Yonetici)
- Durum loglari ve tek tikla basvuru belgesi indirme
- Ogrenci ve personel icin ayri kayit ekranlari, personel icin onay bekleme sureci
- Modern ve resmi arayuz, responsive tasarim

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
