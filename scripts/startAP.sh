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

sudo nmcli dev wifi hotspot ifname ap0 ssid emonPi password emonpi2016
