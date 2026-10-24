<?php
/**
 * Plugin Name: Smart Breadcrumb
 * Plugin URI:  #
 * Description: Breadcrumb tập trung, hỗ trợ style dark/light và màu chủ đạo tuỳ chỉnh. Tích hợp với các plugin BDS, Citizenship Investment và template category/single.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL2
 * Text Domain: smart-breadcrumb
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SBC_VERSION',    '1.0.0' );
define( 'SBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SBC_OPTION_KEY', 'smart_breadcrumb_options' );

// ──────────────────────────────────────────────
// Default options
// ──────────────────────────────────────────────
function sbc_defaults() {
    return [
        'enabled'       => '1',      // '1' | '0'
        'style'         => 'dark',   // 'dark' | 'light'
        'primary_color' => '#1e3a73',
        'separator'     => '›',
        'boxed_width'   => '1280px',
    ];
}

function sbc_option( $key ) {
    $opts = get_option( SBC_OPTION_KEY, sbc_defaults() );
    return $opts[ $key ] ?? sbc_defaults()[ $key ];
}

// ──────────────────────────────────────────────
// Admin: Settings page under Settings → Breadcrumb
// ──────────────────────────────────────────────
add_action( 'admin_menu', 'sbc_admin_menu' );
function sbc_admin_menu() {
    add_options_page(
        'Breadcrumb Settings',
        'Breadcrumb',
        'manage_options',
        'smart-breadcrumb',
        'sbc_settings_page'
    );
}

add_action( 'admin_init', 'sbc_save_settings' );
function sbc_save_settings() {
    if (
        ! isset( $_POST['sbc_nonce'] ) ||
        ! wp_verify_nonce( $_POST['sbc_nonce'], 'sbc_save' ) ||
        ! current_user_can( 'manage_options' )
    ) return;

    $opts = [
        'enabled'       => isset( $_POST['sbc_enabled'] ) ? '1' : '0',
        'style'         => in_array( $_POST['sbc_style'] ?? '', ['dark', 'light'], true )
                           ? $_POST['sbc_style'] : 'dark',
        'primary_color' => sanitize_hex_color( $_POST['sbc_primary_color'] ?? '#1e3a73' ) ?: '#1e3a73',
        'separator'     => sanitize_text_field( $_POST['sbc_separator'] ?? '›' ) ?: '›',
        'boxed_width'   => sanitize_text_field( $_POST['sbc_boxed_width'] ?? '1280px' ) ?: '1280px',
    ];

    update_option( SBC_OPTION_KEY, $opts );
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Đã lưu cài đặt Breadcrumb.</p></div>';
    });
}

add_action( 'admin_enqueue_scripts', 'sbc_admin_scripts' );
function sbc_admin_scripts( $hook ) {
    if ( 'settings_page_smart-breadcrumb' !== $hook ) return;
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script( 'wp-color-picker',
        'jQuery(function($){ $(".sbc-color-picker").wpColorPicker(); });'
    );
}

function sbc_settings_page() {
    $opts    = get_option( SBC_OPTION_KEY, sbc_defaults() );
    $enabled = $opts['enabled'] ?? '1';
    $style   = $opts['style'];
    $color   = $opts['primary_color'];
    $sep     = $opts['separator'];
    $boxed   = $opts['boxed_width'];

    // Live preview colours
    $preview_bg    = ( $style === 'dark' ) ? $color   : '#ffffff';
    $preview_link  = ( $style === 'dark' ) ? '#ffffff' : '#111111';
    $preview_curr  = ( $style === 'dark' ) ? '#ffffff' : $color;
    $preview_sep   = ( $style === 'dark' ) ? 'rgba(255,255,255,.45)' : '#999999';
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-location-alt" style="font-size:26px;width:26px;height:26px;color:#6366f1;"></span>
            Smart Breadcrumb
        </h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'sbc_save', 'sbc_nonce' ); ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row"><label for="sbc_enabled">Hiển thị Breadcrumb</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="sbc_enabled" name="sbc_enabled" value="1" <?php checked( $enabled, '1' ); ?>>
                            Bật breadcrumb trên toàn trang
                        </label>
                        <p class="description">Bỏ tích để tắt breadcrumb — không xoá code, chỉ ngừng hiển thị.</p>
                    </td>
                </tr>

                <tbody id="sbc-options-rows"<?php echo $enabled !== '1' ? ' style="opacity:.4;pointer-events:none;"' : ''; ?>>
                <tr>
                    <th scope="row"><label>Style</label></th>
                    <td>
                        <label style="margin-right:20px;">
                            <input type="radio" name="sbc_style" value="dark" <?php checked( $style, 'dark' ); ?>>
                            <strong>Dark</strong> — nền màu chủ đạo, chữ trắng
                        </label>
                        <label>
                            <input type="radio" name="sbc_style" value="light" <?php checked( $style, 'light' ); ?>>
                            <strong>Light</strong> — nền trắng, link đen, active link màu chủ đạo
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="sbc_primary_color">Màu chủ đạo</label></th>
                    <td>
                        <input type="text" id="sbc_primary_color" name="sbc_primary_color"
                               class="sbc-color-picker" value="<?php echo esc_attr( $color ); ?>">
                        <p class="description">Dùng làm màu nền (dark) hoặc màu active link (light).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="sbc_separator">Separator</label></th>
                    <td>
                        <input type="text" id="sbc_separator" name="sbc_separator"
                               value="<?php echo esc_attr( $sep ); ?>"
                               class="small-text" style="width:48px;text-align:center;font-size:16px;">
                        <p class="description">Ký tự phân cách giữa các mục. Mặc định: ›</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="sbc_boxed_width">Chiều rộng boxed</label></th>
                    <td>
                        <input type="text" id="sbc_boxed_width" name="sbc_boxed_width"
                               value="<?php echo esc_attr( $boxed ); ?>"
                               class="regular-text" placeholder="1280px">
                        <p class="description">Max-width của vùng nội dung bên trong. Ví dụ: 1280px, 1200px.</p>
                    </td>
                </tr>
                </tbody>

            </table>

            <!-- Preview -->
            <div id="sbc-preview-section"<?php echo $enabled !== '1' ? ' style="display:none;"' : ''; ?>>
                <h2>Preview</h2>
                <div id="sbc-preview-wrap" style="border:1px solid #ddd;border-radius:6px;overflow:hidden;max-width:680px;">
                    <div id="sbc-preview"
                         style="background:<?php echo esc_attr( $preview_bg ); ?>;padding:10px 20px;font-size:13px;display:flex;align-items:center;gap:0;">
                        <a href="#" id="pv-home"
                           style="color:<?php echo esc_attr( $preview_link ); ?>;text-decoration:none;">Trang chủ</a>
                        <span id="pv-sep1" style="margin:0 8px;color:<?php echo esc_attr( $preview_sep ); ?>;"><?php echo esc_html( $sep ); ?></span>
                        <a href="#" id="pv-cat"
                           style="color:<?php echo esc_attr( $preview_link ); ?>;text-decoration:none;">Danh mục</a>
                        <span id="pv-sep2" style="margin:0 8px;color:<?php echo esc_attr( $preview_sep ); ?>;"><?php echo esc_html( $sep ); ?></span>
                        <span id="pv-current"
                              style="color:<?php echo esc_attr( $preview_curr ); ?>;font-weight:600;">Tên bài viết</span>
                    </div>
                </div>
                <p class="description" style="margin-top:8px;">Preview cập nhật realtime khi bạn thay đổi cài đặt.</p>
            </div>

            <?php submit_button( '💾 Lưu cài đặt', 'primary', 'submit' ); ?>
        </form>
    </div>

    <script>
    (function($) {
        // ── Toggle enabled/disabled ──
        $('#sbc_enabled').on('change', function() {
            var on = $(this).is(':checked');
            $('#sbc-options-rows').css({ opacity: on ? 1 : 0.4, 'pointer-events': on ? '' : 'none' });
            $('#sbc-preview-section').toggle(on);
        });

        // ── Preview ──
        function updatePreview() {
            var style   = $('input[name="sbc_style"]:checked').val();
            var color   = $('#sbc_primary_color').val() || '#1e3a73';
            var sep     = $('#sbc_separator').val() || '›';

            var bg, linkColor, currentColor, sepColor;
            if ( style === 'dark' ) {
                bg           = color;
                linkColor    = '#ffffff';
                currentColor = '#ffffff';
                sepColor     = 'rgba(255,255,255,.45)';
            } else {
                bg           = '#ffffff';
                linkColor    = '#111111';
                currentColor = color;
                sepColor     = '#999999';
            }

            $('#sbc-preview').css('background', bg);
            $('#pv-home, #pv-cat').css('color', linkColor);
            $('#pv-current').css('color', currentColor);
            $('#pv-sep1, #pv-sep2').css('color', sepColor).text(sep);
        }

        $('input[name="sbc_style"]').on('change', updatePreview);
        $('#sbc_separator').on('input', updatePreview);
        $('#sbc_primary_color').on('change', updatePreview);
        $(document).on('irischange', updatePreview);
        setInterval(updatePreview, 300);

    }(jQuery));
    </script>
    <?php
}

// ──────────────────────────────────────────────
// Enqueue frontend CSS (inline, generated from options)
// ──────────────────────────────────────────────
add_action( 'wp_head', 'sbc_inline_css', 5 );
function sbc_inline_css() {
    if ( sbc_option( 'enabled' ) !== '1' ) return;
    $style   = sbc_option( 'style' );
    $color   = sbc_option( 'primary_color' );
    $boxed   = sbc_option( 'boxed_width' );

    // Compute colours per style
    if ( $style === 'dark' ) {
        $bg_color      = $color;
        $link_color    = '#ffffff';
        $link_hover    = 'rgba(255,255,255,0.75)';
        $sep_color     = 'rgba(255,255,255,0.45)';
        $current_color = '#ffffff';
        $border_bottom = 'none';
    } else {
        // light
        $bg_color      = '#ffffff';
        $link_color    = '#111111';
        $link_hover    = $color;
        $sep_color     = '#999999';
        $current_color = $color;
        $border_bottom = '1px solid #e5e5e5';
    }

    echo '<style id="smart-breadcrumb-css">
/* ── Smart Breadcrumb ── */
.sbc-wrap {
    background: ' . esc_attr( $bg_color ) . ';
    border-bottom: ' . esc_attr( $border_bottom ) . ';
    width: 100%;
    font-size: 13px;
    line-height: 1;
}
.sbc-inner {
    max-width: ' . esc_attr( $boxed ) . ';
    margin: 0 auto;
    padding: 20px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 2px;
}
.sbc-inner a {
    color: ' . esc_attr( $link_color ) . ';
    text-decoration: none;
    transition: color 0.2s;
}
.sbc-inner a:hover { color: ' . esc_attr( $link_hover ) . '; }
.sbc-sep {
    margin: 0 7px;
    color: ' . esc_attr( $sep_color ) . ';
}
.sbc-current {
    color: ' . esc_attr( $current_color ) . ';
    font-weight: 600;
}
@media (max-width: 768px) {
    .sbc-inner { padding: 9px 16px; }
}
</style>' . "\n";
}

