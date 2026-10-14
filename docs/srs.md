[srs.md](https://github.com/user-attachments/files/33435949/srs.md)
# Kütüphane Kitap Ödünç Sistemi + Öneri Motoru


## 1 Giriş


### 1.1 Grup Üyeleri

| Ad Soyad | Görevi |
|---|---|
| Remziye Ersun | Proje Yöneticisi, Arayüz ve Dokümantasyon Sorumlusu |
| Ümmügülsüm Kitapçıoğlu | Veri tabanı ve Test Sorumlusu |
| Gizem Naz Karasu | Mimar |

### 1.2 Amaç

Bu belge tasarlanan sistemin işlevlerini, kullanıcıya sunduğu hizmeti detaylı bir şekilde açıklamaktadır. Rezervasyon sırasının doğru yönetilmesi, gecikme cezalarının belirlenen kurallara göre hesaplanması ve ödünç geçmişinden anlamlı kitap önerileri üretmesidir.


### 1.3 Çekirdek Problem Tanımı

Grup olarak Kütüphane Ödünç Sistemi ve Öneri Motoru projesini seçmemizin nedeni, günlük yaşamda karşılığı olan bir ihtiyacı çözerken veritabanı tasarımı, katmanlı mimari, algoritma geliştirme ve yazılım testi konularındaki bilgilerimizi birlikte uygulayabilmemizdir. Amacımız, üyelerin kitaplara kolayca ulaşabildiği, kütüphanecilerin kitap kopyalarını ve ödünç işlemlerini düzenli biçimde takip edebildiği, adil ve güvenilir bir sistem geliştirmektir. Projemizin çekirdek problemi; rezervasyon sırasının doğru yönetilmesi, gecikme cezalarının belirlenen kurallara göre hesaplanması ve ödünç geçmişinden anlamlı kitap önerileri üretilmesidir. Bu kapsamda İade edilen uygun kitap kopyasının rezervasyon sırasındaki ilk üyeye üç gün boyunca rezerve edilmesini, üyeye sistem içi bildirim gösterilmesini, bu süre içinde teslim alınmazsa hakkın sıradaki üyeye geçmesini ve eşzamanlı taleplerde aynı kopyanın iki kişiye verilmemesini sağlayacağız. Gecikme cezalarını günlük ücret, üst sınır ve hafta sonu kuralına göre hesaplayacak; aynı kitabı ödünç alan üyelerin okuduğu diğer kitapları ortak ödünç sayısına göre sıralayarak “Bunu okuyanlar şunu da okudu” önerileri sunacağız. Böylece yalnızca kitap kayıtlarının tutulduğu bir uygulama yerine, kaynakların adil paylaşımını sağlayan ve kullanıcıların yeni kitaplar keşfetmesine yardımcı olan, temel kuralları testlerle doğrulanmış bir sistem geliştirmeyi hedefliyoruz.


### 1.4 Proje Özeti

Projemiz, kütüphane işlemlerini dijital ortamda yönetmeyi amaçlayan bir Kütüphane Yönetim Sistemidir. Sistem üzerinden kitap ekleme, silme ve arama, kitap ödünç alma ve iade etme, üye bilgilerini yönetme, ceza sistemi oluşturma ve ödünçteki kitaplar için bekleme listesi oluşturma işlemleri gerçekleştirilebilir. Ayrıca kullanıcı rolleri ve yetkileri yönetilerek güvenli ve düzenli bir kütüphane deneyimi sunulması hedeflenmektedir.


### 1.5 Kapsam

Sistem, kütüphanede bulunan tüm kitaplara üyelerin kolaylıkla ve adil bir biçimde erişebilmesi için rezervasyon sistemini yönetir. Kitap kopyalarını ve ödünç işlemlerini düzenli bir biçimde takip eder. Bu kapsamda iade edilen uygun kitap kopyasının sistem kurallarına göre sıradaki üyelere rezervasyonunu sağlamaktadır.


### 1.6 Kapsam Dışı

E-posta ile bilgilendirme bulunmamaktadır.

Barkod okuma sistemi bulunmamaktadır.


## 2 Paydaşlar ve Roller

Sistem üç temel kullanıcı rolüne sahiptir:


**Üyeler:** Kitapları sistem üzerinden aratabilir, arama kriterlerini belirleyebilir, beğendiği kitapları listeye ekleyebilir, kitapların müsaitlik durumunu görüntüleyebilir, ödünç alma talebinde bulunabilir, bekleme sırasına girebilir, ödünç süresini belirli kurallar doğrultusunda uzatabilir ve sonuçlarını görüntüleyebilir. Gecikme cezası ve öneri kitapları görüntüleyebilir. Uygulama içi bildirim alabilir.


**Kütüphane Görevlisi:** Kitap, yazar, kategori ve kitap kopyası kayıtlarını düzenler ve görüntüleyebilir, ödünç ve iade işlemlerini gerçekleştirir. Rezervasyon sırasını takip eder ve ceza ödemelerini kaydeder. Bunun yanında en çok okunan kitaplar, geciken iadeler ve üye bazlı okuma istatistiklerine ilişkin raporları görüntüleyebilir.


**Sistem Yöneticileri:** Kullanıcı hesaplarını ve erişim yetkilerini yönetir. Ödünç limitleri, ödünç süreleri ve gecikme cezası politikaları ve miktarı gibi sistem kurallarını yapılandırır. Verileri yedekler ve yedekleme kontrolü işlemlerini gerçekleştirir.


## 3. Kullanıcı Hikâyeleri ve Kabul Kriterleri

### US-01

Bir üye olarak, kitapları adına ve yazarına göre aramak istiyorum, böylece aradığım kitabı kolayca bulabilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Üye sisteme giriş yapmıştır. | Üye aradığı kitabı adına veya yazarına göre aradığında, | Sistem arama kriterlerine uygun kitapları listeler ve üyenin aradığı kitabı kolayca bulmasını sağlar. |
| Olumsuz | Üye kitap arama sayfasındadır. | Üye girdiği kitap adı veya yazar adına uygun bir kitap bulamadığında, | Sistem arama kriterlerine uygun kitap bulunamadığını belirten bir bilgilendirme mesajı gösterir. |

### US-02

Bir üye olarak, uygun durumdaki kitabı ödünç almak istiyorum, böylece kitabı belirlenen süre boyunca kullanabilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Üye sisteme giriş yapmıştır ve ödünç almak istediği kitap ödünç almaya uygundur. | Üye uygun durumdaki kitabı ödünç almak istediğinde, | Sistem kitabı üyenin üzerine kaydeder, iade tarihini belirler ve ödünç alma işleminin başarıyla tamamlandığını bildiren bir mesaj gösterir. |
| Olumsuz | Üye ödünç almak istediği kitabın sayfasındadır. | Üye başka bir üyede olan veya ödünç alınmaya uygun olmayan bir kitabı ödünç almaya çalıştığında, | Sistem ödünç alma işlemini gerçekleştirmez ve kitabın ödünç alınamadığını belirten bir uyarı mesajı gösterir. |

### US-03

Bir üye olarak, ödünçte olan bir kitap için bekleme sırasına girmek istiyorum, böylece kitap iade edildiğinde sıranın bana geldiğinden haberdar olabilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Üye sisteme giriş yapmıştır ve ödünç almak istediği kitap başka bir üyededir. | Üye, ödünçte olan kitap için bekleme sırasına girmek istediğinde, | Sistem üyeyi bekleme listesine ekler ve kitap iade edildiğinde üyeye sıranın kendisine geldiğini bildiren bir mesaj gönderir. |
| Olumsuz | Üye bekleme listesine katılmak istediği kitabın sayfasındadır. | Üye, sistemde bulunmayan bir kitap için bekleme sırasına girmeye çalıştığında, | Sistem üyeyi bekleme listesine eklemez ve kitabın bulunamadığını belirten bir uyarı mesajı gösterir. |

### US-04

Bir üye olarak, gecikme cezamı görüntülemek istiyorum, böylece varsa ceza tutarımı öğrenebilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Üye sisteme giriş yapmıştır. | Üye gecikme cezasını görüntülemek istediğinde, | Sistem üyeye varsa gecikme cezasının tutarını gösterir. |
| Olumsuz | Üye gecikme cezasını görüntüleme sayfasındadır. | Üyenin gecikme cezası bulunmadığında, | Sistem üyeye gecikme cezası bulunmadığını belirten bir bilgilendirme mesajı gösterir. |

### US-05

Bir kütüphane görevlisi olarak, kullanıcıların rollerini ve yetkilerini düzenlemek istiyorum, böylece herkes yalnızca yetki verilen işlemleri yapabilir.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Kütüphane görevlisi sisteme giriş yapmıştır. | Kütüphane görevlisi kullanıcıların rollerini ve yetkilerini düzenlediğinde, | Sistem yapılan değişiklikleri kaydeder ve kullanıcıların yalnızca kendilerine verilen yetkiler kapsamındaki işlemleri gerçekleştirmesini sağlar. |
| Olumsuz | Kütüphane görevlisi kullanıcıların rollerini ve yetkilerini düzenleme sayfasındadır. | Kütüphane görevlisi geçersiz bir rol veya yetki tanımlamaya çalıştığında, | Sistem değişiklikleri kaydetmez ve kullanıcıya geçersiz rol veya yetki tanımlandığını belirten bir uyarı mesajı gösterir. |

### US-06

Bir kütüphane görevlisi olarak, üyelerin kayıt bilgilerini görüntülemek istiyorum, böylece kütüphane işlemlerini doğru şekilde yönetebilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Kütüphane görevlisi sisteme giriş yapmıştır. | Kütüphane görevlisi bir üyenin kayıt bilgilerini görüntülemek istediğinde, | Sistem ilgili üyenin kayıt bilgilerini görüntüler ve kütüphane görevlisinin işlemleri doğru şekilde yönetmesini sağlar. |
| Olumsuz | Kütüphane görevlisi üye kayıtlarını görüntüleme sayfasındadır. | Kütüphane görevlisi sistemde kayıtlı olmayan bir üyeyi görüntülemeye çalıştığında, | Sistem üye bilgilerini görüntülemez ve üye kaydının bulunamadığını belirten bir uyarı mesajı gösterir. |

### US-07

Bir kütüphane görevlisi olarak, iade tarihi geçmiş olan kitapları listelemek istiyorum, böylece geciken ödünç işlemlerini takip edebilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Kütüphane görevlisi sisteme giriş yapmıştır. | Kütüphane görevlisi iade tarihi geçmiş olan kitapları listelemek istediğinde, | Sistem iade tarihi geçmiş kitapları ve bu kitapları ödünç alan üyelerin bilgilerini listeleyerek geciken ödünç işlemlerinin takip edilmesini sağlar. |
| Olumsuz | Kütüphane görevlisi geciken kitapları listeleme sayfasındadır. | Sistemde iade tarihi geçmiş herhangi bir kitap bulunmadığında, | Sistem geciken kitap bulunmadığını belirten bir bilgilendirme mesajı gösterir. |

### US-08

Bir kütüphane görevlisi olarak, katalogda bulunmayan kitapların kaydını kaldırmak istiyorum, böylece kitap listesini güncel tutabilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Kütüphane görevlisi sisteme giriş yapmıştır. | Kütüphane görevlisi katalogda bulunmayan bir kitabın kaydını kaldırmak istediğinde, | Sistem kitabın kaydını katalogdan kaldırır ve kitap listesini günceller. |
| Olumsuz | Kütüphane görevlisi kitap kayıtlarını yönetme sayfasındadır. | Kütüphane görevlisi sistemde bulunmayan bir kitabın kaydını kaldırmaya çalıştığında, | Sistem işlemi gerçekleştirmez ve kitabın kaydının bulunamadığını belirten bir uyarı mesajı gösterir. |

### US-09

Bir sistem yöneticisi olarak, kullanıcı hesaplarını oluşturmak, düzenlemek ve devre dışı bırakmak istiyorum, böylece sistemdeki kullanıcıları yönetmek istiyorum.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Sistem yöneticisi sisteme giriş yapmıştır. | Sistem yöneticisi yeni bir kullanıcı hesabı oluşturduğunda, mevcut kullanıcı bilgilerini düzenlediğinde veya bir kullanıcı hesabını devre dışı bıraktığında, | Sistem yapılan işlemi başarıyla gerçekleştirir ve kullanıcı hesabındaki değişiklikleri kaydeder. |
| Olumsuz | Sistem yöneticisi kullanıcı hesaplarını yönetme sayfasındadır. | Sistem yöneticisi zorunlu alanları boş bıraktığında, geçersiz bilgiler girdiğinde veya mevcut bir kullanıcı adıyla yeni hesap oluşturmaya çalıştığında, | Sistem işlemi gerçekleştirmez ve kullanıcıya hatanın nedenini açıklayan bir uyarı mesajı gösterir. |

### US-10

Bir sistem yöneticisi olarak, ödünç alma süresi ve gecikme cezası gibi sistem ayarlarını düzenlemek istiyorum, böylece kütüphane kurallarını güncel tutabilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Sistem yöneticisi sisteme giriş yapmıştır. | Sistem yöneticisi ödünç alma süresini ve gecikme cezasını geçerli değerlerle düzenleyip kaydettiğinde, | Sistem yeni ayarları kaydeder ve kütüphane kurallarını günceller. |
| Olumsuz | Sistem yöneticisi sistem ayarlarını düzenleme formundadır. | Sistem yöneticisi ödünç alma süresi veya gecikme cezası için geçersiz bir değer girdiğinde, | Sistem ayarları kaydetmez ve kullanıcıya anlaşılır bir hata mesajı gösterir. |

### US-11

Bir sistem yöneticisi olarak, sistemde gerçekleştirilen önemli işlemlerin kayıtlarını görüntülemek istiyorum, böylece yapılan değişiklikleri takip edebilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Sistem yöneticisi sisteme giriş yapmıştır. | Sistem yöneticisi sistemde gerçekleştirilen önemli işlemlerin kayıtlarını görüntülemek istediğinde, | Sistem gerçekleştirilen işlemlerin kayıtlarını tarih, işlem türü ve işlemi gerçekleştiren kullanıcı bilgileriyle birlikte gösterir. |
| Olumsuz | Sistem yöneticisi işlem kayıtlarını görüntüleme sayfasındadır. | Sistemde görüntülenecek herhangi bir işlem kaydı bulunmadığında, | Sistem kayıt bulunmadığını belirten anlaşılır bir bilgilendirme mesajı gösterir. |

### US-12

Bir sistem yöneticisi olarak, kütüphane sistemindeki verilerin yedeğini almak istiyorum, böylece veri kaybı yaşanması durumunda bilgileri geri yükleyebilirim.

| Senaryo | Verilen | Olduğunda | O zaman |
|---|---|---|---|
| Olumlu | Sistem yöneticisi sisteme giriş yapmıştır. | Sistem yöneticisi kütüphane sistemindeki verilerin yedeğini almak istediğinde, | Sistem verilerin yedeğini oluşturur ve yedekleme işleminin başarıyla tamamlandığını belirten bir mesaj gösterir. |
| Olumsuz | Sistem yöneticisi veri yedekleme sayfasındadır. | Yedekleme işlemi sırasında bir hata oluştuğunda, | Sistem yedekleme işlemini tamamlayamaz ve kullanıcıya anlaşılır bir hata mesajı gösterir. |

## 4. Fonksiyonel ve Fonksiyonel Olmayan Gereksinimler

### 4.1 Fonksiyonel Gereksinimler

| Kod | Gereksinim |
|---|---|
| FR-01 | Üyelerden sisteme girişte üye numarası ve şifre ister, üyeliği bulunmayan kişiler ise kayıt olma sayfasına yönlendirilir. |
| FR-02 | Sistem, üyelerin kitapları kitap adına, yazar adına veya kategoriye göre filtrelemesini sağlamalıdır. |
| FR-03 | Sistem seçili kitabı üyenin ödünç listesine eklemesini sağlamalı ve uygunluk durumunda onaylamalıdır. |
| FR-04 | Sistem, ödünç alınan kitapların iade işlemlerini kaydetmelidir. |
| FR-05 | Sistem ödünçte olan kitaplar için üyelerin bekleme sırasına katılmasını sağlamalıdır. |
| FR-06 | Sistem gecikmiş kitaplar için gecikme gününü ve ceza tutarını hesaplamalıdır. |
| FR-07 | Sistem üyelerin ilgi alanları, kitapların kategorileri veya okuma geçmişlerine göre kitap önerileri sunmalıdır. |
| FR-08 | Sistem, kütüphane görevlisinin yeni kitap kaydı oluşturmasını sağlamalıdır. |
| FR-09 | Sistem, kütüphane görevlilerinin üye bilgilerini görüntülemesini ve üye kayıtlarını yönetmesini sağlamalıdır. |
| FR-10 | Sistem, sistem yöneticisinin kullanıcı hesaplarını oluşturmasını, güncellemesini ve devre dışı bırakmasını sağlamalıdır. |
| FR-11 | Sistem, üyeye ödünç limitini en fazla 5 kitap şeklinde 14 gün rezerve süresince ve 1 kez de uzatma hakkı olacak şekilde sınırlamalıdır. |
| FR-12 | Sistem, üyeye rezervasyon sırası gelen kitabı 3 gün süre içerisinde almasını bildiren bir uygulama içi bir bildirim ile bilgilendirilmelidir. |

### 4.2 Fonksiyonel Olmayan Gereksinimler

| Kod | Gereksinim |
|---|---|
| NFR-01 | Sistem, giriş yapmamış kullanıcıların üyelere özel sayfalara erişmesini engellemelidir. |
| NFR-02 | Sistem, normal çalışma koşullarında kitap isteklerinin en az %60’ını 2 saniye içerisinde tamamlamalıdır. |
| NFR-03 | Aynı kitabın ödünç verilebilir kopya sayısından fazlasını herhangi bir kullanıcıya rezerve etmemelidir. |
| NFR-04 | Kayıt olunurken istenen kişisel bilgilerde eksik, hatalı veya geçersiz sembol kullanımı durumunda sistem ekrana hata mesajı vermelidir. |
| NFR-05 | Sistem giriş yapan üyenin yapmak istediği işlemleri kolay ve hızlı bir şekilde gerçekleştirebilmesini sağlamalıdır. |

## 5. Risk Tablosu

| Risk | Olasılık | Etki | Önlem |
|---|---|---|---|
| Aynı kitabın mevcut kopya sayısından fazla rezerve edilmesi (Çekirdek Problem) | Orta | Yüksek | Rezervasyon sırasında stok kontrolü yapılmalı ve eş zamanlı işlemler test edilmelidir. |
| Hatalı veya eksik bilgilerin kayıt sırasında kabul edilmesi | Orta | Orta | Zorunlu alan, veri tipi ve giriş doğrulama kontrolleri uygulanmalıdır. |
| Oturum yetkilendirme kontrollerinin hatalı çalışması | Düşük | Yüksek | Giriş, çıkış ve yetki kontrolleri farklı kullanıcı rolleriyle test edilmelidir. |
| Performans testlerinin yetersiz kalması nedeniyle yoğun kullanımda sistemin yavaşlaması | Orta | Orta | Farklı kullanıcı ve istek yükleri altında performans testleri yapılmalıdır. |
| Hatalı giriş denemelerine karşı yeterli güvenlik önlemi bulunmaması | Orta | Yüksek | Belirli sayıda başarısız giriş denemesinden sonra geçici hesap kilitleme uygulanmalı ve giriş denemeleri kontrol edilmelidir. |
| Ödünç geçmişine göre kitap önerilerinin yanlış oluşturulması (Çekirdek Problem) | Orta | Orta | Kitap önerileri oluşturulurken üyelerin ödünç alma geçmişi doğru analiz edilmeli ve öneri algoritması test edilmelidir. |
## 6. Use Case Diyagramı

Aşağıdaki diyagram, üye, kütüphane görevlisi ve sistem yöneticisinin sistemle etkileşimlerini göstermektedir.

![Kütüphane sistemi use case diyagramı](use-case.jpg)
