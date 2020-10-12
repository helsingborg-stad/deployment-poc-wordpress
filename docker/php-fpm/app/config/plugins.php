<?php

/**
 * Turn of admin panel for ACF.
 * @var bool
 */
 define('ACF_LITE', false);

/**
 * Force search statistics to only list local searches.
 * @var bool
 */
 define('LOCAL_SITE_STATS', true);

/**
 * Municipio webfont
 * Load webfonts for municipio theme.
 */

define('WEB_FONT', 'Roboto');

/**
 * Integration with ad
 * @var string
 */
 define('AD_INTEGRATION_URL', 'https://intranat.helsingborg.se/ad-api/');
 define('AD_UPDATE_NAME', true);
 define('AD_UPDATE_EMAIL', true);
 define('AD_SAVE_PASSWORD', true);
 define('AD_RANDOM_PASSWORD', false);
 define('AD_USER_DOMAIN', 'helsingborg.se');

/**
 * Enable ssl proxy mode in ssl plugin
 * @var bool
 */
 define('SSL_PROXY', true);

 /**
 * Set a priority to load algolia search at
 * @var bool
 */
define('ALGOLIA_INIT_PRIORITY', 5);