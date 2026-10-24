<?php
/**
 * Template dự phòng — WordPress dùng khi không có template cụ thể hơn.
 */

get_header();
?>

<main class="hdt-main">
    <div class="hdt-wrap">

        <?php if ( have_posts() ) : ?>

            <?php if ( ! is_front_page() && ! is_home() ) : ?>
                <h1 class="hdt-page-title"><?php the_title(); ?></h1>
            <?php endif; ?>

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
            <p><?php esc_html_e( 'Chưa có bài viết nào.', 'hoangditru' ); ?></p>
        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
