<?php global $path; ?>
<?php

// Load country list
$countries = array();
if (file_exists("/usr/share/zoneinfo/iso3166.tab")) {
    foreach (array_filter(file("/usr/share/zoneinfo/iso3166.tab")) as $line) {
        if ($line[0] != "#") {
            list($code, $name) = explode("\t", $line);
            $countries[$code] = $name;
        }
    }
    asort($countries);
}
?>

<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<style>
    .welcome {
        margin-top: 20px;
        font-size: 24px;
        line-height: 52px;
        color: #fff;
    }

    .welcome2 {
        font-weight: bold;
        font-size: 52px;
        color: #fff;
        margin-top:10px;
        margin-bottom:20px;
    }

    .setupbox {
        color: #fff;
        font-size: 18px;
        padding: 20px;
        border: 1px #fff solid;
        border-bottom: 0;
        cursor: pointer;
    }

    .setupbox:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .setupbox:last-child {
        border-bottom: 1px #fff solid;
    }

    .client-progress {
        padding-top: 50px;
        padding-bottom: 20px;
        min-height: 100px;
        text-align: center;
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px #fff solid;
    }


    .wifi-client-list {
        padding: 10px;
        border: 1px #fff solid;
        border-bottom: 0;
        cursor: pointer;
    }

    .wifi-client-list:last-child {
        border-bottom: 1px #fff solid;
    }

    .wifi-client-list:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .iconwifi {
        width: 18px;
        margin-top: -3px;
        padding-right: 10px;
    }

    .auth-showpass {
        margin-bottom: 10px;
    }

    .box-border {
        padding: 10px;
        border: 1px #fff solid;
        background-color: rgba(255, 255, 255, 0.1);
        margin-bottom:10px;
    }

    .network-box {
        margin-bottom: 20px;
    }

    .rescan {
        font-size: 14px;
        color: #D1E8F1;
        cursor: pointer;
    }

    .rescan:hover {
        color: #fff;
    }

    .log {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 10px;
    }

    .ip-link {
        color: #D1E8F1;
    }

    .ip-link:hover {
        color: #fff;
    }

    .iface-heading {
        font-size: 18px;
        color: #fff;
        margin-top: 10px;
        margin-bottom: 10px;
        font-weight: bold;
        display:inline-block;
        width:100px;
    }
    .iface-status {
        font-weight: normal;
        font-size: 16px;
    }
    
    
    .content-container { max-width:800px; }
    

    
