<?php
/**
 * Plugin Name:  Landing Page Builder
 * Description:  Custom Landing Page Template — không cần ACF. Tự quản lý meta boxes.
 * Version:      1.3.2
 * Author:       Your Name
 * Text Domain:  landing-page-plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LPB_VERSION', '1.3.2' );
define( 'LPB_DIR',     plugin_dir_path( __FILE__ ) );
define( 'LPB_URL',     plugin_dir_url( __FILE__ ) );

require_once LPB_DIR . 'includes/cpt-golden-visa.php';
require_once LPB_DIR . 'includes/page-template.php';
require_once LPB_DIR . 'includes/meta-boxes.php';
require_once LPB_DIR . 'includes/save-meta.php';
require_once LPB_DIR . 'includes/admin-ui.php';
require_once LPB_DIR . 'includes/enqueue.php';
require_once LPB_DIR . 'includes/ajax-bds.php';

/* ── Helper: load template part từ plugin ── */
if ( ! function_exists( 'lpb_part' ) ) {
    function lpb_part( $name ) {
        $file = LPB_DIR . "template-parts/sections/{$name}.php";
        if ( file_exists( $file ) ) include $file;
    }
}

/* ── Flush rewrite rules khi activate ── */
register_activation_hook( __FILE__, function () {
    lpb_register_golden_visa_cpt();
    flush_rewrite_rules();
} );

/* ── Flush rewrite rules khi deactivate ── */
register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );
