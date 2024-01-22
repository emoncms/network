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
        margin-top: 40px;
        font-size: 32px;
        line-height: 52px;
        color: #fff;
    }

    .welcome2 {
        font-weight: bold;
        font-size: 52px;
        line-height: 52px;
        color: #fff;
        padding-bottom: 10px;
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
        height: 100px;
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
        font-size: 22px;
        color: #D1E8F1;
    }

    .ip-link:hover {
        font-size: 22px;
        color: #fff;
    }

    .iface-heading {
        font-size: 18px;
        color: #fff;
        margin-top: 10px;
        margin-bottom: 10px;
        font-weight: bold;
    }
    .iface-status {
        font-weight: normal;
        font-size: 16px;
    }

    
</style>
<div style="color:#fff">
    <div id="network-app">
    
        <div v-if="mode=='setup'">
            <div class="welcome">Welcome to your</div>
            <div class="welcome2"><span style="color:#c8e9f6">emon</span><span>Pi</span></div>
            <p style="font-size:18px">This is a quick setup wizard to get you started.</p>
            <div style="clear:both; height:20px"></div>

            <div v-if="setup_stage==1">
                <p style="font-size:18px"><b>Network Configuration:</b> Would you like to:</p>
                <div class="setupbox" @click="setup('ethernet')" v-if="eth0.ip">Continue on Ethernet</div>
                <div class="setupbox" @click="setup('standalone')" v-if="ap0.service!='inactive'">Continue in stand-alone WiFi Access Point mode</div>
                <div class="setupbox" @click="setup('client')" v-if="wlan0.service!='inactive'">Connect to WiFi network</div>
            </div>
        </div>

        <h3 v-if="mode=='network'">Network</h3>
    
        <div class="row-fluid" style="margin-bottom:20px" v-if="mode=='network'">
            <div class="span4 box-border" style="height:120px">
            <div class="iface-heading">Ethernet <span class="iface-status">({{ eth0.state_description }})</span></div>
                <p><b>IP Address:</b> {{ eth0.ip }}</p>
            </div>
            <div class="span4 box-border">
                <div class="btn-group" style="float:right">
                    <button class="btn" @click="startAP" v-if="ap0.state_description!='Connected'">Enable</button>
                    <button class="btn" @click="stopAP" v-if="ap0.state_description=='Connected'">Disable</button>

                </div>
                <div class="iface-heading">WiFi Hotspot <span class="iface-status">({{ ap0.state_description }})</span></div>
                <p><b>SSID:</b> {{ ap0.ssid }}</p>
                <p><b>IP Address:</b> {{ ap0.ip }}</p>

            </div>

            <div class="span4 box-border">
                <div class="iface-heading">WiFi Client <span class="iface-status">({{ wlan0.state_description }})</span></div>
                <p><b>SSID:</b> {{ wlan0.ssid }}</p>
                <p><b>IP Address:</b> {{ wlan0.ip }}</p>
            </div>
        </div>

        <div class="network-box" v-if="mode=='network' || setup_stage==2">
            <button class="btn" style="float:right; margin-top:-5px" @click="scan_for_networks" v-if="wifi_client_mode=='list'">Scan</button>
            <div class="client-progress" v-if="wifi_client_mode=='scan'">Scanning for WiFi networks, this may take a few seconds..<br><br><img src="<?php echo $path; ?>Modules/network/icons/ajax-loader.gif" loop=infinite></div>

            <div v-if="wifi_client_mode=='list'">

                <h4>Available WiFi networks</h4>
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
            </div>

            <div v-if="wifi_client_mode=='connected'" class="client-progress">
                <p>Connected to <b>{{ wlan0.ssid }}</b></p>
                <p><a :href="'http://'+wlan0.ip" class="ip-link">{{ wlan0.ip }}</a></p>
                <p @click="scan_for_networks" class="rescan">Connect to a different network</p>
            </div>

        </div>

        <div class="network-box" v-if="show_log_button && (mode=='network' || setup_stage==2)">
            <div style="margin-bottom:10px">
                <button class="btn" v-if="!show_log" @click="show_log=true">Show network log</button>
                <button class="btn" v-if="show_log" @click="show_log=false">Hide network log</button>
            </div>
            <pre v-if="show_log" class="log">{{ log }}</pre>
        </div>

    </div>
</div>

<script>

    $("body").css("background-color", "#1d8dbc");
    
    var mode = "<?php echo $mode; ?>";
    
    // On first run call WiFi client scan after first status request
    // and restart wlan0 if inactive
    var first_run = true;

    var app = new Vue({
        el: '#network-app',
        data: {
            mode: mode,
            setup_stage: 1,
            eth0: {

            },
            wlan0: {
                ssid: "",
                ip: ""
            },
            ap0: {
                ssid: "emonPi",
                ip: ""
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
            show_log_button: true

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
                if (setup_mode=="ethernet" || setup_mode=="standalone") {
                    setup_set_status(setup_mode,true);
                } else {
                    if (setup_mode=="client") {
                        app.setup_stage = 2;
                        app.show_log_button = false;
                    }
                }
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
                
                if (app.mode=="setup") setup_set_status("client",false);

                $.ajax({
                    type: 'POST',
                    url: "network/connect-wlan0.json",
                    data: "ssid="+encodeURIComponent(this.selected_SSID)+"&psk="+encodeURIComponent(this.selected_password),
                    dataType: 'text',
                    async: true,
                    success: function(result) {
                    
                    }
                });
            },

            show_log: function() {
                update_log();
            }
        }
    });
    
    function setup_set_status(setup_mode,redirect=false) {
        $.ajax({
            url: path + "setup/set_status?mode="+setup_mode,
            dataType: "text",
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
            success: function(result) {

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
            }
        });
    }

    function update_status() {
        $.ajax({
            url: path + "network/status",
            dataType: "json",
            success: function(result) {
                
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

                if (app.wifi_client_mode == "connect" && app.wlan0.ip) {
                    app.wifi_client_mode = 'connected';
                }
                
                // First run
                if (first_run) {
                    first_run = false;
                    scan();
                }          
               
            }
        });
    }



    update_status();
    setInterval(function() {
        update_status();
    }, 5000);

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
    }, 5000);
</script>
