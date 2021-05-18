<?php

/**
* Set memory limit
*/

define('WP_MEMORY_LIMIT', '512M');


/**
 * Set S3 bucket.
 */
define('S3_UPLOADS_BUCKET', getenv('S3_UPLOADS_BUCKET'));
