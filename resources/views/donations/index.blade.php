<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Bağış Geçmişi</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
    @include('components.site-header')

    <div class="container mx-auto max-w-7xl p-4 pb-24">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-[#4c2447]">📊 Bağış Geçmişiniz</h1>
                    <p class="text-gray-600 mt-2">{{ $historyEmail }} e-posta adresi ile yapılan bağışlar</p>
                </div>
                <div class="text-right">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 bg-[#4c2447] text-white rounded-lg hover:bg-[#3a1c35] transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Yeni Bağış Yap
                    </a>
                </div>
            </div>

            <!-- Flash messages -->
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-700 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Toplam Bağış</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $donations->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Başarılı</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $successfulCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Başarısız</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $failedCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Toplam Tutar</p>
                            <p class="text-2xl font-bold text-gray-900">₺{{ number_format($totalAmount / 100, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- Subscriptions Section                                               -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        @if($activeSubscriptions->count() > 0)
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">🔄 Aylık Bağış Abonelikleriniz</h2>
                        <p class="text-sm text-gray-600 mt-1">Düzenli bağış aboneliklerinizi buradan yönetebilirsiniz.</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ $activeSubscriptions->where('status', 'active')->count() }} aktif
                    </span>
                </div>

                <div class="divide-y divide-gray-100">
                    @foreach($activeSubscriptions as $subscription)
                    <div class="px-6 py-5" x-data="{ confirmCancel: false, showCardUpdate: false }">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <!-- Subscription info -->
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center
                                    {{ $subscription->isActive() ? 'bg-green-100' : ($subscription->isCancelled() ? 'bg-gray-100' : 'bg-red-100') }}">
                                    @if($subscription->isActive())
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    @elseif($subscription->isCancelled())
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-lg font-bold text-gray-900">
                                            ₺{{ number_format($subscription->amount_minor / 100, 2, ',', '.') }}/ay
                                        </span>
                                        @if($subscription->isActive())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ✅ Aktif
                                            </span>
                                        @elseif($subscription->isCancelled())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                🚫 İptal edildi
                                            </span>
                                        @elseif($subscription->isPaymentFailed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                ⚠️ Ödeme başarısız
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                ⏳ Beklemede
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 mt-1 space-x-3">
                                        @if($subscription->started_at)
                                            <span>Başladı: {{ $subscription->started_at->timezone('Europe/Istanbul')->format('d.m.Y') }}</span>
                                        @endif
                                        @if($subscription->canceled_at)
                                            <span>· İptal: {{ $subscription->canceled_at->timezone('Europe/Istanbul')->format('d.m.Y') }}</span>
                                        @endif
                                        @if($subscription->next_billing_at && $subscription->isActive())
                                            <span>· Sonraki: {{ $subscription->next_billing_at->timezone('Europe/Istanbul')->format('d.m.Y') }}</span>
                                        @endif
                                    </div>
                                    @if($subscription->isPaymentFailed())
                                        <p class="text-xs text-red-600 mt-1">
                                            Son ödeme tahsil edilemedi. Aboneliğinizin devam etmesi için kart bilgilerinizi güncelleyin.
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Action buttons (only for active / payment_failed) -->
                            @if($subscription->isActive() || $subscription->isPaymentFailed())
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <!-- Card Update button -->
                                <form method="POST"
                                      action="{{ route('donations.subscription.card-update', $subscription) }}">
                                    @csrf
                                    <input type="hidden" name="magic_token" value="{{ $magicLinkToken }}">
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:border-[#4c2447] hover:text-[#4c2447] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        Kartı Güncelle
                                    </button>
                                </form>

                                <!-- Cancel button (with confirmation) -->
                                @if($subscription->isActive())
                                <div>
                                    <button type="button"
                                            @click="confirmCancel = true"
                                            x-show="!confirmCancel"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-red-200 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        İptal Et
                                    </button>

                                    <!-- Confirmation state -->
                                    <div x-show="confirmCancel" x-cloak
                                         class="flex items-center gap-2 p-2 bg-red-50 border border-red-200 rounded-lg">
                                        <span class="text-xs text-red-700 font-medium">Aboneliği iptal etmek istediğinizden emin misiniz?</span>
                                        <form method="POST"
                                              action="{{ route('donations.subscription.cancel', $subscription) }}"
                                              class="inline">
                                            @csrf
                                            <input type="hidden" name="magic_token" value="{{ $magicLinkToken }}">
                                            <button type="submit"
                                                    class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                                                Evet, İptal Et
                                            </button>
                                        </form>
                                        <button type="button"
                                                @click="confirmCancel = false"
                                                class="px-2 py-1 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-50 transition-colors">
                                            Vazgeç
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- Donations List                                                       -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Bağış Detayları</h2>
                <p class="text-sm text-gray-600 mt-1">Son 24 ay içindeki tüm bağış işlemleriniz</p>
            </div>

            @if($donations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih & Saat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tutar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ödeme Bilgileri</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Not</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referans</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($donations as $donation)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $donation->created_at->timezone('Europe/Istanbul')->format('d.m.Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $donation->created_at->timezone('Europe/Istanbul')->format('H:i') }}
                                        </div>
                                        @if($donation->completed_at)
                                            <div class="text-xs text-green-600">
                                                ✅ {{ $donation->completed_at->timezone('Europe/Istanbul')->format('H:i') }} tamamlandı
                                            </div>
                                        @endif
                                        @if($donation->notes === 'Aylık otomatik bağış')
                                            <div class="text-xs text-purple-600 mt-0.5">🔄 Aylık</div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-bold text-gray-900">
                                            ₺{{ number_format($donation->amount_minor / 100, 2, ',', '.') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $donation->currency ?? 'TRY' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($donation->status === 'success')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                Başarılı
                                            </span>
                                        @elseif($donation->status === 'failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                                Başarısız
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                </svg>
                                                Beklemede
                                            </span>
                                        @endif

                                        @if($donation->status === 'failed' && $donation->failed_reason)
                                            <div class="text-xs text-red-600 mt-1">
                                                {{ $donation->failed_reason }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($donation->payment_id)
                                            <div class="text-sm font-medium text-gray-900">
                                                ID: {{ $donation->payment_id }}
                                            </div>
                                        @endif

                                        @if($donation->card_last4)
                                            <div class="text-sm text-gray-600">
                                                💳 **** **** **** {{ $donation->card_last4 }}
                                            </div>
                                            @if($donation->card_brand)
                                                <div class="text-xs text-gray-500">
                                                    {{ $donation->card_brand }}
                                                </div>
                                            @endif
                                        @endif

                                        @if(!$donation->payment_id && !$donation->card_last4)
                                            <div class="text-sm text-gray-500">
                                                -
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        @if($donation->notes && $donation->notes !== 'Aylık otomatik bağış')
                                            <div class="text-sm text-gray-900 max-w-xs">
                                                {{ $donation->notes }}
                                            </div>
                                        @elseif($donation->notes === 'Aylık otomatik bağış')
                                            <div class="text-sm text-purple-600 italic">
                                                🔄 Aylık otomatik
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-500 italic">
                                                Not yok
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                            {{ $donation->conversation_id }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            #{{ $donation->id }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz bağış yapılmamış</h3>
                    <p class="mt-1 text-sm text-gray-500">Bu e-posta adresi ile henüz bağış işlemi gerçekleştirilmemiş.</p>
                    <div class="mt-6">
                        <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#4c2447] hover:bg-[#3a1c35] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#4c2447]">
                            İlk Bağışınızı Yapın
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Additional Information -->
        @if($donations->count() > 0)
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 Son Aktiviteler</h3>
                    <div class="space-y-3">
                        @foreach($donations->take(5) as $donation)
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @if($donation->status === 'success')
                                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                    @elseif($donation->status === 'failed')
                                        <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                                    @else
                                        <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $donation->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }} - ₺{{ number_format($donation->amount_minor / 100, 2, ',', '.') }}
                                        @if($donation->notes === 'Aylık otomatik bağış')
                                            <span class="text-purple-600 text-xs">🔄</span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $donation->status === 'success' ? 'Başarılı' : ($donation->status === 'failed' ? 'Başarısız' : 'Beklemede') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 İstatistikler</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Ortalama Bağış</span>
                                <span class="font-medium text-gray-900">
                                    ₺{{ number_format($averageAmount / 100, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">En Yüksek Bağış</span>
                                <span class="font-medium text-gray-900">
                                    ₺{{ number_format($maxAmount / 100, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">En Düşük Bağış</span>
                                <span class="font-medium text-gray-900">
                                    ₺{{ number_format($minAmount / 100, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Başarı Oranı</span>
                                <span class="font-medium text-gray-900">
                                    {{ $donations->count() > 0 ? round(($successfulCount / $donations->count()) * 100, 1) : 0 }}%
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Son Bağış</span>
                                <span class="font-medium text-gray-900">
                                    {{ $donations->where('status', 'success')->first() ? $donations->where('status', 'success')->first()->created_at->timezone('Europe/Istanbul')->diffForHumans() : 'Yok' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Bekleyen İşlemler</span>
                                <span class="font-medium text-gray-900">
                                    {{ $pendingCount }}
                                </span>
                            </div>
                        </div>
                        @if($activeSubscriptions->where('status','active')->count() > 0)
                        <div class="pt-2 border-t border-gray-100">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Aylık Abonelik</span>
                                <span class="font-medium text-purple-700">
                                    ₺{{ number_format($activeSubscriptions->where('status','active')->sum('amount_minor') / 100, 2, ',', '.') }}/ay
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="mt-8 mb-8 text-center text-sm text-gray-500">
            <p>Bu sayfa sadece size özel olarak oluşturulmuştur. Güvenlik için oturum süreniz dolduğunda tekrar giriş yapmanız gerekecektir.</p>
        </div>
    </div>

    @include('components.top-banner')
</body>
</html>
