<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Firmware;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;

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
        session()->flash('message', 'Download started, please wait...');
        Log::info("Firmware update started");
        $path = Firmware::basedir();
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
            Log::info("Created directory: " . $path);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $tmpfile = tempnam(sys_get_temp_dir(), "firmware-download");
            Log::info("Temporary file created: " . $tmpfile);

            $response = $client->get('https://github.com/raspberrypi/rpi-eeprom/archive/refs/heads/master.zip', [
                'sink' => $tmpfile,
                'allow_redirects' => true
            ]);
            Log::info("HTTP request sent, response status: " . $response->getStatusCode());

            if ($response->getStatusCode() != 200) {
                throw new \Exception("Expected HTTP response code 200, but received " . $response->getStatusCode());
            }

            clearstatcache(true, $tmpfile);
            $downloadedFileSize = filesize($tmpfile);
            Log::info("Downloaded file size: " . $downloadedFileSize);
            if ($downloadedFileSize == 0) {
                throw new \Exception("Downloaded file is empty");
            }

            $zip = new \ZipArchive;
            $openResult = $zip->open($tmpfile);
            if ($openResult !== true) {
                throw new \Exception("Error opening .zip file, ZipArchive open() returned: " . $openResult);
            }

            if ($zip->numFiles == 0) {
                throw new \Exception("Zip file contains no files");
            }
            Log::info("file in .zip file " . $zip->numFiles);

            $allowedSubfolders = ['latest', 'feature-specific'];
            $found = false; // Track if any matching file is found

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#rpi-eeprom-.+?/firmware-2711/(' . implode('|', $allowedSubfolders) . ')/(.*)#', $name, $matches)) {
                    $found = true; // Mark as found when a match is detected
                    $subfolder = $matches[1];
                    $subfolderPath = $matches[2];

                    $destPath = $path . "/" . $subfolder . "/" . $subfolderPath;

                    if (substr($name, -1) === "/") {
                        if (!is_dir($destPath)) {
                            mkdir($destPath, 0755, true);
                            Log::info("Created directory: " . $destPath);
                        }
                    } else {
                        $directoryPath = dirname($destPath);
                        if (!is_dir($directoryPath)) {
                            mkdir($directoryPath, 0755, true);
                        }
                        if (!is_dir($destPath)) {
                            file_put_contents($destPath, $zip->getFromIndex($i));
                            Log::info("File extracted: " . $destPath);
                        }
                    }
                }
            }

            if (!$found) {
                throw new \Exception("Downloaded file has not the expected content.");
            }

            $zip->close();
            session()->flash('message', 'Firmware updated');
        } catch (\Exception | GuzzleException $e) {
            Log::error("Error: " . $e->getMessage());
            session()->flash('message', 'Error: ' . $e->getMessage());
        } finally {
            @unlink($tmpfile);
        }
    }
}
