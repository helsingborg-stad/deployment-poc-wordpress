<?php

/*
Plugin Name: Additional site redirects
Description: Add staticly eneterd sites to redirect site array
Version:     1.0
Author:      Sebastian Thulin, Helsingborg Stad
*/

namespace AdditionalSiteRedirect;

class AdditionalSiteRedirect
{
    public function __construct()
    {
        add_filter('siteRedirect', array($this, 'addMoreSites'), 50);
    }

    public function addMoreSites($sitesItems = array())
    {
        foreach (array(
                "foretagare.helsingborg.se",
                "drottningh.helsingborg.se"
            ) as $site) {

            $sitesItems[] = (object) array(
                'domain' => $site,
                'url' => 'http://' . $site
            );
        }

        return (array) $sitesItems;

    }
}

new \AdditionalSiteRedirect\AdditionalSiteRedirect();
