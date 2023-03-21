#!/bin/sh
set -o pipefail

export SERIAL="{{ $cm->serial }}"
export SERVER="{{ $server }}"
export STORAGE="{{ $storage }}"
export PART1="{{ $part1 }}"
export PART2="{{ $part2 }}"

# Make sure we have random entropy
echo "{{Str::random(64)}}" >/dev/urandom

@foreach ( $preinstall_scripts as $script )
# Writing script '{{$script->name}}' to /tmp/pre-{{$script->id}}.sh
cat >/tmp/pre-{{$script->id}}.sh << "CMPROVISIONINGEOF"
{!! $script->script !!}
CMPROVISIONINGEOF
@endforeach
@foreach ( $postinstall_scripts as $script )
# Writing script '{{$script->name}}' to /tmp/post-{{$script->id}}.sh
cat >/tmp/post-{{$script->id}}.sh << "CMPROVISIONINGEOF"
{!! $script->script !!}
CMPROVISIONINGEOF
@endforeach

@if (!$project->eeprom_firmware)
echo Querying and registering EEPROM version
@if ($bootmode == 3)
flashrom -p "linux_spi:dev=/dev/spidev0.0,spispeed=16000" -r "/tmp/pieeprom.bin" || true
strings /tmp/pieeprom.bin |grep VERSION: >/tmp/eeprom_version
strings /tmp/pieeprom.bin |grep BUILD_TIMESTAMP= >>/tmp/eeprom_version
@else
vcgencmd bootloader_version >/tmp/eeprom_version || true
@endif
if [ -f /tmp/eeprom_version ]; then
    curl --retry 10 -g -F 'eeprom_version=@/tmp/eeprom_version' "http://{{ $server }}/scriptexecute?serial={{ $cm->serial }}"
fi
@endif

@if ( count($preinstall_scripts) )
echo "Running pre-install scripts"
@foreach ( $preinstall_scripts as $script )
echo "===" >> /tmp/pre.log
echo "Running pre-installation script '{{ $script->name }}'" >> /tmp/pre.log
echo "===" >> /tmp/pre.log
sh -v /tmp/pre-{{$script->id}}.sh >>/tmp/pre.log 2>&1 @if ($script->bg) & @endif 
RETCODE=$?
if [ $RETCODE -ne 0 ]; then
    echo "Pre-installation script failed."
    curl --retry 10 -g -F 'log=@/tmp/pre.log' "http://{{ $server }}/scriptexecute?serial={{ $cm->serial }}&retcode=$RETCODE&phase=preinstall"
    exit 1
fi
@endforeach
curl --retry 10 -g -F 'log=@/tmp/pre.log' "http://{{ $server }}/scriptexecute?serial={{ $cm->serial }}&retcode=0&phase=preinstall"
@endif

@if ($image_url)
echo Sending BLKDISCARD to $STORAGE
blkdiscard -v $STORAGE || true

echo Writing image from {{ $image_url }} to $STORAGE
curl --retry 10 -g "{{ $image_url }}" \
@if ($image_extension == 'gz') | gzip -dc @elseif ($image_extension == 'xz') | xz -dc @elseif ($image_extension == 'bz2') | bunzip2 -dc @endif \
 | dd of=$STORAGE conv=fsync obs=1M >/tmp/dd.log 2>&1
RETCODE=$?
if [ $RETCODE -eq 0 ]; then
    echo Original image written successfully
else
    echo Writing image failed.
    curl --retry 10 -g -F 'log=@/tmp/dd.log' "http://{{ $server }}/scriptexecute?serial={{ $cm->serial }}&retcode=$RETCODE&phase=dd"
    exit 1
fi

partprobe $STORAGE
sleep 0.1
@endif

@if ( count($postinstall_scripts) )
echo "Running post-install scripts"
@foreach ( $postinstall_scripts as $script )
echo "===" >> /tmp/post.log
echo "Running post-installation script '{{ $script->name }}'" >> /tmp/post.log
echo "===" >> /tmp/post.log
sh -v /tmp/post-{{$script->id}}.sh >>/tmp/post.log 2>&1 @if ($script->bg) & @endif 
RETCODE=$?
if [ $RETCODE -ne 0 ]; then
    echo "Postinstallation script failed."
    curl --retry 10 -g -F 'log=@/tmp/post.log' "http://{{ $server }}/scriptexecute?serial={{ $cm->serial }}&retcode=$RETCODE&phase=postinstall"
    exit 1
fi
@endforeach
curl --retry 10 -g -F 'log=@/tmp/post.log' "http://{{ $server }}/scriptexecute?serial={{ $cm->serial }}&retcode=0&phase=postinstall"
@endif

TEMP=`vcgencmd measure_temp`
curl --retry 10 -g "http://{{ $server }}/scriptexecute?serial={{ $cm->serial }}&alldone=1&temp=${TEMP:5}&verify={{ $project->verify }}"

echo ""
echo "====="
echo "Provisioning completed successfully!"

sleep 0.1
if [ -f /sys/kernel/config/usb_gadget/g1/UDC ]; then
    echo "" > /sys/kernel/config/usb_gadget/g1/UDC
fi

if [ -e /sys/class/leds/led1 ]; then
    while true; do
        echo 255 > /sys/class/leds/led0/brightness
        echo 0 > /sys/class/leds/led1/brightness
        sleep 0.5
        echo 0 > /sys/class/leds/led0/brightness
        echo 255 > /sys/class/leds/led1/brightness
        sleep 0.5
    done
fi
if [ -e /sys/class/leds/led0 ]; then
    echo 255 > /sys/class/leds/led0/brightness
fi
