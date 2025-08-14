<div x-data="{ show: true }" x-show="show" x-transition.opacity class="fixed bottom-0 left-0 right-0 bg-[#f4ecf7] border-t border-[#e2d6ea] text-[#4c2447] text-sm z-50">
    <div class="container mx-auto max-w-6xl px-4 py-2 flex items-start md:items-center justify-between gap-3">
        <div>
            Kredi kartıyla yapılan bağışlarınız <span class="font-medium">iyzico</span> güvencesi ile alınmaktadır. Kişisel verileriniz Kişisel Veri ve Gizlilik Politikamız kapsamında korunmaktadır.
            <a href="{{ route('policy') }}" class="underline font-medium" target="_blank" rel="noopener">Detaylar</a>
        </div>
        <button type="button" @click="show=false" class="shrink-0 text-xs underline">Kapat</button>
    </div>
</div>


