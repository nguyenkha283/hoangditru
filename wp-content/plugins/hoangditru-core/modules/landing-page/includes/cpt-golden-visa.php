<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'lpb_register_golden_visa_cpt' );

function lpb_register_golden_visa_cpt() {

    register_post_type( 'golden_visa', [
        'labels' => [
            'name'               => 'Golden Visa',
            'singular_name'      => 'Golden Visa',
            'add_new'            => 'Thêm mới',
            'add_new_item'       => 'Thêm Golden Visa mới',
            'edit_item'          => 'Chỉnh sửa Golden Visa',
            'new_item'           => 'Golden Visa mới',
            'view_item'          => 'Xem Golden Visa',
            'search_items'       => 'Tìm kiếm Golden Visa',
            'not_found'          => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác',
            'all_items'          => 'Tất cả Golden Visa',
            'menu_name'          => 'Golden Visa',
        ],
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_nav_menus'  => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'golden-visa', 'with_front' => false ],
        'capability_type'    => 'page',
        'map_meta_cap'       => true,
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-awards',
        'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
        'taxonomies'         => [ 'golden_visa_category' ],
    ] );

    register_taxonomy( 'golden_visa_category', 'golden_visa', [
        'labels' => [
            'name'              => 'Danh mục Golden Visa',
            'singular_name'     => 'Danh mục',
            'search_items'      => 'Tìm danh mục',
            'all_items'         => 'Tất cả danh mục',
            'parent_item'       => 'Danh mục cha',
            'parent_item_colon' => 'Danh mục cha:',
            'edit_item'         => 'Sửa danh mục',
            'update_item'       => 'Cập nhật danh mục',
            'add_new_item'      => 'Thêm danh mục mới',
            'new_item_name'     => 'Tên danh mục mới',
            'menu_name'         => 'Danh mục',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => [ 'slug' => 'golden-visa-category', 'with_front' => false ],
    ] );
}
