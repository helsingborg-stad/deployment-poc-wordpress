<?php

/**
 * Use local varnish server.
 * @var string
 */
#define('VHP_VARNISH_IP', '127.0.0.1');

/**
 * Use memcached.
 * @var bool
 */
define('WP_USE_MEMCACHED', false); //We are using redis here.

/**
* Memcache/Redis key salt
* @var string
*/
define('WP_CACHE_KEY_SALT', md5(NONCE_KEY));


/**
 * Use redis.
 * @var bool
 */
define('WP_REDIS_DISABLED', false);
define('WP_REDIS_HOST', getenv('WP_REDIS_HOST'));
