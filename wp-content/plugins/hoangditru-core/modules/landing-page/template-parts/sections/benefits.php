<?php
$rows = get_post_meta( get_the_ID(), 'lpb_benefits', true ) ?: [];
if ( empty( $rows ) ) return;
?>
<section class="lpb-section lpb-benefits">
    <div class="lpb-container">
        <h2 class="lpb-section__title">Quyền lợi</h2>
        <div class="lpb-benefits__grid">
            <?php foreach ( $rows as $row ) :
                $img  = $row['image'] ?? '';
                $desc = $row['desc']  ?? '';
                if ( ! $img && ! $desc ) continue;
            ?>
            <div class="lpb-benefits__item">
                <?php if ( $img ) : ?>
                    <div class="lpb-benefits__img-wrap">
                        <img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy">
                    </div>
                <?php endif; ?>
                <?php if ( $desc ) : ?>
                    <p class="lpb-benefits__desc"><?php echo nl2br( esc_html( $desc ) ); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
