<?php
defined( 'ABSPATH' ) || exit;
$ids = get_post_meta( get_the_ID(), '_ci_news', true ) ?: [];
if ( empty( $ids ) ) return;
?>
<section class="ci-section ci-posts-section ci-news-section">
    <div class="ci-container">
        <h2 class="ci-section-title">Tin tức</h2>
        <div class="ci-posts-grid">
            <?php foreach ( $ids as $pid ) :
                $p = get_post( $pid );
                if ( ! $p || $p->post_status !== 'publish' ) continue;
            ?>
                <article class="ci-post-card">
                    <?php if ( has_post_thumbnail( $p->ID ) ) : ?>
                        <a href="<?php echo get_permalink( $p->ID ); ?>" class="ci-post-card__img">
                            <?php echo get_the_post_thumbnail( $p->ID, 'medium' ); ?>
                        </a>
                    <?php endif; ?>
                    <div class="ci-post-card__body">
                        <h3 class="ci-post-card__title">
                            <a href="<?php echo get_permalink( $p->ID ); ?>"><?php echo esc_html( $p->post_title ); ?></a>
                        </h3>
                        <p class="ci-post-card__excerpt"><?php echo wp_trim_words( $p->post_excerpt ?: $p->post_content, 20 ); ?></p>
                        <span class="ci-post-card__date"><?php echo get_the_date( 'd/m/Y', $p ); ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
