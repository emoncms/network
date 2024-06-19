#!/bin/bash

# Prompt the user for the SSID
read -p "Enter the SSID: " ssid

# Exit if the SSID is empty
if [ -z "$ssid" ]; then
  echo "SSID cannot be empty"
  exit 1
fi

# Exit if the SSID is called hotspot
if [ "$ssid" == "hotspot" ]; then
  echo "SSID cannot be called hotspot"
  exit 1
fi

# Prompt the user for the PSK (password)
read -sp "Enter the PSK: " psk
echo  # To move to a new line after the password input

# Exit if the PSK is empty
if [ -z "$psk" ]; then
  echo "PSK cannot be empty"
  exit 1
fi

wifi_interface="wlan0"  # Replace this with your WiFi interface name if different

echo "Creating connection $ssid"

# Delete any existing connection with the same SSID
nmcli connection delete id "$ssid" 2>/dev/null

# Check if deletion was successful or if the connection did not exist
if [ $? -eq 0 ]; then
  echo "Deleted existing connection with SSID $ssid"
else
  echo "No existing connection with SSID $ssid found, continuing with creation"
fi

# Add new connection profile
nmcli connection add type wifi ifname "$wifi_interface" con-name "$ssid" ssid "$ssid"

# Check if the connection profile was created successfully
if [ $? -ne 0 ]; then
  echo "Failed to add new connection profile for SSID $ssid"
  exit 1
fi

# Modify the connection to add security settings
nmcli connection modify "$ssid" wifi-sec.key-mgmt wpa-psk

# Check if the security settings were added successfully
if [ $? -ne 0 ]; then
  echo "Failed to set key management for SSID $ssid"
  exit 1
fi

nmcli connection modify "$ssid" wifi-sec.psk "$psk"

# Check if the passkey was set successfully
if [ $? -ne 0 ]; then
  echo "Failed to set passkey for SSID $ssid"
  exit 1
fi

echo "Connection $ssid created successfully"

# Display the connection profile for verification
connection_file="/etc/NetworkManager/system-connections/$ssid.nmconnection"
if [ -f "$connection_file" ]; then
  cat "$connection_file"
else
  echo "Connection file $connection_file not found"
fi

