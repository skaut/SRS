<?php

// uncomment this line if you must temporarily take down your site for maintenance
// require '.maintenance.php';

// absolute filesystem path to this web root
define('WWW_DIR', __DIR__);

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../app');

// absolute filesystem path to the libraries downloadable by composer
define('LIBS_DIR', WWW_DIR . '/../libs/global');

// absolute filesystem path to the libraries, that must be download manually
define ('LOCAL_LIBS_DIR', WWW_DIR . '/../libs/local');

define ('TESTS_DIR', WWW_DIR . '/../tests');

// load bootstrap file
require APP_DIR . '/bootstrap.php';
