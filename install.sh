#!/bin/bash

# ------------------------------------------------------
# Install network-sudoers
# ------------------------------------------------------
filename=/etc/sudoers.d/network-sudoers
sudo cat > $filename <<-EOF                                                                                   
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

