<?php
defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();
$slides  = get_post_meta( $post_id, '_ci_carousel_slides', true ) ?: [];
$att_id  = (int) get_post_meta( $post_id, '_ci_overlay_icon', true );
$icon    = $att_id ? wp_get_attachment_image_url( $att_id, 'thumbnail' ) : '';
$title   = get_the_title();

if ( empty( $slides ) ) return;
?>

<section class="ci-section ci-carousel-section">
    <div class="ci-carousel" id="ci-carousel">
        <div class="ci-carousel__track" id="ci-carousel-track">
            <?php foreach ( $slides as $att_id ) :
                $url = wp_get_attachment_image_url( $att_id, 'full' );
                if ( ! $url ) continue;
            ?>
                <div class="ci-carousel__slide">
                    <img src="<?php echo esc_url( $url ); ?>" alt="">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bullets -->
        <div class="ci-carousel__bullets" id="ci-carousel-bullets">
            <?php foreach ( $slides as $i => $att_id ) : ?>
                <button class="ci-bullet<?php echo $i === 0 ? ' is-active' : ''; ?>" data-idx="<?php echo $i; ?>" aria-label="Slide <?php echo $i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>

        <!-- Arrows -->
        <button class="ci-carousel__arrow ci-arrow-prev" id="ci-arrow-prev" aria-label="Trước">&#10094;</button>
        <button class="ci-carousel__arrow ci-arrow-next" id="ci-arrow-next" aria-label="Tiếp">&#10095;</button>
    </div>

    <!-- Fixed overlay: icon + h2 title -->
    <div class="ci-carousel-overlay">
        <div class="ci-carousel-overlay__inner">
            <?php if ( $icon ) : ?>
                <img class="ci-overlay-icon" src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $title ); ?>">
            <?php endif; ?>
            <h2 class="ci-overlay-title"><?php echo esc_html( $title ); ?></h2>
        </div>
    </div>
</section>
