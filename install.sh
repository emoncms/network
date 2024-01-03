#!/bin/bash


### Check if run as root ############################
if [[ $EUID -ne 0 ]]; then
	echo "This script must be run as root" 
	echo "Try \"sudo $0\""	
	exit 1
fi
	
## Change over to systemd-networkd
## https://raspberrypi.stackexchange.com/questions/108592
# deinstall classic networking
apt --autoremove -y purge ifupdown dhcpcd5 isc-dhcp-client isc-dhcp-common rsyslog
apt-mark hold ifupdown dhcpcd5 isc-dhcp-client isc-dhcp-common rsyslog raspberrypi-net-mods openresolv
rm -r /etc/network /etc/dhcp

# setup/enable systemd-resolved and systemd-networkd
apt --autoremove -y purge avahi-daemon
apt-mark hold avahi-daemon libnss-mdns
apt install -y libnss-resolve
ln -sf /run/systemd/resolve/stub-resolv.conf /etc/resolv.conf
systemctl enable systemd-networkd.service systemd-resolved.service

# ------------------------------------------------------
# eth0 is the ethernet interface
# ------------------------------------------------------
filename='/etc/systemd/network/04-eth0.network'
if [ ! -f $filename ]; then
cat > $filename <<-EOF
  [Match]
  Name=eth0
  [Network]
  DHCP=yes
  MulticastDNS=yes
EOF
else
  echo "File $filename already exists"
fi

# ------------------------------------------------------
# wlan0 is the wifi client interface
# ------------------------------------------------------
filename='/etc/systemd/network/08-wlan0.network'
if [ ! -f $filename ]; then
cat > $filename <<-EOF
  [Match]
  Name=wlan0
  [Network]
  DHCP=yes
EOF
else
  echo "File $filename already exists"
fi

# ------------------------------------------------------
# ap0 is the wifi access point interface
# ------------------------------------------------------
filename='/etc/systemd/network/12-ap0.network'
if [ ! -f $filename ]; then
cat > $filename <<-EOF
  [Match]
  Name=ap0
  [Network]
  Address=192.168.42.1/24
  DHCPServer=yes
  [DHCPServer]
  DNS=84.200.69.80 84.200.70.40
EOF
else 
  echo "File $filename already exists"
fi

# ------------------------------------------------------
# ap0 wpa_supplicant config
# ------------------------------------------------------
filename='/etc/wpa_supplicant/wpa_supplicant-ap0.conf'
if [ ! -f $filename ]; then
cat > $filename <<-EOF
  country=GB
  ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
  update_config=1

  network={
      ssid="emonPi"
      mode=2
      key_mgmt=WPA-PSK
      psk="emonsd"
      frequency=2412
  }
EOF
else 
  echo "File $filename already exists"
fi

# ------------------------------------------------------
# wlan0 wpa_supplicant config
# ------------------------------------------------------
filename='/etc/wpa_supplicant/wpa_supplicant-wlan0.conf'
if [ ! -f $filename ]; then
cat > $filename <<-EOF
  ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
  update_config=1
  country=GB
EOF
else 
  echo "File $filename already exists"
fi

# ------------------------------------------------------
# Create ap0 service
# ------------------------------------------------------
filename=/lib/systemd/system/wpa_supplicant@ap0.service
if [ ! -f $filename ]; then
cat > $filename <<-EOF
[Unit]
Description=WPA supplicant daemon (interface-specific version)
Requires=sys-subsystem-net-devices-wlan0.device
After=sys-subsystem-net-devices-wlan0.device
# Conflicts=wpa_supplicant@wlan0.service
Before=network.target
Wants=network.target

# NetworkManager users will probably want the dbus version instead.

[Service]
Type=simple
ExecStartPre=/sbin/iw dev wlan0 interface add ap0 type __ap
ExecStart=/sbin/wpa_supplicant -c/etc/wpa_supplicant/wpa_supplicant-%I.conf -Dnl80211,wext -i%I
ExecStopPost=/sbin/iw dev ap0 del

[Install]
Alias=multi-user.target.wants/wpa_supplicant@%i.service
EOF
else 
  echo "File $filename already exists"
fi

systemctl daemon-reload
systemctl enable wpa_supplicant@ap0.service
systemctl enable wpa_supplicant@wlan0.service
systemctl disable wpa_supplicant.service

echo "Reboot now!"
exit 0

