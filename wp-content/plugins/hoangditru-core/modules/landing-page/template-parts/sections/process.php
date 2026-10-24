<?php
$rows = get_post_meta( get_the_ID(), 'lpb_process', true ) ?: [];
if ( empty( $rows ) ) return;
$total = count( $rows );
?>
<section class="lpb-section lpb-process">
    <div class="lpb-container">
        <h2 class="lpb-section__title">Quy trình hồ sơ</h2>
        <div class="lpb-timeline" data-total="<?php echo $total; ?>">
            <?php foreach ( $rows as $i => $row ) :
                $title    = $row['title'] ?? '';
                $desc     = $row['desc']  ?? '';
                $num      = $i + 1;
                $is_first = ( $i === 0 );
                $is_last  = ( $num === $total );
                $is_solid = $is_first || $is_last;
            ?>
            <div class="lpb-timeline__step <?php echo $is_solid ? 'is-solid' : 'is-hollow'; ?> <?php echo $is_last ? 'is-last' : ''; ?>">
                <?php if ( ! $is_last ) : ?>
                    <div class="lpb-timeline__line" aria-hidden="true"></div>
                <?php endif; ?>
                <div class="lpb-timeline__node-wrap">
                    <div class="lpb-timeline__node"></div>
                    <div class="lpb-timeline__tick" aria-hidden="true"></div>
                </div>
                <div class="lpb-timeline__label">
                    <?php if ( $title ) : ?><p class="lpb-timeline__title"><?php echo esc_html( $title ); ?></p><?php endif; ?>
                    <?php if ( $desc )  : ?><p class="lpb-timeline__desc"><?php echo nl2br( esc_html( $desc ) ); ?></p><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
