<?php
defined( 'ABSPATH' ) || exit;
$post_id = get_the_ID();
$bg_id   = get_post_meta( $post_id, '_ci_why_bg', true );
$blocks  = get_post_meta( $post_id, '_ci_why_blocks', true ) ?: [];

if ( empty( $blocks ) ) return;

$bg_url   = $bg_id ? wp_get_attachment_image_url( $bg_id, 'full' ) : '';
$bg_style = $bg_url ? 'style="background-image:url(' . esc_url( $bg_url ) . ')"' : '';
?>
<section class="ci-section ci-why-section" <?php echo $bg_style; ?>>
    <div class="ci-container">
        <div class="ci-why-blocks">
            <?php foreach ( $blocks as $i => $b ) :
                $class = 'ci-why-block';
                if ( $i === 1 ) $class .= ' ci-why-block--middle';
            ?>
                <div class="<?php echo esc_attr( $class ); ?>">
                    <h3 class="ci-why-block__title">
                        <?php echo ! empty( $b['title'] ) ? esc_html( $b['title'] ) : '&nbsp;'; ?>
                    </h3>
                    <div class="ci-why-block__content">
                        <?php echo ! empty( $b['content'] ) ? wp_kses_post( $b['content'] ) : ''; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
