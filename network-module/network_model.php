<?php

class Network
{
    public function __construct()
    {
        $this->log = new EmonLogger(__FILE__);
    }

    public function log($winterface)
    {
        global $settings;

        if (file_exists($settings["emoncms_dir"].'/modules/network/scripts/log_'.$winterface.'.sh')) {
            exec("sudo ".$settings["emoncms_dir"].'/modules/network/scripts/log_'.$winterface.'.sh',$out);
            $result = "";
            foreach($out as $line) {
                $result .= $line."\n";
            }
            return $result;
        }
        return "Error: Cannot find ".$settings["emoncms_dir"].'/modules/network/scripts/log_'.$winterface.'.sh';
    }

    public function scan()
    {
        ob_start();
        passthru("sudo /bin/nmcli device wifi rescan ifname wlan0");
        $result = ob_get_clean();
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
                
                // nmcli -t -f active,ssid dev wifi
                
                $ssid = "";
                if ($interface=="wlan0") {
                    $ssid = "OpenEnergyMonitor";
                }
            
                $status[$device['GENERAL.DEVICE']] = array(
                    "state" => $device['GENERAL.STATE'],
                    "ip" => $ip,
                    "ssid" => $ssid
                );
            }
        }
        
        return $status;
    }
    
    public function get_device_status($interface) {
        // Parse wlan0
        ob_start();
        passthru("nmcli -f GENERAL.DEVICE,IP4.ADDRESS,GENERAL.STATE device show $interface");
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

        exec("sudo /opt/emoncms/modules/network/scripts/wifi_connect.sh",$result);        
        // Remove ANSI escape sequences and carriage returns
        $result = preg_replace('/[\x00-\x1F\x7F\x1B\[2K\r]/', '', $result[0]);
        return $result;
    }
}
