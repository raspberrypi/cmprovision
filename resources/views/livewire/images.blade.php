<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Images
    </h2>
</x-slot>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-4 py-4">
            @if (session()->has('message'))
                <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md my-3" role="alert">
                  <div class="flex">
                    <div>
                      <p class="text-sm">{{ session('message') }}</p>
                    </div>
                  </div>
                </div>
            @endif
            @if ($errors->any())
            <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4" role="alert">
                <p>
                <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                </ul>
                </p>
            </div>
            @endif            
            <button wire:click="create()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded my-3">Add image</button>
            @if($isOpen)
                @include('livewire.addimage')
            @endif
            <table class="table-fixed min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="w-1/4 px-4 py-2">Filename</th>
                        <th class="w-1/8 px-4 py-2">Size</th>
                        <th class="w-1/4 px-4 py-2">SHA256</th>
                        <th class="w-1/8 px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($images as $i)
                    <tr>
                        <td class="border px-4 py-2">{{ $i->filename }} (added {{ date_format($i->created_at, "d-M-Y") }})</td>
                        <td class="border px-4 py-2">{{ number_format($i->filesize()/1000000000,1) }} GB</td>
                        <td class="border px-4 py-2">{{ $i->sha256 }}</td>
                        <td class="border px-4 py-2">
                            <button wire:click="delete({{ $i->id }})" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td class="border px-4 py-2" colspan="4">No entries</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>