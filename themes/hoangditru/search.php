<?php
/**
 * Kết quả tìm kiếm.
 */

get_header();
?>

<main class="hdt-main">
    <div class="hdt-wrap">

        <h1 class="hdt-page-title">
            <?php
            printf(
                /* translators: %s: từ khoá tìm kiếm */
                esc_html__( 'Kết quả cho “%s”', 'hoangditru' ),
                esc_html( get_search_query() )
            );
            ?>
        </h1>

        <?php get_search_form(); ?>

        <?php if ( have_posts() ) : ?>

            <ul class="hdt-list" style="margin-top:32px">
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
            <p style="margin-top:32px"><?php esc_html_e( 'Không tìm thấy nội dung nào khớp.', 'hoangditru' ); ?></p>
        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
