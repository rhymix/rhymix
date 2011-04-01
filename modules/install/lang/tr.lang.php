<?php
    /**
     * @file   en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  English language pack (Only basic contents are listed)
     **/

    $lang->introduce_title = 'XE Installation';
    $lang->license = <<<EndOfLicense
 <b>GNU KISITLI GENEL KAMU LİSANSI</b>
		       Sürüm 2.1, Şubat 1999

 Telif Hakkı © 1991, 1999 Free Software Foundation.
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 Bu lisans dökümanının birebir kopyalarını yapma ve dağıtma 
 izni herkese verilmiştir, fakat metinde değişiklik yapma izni yoktur.

[Bu döküman Kısıtlı GPL'in ilk yayınlanmış sürümüdür. Aynı zamanda 
 GNU Kitaplık Kamu Lisansı, 2. sürüm'ün devamı sayılmaktadır, bu 
 sebeple sürüm numarası 2.1 olarak verilmiştir.]

			    GİRİŞ 

  Yazılım lisanslarının çoğu sizin yazılımı paylaşma ve değiştirme 
hakkınızın elinizden alınması için hazırlanmıştır. Buna karşılık, 
GNU Genel Kamu Lisansları sizin serbest yazılımları değiştirme ve 
paylaşma hakkınızın mahfuz tutulması ve yazılımın bütün kullanıcıları 
için serbest olması amacı ile yazılmıştır.

  Bu Kısıtlı Genel Kamu Lisansı, bazı özel amaçlı yazılım paketleri 
-genelde kitaplıklar- için hazırlanmış olup, Free Software Foundation'un 
bazı yazılımları ve bu lisansı kullanmayı tercih eden diğer yazılımcıların
yazılımları için kullanılmaktadır. Siz de bu lisansı kullanabilirsiniz, 
fakat kullanmadan önce sizin özel durumunuz için bu lisansı mı, yoksa 
GNU Genel Kamu Lisansı (GPL) kullanmanın mı daha uygun bir strateji 
olacağını, aşağıdaki açıklamaları okuyarak düşünmeniz ve öyle karar 
vermenizi öneriyoruz. 

  Serbest yazılımdan bahsettiğimiz zaman fiyattan değil, özgürlükten 
bahsediyoruz. Bizim Genel Kamu Lisanslarımız, sizin serbest yazılımların
kopyalarını dağıtma özgürlüğünüzü (ve isterseniz bu hizmet için para almanızı)
, yazılım kaynak kodlarının size dağıtım esnasında veya eğer isterseniz 
verilmesini, yazılımı değiştirebilmenizi, yazılımın parçalarını yeni 
yazılımlar içerisinde kullanabilmenizi ve bunları yapabileceğinizi 
bilmenizi sağlamaktadır.

  Haklarınızı koruyabilmemiz için dağıtıcıların sizin haklarınızı kısıtlama
veya sizin bu haklarınızdan feragat etmenizi isteme yollarını yasaklayıcı
bazı kısıtlamalar getirmemiz gerekmektedir. Bu kısıtlamalar eğer kitaplığın
kopyalarını dağıtıyor veya değiştiriyorsanız size bazı yükümlülükler 
getirmektedir. 

  Örneğin kitaplığın kopyalarını, bedava veya ücret karşılığı dağıtıyorsanız 
alıcılara sizin sahip olduğunuz bütün hakları sağlamalısınız. Onların da 
kaynak kodlarına sahip olmalarını veya ulaşabilmelerini sağlamalısınız. 
Eğer kitaplık ile başka kodlar bağlanıyorsa, alıcılara bütün nesne dosyalarını 
vermelisiniz ki, alıcılar kitaplığın kaynak kodlarında değişiklik yapıp 
kitaplığı yeniden derledikten sonra nesne dosyalarını tekrar kitaplık ile 
bağlayabilsinler. Ve, onlara da haklarını bilebilmeleri için bu şartları 
göstermelisiniz.

  Haklarınızı iki koruma iki aşamada gerçekleşmektedir:
   1. Kitaplığa telif hakkı alınmaktadır.
   2. Yazılım lisansı olarak size, hukuki olarak, kitaplığı kopyalama, 
   dağıtma ve/veya değiştirme hakkı tanıyan bu lisans sunulmaktadır. 

  Her dağıtıcıyı korumak için bu serbest kitaplığın herhangi bir garantisi 
olmadığını vurgulamak istiyoruz. Eğer kitaplık başkası tarafından değiştirilmiş
ve değiştirilmiş hali ile tarafınıza ulaştırılmış ise alıcıların, ellerinde 
olan kitaplığın orjinal olmadığını, dolayısıyla başkaları tarafından eklenen 
problemlerin ilk yazarların şöhretlerine olumsuz etkide bulunmaması gerektiğini
 bilmelerini istiyoruz. 

  Son olarak, bütün serbest yazılımlar yazılım patentleri tarafından sürekli tehdit
altında bulunmaktadır. Herhangi bir şirketin serbest bir yazılımın kullanıcılarını, 
bir patent sahibinden kısıtlayıcı bir lisans alarak kısıtlama/engellemesinin mümkün 
olmamasından emin olmak istiyoruz. Dolayısıyla kitaplığın herhangi bir sürümü için 
alınabilecek bir patent lisansının bu lisans içerisinde tanımlanan tam serbesti ile 
uyumlu olması gerektiğini açık olarak ortaya koyuyoruz.

  Kitaplıklar da dahil olmak üzere çoğu GNU yazılımı normal GNU Genel Kamu Lisansı (GPL)
