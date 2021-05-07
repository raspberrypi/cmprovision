<div class="fixed z-10 inset-0 overflow-y-auto ease-out duration-400">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

    <div class="fixed inset-0 transition-opacity">
      <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>
  
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>​
  
    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
      <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">

        @if (session()->has('message'))
          <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md my-3" role="alert">
            <div class="flex">
              <div>
                <p class="text-sm">{{ session('message') }}</p>
              </div>
            </div>
          </div>
        @endif

        <div class="">
              <div class="mb-4">
                  <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Label name:</label>
                  <input type="text" id="name" name="name" wire:model.defer="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('name') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              <div class="mb-4">
                  <label for="printer_type" class="block text-gray-700 text-sm font-bold mb-2">Method to send print job to printer:</label>
                  <select id="printer_type" name="printer_type" wire:model="printer_type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="ftp">FTP</option>
                    <option value="command">Custom command</option>
                  </select>
                  @error('printer_type') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              @if ($printer_type == "command")
              <div class="mb-4">
                  <label for="print_command" class="block text-gray-700 text-sm font-bold mb-2">Command to queue print job:</label>
                  <input type="text" id="print_command" name="print_command" wire:model.defer="print_command" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('print_command') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              @elseif ($printer_type == "ftp")
              <div class="mb-4">
                  <label for="ftp_hostname" class="block text-gray-700 text-sm font-bold mb-2">FTP hostname:</label>
                  <input type="text" id="ftp_hostname" name="ftp_hostname" wire:model.defer="ftp_hostname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('ftp_hostname') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              <div class="mb-4">
                  <label for="ftp_username" class="block text-gray-700 text-sm font-bold mb-2">FTP user:</label>
                  <input type="text" id="ftp_username" name="ftp_username" wire:model.defer="ftp_username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('ftp_username') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              <div class="mb-4">
                  <label for="ftp_password" class="block text-gray-700 text-sm font-bold mb-2">FTP password:</label>
                  <input type="password" id="ftp_password" name="ftp_password" wire:model.defer="ftp_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('ftp_password') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              @endif
              <div class="mb-4">
                  <label for="file_extension" class="block text-gray-700 text-sm font-bold mb-2">File extension for print job:</label>
                  <input type="text" id="file_extension" name="file_extension" wire:model.defer="file_extension" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('file_extension') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              <div class="mb-4">
                  <label for="template" class="block text-gray-700 text-sm font-bold mb-2">Label template:</label>
                  <textarea wrap="off" type="text" id="template" name="template" wire:model.defer="template" class="h-48 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                  <small>Available variables:<br>$serial $mac $provisionboard</small><br>
                  @error('template') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
        </div>
      </div>
  
      <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <span class="flex w-full rounded-md shadow-sm sm:ml-3 sm:w-auto">
          <button wire:click="store()" type="button" class="inline-flex justify-center w-full rounded-md border border-transparent px-4 py-2 bg-green-600 text-base leading-6 font-medium text-white shadow-sm hover:bg-green-500 focus:outline-none focus:border-green-700 focus:shadow-outline-green transition ease-in-out duration-150 sm:text-sm sm:leading-5">
            Save
          </button>
        </span>
        <span class="flex w-full rounded-md shadow-sm sm:ml-3 sm:w-auto">
          <button wire:click="printTestLabel()" type="button" class="inline-flex justify-center w-full rounded-md border border-transparent px-4 py-2 bg-blue-600 text-base leading-6 font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150 sm:text-sm sm:leading-5">
            Print test label
          </button>
        </span>
        <span class="mt-3 flex w-full rounded-md shadow-sm sm:mt-0 sm:w-auto">
          <button wire:click="closeModal()" type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-base leading-6 font-medium text-gray-700 shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150 sm:text-sm sm:leading-5">
            Cancel
          </button>
        </span>
      </div>
        
    </div>
  </div>
</div>