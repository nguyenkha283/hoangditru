<?php
$rows = get_post_meta( get_the_ID(), 'lpb_conditions', true ) ?: [];
if ( empty( $rows ) ) return;
?>
<section class="lpb-section lpb-conditions">
    <div class="lpb-container">
        <h2 class="lpb-section__title">Điều kiện chương trình</h2>
        <div class="lpb-accordion">
            <?php foreach ( $rows as $i => $row ) :
                $title   = $row['title']   ?? '';
                $content = $row['content'] ?? '';
                if ( ! $title ) continue;
                $open = $i === 0;
            ?>
            <div class="lpb-accordion__item">
                <button class="lpb-accordion__trigger"
                        aria-expanded="<?php echo $open ? 'true' : 'false'; ?>"
                        aria-controls="lpb-cond-<?php echo $i; ?>">
                    <span><?php echo esc_html( $title ); ?></span>
                    <svg class="lpb-accordion__arrow" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="lpb-accordion__body <?php echo $open ? 'is-open' : ''; ?>"
                     id="lpb-cond-<?php echo $i; ?>">
                    <div class="lpb-accordion__content">
                        <?php echo wp_kses_post( $content ); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
