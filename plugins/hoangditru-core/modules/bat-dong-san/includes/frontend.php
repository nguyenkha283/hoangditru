<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'template_include', 'bds_template_include' );
function bds_template_include( $template ) {
    if ( is_singular('bat-dong-san') ) {
        $custom = BDS_PATH . 'templates/single-bat-dong-san.php';
        if ( file_exists($custom) ) return $custom;
    }
    if ( is_tax('bds_category') ) {
        $custom = BDS_PATH . 'templates/taxonomy-bds_category.php';
        if ( file_exists($custom) ) return $custom;
    }
    if ( is_post_type_archive('bat-dong-san') ) {
        $custom = BDS_PATH . 'templates/archive-bat-dong-san.php';
        if ( file_exists($custom) ) return $custom;
    }
    return $template;
}

/* Enqueue assets cho trang taxonomy category */
add_action( 'wp_enqueue_scripts', 'bds_enqueue_category_scripts' );
function bds_enqueue_category_scripts() {
    $is_tax     = is_tax('bds_category');
    $is_archive = is_post_type_archive('bat-dong-san');

    if ( ! $is_tax && ! $is_archive ) return;

    wp_enqueue_style(  'bds-cat-style', BDS_URL . 'assets/css/category.css', [], '1.2.0' );
    wp_enqueue_script( 'bds-cat-js',    BDS_URL . 'assets/js/category.js', ['jquery'], '1.3.1', true );
    wp_localize_script( 'bds-cat-js', 'BDS_CAT', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('bds_load_more_nonce'),
    ]);
}
