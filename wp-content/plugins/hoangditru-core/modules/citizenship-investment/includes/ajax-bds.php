<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_ci_get_bds_by_cat', 'ci_ajax_get_bds_by_cat' );

function ci_ajax_get_bds_by_cat() {
    check_ajax_referer( 'ci_bds_ajax', 'nonce' );

    $term_id = absint( $_POST['term_id'] ?? 0 );
    if ( ! $term_id ) {
        wp_send_json_error( 'Danh mục không hợp lệ.' );
    }

    $posts = get_posts( [
        'post_type'      => 'bat-dong-san',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [ [
            'taxonomy' => 'bds_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    if ( empty( $posts ) ) {
        wp_send_json_error( 'Danh mục này chưa có bất động sản nào.' );
    }

    ob_start();
    echo '<div class="ci-bds-list">';
    echo '<p style="font-weight:600;margin-bottom:8px">Chọn bất động sản hiển thị <small style="color:#888;font-weight:400">(tối đa 4 BĐS đầu được hiển thị)</small></p>';
    foreach ( $posts as $p ) {
        echo '<label class="ci-bds-item">';
        echo '<input type="checkbox" name="ci_bds_ids[]" value="' . esc_attr( $p->ID ) . '">';
        echo '<span>' . esc_html( $p->post_title ) . '</span>';
        echo '</label>';
    }
    echo '</div>';
    $html = ob_get_clean();

    wp_send_json_success( [ 'html' => $html ] );
}
