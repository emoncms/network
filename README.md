# Emoncms Wifi Module 

Wifi configuration module for emoncms. Installed on emonPi pre-built SD card image 

## Install

Remove old WiFi module if present:

    rm -rf /var/www/emoncms/Modules/wifi

Install new network module:

```
  cd /opt/emoncms/modules
  git clone https://github.com/emoncms/network
  ln -s /opt/emoncms/modules/network/network-module /var/www/emoncms/Modules/network
  ```
  
Give web user permission to execute system wlan commans:

Append the following to the end of `/etc/sudoers`:

  `sudo nano /etc/sudoers`
  
```
  www-data ALL=(ALL) NOPASSWD:/sbin/ifdown wlan0,/sbin/ifup wlan0,/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf,/bin/cp /tmp/wifidata /etc/wpa_supplicant/wpa_supplicant.conf,/sbin/wpa_cli scan_results,/sbin/wpa_cli scan,/bin/cp /tmp/hostapddata /etc/hostapd/hostapd.conf,/etc/init.d/hostapd start,/etc/init.d/hostapd stop,/etc/init.d/dnsmasq start,/etc/init.d/dnsmasq stop,/bin/cp /tmp/dhcpddata /etc/dnsmasq.conf,/home/pi/emonpi/wifiAP/networklog.sh,/home/pi/emonpi/wifiAP/stopAP.sh
```

Install emoncms service runner, see installation guide [here](https://github.com/emoncms/emoncms/blob/master/scripts/services/install-service-runner-update.md).

## Licence

GNU AFFERO GENERAL PUBLIC LICENSE, see emoncms repo:<br>
https://github.com/emoncms/emoncms/blob/master/LICENSE.txt
