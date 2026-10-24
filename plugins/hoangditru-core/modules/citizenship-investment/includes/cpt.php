<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ci_register_cpt' );
function ci_register_cpt() {
    register_post_type( 'citizenship_invest', [
        'labels' => [
            'name'               => 'Đầu tư quốc tịch',
            'singular_name'      => 'Đầu tư quốc tịch',
            'add_new'            => 'Thêm mới',
            'add_new_item'       => 'Thêm Đầu tư quốc tịch',
            'edit_item'          => 'Chỉnh sửa',
            'view_item'          => 'Xem trang',
            'search_items'       => 'Tìm kiếm',
            'not_found'          => 'Không tìm thấy',
        ],
        'public'              => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-flag',
        'supports'            => [ 'title', 'thumbnail', 'excerpt' ],
        'has_archive'         => true,
        'rewrite'             => [ 'slug' => 'dau-tu-quoc-tich' ],
        'show_in_rest'        => true,
    ] );
}

// Disable Gutenberg for this CPT – required for wp_editor() in meta boxes to work correctly
add_filter( 'use_block_editor_for_post_type', 'ci_disable_gutenberg', 10, 2 );
function ci_disable_gutenberg( $enabled, $post_type ) {
    if ( $post_type === 'citizenship_invest' ) return false;
    return $enabled;
}
