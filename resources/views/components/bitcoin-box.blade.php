<div class="border rounded p-4 space-y-3">
  <h3 class="font-semibold text-[#4c2447]">Bitcoin ile Bağış</h3>
  <div class="text-sm space-y-2">
    <div class="flex justify-center">
      <img src="{{ asset('images/qr.png') }}" alt="Bitcoin QR Code" class="w-24 h-24">
    </div>
    <div class="flex items-center justify-between">
      <span class="font-medium">Adres:</span>
      <button type="button" class="p-2 text-gray-600 hover:text-[#4c2447] hover:bg-gray-100 rounded" onclick="navigator.clipboard.writeText('{{ config('payments.bitcoin.address') }}')" title="Bitcoin adresini kopyala">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
      </button>
    </div>
    <div class="font-mono text-xs bg-gray-50 p-2 rounded border break-all">{{ config('payments.bitcoin.address') }}</div>
    <div class="text-xs text-gray-500">Açıklama: Donation {{ now()->format('Y-m-d') }}</div>
  </div>
</div>



