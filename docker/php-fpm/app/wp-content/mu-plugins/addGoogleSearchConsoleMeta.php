<?php

/*
Plugin Name: GSS Meta
Description: Adds google search console meta tag
Version:     1.0
Author:      Sebastian Thulin, Helsingborg Stad
*/

namespace GoogleSearchConsoleMeta;

class GoogleSearchConsoleMeta
{
    public function __construct()
    {
        add_filter('wp_head', array($this, 'addMetaTag'), 50);
    }

    public function addMetaTag($sitesItems = array())
    {
        echo '<meta name="google-site-verification" content="Mg4roQt1zi_-HRVTPiTKpXuc7HPxHJUZ9gzjMyNBhnM" />';
    }
}

new \GoogleSearchConsoleMeta\GoogleSearchConsoleMeta();
