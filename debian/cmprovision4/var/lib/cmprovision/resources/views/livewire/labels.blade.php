<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Labels
    </h2>
</x-slot>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-4 py-4">
            @if (session()->has('message') && !$isOpen)
                <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md my-3" role="alert">
                  <div class="flex">
                    <div>
                      <p class="text-sm">{{ session('message') }}</p>
                    </div>
                  </div>
                </div>
            @endif
            <button wire:click="create()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded my-3">Add label</button>
            @if($isOpen)
                @include('livewire.editlabel')
            @endif
            <table class="table-fixed min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="w-1/2 px-4 py-2">Name</th>
                        <th class="w-1/8 px-4 py-2">Type</th>
                        <th class="w-1/4 px-4 py-2">Printer FTP hostname</th>
                        <th class="w-1/4 px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($labels as $l)
                    <tr>
                        <td class="border px-4 py-2">{{ $l->name }}</td>
                        <td class="border px-4 py-2">{{ $l->printer_type }}</td>
                        <td class="border px-4 py-2">@if ($l->printer_type == "ftp"){{ $l->ftp_hostname }}@else N/A @endif</td>
                        <td class="border px-4 py-2">
                            <button wire:click="edit({{ $l->id }})" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
                            <button wire:click="delete({{ $l->id }})" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
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