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
            ?>
                <div class="ci-feat-item">
                    <?php echo hdtc_img( $att_id, 'thumbnail', array( 'class' => 'ci-feat-icon', 'alt' => '', 'sizes' => '36px' ) ); ?>
                    <span class="ci-feat-text"><?php echo esc_html( $f['text'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
