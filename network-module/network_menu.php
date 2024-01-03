<?php
global $session;
if ($session["write"]) {
    $menu["setup"]["l2"]['network'] = array(
        "name"=>_("Network"),
        "href"=>"network", 
        "order"=>11, 
        "icon"=>"wifi"
    );
}
