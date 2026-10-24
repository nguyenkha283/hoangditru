<?php
/**
 * Template: Archive Danh mục BĐS (bds_category)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$term      = get_queried_object();
$term_name = $term ? esc_html( $term->name ) : 'Danh mục';
$term_desc = $term ? $term->description : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $term_name; ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bds-category-archive'); ?>>
<?php if ( function_exists('get_header') ) get_header(); ?>

<div class="bds-cat-wrap">

    <!-- BREADCRUMB -->
    <?php if ( function_exists( 'sbc_bds_taxonomy' ) ) sbc_bds_taxonomy( $term_name ); ?>

    <div class="bds-cat-header">
        <h2 class="bds-cat-title"><?php echo $term_name; ?></h2>
        <?php if ( $term_desc ) : ?>
            <p class="bds-cat-desc"><?php echo esc_html( $term_desc ); ?></p>
        <?php endif; ?>
    </div>

    <div class="bds-cat-container">
        <div class="bds-du-an__grid" id="bds-cat-grid">
            <?php
            $paged = get_query_var('paged') ?: 1;
            $query = new WP_Query([
                'post_type'      => 'bat-dong-san',
                'post_status'    => 'publish',
                'posts_per_page' => 12,
                'paged'          => $paged,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'tax_query'      => [[
                    'taxonomy' => 'bds_category',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ]],
            ]);

            if ( $query->have_posts() ) :
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
            else : ?>
                <p class="bds-cat-empty">Chưa có bất động sản nào trong danh mục này.</p>
            <?php endif; ?>
        </div>

        <?php if ( isset($query) && $query->max_num_pages > 1 ) : ?>
        <div class="bds-cat-loadmore-wrap" id="bds-loadmore-wrap">
            <button
                id="bds-loadmore-btn"
                class="bds-loadmore-btn"
                data-page="1"
                data-max="<?php echo esc_attr( $query->max_num_pages ); ?>"
                data-term="<?php echo esc_attr( $term->term_id ); ?>"
            >Xem thêm</button>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php if ( function_exists('get_footer') ) get_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
