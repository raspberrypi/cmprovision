<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
                    <div>
                        <x-jet-application-logo class="block h-12 w-auto" />
                    </div>

                    <div class="mt-8 text-2xl">
                        Welcome to the CM4 provisioning system!
                    </div>

                    <div class="mt-6 text-gray-500">
                        To get started add both an Image and an Project to the system.
                    </div>
                </div>
            </div>
            <br>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg sm:px-20">
                <div class="mt-8 text-2xl">
                        Last 100 provisioning log entries
                </div><br>

                <table class="table-fixed min-w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="w-1/6 px-4 py-2">Board</th>
                            <th class="w-1/6 px-4 py-2">CM serial</th>
                            <th class="px-4 py-2">Log message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($log as $l)
                        @if ($l->loglevel == 'error')<tr class="bg-red-100">@else <tr>@endif 
                            <td class="border px-4 py-2">{{ $l->board }}</td>
                            <td class="border px-4 py-2">{{ $l->cm }}</td>
                            <td class="border px-4 py-2">{!! nl2br(e($l->created_at->toTimeString().' '.$l->msg), false) !!}</td>
                        </tr>
                        @empty
                        <tr><td class="border px-4 py-2" colspan="3">No entries</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <br>
            </div>            
        </div>
    </div>
</x-app-layout>
