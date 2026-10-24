<?php
$content = get_the_content();
if ( empty( trim( $content ) ) ) return;
?>
<section class="lpb-faqs">
    <div class="lpb-container">
        <h2 class="lpb-section__title">FAQs</h2>
        <div class="lpb-faqs__content">
            <?php echo apply_filters( 'the_content', $content ); ?>
        </div>
    </div>
</section>
