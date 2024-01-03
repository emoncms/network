<?php
/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function network_controller()
{
    global $settings, $session, $route, $redis;

    $route->format = "json";

    require "Modules/network/network_model.php";
    $network = new Network();

    $result = false;

    // Special setup access to WIFI function scan and setconfig
    $setup_access = false;
    if (isset($_SESSION['setup_access']) && $_SESSION['setup_access']) {
        $setup_access = true;
    }

    // ------------------------------------------------------------
    // Write level access
    // ------------------------------------------------------------
    if ($session["write"]) {
    
        if ($route->action=="") {
            $route->format = "html";
            return view("Modules/network/network_view.php",array());
            
        } elseif ($route->action=="ap0") {
            if (in_array($route->subaction,array("start","stop","restart"))) {
                return $network->service("ap0",$route->subaction);
            }
        } elseif ($route->action=="wlan0") {
            if (in_array($route->subaction,array("start","stop","restart"))) {
                return $network->service("wlan0",$route->subaction);       
            }
        }
    }

    // ------------------------------------------------------------
    // Read level access
    // ------------------------------------------------------------       
    if ($session["read"] || $setup_access) {
        if ($route->action=="status") {
            return $network->status();
        } elseif ($route->action=="info") {
            return $network->info();
        } elseif ($route->action=="getconfig") {
            return $network->getconfig();
        } elseif ($route->action=="log" && in_array($route->subaction,array("ap0","wlan0"))) {
            $route->format = "text";
            return $network->log($route->subaction);
        } elseif ($route->action=="scan") {
            if (file_exists($settings["emoncms_dir"]."/modules/network/scripts/wifi_scan.php")) {
                return cmd("wifi/scan",array());
            } else {
                return $network->scan();
            }
        }
    }
    
    if ($session["write"] || $setup_access) {
        if ($route->action=="setconfig") {
              $networks = urldecode(post('networks'));
              $country = "GB"; 
              if (isset($_POST['country'])) {
                $country = $_POST['country'];
            }
            return $network->setconfig(json_decode($networks),$country);
        }
    }

    return false;
}


function cmd($classmethod,$properties) {
    global $settings, $redis;

    if ($redis) {
        $redis->del($classmethod); // 1. remove last result

        $update_script = $settings["emoncms_dir"]."/modules/network/scripts/wifi_scan.sh";
        $update_logfile = $settings['log']['location']."/wifiscan.log";
        $redis->rpush("service-runner","$update_script>$update_logfile");

        $start = time(); // 3. wait for result
        while((time()-$start)<5.0) { 
            $result = $redis->get($classmethod);
            if ($result) {
                return json_decode($result);
            }
            usleep(100000); // check every 100ms
        }
    }
    return false;
}