altında yayınlanmaktadır. GNU Kısıtlı Genel Kamu Lisansı (LGPL) olarak adlandırılan 
bu lisans ise, yalnızca bazı özel kitaplıklar için uygulanmakta ve GNU Genel Kamu 
Lisansı'ndan (GPL) bir hayli değişik koşullar içermektedir. Bizler bu lisansı (LGPL),
bazı kitaplıkların serbest olmayan yazılımlara bağlanabilmesine imkan tanımak amacı 
ile kullanıyoruz. 

  Bir yazılım bir kitaplık ile, gerek statik, gerek paylaşımlı kitaplık yolu ile 
bağlandığı zaman, ikisinin birleşimi, hukuki açıdan birleşik ve orjinal kitaplıktan
iştikak eden bir eser oluşturur. Dolayısıyla normal Genel Kamu Lisansı, böyle bir 
bağlanma işlemine ancak eğer oluşan bütün GPL'de tanımlanan serbesti kriterlerine 
uyuyor ise izin verir. Kısıtlı Genel Kamu Lisansı (LGPL) ise, kitaplık ile başka 
yazılımların bağlanması halinde daha gevşek kriterler uygular. 

  Bu lisansa "Kısıtlı" Genel Kamu Lisansı dememizin sebebi, kullanıcının haklarını 
korumak açısından Genel Kamu Lisansı'na göre daha kısıtlı olmasıdır. Bu lisans, 
serbest yazılım geliştiricilerine, serbest olmayan kapalı yazılım geliştiricileri 
ile rekabet etmeleri için daha kısıtlı imkanlar sunmaktadır. Bu dezavantajlar sebebi 
ile, pek çok kitaplık için bu lisansı değil, Genel Kamu Lisansı'nı (GPL) 
kullanmaktayız. Fakat Kısıtlı lisans bazı özel durumlarda bazı avantajlar 
sağlamaktadır. 

  Örneğin bazı özel ve seyrek rastlanan durumlarda, bir kitaplığın en 
yaygın kullanımınısağlamak ve özendirmek ve bu şekilde de-fakto 
standart haline gelmesini sağlamak istenebilir.Bunu sağlamak için, 
serbest olmayan yazılımların da kitaplığı kullanabilmesine imkan 
tanımak gerekir. Daha sık rastlanan bir durum ise, serbest bir 
kitaplığın serbest olmayan ve yaygın kullanımda olan kitaplıklarla 
aynı işlevi yapmasıdır. Böyle durumlarda serbest kitaplığı yalnız 
serbest yazılımlarla kullanılır hale getirmenin bir anlamı yoktur,
dolayısıyla Kısıtlı Genel Kamu Lisansı kullanılır. 

  Başka durumlarda, belirli bir kitaplığı serbest olmayan yazılımlarda
kullanma izninin verilmesi, daha çok sayıda kişinin çok sayıda serbest
yazılımı kullanmasına imkan verebilir. Örneğin GNU C kitaplığının 
serbest olmayan yazılımlarla birlikte kullanılabilmesi, pek çok kişinin
bütün GNU işletim sistemini ve onun bir türevi olan GNU/Linux işletim 
sistemini kullanmasına imkan tanımaktadır.

  Kısıtlı Genel Kamu Lisansı, kullanıcının özgürlüğünü korumakta daha 
kısıtlı ise de, Kitaplık ile bağlanan bir yazılımı kullanan kullanıcıya,
o yazılımı Kitaplığın değiştirilmiş bir hali ile kullanabilme hakkı ve
imkanı vermektedir. 

  Kopyalama, dağıtım ve değiştirme ile ilgili kesin şart ve kayıtlar 
aşağıda yer almaktadır. "kitaplığı baz alan eser" ile "kitaplığı 
kullanan eser" arasındaki farka özellikle dikkat edin. Birincisi 
kitaplığın kaynak kodlarından türeyen kod kullanmaktadır, ikincisi 
ise çalışmak için kitaplık ile bağlanmalıdır.

		  GNU KISITLI GENEL KAMU LİSANSI
   KOPYALAMA, DAĞITIM VE DEĞİŞTİRME İLE İLGİLİ ŞART VE KAYITLAR

  0. Bu Lisans, telif hakkı sahibi veya başka yetkili taraf tarafından
içerisine bu Kısıtlı Genel Kamu Lisansı altında dağıtıldığına dair 
ibare konmuş olan herhangi bir kitaplık veya başka yazılımı 
kapsamaktadır. Her ruhsat sahibine "siz" olarak hitap edilmektedir.

"Kitaplık", kolayca (kitaplığın içerdiği bazı işlev ve veriyi kullanan)
 uygulama yazılımları ile bağlanabilecek şekilde hazırlanmış yazılım 
 işlevleri ve/veya veri topluluğu anlamına gelmektedir.

Aşağıda "Kitaplık", bu koşullar altında dağıtılmış herhangi bir
yazılım kitaplığı veya eser manasında kullanılmaktadır. "Kitaplığı
baz alan eser", Kitaplık veya telif hakkı kanunu altında Kitaplık'tan 
iştikak etmiş, Kitaplığın tamamını veya bir parçasını, değiştirmeden 
veya değişiklikler ile veya başka bir dile tercüme edilmiş hali içeren
herhangi bir ürün manasında kullanılmaktadır. (Bundan sonra tercüme 
"değiştirme" kapsamında sınırsız olarak içerilecektir.)

