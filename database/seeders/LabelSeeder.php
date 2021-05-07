<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $template = <<<'EOF'
m m
J
; 6.35 mm label height, 19.05 mm width
S l1;0,0,6.35,6.35,19.05
; 18x18 matrix can hold 25 characters, and at 0.3 mm dot width should be 5.4x5.4 mm
B 0.4,0.6,0,DATAMATRIX+ROWS18+COLS18,0.3;$mac
; text MAC address
T 6,2,0,3,1.5;$mac
; text CMIO board (jumper setting)
T 14,6,0,3,1.5;$provisionboard
A 1
EOF;     

        DB::table('labels')->insert([
            'name' => 'Datamatrix on Brady/CAB printer',
            'printer_type' => 'ftp',
            'ftp_hostname' => 'CHANGEME',
            'ftp_username' => 'print',
            'ftp_password' => 'ftpprint',
            'print_command' => '/usr/bin/lpr -o raw $file 2>&1',
            'file_extension' => 'jscript',
            'template' => $template
        ]);
    }
}
