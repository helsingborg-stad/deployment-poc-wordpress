<?php

/*
Plugin Name:    New relic ignores
Description:    Ignore some scripts to be processed in new relic
Version:        1.0
Author:         Sebastian Thulin
*/

namespace NewRelicIgnore;

class NewRelicIgnore
{

    private $disallowedScripts = array("wp-cron.php");

    public function __construct()
    {

        // Do not run if extension is missing.
        if (!extension_loaded('newrelic')) {
            return;
        }

        //Check if functions exists for new relic actions
        if (!function_exists('newrelic_ignore_transaction')) {
            return;
        }

        if (!function_exists('newrelic_ignore_apdex')) {
            return;
        }

        //Ok, run ignore transactions
        $this->ignoreTransaction($_SERVER["SCRIPT_FILENAME"]);
    }

    public function ignoreTransaction($scriptName)
    {
        if (in_array($scriptName, $this->disallowedScripts)) {
            newrelic_ignore_transaction(true);
            newrelic_ignore_apdex(true);
        }
    }
}

new \NewRelicIgnore\NewRelicIgnore();