Bir eserin "kaynak kodu", o esere değişiklik yapmak için en uygun 
imkan ve yöntem manasında kullanılmaktadır. Bir kitaplık için bütün 
kaynak kodu, kitaplığın içerdiği bütün modüllere ait bütün kaynak 
kodları, ilgili arayüz tanım dosyaları ve kitaplığın derleme ve 
kurulma işlemlerini kontrol etmekte kullanılan bütün betikler 
manasında kullanılmaktadır.

Kopyalama, dağıtım ve değiştirme haricinde kalan faaliyetler bu 
Lisans'ın kapsamı dışındadırlar. Kitaplığı kullanan bir yazılımı 
çalıştırma eylemi sınırlandırılmamıştır ve böyle bir yazılımın çıktısı 
yalnızca çıktının içeriği (Kitaplığı yazmak için kullanılan bir araçta 
Kitaplığın kullanılmasından bağımsız olarak) Kitaplığı baz alan ürün 
kapsamına girer ise bu Lisans kapsamındadır. Bu koşulun sağlanıp 
sağlanmadığı Kitaplığın ve Kitaplığı kullanan yazılımın ne yaptığı 
ile ilgilidir.

1.  Kitaplığın bütün kaynak kodlarını birebir, aldığınız şekilde, 
herhangi bir ortamda ve vasıta ile, uygun ve görünür bir şekilde 
telif hakkı bildirimi ve garantisiz olduğuna dair bildirim koymak, 
bu Lisans'dan bahseden herhangi bir bildirimi aynen muhafaza etmek ve 
bütün diğer alıcılara Kitaplık ile birlikte bu Lisans'ın bir kopyasını
vermek şartı ile kopyalayabilir ve dağıtabilirsiniz.

Kopyalamak fiili işlemi için bir ücret talep edebilir ve sizin seçiminize
bağlı olarak ücret karşılığı garanti verebilirsiniz.

2.  Kitaplığın kopyasını veya kopyalarını veya herhangi bir parçasını
değiştirerek Kitaplığı baz alan ürün elde edebilir, bu değişiklikleri
veya ürünün kendisini yukarıda 1. bölümdeki şartlar dahilinde ve aşağıda
sıralanan şartların yerine getirilmesi koşulu ile kopyalayabilir ve 
dağıtabilirsiniz.

a) Değiştirilen eser de bir yazılım kitaplığı olmalıdır.

b) Değiştirilen dosyaların görünür bir şekilde dosyaların sizin 
tarafınızdan değiştirildiğine dair, tarihli bir bildirim içermesini 
sağlamalısınız.

c) Eserin bütününün bütün üçüncü şahıslara bu Lisans şartları altında
ücretsiz olarak ruhsatlanmasını sağlamalısınız.

d) Eğer değiştirilmiş Kitaplıktaki bir özellik, bu özellik çağrıldığı
zaman verilecek bir argüman haricinde uygulama yazılımı tarafından 
ağlanacak bir işlev ya da veri tablosuna başvuruda bulunuyor ise, 
o zaman uygulama yazılımı böyle bir işlev ya da tablo sağlamasa dahi 
özelliğin çalışır durumda kalacağı ve amacının anlamlı kalan parçası 
her ne ise onu yerine getirmeye devam edeceğine dair iyi niyetli bir 
uğraş vermelisiniz.

(Örneğin bir kitaplıkta karekök hesaplayan bir özellik uygulamadan 
tamamen bağımsız olarak tanımlanabilen bir işleve sahiptir. Dolayısıyla 
2d bölümü bu özellik tarafından kullanılan ve uygulama yazılımı 
tarafından sağlanan herhangi bir işlev ya da tablonun seçime bağlı 
olmasını şart koşar: Eğer uygulama bu işlev ya da veriyi sağlamaz ise, 
karekök özelliği karekök hesaplayabilir olmalıdır.)

Bu şartlar değiştirilmiş eserin tamamını kapsamaktadır. Eğer eserin 
tespit edilebilir kısımları Kitaplık'tan iştikak etmemiş ise ve makul 
surette kendi başlarına bağımsız ve ayrı eserler olarak kabul edilebilir 
ise, o zaman bu Lisans ve şartları, bu parçaları ayrı eser olarak 
dağıttığınız zaman bağlayıcı değildir. Fakat, aynı parçaları Kitaplığı 
baz alan bir ürün bütününün bir parçası olarak dağıttığınız zaman bütünün 
dağıtımı, diğer ruhsat sahiplerine verilen izinlerin bütüne ait olduğu ve 
parçalarına, yazarının kim olduğuna bakılmaksızın bütün parçalarına tek tek 
e müşterek olarak uygulandığı bu Lisans şartlarına uygun olmalıdır.

Bu bölümün hedefi tamamen sizin tarafınızdan yazılan bir eser üzerinde 
hak iddia etmek veya sizin böyle bir eser üzerindeki haklarınıza muhalefet 
etmek değil, Kitaplığı baz alan, Kitaplık'tan iştikak etmiş veya müşterek 
olarak ortaya çıkarılmış eserlerin dağıtımını kontrol etme haklarını d
üzenlemektir.

Buna ek olarak, Kitaplığı baz almayan herhangi bir ürünün Kitaplık 
ile (veya Kitaplığı baz alan bir ürün ile) bir bilgi saklama ortamında 
veya bir dağıtım ortamında beraber tutulması diğer eseri bu Lisans 
kapsamına sokmaz.

