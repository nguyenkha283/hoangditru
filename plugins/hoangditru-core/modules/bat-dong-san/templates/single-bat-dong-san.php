<?php
/**
 * Template: Single Bất động sản
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id    = get_the_ID();
$title      = get_the_title();
$dia_chi    = get_post_meta( $post_id, '_bds_dia_chi',         true );
$khoang_gia = get_post_meta( $post_id, '_bds_khoang_gia',      true );
$loai_hinh  = get_post_meta( $post_id, '_bds_loai_hinh',       true );
$carousel   = get_post_meta( $post_id, '_bds_carousel_images', true );
$tong_quan  = get_post_meta( $post_id, '_bds_tong_quan',        true );
$mat_bang   = get_post_meta( $post_id, '_bds_mat_bang',         true );

$carousel  = is_array($carousel)  ? $carousel  : [];
$tong_quan = is_array($tong_quan) ? $tong_quan : [];
$mat_bang  = is_array($mat_bang)  ? $mat_bang  : [];

// Tháng / năm hiện tại (tiếng Việt)
$thang_nam   = date_i18n( 'n/Y' );
$ngay_cap_nhat = get_the_modified_date('d/m/Y');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bds-single'); ?>>

<?php
// Nếu theme có header thì dùng, không thì bỏ qua
if ( function_exists('get_header') ) get_header();
?>

<?php if ( function_exists( 'sbc_bds_single' ) ) sbc_bds_single( $title ); ?>

<div class="bds-wrapper">

    <!-- ===== TIÊU ĐỀ H1 ===== -->
    <h1 class="bds-page-title"><?php echo esc_html($title); ?></h1>

    <!-- ===== CAROUSEL (Slick Syncing) ===== -->
    <?php if ( ! empty($carousel) ) : ?>
    <div class="bds-carousel-section">
        <!-- Slider chính -->
        <div class="bds-slider-main">
            <?php foreach ( $carousel as $img_id ) :
                $img = hdtc_img( $img_id, 'large', array( 'alt' => $title, 'loading' => 'lazy', 'sizes' => '(max-width: 1200px) 100vw, 1152px' ) );
                if ( ! $img ) continue; ?>
                <div class="bds-slide">
                    <?php echo $img; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Slider nav (thumbnails) -->
        <div class="bds-slider-nav">
            <?php foreach ( $carousel as $img_id ) :
                $img = hdtc_img( $img_id, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) );
                if ( ! $img ) continue; ?>
                <div class="bds-slide-thumb">
                    <?php echo $img; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Giá & Loại hình -->
        <div class="bds-price-type-row">
            <div class="bds-price"><?php echo esc_html($khoang_gia); ?></div>
            <div class="bds-loai-hinh"><?php echo esc_html(strtoupper($loai_hinh)); ?></div>
        </div>
    </div>
    <?php elseif ($khoang_gia || $loai_hinh) : ?>
        <div class="bds-price-type-row">
            <div class="bds-price"><?php echo esc_html($khoang_gia); ?></div>
            <div class="bds-loai-hinh"><?php echo esc_html(strtoupper($loai_hinh)); ?></div>
        </div>
    <?php endif; ?>

    <!-- ===== TỔNG QUAN ===== -->
    <?php if ( ! empty($tong_quan) ) : ?>
    <section class="bds-section bds-tong-quan">
        <h2 class="bds-section-title">Tổng quan</h2>
        <hr class="bds-divider" />
        <table class="bds-tq-table">
            <tbody>
                <?php foreach ( $tong_quan as $item ) : ?>
                <tr>
                    <th><?php echo esc_html($item['label']); ?></th>
                    <td><?php echo esc_html($item['value']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- ===== MÔ TẢ (WordPress content) ===== -->
    <?php
    $content = get_the_content();
    if ( $content ) :
    ?>
    <section class="bds-section bds-mo-ta">
        <h2 class="bds-section-title">Mô tả dự án</h2>
        <hr class="bds-divider" />
        <div class="bds-content-wrap">
            <?php echo apply_filters('the_content', $content); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== MẶT BẰNG (Accordion) ===== -->
    <?php if ( ! empty($mat_bang) ) : ?>
    <section class="bds-section bds-mat-bang">
        <h2 class="bds-section-title">Mặt bằng</h2>
        <hr class="bds-divider" />
        <div class="bds-accordion">
            <?php foreach ( $mat_bang as $i => $mb ) :
                if ( empty($mb['title']) && empty($mb['images']) ) continue;
                $img_ids = isset($mb['images']) ? $mb['images'] : [];
            ?>
            <div class="bds-acc-item <?php echo $i === 0 ? 'active' : ''; ?>">
                <button class="bds-acc-header" type="button">
                    <span><?php echo esc_html($mb['title']); ?></span>
                    <svg class="bds-acc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="bds-acc-body">
                    <?php if ( ! empty($img_ids) ) : ?>
                    <div class="bds-mb-gallery">
                        <?php foreach ( $img_ids as $img_id ) :
                            $full  = wp_get_attachment_image_src( $img_id, 'large' );
                            if ( ! $full ) continue; ?>
                            <a href="<?php echo esc_url($full[0]); ?>" class="bds-mb-img-link bds-lb-trigger" data-full="<?php echo esc_url($full[0]); ?>" data-caption="<?php echo esc_attr($mb['title']); ?>">
                                <?php
                                // Lightbox riêng của BĐS (#bds-lightbox) lo phần bấm
                                // xem; lightbox chung của site loại trừ vùng này.
                                echo hdtc_img( $img_id, 'medium', array(
                                    'alt'     => $mb['title'],
                                    'loading' => 'lazy',
                                    'sizes'   => '(max-width: 480px) 100vw, (max-width: 768px) 50vw, 380px',
                                ) );
                                ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p style="color:#888;font-style:italic;">Chưa có hình ảnh mặt bằng.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div><!-- .bds-wrapper -->

<?php if ( function_exists('get_footer') ) get_footer(); ?>

<!-- LIGHTBOX – Mặt bằng -->
<div class="bds-lightbox" id="bds-lightbox" role="dialog" aria-modal="true" aria-label="Xem ảnh mặt bằng">
    <div class="bds-lb-inner">
        <button class="bds-lb-close" aria-label="Đóng">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <div class="bds-lb-img-wrap">
            <button class="bds-lb-prev" aria-label="Ảnh trước">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <img class="bds-lb-img" src="" alt="" />
            <button class="bds-lb-next" aria-label="Ảnh sau">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
        <div class="bds-lb-caption"></div>
        <div class="bds-lb-counter"></div>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
