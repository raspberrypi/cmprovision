<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\EthernetSwitch;

class ConfigureEthernetSwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ethernetswitch:configure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect to managed Ethernet switch by SNMP for port identification purposes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ip = $this->ask("Ethernet switch IP-address (leave empty to disable module)");
        if (!$ip)
        {
            Setting::destroy("ethernetswitch_ip");
            Setting::destroy("ethernetswitch_snmp_community");
            echo "Disabled Ethernet switch integration\n";
            return 0;
        }

        do
        {
            $community = $this->ask("SNMP v2c community name");
        } while (!$community);

        echo "Trying to communicate with Ethernet switch...\n";
        $switch = new EthernetSwitch($ip, $community);
        $table = $switch->getMac2portNameTable();

        if ($table && count($table))
        {
            echo "=================================\n";
            echo "Mac -> interface name/alias table\n";
            echo "=================================\n";
            foreach ($table as $mac => $portName)
            {
                echo "$mac $portName\n";
            }
            echo "=================================\n";

            Setting::updateOrCreate(['key' => 'ethernetswitch_ip'], ['value' => $ip]);
            Setting::updateOrCreate(['key' => 'ethernetswitch_snmp_community'], ['value' => $community]);

            echo "\nCommunication successful. Enabled Ethernet switch integration.\n";

        }
        else
        {
            echo "Error communicating with switch. Unable to connect or does not support BRIDGE-MIB.\n";
            echo "Settings unchanged.\n";
            return 1;
        }

        return 0;
    }
}