3.  Kitaplığın herhangi bir kopyasına bu Lisans şartları yerine GNU 
Genel Kamu Lisansı şartlarını uygulamayı tercih edebilirsiniz. Bunu 
yapmak için bu Lisans'a yapılan her referansı GNU Genel Kamu Lisansı, 
2. sürüm olarak değiştirmelisiniz. (Eğer GNU Genel Kamu Lisansı'nın 2. 
sürümden daha üst numaralı bir sürümü çıkmışsa, o sürüm numarasını 
belirtebilirsiniz. Bu bildirimlerde başka bir değişiklik yapmayınız.

Bu değişiklik Kitaplığın herhangi bir kopyasına uygulandıktan sonra 
o kopya için geri dönülemez, dolayısıyla o kopyadan yapılan bütün 
kopyalar ve o kopyadan iştikak eden bütün eserler GNU Genel Kamu 
Lisansı altında lisanslanır.

Bu seçenek, Kitaplığın bazı kodlarını kitaplık olmayan bir yazılıma 
kopyalamak istediğiniz zaman faydalıdır.

4.  Kitaplığı ( veya 2. bölümde tanımlandığı hali ile onu baz 
alan bir ürünü) ara derlenmiş veya uygulama hali ile 1. ve 2. Bölüm'deki
 şartlar dahilinde ve yaygın olarak yazılım dağıtımında kullanılan bir 
 ortam üzerinde, bilgisayar tarafından okunabilir ve 1. ve 2. Bölüm'deki 
 şartlar dahilinde dağıtılabilir kaynak kodlarının tamamı ile birlikte 
 kopyalayabilir ve dağıtabilirsiniz.

Eğer ara derlenmiş nesne kodlarının dağıtımı belli bir yere erişim ve 
oradan kopyalama imkanı olarak yapılıyorsa, aynı yerden, aynı koşullar 
altında kaynak koduna erişim imkanı sağlamak, üçüncü şahısların ara 
derlenmiş nesne kodları ile birlikte kaynak kodunu kopyalama zorunlulukları 
olmasa bile kaynak kodunu dağıtmak olarak kabul edilmektedir.

5.  Kitaplığın herhangi bir parçasından iştikak etmiş herhangi bir parça 
bulundurmayan fakat Kitaplık ile ona bağlanarak ve derlenerek çalışmak için 
tasarlanmış bir yazılım, "Kitaplığı kullanan eser" olarak tanımlanmaktadır. 
Tek başına böyle bir eser Kitaplık'tan iştikak eden bir eser değildir ve bu 
Lisans'ın kapsamı dahiline girmez.

Fakat, "Kitaplığı kullanan bir eser" ile Kitaplığı bağlama işlemi, Kitaplıktan 
iştikak eden bir uygulamayı vücuda getirir (çünkü Kitaplığın parçalarını 
içermektedir). Dolayısıyla derleme/bağlama işlemi sonucunda elde edilen 
uygulama bu Lisans kapsamındadır. 6. Bölüm bu kapsama giren uygulama 
yazılımlarının dağıtım koşullarını içermektedir.

"Kitaplığı kullanan bir eser", Kitaplığın parçası olan bir başlık (header) 
dosyasından materyal kullandığı zaman, eserin kaynak kodları Kitaplıktan 
iştikak eden bir eser olmamasına rağmen eserin nesne kodları Kitaplıktan 
iştikak eden bir eser olabilir. Bunun doğru olup olmadığı özellikle eserin 
kendisinin bir kitaplık olup olmadığına veya eserin Kitaplık olmaksızın 
bağlanıp bağlanamadığına göre değişebilir. Bu koşulun ne zaman geçerli 
olacağı kanun kapsamında açık ve seçik olarak tanımlanmamıştır.

Eğer böyle bir nesne dosyası yalnızca nümerik parametreler, veri yapısı 
şablonları ve erişim yolları ve küçük makro ve içerilmiş (inline) işlevler 
(10 satır veya daha az uzunlukta) içeriyorsa, o zaman hukuki olarak iştikak 
eden bir eser olup olmadığına bakılmaksızın nesne dosyasının kullanımı 
sınırlandırılmamıştır. (Bu nesne dosyasını ve Kitaplığın parçalarını içeren 
uygulama dosyaları 6. Bölümün kapsamına girmeye devam etmektedir).

Eğer nesne dosyası, Kitaplıktan iştikak etmiş bir eser ise, eserin nesne 
kodlarını 6. Bölümdeki koşullar uyarınca dağıtabilirsiniz. Eseri içeren 
uygulama dosyaları da, Kitaplık ile direkt olarak bağlanıp bağlanmadıklarına 
bakılmaksızın 6. Bölüm kapsamına girer.

6.  Yukarıdaki Bölümlere bir istisna olarak, "Kitaplığı kullanan bir eser" 
ile Kitaplığı bağlayarak Kitaplığı içeren bir eser oluşturabilir ve 
uygulayacağınız koşullar eseri müşterinin kendi kullanımı için değiştirmesine 
ve bu değişiklikleri test edebilmesine imkan tanımak için eserin geri 
çözümlenebilmesine imkan tanıdığı sürece, bu eseri istediğiniz koşullar 
altında dağıtabilirsiniz.

Eserin her kopyası ile birlikte eserde Kitaplığın kullanıldığına ve kullanımıyla 
ilgili şart ve koşulların bu Lisans ile düzenlendiğine dair görünür ve belirgin 
bir bildirim vermelisiniz. Bu Lisans'ın bir kopyasını eserle birlikte vermelisiniz. 
Eğer eser çalışma esnasında telif hakkı bildirimleri gösteriyor ise, Kitaplık için 
telif hakkı bildirimini ve kullanıcıyı Lisans'ın kopyasına yönlendiren bir bildirimi 
de bu esnada göstermelisiniz. Ayrıca aşağıdaki koşullardan birini yerine 
getirmelisiniz:

a) Eseri, Kitaplıkta eser için yapılan değişikliklerin tümünü (bu değişiklikler 
1. ve 2. Bölüm kapsamında dağıtılmalıdır) içeren ve Kitaplığa ait ve bilgisayar 
tarafından okunabilir kaynak kodlarının tamamı ile; ve eğer eser Kitaplık ile 
bağlanmış bir uygulama ise, nesne kodları ve/veya kaynak kodları ile, kullanıcıların 
Kitaplık'ta değişiklikler yaptıktan sonra yeniden bağlama işlemi ile değiştirilmiş 
Kitaplığı kapsayan değiştirilmiş eser oluşturabilecekleri şekilde dağıtmalısınız. 
(Kitaplıktaki tanım dosyalarını değiştiren bir kullanıcının uygulamayı değiştirilmiş 
olan tanımları kullanabilecek şekilde yeniden derleyemeyebileceği anlaşılmakta 
ve kabul edilmektedir.)

