<?php
/**
 * Template Name: Trang Chủ
 * Template Post Type: page
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tc = wp_parse_args( get_option( 'trang_chu_settings', [] ), [
    's3_title_black'  => 'CHƯƠNG TRÌNH THẺ',
    's3_title_orange' => 'GOLDEN VISA',
    's3_post_ids'     => [],
    's4_title_black'  => 'CHƯƠNG TRÌNH',
    's4_title_orange' => 'QUỐC TỊCH',
    's4_post_ids'     => [],
    's5_title'        => 'DỰ ÁN TIÊU BIỂU',
    's5_bds_ids'      => [],
    's6_title'        => 'VỀ CHÚNG TÔI',
    's6_desc'         => '',
    's6_image'        => 0,
    's7_title'        => 'KIẾN THỨC & TIN TỨC',
    's7_post_ids'     => [],
]);

function tc_cim( $key ) {
    static $data = null;
    if ( $data === null ) $data = get_option( 'cim_contact_info', [] );
    return $data[ $key ] ?? '';
}
function tc_cim_img( $id, $size = 'full' ) {
    return $id ? wp_get_attachment_image_url( (int)$id, $size ) : '';
}
function tc_social_icon( $key ) {
    $icon_id = tc_cim( $key . '_icon' );
    if ( $icon_id ) {
        $url = tc_cim_img( $icon_id, 'thumbnail' );
        return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $key ) . '">';
    }
    $defaults = ['youtube' => '▶', 'facebook' => 'f', 'tiktok' => '♪'];
    return '<span class="tc-social-letter">' . ( $defaults[ $key ] ?? '' ) . '</span>';
}

get_header();

// Dữ liệu dùng chung
$phone        = tc_cim('phone');
$phone_tel    = preg_replace('/\D/', '', $phone );
$avatar_url   = tc_cim_img( tc_cim('avatar'), 'medium' );
$full_name    = tc_cim('full_name');
$bio_raw      = tc_cim('bio');
$email        = tc_cim('email');
$youtube_url  = tc_cim('youtube');
$facebook_url = tc_cim('facebook');
$tiktok_url   = tc_cim('tiktok');

$bio_limit    = 365;
$bio_short    = '';
$bio_rest     = '';
$bio_has_more = false;
if ( $bio_raw ) {
    $bio_plain = wp_strip_all_tags( $bio_raw );
    if ( mb_strlen( $bio_plain ) > $bio_limit ) {
        $bio_has_more = true;
        $bio_short    = esc_html( mb_substr( $bio_plain, 0, $bio_limit ) );
        $bio_rest     = esc_html( mb_substr( $bio_plain, $bio_limit ) );
    } else {
        $bio_short = esc_html( $bio_plain );
    }
}
?>
<div class="tc-page" id="trang-chu">

<?php
/* ═══════════════════════════════════════════════════════════════
   .tc-page dùng flex-direction:column để CSS order hoạt động.

   Thứ tự DOM:   cover(order:1) → contact(order:2) → nav(order:3) → sections(order:4+)
   Desktop hiển thị: cover → contact → nav (sticky) → sections  ✓
   Mobile hiển thị:  nav(order:0, fixed) → cover → contact → sections  ✓
═══════════════════════════════════════════════════════════════ */

/* SECTION 1 — ẢNH BÌA */
$cover_url = tc_cim_img( tc_cim('cover_image'), 'full' );
?>
<section class="tc-cover" aria-label="Ảnh bìa">
    <div class="tc-cover__img<?php echo $cover_url ? '' : ' tc-cover__img--empty'; ?>"
         <?php if ( $cover_url ) echo 'style="background-image:url(\'' . esc_url($cover_url) . '\')"'; ?>
         role="img" aria-label="Ảnh bìa trang"></div>
</section>

