<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Image;
use App\Models\Label;
use App\Models\Project;
use App\Models\Script;
use App\Models\Setting;
use App\Models\Firmware;
use Illuminate\Support\Str;
use App\Jobs\ComputeSHA256;

class Projects extends Component
{
    public $isOpen = false;
    public $active = true;
    public $projects, $activeProject;
    public $projectid, $name, $device, $storage, $image_id, $label_id, $label_moment, $selectedScripts, $offerSettingsReset;
    public $firmware, $eeprom_settings, $verify;
    public $images, $labels, $scripts, $beta_firmware, $stable_firmware;

    protected $rules = [
        'name' => 'required|max:255',
        'device' => 'required|in:cm4',
        'storage' => 'required|max:255',
        'image_id' => 'nullable|numeric',
        'label_id' => 'nullable|numeric',
        'firmware' => 'nullable|max:255',
        'eeprom_settings' => 'nullable|max:2024',
        'label_moment' => 'required|in:never,preinstall,postinstall',
        'selectedScripts' => 'array',
        'verify' => 'required|boolean'
    ];

    public function render()
    {
        $this->projects = Project::orderBy('name')->get();
        $this->activeProject = Project::getActiveId();
        $this->images = Image::orderBy('filename')->orderBy('id')->get();
        $this->labels = Label::orderBy('name')->get();
        $this->scripts = Script::orderBy('name')->get();
        $this->beta_firmware = Firmware::allOfChannel('beta');
        $this->stable_firmware = Firmware::allOfChannel('stable');        

        if ($this->isOpen && $this->label_moment != 'never' && !$this->label_id && count($this->labels))
            $this->label_id = $this->labels[0]->id;

        return view('livewire.projects');
    }

    /* Called when EEPROM firmware selection changes */
    public function updatingFirmware($newvalue)
    {
        if (!$newvalue)
            return;

        $oldDefaultSettings = $this->firmware ? $this->getEepromSettingsFromFirmwareFile($this->firmware) : '';
        $newDefaultSettings = $this->getEepromSettingsFromFirmwareFile($newvalue);
        $currentSettings = str_replace("\r", "", $this->eeprom_settings);

        if ($currentSettings
            && $currentSettings != $oldDefaultSettings
            && $currentSettings != $newDefaultSettings)
        {
            /* Do not overwrite user's custom settings by default
               do offer user option to 'reset settings' */
            $this->offerSettingsReset = true;
        }
        else
        {
            $this->eeprom_settings = $newDefaultSettings;
            $this->offerSettingsReset = false;
        }
    }

    public function resetEEPROMsettings()
    {
        $this->eeprom_settings = $this->getEepromSettingsFromFirmwareFile($this->firmware);
        $this->offerSettingsReset = false;
    }

