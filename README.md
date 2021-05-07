CM4 provisioning system
===

Webapplication to mass program Compute Module 4 devices.


Usage
==

Using the webapplication as end-user.

- Make sure eth0 connects to an Ethernet switch that only has the CMIO 4 boards connected. Do NOT connect eth0 to your office/public network, or it may "provision" other Pi devices in your network as well.
You do can use wifi to connect to your local network.

1) start with a fresh RPI OS (Lite) installation.  For simplicity, use rpi-imager and the advanced settings menu (Ctrl-Shift-X) to set the password, hostname and WiFi.

2) configure eth0 to have a static IP of 172.20.0.1 inside a /16 subnet (netmask 255.255.0.0)

```
sudo nano /etc/dhcpcd.conf
```

Add to bottom of file:

```
interface eth0
static ip_address=172.20.0.1/16
```

(Do not set gateway)
Reboot afterwards.

3) run `sudo apt update` to make sure your repository is up-to-date, and it is able to install dependencies.

4) install the ready-made .deb provided ( https://github.com/raspberrypi/cmprovision/releases/ )

```
sudo apt install ./cmprovision4_*_all.deb
```

5) set a web application username and password with:

```
/var/lib/cmprovision/artisan auth:create-user
```

- You can now access the webinterface with a web browser on the wifi IP.


Development
==

This PHP webapplication uses the Laravel framework.
Make sure you familarize yourself with the fine documentation: https://laravel.com/docs/8.x/

In particular note:
* run `composer --install` to install the dependencies living in the `vendor` directory.
* you probably also want to `npm install` to be able to rebuild resources.
* if you want to use Tailwind css styles not already used in the application run: `npm run prod` after adding the html to have the .css file rebuild with the used styles included. (alternatively can run `npm run dev` to include all styles. But will result in a large .css file, so only use that during development)
* if you modify .blade files make sure you regenerate the cache with: `./artisan view:cache`

License
==

The main code of the CM4 provisioning system is made available under the terms of the BSD 3-clause ("new") license.
Look in the "vendor" directory (after running `composer --install`) to consult the open source licenses of the various dependencies like the Laravel framework used.

