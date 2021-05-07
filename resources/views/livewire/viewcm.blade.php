<div class="fixed z-10 inset-0 overflow-y-auto ease-out duration-400">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      
    <div class="fixed inset-0 transition-opacity">
      <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>
  
    <!-- This element is to trick the browser into centering the modal contents. -->
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>​
  
    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
      <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div>
          <table class="table-auto w-full">
          <tbody>
            <tr><td class="border px-4 py-2">Serial:</td><td class="border px-4 py-2">{{ $cm->serial }}</td></tr>
            <tr><td class="border px-4 py-2">MAC-address:</td><td class="border px-4 py-2">{{ $cm->mac }}</td></tr>
            <tr><td class="border px-4 py-2">Model:</td><td class="border px-4 py-2">{{ $cm->model }}</td></tr>
            <tr><td class="border px-4 py-2">Memory:</td><td class="border px-4 py-2">{{ $cm->memory_in_gb }} GiB</td></tr>
            <tr><td class="border px-4 py-2">Storage:</td><td class="border px-4 py-2">{{ round($cm->storage/1000/1000/1000) }} GB</td></tr>
            <tr><td class="border px-4 py-2">eMMC CSD:</td><td class="border px-4 py-2">{{ $cm->csd }}</td></tr>
            <tr><td class="border px-4 py-2">eMMC CID:</td><td class="border px-4 py-2">{{ $cm->cid }}</td></tr>
            <tr><td class="border px-4 py-2">Firmware version:</td><td class="border px-4 py-2">{{ $cm->firmware }}</td></tr>
            <tr><td class="border px-4 py-2">Installed image filename:</td><td class="border px-4 py-2">{{ $cm->image_filename }}</td></tr>
            <tr><td class="border px-4 py-2">Installed image sha256:</td><td class="border px-4 py-2">{{ $cm->image_sha256 }}</td></tr>
            <tr><td class="border px-4 py-2">Pre-installation script output:</td><td class="border px-4 py-2"><textarea class="w-full">{{ $cm->pre_script_output }}</textarea></td></tr>
            <tr><td class="border px-4 py-2">Post-installation script output:</td><td class="border px-4 py-2"><textarea class="w-full">{{ $cm->post_script_output }}</textarea></td></tr>
            <tr><td class="border px-4 py-2">Script return code:</td><td class="border px-4 py-2">{{ $cm->script_return_code }}</td></tr>
            <tr><td class="border px-4 py-2">Temp at start of provisioning:</td><td class="border px-4 py-2">{{ $cm->temp1 }}</td></tr>
            <tr><td class="border px-4 py-2">Temp at end of provisioning:</td><td class="border px-4 py-2">{{ $cm->temp2 }}</td></tr>
            <tr><td class="border px-4 py-2">First seen at:</td><td class="border px-4 py-2">{{ $cm->created_at }}</td></tr>
            <tr><td class="border px-4 py-2">Provisioning started at:</td><td class="border px-4 py-2">{{ $cm->provisioning_started_at }}</td></tr>
            <tr><td class="border px-4 py-2">Provisioning complete at:</td><td class="border px-4 py-2">@if ($cm->provisioning_complete_at) {{ $cm->provisioning_complete_at }} @else Not completed yet @endif</td></tr>
            <tr><td class="border px-4 py-2">Provisioning duration:</td><td class="border px-4 py-2">@if ($cm->provisioning_complete_at) {{ $cm->provisioning_complete_at->diffAsCarbonInterval($cm->provisioning_started_at) }} @else Not completed yet @endif</td></tr>
          </tbody>
          </table>
        </div>
      </div>

      <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <span class="mt-3 flex w-full rounded-md shadow-sm sm:mt-0 sm:w-auto">
          <button wire:click="cancel()" type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 px-4 py-2 bg-white text-base leading-6 font-medium text-gray-700 shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue transition ease-in-out duration-150 sm:text-sm sm:leading-5">
            Close
          </button>
        </span>
      </div>
        
    </div>
  </div>
</div>