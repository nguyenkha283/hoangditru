<?php
defined( 'ABSPATH' ) || exit;

$post_id    = get_the_ID();
$slides     = get_post_meta( $post_id, '_ci_carousel_slides', true ) ?: [];
// Ảnh của hộp "Hero — Icon góc dưới trái". Tên biến đặt rõ để không bị vòng lặp
// nào bên dưới vô tình ghi đè như đã từng xảy ra.
$overlay_id = (int) get_post_meta( $post_id, '_ci_overlay_icon', true );
$title      = get_the_title();

if ( empty( $slides ) ) return;
?>

<section class="ci-section ci-carousel-section">
    <div class="ci-carousel" id="ci-carousel">
        <div class="ci-carousel__track" id="ci-carousel-track">
            <?php foreach ( $slides as $i => $slide_id ) :
                // Slide rộng hết màn hình. Slide đầu tải ngay (ảnh LCP), các
                // slide sau lazy.
                $img = hdtc_img( $slide_id, 'full', array(
                    'alt'     => '',
                    'sizes'   => '100vw',
                    'loading' => 0 === $i ? false : 'lazy',
                ) );
                if ( ! $img ) continue;
            ?>
                <div class="ci-carousel__slide">
                    <?php echo $img; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bullets -->
        <div class="ci-carousel__bullets" id="ci-carousel-bullets">
            <?php // Chỉ cần chỉ số. Trước đây vòng này nhận id slide vào $att_id,
                  // ghi đè mất id ảnh Hero lấy ở đầu file, nên overlay bên dưới
                  // hiện ảnh slide cuối thay vì icon Hero.
            foreach ( array_keys( $slides ) as $i ) : ?>
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
            <?php echo hdtc_img( $overlay_id, 'thumbnail', array(
                'class'   => 'ci-overlay-icon',
                'alt'     => $title,
                'loading' => false,
                'sizes'   => '36px',
            ) ); ?>
            <h2 class="ci-overlay-title"><?php echo esc_html( $title ); ?></h2>
        </div>
    </div>
</section>
