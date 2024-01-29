#!/bin/bash

# uninstall wpa_supplicant ap0 implementation
sudo rm /etc/systemd/network/04-eth0.network
sudo rm /etc/systemd/network/08-wlan0.network
sudo rm /etc/systemd/network/12-ap0.network
sudo rm /etc/wpa_supplicant/wpa_supplicant-ap0.conf

sudo systemctl disable --now wpa_supplicant@ap0.service
sudo systemctl disable --now wpa_supplicant@wlan0.service
sudo systemctl mask wpa_supplicant@ap0.service
sudo systemctl mask wpa_supplicant@wlan0.service

sudo rm /etc/sudoers.d/wifi-sudoers
sudo systemctl daemon-reload

