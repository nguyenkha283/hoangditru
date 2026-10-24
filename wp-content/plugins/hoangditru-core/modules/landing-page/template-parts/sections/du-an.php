<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$bds_cat_id = get_post_meta( get_the_ID(), 'lpb_bds_category', true );
$bds_ids    = get_post_meta( get_the_ID(), 'lpb_bds_ids',      true ) ?: [];

// Ẩn section nếu không có danh mục hoặc không chọn BĐS nào
if ( ! $bds_cat_id || empty( $bds_ids ) ) return;

// Lấy tối đa 4 BĐS đầu tiên được chọn
$bds_ids_show = array_slice( $bds_ids, 0, 4 );

$bds_posts = get_posts( [
    'post_type'      => 'bat-dong-san',
    'post__in'       => $bds_ids_show,
    'orderby'        => 'post__in',   // giữ thứ tự người dùng chọn
    'posts_per_page' => 4,
    'post_status'    => 'publish',
] );

if ( empty( $bds_posts ) ) return;

// URL "Xem tất cả" → archive của taxonomy term
$term     = get_term( $bds_cat_id, 'bds_category' );
$cat_url  = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : '';
?>

<section class="lpb-section lpb-du-an">
    <div class="lpb-container">

        <h2 class="lpb-section__title">Danh mục dự án</h2>

        <div class="lpb-du-an__grid">
            <?php foreach ( $bds_posts as $bds ) :
                $dia_chi    = get_post_meta( $bds->ID, '_bds_dia_chi',    true );
                $khoang_gia = get_post_meta( $bds->ID, '_bds_khoang_gia', true );
                $thumb_id   = get_post_thumbnail_id( $bds->ID );
                $link       = get_permalink( $bds->ID );
            ?>
            <article class="lpb-du-an__item">
                <a href="<?php echo esc_url( $link ); ?>" class="lpb-du-an__thumb-link">
                    <?php if ( $thumb_id ) : ?>
                        <?php echo hdtc_img( $thumb_id, 'large', array(
                            'alt'     => $bds->post_title,
                            'class'   => 'lpb-du-an__thumb',
                            'loading' => 'lazy',
                            'sizes'   => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw',
                        ) ); ?>
                    <?php else : ?>
                        <div class="lpb-du-an__thumb lpb-du-an__thumb--placeholder"></div>
                    <?php endif; ?>
                </a>

                <div class="lpb-du-an__body">
                    <h3 class="lpb-du-an__name">
                        <a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $bds->post_title ); ?></a>
                    </h3>

                    <?php if ( $dia_chi ) : ?>
                    <p class="lpb-du-an__address">
                        <svg viewBox="0 0 24 24" class="lpb-du-an__icon" aria-hidden="true">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>
                        </svg>
                        <?php echo esc_html( $dia_chi ); ?>
                    </p>
                    <?php endif; ?>

                    <?php if ( $khoang_gia ) : ?>
                    <p class="lpb-du-an__price">
                        <svg viewBox="0 0 24 24" class="lpb-du-an__icon" aria-hidden="true">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                        <?php echo esc_html( $khoang_gia ); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ( $cat_url ) : ?>
        <div class="lpb-du-an__footer">
            <a href="<?php echo esc_url( $cat_url ); ?>" class="lpb-du-an__view-all">
                Xem tất cả
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5l8 7-8 7"/></svg>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>
