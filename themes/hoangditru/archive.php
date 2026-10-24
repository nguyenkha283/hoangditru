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

        <h1 class="hdt-page-title"><?php echo esc_html( get_the_archive_title() ); ?></h1>

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
