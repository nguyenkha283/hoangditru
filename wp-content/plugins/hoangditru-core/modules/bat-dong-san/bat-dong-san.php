<?php
/**
 * Plugin Name: Bất Động Sản - Khaiditru
 * Plugin URI:  https://khaiditru.com
 * Description: Custom Post Type Bất động sản với đầy đủ tính năng: tổng quan, carousel, mặt bằng, category.
 * Version:     1.1.0
 * Author:      Khaiditru
 * Text Domain: bat-dong-san
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BDS_PATH', plugin_dir_path( __FILE__ ) );
define( 'BDS_URL',  plugin_dir_url( __FILE__ ) );

require_once BDS_PATH . 'includes/cpt.php';
require_once BDS_PATH . 'includes/card-helper.php';
require_once BDS_PATH . 'includes/meta-boxes.php';
require_once BDS_PATH . 'includes/admin-ui.php';
require_once BDS_PATH . 'includes/frontend.php';
require_once BDS_PATH . 'includes/ajax-category.php';
require_once BDS_PATH . 'includes/shortcodes.php';

/* -------------------------------------------------------
 * Flush rewrite rules khi activate / deactivate
 * Phải đặt ở file plugin chính, KHÔNG đặt trong file include
 * ----------------------------------------------------- */
register_activation_hook( __FILE__, 'bds_activate' );
function bds_activate() {
    bds_register_cpt();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'bds_deactivate' );
function bds_deactivate() {
    flush_rewrite_rules();
}

/* -------------------------------------------------------
 * Enqueue assets trang single BĐS
 * ----------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'bds_enqueue_scripts' );
function bds_enqueue_scripts() {
    if ( ! is_singular('bat-dong-san') ) return;

    wp_enqueue_style( 'slick',       'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',       [], '1.8.1' );
    wp_enqueue_style( 'slick-theme', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', ['slick'], '1.8.1' );
    wp_enqueue_style( 'bds-style',    BDS_URL . 'assets/css/style.css', [], '1.1.0' );

    wp_enqueue_script( 'slick-js',   'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true );
    wp_enqueue_script( 'bds-main',    BDS_URL . 'assets/js/main.js', ['jquery', 'slick-js'], '1.1.0', true );
}
