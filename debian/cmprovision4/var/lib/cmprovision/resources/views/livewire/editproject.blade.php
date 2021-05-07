<div class="fixed z-10 inset-0 overflow-y-auto ease-out duration-400">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

    <div class="fixed inset-0 transition-opacity">
      <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>
  
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>​
  
    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
      <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="">
              <div class="mb-4">
                  <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Project name:</label>
                  <input type="text" id="name" name="name" wire:model.defer="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('name') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              <div class="mb-4">
                  <label for="image_id" class="block text-gray-700 text-sm font-bold mb-2">Image to write:</label>
                  <select id="image_id" name="image_id" wire:model.defer="image_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">-none-</option>
                    @foreach ($images as $image)
                    <option value="{{ $image->id }}">{{ $image->filename }} (added {{ date_format($image->created_at, "d-M-Y") }})</option>
                    @endforeach
                  </select>
                  @error('image_id') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              <div class="mb-4">
                  <label for="storage" class="block text-gray-700 text-sm font-bold mb-2">Destination storage device:</label>
                  <input type="text" id="storage" name="storage" wire:model.defer="storage" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                  @error('storage') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              <div class="mb-4">
                  <label for="firmware" class="block text-gray-700 text-sm font-bold mb-2">EEPROM firmware update to apply:</label>
                  <select id="firmware" name="firmware" wire:model="firmware" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">-none-</option>
                      @if (count($stable_firmware))
                      <optgroup label="stable">
                        @foreach ($stable_firmware as $fw)
                        <option value="{{ $fw->path }}">{{ $fw->name }}</option>
                        @endforeach
                      </optgroup>
                      @endif
                      @if (count($beta_firmware))
                      <optgroup label="beta">
                        @foreach ($beta_firmware as $fw)
                        <option value="{{ $fw->path }}">{{ $fw->name }}</option>
                        @endforeach
                      </optgroup>
                      @endif
                  </select>
                  @error('firmware') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              @if ($firmware != "")
              <div class="mb-4">
                  <label for="eeprom_settings" class="block text-gray-700 text-sm font-bold mb-2">EEPROM settings:</label>
                  <textarea type="text" id="eeprom_settings" name="eeprom_settings" wire:model.defer="eeprom_settings" class="h-28 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                  @error('eeprom_settings') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              @endif
              <div class="mb-4">
                  <label for="label_moment" class="block text-gray-700 text-sm font-bold mb-2">When to print label:</label>
                  <select id="label_moment" name="label_moment" wire:model="label_moment" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="never">Never</option>
                    <option value="preinstall">Before provisioning</option>
                    <option value="postinstall">After provisioning completed successfully</option>
                  </select>
                  @error('label_moment') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              @if ($label_moment != "never")
              <div class="mb-4">
                  <label for="label_id" class="block text-gray-700 text-sm font-bold mb-2">Label to print:</label>
                  <select id="label_id" name="label_id" wire:model.defer="label_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    @foreach ($labels as $label)
                    <option value="{{ $label->id }}">{{ $label->name }}</option>
                    @endforeach
                  </select>
                  @error('label_id') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>
              @endif

              @if (count($scripts))
              <div class="mb-4">
                  <div class="block text-gray-700 text-sm font-bold mb-2">Extra scripts to apply:</div>
                  @foreach ($scripts as $script)
                  <label class="inline-flex items-center">
                    <input type="checkbox" id="chkscript{{ $script->id }}" value="{{ $script->id }}" wire:model.defer="selectedScripts" class="form-checkbox h-6 w-6 text-gray-700">
                    <span class="ml-3 text-sm">{{ $script->name }}</span>
                  </label><br>
                  @endforeach
                  @error('selectedScripts') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>              
              @endif

              <div class="mb-4">
                  <div class="block text-gray-700 text-sm font-bold mb-2">Other options:</div>
                  <label class="inline-flex items-center">
                    <input type="checkbox" id="active" name="active" wire:model.defer="active" class="form-checkbox h-6 w-6 text-gray-700">
                    <span class="ml-3 text-sm">Set as active project</span>
                  </label>
                  @error('active') <span class="text-red-500">{{ $message }}</span>@enderror
              </div>     
        </div>
      </div>
  
      <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <span class="flex w-full rounded-md shadow-sm sm:ml-3 sm:w-auto">
          <button wire:click="store()" type="button" class="inline-flex justify-center w-full rounded-md border border-transparent px-4 py-2 bg-green-600 text-base leading-6 font-medium text-white shadow-sm hover:bg-green-500 focus:outline-none focus:border-green-700 focus:shadow-outline-green transition ease-in-out duration-150 sm:text-sm sm:leading-5">
            Save
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