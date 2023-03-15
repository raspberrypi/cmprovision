<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Host;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

class Settings extends Component
{
    public $dnsmasqRunning, $rpibootRunning, $logOutput, $logTitle, $oui_filter = false;
    public $ip, $mac, $detectedMacs;
    public $hosts;
    public $logModalOpen = false, $staticModalOpen = false;
    const DEFAULT_OUI_FILTER = "b8:27:eb:*:*:*\ndc:a6:32:*:*:*\ne4:5f:01:*:*:*\nd8:3a:dd:*:*:*\n";

    public function render()
    {
        $this->dnsmasqRunning = $this->isActive('cmprovision-dnsmasq');
        $this->rpibootRunning = $this->isActive('cmprovision-rpiboot');
        
        if (!$this->oui_filter)
        {
            $s = Setting::find("oui_filter");
            if ($s)
                $this->oui_filter = $s->value;
            else
                $this->oui_filter = self::DEFAULT_OUI_FILTER;
        }
        $this->hosts = Host::orderBy('ip')->get();

        return view('livewire.settings');
    }

    protected function isActive($service)
    {
        $output = $retcode = '';
        exec("/bin/systemctl is-active ".escapeshellarg($service), $output, $retcode);
        return ($retcode == 0);
    }

    protected function getLog($service)
    {
        $output = $retcode = '';
        exec("/bin/journalctl --boot -u ".escapeshellarg($service), $output, $retcode);
        return implode("\n", $output);
    }

    protected function restartDnsmasq()
    {
        $output = $retcode = '';
        exec("sudo -n /bin/systemctl restart cmprovision-dnsmasq", $output, $retcode);
        return ($retcode == 0);
    }

    public function viewLogDnsmasq()
    {
        $this->logTitle = "Dnsmasq log since boot";
        $this->logOutput = $this->getLog('cmprovision-dnsmasq');
        $this->logModalOpen = true;
    }

    public function viewLogRpiboot()
    {
        $this->logTitle = "Rpiboot log since boot";
        $this->logOutput = $this->getLog('cmprovision-rpiboot');
        $this->logModalOpen = true;
    }

    public function viewLogLaravel()
    {
        $this->logTitle = "Laravel error log";
        $this->logOutput = file_get_contents(storage_path('logs/laravel.log'));
        $this->logModalOpen = true;
    }

    public function closeModal()
    {
        $this->logOutput = '';
        $this->logModalOpen = $this->staticModalOpen = false;
    }

    public function saveDHCPsettings()
    {
        $this->oui_filter = str_replace("\r", "", $this->oui_filter);

        Validator::extend('oui_filter',
        function($attribute, $value, $parameters, $validator) {
            $lines = explode("\n", $value);
            foreach ($lines as $line)
            {
                $line = trim($line);

                if ($line && !preg_match("/^([*0-9A-Fa-f]{1,2}[:]){5}([*0-9A-Fa-f]{1,2})$/", $line))
                {
                    return false;
                }
            }

            return true;
        }, "Invalid OUI filter");

        $this->validate(['oui_filter' => 'required|oui_filter']);
        Setting::updateOrCreate(['key' => 'oui_filter'], ['value' => $this->oui_filter]);

        $this->regenDnsmasqConfAndRestart();
    }

    protected function regenDnsmasqConfAndRestart()
    {
        $confFile = base_path('etc/dnsmasq.conf');
        $extraConf = "";
        $s = Setting::find("oui_filter");
        if ($s)
            $oui_filter = $s->value;
        else
            $oui_filter = self::DEFAULT_OUI_FILTER;

        $lines = explode("\n", $oui_filter);
        foreach ($lines as $line)
        {
            $line = trim($line);

            if ($line)
            {
                $extraConf .= "dhcp-mac=set:client_is_a_pi,$line\n";
            }
        }
        $hosts = Host::orderBy('ip')->get();
        foreach ($hosts as $host)
        {
            $extraConf .= "dhcp-host=".$host->mac.",set:client_is_a_pi,".$host->ip;
            if ($host->hostname)
                $extraConf .= ','.$hostname;
            $extraConf .= "\n";
        }

        $oldConf = @file_get_contents($confFile);
        if (!$oldConf)
        {
            session()->flash('message', "Error reading existing $confFile");
            return;
        }

        $oldConfLines = explode("\n", trim($oldConf));
        $newConf = '';

        foreach ($oldConfLines as $line)
        {
            if (strpos($line, "dhcp-mac=") === 0 || strpos($line, "dhcp-host=") === 0)
                continue;

            $newConf .= $line."\n";
        }
        $newConf .= $extraConf;

        if (!@file_put_contents($confFile, $newConf))
        {
            session()->flash('message', "Error writing to $confFile. Check file permissions.");
            return;
        }

        if ($this->restartDnsmasq() )
            session()->flash('message', 'Restarted dnsmasq.');
        else
            session()->flash('message', 'Unable to restart dnsmasq automatically, please do so manually.');
    }

    public function deleteStaticIP($id)
    {
        Host::destroy($id);
        session()->flash('message', 'Static IP deleted.');
    }

    public function addStaticIP()
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->ip = Host::firstAvailableIP();
        $this->mac = '';
        $this->detectedMacs = [];
        $this->staticModalOpen = true;
        
        $dnsmasqlog = explode("\n", $this->getLog('cmprovision-dnsmasq'));
        foreach ($dnsmasqlog as $l)
        {
            if (preg_match("/ ([0-9A-Fa-f:]{17}) no address available/", $l, $regs))
            {
                if (!in_array($regs[1], $this->detectedMacs))
                {
                    $this->detectedMacs[] = $regs[1];
                }
            }
        }

        /* Prefill MAC address field if a host attempted to get DHCP lease recently
           and it is not already in our database */
        if (count($this->detectedMacs))
        {
            $lastmac = end($this->detectedMacs);
            if (!Host::firstWhere('mac', $lastmac))
            {
                $this->mac = $lastmac;
            }
        }
    }

    public function storeStaticIP()
    {
        $this->mac = strtolower($this->mac);

        $this->validate([
            'ip' => 'required|ipv4|unique:hosts,ip',
            'mac' => 'required|regex:/^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$/|unique:hosts,mac'
        ]);

        Host::create([
            'ip' => $this->ip,
            'mac' => $this->mac
        ]);

        $this->regenDnsmasqConfAndRestart();
        $this->closeModal();        
    }
}