<?php
/* SECTION 2 — THÔNG TIN LIÊN HỆ */
?>
<section class="tc-contact" aria-label="Thông tin liên hệ">
    <div class="tc-boxed">
        <div class="tc-contact__inner">
            <div class="tc-contact__avatar-col">
                <?php if ( $avatar_url ) : ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($full_name); ?>" class="tc-contact__avatar">
                <?php else : ?>
                    <div class="tc-contact__avatar tc-contact__avatar--placeholder"></div>
                <?php endif; ?>
            </div>
            <div class="tc-contact__info-col">
                <?php if ( $full_name ) : ?>
                    <h1 class="tc-contact__name"><?php echo esc_html($full_name); ?></h1>
                <?php endif; ?>
                <?php if ( $bio_short ) : ?>
                <div class="tc-contact__bio">
                    <span class="tc-bio__text"><?php echo $bio_short; ?><?php if ( $bio_has_more ) : ?><span class="tc-bio__rest" hidden><?php echo $bio_rest; ?></span><span class="tc-bio__dots">…</span><?php endif; ?></span>
                    <?php if ( $bio_has_more ) : ?>
                    <a href="#" class="tc-bio__toggle" aria-expanded="false" role="button">Xem thêm</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="tc-contact__details">
                    <?php if ( $email ) : ?>
                    <span class="tc-contact__detail-item">
                        <span class="tc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg></span>
                        <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                    </span>
                    <?php endif; ?>
                    <?php if ( $phone ) : ?>
                    <span class="tc-contact__detail-item">
                        <span class="tc-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 01.22 1.18 2 2 0 012.2 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.09a16 16 0 006 6l.66-.66a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg></span>
                        <a href="tel:<?php echo esc_attr($phone_tel); ?>"><?php echo esc_html($phone); ?></a>
                    </span>
                    <?php endif; ?>
                    <?php if ( $facebook_url || $youtube_url || $tiktok_url ) : ?>
                    <span class="tc-contact__divider" aria-hidden="true"></span>
                    <div class="tc-social-row">
                        <?php if ( $facebook_url ) : ?>
                        <a href="<?php echo esc_url($facebook_url); ?>" class="tc-social tc-social--facebook" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><?php echo tc_social_icon('facebook'); ?></a>
                        <?php endif; ?>
                        <?php if ( $youtube_url ) : ?>
                        <a href="<?php echo esc_url($youtube_url); ?>" class="tc-social tc-social--youtube" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><?php echo tc_social_icon('youtube'); ?></a>
                        <?php endif; ?>
                        <?php if ( $tiktok_url ) : ?>
                        <a href="<?php echo esc_url($tiktok_url); ?>" class="tc-social tc-social--tiktok" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><?php echo tc_social_icon('tiktok'); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php /* NAV — trong DOM sau contact, trước các section nội dung */ ?>
<div id="tc-nav-bar" class="tc-nav-bar" role="navigation" aria-label="Menu chính">
    <div class="tc-boxed tc-nav__inner">
        <?php
        global $tc_rendering_custom_nav;
        $tc_rendering_custom_nav = true;
        wp_nav_menu([
            'theme_location' => 'primary',
            'menu_class'     => 'tc-nav__list',
            'container'      => false,
            'fallback_cb'    => static function () {
                echo '<ul class="tc-nav__list"><li><a href="' . esc_url( home_url('/') ) . '">Trang chủ</a></li></ul>';
            },
        ]);
        $tc_rendering_custom_nav = false;
        ?>
        <div class="tc-nav__toggle" role="button" aria-label="Mở menu" aria-expanded="false" tabindex="0">
            <span></span><span></span><span></span>
        </div>
        <?php if ( $phone ) : ?>
        <a href="tel:<?php echo esc_attr( $phone_tel ); ?>" class="tc-nav__hotline" aria-label="Gọi hotline <?php echo esc_attr($phone); ?>">
            <span class="tc-nav__hotline-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24 11.47 11.47 0 003.58.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.47 11.47 0 00.57 3.57 1 1 0 01-.25 1.02l-2.2 2.2z"/></svg>
            </span>
            <span class="tc-nav__hotline-number"><?php echo esc_html($phone); ?></span>
        </a>
        <?php endif; ?>
    </div>
</div>
<div id="tc-nav-sentinel" aria-hidden="true"></div>

