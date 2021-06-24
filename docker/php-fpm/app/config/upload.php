<?php

/**
* Set memory limit
*/

define('WP_MEMORY_LIMIT', '512M');


/**
 * Set S3 bucket.
 */
define('S3_UPLOADS_BUCKET', getenv('S3_UPLOADS_BUCKET'));
define('S3_UPLOADS_KEY', getenv('S3_UPLOADS_KEY'));
define('S3_UPLOADS_SECRET', getenv('S3_UPLOADS_SECRET'));
define('S3_UPLOADS_REGION', getenv('S3_UPLOADS_REGION'));

