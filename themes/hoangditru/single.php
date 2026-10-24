<?php
/**
 * single.php — Template hiển thị bài viết
 */

get_header();

while ( have_posts() ) :
    the_post();

    $post_id      = get_the_ID();
    $post_cats    = get_the_category( $post_id );
    $cat          = $post_cats ? $post_cats[0] : null;
    $author_id    = get_the_author_meta( 'ID' );
    $author_name  = get_the_author_meta( 'display_name' );
    $author_url   = get_author_posts_url( $author_id );
    $author_avatar= get_avatar_url( $author_id, array( 'size' => 36 ) );
    $date_publish = get_the_date( 'd/m/Y' );
    $date_modify  = get_the_modified_date( 'd/m/Y' );
    $excerpt      = get_the_excerpt();
    // Use post excerpt field in full — no truncation

    // ── Build Table of Contents ──────────────────────────────
    $content_raw = get_the_content();
    preg_match_all( '/<(h[2-4])[^>]*>(.*?)<\/h[2-4]>/is', $content_raw, $headings );

    $toc_items = array();
    $slug_count = array();

    foreach ( $headings[2] as $i => $heading_text ) {
        $level      = $headings[1][ $i ]; // h2 / h3 / h4
        $clean_text = wp_strip_all_tags( $heading_text );
        $slug_base  = sanitize_title( $clean_text );
        // Handle duplicate slugs
        if ( isset( $slug_count[ $slug_base ] ) ) {
            $slug_count[ $slug_base ]++;
            $slug = $slug_base . '-' . $slug_count[ $slug_base ];
        } else {
            $slug_count[ $slug_base ] = 0;
            $slug = $slug_base;
        }
        $toc_items[] = array(
            'level' => (int) substr( $level, 1 ), // 2 / 3 / 4
            'text'  => $clean_text,
            'id'    => $slug,
        );
    }

    // Inject IDs into headings in content
    $content_with_ids = $content_raw;
    $used_slugs = array();
    $content_with_ids = preg_replace_callback(
        '/<(h[2-4])([^>]*)>(.*?)<\/h[2-4]>/is',
        function( $m ) use ( &$used_slugs ) {
            $tag        = $m[1];
            $attrs      = $m[2];
            $inner      = $m[3];
            $clean      = wp_strip_all_tags( $inner );
            $slug_base  = sanitize_title( $clean );
            if ( isset( $used_slugs[ $slug_base ] ) ) {
                $used_slugs[ $slug_base ]++;
                $slug = $slug_base . '-' . $used_slugs[ $slug_base ];
            } else {
                $used_slugs[ $slug_base ] = 0;
                $slug = $slug_base;
            }
            // Remove existing id attr to avoid duplicates
            $attrs = preg_replace( '/\s*id=["\'][^"\']*["\']/', '', $attrs );
            return "<{$tag} id=\"{$slug}\"{$attrs}>{$inner}</{$tag}>";
        },
        $content_raw
    );
?>

<style>
/* =============================================
   SINGLE POST PAGE
   ============================================= */

.single-page-wrapper {
    padding: 0 0 60px;
}

