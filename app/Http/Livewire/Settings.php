<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Host;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

class Settings extends Component
{
    public $dnsmasqRunning, $rpibootRunning, $logOutput, $logTitle;
    public $ip, $mac, $detectedMacs;
    public $hosts;
    public $logModalOpen = false, $staticModalOpen = false;

    public function render()
    {
        $this->dnsmasqRunning = $this->isActive('cmprovision-dnsmasq');
        $this->rpibootRunning = $this->isActive('cmprovision-rpiboot');
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

    protected function regenDnsmasqConfAndRestart()
    {
        $confFile = base_path('etc/dnsmasq.conf');
        $extraConf = "";
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
            if (strpos($line, "dhcp-host=") === 0)
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
