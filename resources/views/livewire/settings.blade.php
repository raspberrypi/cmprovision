<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Other settings
    </h2>
</x-slot>


<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="md:grid md:grid-cols-3 md:gap-6">

        @if($logModalOpen)
            @include('livewire.viewlog')
        @elseif($staticModalOpen)
            @include('livewire.addstaticip')
        @endif

        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-gray-900">DHCP server settings</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Change the DHCP server configuration
                </p>
            </div>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-tl-md sm:rounded-tr-md">
                @if (session()->has('message'))
                <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md my-3" role="alert">
                    <div class="flex">
                        <div>
                            <p class="text-sm">{{ session('message') }}</p>
                        </div>
                    </div>
                </div>
                @endif
                <div class="mb-4">
                    <div class="block text-gray-700 text-sm font-bold mb-2">Static IP assignments:</div>
                </div>

                <table class="table-fixed min-w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2">IP</th>
                            <th class="px-4 py-2">MAC</th>
                            <th class="px-4 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hosts as $h)
                        <tr>
                            <td class="border px-4 py-2">{{ $h->ip }}</td>
                            <td class="border px-4 py-2">{{ $h->mac }}</td>
                            <td class="border px-4 py-2"><button wire:click="deleteStaticIP({{ $h->id }})" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button></td>
                        </tr>
                        @empty
                        <tr><td class="border px-4 py-2" colspan="3">No entries</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <button wire:click="addStaticIP()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded my-3">Add new assignment</button>
            </div>
        </div>

        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-gray-900">Service status</h3>
                <p class="mt-1 text-sm text-gray-600">
                    View the logs of server processes
                </p>
            </div>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-md">
                <table class="table-flex">
                <tr>
                    <td class="px-4 py-2">
                        <ul class="list-outside list-disc ml-6">
                            <li @if ($dnsmasqRunning) class="text-green-500" @else class="text-red-500" @endif>
                                <span class="text-black"> dnsmasq (DHCP server)</span>
                            </li>
                        </ul>
                    </td>
                    <td class="px-4 py-2">
                        <button wire:click="viewLogDnsmasq()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">View log</button>
                    </td>
                </tr>
                <tr>
                    <td class="px-4 py-2">
                        <ul class="list-outside list-disc ml-6">
                        <li @if ($rpibootRunning) class="text-green-500" @else class="text-red-500" @endif>
                                <span class="text-black"> rpiboot (USB file server)</span>
                            </li>
                        </ul>
                    </td>
                    <td class="px-4 py-2">
                        <button wire:click="viewLogRpiboot()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">View log</button>                                
                    </td>
                </tr>
                <tr>
                    <td class="px-4 py-2">
                        <ul class="list-outside list-disc ml-6">
                            <li class="text-green-500">
                                <span class="text-black"> web interface</span>
                            </li>
                        </ul>
                    </td>
                    <td class="px-4 py-2">
                        <button wire:click="viewLogLaravel()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">View log</button>                                
                    </td>
                </tr>
                </table>
            </div>

            @if (isset($actions))
                <div class="flex items-center justify-end px-4 py-3 bg-gray-50 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</div>
