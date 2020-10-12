<?php

/*
Plugin Name:    Composer WordPress Network URL
Description:    Fixes issues with network-admin url when using wp-composer setup.
Version:        1.1
Author:         Sebastian Thulin
*/

namespace ComposerNetworkAdmin;

class ComposerNetworkAdmin
{
    public function __construct()
    {
        add_filter('network_admin_url', array($this, 'sanitizeNetworkAdminUrl'), 50, 2);
        add_filter('admin_url', array($this, 'sanitizeAdminUrl'), 50, 3);
    }

    public function sanitizeAdminUrl($url, $path, $blog_id)
    {
        if (strpos($url, '/wp/wp-admin') === false && !strpos($url, '/network')) {
            return str_replace('/wp-admin/', '/wp/wp-admin/', $url);
        }
        return $url;
    }

    public function sanitizeNetworkAdminUrl($url, $path)
    {
        if (strpos($url, '/wp/wp-admin/network') === false && strpos($url, '/network')) {
            return str_replace('/wp-admin/', '/wp/wp-admin/', $url);
        }
        return $url;
    }
}

new \ComposerNetworkAdmin\ComposerNetworkAdmin();
