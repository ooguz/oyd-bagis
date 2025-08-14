<div class="border rounded p-4 space-y-2">
  <h3 class="font-semibold text-[#4c2447]">Havale / EFT</h3>
  <div class="text-sm">
    <div><span class="font-medium">Banka:</span> {{ config('payments.bank_transfer.bank_name') }}</div>
    <div class="flex items-center gap-2">
      <span class="font-medium">IBAN:</span>
      <input type="text" readonly value="{{ config('payments.bank_transfer.iban') }}" class="w-full bg-gray-50 border rounded px-2 py-1 text-xs">
      <button type="button" class="px-2 py-1 text-xs border rounded" onclick="navigator.clipboard.writeText('{{ config('payments.bank_transfer.iban') }}')">Kopyala</button>
    </div>
    <div class="text-xs text-gray-500">Açıklama: Ad Soyad + "Bağış"</div>
  </div>
</div>



