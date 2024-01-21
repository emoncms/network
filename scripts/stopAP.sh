#!/bin/bash
sudo nmcli connection down Hotspot
sudo iw dev ap0 del
