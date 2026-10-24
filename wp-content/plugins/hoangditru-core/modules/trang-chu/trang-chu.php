<?php
/**
 * Plugin Name: Trang Chủ
 * Description: Quản lý trang chủ custom: page template, sticky nav toàn trang, hotline, admin settings.
 * Version:     1.1.0
 * Author:      Your Name
 * Text Domain: trang-chu
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TC_VERSION', '1.1.0' );
define( 'TC_DIR',     plugin_dir_path( __FILE__ ) );
define( 'TC_URL',     plugin_dir_url( __FILE__ ) );
define( 'TC_KEY',     'trang_chu_settings' );
define( 'TC_TPL',     'tct-trang-chu' );

// ═══════════════════════════════════════════════════════════════
// 1. PAGE TEMPLATE
// ═══════════════════════════════════════════════════════════════

add_filter( 'theme_page_templates', 'tc_register_template' );
function tc_register_template( $templates ) {
    $templates[ TC_TPL ] = 'Trang Chủ';
    return $templates;
}

add_filter( 'template_include', 'tc_load_template' );
function tc_load_template( $template ) {
    if ( is_page() && get_page_template_slug() === TC_TPL ) {
        $file = TC_DIR . 'templates/trang-chu.php';
        if ( file_exists( $file ) ) return $file;
    }
    return $template;
}

// ═══════════════════════════════════════════════════════════════
// 2. ENQUEUE — trang chủ (CSS + ẩn header bottom Flatsome)
// ═══════════════════════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', 'tc_enqueue_frontend' );
function tc_enqueue_frontend() {
    if ( ! is_page() || get_page_template_slug() !== TC_TPL ) return;

    wp_enqueue_style( 'trang-chu', TC_URL . 'assets/trang-chu.css', [], TC_VERSION );

    // CSS BĐS plugin nếu có
    if ( defined( 'BDS_URL' ) ) {
        wp_enqueue_style( 'bds-card', BDS_URL . 'assets/css/category.css', [],
            defined( 'BDS_VERSION' ) ? BDS_VERSION : '1.1.0' );
    }

    // Ẩn header-bottom Flatsome (#wide-nav) — trang chủ dùng nav riêng
    wp_add_inline_style( 'trang-chu',
        '#wide-nav, .header-bottom { display: none !important; }'
    );
}

// Ẩn header-bottom bằng Flatsome hook (trước khi render HTML)
add_filter( 'flatsome_has_bottom_bar', 'tc_hide_flatsome_bottom_bar' );
function tc_hide_flatsome_bottom_bar( $result ) {
    if ( is_page() && get_page_template_slug() === TC_TPL ) {
        return [ 'large' => false, 'mobile' => false, 'large_or_mobile' => false, 'mobile_only' => false ];
    }
    return $result;
}

// ═══════════════════════════════════════════════════════════════
// 3. NAV TOÀN TRANG — enqueue CSS + inject nav
// ═══════════════════════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', 'tc_enqueue_global_nav' );
function tc_enqueue_global_nav() {
    wp_enqueue_style( 'tc-nav-global', TC_URL . 'assets/nav-global.css', [], TC_VERSION );
    // Ẩn #wide-nav Flatsome toàn bộ site
    wp_add_inline_style( 'tc-nav-global',
        '#wide-nav, .header-bottom { display: none !important; }'
    );
}

// Inject nav bằng nhiều hook để đảm bảo hoạt động với mọi theme/version Flatsome:
//   wp_body_open        — WordPress 5.2+ chuẩn
//   flatsome_after_header — Flatsome specific, chạy sau header wrapper
// static flag ngăn render 2 lần nếu cả 2 hook đều kích hoạt.
add_action( 'wp_body_open',          'tc_inject_global_nav', 1 );
add_action( 'flatsome_after_header', 'tc_inject_global_nav', 1 );

function tc_inject_global_nav() {
    static $rendered = false;
    if ( $rendered ) return;
    if ( is_page() && get_page_template_slug() === TC_TPL ) return;
    $rendered  = true;

    $cim       = get_option( 'cim_contact_info', [] );
    $phone     = $cim['phone'] ?? '';
    $phone_tel = preg_replace( '/\D/', '', $phone );
    ?>
    <div id="tc-nav-bar-global" class="tc-nav-bar tc-nav-bar--global" role="navigation" aria-label="Menu chính">
        <div class="tc-nav__inner" style="max-width:var(--tc-boxed,1200px);margin:0 auto;padding:0 24px;display:flex;align-items:center;height:var(--tc-nav-h,68px);">
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
            <a href="tel:<?php echo esc_attr( $phone_tel ); ?>" class="tc-nav__hotline">
                <span class="tc-nav__hotline-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24 11.47 11.47 0 003.58.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.47 11.47 0 00.57 3.57 1 1 0 01-.25 1.02l-2.2 2.2z"/></svg>
                </span>
                <span class="tc-nav__hotline-number"><?php echo esc_html( $phone ); ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div id="tc-nav-sentinel-global" style="height:0" aria-hidden="true"></div>
    <script>
    (function(){
        var nav      = document.getElementById('tc-nav-bar-global');
        var sentinel = document.getElementById('tc-nav-sentinel-global');
        var toggle   = nav && nav.querySelector('.tc-nav__toggle');
        var list     = nav && nav.querySelector('.tc-nav__list');

        function sync() {
            if (!sentinel) return;
            sentinel.style.height = (nav && window.innerWidth <= 480)
                ? nav.offsetHeight + 'px' : '0px';
        }
        sync();
        window.addEventListener('resize', sync);

        if (!toggle || !list) return;

        function openMenu(e) {
            e.preventDefault();
            e.stopPropagation();
            var open = list.classList.toggle('tc-nav--open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        toggle.addEventListener('click', openMenu);
        toggle.addEventListener('touchend', function(e) {
            e.preventDefault();
            openMenu(e);
        }, { passive: false });

        list.querySelectorAll('.menu-item-has-children > a').forEach(function(a) {
            function toggleSub(e) {
                if (window.innerWidth > 480) return;
                e.preventDefault();
                e.stopPropagation();
                var li = a.parentElement;
                list.querySelectorAll('.menu-item-has-children.tc-sub-open').forEach(function(o) {
                    if (o !== li) o.classList.remove('tc-sub-open');
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

        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target)) {
                list.classList.remove('tc-nav--open');
                toggle.setAttribute('aria-expanded','false');
            }
        });
        document.addEventListener('touchend', function(e) {
            if (!nav.contains(e.target)) {
                list.classList.remove('tc-nav--open');
                toggle.setAttribute('aria-expanded','false');
            }
        }, { passive: true });
    })();
    </script>
    <?php
}

// ═══════════════════════════════════════════════════════════════
// 4. FLAG — cho phép template gọi menu mà không bị chặn
// ═══════════════════════════════════════════════════════════════

global $tc_rendering_custom_nav;
$tc_rendering_custom_nav = false;

add_filter( 'wp_nav_menu_args', 'tc_suppress_header_menu' );
function tc_suppress_header_menu( $args ) {
    global $tc_rendering_custom_nav;
    if ( $tc_rendering_custom_nav ) return $args;
    $locs = [ 'primary', 'main', 'header-nav', 'top-bar', 'main-menu' ];
    if ( isset( $args['theme_location'] ) && in_array( $args['theme_location'], $locs, true ) ) {
        $args['fallback_cb'] = '__return_empty_string';
        $args['menu']        = 9999999;
    }
    return $args;
}

add_filter( 'wp_nav_menu', 'tc_empty_header_menu_output', 10, 2 );
function tc_empty_header_menu_output( $nav_html, $args ) {
    global $tc_rendering_custom_nav;
    if ( $tc_rendering_custom_nav ) return $nav_html;
    $locs = [ 'primary', 'main', 'header-nav', 'top-bar', 'main-menu' ];
    if ( isset( $args->theme_location ) && in_array( $args->theme_location, $locs, true ) ) {
        return '';
    }
    return $nav_html;
}

// ═══════════════════════════════════════════════════════════════
// 5. ADMIN
// ═══════════════════════════════════════════════════════════════

add_action( 'admin_menu', 'tc_admin_menu' );
function tc_admin_menu() {
    // Cap riêng, không phải manage_options: Biên tập viên vào được trang này mà
    // không mở theo cả Cài đặt / Plugin / Người dùng.
    // Xem modules/phan-quyen/phan-quyen.php.
    add_menu_page( 'Cài Đặt Trang Chủ', 'Trang Chủ', HDTQ_CAP_SITE_SETTINGS,
        'trang-chu-settings', 'tc_admin_page', 'dashicons-home', 25 );
}

add_action( 'admin_enqueue_scripts', 'tc_admin_enqueue' );
function tc_admin_enqueue( $hook ) {
    if ( 'toplevel_page_trang-chu-settings' !== $hook ) return;
    wp_enqueue_media();
    wp_enqueue_style(  'tc-admin', TC_URL . 'assets/admin.css', [], TC_VERSION );
    wp_enqueue_script( 'tc-admin', TC_URL . 'assets/admin.js', ['jquery','jquery-ui-sortable'], TC_VERSION, true );
    wp_localize_script( 'tc-admin', 'TCS', [
        'golden_visa_posts' => tc_posts_for_select( 'golden_visa' ),
        'citizenship_posts' => tc_posts_for_select( 'citizenship_invest' ),
        'bds_posts'         => tc_posts_for_select( 'bat-dong-san' ),
        'news_posts'        => tc_posts_for_select( 'post' ),
    ]);
}

function tc_posts_for_select( $post_type ) {
    $posts = get_posts([ 'post_type'=>$post_type, 'posts_per_page'=>50, 'post_status'=>'publish', 'orderby'=>'date', 'order'=>'DESC' ]);
    return array_map( fn($p) => [ 'id'=>$p->ID, 'title'=>$p->post_title?:'(Không có tiêu đề)', 'thumb'=>get_the_post_thumbnail_url($p->ID,'thumbnail')?:'' ], $posts );
}

// ═══════════════════════════════════════════════════════════════
// 6. SETTINGS
// ═══════════════════════════════════════════════════════════════

function tc_defaults() {
    return [
        's3_title_black'=>'CHƯƠNG TRÌNH THẺ','s3_title_orange'=>'GOLDEN VISA','s3_post_ids'=>[],
        's4_title_black'=>'CHƯƠNG TRÌNH','s4_title_orange'=>'QUỐC TỊCH','s4_post_ids'=>[],
        's5_title'=>'DỰ ÁN TIÊU BIỂU','s5_bds_ids'=>[],
        's6_title'=>'VỀ CHÚNG TÔI','s6_desc'=>'','s6_image'=>0,
        's7_title'=>'KIẾN THỨC & TIN TỨC','s7_post_ids'=>[],
    ];
}

function tc_get( $key ) {
    $d = get_option( TC_KEY, tc_defaults() );
    return $d[$key] ?? (tc_defaults()[$key] ?? '');
}

add_action( 'admin_init', 'tc_save_settings' );
function tc_save_settings() {
    if ( ! isset($_POST['tc_nonce']) || ! wp_verify_nonce($_POST['tc_nonce'],'tc_save') ) return;
    if ( ! current_user_can( HDTQ_CAP_SITE_SETTINGS ) ) return;
    $data = [
        's3_title_black'  => sanitize_text_field($_POST['s3_title_black']  ?? ''),
        's3_title_orange' => sanitize_text_field($_POST['s3_title_orange'] ?? ''),
        's3_post_ids'     => array_slice(array_map('absint',(array)($_POST['s3_post_ids']??[])),0,6),
        's4_title_black'  => sanitize_text_field($_POST['s4_title_black']  ?? ''),
        's4_title_orange' => sanitize_text_field($_POST['s4_title_orange'] ?? ''),
        's4_post_ids'     => array_slice(array_map('absint',(array)($_POST['s4_post_ids']??[])),0,6),
        's5_title'        => sanitize_text_field($_POST['s5_title']        ?? ''),
        's5_bds_ids'      => array_slice(array_map('absint',(array)($_POST['s5_bds_ids'] ??[])),0,4),
        's6_title'        => sanitize_text_field($_POST['s6_title']        ?? ''),
        's6_desc'         => wp_kses_post($_POST['s6_desc']                ?? ''),
        's6_image'        => absint($_POST['s6_image'] ?? 0),
        's7_title'        => sanitize_text_field($_POST['s7_title']        ?? ''),
        's7_post_ids'     => array_slice(array_map('absint',(array)($_POST['s7_post_ids']??[])),0,3),
    ];
    update_option(TC_KEY,$data);
    add_action('admin_notices', fn() => print '<div class="notice notice-success is-dismissible"><p>✅ Đã lưu cài đặt trang chủ.</p></div>');
}

// ═══════════════════════════════════════════════════════════════
// 7. ADMIN PAGE HTML
// ═══════════════════════════════════════════════════════════════

function tc_admin_page() {
    $d=$d=get_option(TC_KEY,tc_defaults());
    $cim=get_option('cim_contact_info',[]);
    $cover_url=$cim['cover_image']??0 ? wp_get_attachment_image_url($cim['cover_image'],'medium') : '';
    $s6_img_url=$d['s6_image'] ? wp_get_attachment_image_url($d['s6_image'],'medium') : '';
    ?>
    <div class="wrap tcs-wrap">
    <h1 class="tcs-page-title"><span class="dashicons dashicons-home"></span> Cài Đặt Trang Chủ</h1>
    <form method="post" action=""><?php wp_nonce_field('tc_save','tc_nonce'); ?>

    <div class="tcs-card tcs-card--info">
        <h2 class="tcs-card-title">🖼 Section 1 — Ảnh bìa trang</h2>
        <p class="description">Ảnh bìa lấy từ <strong>plugin Thông Tin Liên Hệ</strong>. Vào <a href="<?php echo esc_url(admin_url('admin.php?page=contact-info-manager')); ?>"><strong>Thông Tin Liên Hệ</strong></a> để thay đổi (khuyến nghị 1280×280px).</p>
        <?php if($cover_url): ?><img src="<?php echo esc_url($cover_url); ?>" alt="" class="tcs-cover-preview"><?php else: ?><div class="tcs-img-placeholder tcs-img-placeholder--sm"><span class="dashicons dashicons-format-image"></span><span>Chưa có ảnh bìa</span></div><?php endif; ?>
    </div>

    <div class="tcs-card"><h2 class="tcs-card-title">🏅 Section 3 — Golden Visa</h2>
        <div class="tcs-row">
            <div class="tcs-field"><label>Tiêu đề màu đen</label><input type="text" name="s3_title_black" value="<?php echo esc_attr($d['s3_title_black']); ?>" class="regular-text"></div>
            <div class="tcs-field"><label>Tiêu đề màu cam</label><input type="text" name="s3_title_orange" value="<?php echo esc_attr($d['s3_title_orange']); ?>" class="regular-text"></div>
        </div>
        <div class="tcs-field"><label>Chọn & sắp xếp bài viết <span class="tcs-note">(tối đa 6)</span></label><?php tc_sortable_picker('s3_post_ids',$d['s3_post_ids'],'golden_visa'); ?></div>
    </div>

    <div class="tcs-card"><h2 class="tcs-card-title">🌍 Section 4 — Quốc Tịch</h2>
        <div class="tcs-row">
            <div class="tcs-field"><label>Tiêu đề màu đen</label><input type="text" name="s4_title_black" value="<?php echo esc_attr($d['s4_title_black']); ?>" class="regular-text"></div>
            <div class="tcs-field"><label>Tiêu đề màu cam</label><input type="text" name="s4_title_orange" value="<?php echo esc_attr($d['s4_title_orange']); ?>" class="regular-text"></div>
        </div>
        <div class="tcs-field"><label>Chọn & sắp xếp bài viết <span class="tcs-note">(tối đa 6)</span></label><?php tc_sortable_picker('s4_post_ids',$d['s4_post_ids'],'citizenship_invest'); ?></div>
    </div>

    <div class="tcs-card"><h2 class="tcs-card-title">🏢 Section 5 — Dự Án Tiêu Biểu</h2>
        <div class="tcs-field"><label>Tiêu đề</label><input type="text" name="s5_title" value="<?php echo esc_attr($d['s5_title']); ?>" class="regular-text"></div>
        <div class="tcs-field"><label>Chọn & sắp xếp BĐS <span class="tcs-note">(tối đa 4)</span></label><?php tc_sortable_picker('s5_bds_ids',$d['s5_bds_ids'],'bat-dong-san',4); ?></div>
    </div>

    <div class="tcs-card"><h2 class="tcs-card-title">📝 Section 6 — Giới Thiệu</h2>
        <div class="tcs-field"><label>Tiêu đề (màu cam)</label><input type="text" name="s6_title" value="<?php echo esc_attr($d['s6_title']); ?>" class="regular-text"></div>
        <div class="tcs-field"><label>Mô tả</label><textarea name="s6_desc" rows="6" class="large-text"><?php echo wp_kses_post($d['s6_desc']); ?></textarea></div>
        <div class="tcs-field"><label>Hình ảnh bên phải</label>
            <div class="tcs-image-upload">
                <?php if($s6_img_url): ?><img id="s6-img-preview" src="<?php echo esc_url($s6_img_url); ?>" alt=""><?php else: ?>
                <div id="s6-img-placeholder" class="tcs-img-placeholder"><span class="dashicons dashicons-format-image"></span><span>Chưa có ảnh</span></div>
                <img id="s6-img-preview" src="" style="display:none" alt=""><?php endif; ?>
                <input type="hidden" name="s6_image" id="s6_image" value="<?php echo esc_attr($d['s6_image']); ?>">
                <div class="tcs-btn-row">
                    <button type="button" class="button button-secondary tcs-upload-btn" data-target="s6_image" data-preview="s6-img-preview" data-placeholder="s6-img-placeholder"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
                    <button type="button" class="button tcs-remove-btn" data-target="s6_image" data-preview="s6-img-preview" data-placeholder="s6-img-placeholder" <?php echo $s6_img_url?'':'style="display:none"'; ?>><span class="dashicons dashicons-trash"></span> Xoá</button>
                </div>
            </div>
        </div>
    </div>

    <div class="tcs-card"><h2 class="tcs-card-title">📰 Section 7 — Kiến Thức & Tin Tức</h2>
        <div class="tcs-field"><label>Tiêu đề</label><input type="text" name="s7_title" value="<?php echo esc_attr($d['s7_title']); ?>" class="regular-text"></div>
        <div class="tcs-field"><label>Chọn bài viết <span class="tcs-note">(tối đa 3 — tự bổ sung bài mới nhất nếu thiếu)</span></label><?php tc_sortable_picker('s7_post_ids',$d['s7_post_ids']??[],'post',3); ?></div>
    </div>

    <div class="tcs-submit-row"><?php submit_button('💾 Lưu cài đặt','primary large','submit',false); ?></div>
    </form></div>
    <?php
}

// ═══════════════════════════════════════════════════════════════
// 8. SORTABLE PICKER
// ═══════════════════════════════════════════════════════════════

function tc_sortable_picker( $name, $selected_ids, $post_type, $max = 6 ) {
    $all_posts = get_posts([ 'post_type'=>$post_type, 'posts_per_page'=>100, 'post_status'=>'publish', 'orderby'=>'date', 'order'=>'DESC' ]);
    $all_map = [];
    foreach ( $all_posts as $p ) $all_map[$p->ID] = $p->post_title ?: '(Không có tiêu đề)';
    $ordered = array_values(array_filter((array)$selected_ids));
    ?>
    <div class="tcs-picker" data-name="<?php echo esc_attr($name); ?>" data-max="<?php echo (int)$max; ?>">
        <div class="tcs-picker-cols">
        <div class="tcs-picker-all">
            <div class="tcs-picker-label">Tất cả <span class="tcs-count">(<?php echo count($all_map); ?>)</span></div>
            <input type="text" class="tcs-search" placeholder="🔍 Tìm kiếm...">
            <ul class="tcs-all-list">
                <?php foreach($all_posts as $p): $used=in_array($p->ID,$ordered); $thumb=get_the_post_thumbnail_url($p->ID,'thumbnail'); ?>
                <li data-id="<?php echo $p->ID; ?>" class="<?php echo $used?'tcs-used':''; ?>">
                    <?php if($thumb) echo '<img src="'.esc_url($thumb).'" alt="">'; ?>
                    <?php echo esc_html($p->post_title?:'(Không có tiêu đề)'); ?>
                    <button type="button" class="tcs-add-btn" <?php disabled($used); ?>>+</button>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="tcs-picker-selected">
            <div class="tcs-picker-label">Đã chọn <span class="tcs-count tcs-selected-count"><?php echo count($ordered); ?>/<?php echo (int)$max; ?></span></div>
            <ul class="tcs-selected-list ui-sortable">
                <?php foreach($ordered as $id): if(!isset($all_map[$id])) continue; $thumb=get_the_post_thumbnail_url($id,'thumbnail'); ?>
                <li data-id="<?php echo $id; ?>">
                    <span class="tcs-drag-handle dashicons dashicons-menu"></span>
                    <?php if($thumb) echo '<img src="'.esc_url($thumb).'" alt="">'; ?>
                    <span class="tcs-item-title"><?php echo esc_html($all_map[$id]); ?></span>
                    <input type="hidden" name="<?php echo esc_attr($name); ?>[]" value="<?php echo $id; ?>">
                    <button type="button" class="tcs-remove-item button-link"><span class="dashicons dashicons-no-alt"></span></button>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="tcs-empty-msg" <?php echo count($ordered)?'style="display:none"':''; ?>>Nhấn + để thêm hoặc kéo để sắp xếp</p>
        </div>
        </div>
    </div>
    <?php
}
