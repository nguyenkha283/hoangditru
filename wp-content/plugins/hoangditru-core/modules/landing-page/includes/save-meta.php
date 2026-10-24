<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'save_post_golden_visa', 'lpb_save_meta', 10, 2 );

function lpb_save_meta( $post_id, $post ) {
    if ( ! isset( $_POST['lpb_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['lpb_nonce'], 'lpb_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    /* ── Hero icon ── */
    if ( isset( $_POST['lpb_hero_icon'] ) ) {
        update_post_meta( $post_id, 'lpb_hero_icon', esc_url_raw( wp_unslash( $_POST['lpb_hero_icon'] ) ) );
    }

    /* ── Repeaters ── */
    $repeaters = [
        'lpb_advantages' => [ 'icon', 'text' ],
        'lpb_benefits'   => [ 'image', 'desc' ],
        'lpb_conditions' => [ 'title', 'content' ],
        'lpb_process'    => [ 'title', 'desc' ],
    ];

    foreach ( $repeaters as $key => $sub_fields ) {
        $data = [];
        if ( ! empty( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) {
            foreach ( $_POST[ $key ] as $row ) {
                $clean = [];
                foreach ( $sub_fields as $f ) {
                    if ( $f === 'content' ) {
                        $clean[ $f ] = wp_kses_post( wp_unslash( $row[ $f ] ?? '' ) );
                    } elseif ( $f === 'icon' || $f === 'image' ) {
                        $clean[ $f ] = esc_url_raw( wp_unslash( $row[ $f ] ?? '' ) );
                    } else {
                        $clean[ $f ] = sanitize_text_field( wp_unslash( $row[ $f ] ?? '' ) );
                    }
                }
                if ( ! empty( array_filter( $clean ) ) ) {
                    $data[] = $clean;
                }
            }
        }
        update_post_meta( $post_id, $key, $data );
    }

    /* ── Tin tức ── */
    $news_ids = [];
    if ( ! empty( $_POST['lpb_news_ids'] ) && is_array( $_POST['lpb_news_ids'] ) ) {
        $news_ids = array_map( 'absint', $_POST['lpb_news_ids'] );
    }
    update_post_meta( $post_id, 'lpb_news_ids', $news_ids );

    /* ── Danh mục BĐS ── */
    $bds_cat = absint( $_POST['lpb_bds_category'] ?? 0 );
    update_post_meta( $post_id, 'lpb_bds_category', $bds_cat );

    $bds_ids = [];
    if ( ! empty( $_POST['lpb_bds_ids'] ) && is_array( $_POST['lpb_bds_ids'] ) ) {
        $bds_ids = array_map( 'absint', $_POST['lpb_bds_ids'] );
    }
    update_post_meta( $post_id, 'lpb_bds_ids', $bds_ids );
}
