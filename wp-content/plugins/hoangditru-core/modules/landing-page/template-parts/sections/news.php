<?php
$ids  = get_post_meta( get_the_ID(), 'lpb_news_ids', true ) ?: [];
$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => empty( $ids ) ? 3 : count( $ids ),
];
if ( ! empty( $ids ) ) $args['post__in'] = $ids;
$news = get_posts( $args );
if ( empty( $news ) ) return;
?>
<section class="lpb-section lpb-news">
    <div class="lpb-container">
        <h2 class="lpb-section__title">Tin tức</h2>
        <div class="lpb-news__grid">
            <?php foreach ( $news as $post ) : setup_postdata( $post ); ?>
            <article class="lpb-news__item">
                <?php if ( has_post_thumbnail( $post ) ) : ?>
                    <a href="<?php echo get_permalink( $post ); ?>" class="lpb-news__thumb">
                        <?php echo get_the_post_thumbnail( $post, 'medium', [ 'loading' => 'lazy' ] ); ?>
                    </a>
                <?php endif; ?>
                <div class="lpb-news__body">
                    <p class="lpb-news__date"><?php echo get_the_date( 'd/m/Y', $post ); ?></p>
                    <h3 class="lpb-news__title">
                        <a href="<?php echo get_permalink( $post ); ?>"><?php echo get_the_title( $post ); ?></a>
                    </h3>
                    <p class="lpb-news__excerpt"><?php echo wp_trim_words( get_the_excerpt( $post ), 20, '...' ); ?></p>
                </div>
            </article>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
