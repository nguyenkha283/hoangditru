<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Đăng ký Taxonomy TRƯỚC, CPT SAU
 * WordPress cần taxonomy tồn tại trước khi gán vào CPT
 * để rewrite rules được sinh ra đúng thứ tự.
 * Activation hook và flush_rewrite_rules nằm trong bat-dong-san.php
 */
add_action( 'init', 'bds_register_cpt', 0 );
function bds_register_cpt() {

    // 1. Taxonomy trước
    register_taxonomy( 'bds_category', 'bat-dong-san', [
        'labels' => [
            'name'              => 'Danh mục BĐS',
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
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => [
            'slug'         => 'danh-muc-bds',
            'with_front'   => false,
            'hierarchical' => true,
        ],
    ] );

    // 2. CPT sau
    register_post_type( 'bat-dong-san', [
        'labels' => [
            'name'               => 'Bất động sản',
            'singular_name'      => 'Bất động sản',
            'add_new'            => 'Thêm mới',
            'add_new_item'       => 'Thêm bất động sản mới',
            'edit_item'          => 'Chỉnh sửa bất động sản',
            'new_item'           => 'Bất động sản mới',
            'view_item'          => 'Xem bất động sản',
            'search_items'       => 'Tìm kiếm bất động sản',
            'not_found'          => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác',
            'menu_name'          => 'Bất động sản',
        ],
        'public'      => true,
        'has_archive' => true,
        'show_in_rest'=> true,
        'supports'    => ['title', 'editor', 'thumbnail'],
        'menu_icon'   => 'dashicons-building',
        'rewrite'     => [
            'slug'       => 'bat-dong-san',
            'with_front' => false,
        ],
        'taxonomies'  => ['bds_category'],
    ] );
}
