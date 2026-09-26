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

load_js("Lib/js/vue.global.prod-3.5.22.min.js");
load_css("Modules/network/network_view.css");
?>

<div class="net-page<?php if ($mode == "setup") echo " net-blue"; ?>" data-bs-theme="dark">
<div class="net-inner" id="network-app" v-cloak>

    <div class="net-welcome" v-if="mode=='setup'">Welcome to your<b><span>emon</span>Pi</b></div>
    <div class="net-header" v-else><h2>Network</h2><p class="net-sub">Connections on this device</p></div>

    <div class="net-section"><h4>Network connections</h4></div>

    <div class="net-box"><div class="net-row">
        <span class="net-icon"><span class="svg-icon-link"></span></span>
        <span class="net-name">Ethernet</span>
        <span class="net-status" v-if="eth0.ip!='---'"><a :href="'http://'+eth0.ip" class="net-mono" target="_blank">{{ eth0.ip }}</a></span>
        <span class="net-status" v-else></span>
        <span class="net-tag" :class="{'net-tag-ok': eth0.ip!='---'}">{{ eth0.ip!='---' ? 'Connected' : 'Disconnected' }}</span>
    </div></div>

    <div class="net-box"><div class="net-row">
        <span class="net-icon"><span class="svg-icon-wifi"></span></span>
        <span class="net-name">WiFi</span>
        <span class="net-status" v-if="wlan0.ip!='---'"><b>{{ wlan0.ssid }}</b> <a :href="'http://'+wlan0.ip" class="net-mono" target="_blank">{{ wlan0.ip }}</a></span>
        <span class="net-status" v-else></span>
        <span class="net-tag" :class="{'net-tag-ok': wlan0.ip!='---'}">{{ wlan0.ip!='---' ? 'Connected' : 'Disconnected' }}</span>
    </div></div>

    <div class="net-box" v-if="mode=='network'"><div class="net-row">
        <span class="net-icon"><span class="svg-icon-phonelink_setup"></span></span>
        <span class="net-name">Hotspot</span>
        <span class="net-status" v-if="ap0.ip!='---'"><b>{{ ap0.ssid }}</b> <a :href="'http://'+ap0.ip" class="net-mono" target="_blank">{{ ap0.ip }}</a></span>
        <span class="net-status" v-else></span>
        <span class="net-tag" :class="{'net-tag-ok': ap_on}">{{ ap_on ? 'On' : 'Off' }}</span>
        <button class="net-btn" @click="stopAP" v-if="ap_on">Turn off</button>
        <button class="net-btn" @click="startAP" v-else>Turn on</button>
    </div></div>

    <template v-if="setup_stage==1">
        <div class="net-section"><h4>{{ mode=='setup' ? 'Next step' : 'WiFi' }}</h4></div>
        <div class="net-box" v-if="write"><div class="net-row is-link" @click="setup('client')">
            <span class="net-icon"><span class="svg-icon-wifi"></span></span>
            <span class="net-choice">{{ wlan0.ssid=='' ? 'Connect to WiFi network' : 'Change WiFi network' }}</span>
            <span class="svg-icon-arrow_forward net-arrow"></span>
        </div></div>
        <div class="net-box" v-if="mode=='setup' && eth0.ip!='---' && write"><div class="net-row is-link" @click="setup('ethernet')">
            <span class="net-icon"><span class="svg-icon-link"></span></span>
            <span class="net-choice">Continue on Ethernet</span>
            <span class="svg-icon-arrow_forward net-arrow"></span>
        </div></div>
        <div class="net-box" v-if="mode=='setup' && ap_on"><div class="net-row is-link" @click="continue_to_emoncms">
            <span class="net-icon"><span class="svg-icon-enter"></span></span>
            <span class="net-choice">Continue to Emoncms login</span>
            <span class="svg-icon-arrow_forward net-arrow"></span>
        </div></div>
    </template>

    <template v-if="setup_stage==2">
        <div class="net-section">
            <h4>WiFi networks</h4>
            <template v-if="wifi_client_mode=='list'">
                <button class="net-btn" @click="scan_for_networks"><span class="svg-icon-refresh-cw"></span> Scan</button>
                <button class="net-btn" @click="setup_stage=1">Close</button>
            </template>
        </div>

        <div class="net-box" v-if="wifi_client_mode=='scan'"><div class="net-progress">
            <div class="spinner-border" role="status"></div>
            Scanning for WiFi networks, this may take a few seconds
        </div></div>

        <template v-if="wifi_client_mode=='list'">
            <div class="net-box" v-for="network in available_networks" :class="{open: selected_SSID==network.SSID}">
                <div class="net-row is-link" @click="configure_client(network.SSID)">
                    <span class="wifi-signal" :class="'wifi-signal-'+network.level" :title="network.SIGNAL+'%'"><i></i><i></i><i></i><i></i></span>
                    <span class="net-ssid">{{ network.SSID }}</span>
                    <span class="net-tag net-tag-ok" v-if="wlan0.ip!='---' && wlan0.ssid==network.SSID">Connected</span>
                    <span class="net-tag net-tag-lock" v-if="network.SECURITY!=''">Secured</span>
                    <span class="net-signal">{{ network.SIGNAL }}%</span>
                    <span class="net-chevron" :class="selected_SSID==network.SSID ? 'svg-icon-chevron-up' : 'svg-icon-chevron-down'"></span>
                </div>
                <div class="net-form" v-if="selected_SSID==network.SSID">
                    <template v-if="network.SECURITY!=''">
                        <label>Password</label>
                        <input class="net-mono net-password" :type="show_password ? 'text' : 'password'" v-model="selected_password" @keyup.enter="connect">
                        <label><input type="checkbox" v-model="show_password"> Show</label>
                    </template>
                    <label v-else>Open network, no password needed</label>
                    <span class="net-spacer"></span>
                    <button class="net-btn" @click="selected_SSID=''">Cancel</button>
                    <button class="net-btn net-btn-primary" @click="connect">Connect</button>
                </div>
            </div>
        </template>

        <div class="net-box" v-if="wifi_client_mode=='connect'"><div class="net-progress">
            <div class="spinner-border" role="status"></div>
            <div>Connecting to <b>{{ selected_SSID }}</b></div>
            <div v-if="status_error">{{ status_error }}</div>
        </div></div>

        <div class="net-box" v-if="wifi_client_mode=='failed'"><div class="net-progress">
            <div>Could not connect to <b>{{ selected_SSID }}</b>. Check the password and try again.</div>
            <button class="net-btn" @click="wifi_client_mode='list'">Back to networks</button>
        </div></div>

        <div class="net-box" v-if="wifi_client_mode=='connected'"><div class="net-progress">
            <div>Connected to <b>{{ wlan0.ssid }}</b></div>
            <a :href="'http://'+wlan0.ip" class="net-ip net-mono">{{ wlan0.ip }}</a>
            <div v-if="mode=='setup'">Connect this computer to the same network, then open the address above.</div>
            <button class="net-btn" @click="scan_for_networks">Connect to a different network</button>
        </div></div>
    </template>

