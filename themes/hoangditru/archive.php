<?php
/**
 * Lưu trữ theo ngày, tác giả, tag và các taxonomy không có template riêng.
 *
 * Category dùng category.php; bất động sản dùng template của plugin.
 */

get_header();
?>

<main class="hdt-main">
    <div class="hdt-wrap">

        <?php
        /*
         * Hai cách in tiêu đề, khác nhau ở chỗ escape. Đọc kỹ trước khi gộp
         * lại cho gọn, vì gộp sai là lỗi hiện ra tận mặt khách:
         *
         *   post_type_archive_title( '', false ) trả về CHỮ THUẦN — nhãn của
         *   post type, ví dụ "Golden Visa" — nên phải tự bọc esc_html.
         *
         *   the_archive_title() in ra HTML có sẵn dạng `Lưu trữ: <span>…</span>`
         *   và đã tự escape phần chữ bên trong. Bọc esc_html quanh nó là in
         *   nguyên chuỗi "<span>" cho khách đọc — đúng lỗi đã có thật trên
         *   /golden-visa/ trước đây.
         *
         * Golden Visa và Đầu tư quốc tịch chỉ lấy tên mục, bỏ tiền tố
         * "Lưu trữ:" cho sạch.
         */
        if ( is_post_type_archive( HDT_PROGRAM_POST_TYPES ) ) {
            printf(
                '<h1 class="hdt-page-title">%s</h1>',
                esc_html( post_type_archive_title( '', false ) )
            );
        } else {
            the_archive_title( '<h1 class="hdt-page-title">', '</h1>' );
        }
        ?>

        <?php
        $hdt_description = get_the_archive_description();

        if ( $hdt_description ) {
            echo '<div class="hdt-entry">' . wp_kses_post( $hdt_description ) . '</div>';
        }
        ?>

        <?php if ( have_posts() ) : ?>

            <ul class="hdt-list">
                <?php
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'template-parts/content-card' );
                endwhile;
                ?>
            </ul>

            <?php
            $hdt_pagination = paginate_links( array( 'type' => 'list' ) );

            if ( $hdt_pagination ) {
                echo '<nav class="hdt-pagination">' . $hdt_pagination . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
            }
            ?>

        <?php else : ?>
            <p><?php esc_html_e( 'Chưa có bài viết nào trong mục này.', 'hoangditru' ); ?></p>
        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