b) Kitaplık ile bağlanmak için uygun bir paylaşımlı kitaplık mekanizması kullanılmalıdır. 
Uygun bir mekanizma, (1) Kitaplık işlevlerini uygulamanın içerisine kopyalamak yerine 
Kitaplığın kullanıcının bilgisayarında zaten mevcut olan bir kopyasını çalışma zamanında 
kullanan, ve (2) eserin oluşturulmasında kullanılan Kitaplık sürümü ile arayüz bakımından 
uyumlu olmak kaydıyla kullanıcı tarafından kurulan değiştirilmiş bir Kitaplıkla sorunsuz 
çalışabilen bir mekanizma olarak tanımlanmıştır.

c) Aynı kullanıcıya, fiziksel olarak dağıtımı gerçekleştirme masrafınızdan daha fazla 
ücret almayarak, yukarıda 6a Bölümünde belirlenen materyalleri vereceğinize dair en az 
üç yıl geçerli olacak yazılı bir taahhütname vermelisiniz.

d)Eğer eserin dağıtımı belli bir yere erişim ve oradan kopyalama imkanı olarak yapılıyorsa,
aynı yerden, aynı koşullar altında yukarıda belirtilen materyallere erişim imkanı 
sağlamalısınız.

e) Kullanıcının bu materyallerin bir kopyasını daha önce almış olduğunu veya bu kullanıcıya 
daha önce bir kopya vermiş olduğunuzu tevsik etmelisiniz.

Uygulamalar için, "Kitaplığı kullanan eser"in kabul edilen biçemi uygulamayı tekrar
oluşturmak için gereken bütün veri ve yardımcı yazılımları kapsamalıdır. Özel bir 
istisna olarak, eğer söz konusu bileşen uygulama ile birlikte dağıtılmıyor ise, 
genelde uygulamanın çalıştırıldığı işletim sisteminin ana parçaları (derleyici, 
çekirdek v.b.) ile birlikte dağıtılan (gerek kaynak kodu, gerek ikilik dosya biçeminde) 
herhangi bir bileşen bu kapsamın dışında tutulmuştur.

Bu koşul, işletim sistemi ile birlikte dağıtılmayan bazı serbest olmayan 
kitaplıkların dağıtım koşulları ile çelişebilir. Böyle bir çelişki, dağıttığınız 
bir uygulamada hem bu serbest olmayan kitaplıkları hem de Kitaplığı beraber 
kullanamayacağınız manasına gelir.

7.  Kitaplığı baz alan eser olan kitaplık özellikleri ile bu Lisans kapsamında 
olmayan başka kitaplık özelliklerini yan yana tek bir kitaplık içerisine koyabilir 
ve böyle elde edilen bileşik kitaplığı aşağıdaki iki koşulu yerine getirmek ve 
Kitaplığı baz alan eserin ve diğer kitaplığa ait özelliklerin tek başına dağıtılmasına 
izin verilmesi koşulu ile dağıtabilirsiniz.

a) Bileşik kitaplığın yanısıra kitaplıkta içerilen Kitaplığı baz alan eserin diğer 
kitaplıkla birleştirilmemiş bir kopyasını dağıtmalısınız. Bu dağıtım işlemi yukarıdaki 
Bölümlere uygun olmalıdır.

b) Bileşik kitaplık ile birlikte, bileşik kitaplığın bir parçasının Kitaplığı baz 
alan eser olduğuna dair görünür ve belirgin bir bildirim vermeli ve yanında dağıtılan 
eserin birleştirilmemiş halinin nasıl bulunacağını açıklamalısınız.

8.  Kitaplığı bu Lisans'ta sarih olarak belirtilen şartlar haricinde kopyalayamaz, 
değiştiremez, ruhsat hakkını veremez ve dağıtamazsınız. Buna aykırı herhangi bir kopyalama, 
değiştirme, ruhsat hakkı verme, veya dağıtımda bulunma hükümsüzdür ve böyle bir teşebbüs 
halinde bu Lisans altındaki bütün haklarınız iptal edilir. Sizden, bu Lisans kapsamında 
kopya veya hak almış olan üçüncü şahıslar, Lisans şartlarına uygunluklarını devam 
ettirdikleri sürece, ruhsat haklarını muhafaza edeceklerdir.

