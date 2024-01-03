<?php global $path; ?>
<?php 

// Load country list
$countries = array();
if (file_exists("/usr/share/zoneinfo/iso3166.tab")) {
    foreach (array_filter(file("/usr/share/zoneinfo/iso3166.tab")) as $line ) {
        if($line[0]!="#"){
            list($code,$name) = explode("\t", $line);
            $countries[$code] = $name;
        }
    }
    asort($countries);
}
?>

<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<style>

    #networks-scanning {
        padding-top:50px;
        padding-bottom:20px;
        height:100px;
        text-align:center;
    }

    .network-item {
        padding: 10px;
        border: 1px #666 solid;
        border-bottom: 0;
        cursor: pointer;
    }

    .network-item:last-child {
        border-bottom: 1px #666 solid;
    }

    .network-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .iconwifi {
        width: 18px;
        margin-top: -3px;
        padding-right: 10px;
    }
    
    .auth-heading {
        font-weight:bold;
        font-size:18px;
        line-height:25px;
    }
    
    .auth-message { margin-bottom:10px; }
    .auth-showpass { margin-bottom:10px; }
    #wifi-password { width:260px }
    
    
    .network-box {
        padding:10px; 
        margin-bottom:10px;
        /*border: 1px solid #aaa;*/
        background-color:#f0f0f0;
    }
</style>
<div>
<h3>Network</h3>

<div id="network-app">
    <div class="row-fluid">
        <div class="span4 network-box" style="height:120px">
            <h4>Ethernet</h4>
            <p><b>IP Address:</b> {{ eth0.ip }}</p>
        </div>
        <div class="span4 network-box">
            <div class="btn-group" style="float:right">
                <button class="btn btn-success" @click="service('ap0','start')" v-if="ap0.service=='inactive'">Start</button>
                <button class="btn btn-danger" @click="service('ap0','stop')" v-if="ap0.service!='inactive'">Stop</button> 
                <button class="btn btn-warning" @click="service('ap0','restart')" v-if="ap0.service!='inactive'">Restart</button>
            </div>
            <h4>WiFi Access Point</h4>
            <p><b>SSID:</b> {{ ap0.ssid }}</p>
            <p><b>IP Address:</b> {{ ap0.ip }}</p>

        </div>

        <div class="span4 network-box">
           <div class="btn-group" style="float:right">
                <button class="btn btn-success" @click="service('wlan0','start')" v-if="wlan0.service=='inactive'">Start</button>
                <button class="btn btn-danger" @click="service('wlan0','stop')" v-if="wlan0.service!='inactive'">Stop</button> 
                <button class="btn btn-warning" @click="service('wlan0','restart')" v-if="wlan0.service!='inactive'">Restart</button>
            </div>
            <h4>WiFi Client</h4>
            <p><b>SSID:</b> {{ wlan0.ssid }}</p>
            <p><b>IP Address:</b> {{ wlan0.ip }}</p>
        </div>
    </div>

    <div class="network-box">
        <button class="btn btn-info" style="float:right" @click="scan_for_networks" v-if="!show_configure_client && !show_scanning">Scan</button>
        <div id="networks-scanning" v-if="show_scanning">Scanning for WiFi networks, this may take a few seconds..<br><br><img src="<?php echo $path; ?>Modules/network/icons/ajax-loader.gif" loop=infinite></div>
        <div v-if="!show_configure_client && !show_scanning">

          <h4>Available WiFi networks</h4>
          <div class="network-item" v-for="(network,SSID) in available_networks" @click="configure_client(SSID)"><img class="iconwifi" :src="'<?php echo $path; ?>Modules/network/icons/dark/'+network.icon+'.png'" :title="network.SIGNAL+' dbm'">{{ SSID }}</div>
        </div>
        
        <div id="network-authentication" v-if="show_configure_client">
            <div class="auth-heading">Authentication required</div>
            <div class="auth-message">Passwords or encryption keys are required to access WiFi network:<br><b>{{ selected_SSID }}</b></div>
            Password:<br>
            <input v-model="selected_password" :type="password_mode" style="height:auto">
            <div class="auth-showpass"><input type="checkbox" style="margin-top:-3px" @click="toggle_password_visibility"> Show password</div>
            
            <div class="auth-message">WiFi country:<br>
            <select v-model="selected_country">
                <option v-for="(country,code) in countries" :value="code">{{ country }}</option>
            </select>
            </div>
            
            <button class="btn" @click="show_configure_client=false">Cancel</button> <button class="btn" @click="connect">Connect</button>
        </div>

    </div>
    
    <div class="network-box">
        <div class="btn-group" style="float:right">
            <button class="btn" :class="{ 'btn-info' : log_interface=='ap0' }" @click="show_log('ap0')">Access Point</button>
            <button class="btn" :class="{ 'btn-info' : log_interface=='wlan0' }" @click="show_log('wlan0')">Client</button>
        </div>
        <h4>Network Log</h4>
        <pre>{{ log }}</pre>
    </div>

