<?php
/**
 * Custom category.php template
 */

get_header();

$category = get_queried_object();
$cat_id   = $category->term_id;

$pinned_post_id = get_term_meta( $cat_id, '_pinned_post_id', true );
$pinned_post    = $pinned_post_id ? get_post( $pinned_post_id ) : null;

$exclude_ids = $pinned_post ? array( $pinned_post_id ) : array();

$latest_posts_query = new WP_Query( array(
    'cat'            => $cat_id,
    'posts_per_page' => 9,
    'post__not_in'   => $exclude_ids,
    'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
) );
?>

<style>
/* =============================================
   CATEGORY PAGE
   ============================================= */

.cat-page-wrapper {
    padding: 0 0 60px;
}

/* ── BOXED CONTAINER ── */
.cat-boxed {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ── CATEGORY TITLE ── */
.cat-page-title {
    text-align: center;
    padding: 20px 0 36px;
}
.cat-page-title h1 {
    margin: 0;
}

/* ── SECTION 1: FEATURED POST ── */
.section-featured {
    padding-bottom: 48px;
}

.featured-post-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 420px;
    border: 1px solid #e5e5e5;
    overflow: hidden;
}

/* Left: image */
.featured-image-col {
    position: relative;
    overflow: hidden;
}
.featured-image-col a {
    display: block;
    height: 100%;
}
.featured-image-col img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
}
.featured-post-grid:hover .featured-image-col img {
    transform: scale(1.04);
}

/* Tag overlay - styled by my_template_tag() */

/* Right: content */
.featured-content-col {
    padding: 40px 36px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 16px;
    border-left: 1px solid #e5e5e5;
    background: #fff;
}
.featured-content-col h3 {
    margin: 0;
}
.featured-content-col h3 a {
    color: inherit;
    text-decoration: none;
}
.featured-content-col h3 a:hover { color: var(--hdt-accent); }

.post-meta-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #888;
}
.post-meta-date svg {
    width: 14px;
    height: 14px;
    color: var(--hdt-accent);
    flex-shrink: 0;
}

.featured-excerpt {
    font-size: 1em !important;
    line-height: 1.7;
    color: #000 !important;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.read-more-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--hdt-accent);
    text-decoration: none;
    width: fit-content;
    transition: gap 0.2s;
}
.read-more-link:hover { gap: 10px; }



/* ── SECTION 2: LATEST POSTS ── */
.posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}

.post-card {
    display: flex;
    flex-direction: column;
    border: 1px solid #e5e5e5;
    overflow: hidden;
    background: #fff;
    transition: box-shadow 0.25s ease, transform 0.25s ease;
}
.post-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.09);
    transform: translateY(-3px);
}

.post-card-image {
    position: relative;
    overflow: hidden;
    aspect-ratio: 16/10;
}
.post-card-image a { display: block; height: 100%; }
.post-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}
.post-card:hover .post-card-image img { transform: scale(1.05); }

.post-card-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}
.post-card-body h3 {
    margin: 0;
    font-size: 1em !important;
}
.post-card-body h3 a {
    color: inherit;
    text-decoration: none;
    font-size: 1em !important;
}
.post-card-body h3 a:hover { color: var(--hdt-accent); }

.post-card-desc {
    font-size: 1em !important;
    line-height: 1.65;
    color: #000 !important;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}

/* ── PAGINATION ── */
.cat-pagination {
    margin-top: 48px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
}
.cat-pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    font-size: 14px;
    color: #555;
    text-decoration: none;
    border: 1px solid #ddd;
    background: #fff;
    transition: all 0.2s;
}
.cat-pagination .page-numbers:hover,
.cat-pagination .page-numbers.current {
    background: #2d2d2d;
    border-color: #2d2d2d;
    color: #fff;
}
.cat-pagination .page-numbers.dots {
    border: none;
    background: transparent;
}

/* No posts */
.no-posts-msg {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px;
    color: #999;
    font-size: 14px;
}

/* ── RESPONSIVE ── */
@media (max-width: 1024px) {
    .posts-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .cat-breadcrumb { width: 100%; border-radius: 0; }
    .featured-post-grid { grid-template-columns: 1fr; }
    .featured-image-col { aspect-ratio: 16/9; }
    .featured-content-col {
        padding: 24px 20px;
        border-left: none;
        border-top: 1px solid #e5e5e5;
    }
    .posts-grid { grid-template-columns: 1fr; }
}
</style>

