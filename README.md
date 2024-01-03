# Emoncms Wifi Module 

Wifi configuration module for emoncms. Installed on emonPi pre-built SD card image 

## Install

Remove old WiFi module if present:

    rm -rf /var/www/emoncms/Modules/wifi

Install new network module:

    cd /opt/emoncms/modules
    git clone https://github.com/emoncms/network
    ln -s /opt/emoncms/modules/network/network-module /var/www/emoncms/Modules/network

Install sudoers file:

    sudo cp /opt/emoncms/modules/network/wifi-sudoers /etc/sudoers.d/wifi-sudoers

## Licence

GNU AFFERO GENERAL PUBLIC LICENSE, see emoncms repo:<br>
https://github.com/emoncms/emoncms/blob/master/LICENSE.txt