    public function openModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();        
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }
    
    public function delete($id)
    {
        Project::destroy($id);
        session()->flash('message', 'Project deleted.');
    }

    public function create()
    {
        $this->name = $this->projectid = $this->label_id = '';
        $this->device = 'cm4';
        $this->storage = "/dev/mmcblk0";
        $this->selectedScripts = [];
        $this->label_moment = 'never';
        $this->image_id = Image::max('id');
        $this->active = true;
        $this->verify = false;
        $this->eeprom_settings = '';
        $this->firmware = '';
        $this->offerSettingsReset = false;

        $this->openModal();
    }

    public function edit($id)
    {
        $p = Project::findOrFail($id);
        $this->projectid = $id;
        $this->name = $p->name;
        $this->device = $p->device;
        $this->storage = $p->storage;
        $this->label_moment = $p->label_moment;
        $this->image_id = $p->image_id;
        $this->label_id = $p->label_id;
        $this->verify = $p->verify;
        $this->active = $p->isActive();
        $this->selectedScripts = [];
        foreach ($p->scripts as $script)
        {
            $this->selectedScripts[] = strval($script->id);
        }
        $this->firmware = $p->eeprom_firmware;
        $this->eeprom_settings = $p->eeprom_settings;
        $this->offerSettingsReset = false;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        $project = Project::updateOrCreate(['id' => $this->projectid], [
            'name' => $this->name,
            'device' => $this->device,
            'storage' => $this->storage,
            'label_moment' => $this->label_moment,
            'image_id' => $this->image_id ? $this->image_id : null,
            'label_id' => $this->label_id ? $this->label_id : null,
            'eeprom_firmware' => $this->firmware ? $this->firmware : null,
            'eeprom_settings' => $this->eeprom_settings ? str_replace("\r", "", $this->eeprom_settings) : null,
            'verify' => $this->verify
        ]);
        $project->scripts()->sync($this->selectedScripts);

        if ($this->active)
        {
            $this->setActive($project->id);
        }

        if ($this->verify && $this->image_id && !$project->image->uncompressed_sha256)
        {
            /* Queue SHA256 calculation job */
            ComputeSHA256::dispatch($project->image);
        }

        $this->closeModal();
    }

    public function cancel()
    {
        $this->closeModal();
    }

    public function setActive($id)
    {
        $p = Project::findOrFail($id);

        Setting::updateOrCreate(['key' => 'active_project'], ['value' => $id]);
        $this->activeProject = $id;

        /* Set active firmware update */
        $firmware = $p->eeprom_firmware;
        if ($firmware)
        {
            $allFirmware = Firmware::all();
            $found = false;

            foreach ($allFirmware as $f)
            {
                if ($f->path == $firmware)
                {
                    $found = true;
                    break;
                }
            }

            if (!$found)
            {
                session()->flash('message', "EEPROM firmware '$firmware' no longer available");
                $firmware = '';
            }
        }

        
        //$updfile = base_path('scriptexecute/pieeprom.upd');
        /* Delete file created by previous CMprovision beta */
        $sigfile = base_path('scriptexecute/pieeprom.sig');
        if (file_exists($sigfile))
            unlink($sigfile);

        $updfile = public_path('uploads/pieeprom.bin');

        if ($firmware)
        {
            $data = @file_get_contents(Firmware::basedir().'/'.$firmware);
            if (!$data)
            {
                session()->flash('message', "Error opening EEPROM firmware file '".Firmware::basedir().'/'.$firmware."'");
                return;
            }

            if (!$this->setEepromSettings($data, $p->eeprom_settings))
            {
                session()->flash('message', "Error parsing EEPROM file for configuration");
                return;
            }

            if (!@file_put_contents($updfile, $data))
            {
                session()->flash('message', "Error writing to '$updfile'");
                return;
            }

            $sha256 = hash_file("sha256", $updfile);
            /*if (!@file_put_contents($sigfile, "$sha256\nts=".time()."\n"))
            {
                session()->flash('message', "Error writing to '$sigfile'");
                return;
            }*/
            Setting::updateOrCreate(['key' => 'active_eeprom_sha256'], ['value' => $sha256]);
        }
        else
        {
            /* EEPROM firmware update disabled */
            //if (file_exists($sigfile))
            //    unlink($sigfile);
            if (file_exists($updfile))
                unlink($updfile);
        }
    }

    function setEepromSettings(&$data, $settings)
    {
        $MAGIC = 0x55aaf00f;
        $MAGIC_MASK = 0xfffff00f;
        $FILE_MAGIC = 0x55aaf11f;
        $FILE_HDR_LEN = 20;
        $FILENAME_LEN = 12;
        $MAX_BOOTCONF_SIZE = 2024;

        $offset = $magic = $len = 0;
        $found = false;

        while ($offset+8 < strlen($data))
        {
            list($magic, $len) = array_values(unpack("Nmagic/Nlen", $data, $offset));
            if (($magic & $MAGIC_MASK) != $MAGIC)
            {
                // EEPROM corrupt
                return false;
            }

            if ($magic == $FILE_MAGIC)
            {
                if (Str::startsWith(substr($data, $offset+8, $FILE_HDR_LEN), "bootconf.txt\0"))
                {
                    $found = true;
                    break;
                }
            }

            $offset += 8 + $len;
            $offset = ($offset + 7) & ~7;
        }

        if (!$found)
            return false;

        $newlen = strlen($settings) + $FILENAME_LEN + 4;
        $binnewlen = pack("N", $newlen);
        $data = substr($data, 0, $offset+4).$binnewlen.substr($data, $offset+8);
        $settings = str_pad($settings, $MAX_BOOTCONF_SIZE, "\xff");
        $data = substr($data, 0, $offset+4+$FILE_HDR_LEN).$settings.substr($data, $offset+4+$FILE_HDR_LEN+$MAX_BOOTCONF_SIZE);

        return true;
    }

    function getEepromSettingsFromFirmwareFile($fn)
    {
        $data = @file_get_contents(Firmware::basedir().'/'.$fn);
        return $this->getEepromSettings($data);
    }

    function getEepromSettings(&$data)
    {
        $MAGIC = 0x55aaf00f;
        $MAGIC_MASK = 0xfffff00f;
        $FILE_MAGIC = 0x55aaf11f;
        $FILE_HDR_LEN = 20;
        $FILENAME_LEN = 12;
        $MAX_BOOTCONF_SIZE = 2024;

        $offset = $magic = $len = 0;
        $found = false;

        while ($offset+8 < strlen($data))
        {
            list($magic, $len) = array_values(unpack("Nmagic/Nlen", $data, $offset));
            if (($magic & $MAGIC_MASK) != $MAGIC)
            {
                // EEPROM corrupt
                return false;
            }

            if ($magic == $FILE_MAGIC)
            {
                if (Str::startsWith(substr($data, $offset+8, $FILE_HDR_LEN), "bootconf.txt\0"))
                {
                    $found = true;
                    break;
                }
            }

            $offset += 8 + $len;
            $offset = ($offset + 7) & ~7;
        }

        if (!$found)
            return false;

        $datalen = $len - $FILENAME_LEN - 4;

        if ($datalen < 0)
            return false;

        return substr($data, $offset+4+$FILE_HDR_LEN, $datalen);
    }
}
