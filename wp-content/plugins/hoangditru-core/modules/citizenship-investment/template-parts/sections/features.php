<?php
defined( 'ABSPATH' ) || exit;
$feats = get_post_meta( get_the_ID(), '_ci_features', true ) ?: [];
if ( empty( $feats ) ) return;
?>
<section class="ci-section ci-features-section">
    <div class="ci-container">
        <div class="ci-features-grid">
            <?php foreach ( $feats as $f ) :
                if ( empty( $f['text'] ) ) continue;
                $att_id  = (int) ( $f['icon'] ?? 0 );
                $img_url = $att_id ? wp_get_attachment_image_url( $att_id, 'thumbnail' ) : '';
            ?>
                <div class="ci-feat-item">
                    <?php if ( $img_url ) : ?>
                        <img class="ci-feat-icon" src="<?php echo esc_url( $img_url ); ?>" alt="">
                    <?php endif; ?>
                    <span class="ci-feat-text"><?php echo esc_html( $f['text'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
