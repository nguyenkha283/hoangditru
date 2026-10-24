<?php
/**
 * Plugin Name: Citizenship Investment
 * Description: Custom Post Type for Đầu tư quốc tịch with carousel, features, why us, programs & news sections.
 * Version:     1.0.0
 * Text Domain: citizenship-investment
 */

defined( 'ABSPATH' ) || exit;

define( 'CI_VERSION', '1.0.0' );
define( 'CI_FILE',    __FILE__ );
define( 'CI_DIR',     plugin_dir_path( __FILE__ ) );
define( 'CI_URL',     plugin_dir_url( __FILE__ ) );

require_once CI_DIR . 'includes/cpt.php';
require_once CI_DIR . 'includes/meta-boxes.php';
require_once CI_DIR . 'includes/enqueue.php';
require_once CI_DIR . 'includes/template-loader.php';
require_once CI_DIR . 'includes/ajax-bds.php';
require_once CI_DIR . 'includes/ajax-bds.php';
