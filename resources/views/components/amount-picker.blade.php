<div x-data="{ amount: '{{ old('amount') ?: '250' }}' }" class="space-y-4">
  <div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700">Bağış tutarı</label>
    <div class="grid grid-cols-2 gap-3">
      @php 
        $presets = [
          ['amount' => 2500, 'label' => '₺ 2500', 'desc' => 'ÖYD\'nin bir aylık kirası'],
          ['amount' => 1000, 'label' => '₺ 1000', 'desc' => 'Bir şehirlerarası otobüs bileti'],
          ['amount' => 500, 'label' => '₺ 500', 'desc' => 'Bir yıllık .com alan adı'],
          ['amount' => 250, 'label' => '₺ 250', 'desc' => 'Bir öğle yemeği'],
          ['amount' => 100, 'label' => '₺ 100', 'desc' => 'Bir kahve'],
          ['amount' => 50, 'label' => '₺ 50', 'desc' => 'Bir çay']
        ];
      @endphp
      @foreach($presets as $preset)
        <button type="button" @click="amount='{{ $preset['amount'] }}'" 
          class="p-3 text-left rounded border transition-colors"
          :class="amount=='{{ $preset['amount'] }}' ? 'bg-[#4c2447] text-white border-[#4c2447]' : 'border-gray-300 hover:border-gray-400'">
          <div class="font-medium">{{ $preset['label'] }}</div>
          <div class="text-xs opacity-75">{{ $preset['desc'] }}</div>
        </button>
      @endforeach
    </div>
  </div>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700">Kendim belirleyeceğim</label>
    <div class="relative">
      <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₺</span>
      <input name="amount" x-model="amount" inputmode="decimal" autocomplete="off" 
        placeholder="25" 
        class="w-full border rounded px-8 py-3 focus:ring-2 focus:ring-[#4c2447] focus:border-transparent" />
    </div>
  </div>
</div>



