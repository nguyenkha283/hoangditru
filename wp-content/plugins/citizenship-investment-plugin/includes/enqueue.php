<?php
defined( 'ABSPATH' ) || exit;

/* ---- Admin ---- */
add_action( 'admin_enqueue_scripts', 'ci_admin_assets' );
function ci_admin_assets( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;
    global $post;
    if ( ! $post || $post->post_type !== 'citizenship_invest' ) return;

    wp_enqueue_media();
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_script( 'ci-admin', CI_URL . 'assets/js/admin.js', [ 'jquery', 'jquery-ui-sortable' ], CI_VERSION, true );
    wp_enqueue_style( 'ci-admin', CI_URL . 'assets/css/admin.css', [], CI_VERSION );
}

/* ---- Frontend ---- */
add_action( 'wp_enqueue_scripts', 'ci_frontend_assets' );
function ci_frontend_assets() {
    if ( ! is_singular( 'citizenship_invest' ) ) return;
    wp_enqueue_style( 'ci-frontend', CI_URL . 'assets/css/frontend.css', [], CI_VERSION );
    wp_enqueue_script( 'ci-frontend', CI_URL . 'assets/js/frontend.js', [ 'jquery' ], CI_VERSION, true );
}
