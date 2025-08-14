<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="payments-flow" content="{{ config('payments.flow', 'checkout') }}">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
        @include('components.site-header')
        <div class="container mx-auto max-w-6xl p-4 pb-24">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded shadow p-6">
                    <h1 class="text-2xl font-semibold mb-4 text-[#4c2447]">Kredi kartı ile bağış</h1>
                    @if ($errors->any())
                        <div class="p-3 mb-3 text-sm rounded bg-red-50 border border-red-200">
                            <ul class="list-disc list-inside text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="p-3 mb-3 text-sm rounded bg-green-50 border border-green-200 text-green-800">{{ session('success') }}</div>
                    @endif
                    @isset($errorMessage)
                        <div class="p-3 mb-3 text-sm rounded bg-red-50 border border-red-200 text-red-700">{{ $errorMessage }}</div>
                    @endisset
                    <form method="POST" action="{{ route('donate.start') }}" class="space-y-4">
                        @csrf
                        <x-amount-picker />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm mb-1">Ad Soyad</label>
                                <input name="full_name" value="{{ old('full_name') }}" class="w-full border rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm mb-1">E-posta</label>
                                <input name="email" type="email" value="{{ old('email') }}" class="w-full border rounded px-3 py-2" placeholder="ornek@example.com" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Not (opsiyonel)</label>
                            <textarea name="notes" class="w-full border rounded px-3 py-2" rows="2" placeholder="Özel taleplerinizi buraya yazabilirsiniz (ör. makbuz, alındı belgesi, bir kişi/kurum adına bağış vb.)">{{ old('notes') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Kart bilgileriniz</label>
                            <div data-checkout-container>
                                <x-one-line-card-input :checkout-form-content="$checkoutFormContent ?? null" />
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                Not: Kart bilgileriniz, <span class="font-medium">iyzico</span> tarafından açılacak pencerede alınacaktır. Bu alan sadece bilgilendirme amaçlıdır.
                            </p>
                            @if(!empty($paymentPageUrl))
                                <div class="mt-3 text-sm">
                                    <a class="text-[#4c2447] underline" href="{{ $paymentPageUrl }}" target="_blank">Ödeme sayfasına git</a>
                                    <span class="text-xs text-gray-500 block mt-1">Mobil cihazlarda otomatik olarak yönlendirileceksiniz.</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-[#4c2447] text-white px-4 py-2 rounded">Bağış Yap</button>
                           <a href="/kvkk-ve-gizlilik" class="text-xs text-gray-500">Kişisel Veri ve Gizlilik Politikamız</a>
                        </div>
                    </form>
                </div>
                <div class="space-y-4">
                    @include('components.bank-transfer-box')
                    @include('components.bitcoin-box')
                    @include('components.other-donation-methods')
                    <div class="border rounded p-4 space-y-2 bg-white">
                        <h3 class="font-semibold text-[#4c2447]">Önceki bağışlarınızı görüntüleyin</h3>
                        <form method="POST" action="{{ route('magic.request') }}" class="space-y-2">
                            @csrf
                            <input type="email" name="email" placeholder="E-posta" class="w-full border rounded px-3 py-2" required>
                            <button type="submit" class="px-3 py-2 border rounded">Bağlantı gönder</button>
                        </form>
                        @isset($historyEmail)
                            <div class="mt-3">
                                <h4 class="font-medium">{{ $historyEmail }} için son bağışlar</h4>
                                <ul class="text-sm mt-2 space-y-1">
                                    @forelse(($donations ?? []) as $d)
                                        <li class="flex justify-between"><span>{{ $d->created_at->format('d.m.Y H:i') }}</span><span>₺{{ number_format($d->amount_minor/100, 2, ',', '.') }}</span></li>
                                    @empty
                                        <li>Geçmiş kayıt yok.</li>
                                    @endforelse
                                </ul>
                            </div>
                        @endisset
                    </div>
                </div>
            </div>
        </div>
        @include('components.top-banner')
    </body>
</html>


