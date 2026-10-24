<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_bds_load_more',        'bds_ajax_load_more' );
add_action( 'wp_ajax_nopriv_bds_load_more', 'bds_ajax_load_more' );

function bds_ajax_load_more() {
    check_ajax_referer( 'bds_load_more_nonce', 'nonce' );

    $page    = absint( $_POST['page'] ?? 1 ) + 1;
    $term_id = absint( $_POST['term_id'] ?? 0 );

    $query = new WP_Query([
        'post_type'      => 'bat-dong-san',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => [[
            'taxonomy' => 'bds_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]],
    ]);

    if ( ! $query->have_posts() ) {
        wp_send_json_success( [ 'html' => '', 'no_more' => true ] );
    }

    ob_start();
    while ( $query->have_posts() ) : $query->the_post();
        $pid = get_the_ID();
        echo bds_render_card(
            $pid,
            get_the_post_thumbnail_url( $pid, 'large' ),
            get_the_title(),
            get_post_meta( $pid, '_bds_dia_chi',    true ),
            get_post_meta( $pid, '_bds_khoang_gia', true ),
            get_post_meta( $pid, '_bds_loai_hinh',  true ),
            get_permalink()
        );
    endwhile;
    wp_reset_postdata();

    wp_send_json_success([
        'html'    => ob_get_clean(),
        'no_more' => ( $page >= $query->max_num_pages ),
        'page'    => $page,
    ]);
}
