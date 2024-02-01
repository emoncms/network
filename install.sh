#!/bin/bash


### Check if run as root ############################
# This blocks install at modules stage
# so that this script is ran later
if [[ $EUID -ne 0 ]]; then
	echo "This script must be run as root" 
	echo "Try \"sudo $0\""	
	exit 1
fi

echo "-- install default network module config"
if [ ! -f /opt/emoncms/modules/network/config.ini ]; then 
    cp /opt/emoncms/modules/network/default.config.ini /opt/emoncms/modules/network/config.ini
fi

# ------------------------------------------------------
# Install network-sudoers
# ------------------------------------------------------
echo "-- install /etc/sudoers.d/network-sudoers"
filename=/etc/sudoers.d/network-sudoers
cat > $filename <<-EOF                                                                                   
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/wifi_connect.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/wifi_rescan.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/startAP.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/stopAP.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/nm_log.sh
EOF

echo "-- configure /etc/NetworkManager/NetworkManager.conf"
filename=/etc/NetworkManager/NetworkManager.conf
cat > $filename <<-EOF  
[main]
plugins=ifupdown,keyfile
dns=dnsmasq

[ifupdown]
managed=false

[device]
wifi.scan-rand-mac-address=no
EOF

# ------------------------------------------------------
# wifi-check not yet implemented (to review)
# ------------------------------------------------------
echo "-- install /usr/local/bin/wifi-check"
ln -sf /opt/emoncms/modules/network/scripts/wifi-check /usr/local/bin/wifi-check

echo "-- install wifi-check cron entry"
crontab -l > mycron
if grep -Fq "wifi-check" mycron; then
    echo "wifi-check already present in crontab"
else
    echo "* * * * * /usr/local/bin/wifi-check >> /var/log/emoncms/wificheck.log 2>&1" >> mycron
    crontab mycron
    rm mycron
fi