9.  Bu Lisans sizin tarafınızdan imzalanmadığı için bu Lisans'ı kabul etmek 
zorunda değilsiniz. Fakat, size Kitaplığı veya onu baz alan ürünleri 
değiştirmek veya dağıtmak için izin veren başka bir belge yoktur. 
Eğer bu Lisans'ı kabul etmiyorsanız bu eylemler kanun tarafından sizin 
için yasaklanmıştır. Dolayısıyla, Kitaplığı (veya onu baz alan bir ürünü) 
değiştirmeniz veya dağıtmanız bu Lisans'ı ve Lisans'ın Kitaplığı veya ondan 
iştikak etmiş bütün eserleri kopyalamak, değiştirmek ve dağıtmak için getirdiği 
şart ve kayıtları kabul ettiğiniz manasına gelmektedir.

10.  Kitaplığı (veya onu baz alan herhangi bir ürünü) yeniden dağıttığınız 
her defada alıcı, ilk ruhsat sahibinden otomatik olarak Kitaplığı bu şartlar 
ve kayıtlar dahilinde kopyalamak, değiştirmek ve dağıtmak için ruhsat 
almaktadır. Alıcının burada verilen hakları kullanmasına ek bir takım 
kısıtlamalar getiremezsiniz. Üçüncü şahısları bu Lisans mucibince hareket 
etmeğe mecbur etmek sizin sorumluluk ve yükümlülüğünüz altında değildir.

11.  Eğer bir mahkeme kararı veya patent ihlal iddiası veya herhangi başka 
bir (patent meseleleri ile sınırlı olmayan) sebep sonucunda size, bu Lisans'ın 
şart ve kayıtlarına aykırı olan bir takım (mahkeme kararı, özel anlaşma veya 
başka bir şekilde) kısıtlamalar getirilirse, bu sizi bu Lisans şart ve kayıtlarına 
uyma mecburiyetinden serbest bırakmaz. Eğer aynı anda hem bu Lisans'ın şartlarını 
yerine getiren hem de diğer kısıtlamalara uygun olan bir şekilde Kitaplığı 
dağıtamıyorsanız, o zaman Kitaplığı dağıtamazsınız. Örneğin, eğer bir patent 
lisansı direkt veya endirekt olarak sizden kopya alacak olan üçüncü şahısların 
bedel ödemeksizin Kitaplığı dağıtmalarına hak tanımıyorsa o zaman sizin hem bu 
koşulu hem Lisans koşullarını yerine getirmenizin tek yolu Kitaplığı dağıtmamak 
olacaktır.

Eğer bu bölümün herhangi bir parçası herhangi bir şart altında uygulanamaz 
veya hatalı bulunur ise o şartlar dahilinde bölümün geri kalan kısmı, bütün 
diğer şartlar altında da bölümün tamamı geçerlidir.

Bu bölümün amacı sizin patent haklarını, herhangi bir mülkiyet hakkını ihlal 
etmenize yol açmak veya bu hakların geçerliliğine muhalefet etmenizi sağlamak 
değildir; bu bölümün bütün amacı kamu lisans uygulamaları ile oluşturulan 
serbest yazılım dağıtım sisteminin bütünlüğünü ve işlerliğini korumaktır. 
Bu sistemin tutarlı uygulanmasına dayanarak pek çok kişi bu sistemle dağıtılan 
geniş yelpazedeki yazılımlara katkıda bulunmuştur; yazılımını bu veya başka bir 
sistemle dağıtmak kararı yazara aittir, herhangi bir kullanıcı bu kararı veremez.

Bu bölüm Lisans'ın geri kalanının doğurduğu sonuçların ne olduğunu açıklığa 
kavuşturmak amacını gütmektedir.

12.  Eğer Kitaplığın kullanımı ve/veya dağıtımı bazı ülkelerde telif hakkı 
taşıyan arayüzler veya patentler yüzünden kısıtlanırsa, Kitaplığı bu Lisans 
kapsamına ilk koyan telif hakkı sahibi, Kitaplığın yalnızca bu ülkeler haricinde 
dağıtılabileceğine dair açık bir coğrafi dağıtım kısıtlaması koyabilir. Böyle bir 
durumda bu Lisans bu kısıtlamayı sanki Lisans'ın içerisine yazılmış gibi kapsar.

13.  Free Software Foundation zaman zaman Kısıtlı Genel Kamu Lisansı'nın yeni 
ve/veya değiştirilmiş biçimlerini yayınlayabilir. Böyle yeni sürümler mana 
olarak şimdiki haline benzer olacaktır, fakat doğacak yeni problemler veya 
kaygılara cevap verecek şekilde detayda farklılık arzedebilir.

Her yeni biçime ayırdedici bir sürüm numarası verilmektedir. Eğer Kitaplık 
bir sürüm numarası belirtiyor ve "bu ve bundan sonraki sürümler" altında 
dağıtılıyorsa, belirtilen sürüm veya Free Software Foundation tarafından 
yayınlanan herhangi sonraki bir sürümün şart ve kayıtlarına uymakta serbestsiniz. 
Eğer Kitaplık Lisans için bir sürüm numarası belirtmiyor ise, Free Software 
Foundation tarafından yayınlanmış olan herhangi bir sürümün şart ve kayıtlarına 
uymakta serbestsiniz.

