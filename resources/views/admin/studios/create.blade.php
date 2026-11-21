<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Studio</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.studios.store') }}" enctype="multipart/form-data"
                  class="bg-white rounded-lg shadow p-6 space-y-6">
                @csrf
                @include('admin.studios._form')
            </form>
        </div>
    </div>
</x-app-layout>