<?php
/* ═══════════════════════════════════════════════════════════════
   SECTION 3 — GOLDEN VISA
═══════════════════════════════════════════════════════════════ */
$s3_ids = array_filter( (array) $tc['s3_post_ids'] );
$s3_query = new WP_Query( $s3_ids
    ? [ 'post_type'=>'golden_visa', 'post__in'=>array_slice($s3_ids,0,6), 'orderby'=>'post__in', 'posts_per_page'=>6, 'post_status'=>'publish' ]
    : [ 'post_type'=>'golden_visa', 'posts_per_page'=>6, 'post_status'=>'publish', 'orderby'=>'date', 'order'=>'DESC' ]
);
?>
<section class="tc-section tc-section--orange-bg">
    <div class="tc-boxed">
        <h1 class="tc-section__title">
            <span class="tc-title--black"><?php echo esc_html($tc['s3_title_black']); ?></span>
            <?php if ( $tc['s3_title_orange'] ) : ?><span class="tc-title--orange"> <?php echo esc_html($tc['s3_title_orange']); ?></span><?php endif; ?>
        </h1>
        <?php if ( $s3_query->have_posts() ) : ?>
        <div class="tc-grid-2x3">
            <?php while ( $s3_query->have_posts() ) : $s3_query->the_post(); $thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>
            <article class="tc-program-card">
                <a href="<?php the_permalink(); ?>" class="tc-program-card__link">
                    <div class="tc-program-card__bg<?php echo $thumb ? '' : ' tc-program-card__bg--empty'; ?>" <?php if($thumb) echo 'style="background-image:url(\''.esc_url($thumb).'\')"'; ?>></div>
                    <div class="tc-program-card__overlay"></div>
                    <h3 class="tc-program-card__title"><?php the_title(); ?></h3>
                </a>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php else : ?><p class="tc-no-posts">Chưa có bài viết Golden Visa nào.</p><?php endif; ?>
    </div>
</section>

<?php
/* ═══════════════════════════════════════════════════════════════
   SECTION 4 — QUỐC TỊCH
═══════════════════════════════════════════════════════════════ */
$s4_ids = array_filter( (array) $tc['s4_post_ids'] );
$s4_query = new WP_Query( $s4_ids
    ? [ 'post_type'=>'citizenship_invest', 'post__in'=>array_slice($s4_ids,0,6), 'orderby'=>'post__in', 'posts_per_page'=>6, 'post_status'=>'publish' ]
    : [ 'post_type'=>'citizenship_invest', 'posts_per_page'=>6, 'post_status'=>'publish', 'orderby'=>'date', 'order'=>'DESC' ]
);
?>
<section class="tc-section">
    <div class="tc-boxed">
        <h1 class="tc-section__title">
            <span class="tc-title--black"><?php echo esc_html($tc['s4_title_black']); ?></span>
            <?php if ( $tc['s4_title_orange'] ) : ?><span class="tc-title--orange"> <?php echo esc_html($tc['s4_title_orange']); ?></span><?php endif; ?>
        </h1>
        <?php if ( $s4_query->have_posts() ) : ?>
        <div class="tc-grid-2x3">
            <?php while ( $s4_query->have_posts() ) : $s4_query->the_post(); $thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>
            <article class="tc-program-card">
                <a href="<?php the_permalink(); ?>" class="tc-program-card__link">
                    <div class="tc-program-card__bg<?php echo $thumb ? '' : ' tc-program-card__bg--empty'; ?>" <?php if($thumb) echo 'style="background-image:url(\''.esc_url($thumb).'\')"'; ?>></div>
                    <div class="tc-program-card__overlay"></div>
                    <h3 class="tc-program-card__title"><?php the_title(); ?></h3>
                </a>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php else : ?><p class="tc-no-posts">Chưa có bài viết Quốc tịch nào.</p><?php endif; ?>
    </div>
</section>

