@php
    $studio   = $studio ?? null;
    $days     = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
    $existing = $studio ? $studio->schedules->keyBy('day_of_week') : collect();
    $hasOld   = old('schedules') !== null;
@endphp

@if($errors->any())
    <div class="p-3 rounded bg-red-100 text-red-700 text-sm">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

{{-- Info dasar --}}
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium mb-1">Nama Studio</label>
        <input type="text" name="name" value="{{ old('name', $studio?->name) }}"
               class="w-full border-gray-300 rounded" required>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Deskripsi</label>
        <textarea name="description" rows="3" class="w-full border-gray-300 rounded">{{ old('description', $studio?->description) }}</textarea>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Tarif per jam (Rp)</label>
            <input type="number" name="hourly_rate" min="0" value="{{ old('hourly_rate', $studio?->hourly_rate) }}"
                   class="w-full border-gray-300 rounded" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Kapasitas (orang)</label>
            <input type="number" name="capacity" min="1" value="{{ old('capacity', $studio?->capacity ?? 1) }}"
                   class="w-full border-gray-300 rounded" required>
        </div>
    </div>
</div>

{{-- Jam operasional --}}
<div>
    <h3 class="font-semibold mb-2">Jam Operasional</h3>
    <p class="text-xs text-gray-500 mb-3">Hari yang tidak dicentang dianggap tutup.</p>
    <div class="space-y-2">
        @foreach($days as $d => $label)
            @php
                $active = $hasOld
                    ? ! empty(old("schedules.$d.active"))
                    : ($studio ? $existing->has($d) : true);
                $open  = old("schedules.$d.open_time",  isset($existing[$d]) ? substr($existing[$d]->open_time, 0, 5)  : '09:00');
                $close = old("schedules.$d.close_time", isset($existing[$d]) ? substr($existing[$d]->close_time, 0, 5) : '21:00');
            @endphp
            <div class="flex items-center gap-3 text-sm">
                <label class="w-24 flex items-center gap-2">
                    <input type="checkbox" name="schedules[{{ $d }}][active]" value="1"
                           class="rounded border-gray-300" @checked($active)>
                    {{ $label }}
                </label>
                <input type="time" name="schedules[{{ $d }}][open_time]"  value="{{ $open }}"  class="border-gray-300 rounded">
                <span>-</span>
                <input type="time" name="schedules[{{ $d }}][close_time]" value="{{ $close }}" class="border-gray-300 rounded">
            </div>
        @endforeach
    </div>
</div>

{{-- Foto yang sudah ada --}}
@if($studio && $studio->photos->isNotEmpty())
    <div>
        <h3 class="font-semibold mb-2">Foto Saat Ini</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($studio->photos as $p)
                <div class="border rounded overflow-hidden">
                    <img src="{{ $p->url }}" class="w-full h-24 object-cover" alt="">
                    <div class="p-2 text-xs flex items-center justify-between gap-2">
                        <span class="truncate text-gray-600">{{ $p->caption ?: 'Tanpa caption' }}</span>
                        <button type="submit" form="del-photo-{{ $p->id }}" class="text-red-600 hover:underline">Hapus</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Upload foto baru --}}
<div x-data="{ rows: [0] }">
    <h3 class="font-semibold mb-2">Tambah Foto</h3>
    <p class="text-xs text-gray-500 mb-3">Maks 4 MB per foto. Caption dipakai untuk menjelaskan fasilitas di carousel.</p>
    <template x-for="r in rows" :key="r">
        <div class="flex flex-col sm:flex-row gap-2 mb-2">
            <input type="file" accept="image/*" :name="`photos[${r}]`" class="text-sm">
            <input type="text" :name="`captions[${r}]`" placeholder="Caption fasilitas (opsional)"
                   class="flex-1 border-gray-300 rounded text-sm">
        </div>
    </template>
    <button type="button" @click="rows.push(rows.length)" class="text-sm text-indigo-600 hover:underline">
        + Tambah baris foto
    </button>
</div>

<div class="flex gap-3 pt-2">
    <a href="{{ route('admin.studios.index') }}"
       class="border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50">Batal</a>
    <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Simpan</button>
</div>