</div>
</div>

<script>

    var mode = "<?php echo $mode; ?>";
    var write = <?php echo $write?"true":"false"; ?>;
    
    // On first run call WiFi client scan after first status request
    // and restart wlan0 if inactive
    var first_run = true;
    
    var status_timeout_count = 0;

    // Connect gives up after this long without an IP address (network page only)
    var connect_timeout = 60000;
    var connect_timer = false;

    var app = Vue.createApp({
        data: function () {
            return {
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
            };
        },
        computed: {
            ap_on: function() {
                return this.ap0.state_description == 'Connected';
            }
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
                if (this.selected_SSID == SSID) {
                    this.selected_SSID = "";
                    return;
                }
                this.selected_SSID = SSID;
                this.selected_password = "";
                this.$nextTick(function() {
                    var input = document.querySelector("#network-app .net-password");
                    if (input) input.focus();
                });
            },
            connect: function() {
                this.wifi_client_mode = 'connect';
                this.show_log_button = true;
                this.status_error = "";

                clearTimeout(connect_timer);
                if (this.mode == "network") {
                    connect_timer = setTimeout(function() {
                        if (app.wifi_client_mode == 'connect') app.wifi_client_mode = 'failed';
                    }, connect_timeout);
                }
                
                $.ajax({
                    type: 'POST',
                    url: "network/connect-wlan0.json",
                    data: "ssid="+btoa(this.selected_SSID)+"&psk="+btoa(this.selected_password),
                    dataType: 'text',
                    async: true,
                    timeout: 5000,
                    success: function(result) {
                        setTimeout(function() {
                            setup_set_status("client",false);
                        }, 1000);
                    },
                    error: function() {
                    
                    }
                });
            },
        }
    }).mount('#network-app');
    
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

                    result[z].level = signal;
                }

                app.available_networks = result;
                app.selected_SSID = "";
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
                    clearTimeout(connect_timer);
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
