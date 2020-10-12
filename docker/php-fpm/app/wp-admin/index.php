<?php header('Location: /wp/' . trim(parse_url($url, PHP_URL_PATH), '/') . "?" . http_build_query($_GET));
