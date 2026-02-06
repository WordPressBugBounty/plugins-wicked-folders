<?php

namespace Wicked_Folders\REST_API\v1;

use WP_REST_Controller;

// Disable direct load
defined( 'ABSPATH' ) || exit;

class REST_API extends WP_REST_Controller {

    protected $version = 1;

    protected $base = 'wicked-folders/v1';
}
