<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cm;
use App\Models\Cmlog;
use App\Models\Project;
use App\Models\Script;
use App\Models\Setting;

class ScriptExecuteController extends Controller
{
    public $serial, $cm;
    const MAX_LOG_SIZE = 1*1024*1024;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $req)
    {
        $this->serial = $req->query('serial');
        if (!$this->serial)
            abort(401);

        if ($req->query("alldone"))
        {
            return $this->provisioningComplete($req);
        }
        else if ($req->hasFile("log"))
        {
            return $this->registerLogFile($req);
        }
        /*else if ($req->hasFile("eeprom_version"))
        {
            return $this->registerFirmware($req);
        }*/
        else
        {
            return $this->startProvisoning($req);
        }
    }

    public function startProvisoning(Request $req)
    {
        $project = Project::getActive();
        $image   = $project ? $project->image : null;
        $jumper  = $req->query('inversejumper');
        if ($jumper)
        {
            // Inverse jumper bits
            for ($i=0; $i<strlen($jumper); $i++)
                $jumper[$i] = $jumper[$i] == "0" ? "1" : "0";

            $board = $jumper.' ('.bindec($jumper).')';
        }
        else
        {
            $board = null;
        }
        $memoryInGb = null;
        if ($req->query("memorysize"))
        {
            $memoryInGb = round( ($req->query("memorysize")+200000)/1024/1024 );
        }

        $this->cm = Cm::updateOrCreate(['serial' => $this->serial], [
            'serial' => $this->serial,
            'mac'    => $req->query('mac') ? $req->query('mac')
                        : "b8:27:eb:".substr($this->serial, -6, 2).":".substr($this->serial, -4, 2).":".substr($this->serial, -2, 2),
            'model'  => $req->query('model'),
            'memory_in_gb' => $memoryInGb,
            'storage' => $req->query('storagesize') ? $req->query('storagesize')*512 : null,
            'firmware' => $project ? $project->eeprom_firmware : null,
            'cid' => $req->query('cid'),
            'csd' => $req->query('csd'),
            'pre_script_output' => null,
            'post_script_output' => null,
            'script_return_code' => null,
            'temp1' => $req->query('temp'),
            'temp2' => null,
            'project_id' => $project ? $project->id : null,
            'image_filename' => $image ? $image->filename : null,
            'image_sha256'   => $image ? $image->sha256 : null,
            'provisioning_board' => $board,
            'provisioning_started_at' => now(),
            'provisioning_complete_at' => null
        ]);

        if (!$project)
        {
            $this->logInfo("Could not provision, because there is no active project", "error");
            return "echo 'No active project set in CMprovisioning'";
        }
        if ($project->verify && $image)
        {
            if (!$image->uncompressed_sha256)
            {
                $this->logInfo("Verification enabled, but uncompressed SHA256 not computed yet, try again later...", "error");
                return "echo 'Verification enabled, but uncompressed SHA256 not computed yet, try again later...'";
            }
            if ($image->uncompressed_size % 512 != 0)
            {
                $this->logInfo("Image is not a valid disk image. Uncompressed size not dividable by sector size of 512 bytes.", "error");
                return "echo 'Image is not a valid disk image. Uncompressed size not dividable by sector size of 512 bytes.'";

            }
        }

        $preinstall_scripts = $project->scripts()->where('script_type','preinstall')->orderBy('priority')->orderBy('id')->get();
        $postinstall_scripts = $project->scripts()->where('script_type','postinstall')->orderBy('priority')->orderBy('id')->get();

        $server = $req->server('HTTP_HOST');
        if ($server[0] == '[')
        {
            // From the CM's side IPv6LL address will end in %usb0
            $server = substr($server, 0, -1).'%usb0]';
        }

        if ($project->eeprom_firmware)
        {
            $setting = Setting::findOrFail('active_eeprom_sha256');
            $eeprom_url = "http://$server/uploads/pieeprom.bin";
            $eeprom_sha256 = $setting->value;
            $fscript = new Script;
            $fscript->id = 0;
            $fscript->name = 'Flash EEPROM firmware ('.$project->eeprom_firmware.')';
            $fscript->bg = false;
            $fscript->script = "#!/bin/sh\n"
                             . "set -e\n"
                             . "curl --retry 10 --silent --show-error -g -o pieeprom.bin \"$eeprom_url\"\n"
                             . "echo \"$eeprom_sha256  pieeprom.bin\" | sha256sum -c\n"
                             . 'flashrom -p "linux_spi:dev=/dev/spidev0.0,spispeed=16000" -w "pieeprom.bin"'."\n";
            $preinstall_scripts->prepend($fscript);
        }

        if ($project->verify && $image)
        {
            $fscript = new Script;
            $fscript->id = 0;
            $fscript->name = 'Verifying written image';
            $fscript->bg = false;

            if ($image->uncompressed_size % 1048576 == 0)
                $ddline = "dd if=".$project->storage." bs=1M count=".($image->uncompressed_size / 1048576);
            else
                $ddline = "dd if=".$project->storage." count=".($image->uncompressed_size / 512);

            $fscript->script = "#!/bin/sh\n"
                             . "set -e\n"
                             . 'READ_SHA256=$('."$ddline | sha256sum | awk '{print $1}')\n"
                             . 'echo Computed SHA256: "$READ_SHA256"'."\n"
                             . 'if [ "$READ_SHA256" = "'.$image->uncompressed_sha256.'" ]; then echo Verification successful!; else echo Verification failed; exit 2; fi'."\n";
            $postinstall_scripts->prepend($fscript);
        }

        $msg = "Provisioning started.";
        if ($project->label_moment == 'preinstall' && $project->label)
        {
            $msg .= " Printing label.";
        }
        if (count($preinstall_scripts))
        {
            $msg .= " Starting preinstall scripts.";
        }
        else if ($project->image)
        {
            $msg .= " Starting to write image.";
        }
        else
        {
            $msg .= " No image to write.";
        }
        $this->logInfo($msg);

        if ($project->label_moment == 'preinstall' && $project->label)
        {
            $this->printLabel();
        }

        // Send script to client (see view in resources/view/scriptexecute.blade.php)
        $storage = $project->storage;
        if (is_numeric($storage[strlen($storage)-1]))
        {
            $part1 = $storage."p1";
            $part2 = $storage."p2";
        }
        else
        {
            $part1 = $storage."1";
            $part2 = $storage."2";
        }

        return response()->view('scriptexecute', [
            'cm' => $this->cm,
            'project' => $project,
            'storage' => $storage,
            'part1' => $part1,
            'part2' => $part2,
            'server' => $server,
            'image_url' => $image ? "http://$server/uploads/".$image->filename_on_server : null,
            'image_extension' => $image ? $image->filename_extension : null,
            'preinstall_scripts' => $preinstall_scripts,
            'postinstall_scripts' => $postinstall_scripts
        ])->header('Content-Type', 'text/plain');
    }

    public function provisioningComplete(Request $req)
    {
        $this->cm = Cm::where('serial', $this->serial)->firstOrFail();
        $project = $this->cm->project;
        $this->cm->provisioning_complete_at = now();
        $this->cm->temp2 = $req->query('temp');
        $this->cm->save();

        $msg = 'Provisioning completed.';
        if ($project->label_moment == 'postinstall' && $project->label)
        {
            $msg .= " Printing label.";
        }
        $this->logInfo($msg);

        if ($project->label_moment == 'postinstall' && $project->label)
        {
            $this->printLabel();
        }

        return "";
    }

    public function registerLogFile(Request $req)
    {
        $this->cm = Cm::where('serial', $this->serial)->firstOrFail();
        $logfile = $req->file('log')->get();
        if (strlen($logfile) > self::MAX_LOG_SIZE)
            $logfile = substr($logfile, 0, self::MAX_LOG_SIZE)."\nLog was bigger than max allowed size. Truncated.";
        $phase   = $req->query("phase");
        $retcode = $req->query("retcode");
        $this->cm->script_return_code = $retcode;

        if ($phase == "preinstall")
        {
            $this->cm->pre_script_output = $logfile;
        }
        else if ($phase == "postinstall")
        {
            $this->cm->post_script_output = $logfile;
        }

        if ($retcode)
        {
            if ($phase == "dd" || $phase == "preinstall") {
                /* Failed before or during image write. Clear image fields in database */
                $this->cm->image_filename = null;
                $this->cm->image_sha256 = null;
            }

            $this->logInfo("Error during $phase. Return code $retcode. Script output:\n\n".$logfile, 'error');
        }
        else
        {
            if ($phase == "preinstall")
            {
                $msg = "Preinstall script complete.";
                if ($this->cm->project->image)
                {
                    $msg .= " Starting to write image.";
                }

                $this->logInfo($msg);
            }
        }

        $this->cm->save();
        return "";
    }

    /*
    public function registerFirmware(Request $req)
    {
        $this->cm = Cm::where('serial', $this->serial)->firstOrFail();
        $this->cm->firmware = $req->file('eeprom_version')->get();
        $this->cm->save();
    }
    */

    public function logInfo($msg, $loglevel = 'info')
    {
        Cmlog::create([
            'cm' => $this->serial,
            'board' => $this->cm->provisioning_board,
            'loglevel' => $loglevel,
            'ip' => request()->ip(),
            'msg' => $msg
        ]);
    }

    public function fatal($msg)
    {
        $this->logInfo($msg, 'error');
        abort(500);
    }

    public function printLabel()
    {
        $labelsettings = $this->cm->project->label;
        $label = str_replace('$mac', $this->cm->mac, $labelsettings->template);
        $label = str_replace('$serial', $this->serial, $label);
        $label = str_replace('$provisionboard', $this->cm->provisioning_board, $label);
        $tmpfile = tempnam(sys_get_temp_dir(), "label-");

        try
        {
            if (!@file_put_contents($tmpfile, $label))
                throw new \Exception("Error creating temporary file for label '$tmpfile'");

            if ($labelsettings->printer_type == 'ftp')
            {
                $ftp = @ftp_connect($labelsettings->ftp_hostname);
                if (!$ftp)
                    throw new \Exception("Error connecting to printer's FTP server ".$labelsettings->ftp_hostname);
                if (!@ftp_login($ftp, $labelsettings->ftp_username, $labelsettings->ftp_password))
                    throw new \Exception("Error logging in to printer's FTP server. Check username and password");
                @ftp_pasv($ftp, true);
                if (!@ftp_put($ftp, "label-".$this->serial.".".$labelsettings->file_extension, $tmpfile))
                    throw new \Exception("Error uploading file to printer's FTP server");
                @ftp_close($ftp);
            }
            else if ($labelsettings->printer_type == 'command')
            {
                $cmd = str_replace('$file', escapeshellarg($tmpfile), $labelsettings->print_command);
                $output = $retcode = null;
                if (@exec($cmd, $output, $retcode) === false)
                    throw new \Exception("Error executing '$cmd'");
                if ($retcode)
                    throw new \Exception("Executing '$cmd' returned exit code $retcode. Program output:\n".implode("\n", $output));
            }
        }
        catch (\Exception $e)
        {
            $this->loginfo($e->getMessage(), 'error');
        }
        @unlink($tmpfile);
    }
}