14.  Eğer bu Kitaplığın parçalarını dağıtım koşulları farklı olan başka 
serbest yazılımların içerisinde kullanmak isterseniz, yazara sorarak izin isteyin. 
Telif hakkı Free Software Foundation'a ait olan yazılımlar için Free Software 
Foundation'a yazın, bazen istisnalar kabul edilmektedir. Kararımız, serbest 
yazılımlarımızdan iştikak etmiş yazılımların serbest statülerini korumak ve 
genel olarak yazılımların yeniden kullanılabilirliğini ve paylaşımını sağlamak 
amaçları doğrultusunda şekillenecektir. 

			    GARANTİ YOKTUR

15.  BU KİTAPLIK ÜCRETSİZ OLARAK RUHSATLANDIĞI İÇİN, KİTAPLIK İÇİN İLGİLİ 
KANUNLARIN İZİN VERDİĞİ ÖLÇÜDE HERHANGİ BİR GARANTİ VERİLMEMEKTEDİR. AKSİ YAZILI 
OLARAK BELİRTİLMEDİĞİ MÜDDETÇE TELİF HAKKI SAHİPLERİ VE/VEYA BAŞKA ŞAHISLAR 
KİTAPLIĞI "OLDUĞU GİBİ", AŞİKAR VEYA ZIMNEN, SATILABİLİRLİĞİ VEYA HERHANGİ 
BİR AMACA UYGUNLUĞU DA DAHİL OLMAK ÜZERE HİÇBİR GARANTİ VERMEKSİZİN DAĞITMAKTADIRLAR. 
KİTAPLIĞIN KALİTESİ VEYA PERFORMANSI İLE İLGİLİ TÜM SORUNLAR SİZE AİTTİR. KİTAPLIKTA
 HERHANGİ BİR BOZUKLUKTAN DOLAYI DOĞABİLECEK OLAN BÜTÜN SERVİS, TAMİR VEYA DÜZELTME
 MASRAFLARI SİZE AİTTİR.

16.  İLGİLİ KANUNUN İCBAR ETTİĞİ DURUMLAR VEYA YAZILI ANLAŞMA HARİCİNDE HERHANGİ 
BİR ŞEKİLDE TELİF HAKKI SAHİBİ VEYA YUKARIDA İZİN VERİLDİĞİ ŞEKİLDE KİTAPLIĞI 
DEĞİŞTİREN VEYA YENİDEN DAĞITAN HERHANGİ BİR KİŞİ, KİTAPLIĞIN KULLANIMI VEYA 
KULLANILAMAMASI (VEYA VERİ KAYBI OLUŞMASI, VERİNİN YANLIŞ HALE GELMESİ, SİZİN 
VEYA ÜÇÜNCÜ ŞAHISLARIN ZARARA UĞRAMASI VEYA KİTAPLIĞIN BAŞKA YAZILIMLARLA 
BERABER ÇALIŞAMAMASI) YÜZÜNDEN OLUŞAN GENEL, ÖZEL, DOĞRUDAN YA DA DOLAYLI 
HERHANGİ BİR ZARARDAN, BÖYLE BİR TAZMİNAT TALEBİ TELİF HAKKI SAHİBİ VEYA İLGİLİ 
KİŞİYE BİLDİRİLMİŞ OLSA DAHİ, SORUMLU DEĞİLDİR. 

		     ŞART VE KAYITLARIN SONU 

