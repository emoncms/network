#!/bin/bash

if [[ $EUID -ne 0 ]]; then
    echo "This script must be run as root" 
    echo "Try \"sudo $0\""	
    exit 1
fi

config_file=/opt/emoncms/modules/network/config.ini
if [ -f $config_file ]; then
    # Use awk to extract values from the config file
    ssid=$(awk -F ' *= *' '/ssid/{print $2}' "$config_file" | tr -d '"')
    psk=$(awk -F ' *= *' '/psk/{print $2}' "$config_file" | tr -d '"')
else
    echo "missing config file"
fi

ssid=${ssid:-"emonPi"}
psk=${psk:-"emonpi2016"}

# Check if the interface ap0 exists
if /usr/sbin/ip link show ap0 > /dev/null 2>&1; then
    echo "The interface 'ap0' already exists."
else
    echo "The interface 'ap0' does not exist. Creating the interface..."
    /usr/sbin/iw dev wlan0 interface add ap0 type __ap
    # Add any additional commands to configure the interface here
fi

sleep 1

# dnsmasq captive portal settings
filename='/etc/NetworkManager/dnsmasq.d/redirect.conf'
cat > $filename <<-EOF
address=/connectivitycheck.gstatic.com/192.168.42.1
address=/clients3.google.com/192.168.42.1
address=/captive.apple.com/192.168.42.1
address=/www.msftncsi.com/192.168.42.1
EOF

# sudo nmcli dev wifi hotspot ifname ap0 ssid emonPi password emonpi2016
nmcli con delete hotspot

# Step 1: Create a new Wi-Fi connection profile
nmcli con add type wifi ifname ap0 con-name hotspot autoconnect no ssid $ssid

# Set Wi-Fi security (Assuming WPA-PSK here)
nmcli con modify hotspot 802-11-wireless-security.key-mgmt wpa-psk
nmcli con modify hotspot 802-11-wireless-security.psk $psk

# Step 2: Modify the connection with static IP settings
nmcli con modify hotspot ipv4.addresses 192.168.42.1/24

# sudo nmcli con modify hotspot ipv4.gateway 192.168.42.1
# sudo nmcli con modify hotspot ipv4.dns 8.8.8.8
nmcli con modify hotspot 802-11-wireless.mode ap 802-11-wireless.band bg ipv4.method shared

# Step 3: Start the connection
nmcli con up hotspot
