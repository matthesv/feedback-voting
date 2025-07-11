<?php
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = '/tmp/wordpress-develop/tests/phpunit';
}

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', '/tmp/PHPUnit-Polyfills' );
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    add_filter( 'pre_http_request', '__return_true' );
    require dirname( __DIR__ ) . '/feedback-voting.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
