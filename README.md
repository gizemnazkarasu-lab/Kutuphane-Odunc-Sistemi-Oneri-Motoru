# Kütüphane Ödünç Sistemi ve Öneri Motoru (2. Aşama — JSON Veri Deposu ve Katmanlı Mimari)

Bu proje; katmanlı mimari (`Router -> Controller -> Service -> Repository -> Model -> View`) ve **Repository**, **State**, **Strategy**, **Observer** tasarım desenleri kullanılarak geliştirilmiş, veritabanından bağımsız olarak **kalıcı JSON dosyaları (`storage/*.json`)** üzerinde çalışan tam kapsamlı bir Kütüphane Yönetim ve Öneri Sistemidir.

---

## Grup Üyeleri
| Ad Soyad | Görevi |
|---|---|
| **Remziye Ersun** | Proje Yöneticisi, Arayüz ve Dokümantasyon Sorumlusu |
| **Ümmügülsüm Kitapçıoğlu** | Veri Tabanı ve Test Sorumlusu |
| **Gizem Naz Karasu** | Mimar |

---

## Projeyi Bilgisayarda Çalıştırma (Canlı Demo)

Projeyi GitHub'dan indirdikten sonra çalıştırmak için aşağıdaki yöntemlerden birini kullanabilirsiniz:

### Yöntem 1: Mac'te Çift Tıklayarak Başlatma (`baslat.command`)
Proje klasöründeki **`baslat.command`** dosyasına çift tıklamanız yeterlidir; sunucu otomatik olarak başlar ve tarayıcıda `http://localhost:8080` adresini açar.

### Yöntem 2: Terminalden Tek Komutla Başlatma
Proje klasöründe terminali açıp şu komutu çalıştırın:
```bash
php -S localhost:8080 -t public
```
*(Eğer MAMP kullanıyorsanız: `/Applications/MAMP/bin/php/php8.3.30/bin/php -S localhost:8080 -t public`)*

Ardından tarayıcınızda şu adrese gidin:
`http://localhost:8080`

---

## Hazır Demo Kullanıcı Hesapları (Şifrelerin Tamamı: `123456`)

Uygulamanın en üstündeki **"Hızlı Hesap Geçişi"** çubuğundan tek tıkla hesaplar arasında geçiş yapabilir veya yeni üye kaydı oluşturabilirsiniz:

| Üye No | Ad Soyad | E-posta / Kullanıcı Adı | Rol |
|---|---|---|---|
| `UYE-0001` | Ümmügülsüm Kitapçıoğlu | `gulsum@kutuphane.edu.tr` | Üye (`member`) |
| `UYE-0002` | Gizem Naz Karasu | `gizem@kutuphane.edu.tr` | Üye (`member`) |
| `UYE-0003` | Remziye Ersun | `remziye@kutuphane.edu.tr` | Üye (`member`) |
| `UYE-0004` | Ahmet Yılmaz | `ahmet@kutuphane.edu.tr` | Üye (`member`) |
| `GOR-0001` | Ayşe Demir | `kutuphaneci@kutuphane.edu.tr` | Kütüphaneci (`librarian`) |
| `ADM-0001` | Sistem Yöneticisi | `admin@kutuphane.edu.tr` | Sistem Yöneticisi (`admin`) |

---

## 2. Aşamada Tamamlanan Çekirdek İş Kuralları ve Tasarım Desenleri

1. **Repository Deseni (`src/Repository/Json*Repository.php`):**
   - Tüm veriler `storage/` klasörü altındaki JSON dosyalarında (`users.json`, `books.json`, `copies.json`, `loans.json`, `reservations.json`, `fines.json`, `payments.json`, `notifications.json`, `settings.json`, `audit_logs.json`) kalıcı olarak tutulur.
2. **State Deseni (`src/Service/*State.php`):**
   - Rezervasyonların `WaitingState` -> `AllocatedState` -> `CompletedState` / `ExpiredState` / `CancelledState` durum geçişlerini yönetir ve geçersiz işlemleri engeller.
3. **Strategy Deseni (`src/Service/*FinePolicy.php`):**
   - Gecikme cezalarını kuruş bazında (`CalendarDayFinePolicy` hafta sonu dahil, `WeekdayFinePolicy` hafta sonu hariç) ve üst sınır kuralına göre hesaplar.
4. **Observer Deseni (`src/Service/EventDispatcher.php`, `ReservationNotificationListener.php`):**
   - Bir kitap iade edildiğinde rezervasyon sırasındaki ilk uygun üyeye **3 gün süreyle** ayrılır (`ReservationAllocatedEvent`) ve üyeye otomatik olarak **sistem içi bildirim** gönderilir.
5. **Ödünç Kuralları ve Öneri Motoru (`LoanService`, `RecommendationService`):**
   - Üye başına **maksimum 5 aktif kitap**, **14 gün ödünç süresi**, **en fazla 1 kez uzatma** kontrolü.
   - Ortak ödünç geçmişine (co-borrowing) göre **"Bunu okuyanlar şunu da okudu"** öneri listesi.
