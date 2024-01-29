#!/bin/bash

### Check if run as root ############################
# This blocks install at modules stage
# so that this script is ran later
if [[ $EUID -ne 0 ]]; then
	echo "This script must be run as root" 
	echo "Try \"sudo $0\""	
	exit 1
fi

# Possible locations for the wpa_supplicant configuration
WPA_CONFIG_LOCATIONS=(
    "/etc/wpa_supplicant/wpa_supplicant.conf"
    "/etc/wpa_supplicant/wpa_supplicant-wlan0.conf"
)

# Function to extract value after the '=' character
extract_value() {
    echo "$1" | sed -e 's/.*=//' -e 's/"//g' -e 's/#.*//'
}

# Initialize variables
SSID=""
PSK=""

# Check each possible configuration file
for WPA_CONFIG in "${WPA_CONFIG_LOCATIONS[@]}"; do
    if [[ -f "$WPA_CONFIG" ]]; then
        while IFS= read -r line; do
            if [[ "$line" =~ ssid= ]]; then
                SSID=$(extract_value "$line")
            fi
            if [[ "$line" =~ psk= ]] && [[ ! "$line" =~ ^\s*# ]]; then
                PSK=$(extract_value "$line")
            fi
        done < "$WPA_CONFIG"

        # If both SSID and PSK have been found, no need to check further
        if [[ -n "$SSID" && -n "$PSK" ]]; then
            break
        fi
    fi
done

# Check if SSID and PSK have been found
if [[ -z "$SSID" || -z "$PSK" ]]; then
    echo "SSID or PSK not found in the provided configuration files."
    exit 1
fi

# Directory for NetworkManager connections
NM_DIR="/etc/NetworkManager/system-connections"
NM_CONN_FILE="${NM_DIR}/${SSID}.nmconnection"

if [ -f $NM_CONN_FILE ]; then
    echo "NetworkManager connection configuration already exists"
    exit 1
fi

# Create the .nmconnection file
cat > "$NM_CONN_FILE" << EOF
[connection]
id=$SSID
type=wifi
interface-name=wlan0

[wifi]
ssid=$SSID
mode=infrastructure

[wifi-security]
key-mgmt=wpa-psk
psk=$PSK

[ipv4]
method=auto

[ipv6]
method=auto
EOF

# Set the appropriate permissions
chmod 600 "$NM_CONN_FILE"
echo "NetworkManager connection file created at $NM_CONN_FILE"

systemctl restart NetworkManager

