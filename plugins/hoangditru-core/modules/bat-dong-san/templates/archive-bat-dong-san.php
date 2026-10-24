<?php
/**
 * Template: Archive CPT bat-dong-san (/bat-dong-san)
 * Hiển thị tất cả category con, mỗi category 1 section với 4 BĐS mới nhất
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bất động sản | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bds-archive'); ?>>
<?php if ( function_exists('get_header') ) get_header(); ?>

<div class="bds-arc-wrap">

    <!-- BREADCRUMB -->
    <?php if ( function_exists( 'sbc_bds_archive' ) ) sbc_bds_archive(); ?>

    <div class="bds-arc-container">

        <!-- TITLE -->
        <h1 class="bds-arc-title">Toàn bộ Bất động sản</h1>
        <hr class="bds-arc-divider">

        <?php
        // Lấy tất cả category con (top-level)
        $categories = get_terms([
            'taxonomy'   => 'bds_category',
            'hide_empty' => true,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if ( empty($categories) || is_wp_error($categories) ) : ?>
            <p class="bds-arc-empty">Chưa có danh mục bất động sản nào.</p>
        <?php else :
            foreach ( $categories as $cat ) :
                $cat_link = get_term_link( $cat );

                $posts = get_posts([
                    'post_type'      => 'bat-dong-san',
                    'post_status'    => 'publish',
                    'posts_per_page' => 4,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'tax_query'      => [[
                        'taxonomy'         => 'bds_category',
                        'field'            => 'term_id',
                        'terms'            => $cat->term_id,
                        'include_children' => true,
                    ]],
                ]);

                if ( empty($posts) ) continue;
                ?>
                <section class="bds-arc-section">
                    <div class="bds-arc-section__header">
                        <h2 class="bds-arc-section__title"><?php echo esc_html( $cat->name ); ?></h2>
                        <a href="<?php echo esc_url( $cat_link ); ?>" class="bds-arc-section__viewall">
                            Xem tất cả
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                        </a>
                    </div>

                    <div class="bds-du-an__grid">
                        <?php foreach ( $posts as $post ) :
                            $pid = $post->ID;
                            echo bds_render_card(
                                $pid,
                                get_the_post_thumbnail_url( $pid, 'large' ),
                                $post->post_title,
                                get_post_meta( $pid, '_bds_dia_chi',    true ),
                                get_post_meta( $pid, '_bds_khoang_gia', true ),
                                get_post_meta( $pid, '_bds_loai_hinh',  true ),
                                get_permalink( $pid )
                            );
                        endforeach; ?>
                    </div>
                </section>
            <?php endforeach;
        endif; ?>

    </div><!-- .bds-arc-container -->

</div><!-- .bds-arc-wrap -->

<?php if ( function_exists('get_footer') ) get_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
