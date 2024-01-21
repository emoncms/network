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
    if ($session["write"] || $setup_access) {
        if ($route->action=="") {
            $route->format = "html";
            return view("Modules/network/network_view.php",array("mode"=>"network")); 
        }
    }

    // ------------------------------------------------------------
    // Read level access
    // ------------------------------------------------------------       
    if ($session["read"] || $setup_access) {
        if ($route->action=="status") {
            return $network->status();
        } elseif ($route->action=="log" && in_array($route->subaction,array("ap0","wlan0"))) {
            $route->format = "text";
            // return $network->log($route->subaction);
        } elseif ($route->action=="scan") {
            $route->format = "json";
            return $network->scan();
        }
    }
    
    if ($session["write"] || $setup_access) {
        if ($route->action=="setconfig") {
            $route->format = "text";
            $ssid = prop("ssid",true);
            $psk = prop("psk",true);
            return $network->connect_wlan0($ssid, $psk);
        }
    }

    return false;
}