EndOfLicense;

    $lang->install_condition_title = "Lütfen kurulum gereksinimlerini kontrol ediniz.";

    $lang->install_checklist_title = array(
			'php_version' => 'PHP Sürümü',
            'permission' => 'Yetki',
            'xml' => 'XML Kitaplığı',
            'iconv' => 'ICONV Kitaplığı',
            'gd' => 'GD Kitaplığı',
            'session' => 'Session.auto_start(otomatik.oturum_acma) ayarı',
        );

    $lang->install_checklist_desc = array(
			'php_version' => '[Gerekli] Eğer PHP sürümü 5.2.2 ise, XE yazılım hatasından dolayı kurulmayacaktır',
            'permission' => '[Gerekli] XE kurulum yolu ya da ./files directory yolunun yetkisi 707 olmalıdır',
            'xml' => '[Gerekli] XML iletişimi için XML kitaplığı gereklidir.',
            'session' => '[Gerekli] PHP ayar dosyasındaki (php.ini) \'Session.auto_start\' XE\'nin oturumu kullanabilmesi için sıfıra eşit olmalıdır',
            'iconv' => 'Iconv, UTF-8 ve diğer dil ayarlarını değiştirebilmek için kurulmuş olmalıdır',
            'gd' => 'GD Kitaplığı, resim değiştirme özelliğini kullanabilmek için kurulmuş, olmalıdır',
        );

    $lang->install_checklist_xml = 'XML Kitaplığını Kur';
    $lang->install_without_xml = 'XML Kitaplığı kurulmamış';
    $lang->install_checklist_gd = 'GD Kitaplığı Kur';
    $lang->install_without_gd  = 'GD Library, resim dönüştürmek için, kurulmamış';
    $lang->install_checklist_gd = 'GD Kitaplığını Kur';
    $lang->install_without_iconv = 'Iconv Kitaplığı, karakterleri sıralamak için, kurulmamış';
    $lang->install_session_auto_start = 'Olası hatalar php ayarlarından dolayı oluşabilir. session.auto_start 1\'e eşit olmalıdır';
    $lang->install_permission_denied = 'Kurulum yolu yetkisi 707\'ye eşit değil';

    $lang->cmd_agree_license = 'Lisansı kabul ediyorum';
    $lang->cmd_install_fix_checklist = 'Gerekli koşulları tamamladım.';
    $lang->cmd_install_next = 'Kuruluma Devam Et';
    $lang->cmd_ignore = 'Önemseme';

    $lang->db_desc = array(
        'mysql' => 'PHP\'de mysql*() özellikleri için MySQL\'ü veritabanı olarak kullanınız.<br />İşlemler, veritabanı dosyası myisam \'da oluşturulduğu zaman işlenmeyecektir.',
        'mysqli' => 'PHP\'de mysqli*() özellikleri için MySQL\'ü veritabanı olarak kullanınız.<br />İşlemler, veritabanı dosyası myisam \'da oluşturulduğu zaman işlenmeyecektir.',
        'mysql_innodb' => 'innodb ile MySQL\'ü veritabanı olrak kullanınız.<br />İşlemler, innodb ile işlenecektir',
        'sqlite2' => '\'Verileri dosya olarak kaydeden sqlite2 \'yi veritabanı olarak kullanınız. <br />VT dosyası tarayıcıdan erişilebilir <b>olmamalıdır</b>.<br />(Sabitleme için hiç test edilmedi)',
        'sqlite3_pdo' => 'PHP\'nin PDO\'sunun desteğiyle sqlite3\'ü veritabanı olarak kullanınız.<br />VT dosyası tarayıcıdan erişilebilir <b>olmamalıdır</b>.',
        'cubrid' => 'CUBRID\'ü veritabanı olarak kullanın. Daha fazla bilgi için <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manuel</a>i inceleyiniz',
        'mssql' => 'MSSQL\'ü veritabanı olarak kullanın',
        'postgresql' => 'PostgreSql\'ü veritabanı olarak kullanın.',
        'firebird' => 'Firebird\'ü veritabanı olarak kullanın.<br /> (create database "/path/dbname.fdb" page_size=8192 default character set UTF-8;) ile veritabanı oluşturabilirsiniz',
    );

    $lang->form_title = 'Veritabanı &amp; Yönetici Bilgisi';
    $lang->db_title = 'Lütfen Veritabanı bilgisini giriniz';
    $lang->db_type = 'Veritabanı Tipi';
    $lang->select_db_type = 'Lütfen kullanmak istediğiniz Veritabanını seçiniz.';
    $lang->db_hostname = 'Veritabanı Sunucuadı';
    $lang->db_port = 'Veritabanı Portu';
    $lang->db_userid = 'Veritabanı ID';
    $lang->db_password = 'Veritabanı Şifresi';
    $lang->db_database = 'DB Database';
    $lang->db_database_file = 'DB Database File';
    $lang->db_table_prefix = 'Tablo Başlığı';

    $lang->admin_title = 'Yönetici Bilgisi';

    $lang->env_title = 'Yapılandırma';
    $lang->use_optimizer = 'Optimizasyonu Etkinleştir';
    $lang->about_optimizer = 'Eğer Optimizasyon etkinleştirildiyse, çoklu CSS / JS dosyaları gönderimden önce sıkıştırılıp bir araya konulduğundan, kullanıcılar siteye hızlı bir şekilde ulaşacaktır. <br /> Ancak;  bu optimizasyon, CSS ve JS\'ye göre sorunlu olabilir. Eğer bunu devre dışı bırakırsanız, düzgün bir şekilde çalışmasına karşın daha yavaş çalışacaktır.';
    $lang->use_rewrite = 'YenidenYazma Modu (mod_rewrite)';
    $lang->use_sso = 'Tekli Oturum Açma';
    $lang->about_rewrite = "Eğer websunucusu yenidenyazma(rewritemod) destekliyorsa, http://ornek/?dosya_no=123 gibi URLler http://ornek/123 olarak kısaltılabilir";
	$lang->about_sso = 'SSO kullanıcıları, geçreli ya da sanal siteye bir kere kayıt olmakla, ikisinden de yararlandıracaktır. Bu, size sadece sanal websiteler kullandığınız durumda lazım olacaktır.';
    $lang->time_zone = 'Zaman Dilimi';
    $lang->about_time_zone = "Eğer sunucu zaman dilimi ve bulunduğunuz yerin zaman dilimi uyumlu değilse; zaman dilimi özelliğini kullanarak zamanı bulunduğunuz yere göre ayarlayabilirsiniz ";
    $lang->qmail_compatibility = 'Qmail\'i Etkinleştir';
    $lang->about_qmail_compatibility = 'Bu size QMail gibi CRLF\'den ayırt edilemeyen MTA\'dan mail gönderme imkanı sağlayacaktır.';

    $lang->about_database_file = 'Sqlite veriyi dosyaya kaydeder. Veritabanı dosyası tarayıcıyla erişilebilir olmamalıdır.<br/><span style="color:red">Veri dosyası 707 yetki kapsamı içinde olmalıdır.</span>';

    $lang->success_installed = 'Kurulum tamamlandı';

    $lang->msg_cannot_proc = 'Kurulum ortamı devam etmek için uygun değil.';
    $lang->msg_already_installed = 'XE zaten kurulmuştur';
    $lang->msg_dbconnect_failed = "VT\'ye ulaşırken bir hata oluştu.\nLütfen VT bilgisini tekrar kontrol ediniz";
    $lang->msg_table_is_exists = "Tablo zaten VT\'da oluşturuldu.\nYapılandırma dosyası yeniden oluşturuldu";
    $lang->msg_install_completed = "Kurulum tamamlandı.\nXE\'yi seçtiğiniz için teşekkür ederiz";
    $lang->msg_install_failed = "Kurulum dosyası oluşturulurken bir hata oluştu.";

    $lang->ftp_get_list = "Liste Al";
?>