<?php
/* ═══════════════════════════════════════════════════════════════
   SECTION 5 — DỰ ÁN TIÊU BIỂU (BĐS)
   Render ảnh trực tiếp bằng src thật — không phụ thuộc lazy-load
   của bds_render_card (dùng data-src).
═══════════════════════════════════════════════════════════════ */
$s5_ids   = array_filter( (array) $tc['s5_bds_ids'] );
$s5_posts = get_posts( $s5_ids
    ? [ 'post_type'=>'bat-dong-san', 'post__in'=>array_slice($s5_ids,0,4), 'orderby'=>'post__in', 'posts_per_page'=>4, 'post_status'=>'publish' ]
    : [ 'post_type'=>'bat-dong-san', 'posts_per_page'=>4, 'post_status'=>'publish', 'orderby'=>'date', 'order'=>'DESC' ]
);
?>
<section class="tc-section tc-section--orange-bg">
    <div class="tc-boxed">
        <h1 class="tc-section__title"><span class="tc-title--black"><?php echo esc_html($tc['s5_title']); ?></span></h1>
        <?php if ( ! empty($s5_posts) ) : ?>
        <div class="tc-bds-grid">
            <?php foreach ( $s5_posts as $bds ) :
                $thumb_url  = get_the_post_thumbnail_url( $bds->ID, 'large' );
                $dia_chi    = get_post_meta( $bds->ID, '_bds_dia_chi',    true );
                $khoang_gia = get_post_meta( $bds->ID, '_bds_khoang_gia', true );
                $loai_hinh  = get_post_meta( $bds->ID, '_bds_loai_hinh',  true );
                $link       = get_permalink( $bds->ID );
            ?>
            <article class="bds-du-an__item">
                <a href="<?php echo esc_url($link); ?>" class="bds-du-an__thumb-link">
                    <?php if ( $thumb_url ) : ?>
                        <img src="<?php echo esc_url($thumb_url); ?>"
                             alt="<?php echo esc_attr($bds->post_title); ?>"
                             class="bds-du-an__thumb loaded"
                             width="400" height="200" loading="lazy">
                    <?php else : ?>
                        <div class="bds-du-an__thumb bds-du-an__thumb--placeholder"></div>
                    <?php endif; ?>
                    <?php if ( $loai_hinh ) : ?>
                        <span class="bds-du-an__tag"><?php echo esc_html($loai_hinh); ?></span>
                    <?php endif; ?>
                </a>
                <div class="bds-du-an__body">
                    <h3 class="bds-du-an__name">
                        <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($bds->post_title); ?></a>
                    </h3>
                    <?php if ( $dia_chi ) : ?>
                    <p class="bds-du-an__address">
                        <svg viewBox="0 0 24 24" class="bds-du-an__icon" aria-hidden="true">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>
                        </svg>
                        <?php echo esc_html($dia_chi); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ( $khoang_gia ) : ?>
                    <p class="bds-du-an__price">
                        <svg viewBox="0 0 24 24" class="bds-du-an__icon" aria-hidden="true">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                        <?php echo esc_html($khoang_gia); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?><p class="tc-no-posts">Chưa có bất động sản nào.</p><?php endif; ?>
    </div>
</section>

<?php
/* ═══════════════════════════════════════════════════════════════
   SECTION 6 — GIỚI THIỆU
═══════════════════════════════════════════════════════════════ */
$s6_img_url = $tc['s6_image'] ? wp_get_attachment_image_url( $tc['s6_image'], 'large' ) : '';
?>
<section class="tc-section tc-intro">
    <div class="tc-boxed">
        <div class="tc-intro__inner">
            <div class="tc-intro__text-col">
                <?php if ( $tc['s6_title'] ) : ?><h2 class="tc-intro__title"><?php echo esc_html($tc['s6_title']); ?></h2><?php endif; ?>
                <?php if ( $tc['s6_desc']  ) : ?><div class="tc-intro__desc"><?php echo wp_kses_post($tc['s6_desc']); ?></div><?php endif; ?>
            </div>
            <div class="tc-intro__image-col">
                <?php if ( $s6_img_url ) : ?>
                <img src="<?php echo esc_url($s6_img_url); ?>" alt="<?php echo esc_attr($tc['s6_title']); ?>" class="tc-intro__img" loading="lazy">
                <?php else : ?>
                <div class="tc-intro__img-placeholder"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
/* ═══════════════════════════════════════════════════════════════
   SECTION 7 — TIN TỨC
═══════════════════════════════════════════════════════════════ */
$s7_selected = array_filter( array_map('absint', (array)($tc['s7_post_ids'] ?? []) ) );
$s7_posts    = [];
if ( ! empty( $s7_selected ) ) {
    $s7_posts = get_posts([ 'post_type'=>'post', 'post__in'=>$s7_selected, 'orderby'=>'post__in', 'posts_per_page'=>count($s7_selected), 'post_status'=>'publish' ]);
    if ( count($s7_posts) < 3 ) {
        $s7_posts = array_merge( $s7_posts, get_posts([ 'post_type'=>'post', 'posts_per_page'=>3-count($s7_posts), 'post_status'=>'publish', 'orderby'=>'date', 'order'=>'DESC', 'post__not_in'=>wp_list_pluck($s7_posts,'ID') ]) );
    }
} else {
    $s7_posts = get_posts([ 'post_type'=>'post', 'posts_per_page'=>3, 'post_status'=>'publish', 'orderby'=>'date', 'order'=>'DESC' ]);
}
?>
<section class="tc-section tc-news">
    <div class="tc-boxed">
        <h1 class="tc-section__title"><span class="tc-title--black"><?php echo esc_html($tc['s7_title']); ?></span></h1>
        <?php if ( ! empty($s7_posts) ) : ?>
        <div class="tc-news__grid">
            <?php foreach ( $s7_posts as $post ) : setup_postdata($post); ?>
            <article class="tc-news__item">
                <?php if ( has_post_thumbnail($post->ID) ) : ?>
                <a href="<?php echo get_permalink($post->ID); ?>" class="tc-news__thumb-link">
                    <?php echo get_the_post_thumbnail($post->ID, 'medium_large', ['class'=>'tc-news__thumb','loading'=>'lazy']); ?>
                </a>
                <?php endif; ?>
                <div class="tc-news__body">
                    <div class="tc-news__meta"><time datetime="<?php echo get_the_date('c',$post); ?>"><?php echo get_the_date('',$post); ?></time></div>
                    <h3 class="tc-news__title"><a href="<?php echo get_permalink($post->ID); ?>"><?php echo get_the_title($post->ID); ?></a></h3>
                    <p class="tc-news__excerpt"><?php echo wp_trim_words(get_the_excerpt($post->ID),20,'…'); ?></p>
                    <a href="<?php echo get_permalink($post->ID); ?>" class="tc-news__readmore">Đọc thêm →</a>
                </div>
            </article>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
        <?php else : ?><p class="tc-no-posts">Chưa có bài viết nào.</p><?php endif; ?>
    </div>