<div class="cat-page-wrapper">

    <?php if ( function_exists( 'sbc_category' ) ) sbc_category( $category ); ?>

    <div class="cat-boxed">

        <!-- TITLE -->
        <div class="cat-page-title">
            <h1><?php echo esc_html( $category->name ); ?></h1>
        </div>

        <!-- ========== SECTION 1: PINNED POST ========== -->
        <?php if ( $pinned_post && 'publish' === $pinned_post->post_status ) :
            $pinned_thumb = get_the_post_thumbnail_url( $pinned_post->ID, 'large' );
            $pinned_cats  = get_the_category( $pinned_post->ID );
            $pinned_tag   = $pinned_cats ? $pinned_cats[0]->name : $category->name;
            $pinned_date  = date_i18n( 'd/m/Y', strtotime( $pinned_post->post_date ) );
            $pinned_exc   = get_the_excerpt( $pinned_post );
            if ( ! $pinned_exc ) {
                $pinned_exc = wp_trim_words( strip_shortcodes( $pinned_post->post_content ), 40, '…' );
            }
        ?>
        <section class="section-featured">

            <div class="featured-post-grid">

                <!-- Left: Image -->
                <div class="featured-image-col">
                    <a href="<?php echo get_permalink( $pinned_post->ID ); ?>">
                        <?php if ( $pinned_thumb ) : ?>
                            <img src="<?php echo esc_url( $pinned_thumb ); ?>"
                                 alt="<?php echo esc_attr( $pinned_post->post_title ); ?>"
                                 loading="lazy">
                        <?php else : ?>
                            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/placeholder.jpg' ); ?>"
                                 alt="" loading="lazy">
                        <?php endif; ?>
                    </a>
                    <?php if ( function_exists( 'my_template_tag' ) ) my_template_tag( $pinned_post->ID ); ?>
                </div>

                <!-- Right: Content -->
                <div class="featured-content-col">
                    <h3>
                        <a href="<?php echo get_permalink( $pinned_post->ID ); ?>">
                            <?php echo esc_html( $pinned_post->post_title ); ?>
                        </a>
                    </h3>
                    <div class="post-meta-date">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span><?php echo esc_html( $pinned_date ); ?></span>
                    </div>
                    <p class="featured-excerpt"><?php echo esc_html( $pinned_exc ); ?></p>
                    <a class="read-more-link" href="<?php echo get_permalink( $pinned_post->ID ); ?>">
                        Đọc thêm
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>

            </div>

        </section>
        <?php endif; ?>

        <!-- ========== SECTION 2: LATEST 9 POSTS ========== -->
        <section class="section-latest">

            <div class="posts-grid">

                <?php if ( $latest_posts_query->have_posts() ) :
                    while ( $latest_posts_query->have_posts() ) :
                        $latest_posts_query->the_post();
                        $thumb     = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
                        $post_cats = get_the_category();
                        $post_tag  = $post_cats ? $post_cats[0]->name : $category->name;
                        $post_date = get_the_date( 'd/m/Y' );
                        $excerpt   = get_the_excerpt();
                        if ( ! $excerpt ) {
                            $excerpt = wp_trim_words( strip_shortcodes( get_the_content() ), 25, '…' );
                        }
                ?>

                <article class="post-card">
                    <div class="post-card-image">
                        <a href="<?php the_permalink(); ?>">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>"
                                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                                     loading="lazy">
                            <?php else : ?>
                                <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/placeholder.jpg' ); ?>"
                                     alt="" loading="lazy">
                            <?php endif; ?>
                        </a>
                        <?php if ( function_exists( 'my_template_tag' ) ) my_template_tag( get_the_ID() ); ?>
                    </div>

                    <div class="post-card-body">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <div class="post-meta-date">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span><?php echo esc_html( $post_date ); ?></span>
                        </div>
                        <p class="post-card-desc"><?php echo esc_html( $excerpt ); ?></p>
                    </div>
                </article>

                <?php endwhile; wp_reset_postdata();
                else : ?>
                <div class="no-posts-msg">Chưa có bài viết nào trong danh mục này.</div>
                <?php endif; ?>

            </div>

            <!-- PAGINATION -->
            <?php
            $pagination = paginate_links( array(
                'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                'format'    => '?paged=%#%',
                'current'   => max( 1, get_query_var( 'paged' ) ),
                'total'     => $latest_posts_query->max_num_pages,
                'type'      => 'array',
                'prev_text' => '‹',
                'next_text' => '›',
            ) );
            if ( $pagination ) : ?>
            <nav class="cat-pagination" aria-label="Phân trang">
                <?php foreach ( $pagination as $page ) echo $page; ?>
            </nav>
            <?php endif; ?>

        </section>

    </div><!-- /.cat-boxed -->
</div><!-- /.cat-page-wrapper -->

<?php get_footer(); ?>
