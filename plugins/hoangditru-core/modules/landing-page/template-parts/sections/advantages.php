<?php
$rows = get_post_meta( get_the_ID(), 'lpb_advantages', true ) ?: [];
if ( empty( $rows ) ) return;
?>
<section class="lpb-section lpb-advantages">
    <div class="lpb-container">
        <h2 class="lpb-section__title">Ưu điểm nổi bật</h2>
        <ul class="lpb-advantages__list">
            <?php foreach ( $rows as $row ) :
                $icon = $row['icon'] ?? '';
                $text = $row['text'] ?? '';
                if ( ! $text ) continue;
            ?>
            <li class="lpb-advantages__item">
                <?php if ( $icon ) : ?>
                    <?php echo hdtc_img( $icon, null, array( 'class' => 'lpb-advantages__icon', 'alt' => '', 'loading' => 'lazy', 'sizes' => '40px' ) ); ?>
                <?php endif; ?>
                <span class="lpb-advantages__text"><?php echo esc_html( $text ); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
