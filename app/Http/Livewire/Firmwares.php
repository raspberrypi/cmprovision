<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Firmware;
use Illuminate\Support\Str;

class Firmwares extends Component
{
    public $firmware;

    public function render()
    {
        $this->firmware = Firmware::all();
        return view('livewire.firmware');
    }

    public function update()
    {
        $path = Firmware::basedir();
        if (!file_exists($path))
            mkdir($path);

        $tmpfile = tempnam(sys_get_temp_dir(), "firmware-download");
        try
        {
            $client = new \GuzzleHttp\Client();
            $r = $client->get('https://api.github.com/repos/raspberrypi/rpi-eeprom/zipball', ['sink' => $tmpfile]);
            if ($r->getStatusCode() != 200)
                throw new \Exception("Expected HTTP response code 200, but received ".$r->getStatusCode()." ".$r->getReasonPhrase() );

            $zip = new \ZipArchive;
            if (!$zip->open($tmpfile))
                throw new \Exception("Error opening .zip file");

            // We only want rpi-eeprom-something/firmware/* of the .zip
            $prefix = $zip->getNameIndex(0);
            if (!$prefix)
                throw new \Exception("Error listing .zip file");

            $prefix .= "firmware/";

            for ($i = 1; $i < $zip->numFiles; $i++)
            {
                $name = Str::of($zip->getNameIndex($i));
                if (!$name->startsWith($prefix) || $name->contains("../"))
                    continue;

                $nameWithoutPrefix = $name->substr(strlen($prefix));
                if (substr($nameWithoutPrefix, -1) == "/")
                {
                    @mkdir($path."/".$nameWithoutPrefix, 0755, true);
                }
                else
                {
                    @file_put_contents($path."/".$nameWithoutPrefix, $zip->getFromIndex($i));
                }
            }

            $zip->close();

            session()->flash('message', 'Firmware updated');
        }
        catch (\Exception $e)
        {
            session()->flash('message', 'Error: '.$e->getMessage() );
        }
        @unlink($tmpfile);
    }
}
