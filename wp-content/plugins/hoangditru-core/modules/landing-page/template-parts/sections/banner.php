<?php
$img      = get_the_post_thumbnail_url( get_the_ID(), 'full' );
$title    = get_the_title();                          // tiêu đề bài (dùng trong description)
$desc     = get_the_excerpt();
$icon     = get_post_meta( get_the_ID(), 'lpb_hero_icon', true );
$cpt_obj  = get_post_type_object( get_post_type() ); // [3] tên CPT = "Golden Visa"
$cpt_name = $cpt_obj ? $cpt_obj->labels->singular_name : '';

if ( ! $img && ! $cpt_name ) return;
$bg = $img ? 'style="background-image:url(' . esc_url( $img ) . ')"' : '';
?>

<section class="lpb-hero" <?php echo $bg; ?>>
    <div class="lpb-hero__overlay">
        <?php if ( $icon ) : ?>
            <img src="<?php echo esc_url( $icon ); ?>" alt="" class="lpb-hero__icon">
        <?php endif; ?>
        <?php if ( $title ) : ?>
            <span class="lpb-hero__title"><?php echo esc_html( $title ); ?></span>
        <?php endif; ?>
    </div>
</section>

<?php if ( $title || $desc ) : ?>
<div class="lpb-hero-desc">
    <div class="lpb-container">
        <?php if ( $desc ) : ?>
            <p class="lpb-hero-desc__text"><?php echo wp_kses_post( $desc ); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
