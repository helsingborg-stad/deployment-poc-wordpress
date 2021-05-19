<?php

/*
Plugin Name:    Bugherd
Description:    User contributed bug reports script inclusion.
Version:        1.0
Author:         Sebastian Thulin
*/

add_action('wp_head', function() {
    if(is_user_logged_in()) {
        echo '<script type="text/javascript" src="https://www.bugherd.com/sidebarv2.js?apikey=grg9pi39r3vcmgc8xie9eg" async="true"></script>';
    }
});