// ──────────────────────────────────────────────
// Core: build breadcrumb HTML
// ──────────────────────────────────────────────
function sbc_build( $items ) {
    $sep  = esc_html( sbc_option( 'separator' ) );
    $html = '<nav class="sbc-wrap" aria-label="Breadcrumb"><div class="sbc-inner">';

    $last = count( $items ) - 1;
    foreach ( $items as $i => $item ) {
        if ( $i === $last ) {
            $html .= '<span class="sbc-current">' . esc_html( $item['label'] ) . '</span>';
        } else {
            $html .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
            $html .= '<span class="sbc-sep">' . $sep . '</span>';
        }
    }

    $html .= '</div></nav>';
    return $html;
}

// ──────────────────────────────────────────────
// Public render function (called by all templates)
// ──────────────────────────────────────────────
function sbc_render( $items ) {
    if ( sbc_option( 'enabled' ) !== '1' ) return;
    echo sbc_build( $items ); // phpcs:ignore WordPress.Security.EscapeOutput
}

// ──────────────────────────────────────────────
// Auto-hook: WordPress standard category & single
// Fires on templates that call get_header()
// ──────────────────────────────────────────────
add_action( 'wp_head', 'sbc_register_auto_hooks' );
function sbc_register_auto_hooks() {
    // We use get_template_part / template_include hook to detect context.
    // Actually we hook into the action fired right after get_header().
    // Flatsome fires 'flatsome_after_header' — we use that.
    // Fallback: hook into wp_body_open then check is_* flags.
}

