<?php
defined( 'ABSPATH' ) || exit;
$desc = get_the_excerpt();
if ( ! $desc ) return;
?>
<section class="ci-section ci-page-desc-section">
    <div class="ci-container">
        <div class="ci-page-desc">
            <?php echo wp_kses_post( $desc ); ?>
        </div>
    </div>
</section>
