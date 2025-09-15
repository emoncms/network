#!/bin/bash

# Check if the wifi-config.ini file exists in the /tmp directory
if [ ! -f /tmp/wifi-config.ini ]; then
    echo "/tmp/wifi-config.ini missing" >&2
    exit 1
fi

# Extract the SSID from the wifi-config.ini file (handle escaped characters)
ssid=$(awk -F "=" '/^ssid=/ {gsub(/^ssid=/, ""); print}' /tmp/wifi-config.ini)

# Extract the password from the wifi-config.ini file
password=$(awk -F "=" '/^password=/ {gsub(/^password=/, ""); print}' /tmp/wifi-config.ini)

# Remove the wifi-config.ini file after reading from it for security
rm /tmp/wifi-config.ini

# Validate the SSID - should not be empty and should be reasonable length
if [[ -z "$ssid" || ${#ssid} -gt 32 ]]; then
    echo "Invalid SSID: empty or too long" >&2
    exit 1
fi

# Validate the password to ensure it is a valid 64-character hexadecimal string (PBKDF2 hash)
# This ensures the hash was properly generated and prevents injection attacks
if [[ ! $password =~ ^[a-fA-F0-9]{64}$ ]]; then
    echo "Invalid password hash: must be 64 hex characters" >&2
    exit 1
fi

# Use nmcli to connect to the WiFi network with the provided SSID and password hash
/usr/bin/nmcli dev wifi connect "$ssid" password "$password" ifname wlan0

