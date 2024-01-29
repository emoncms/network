#!/bin/bash


### Check if run as root ############################
# This blocks install at modules stage
# so that this script is ran later
if [[ $EUID -ne 0 ]]; then
	echo "This script must be run as root" 
	echo "Try \"sudo $0\""	
	exit 1
fi

# ------------------------------------------------------
# Install network-sudoers
# ------------------------------------------------------
filename=/etc/sudoers.d/network-sudoers
cat > $filename <<-EOF                                                                                   
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/wifi_connect.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/wifi_rescan.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/startAP.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/stopAP.sh
www-data ALL=(ALL) NOPASSWD:/opt/emoncms/modules/network/scripts/nm_log.sh
EOF

# ------------------------------------------------------
# wifi-check not yet implemented (to review)
# ------------------------------------------------------
# ln -sf /opt/emoncms/modules/network/scripts/wifi-check /usr/local/bin/wifi-check

