<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KVKK ve Gizlilik Politikası</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18]">
        @include('components.site-header')
        <div class="container mx-auto max-w-3xl p-6 bg-white">
            <h1 class="text-2xl font-semibold text-[#4c2447] mb-4">Kişisel Veri ve Gizlilik Politikamız</h1>
            <p class="mb-3">İstanbul İl Dernekler Kütüğüne 34-242-113 numarası ile kayıtlı Özgür Yazılım Derneği olarak, işbu web sitesine bağlanırken işlenen tüm verileriniz adına Kanunun 10. maddesi kapsamında aydınlatma metnimiz, şeffaflık ve bilgilendirme adına aşağıdaki gibidir.</p>
            <p class="mb-4">Özetle; Bu metin verilerinizin hangi hukuki dayanakla işlendiğine ve işleme koşullarına ilişkindir.</p>

            <h2 class="text-xl font-semibold mt-6 mb-2">İşlenebilecek Kişisel Verileriniz ve İşlenme Amaçları</h2>
            <p class="mb-3">Kişisel verileriniz sadece tarafımıza bağışta bulunurken mecbur olduğumuz kapsamda, gerekli olduğu ölçüde işlenecektir. İşlenecek olan verileriniz bağış sistemindeki gereklilikler dahilinde işlenecek ve bağış konusu dışında kullanılmayacaktır.</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Ödeme bilgileriniz, bağışınızın tarafımıza ulaştırılabilmesi için sanal POS sistemine iletilmesi amacıyla,</li>
                <li>Telefon numaranız ve/veya e‑posta adresiniz, talebiniz üzerine sizinle etkili bir iletişim sürdürülebilmesi için,</li>
                <li>Yazışma adresiniz, talebiniz üzere alındı belgesinin ve eğer yaptığınız bağış karşılığında bir hediye alıyorsanız ilgili hediyenin tarafınıza ulaştırılabilmesi için,</li>
                <li>Kimlik bilgileriniz, yaptığınız bağışın dernek işletme defterine yazılabilmesi için,</li>
                <li>Dolaylı olarak elde edilecek IP adresiniz gibi kişisel verileriniz, web sitesinin çalışabilmesi için,</li>
            </ul>
            <p class="mb-3">işlenecektir.</p>
            <p class="mt-2">Bu verilerden telefon numaranız, e-posta adresiniz ve yazışma adresiniz sistemin çalışması için zorunluluk arz etmemektedir, yalnızca ek gereklilikler dahilinde tarafımızla rızanız dahilinde paylaştığınız verilerdir.</p>

            <h2 class="text-xl font-semibold mt-6 mb-2">Kişisel Veri İşlemenin Yasal Dayanağı</h2>
            <ul class="list-disc list-inside space-y-1">
                <li>Yazışma adresiniz haricindeki iletişim bilgileriniz Kanunun 5. maddesi uyarınca açık rızanız üzerine,</li>
                <li>Kimlik bilgileriniz Dernekler Kanununun 11. maddesi ve Dernekler Yönetmeliği'nin altıncı bölümünde yer alan ilgili maddelere göre kanunen,</li>
                <li>Ödeme bilgileriniz, bağışınızın tarafımıza ulaştırılabilmesi amacıyla açık rızanız üzerine,</li>
                <li>IP adresi gibi sistemsel verileriniz, sistemin çalışabilmesi için gerektiğinden dolayı açık rızanız üzerine,</li>
            </ul>
            <p class="mb-3">işlenecektir.</p>

            <h2 class="text-xl font-semibold mt-6 mb-2">Kişisel Verilerin Aktarılabileceği Üçüncü Kişiler</h2>
            <p class="mb-3">Kişisel verileriniz üçüncü kişilerle aşağıdaki istisnalar haricinde kesinlikle paylaşılmayacaktır.</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Dernek defterlerine işlenecek kişisel verileriniz, olası bir denetim sırasında ilgili makamlarla, gerekli olduğu ölçüde paylaşılabilecektir.</li>
                <li>Bağış sisteminin işleyebilmesi için, ödeme bilgileriniz iyzi Ödeme ve Elektronik Para Hizmetleri A.Ş. ile ilgili gizlilik sözleşmesi dahilinde yalnızca ödeme alabilmek maksadıyla paylaşılabilecektir.</li>
                <li>Bağış miktarınıza binaen, açık rızanız üzerine isim ve soyisminiz Özgür Yazılım Derneği tarafından tarafınıza teşekkür etmek amacıyla paylaşılabilecektir.</li>
            </ul>

            <h2 class="text-xl font-semibold mt-6 mb-2">Veri İşlemenin Süresi ve Saklama</h2>
            <p class="mb-3">Ödeme süreçleri içerisinde kişisel verileriniz işlenecektir. Dernekler Yönetmeliği'nin 39. maddesi uyarınca dernek defterlerine işlenen kişisel verileriniz en az 5 yıl süre ile saklanacaktır. Dernek defterlerinin sayfaları tükendiği andan itibaren 5 yıl sonra defterlerle birlikte tüm bilgileriniz yok edilebilecektir.</p>

            <h2 class="text-xl font-semibold mt-6 mb-2">Kişisel verinizle ilgili haklarınız</h2>
            <ul class="list-disc list-inside space-y-1">
            <li>Derneğimize başvurarak size ait kişisel veri işlenip işlenmediğini ve kişisel veri işleniyor ise bu bilgiler ile ilgili bilgi talep edebilirsiniz.</li>

            <li>Belirli veya genel olarak işlenen kişisel verinizin işlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenebilirsiniz.</li>

            <li>Kişisel verilerinizin aktarıldığı üçüncü kişiler hakkında bilgi talep edebilirsiniz.</li>

        <li>Derneğimizde işlenen kişisel verilerinizin eksikliklerinin tamamlanmasını veya yanlışların düzeltilmesini talep edebilirsiniz.</li>

        <li>Derneğimizde bulunan, rızanıza bağlı işlenen kişisel verilerinizin her zaman, kanuni sebeplerle işlediğimiz verilerinizi ise kanunini gerekliliklerin yerine getirilmesi üzerine yok edilmesini talep edebilirsiniz.</li>
        </ul>

            <h2 class="text-xl font-semibold mt-6 mb-2">Başvuru ve Yöntemi</h2>
            <p>Derneğimize kişisel verileriniz ile ilgili yönelteceğiniz her talep için sunulan tüm iletişim yollarından ulaşabilirsiniz. Kişisel verilerinizin hukuka aykırı üçüncü kişilere ifşasının engellenmesi için Derneğimiz taleplerinizi açıkça bize bildirdiğiniz elektronik iletişim yollarından yazılı olarak veya adresimize noter aracılığı ile yapılacak bildirim üzerine cevap verilecektir. </p>

            <p class="mt-6"><a href="{{ route('home') }}" class="underline text-[#4c2447]">Geri dön</a></p>
        </div>
        @include('components.top-banner')
    </body>
    </html>


