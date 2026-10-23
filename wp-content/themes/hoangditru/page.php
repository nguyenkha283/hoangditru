<?php
/**
 * Trang đơn (post type "page") không có template riêng.
 *
 * Trang chủ, landing page và trang đầu tư quốc tịch dùng page template của
 * plugin nên không đi qua file này.
 */

get_header();
?>

<main class="hdt-main">
    <div class="hdt-wrap">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <h1 class="hdt-page-title"><?php the_title(); ?></h1>

            <div class="hdt-entry">
                <?php the_content(); ?>
            </div>
            <?php
        endwhile;
        ?>
    </div>
</main>

<?php
get_footer();
