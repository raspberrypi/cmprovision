# Raspberry Pi Compute Module Provisioning System #

Web application to mass program compute modules; designed to run on a Raspberry Pi 4. Compute Module 3, 3+ and 4 are supported.


## Provisioning Compute Module 4 Devices ##

This process requires a Raspberry Pi 4 which will be used solely to mass program compute modules. Make sure eth0 connects to an Ethernet switch that only has the compute modules you wish to program connected.

**WARNING:** Do NOT connect eth0 to your office/public network, since it may also provision other Raspberry Pi devices. Instead, use the wireless LAN interface to access the web interface, and your local network.

1. Start with a fresh 64-bit Raspberry Pi OS (Lite) installation. For simplicity, use [Raspberry Pi Imager](https://www.raspberrypi.com/software/) and the advanced settings menu (Ctrl-Shift-X) to set the password, hostname and wireless LAN configuration.  
    
    **NOTE:** If you intend to write images larger than 2 GB, you MUST install the **64-bit** edition of Raspberry Pi OS (Lite), which is available in Raspberry Pi Imager under the category "Raspberry Pi OS (other)" -> "Raspberry Pi OS Lite (64-bit)".

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

1. Run `sudo apt update` to make sure the package information on your Pi is up to date, and it is able to install dependencies.

1. Install the ready-made .deb package from https://github.com/raspberrypi/cmprovision/releases/:  

    ```
    sudo apt install ./cmprovision4_*_all.deb  
    ```

1. Set a web application username and password with:  

    ```
    sudo /var/lib/cmprovision/artisan auth:create-user  
    ```

You can now access the web interface with a web browser on the wireless LAN IP address.

## Provisioning Compute Module 3 and 3+ Devices ##

This process requires a Raspberry Pi 4 which will be used solely to mass program compute modules. Make sure eth0 connects to an Ethernet switch that only has the compute modules you wish to program connected.

**WARNING:** Do NOT connect eth0 to your office/public network, since it may also provision other Raspberry Pi devices. Instead, use the wireless LAN interface to access the web interface, and your local network.

When provisioning Compute Module 3 and 3+ modules, Ethernet is not used: instead the images are transferred over USB. Use a good quality USB A to micro USB cable between the Pi 4 acting as server and the `USB slave` port of each CMIO board containing the compute modules to be provisioned. If you wish to provision more than 4 compute modules at a time, a USB hub can be used. Connect power supplies to the `power in` micro USB port of the CMIO boards, and make sure the J4 `USB slave boot enable` jumper is set to `en`.


1. Start with a fresh 64-bit Raspberry Pi OS (Lite) installation.  For simplicity, use [Raspberry Pi Imager](https://www.raspberrypi.com/software/) and the advanced settings menu (Ctrl-Shift-X) to set the password, hostname and wireless LAN configuration.  
    
    **NOTE:** If you intend to write images larger than 2 GB, you MUST install the **64-bit** edition of Raspberry Pi OS (Lite), which is available in Raspberry Pi Imager under the category "Raspberry Pi OS (other)" -> "Raspberry Pi OS Lite (64-bit)".

1. Run `sudo apt update` to make sure the package information on your Pi is up to date, and it is able to install dependencies.

1. Install the ready-made .deb package from https://github.com/raspberrypi/cmprovision/releases/:  

    ```
    sudo apt install ./cmprovision4_*_all.deb  
    ```

1. Set a web application username and password with:  

    ```
    sudo /var/lib/cmprovision/artisan auth:create-user  
    ```

You can now access the web interface with a web browser on the wireless LAN IP address.

### Provisioning from an alternate OS ###

During provisioning of CM3 and CM3+ devices, a small utility operating system, `scriptexecute`, is USB booted each compute module. This configures the compute modules as  USB network adapters, and expects to be able to reach the provisioning server on predictable IPv6 link-local addresses that can be calculated based on the MAC address that each compute module chooses for its USB network interface. On Raspberry Pi OS, this is configured automatically by putting `slaac hwaddr` in `/etc/dhcpcd.conf`, however if you OS does not use `dhcpcd` as its network manager, then you will need to set this up manually. Examples of alternate network managers include `systemd-networkd` and `Network Manager`. How you set this up depends on the exact network manager used by your Linux distribution, so we cannot advise on how to do this.


## Development ##

This PHP web application uses the Laravel framework.
Make sure you familarize yourself with the fine documentation: https://laravel.com/docs/8.x/.

In particular note:
* run `composer --install` to install the dependencies living in the `vendor` directory.
* you probably also want to `npm install` to be able to rebuild resources.
* if you want to use Tailwind css styles not already used in the application run: `npm run prod` after adding the html to have the .css file rebuild with the used styles included. (alternatively can run `npm run dev` to include all styles. But will result in a large .css file, so only use that during development).
* if you modify .blade files make sure you regenerate the cache with: `./artisan view:cache`.

## Licence ##

The main code of the Raspberry Pi compute module provisioning system is made available under the terms of the BSD 3-clause ("new") license.
Look in the `vendor` directory (after running `composer --install`) to consult the open source licenses of the various dependencies like the Laravel framework used.