</style>
<div style="color:#fff">
    <div id="network-app">
    
        <div v-if="mode=='setup'">
            <div class="welcome">Welcome to your</div>
            <div class="welcome2"><span style="color:#c8e9f6">emon</span><span>Pi</span></div>

            <div style="clear:both; height:20px"></div>
        </div>

        <h3 v-if="mode=='network'">Network</h3>
        <p style="font-size:18px">Network Connections:</p>

        <div class="box-border">
          <span class="iface-heading">Ethernet:</span> 
          <span class="iface-status" v-if="eth0.ip!='---'">(<a :href="'http://'+eth0.ip" class="ip-link" target="_blank">{{ eth0.ip }}</a>)</span>
          <span class="iface-status" v-if="eth0.ip=='---'">Disconnected</span>
        </div>

        <div class="box-border">
          <span class="iface-heading">WiFi:</span>
          <span class="iface-status" v-if="wlan0.ip!='---'">{{ wlan0.ssid }} (<a :href="'http://'+wlan0.ip" class="ip-link" target="_blank">{{ wlan0.ip }}</a>)</span>
          <span class="iface-status" v-if="wlan0.ip=='---'">Disconnected</span>      
        </div>
        
        <div class="box-border" v-if="mode=='network'">
            <div class="btn-group" style="float:right">
                <button class="btn" style="margin-top:5px" @click="startAP" v-if="ap0.state_description!='Connected'">Enable</button>
                <button class="btn" style="margin-top:5px" @click="stopAP" v-if="ap0.state_description=='Connected'">Disable</button>
            </div>
            <span class="iface-heading">Hotspot:</span> 
            <span class="iface-status" v-if="ap0.ip!='---'">{{ ap0.ssid }} (<a :href="'http://'+ap0.ip" class="ip-link" target="_blank">{{ ap0.ip }}</a>)</span>
            <span class="iface-status" v-if="ap0.ip=='---'">Disconnected</span>      
        </div>

        <div class="network-box" v-if="setup_stage==2" style="margin-top:20px">
            <button class="btn" style="float:right; margin-top:-5px" @click="scan_for_networks" v-if="wifi_client_mode=='list'">Scan</button>
            <div class="client-progress" v-if="wifi_client_mode=='scan'">Scanning for WiFi networks, this may take a few seconds..<br><br><img src="<?php echo $path; ?>Modules/network/icons/ajax-loader.gif" loop=infinite></div>

            <div v-if="wifi_client_mode=='list'">

                <p style="font-size:18px">Available WiFi networks:</p>
                <div class="wifi-client-list" v-for="network in available_networks" @click="configure_client(network.SSID)"><img class="iconwifi" :src="'<?php echo $path; ?>Modules/network/icons/light/'+network.icon+'.png'" :title="network.SIGNAL+'%'">{{ network.SSID }}</div>
            </div>

            <div class="box-border" v-if="wifi_client_mode=='config'">
                <h4>Authentication required</h4>
                <p>Passwords or encryption keys are required to access WiFi network: <b>{{ selected_SSID }}</b></p>
                <p>Password:</p>
                <input v-model="selected_password" :type="show_password?'text':'password'" style="height:auto">
                <div class="auth-showpass"><input type="checkbox" v-model="show_password" style="margin-top:-3px"> Show password</div>
                
                <button class="btn" @click="wifi_client_mode='list'">Cancel</button> <button class="btn" @click="connect">Connect</button>
            </div>

            <div v-if="wifi_client_mode=='connect'" class="client-progress">
                Connecting to <b>{{ selected_SSID }}</b><br><br><img src="<?php echo $path; ?>Modules/network/icons/ajax-loader.gif" loop=infinite>
                <div v-if="status_error" style="margin-top:10px" v-html="status_error"></div>
            </div>

            <div v-if="wifi_client_mode=='connected'" class="client-progress">
                <p>Connected to <b>{{ wlan0.ssid }}</b></p>
                <p><a :href="'http://'+wlan0.ip" class="ip-link" style="font-size:22px">{{ wlan0.ip }}</a></p>
                <p @click="scan_for_networks" class="rescan">Connect to a different network</p>
            </div>

        </div>

        <!--
        <div class="network-box" v-if="show_log_button && (mode=='network' || setup_stage==2)">
            <div style="margin-bottom:10px">
                <button class="btn" v-if="!show_log" @click="show_log=true">Show network log</button>
                <button class="btn" v-if="show_log" @click="show_log=false">Hide network log</button>
            </div>
            <pre v-if="show_log" class="log">{{ log }}</pre>
        </div>
        -->
        
        <div v-if="setup_stage==1" style="margin-top:20px">
            <!--<p style="font-size:18px" v-if="write"><b>Network Configuration:</b> Would you like to:</p>-->
            <div class="setupbox" @click="setup('client')" v-if="write">
              <span v-if="wlan0.ssid==''">Connect to WiFi network</span>
              <span v-if="wlan0.ssid!=''">Change WiFi network</span>
            </div>
            <div class="setupbox" @click="setup('ethernet')" v-if="mode=='setup' && eth0.ip!='---' && write">Continue on Ethernet</div>
            <div class="setupbox" @click="continue_to_emoncms" v-if="mode=='setup' && ap0.state_description=='Connected'">Continue to Emoncms login</div>
        </div>

    </div>
</div>

