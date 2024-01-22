#!/bin/bash

# Check if the interface ap0 exists
if ip link show ap0 > /dev/null 2>&1; then
    echo "The interface 'ap0' already exists."
else
    echo "The interface 'ap0' does not exist. Creating the interface..."
    sudo iw dev wlan0 interface add ap0 type __ap
    # Add any additional commands to configure the interface here
fi

sleep 1

# sudo nmcli dev wifi hotspot ifname ap0 ssid emonPi password emonpi2016

sudo nmcli con delete hotspot

# Step 1: Create a new Wi-Fi connection profile
sudo nmcli con add type wifi ifname ap0 con-name hotspot autoconnect no ssid emonPi

# Set Wi-Fi security (Assuming WPA-PSK here)
sudo nmcli con modify hotspot 802-11-wireless-security.key-mgmt wpa-psk
sudo nmcli con modify hotspot 802-11-wireless-security.psk emonpi2016

# Step 2: Modify the connection with static IP settings
sudo nmcli con modify hotspot ipv4.addresses 192.168.42.1/24
sudo nmcli con modify hotspot ipv4.gateway 192.168.42.1
sudo nmcli con modify hotspot ipv4.dns 8.8.8.8
sudo nmcli con modify hotspot 802-11-wireless.mode ap 802-11-wireless.band bg ipv4.method manual

# Step 3: Start the connection
sudo nmcli con up hotspot