// Register the hook that all our templates will call:
// sbc_auto() is invoked directly from each template (see integration section below).

// ──────────────────────────────────────────────
// Integration helpers — called from modified templates
// ──────────────────────────────────────────────

/**
 * Breadcrumb for standard WordPress category archive (category.php)
 * Context: $category = get_queried_object()
 */
function sbc_category( $category = null ) {
    if ( ! $category ) $category = get_queried_object();
    sbc_render( [
        [ 'label' => 'Trang chủ', 'url' => home_url( '/' ) ],
        [ 'label' => $category->name, 'url' => '' ],
    ] );
}

/**
 * Breadcrumb for standard WordPress single post (single.php)
 */
function sbc_single_post() {
    $cats = get_the_category();
    $cat  = $cats ? $cats[0] : null;
    $items = [ [ 'label' => 'Trang chủ', 'url' => home_url( '/' ) ] ];
    if ( $cat ) {
        $items[] = [ 'label' => $cat->name, 'url' => get_category_link( $cat->term_id ) ];
    }
    $items[] = [ 'label' => get_the_title(), 'url' => '' ];
    sbc_render( $items );
}

/**
 * Breadcrumb for BDS archive (archive-bat-dong-san.php)
 */
function sbc_bds_archive() {
    sbc_render( [
        [ 'label' => 'Trang chủ',  'url' => home_url( '/' ) ],
        [ 'label' => 'Bất động sản', 'url' => '' ],
    ] );
}

