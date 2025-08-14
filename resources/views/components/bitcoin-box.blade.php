<div class="border rounded p-4 space-y-2">
  <h3 class="font-semibold text-[#4c2447]">Bitcoin ile Bağış</h3>
  <div class="text-sm flex items-center gap-2">
    <input type="text" readonly value="{{ config('payments.bitcoin.address') }}" class="w-full bg-gray-50 border rounded px-2 py-1 text-xs">
    <button type="button" class="px-2 py-1 text-xs border rounded" onclick="navigator.clipboard.writeText('{{ config('payments.bitcoin.address') }}')">Kopyala</button>
  </div>
  <div class="flex justify-center">
    <img alt="BTC QR" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(config('payments.bitcoin.address')) }}" />
  </div>
  <div class="text-xs text-gray-500">Açıklama: Donation {{ now()->format('Y-m-d') }}</div>
</div>



