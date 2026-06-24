<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Kart Güncelleme</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
    @include('components.site-header')

    <div class="container mx-auto max-w-3xl p-4 pb-24">
        <div class="mb-6">
            <a href="{{ route('donations.index', ['token' => $magicLinkToken]) }}"
               class="inline-flex items-center text-sm text-[#4c2447] hover:underline">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Bağış Geçmişine Dön
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold text-[#4c2447] mb-2">💳 Kart Bilgilerini Güncelle</h1>
            <p class="text-gray-600 mb-6">
                Aylık bağış aboneliğiniz için kullanılan kartı güncelleyebilirsiniz.<br>
                <span class="text-sm text-amber-600">Not: Kart doğrulaması için ₺1 geçici tahsilat yapılır ve hemen iade edilir.</span>
            </p>

            <div class="mb-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Aylık tutar:</span>
                    <span class="font-bold text-[#4c2447]">₺{{ number_format($subscription->amount_minor / 100, 2, ',', '.') }}</span>
                </div>
            </div>

            @if($checkoutFormContent)
                <div id="iyzipay-checkout-form" class="popup">
                    {!! $checkoutFormContent !!}
                </div>
            @else
                <div class="p-4 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                    Kart güncelleme formu yüklenemedi. Lütfen daha sonra tekrar deneyin.
                </div>
            @endif
        </div>
    </div>

    @include('components.top-banner')
</body>
</html>