/**
 * Breadcrumb for BDS taxonomy (taxonomy-bds_category.php)
 * @param string $term_name  Display name of the current term
 */
function sbc_bds_taxonomy( $term_name ) {
    sbc_render( [
        [ 'label' => 'Trang chủ',   'url' => home_url( '/' ) ],
        [ 'label' => 'Bất động sản', 'url' => get_post_type_archive_link( 'bat-dong-san' ) ],
        [ 'label' => $term_name,     'url' => '' ],
    ] );
}

/**
 * Breadcrumb for BDS single (single-bat-dong-san.php)
 * @param string $title  Post title
 */
function sbc_bds_single( $title ) {
    sbc_render( [
        [ 'label' => 'Trang chủ',   'url' => home_url( '/' ) ],
        [ 'label' => 'Bất động sản', 'url' => get_post_type_archive_link( 'bat-dong-san' ) ],
        [ 'label' => $title,         'url' => '' ],
    ] );
}

/**
 * Breadcrumb for Citizenship Investment single
 */
function sbc_citizenship_single() {
    sbc_render( [
        [ 'label' => 'Trang chủ',        'url' => home_url( '/' ) ],
        [ 'label' => 'Đầu tư quốc tịch', 'url' => get_post_type_archive_link( 'citizenship_invest' ) ],
        [ 'label' => get_the_title(),     'url' => '' ],
    ] );
}
