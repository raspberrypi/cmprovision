<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    use HasFactory;

    protected $fillable = ['mac', 'ip', 'hostname'];

    public static function firstAvailableIP()
    {
        $net = "172.20";

        for ($i = 0; $i < 256; $i++)
        {
            for ($j = 0; $j < 256; $j++)
            {
                if ($i == 0 && ($j == 0 || $j == 1))
                    continue; /* Skip network IP 172.20.0.0 and our IP 172.20.0.1 */

                $ip = "$net.$i.$j";
                if (!Host::firstWhere('ip', $ip))
                {
                    return $ip;
                }
            }
        }

        return "";
    }
}