<script>

    $("body").css("background-color", "#1d8dbc");
    
    var mode = "<?php echo $mode; ?>";
    var write = <?php echo $write?"true":"false"; ?>;
    
    // On first run call WiFi client scan after first status request
    // and restart wlan0 if inactive
    var first_run = true;
    
    var status_timeout_count = 0;

    var app = new Vue({
        el: '#network-app',
        data: {
            mode: mode,
            write: write,
            setup_stage: 1,
            eth0: {
                ip: "---"
            },
            wlan0: {
                ssid: "",
                ip: "---",
                state_description: ""
            },
            ap0: {
                ssid: "emonPi",
                ip: "---",
                state_description: ""
            },

            wifi_client_mode: 'scan',
            available_networks: [],
            show_password: true,

            selected_SSID: "",
            selected_password: "",
            selected_country: "GB",

            countries: <?php echo json_encode($countries); ?>,
            
            log: "",
            show_log: false,
            show_log_button: true,
            status_error: ""

        },
        methods: {
            startAP: function() {
                $.ajax({
                    type: 'GET',
                    url: "network/startAP",
                    dataType: 'text',
                    async: true,
                    success: function(result) {
                        update_status();
                    }
                });
            },
            stopAP: function() {
                $.ajax({
                    type: 'GET',
                    url: "network/stopAP",
                    dataType: 'text',
                    async: true,
                    success: function(result) {
                        update_status();
                    }
                });
            },
            setup: function(setup_mode) {
                if (setup_mode=="ethernet") {
                    setup_set_status(setup_mode,true);
                } else {
                    if (setup_mode=="client") {
                        app.setup_stage = 2;
                        app.show_log_button = false;
                    }
                }
            },
            continue_to_emoncms: function () {
                window.location = path+"user/login";
            },
            scan_for_networks: function() {
                this.wifi_client_mode = 'scan';
                scan();
            },
            configure_client: function(SSID) {
                this.wifi_client_mode = 'config';
                this.selected_SSID = SSID
            },
            connect: function() {
                this.wifi_client_mode = 'connect';
                this.show_log_button = true;
                
                setup_set_status("client",false);

                $.ajax({
                    type: 'POST',
                    url: "network/connect-wlan0.json",
                    data: "ssid="+encodeURIComponent(this.selected_SSID)+"&psk="+encodeURIComponent(this.selected_password),
                    dataType: 'text',
                    async: true,
                    timeout: 5000,
                    success: function(result) {
                        
                    },
                    error: function() {
                    
                    }
                });
            },

            show_log: function() {
                // update_log();
            }
        }
    });
    
    function setup_set_status(setup_mode,redirect=false) {
        $.ajax({
            url: path + "setup/set_status?mode="+setup_mode,
            dataType: "text",
            timeout: 500,
            success: function(result) {
                if (redirect) {
                    window.location = path+"user/login";
                }
            }
        });
    }
    
    function scan() {
        $.ajax({
            url: path + "network/scan",
            dataType: "json",
            timeout: 5000,
            success: function(result) {
                if (result=="loading") {
                    setTimeout(scan,2500);
                    return false;
                }
                if (!result) return false;
                
                for (var z in result) {

                    var signal = 0;
                    if (result[z]["SIGNAL"] > 20) signal = 1;
                    if (result[z]["SIGNAL"] > 40) signal = 2;
                    if (result[z]["SIGNAL"] > 60) signal = 3;
                    if (result[z]["SIGNAL"] > 80) signal = 4;

                    var secure = "secure";
                    if (result[z]["SECURITY"] == "") secure = "";

                    result[z].icon = "wifi" + signal + secure;
                }

                app.available_networks = result;
                app.wifi_client_mode = 'list'
            },
            error: function() {
                alert("scan timeout")
            }
        });
    }
    
    function update_status() {
        $.ajax({
            url: path + "network/status",
            dataType: "json",
            timeout:4000,
            success: function(result) {
                status_timeout_count = 0;
                
                if (result=="loading") {
                    setTimeout(update_status,500);
                    return false;
                }
                if (!result) return false;
                
                var interfaces = ["eth0","wlan0","ap0"];
                for (var z in interfaces) {
                    let iface = interfaces[z];
                
                    app[iface].state_code = "";
                    app[iface].state_description = "";
                    app[iface].ssid = "";
                    app[iface].ip = "---"; 
            
                    if (result[iface] != undefined) {
                        if (result[iface].state_code!=undefined) {
                            app[iface].state_code = result[iface].state_code
                        }
                        if (result[iface].state_description!=undefined) {
                            app[iface].state_description = result[iface].state_description
                        }
                        if (result[iface].ssid!=undefined) {
                            app[iface].ssid = result[iface].ssid
                        }
                        if (result[iface].ip!=undefined && result[iface].ip) {
                            app[iface].ip = result[iface].ip
                        }
                    }
                }

                if (app.wifi_client_mode == "connect" && app.wlan0.ip && app.wlan0.ip != "---") {
                    app.wifi_client_mode = 'connected';
                }
                
                // First run
                if (first_run) {
                    first_run = false;
                    scan();
                }
            },
            error: function () {
                status_timeout_count++;
                app.status_error = "Reconnect to access point to see IP address"
            }
        });
    }



    update_status();
    setInterval(function() {
        update_status();
    }, 5000);

    /*
    function update_log() {
        $.ajax({
            url: path + "network/log",
            dataType: "text",
            success: function(result) {
                app.log = result;
            }
        });
    }
    update_log();
    setInterval(function() {
        update_log();
    }, 5000);*/
</script>
