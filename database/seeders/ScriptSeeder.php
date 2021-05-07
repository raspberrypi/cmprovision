<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScriptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $script = <<<'EOF'
#!/bin/sh
set -e

parted -s $STORAGE "resizepart 2 -1" "quit"
resize2fs -f $PART2

mkdir -p /mnt/boot /mnt/root
mount -t ext4 $PART2 /mnt/root
umount /mnt/root
mount -t vfat $PART1 /mnt/boot
sed -i 's| init=/usr/lib/raspi-config/init_resize\.sh||' /mnt/boot/cmdline.txt
umount /mnt/boot
EOF;

        DB::table('scripts')->insert([
            'name' => 'Resize ext4 partition',
            'script_type' => 'postinstall',
            'priority' => 50,
            'bg' => false,
            'script' => $script
        ]);

        $script = <<<'EOF'
#!/bin/sh
set -e

mkdir -p /mnt/boot
mount -t vfat $PART1 /mnt/boot
echo "dtoverlay=dwc2,dr_mode=host" >> /mnt/boot/config.txt
umount /mnt/boot
EOF;

        DB::table('scripts')->insert([
            'name' => 'Add dtoverlay=dwc2 to config.txt',
            'script_type' => 'postinstall',
            'priority' => 100,
            'bg' => false,
            'script' => $script
        ]);


        $script = <<<'EOF'
#!/bin/sh
set +e

MAXSIZEKB=`mmc extcsd read /dev/mmcblk0 | grep MAX_ENH_SIZE_MULT -A 1 | grep -o '[0-9]\+ '`
mmc enh_area set -y 0 $MAXSIZEKB /dev/mmcblk0
if [ $? -eq 0 ]; then
    reboot -f
fi
EOF;

        DB::table('scripts')->insert([
            'name' => 'Format eMMC as pSLC (one time settable only)',
            'script_type' => 'preinstall',
            'priority' => 100,
            'bg' => false,
            'script' => $script
        ]);
    }
}
