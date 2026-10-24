<?php
/**
 * Plugin Name: CIM Footer
 * Plugin URI:  #
 * Description: Footer toàn trang lấy dữ liệu từ plugin Contact Info Manager. Background ảnh nền, overlay, layout 2 cột — tích hợp CF7.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL2
 * Text Domain: cim-footer
 * Requires Plugins: contact-info-manager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CIMF_VERSION',    '1.0.0' );
define( 'CIMF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ──────────────────────────────────────────────
// Enqueue styles
// ──────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'cimf_enqueue' );
function cimf_enqueue() {
    wp_enqueue_style(
        'cim-footer-style',
        CIMF_PLUGIN_URL . 'assets/cim-footer.css',
        [],
        CIMF_VERSION
    );
}

// ──────────────────────────────────────────────
// Hook vào Flatsome trước footer theme
// Fallback về wp_footer nếu theme khác
// ──────────────────────────────────────────────
add_action( 'init', 'cimf_register_hook' );
function cimf_register_hook() {
    if ( function_exists( 'flatsome_before_footer' ) || current_theme_supports( 'flatsome' ) ) {
        add_action( 'flatsome_before_footer', 'cimf_render', 5 );
    } else {
        // Flatsome fire hook này qua do_action — đăng ký trực tiếp
        add_action( 'flatsome_before_footer', 'cimf_render', 5 );
        // Fallback chắc chắn
        add_action( 'wp_footer', 'cimf_render', 1 );
    }
}

// ──────────────────────────────────────────────
// Helper: lấy dữ liệu từ CIM
// ──────────────────────────────────────────────
function cimf_data() {
    if ( ! defined( 'CIM_OPTION_KEY' ) ) return [];
    return get_option( CIM_OPTION_KEY, [] );
}

// ──────────────────────────────────────────────
// SVG icons mặc định
// ──────────────────────────────────────────────
function cimf_default_svg( $network ) {
    $svgs = [
        'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>',
        'tiktok'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.76a4.85 4.85 0 0 1-1.01-.07z"/></svg>',
    ];
    return $svgs[ $network ] ?? '';
}

// ──────────────────────────────────────────────
// Render footer
// ──────────────────────────────────────────────
function cimf_render() {
    // Chỉ render 1 lần
    static $rendered = false;
    if ( $rendered ) return;
    $rendered = true;

    $data = cimf_data();
    if ( empty( $data ) ) return;

    // ── Background image ──
    $bg_id  = ! empty( $data['footer_bg'] ) ? absint( $data['footer_bg'] ) : 0;
    $bg_url = $bg_id ? wp_get_attachment_image_url( $bg_id, 'full' ) : '';
    $bg_style = $bg_url
        ? 'style="background-image:url(' . esc_url( $bg_url ) . ');"'
        : '';

    // ── Col 1 data ──
    $footer_desc = ! empty( $data['footer_desc'] ) ? wp_kses_post( $data['footer_desc'] ) : '';
    $branches    = [];
    if ( ! empty( $data['branches'] ) ) {
        $decoded = json_decode( $data['branches'], true );
        if ( is_array( $decoded ) ) $branches = $decoded;
    }
    $email = ! empty( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
    $phone = ! empty( $data['phone'] ) ? esc_html( $data['phone'] )       : '';

    // ── Social icons ──
    $socials = [
        'youtube'  => 'YouTube',
        'facebook' => 'Facebook',
        'tiktok'   => 'TikTok',
    ];

    $social_html = '';
    foreach ( $socials as $key => $label ) {
        $url = ! empty( $data[ $key ] ) ? esc_url( $data[ $key ] ) : '';
        if ( ! $url ) continue;

        $icon_id = ! empty( $data[ $key . '_icon' ] ) ? absint( $data[ $key . '_icon' ] ) : 0;
        if ( $icon_id ) {
            $icon_html = hdtc_img( $icon_id, 'thumbnail', array(
                'alt'   => $label,
                'class' => 'cimf-social-img',
                'sizes' => '24px',
            ) );
        } else {
            $icon_html = '<span class="cimf-social-svg cimf-svg-' . esc_attr( $key ) . '">'
                       . cimf_default_svg( $key )
                       . '</span>';
        }

        $social_html .= '<a href="' . $url . '" class="cimf-social-link cimf-social-' . esc_attr( $key ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $label ) . '">'
                      . $icon_html
                      . '</a>';
    }

    // ── CF7 shortcode ──
    $cf7_shortcode = '[contact-form-7 id="cc8d593" title="Form liên hệ 2"]';
    $cf7_html      = defined( 'WPCF7_VERSION' ) ? do_shortcode( $cf7_shortcode ) : '<p style="color:rgba(255,255,255,.6);font-size:14px;">Vui lòng kích hoạt plugin Contact Form 7.</p>';

    ?>
    <div id="cim-footer" class="cimf-footer<?php echo $bg_url ? ' has-bg' : ''; ?>" <?php echo $bg_style; ?>>

        <!-- Overlay -->
        <div class="cimf-overlay" aria-hidden="true"></div>

        <!-- Boxed content -->
        <div class="cimf-inner">
            <div class="cimf-grid">

                <!-- ── Cột 1: Liên hệ ── -->
                <div class="cimf-col cimf-col-contact">
                    <h2 class="cimf-heading">Liên Hệ</h2>

                    <?php if ( $footer_desc ) : ?>
                        <div class="cimf-bio"><?php echo $footer_desc; ?></div>
                    <?php endif; ?>

                    <?php if ( ! empty( $branches ) ) : ?>
                        <?php foreach ( $branches as $branch ) :
                            $b_title = sanitize_text_field( $branch['title']   ?? '' );
                            $b_addr  = sanitize_text_field( $branch['address'] ?? '' );
                            if ( ! $b_title && ! $b_addr ) continue;
                        ?>
                        <div class="cimf-contact-row cimf-contact-row--branch">
                            <span class="cimf-icon cimf-icon-address" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            </span>
                            <span class="cimf-contact-text cimf-branch-text">
                                <?php if ( $b_title ) : ?>
                                    <span class="cimf-branch-title"><?php echo esc_html( $b_title ); ?>:</span>
                                <?php endif; ?>
                                <?php if ( $b_addr ) : ?>
                                    <span class="cimf-branch-address"><?php echo esc_html( $b_addr ); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ( $email ) : ?>
                        <div class="cimf-contact-row">
                            <span class="cimf-icon cimf-icon-email" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </span>
                            <a href="mailto:<?php echo esc_attr( $email ); ?>" class="cimf-contact-link">
                                <?php echo esc_html( $email ); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ( $phone ) : ?>
                        <div class="cimf-contact-row">
                            <span class="cimf-icon cimf-icon-phone" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <a href="tel:<?php echo esc_attr( preg_replace( '/\D/', '', $phone ) ); ?>" class="cimf-contact-link">
                                <?php echo $phone; ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ( $social_html ) : ?>
                        <div class="cimf-social-row" role="list" aria-label="Mạng xã hội">
                            <?php echo $social_html; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── Cột 2: Form ── -->
                <div class="cimf-col cimf-col-form">
                    <h2 class="cimf-heading">Yêu Cầu Gọi Lại</h2>
                    <div class="cimf-cf7-wrap">
                        <?php echo $cf7_html; ?>
                    </div>
                </div>

            </div><!-- .cimf-grid -->
        </div><!-- .cimf-inner -->

    </div><!-- #cim-footer -->
    <?php
}
