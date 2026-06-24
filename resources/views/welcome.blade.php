<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="payments-flow" content="{{ config('payments.flow', 'checkout') }}">
        <title>{{ config('app.name') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
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

                    <form method="POST" action="{{ route('donate.start') }}" class="space-y-4"
                          x-data="{ donationType: '{{ old('donation_type', 'once') }}' }">
                        @csrf

                        {{-- ── Donation Type Toggle ─────────────────────────────────── --}}
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-700">Bağış türü</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" id="btn-once"
                                    @click="donationType = 'once'"
                                    :class="donationType === 'once'
                                        ? 'bg-[#4c2447] text-white border-[#4c2447] shadow-sm'
                                        : 'bg-white text-gray-700 border-gray-300 hover:border-[#4c2447] hover:text-[#4c2447]'"
                                    class="flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg text-sm font-medium transition-all duration-150">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                    Tek seferlik
                                </button>
                                <button type="button" id="btn-monthly"
                                    @click="donationType = 'monthly'"
                                    :class="donationType === 'monthly'
                                        ? 'bg-[#4c2447] text-white border-[#4c2447] shadow-sm'
                                        : 'bg-white text-gray-700 border-gray-300 hover:border-[#4c2447] hover:text-[#4c2447]'"
                                    class="flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg text-sm font-medium transition-all duration-150">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Aylık düzenli
                                </button>
                            </div>
                            <input type="hidden" name="donation_type" :value="donationType">

                            {{-- Monthly info box --}}
                            <div x-show="donationType === 'monthly'"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg text-sm text-purple-800">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <strong class="block mb-1">Aylık düzenli bağış hakkında:</strong>
                                        <ul class="space-y-1 text-xs list-none">
                                            <li>🔄 Her ay, girdiğiniz tutar kartınızdan otomatik tahsil edilir.</li>
                                            <li>✅ İlk tahsilat hemen başlar; devamı her ay aynı günde tekrarlanır.</li>
                                            <li>🚫 İstediğiniz zaman bağış geçmişinizden aboneliğinizi iptal edebilirsiniz.</li>
                                            <li>💳 Yalnızca kredi kartları ile kullanılabilir.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                        <div x-show="donationType === 'once'">
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
                            <button type="submit" id="submit-donation"
                                    class="bg-[#4c2447] text-white px-5 py-2 rounded font-medium hover:bg-[#3a1c35] transition-colors"
                                    x-text="donationType === 'monthly' ? '🔄 Aylık Bağışı Başlat' : '💙 Bağış Yap'">
                                Bağış Yap
                            </button>
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