/* ── BOXED ── */
.single-boxed {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ── POST TITLE ── */
.single-post-title {
    margin: 1em 0 20px;
    line-height: 1.4;
}

/* ── META ── */
.single-post-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding: 14px 0;
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
    margin-bottom: 24px;
}
.single-meta-author {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: inherit;
}
.single-meta-author img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.single-meta-author-name {
    font-size: 13px;
    font-weight: 600;
    color: #222;
}
.single-meta-author-label {
    font-size: 11px;
    color: #999;
}
.single-meta-divider {
    width: 1px;
    height: 28px;
    background: #e0e0e0;
}
.single-meta-dates {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.single-meta-date-row {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #888;
}
.single-meta-date-row svg {
    width: 13px;
    height: 13px;
    color: var(--hdt-accent);
    flex-shrink: 0;
}
.single-meta-date-row strong {
    color: #555;
    font-weight: 500;
}

/* ── DESCRIPTION ── */
.single-post-desc {
    font-size: 1em !important;
    color: #000 !important;
    line-height: 1.75;
    margin: 0 0 32px;
    padding: 16px 20px;
    border-left: 3px solid var(--hdt-accent);
    background: #fafafa;
}

/* ── TABLE OF CONTENTS ── */
.single-toc {
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    margin-bottom: 36px;
    overflow: hidden;
}
.single-toc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: #f5f5f5;
    border-bottom: 1px solid #e5e5e5;
    cursor: pointer;
    user-select: none;
}
.single-toc-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #222;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.single-toc-title svg {
    width: 15px;
    height: 15px;
    color: var(--hdt-accent);
}
.single-toc-toggle {
    font-size: 18px;
    color: #999;
    line-height: 1;
    transition: transform 0.25s;
}
.single-toc.is-collapsed .single-toc-toggle {
    transform: rotate(-90deg);
}
.single-toc-body {
    padding: 16px 18px;
    transition: all 0.25s ease;
}
.single-toc.is-collapsed .single-toc-body {
    display: none;
}
.single-toc-list {
    list-style: none;
    margin: 0;
    padding: 0;
    counter-reset: toc-counter;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.single-toc-list li {
    display: flex;
    align-items: baseline;
    gap: 8px;
    line-height: 1.4;
}
.single-toc-list li a {
    font-size: 13.5px;
    color: #333;
    text-decoration: none;
    transition: color 0.2s;
}
.single-toc-list li a:hover { color: var(--hdt-accent); }
/* Level indentation */
.toc-level-2 { padding-left: 0; }
.toc-level-3 { padding-left: 18px; }
.toc-level-4 { padding-left: 36px; }
/* Number bullet */
.toc-level-2 > a::before {
    content: counter(toc-h2) ". ";
    counter-increment: toc-h2;
    color: var(--hdt-accent);
    font-weight: 700;
}
.single-toc-list { counter-reset: toc-h2; }

/* ── POST CONTENT ── */
.single-post-content {
    line-height: 1.8;
    color: #222;
}
.single-post-content h2,
.single-post-content h3,
.single-post-content h4 {
    margin-top: 2em;
    margin-bottom: 0.6em;
    scroll-margin-top: 80px;
}
.single-post-content p { margin-bottom: 1.2em; }
.single-post-content img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 1.5em auto;
    border-radius: 2px;
}
.single-post-content a {
    color: var(--hdt-accent);
    text-decoration: underline;
}
.single-post-content ul,
.single-post-content ol {
    padding-left: 1.5em;
    margin-bottom: 1.2em;
}
.single-post-content blockquote {
    border-left: 3px solid var(--hdt-accent);
    margin: 1.5em 0;
    padding: 12px 20px;
    background: #fafafa;
    color: #555;
    font-style: italic;
}
.single-post-content table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5em;
}
.single-post-content th,
.single-post-content td {
    border: 1px solid #e0e0e0;
    padding: 8px 12px;
    text-align: left;
}
.single-post-content th { background: #f5f5f5; font-weight: 700; }

/* ── POST CONTENT: force 1em on all elements ── */
.single-post-content * {
    font-size: 1em !important;
}
.single-post-content h2,
.single-post-content h3,
.single-post-content h4 {
    font-size: 1em !important;
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .single-post-meta { gap: 10px; }
    .single-meta-divider { display: none; }
}
</style>

<div class="single-page-wrapper">

    <?php if ( function_exists( 'sbc_single_post' ) ) sbc_single_post(); ?>

    <div class="single-boxed">

        <!-- TITLE -->
        <h3 class="single-post-title"><?php the_title(); ?></h3>

        <!-- META -->
        <div class="single-post-meta">
            <a class="single-meta-author" href="<?php echo esc_url( $author_url ); ?>">
                <img src="<?php echo esc_url( $author_avatar ); ?>"
                     alt="<?php echo esc_attr( $author_name ); ?>">
                <div>
                    <div class="single-meta-author-label">Người đăng</div>
                    <div class="single-meta-author-name"><?php echo esc_html( $author_name ); ?></div>
                </div>
            </a>

            <div class="single-meta-divider"></div>

            <div class="single-meta-dates">
                <div class="single-meta-date-row">
                    <!-- Publish icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <strong>Đăng:</strong>
                    <span><?php echo esc_html( $date_publish ); ?></span>
                </div>
                <?php if ( $date_modify !== $date_publish ) : ?>
                <div class="single-meta-date-row">
                    <!-- Update icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                    <strong>Cập nhật:</strong>
                    <span><?php echo esc_html( $date_modify ); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DESCRIPTION 
        <?php //if ( $excerpt ) : ?>
        <p class="single-post-desc"><?php //echo esc_html( $excerpt ); ?></p>
        <?php //endif; ?>

        <!-- TABLE OF CONTENTS 
        <?php //if ( count( $toc_items ) >= 2 ) : ?>
        <div class="single-toc" id="single-toc">
            <div class="single-toc-header" id="toc-toggle-btn">
                <div class="single-toc-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                    Mục lục bài viết
                </div>
                <span class="single-toc-toggle">›</span>
            </div>
            <div class="single-toc-body">
                <ul class="single-toc-list">
                    <?php //foreach ( $toc_items as $item ) : ?>
                    <li class="toc-level-<?php //echo esc_attr( $item['level'] ); ?>">
                        <a href="#<?php //echo esc_attr( $item['id'] ); ?>">
                            <?php //echo esc_html( $item['text'] ); ?>
                        </a>
                    </li>
                    <?php //endforeach; ?>
                </ul>
            </div>
        </div>
        <?php //endif; ?> -->

        <!-- POST CONTENT -->
        <div class="single-post-content">
            <?php echo apply_filters( 'the_content', $content_with_ids ); ?>
        </div>

    </div><!-- /.single-boxed -->
</div><!-- /.single-page-wrapper -->

<script>
(function() {
    // ── Smooth scroll for TOC links ──────────────────────────
    document.querySelectorAll('.single-toc-list a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (!target) return;
            e.preventDefault();
            var offset = 80; // adjust if sticky header
            var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        });
    });

    // ── Toggle collapse ─────────────────────────────────────
    var tocBtn = document.getElementById('toc-toggle-btn');
    var toc    = document.getElementById('single-toc');
    if (tocBtn && toc) {
        tocBtn.addEventListener('click', function() {
            toc.classList.toggle('is-collapsed');
        });
    }
})();
</script>

<?php
endwhile;

get_footer();
?>
