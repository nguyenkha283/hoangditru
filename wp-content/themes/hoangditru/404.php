<?php
/**
 * Trang không tồn tại.
 */

get_header();
?>

<main class="hdt-main">
    <div class="hdt-wrap">
        <h1 class="hdt-page-title"><?php esc_html_e( 'Không tìm thấy trang', 'hoangditru' ); ?></h1>

        <p><?php esc_html_e( 'Đường dẫn này không còn hoặc chưa bao giờ tồn tại. Thử tìm kiếm bên dưới.', 'hoangditru' ); ?></p>

        <?php get_search_form(); ?>

        <p style="margin-top:24px">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <?php esc_html_e( '← Về trang chủ', 'hoangditru' ); ?>
            </a>
        </p>
    </div>
</main>

<?php
get_footer();
