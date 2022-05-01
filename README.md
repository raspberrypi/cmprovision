# CM provisioning system #

Web application that can be run on a Pi 4 to mass program Compute Module devices.


## Using the CM provisioning system to provision CM4 modules ##

Using the web application as an end user.

Make sure eth0 connects to an Ethernet switch that only has the CMIO 4 boards connected. **Do NOT connect eth0 to your office/public network, or it may provision other Pi devices in your network as well.** You can use the wifi interface to connect to your local network.

1. Start with a fresh 64-bit Raspberry Pi OS (Lite) installation.  For simplicity, use Raspberry Pi Imager and the advanced settings menu (Ctrl-Shift-X) to set the password, hostname and wifi.  
    
    **NOTE:** If you intend to write images larger than 2 GB, you MUST install the **64-bit** edition of Raspberry Pi OS (Lite), which is available in Imager under the category "Raspberry Pi OS (other)" -> "Raspberry Pi OS Lite (64-bit)"

1. Configure eth0 to have a static IP of 172.20.0.1 inside a /16 subnet (netmask 255.255.0.0):  
    
    ```
    sudo nano /etc/dhcpcd.conf  
    ```
    
    Add to bottom of file:  

    ```
    interface eth0
    static ip_address=172.20.0.1/16  
    ```

    Do not set a default gateway. Reboot afterwards.

1. Run `sudo apt update` to make sure your repository is up-to-date, and it is able to install dependencies.

1. Install the ready-made .deb package from https://github.com/raspberrypi/cmprovision/releases/:  

    ```
    sudo apt install ./cmprovision4_*_all.deb  
    ```

1. Set a web application username and password with:  

    ```
    sudo /var/lib/cmprovision/artisan auth:create-user  
    ```

You can now access the web interface with a web browser on the wifi IP.

## Using the CM provisioning system to provision CM3(+) modules ##

When provisioning CM3 modules, Ethernet is not used, but the images are transferred over USB instead.
For that you need to connect a (good quality) USB-A to Micro-USB cable between the Pi 4 acting as server and the "USB slave" port of each CMIO board.
If you want to provision more than 4 CMIO boards at a time, an USB hub can be used.
Also connect power supplies to the "power in" micro-USB port of the CMIO boards, and make sure the J4 "USB slave boot enable" jumper is set to "en".

Do NOT connect the Ethernet port of the Pi 4. You can use wifi to access the management web interface.


1. Start with a fresh 64-bit Raspberry Pi OS (Lite) installation.  For simplicity, use Raspberry Pi Imager and the advanced settings menu (Ctrl-Shift-X) to set the password, hostname and wifi.  
    
    **NOTE:** If you intend to write images larger than 2 GB, you MUST install the **64-bit** edition of Raspberry Pi OS (Lite), which is available in Imager under the category "Raspberry Pi OS (other)" -> "Raspberry Pi OS Lite (64-bit)"

1. Run `sudo apt update` to make sure your repository is up-to-date, and it is able to install dependencies.

1. Install the ready-made .deb package from https://github.com/raspberrypi/cmprovision/releases/:  

    ```
    sudo apt install ./cmprovision4_*_all.deb  
    ```

1. Set a web application username and password with:  

    ```
    sudo /var/lib/cmprovision/artisan auth:create-user  
    ```

You can now access the web interface with a web browser on the wifi IP.

### If running the cmprovisioning system on another OS than RPI OS ###

During provisioning a small utility operating system (scriptexecute) is USB booted on the CM3 modules, which will pretend to be a USB network adapter, and expects to be able to reach the server
on a predictable IPv6 link-local address that can be calculated based on the MAC address that the CM3 playing USB network adapter choses.
The cmprovision4 .deb will configure this automatically for RPI OS (by putting "slaac hwaddr" in `/etc/dhcpcd.conf`), so no
action on your part is required for network configuration when using RPI OS.
However if you chose to use another Linux distribution ON THE SERVER that uses a different network manager than dhcpcd (e.g. systemd-networkd or NetworkManager), be aware that you need to setup this manually.
How you do this depends on the exact network manager used by your Linux distribution, so no generic instructions for this can be given.

## Development ##

This PHP web application uses the Laravel framework.
Make sure you familarize yourself with the fine documentation: https://laravel.com/docs/8.x/.

In particular note:
* run `composer --install` to install the dependencies living in the `vendor` directory.
* you probably also want to `npm install` to be able to rebuild resources.
* if you want to use Tailwind css styles not already used in the application run: `npm run prod` after adding the html to have the .css file rebuild with the used styles included. (alternatively can run `npm run dev` to include all styles. But will result in a large .css file, so only use that during development).
* if you modify .blade files make sure you regenerate the cache with: `./artisan view:cache`.

## Licence ##

The main code of the CM4 provisioning system is made available under the terms of the BSD 3-clause ("new") license.
Look in the `vendor` directory (after running `composer --install`) to consult the open source licenses of the various dependencies like the Laravel framework used.

