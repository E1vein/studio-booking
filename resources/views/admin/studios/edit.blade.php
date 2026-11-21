<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Studio: {{ $studio->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.studios.update', $studio) }}" enctype="multipart/form-data"
                  class="bg-white rounded-lg shadow p-6 space-y-6">
                @csrf @method('PUT')
                @include('admin.studios._form')
            </form>

            {{-- Form hapus foto: di luar form utama (HTML tidak boleh form bersarang) --}}
            @foreach($studio->photos as $p)
                <form id="del-photo-{{ $p->id }}" method="POST"
                      action="{{ route('admin.studios.photos.destroy', [$studio, $p]) }}"
                      onsubmit="return confirm('Hapus foto ini?')">
                    @csrf @method('DELETE')
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>