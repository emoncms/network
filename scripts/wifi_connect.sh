#!/bin/bash

# Check if the wifi-config.ini file exists in the /tmp directory
if [ ! -f /tmp/wifi-config.ini ]; then
    echo "/tmp/wifi-config.ini missing" >&2
    exit 1
fi

# Extract the SSID from the wifi-config.ini file
ssid=$(awk -F "=" '/ssid/ {print $2}' /tmp/wifi-config.ini)

# Extract the password from the wifi-config.ini file
password=$(awk -F "=" '/password/ {print $2}' /tmp/wifi-config.ini)

# Remove the wifi-config.ini file after reading from it for security
rm /tmp/wifi-config.ini

# Validate the SSID to ensure it contains only valid characters (letters, numbers, underscore, hyphen)
# and is not longer than 32 characters
if [[ ! "$ssid" =~ ^[a-zA-Z0-9_-]{1,32}$ ]]; then
    echo "Invalid SSID" >&2
    exit 1
fi

# Validate the password to ensure it is a valid hexadecimal string
# This is a simple check assuming password is a hash
if [[ ! $password =~ ^[a-fA-F0-9]+$ ]]; then
    echo "Invalid password hash" >&2
    exit 1
fi

# Use nmcli to connect to the WiFi network with the provided SSID and password hash
/usr/bin/nmcli dev wifi connect "$ssid" password "$password" ifname wlan0

