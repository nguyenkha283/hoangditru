<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render HTML cho 1 card bất động sản
 * Dùng chung cho taxonomy-bds_category.php, archive và ajax-category.php
 */
function bds_render_card( $post_id, $thumb_url, $title, $dia_chi, $khoang_gia, $loai_hinh, $link ) {
    // srcset để category.js gắn cùng lúc với src. Lưới 4 → 3 → 2 → 1 cột trong
    // khung 1200px, xem .bds-du-an__grid trong category.css.
    $thumb_id = $thumb_url ? get_post_thumbnail_id( $post_id ) : 0;
    $srcset   = $thumb_id ? wp_get_attachment_image_srcset( $thumb_id, 'large' ) : '';
    ob_start(); ?>
    <article class="bds-du-an__item">
        <a href="<?php echo esc_url( $link ); ?>" class="bds-du-an__thumb-link">
            <?php if ( $thumb_url ) : ?>
                <img
                    data-src="<?php echo esc_url( $thumb_url ); ?>"
                    <?php if ( $srcset ) : ?>
                    data-srcset="<?php echo esc_attr( $srcset ); ?>"
                    data-sizes="(max-width: 480px) 100vw, (max-width: 768px) 50vw, (max-width: 1024px) 33vw, 290px"
                    <?php endif; ?>
                    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 200'/%3E"
                    alt="<?php echo esc_attr( $title ); ?>"
                    class="bds-du-an__thumb"
                    width="400" height="200"
                >
            <?php else : ?>
                <div class="bds-du-an__thumb bds-du-an__thumb--placeholder"></div>
            <?php endif; ?>

            <?php if ( $loai_hinh ) : ?>
                <span class="bds-du-an__tag"><?php echo esc_html( $loai_hinh ); ?></span>
            <?php endif; ?>
        </a>

        <div class="bds-du-an__body">
            <h3 class="bds-du-an__name">
                <a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a>
            </h3>

            <?php if ( $dia_chi ) : ?>
            <p class="bds-du-an__address">
                <svg viewBox="0 0 24 24" class="bds-du-an__icon" aria-hidden="true">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>
                </svg>
                <?php echo esc_html( $dia_chi ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $khoang_gia ) : ?>
            <p class="bds-du-an__price">
                <svg viewBox="0 0 24 24" class="bds-du-an__icon" aria-hidden="true">
                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                </svg>
                <?php echo esc_html( $khoang_gia ); ?>
            </p>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
