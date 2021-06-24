<?php

$wpSecrets = json_decode(getenv('WP_SECRETS'), true);

define('AUTH_KEY', $wpSecrets['WP_AUTH_KEY']);
define('SECURE_AUTH_KEY', $wpSecrets['WP_SECURE_AUTH_KEY']);
define('LOGGED_IN_KEY', $wpSecrets['WP_LOGGED_IN_KEY']);
define('NONCE_KEY', $wpSecrets['WP_NONCE_KEY']);
define('AUTH_SALT', $wpSecrets['WP_AUTH_SALT']);
define('SECURE_AUTH_SALT', $wpSecrets['WP_SECURE_AUTH_SALT']);
define('LOGGED_IN_SALT', $wpSecrets['WP_LOGGED_IN_SALT']);
define('NONCE_SALT', $wpSecrets['WP_NONCE_SALT']);
