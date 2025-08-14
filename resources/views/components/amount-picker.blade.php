<div x-data="{ amount: '{{ old('amount') }}' }" class="space-y-2">
  <div class="flex flex-wrap gap-2">
    @foreach($preset as $m)
      <button type="button" @click="amount='{{ $m }}'" class="px-3 py-2 rounded border text-sm"
        :class="amount=='{{ $m }}' ? 'bg-[#4c2447] text-white border-[#4c2447]' : 'border-gray-300'">
        ₺{{ number_format($m, 0, ',', '.') }}
      </button>
    @endforeach
  </div>
  <input name="amount" x-model="amount" inputmode="decimal" autocomplete="off" placeholder="₺ Tutar" class="w-full border rounded px-3 py-2" />
</div>



