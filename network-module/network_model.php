<?php

class Network
{
    private $moduledir = "";

    public function __construct()
    {
        $this->log = new EmonLogger(__FILE__);
        
        global $settings;
        $this->moduledir = $settings["emoncms_dir"]."/modules/network/";
    }

    public function log()
    {
        $log_script = $this->moduledir.'scripts/nm_log.sh';
        if (file_exists($log_script)) {
            exec("sudo ".$log_script,$out);
            $result = "";
            foreach($out as $line) {
                $result .= $line."\n";
            }
            return $result;
        }
        return "Error: Cannot find ".$log_script;
    }

    public function scan()
    {   
        exec("sudo /opt/emoncms/modules/network/scripts/wifi_rescan.sh",$result);
        sleep(1);
        
        ob_start();
        passthru("nmcli --get-values ssid,mode,signal,security device wifi list ifname wlan0");
        $result = ob_get_clean();
        
        $networks = array();
        $lines = explode("\n",$result);
        foreach ($lines as $line) {
            $parts = explode(":",$line);
            
            if (count($parts)==4 && $parts[0]!="" && $parts[2]!="0") {
                $networks[] = array(
                    "SSID"=>$parts[0],
                    "MODE"=>$parts[1],
                    "SIGNAL"=>(int) $parts[2],
                    "SECURITY"=>$parts[3]
                );
            }
        }
        return $networks;
    }


    public function status2() {
        $status = array();
        foreach (array("eth0","wlan0","ap0") as $interface) {
    
            if (file_exists("/sys/class/net/$interface/carrier")) {
                $state = (int) file_get_contents("/sys/class/net/$interface/carrier");
                
                
                ob_start();
                passthru("ip addr show $interface | grep -Po 'inet \K[\d.]+'");
                $ip = ob_get_clean();   
                
                $status[$interface] = array(
                    "state_code" => $state,
                    "state_description" => $state?"Connected":"Disconnected",
                    "connection" => "",
                    "ip" => $ip,
                    "ssid" => "TEST"
                );
            }
        }
        return $status;
    }


    public function status()
    {
        $status = array();
        
        foreach (array("eth0","wlan0","ap0") as $interface) {
    
            if ($device = $this->get_device_status($interface)) {
                
                $ip = false;
                if (isset($device["IP4.ADDRESS[1]"])) {
                    $ip = explode("/",$device["IP4.ADDRESS[1]"]);
                    $ip = $ip[0];
                }
            
                // split GENERAL.STATE into state_code and state_description
                if (isset($device['GENERAL.STATE'])) {
                    $state = explode(" ",$device['GENERAL.STATE']);
                    $state_code = $state[0];
                    $state_description = $state[1];
                    // strip opening and closing brackets if present
                    $state_description = trim($state_description,"()");
                    // upper case first letter
                    $state_description = ucfirst($state_description);

                    $status[$device['GENERAL.DEVICE']] = array(
                        "state_code" => $state_code,
                        "state_description" => $state_description,
                        "connection" => $device['GENERAL.CONNECTION'],
                        "ip" => $ip,
                        "ssid" => $device['SSID']
                    );
                }
            }
        }
        
        return $status;
    }
    
    public function get_device_status($interface) {
        // Parse wlan0
        ob_start();
        passthru("nmcli -f GENERAL.DEVICE,IP4.ADDRESS,GENERAL.STATE,GENERAL.CONNECTION device show $interface");
        $result = ob_get_clean();
        $wlan0 = explode("\n",$result);
        
        if ($result == "Error: Device '$interface' not found.") return false;
        
        // Initialize an associative array to store the information
        $wlan0_info = [];

        // Loop through each line of the output
        foreach ($wlan0 as $line) {
            // Split the line into key and value
            $parts = explode(':', $line, 2);

            if (count($parts) === 2) {
                // Trim whitespace and assign the values to the associative array
                $key = trim($parts[0]);
                $value = trim($parts[1]);

                // Map the output to the associative array
                $wlan0_info[$key] = $value;
            }
        }

        $wlan0_info["SSID"] = "";
        if ($interface!="eth0") {
            // Get SSID
            ob_start();
            passthru("nmcli -t -f 802-11-wireless.ssid con show ".trim($wlan0_info['GENERAL.CONNECTION']));
            $result = ob_get_clean();
            // result is given as 802-11-wireless.ssid:SSID
            $parts = explode(":",$result);
            if (count($parts)==2) {
                $wlan0_info["SSID"] = trim($parts[1]);
            }
        }

        return $wlan0_info;
    }

    public function connect_wlan0($ssid, $psk)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,32}$/', $ssid)) {
            return "Invalid SSID";
        }
    
        $psk = hash_pbkdf2("sha1", $psk, $ssid, 4096, 64);

        $config = "[WiFi]\nssid=$ssid\npassword=$psk";

        if (!$file = fopen('/tmp/wifi-config.ini', 'w')) {
            $this->log->error("Could not write to /tmp/wifi-config.ini");
        }
        fwrite($file, $config);
        fclose($file);
        
        // Run via wrapper: 
        // nmcli dev wifi connect "$ssid" password "$password" ifname wlan0

        // exec("sudo /opt/emoncms/modules/network/scripts/wifi_connect.sh",$result);        
        // Remove ANSI escape sequences and carriage returns
        // $result = preg_replace('/[\x00-\x1F\x7F\x1B\[2K\r]/', '', $result[0]);
        // return $result;

        shell_exec('sudo /opt/emoncms/modules/network/scripts/wifi_connect.sh > /dev/null 2>&1 &');
        return true;
    }
    
    public function startAP() {
        shell_exec('sudo /opt/emoncms/modules/network/scripts/startAP.sh > /dev/null 2>&1 &');
        return true;
    }
    
    public function stopAP() {
        shell_exec('sudo /opt/emoncms/modules/network/scripts/stopAP.sh > /dev/null 2>&1 &');
        return true;
    }
}
