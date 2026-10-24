<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Admin: CSS + JS cho meta boxes ── */
add_action( 'admin_enqueue_scripts', 'lpb_admin_assets' );

function lpb_admin_assets( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;
    global $post;
    if ( ! $post || $post->post_type !== 'golden_visa' ) return;

    wp_enqueue_media();

    wp_enqueue_style(
        'lpb-admin',
        LPB_URL . 'assets/css/admin.css',
        [],
        LPB_VERSION
    );

    wp_enqueue_script(
        'lpb-admin',
        LPB_URL . 'assets/js/admin.js',
        [ 'jquery', 'jquery-ui-sortable' ],
        LPB_VERSION,
        true
    );
}

/* ── Frontend: CSS + JS chỉ khi dùng template ── */
add_action( 'wp_enqueue_scripts', 'lpb_frontend_assets' );

function lpb_frontend_assets() {
    if ( ! is_singular( 'golden_visa' ) ) return;

    wp_enqueue_style(
        'lpb-frontend',
        LPB_URL . 'assets/css/landing.css',
        [],
        LPB_VERSION
    );

    wp_enqueue_script(
        'lpb-frontend',
        LPB_URL . 'assets/js/landing.js',
        [],
        LPB_VERSION,
        true
    );
}