</div>
</div>

<script>
    //$("body").css("background-color","#1d8dbc");

    var app = new Vue({
        el: '#network-app',
        data: {
            eth0: {
            
            },
            wlan0: {
                service: "",
                ssid: "",
                ip: ""
            },
            ap0: {
                service: "",
                ssid: "emonPi",
                ip: ""
            },
            
            show_scanning: true,
            available_networks: [],
            show_configure_client: false,
            password_mode: "password",
            
            selected_SSID: "",
            selected_password: "",
            selected_country: "GB",
            
            countries: <?php echo json_encode($countries); ?>,
            
            log_interface: 'wlan0',
            log: ""

        },
        methods: {
            scan_for_networks: function () {
                app.show_scanning = true;
                scan();
            },
            configure_client: function (SSID) {
                this.show_configure_client = true;
                this.selected_SSID = SSID
            },
            toggle_password_visibility: function() {
                if (this.password_mode=="password") {
                    this.password_mode = "text";
                } else {
                    this.password_mode="password";
                }
            },
            connect: function() {
                
                var networks_to_save = {};
                networks_to_save[this.selected_SSID] = {};
                networks_to_save[this.selected_SSID]["PSK"] = this.selected_password
                
                $.ajax({
                    type: 'POST', 
                    url: "network/setconfig.json", 
                    data: "networks="+encodeURIComponent(JSON.stringify(networks_to_save))+"&country="+this.selected_country, 
                    dataType: 'text', 
                    async: true,
                    success: function(result) {
                        alert("Connecting to WiFi client "+app.selected_SSID)
                        app.show_configure_client = false;
                    }
                });
            },
            
            service: function(winterface, action) {
                $.ajax({
                    url: path + "network/"+winterface+"/"+action,
                    dataType: "json",
                    success: function(result) {
                        
                    }
                });          
            },
            
            show_log: function(winterface) {
                this.log_interface = winterface
                update_log();
            }
        }
    });

    function scan() {
        $.ajax({
            url: path + "network/scan",
            dataType: "json",
            success: function(result) {
            
                for (var z in result) {
                
                    var signal = 0;
                    if (result[z]["SIGNAL"]>-100) signal = 1;
                    if (result[z]["SIGNAL"]>-85) signal = 2;
                    if (result[z]["SIGNAL"]>-70) signal = 3;
                    if (result[z]["SIGNAL"]>-60) signal = 4;

                    var secure = "secure";
                    if (result[z]["SECURITY"]=="ESS") secure = "";  
                
                    result[z].icon = "wifi"+signal+secure;
                }
            
                app.available_networks = result;
                app.show_scanning = false;
            }
        });
    }

    function update_status() {
        $.ajax({
            url: path + "network/status",
            dataType: "json",
            success: function(result) {
            
                app.eth0.ip = result.eth0.IPAddress;
            
                app.wlan0.service = result.wlan0.service;
                app.ap0.service = result.ap0.service;
                
                if (result.wlan0.service == "active") {
                    app.wlan0.service = result.wlan0.service;
                    app.wlan0.ssid = result.wlan0.SSID;
                    app.wlan0.ip = result.wlan0.IPAddress;
                } else {
                    app.wlan0.ssid = "";
                    app.wlan0.ip = "---";
                }

                if (result.ap0.service == "active") {
                    app.ap0.ip = result.ap0.IPAddress;
                } else {
                    app.ap0.ip = "---";
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
            url: path + "network/log/"+app.log_interface,
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


    scan();




</script>
