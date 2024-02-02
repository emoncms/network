# Emoncms Network Module 

Network configuration module for emoncms.

## Install

Remove old WiFi module if present:

    rm -rf /var/www/emoncms/Modules/wifi

Install new network module:

    cd /opt/emoncms/modules
    git clone https://github.com/emoncms/network
    ln -s /opt/emoncms/modules/network/network-module /var/www/emoncms/Modules/network

Run install script

    cd /opt/emoncms/modules/network
    ./install.sh

## Licence

GNU AFFERO GENERAL PUBLIC LICENSE, see emoncms repo:<br>
https://github.com/emoncms/emoncms/blob/master/LICENSE.txt

Credit to: 

- https://github.com/0unknwn/auto-hotspot
- https://raspberrypi.stackexchange.com/questions/100195/automatically-create-hotspot-if-no-network-is-available

for a guide on how to do this with systemd-networkd
