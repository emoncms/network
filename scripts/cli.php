<?php
// Lock file
$fp = fopen("/tmp/network-runlock", "w");
if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

// Load emoncms 
require "/var/www/emoncms/Lib/load_emoncms.php";

require "Modules/network/network_model.php";
$network = new Network();

if ($argv[1] == "status") {
    //$status = $redis->get("network:status");
    //if (!$status) {
        $status = $network->status();
        $redis->set("network:status",json_encode($status));
        $redis->expire("network:status",10);
    //}
    
} else if ($argv[1] == "scan") {
    //$status = $redis->get("network:scan");
    //if (!$status) {
        $scan = $network->scan();
        $redis->set("network:scan",json_encode($scan));
        $redis->expire("network:scan",10);
    //}
}
