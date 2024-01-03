<?php
require "/var/www/emoncms/Lib/load_emoncms.php";
require "Modules/network/network_model.php";
$network = new Network();
$redis->set("wifi/scan",json_encode($network->scan()));
