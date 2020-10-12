<?php

    $json = json_decode(file_get_contents('wp_blogs.json'));

    foreach ($json as $key => $site) {
        if (in_array($site->path, array("/", "/foretagare/"))) {
            continue;
        }

        if (isset($_GET['links'])) {
            echo '<a href="http://'. trim($site->path, "/") . str_replace("www", "", $site->domain) .'">' . trim($site->path, "/") . str_replace("www", "", $site->domain). '</a><br/>';
        } else {
            if (isset($_GET['mapping'])) {
                echo ' 139.162.153.238 ';
            }

            if (isset($_GET['separator'])) {
                echo ';';
            }

            echo trim($site->path, "/") . str_replace("www", "", $site->domain);

            if (!isset($_GET['separator'])) {
                echo "\n";
            }
        }
    }