</section>

</div><!-- /.tc-page -->

<script>
(function () {
    'use strict';

    /* ── Bio toggle ── */
    var bioToggle = document.querySelector('.tc-bio__toggle');
    if (bioToggle) {
        bioToggle.addEventListener('click', function (e) {
            e.preventDefault();
            var expanded = this.getAttribute('aria-expanded') === 'true';
            var rest = document.querySelector('.tc-bio__rest');
            var dots = document.querySelector('.tc-bio__dots');
            if (!expanded) {
                rest && (rest.hidden = false);
                dots && (dots.hidden = true);
                this.textContent = 'Thu gọn';
                this.setAttribute('aria-expanded', 'true');
            } else {
                rest && (rest.hidden = true);
                dots && (dots.hidden = false);
                this.textContent = 'Xem thêm';
                this.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ── Sentinel — nav bottom không che nội dung, không cần bù height ── */
    var nav      = document.getElementById('tc-nav-bar');
    var sentinel = document.getElementById('tc-nav-sentinel');
    function syncSentinel() {
        if (!sentinel) return;
        sentinel.style.height = '0px';
    }
    syncSentinel();
    window.addEventListener('resize', syncSentinel);

    /* ── Init nav (dùng chung cho trang chủ và global) ── */
    initNav(nav);

    function initNav(navEl) {
        if (!navEl) return;

        var toggle  = navEl.querySelector('.tc-nav__toggle');
        var list    = navEl.querySelector('.tc-nav__list');
        if (!toggle || !list) return;

        /* Hamburger — dùng cả click và touchend để tương thích iOS */
        function toggleMenu(e) {
            e.preventDefault();
            e.stopPropagation();
            var open = list.classList.toggle('tc-nav--open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        toggle.addEventListener('click', toggleMenu);
        /* touchend cho iOS Safari */
        toggle.addEventListener('touchend', function(e) {
            e.preventDefault();
            toggleMenu(e);
        }, { passive: false });

        /* Sub-menu toggle — click vào link của item có sub-menu */
        list.querySelectorAll('.menu-item-has-children > a').forEach(function (a) {
            function toggleSub(e) {
                if (window.innerWidth > 480) return; /* chỉ mobile */
                e.preventDefault();
                e.stopPropagation();
                var li = a.parentElement;
                /* Đóng các sibling đang mở */
                list.querySelectorAll('.menu-item-has-children.tc-sub-open').forEach(function(other) {
                    if (other !== li) other.classList.remove('tc-sub-open');
                });
                li.classList.toggle('tc-sub-open');
            }
            a.addEventListener('click', toggleSub);
            a.addEventListener('touchend', function(e) {
                if (window.innerWidth > 480) return;
                e.preventDefault();
                toggleSub(e);
            }, { passive: false });
        });

        /* Đóng menu khi click/touch ra ngoài */
        document.addEventListener('click', function (e) {
            if (!navEl.contains(e.target)) {
                list.classList.remove('tc-nav--open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('touchend', function (e) {
            if (!navEl.contains(e.target)) {
                list.classList.remove('tc-nav--open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        }, { passive: true });
    }
})();
</script>

<?php get_footer(); ?>
