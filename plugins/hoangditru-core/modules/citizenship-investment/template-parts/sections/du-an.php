<?php
defined( 'ABSPATH' ) || exit;

$bds_cat_id = get_post_meta( get_the_ID(), '_ci_bds_category', true );
$bds_ids    = get_post_meta( get_the_ID(), '_ci_bds_ids',      true ) ?: [];

if ( ! $bds_cat_id || empty( $bds_ids ) ) return;

$bds_ids_show = array_slice( $bds_ids, 0, 4 );

$bds_posts = get_posts( [
    'post_type'      => 'bat-dong-san',
    'post__in'       => $bds_ids_show,
    'orderby'        => 'post__in',
    'posts_per_page' => 4,
    'post_status'    => 'publish',
] );

if ( empty( $bds_posts ) ) return;

$term    = get_term( $bds_cat_id, 'bds_category' );
$cat_url = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : '';
?>

<section class="ci-section ci-du-an">
    <div class="ci-container">

        <h2 class="ci-section-title">Danh mục dự án</h2>

        <div class="ci-du-an__grid">
            <?php foreach ( $bds_posts as $bds ) :
                $dia_chi    = get_post_meta( $bds->ID, '_bds_dia_chi',    true );
                $khoang_gia = get_post_meta( $bds->ID, '_bds_khoang_gia', true );
                $thumb_url  = get_the_post_thumbnail_url( $bds->ID, 'large' );
                $link       = get_permalink( $bds->ID );
            ?>
            <article class="ci-du-an__item">
                <a href="<?php echo esc_url( $link ); ?>" class="ci-du-an__thumb-link">
                    <?php if ( $thumb_url ) : ?>
                        <img src="<?php echo esc_url( $thumb_url ); ?>"
                             alt="<?php echo esc_attr( $bds->post_title ); ?>"
                             class="ci-du-an__thumb"
                             loading="lazy">
                    <?php else : ?>
                        <div class="ci-du-an__thumb ci-du-an__thumb--placeholder"></div>
                    <?php endif; ?>
                </a>

                <div class="ci-du-an__body">
                    <h3 class="ci-du-an__name">
                        <a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $bds->post_title ); ?></a>
                    </h3>

                    <?php if ( $dia_chi ) : ?>
                    <p class="ci-du-an__address">
                        <svg viewBox="0 0 24 24" class="ci-du-an__icon" aria-hidden="true">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>
                        </svg>
                        <?php echo esc_html( $dia_chi ); ?>
                    </p>
                    <?php endif; ?>

                    <?php if ( $khoang_gia ) : ?>
                    <p class="ci-du-an__price">
                        <svg viewBox="0 0 24 24" class="ci-du-an__icon" aria-hidden="true">
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
        <div class="ci-du-an__footer">
            <a href="<?php echo esc_url( $cat_url ); ?>" class="ci-du-an__view-all">
                Xem tất cả
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5l8 7-8 7"/></svg>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>
