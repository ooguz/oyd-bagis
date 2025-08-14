<div class="border rounded p-4 space-y-3">
  <div class="flex items-center gap-2">
    <img src="{{ asset('images/isbank.svg') }}" alt="İşbank Logo" class="h-6 w-auto">
    <h3 class="font-semibold text-[#4c2447]">Havale / EFT ile bağış</h3>
  </div>
  <div class="text-sm space-y-2">
    <div><span class="font-medium">Alıcı: Özgür Yazılım Derneği</div>
    <div class="flex items-center justify-between">
      <span class="font-medium">IBAN:</span>
      <button type="button" class="p-2 text-gray-600 hover:text-[#4c2447] hover:bg-gray-100 rounded" onclick="navigator.clipboard.writeText('{{ config('payments.bank_transfer.iban') }}')" title="IBAN'ı kopyala">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
      </button>
    </div>
    <div class="font-mono text-xs bg-gray-50 p-2 rounded border">{{ config('payments.bank_transfer.iban') }}</div>
    <div class="text-xs text-gray-500">Açıklama: "Bağış" yazabilir ya da bağışınızın nasıl kullanılmasını istediğinizi (ör. Konferans için bağış vb.) yazabilirsiniz.</div>
  </div>
</div>



