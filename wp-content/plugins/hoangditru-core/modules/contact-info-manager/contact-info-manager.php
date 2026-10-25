<?php
/**
 * Plugin Name: Contact Info Manager
 * Plugin URI: #
 * Description: Quản lý thông tin liên hệ với shortcode riêng cho từng trường. Hỗ trợ ảnh nền footer.
 * Version: 1.1.0
 * Author: Your Name
 * License: GPL2
 * Text Domain: contact-info-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CIM_VERSION', '1.1.2' );
define( 'CIM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CIM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CIM_OPTION_KEY', 'cim_contact_info' );

// ──────────────────────────────────────────────
// Activation / Deactivation
// ──────────────────────────────────────────────
register_activation_hook( __FILE__, 'cim_activate' );
function cim_activate() {
    if ( ! get_option( CIM_OPTION_KEY ) ) {
        add_option( CIM_OPTION_KEY, cim_default_data() );
    }
}

function cim_default_data() {
    return [
        'avatar'            => '',
        'cover_image'       => '',
        'full_name'         => '',
        'bio'               => '',
        'email'             => '',
        'phone'             => '',
        'youtube'           => '',
        'youtube_icon'      => '',
        'facebook'          => '',
        'facebook_icon'     => '',
        'tiktok'            => '',
        'tiktok_icon'       => '',
        'mailchimp_api_key' => '',
        'footer_bg'         => '',
        'footer_desc'       => '',
        'branches'          => '',
    ];
}

// ──────────────────────────────────────────────
// Admin Menu
// ──────────────────────────────────────────────
add_action( 'admin_menu', 'cim_admin_menu' );
function cim_admin_menu() {
    add_menu_page(
        'Thông Tin Liên Hệ',
        'Thông Tin Liên Hệ',
        // Cap riêng, không phải manage_options: Biên tập viên vào được trang
        // này mà không mở theo cả Cài đặt / Plugin / Người dùng.
        // Xem modules/phan-quyen/phan-quyen.php.
        HDTQ_CAP_SITE_SETTINGS,
        'contact-info-manager',
        'cim_admin_page',
        'dashicons-id-alt',
        30
    );
}

// ──────────────────────────────────────────────
// Admin Styles & Scripts
// ──────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'cim_admin_scripts' );
function cim_admin_scripts( $hook ) {
    if ( 'toplevel_page_contact-info-manager' !== $hook ) return;
    wp_enqueue_media();
    wp_enqueue_style( 'cim-admin-style', CIM_PLUGIN_URL . 'assets/admin.css', [], CIM_VERSION );
    wp_enqueue_script( 'cim-admin-script', CIM_PLUGIN_URL . 'assets/admin.js', ['jquery'], CIM_VERSION, true );
}

// ──────────────────────────────────────────────
// Save Handler
// ──────────────────────────────────────────────
add_action( 'admin_init', 'cim_save_settings' );
function cim_save_settings() {
    if (
        ! isset( $_POST['cim_nonce'] ) ||
        ! wp_verify_nonce( $_POST['cim_nonce'], 'cim_save_contact_info' ) ||
        ! current_user_can( HDTQ_CAP_SITE_SETTINGS )
    ) {
        return;
    }

    $fields  = array_keys( cim_default_data() );
    $data    = get_option( CIM_OPTION_KEY, cim_default_data() );

    foreach ( $fields as $field ) {
        if ( in_array( $field, ['bio', 'footer_desc'], true ) ) {
            $data[ $field ] = wp_kses_post( $_POST[ 'cim_' . $field ] ?? '' );
        } elseif ( in_array( $field, ['youtube', 'facebook', 'tiktok'], true ) ) {
            $data[ $field ] = esc_url_raw( $_POST[ 'cim_' . $field ] ?? '' );
        } elseif ( in_array( $field, ['youtube_icon', 'facebook_icon', 'tiktok_icon', 'avatar', 'cover_image', 'footer_bg'], true ) ) {
            $data[ $field ] = absint( $_POST[ 'cim_' . $field ] ?? 0 );
        } elseif ( $field === 'email' ) {
            $data[ $field ] = sanitize_email( $_POST[ 'cim_' . $field ] ?? '' );
        } elseif ( $field === 'mailchimp_api_key' ) {
            $data[ $field ] = sanitize_text_field( $_POST[ 'cim_' . $field ] ?? '' );
        } else {
            $data[ $field ] = sanitize_text_field( $_POST[ 'cim_' . $field ] ?? '' );
        }
    }

    // Chữ ký đã bị gỡ khỏi plugin; lần lưu đầu tiên xoá luôn giá trị cũ.
    unset( $data['signature'] );

    update_option( CIM_OPTION_KEY, $data );

    // ── Lưu danh sách chi nhánh (ngoài foreach vì cấu trúc POST khác) ──
    $branches = [];
    $titles   = $_POST['cim_branch_title']   ?? [];
    $addrs    = $_POST['cim_branch_address'] ?? [];
    $count    = max( count( $titles ), count( $addrs ) );
    for ( $i = 0; $i < $count; $i++ ) {
        $title = sanitize_text_field( $titles[ $i ] ?? '' );
        $addr  = sanitize_text_field( $addrs[ $i ]  ?? '' );
        if ( $title !== '' || $addr !== '' ) {
            $branches[] = [ 'title' => $title, 'address' => $addr ];
        }
    }
    $data['branches'] = wp_json_encode( $branches, JSON_UNESCAPED_UNICODE );
    update_option( CIM_OPTION_KEY, $data );
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Đã lưu thông tin liên hệ thành công!</p></div>';
    });
}

// ──────────────────────────────────────────────
// Admin Page HTML
// ──────────────────────────────────────────────
function cim_admin_page() {
    $data           = get_option( CIM_OPTION_KEY, cim_default_data() );
    $avatar_url     = $data['avatar']      ? wp_get_attachment_image_url( $data['avatar'],      'thumbnail' ) : '';
    $cover_url      = $data['cover_image'] ? wp_get_attachment_image_url( $data['cover_image'], 'medium'    ) : '';
    $footer_bg_url  = $data['footer_bg']   ? wp_get_attachment_image_url( $data['footer_bg'],   'medium'    ) : '';
    ?>
    <div class="wrap cim-wrap">
        <h1 class="cim-title">
            <span class="dashicons dashicons-id-alt"></span>
            Quản Lý Thông Tin Liên Hệ
        </h1>

        <div class="cim-layout">
            <div class="cim-form-col">
                <form method="post" action="">
                    <?php wp_nonce_field( 'cim_save_contact_info', 'cim_nonce' ); ?>

                    <div class="cim-card">
                        <h2 class="cim-card-title">📋 Thông Tin Cơ Bản</h2>

                        <!-- Avatar -->
                        <div class="cim-field">
                            <label>Ảnh đại diện (Avatar)</label>
                            <div class="cim-avatar-wrap">
                                <?php if ( $avatar_url ) : ?>
                                    <img id="cim-avatar-preview" src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar">
                                <?php else : ?>
                                    <img id="cim-avatar-preview" src="" alt="Avatar" style="display:none;">
                                <?php endif; ?>
                                <input type="hidden" name="cim_avatar" id="cim_avatar" value="<?php echo esc_attr( $data['avatar'] ); ?>">
                                <div class="cim-avatar-buttons">
                                    <button type="button" id="cim-upload-avatar" class="button button-secondary">
                                        <span class="dashicons dashicons-upload"></span> Chọn ảnh
                                    </button>
                                    <button type="button" id="cim-remove-avatar" class="button" <?php echo $avatar_url ? '' : 'style="display:none"'; ?>>
                                        <span class="dashicons dashicons-trash"></span> Xoá
                                    </button>
                                </div>

                            </div>
                        </div>

                        <!-- Họ tên -->
                        <div class="cim-field">
                            <label for="cim_full_name">Họ và tên</label>
                            <input type="text" id="cim_full_name" name="cim_full_name"
                                   value="<?php echo esc_attr( $data['full_name'] ); ?>"
                                   placeholder="Nguyễn Văn A" class="regular-text">

                        </div>

                        <!-- Giới thiệu -->
                        <div class="cim-field">
                            <label for="cim_bio">Giới thiệu ngắn</label>
                            <textarea id="cim_bio" name="cim_bio" rows="4" class="large-text"
                                      placeholder="Mô tả ngắn về bản thân..."><?php echo wp_kses_post( $data['bio'] ); ?></textarea>

                        </div>

                        <!-- Ảnh bìa -->
                        <div class="cim-field">
                            <label>Ảnh bìa (Cover Image)</label>
                            <div class="cim-cover-wrap">
                                <?php if ( $cover_url ) : ?>
                                    <img id="cim-cover-preview" src="<?php echo esc_url( $cover_url ); ?>" alt="Cover">
                                <?php else : ?>
                                    <div id="cim-cover-placeholder" class="cim-cover-placeholder">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <span>Chưa có ảnh bìa</span>
                                    </div>
                                    <img id="cim-cover-preview" src="" alt="Cover" style="display:none;">
                                <?php endif; ?>
                                <input type="hidden" name="cim_cover_image" id="cim_cover_image" value="<?php echo esc_attr( $data['cover_image'] ); ?>">
                                <div class="cim-avatar-buttons">
                                    <button type="button" id="cim-upload-cover" class="button button-secondary">
                                        <span class="dashicons dashicons-upload"></span> Chọn ảnh
                                    </button>
                                    <button type="button" id="cim-remove-cover" class="button" <?php echo $cover_url ? '' : 'style="display:none"'; ?>>
                                        <span class="dashicons dashicons-trash"></span> Xoá
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cim-card">
                        <h2 class="cim-card-title">📬 Thông Tin Liên Lạc</h2>

                        <!-- Email -->
                        <div class="cim-field">
                            <label for="cim_email">Email</label>
                            <div class="cim-input-icon-wrap">
                                <span class="cim-input-icon dashicons dashicons-email"></span>
                                <input type="email" id="cim_email" name="cim_email"
                                       value="<?php echo esc_attr( $data['email'] ); ?>"
                                       placeholder="example@email.com" class="regular-text">
                            </div>

                        </div>

                        <!-- Số điện thoại -->
                        <div class="cim-field">
                            <label for="cim_phone">Số điện thoại</label>
                            <div class="cim-input-icon-wrap">
                                <span class="cim-input-icon dashicons dashicons-phone"></span>
                                <input type="text" id="cim_phone" name="cim_phone"
                                       value="<?php echo esc_attr( $data['phone'] ); ?>"
                                       placeholder="0901 234 567" class="regular-text">
                            </div>

                        </div>
                    </div>

                    <div class="cim-card">
                        <h2 class="cim-card-title">🔗 Mạng Xã Hội</h2>

                        <?php
                        $socials = [
                            'youtube'  => [ 'label' => 'YouTube',  'placeholder' => 'https://youtube.com/@kenh',       'default_icon' => '▶', 'color' => '#ff0000' ],
                            'facebook' => [ 'label' => 'Facebook', 'placeholder' => 'https://facebook.com/trang',      'default_icon' => 'f', 'color' => '#1877f2' ],
                            'tiktok'   => [ 'label' => 'TikTok',   'placeholder' => 'https://tiktok.com/@username',    'default_icon' => '♪', 'color' => '#010101' ],
                        ];
                        foreach ( $socials as $key => $cfg ) :
                            $icon_id  = $data[ $key . '_icon' ] ?? 0;
                            $icon_url = $icon_id ? wp_get_attachment_image_url( $icon_id, 'thumbnail' ) : '';
                        ?>
                        <div class="cim-field cim-social-field">
                            <label><?php echo esc_html( $cfg['label'] ); ?></label>
                            <div class="cim-social-row">

                                <!-- Icon upload -->
                                <div class="cim-social-icon-upload" data-key="<?php echo esc_attr( $key ); ?>">
                                    <div class="cim-social-icon-preview" style="background:<?php echo esc_attr( $cfg['color'] ); ?>">
                                        <?php if ( $icon_url ) : ?>
                                            <img id="cim-icon-preview-<?php echo esc_attr( $key ); ?>"
                                                 src="<?php echo esc_url( $icon_url ); ?>" alt="icon">
                                        <?php else : ?>
                                            <img id="cim-icon-preview-<?php echo esc_attr( $key ); ?>"
                                                 src="" alt="icon" style="display:none;">
                                            <span id="cim-icon-default-<?php echo esc_attr( $key ); ?>" class="cim-default-icon-text">
                                                <?php echo esc_html( $cfg['default_icon'] ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden"
                                           name="cim_<?php echo esc_attr( $key ); ?>_icon"
                                           id="cim_<?php echo esc_attr( $key ); ?>_icon"
                                           value="<?php echo esc_attr( $icon_id ); ?>">
                                    <div class="cim-icon-btns">
                                        <button type="button"
                                                class="button button-secondary cim-upload-social-icon"
                                                data-key="<?php echo esc_attr( $key ); ?>"
                                                title="Upload icon">
                                            <span class="dashicons dashicons-upload"></span> Chọn ảnh
                                        </button>
                                        <button type="button"
                                                class="button cim-remove-social-icon"
                                                data-key="<?php echo esc_attr( $key ); ?>"
                                                title="Xoá icon"
                                                <?php echo $icon_url ? '' : 'style="display:none"'; ?>>
                                            <span class="dashicons dashicons-trash"></span> Xoá
                                        </button>
                                    </div>
                                </div>

                                <!-- URL input -->
                                <input type="url"
                                       id="cim_<?php echo esc_attr( $key ); ?>"
                                       name="cim_<?php echo esc_attr( $key ); ?>"
                                       value="<?php echo esc_url( $data[ $key ] ); ?>"
                                       placeholder="<?php echo esc_attr( $cfg['placeholder'] ); ?>"
                                       class="regular-text cim-social-url-input">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cim-card">
                        <h2 class="cim-card-title">📧 Email Marketing</h2>

                        <div class="cim-field">
                            <label for="cim_mailchimp_api_key">Mailchimp API Key</label>
                            <div class="cim-input-icon-wrap">
                                <span class="cim-input-icon dashicons dashicons-admin-network"></span>
                                <input type="password" id="cim_mailchimp_api_key" name="cim_mailchimp_api_key"
                                       value="<?php echo esc_attr( $data['mailchimp_api_key'] ); ?>"
                                       placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-us1"
                                       class="regular-text cim-api-input"
                                       autocomplete="new-password">
                                <button type="button" class="button cim-toggle-api-key" title="Hiện/Ẩn API Key">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                            <p class="description">Lấy API Key tại: <a href="https://mailchimp.com/help/about-api-keys/" target="_blank" rel="noopener">Mailchimp → Account → API Keys</a></p>
                        </div>
                    </div>

                    <div class="cim-card">
                        <h2 class="cim-card-title">🖼️ Footer</h2>

                        <!-- Mô tả ngắn footer -->
                        <div class="cim-field">
                            <label for="cim_footer_desc">Mô tả ngắn (Footer)</label>
                            <textarea id="cim_footer_desc" name="cim_footer_desc" rows="3" class="large-text"
                                      placeholder="Mô tả ngắn hiển thị trong footer..."><?php echo wp_kses_post( $data['footer_desc'] ?? '' ); ?></textarea>
                            <p class="description">Dùng shortcode <code>[cim_footer_desc]</code> hoặc tự động hiển thị trong CIM Footer.</p>
                        </div>

                        <!-- Danh sách chi nhánh -->
                        <div class="cim-field">
                            <label>Địa chỉ chi nhánh</label>
                            <div id="cim-branches-list">
                                <?php
                                $branches = [];
                                if ( ! empty( $data['branches'] ) ) {
                                    $decoded = json_decode( $data['branches'], true );
                                    if ( is_array( $decoded ) ) $branches = $decoded;
                                }
                                if ( empty( $branches ) ) $branches = [ [ 'title' => '', 'address' => '' ] ];
                                foreach ( $branches as $idx => $branch ) :
                                ?>
                                <div class="cim-branch-row">
                                    <div class="cim-branch-fields">
                                        <input type="text"
                                               name="cim_branch_title[]"
                                               value="<?php echo esc_attr( $branch['title'] ); ?>"
                                               placeholder="Tiêu đề (VD: Chi nhánh 1, Trụ sở chính...)"
                                               class="regular-text cim-branch-title">
                                        <input type="text"
                                               name="cim_branch_address[]"
                                               value="<?php echo esc_attr( $branch['address'] ); ?>"
                                               placeholder="Địa chỉ chi tiết..."
                                               class="regular-text cim-branch-address">
                                    </div>
                                    <button type="button" class="button cim-remove-branch" title="Xoá chi nhánh này"<?php echo count( $branches ) <= 1 ? ' style="visibility:hidden"' : ''; ?>>
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="cim-add-branch" class="button button-secondary" style="margin-top:10px;">
                                <span class="dashicons dashicons-plus-alt2"></span> Thêm chi nhánh
                            </button>
                            <p class="description">Dùng shortcode <code>[cim_address]</code> hoặc tự động hiển thị trong CIM Footer.</p>
                        </div>

                        <!-- Footer Background Image -->
                        <div class="cim-field">
                            <label>Ảnh nền Footer</label>
                            <div class="cim-cover-wrap">
                                <?php if ( $footer_bg_url ) : ?>
                                    <img id="cim-footer-bg-preview" src="<?php echo esc_url( $footer_bg_url ); ?>" alt="Footer Background">
                                <?php else : ?>
                                    <div id="cim-footer-bg-placeholder" class="cim-cover-placeholder">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <span>Chưa có ảnh nền footer</span>
                                    </div>
                                    <img id="cim-footer-bg-preview" src="" alt="Footer Background" style="display:none;">
                                <?php endif; ?>
                                <input type="hidden" name="cim_footer_bg" id="cim_footer_bg" value="<?php echo esc_attr( $data['footer_bg'] ); ?>">
                                <div class="cim-avatar-buttons">
                                    <button type="button" id="cim-upload-footer-bg" class="button button-secondary">
                                        <span class="dashicons dashicons-upload"></span> Chọn ảnh
                                    </button>
                                    <button type="button" id="cim-remove-footer-bg" class="button" <?php echo $footer_bg_url ? '' : 'style="display:none"'; ?>>
                                        <span class="dashicons dashicons-trash"></span> Xoá
                                    </button>
                                </div>
                            </div>
                            <p class="description">Dùng shortcode <code>[cim_footer_bg]</code> để lấy URL ảnh nền, hoặc <code>[cim_footer_section]</code> để render thẻ section có background.</p>
                        </div>

                    </div>

                    <div class="cim-submit-row">
                        <?php submit_button( '💾 Lưu thông tin', 'primary large', 'submit', false ); ?>
                    </div>
                </form>
            </div>


        </div>
    </div>
    <?php
}

// ──────────────────────────────────────────────
// Frontend CSS
// ──────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'cim_frontend_styles' );
function cim_frontend_styles() {
    wp_enqueue_style( 'cim-frontend', CIM_PLUGIN_URL . 'assets/frontend.css', [], CIM_VERSION );
}

// ──────────────────────────────────────────────
// Helper
// ──────────────────────────────────────────────
function cim_get( $key ) {
    $data = get_option( CIM_OPTION_KEY, cim_default_data() );
    return $data[ $key ] ?? '';
}

// ──────────────────────────────────────────────
// SHORTCODES
// ──────────────────────────────────────────────

// [cim_avatar size="96" class="" alt=""]
add_shortcode( 'cim_avatar', function( $atts ) {
    $atts = shortcode_atts( [ 'size' => 96, 'class' => '', 'alt' => 'Avatar' ], $atts );
    $id   = cim_get( 'avatar' );
    if ( ! $id ) return '';
    $size   = absint( $atts['size'] );
    $class  = 'cim-avatar ' . sanitize_html_class( $atts['class'] );
    return hdtc_img( $id, 'full', array(
        'alt'   => $atts['alt'],
        'class' => trim( $class ),
        'style' => 'width:' . $size . 'px;height:' . $size . 'px;object-fit:cover;border-radius:50%;',
        'sizes' => $size . 'px',
    ) );
} );

// [cim_cover_image width="" height="" class="" alt=""]
add_shortcode( 'cim_cover_image', function( $atts ) {
    $atts = shortcode_atts( [ 'width' => '100%', 'height' => '', 'class' => '', 'alt' => 'Cover' ], $atts );
    $id   = cim_get( 'cover_image' );
    if ( ! $id ) return '';
    $class  = trim( 'cim-cover-image ' . sanitize_html_class( $atts['class'] ) );
    $style  = 'width:' . $atts['width'] . ';object-fit:cover;display:block;';
    if ( $atts['height'] ) $style .= 'height:' . $atts['height'] . ';';
    return hdtc_img( $id, 'full', array(
        'alt'   => $atts['alt'],
        'class' => $class,
        'style' => $style,
    ) );
} );


add_shortcode( 'cim_full_name', function( $atts ) {
    $atts  = shortcode_atts( [ 'tag' => 'span', 'class' => '' ], $atts );
    $value = cim_get( 'full_name' );
    if ( ! $value ) return '';
    $tag   = in_array( $atts['tag'], ['span','h1','h2','h3','h4','p','strong'], true ) ? $atts['tag'] : 'span';
    $class = 'cim-full-name ' . sanitize_html_class( $atts['class'] );
    return '<' . $tag . ' class="' . esc_attr( trim( $class ) ) . '">' . esc_html( $value ) . '</' . $tag . '>';
} );

// [cim_bio tag="p" class=""]
add_shortcode( 'cim_bio', function( $atts ) {
    $atts  = shortcode_atts( [ 'tag' => 'p', 'class' => '' ], $atts );
    $value = cim_get( 'bio' );
    if ( ! $value ) return '';
    $tag   = in_array( $atts['tag'], ['div','p','span'], true ) ? $atts['tag'] : 'p';
    $class = 'cim-bio ' . sanitize_html_class( $atts['class'] );
    return '<' . $tag . ' class="' . esc_attr( trim( $class ) ) . '">' . wp_kses_post( $value ) . '</' . $tag . '>';
} );

// [cim_email link="no" label="" class=""]
add_shortcode( 'cim_email', function( $atts ) {
    $atts  = shortcode_atts( [ 'link' => 'no', 'label' => '', 'class' => '' ], $atts );
    $email = cim_get( 'email' );
    if ( ! $email ) return '';
    $class = 'cim-email ' . sanitize_html_class( $atts['class'] );
    if ( 'yes' === $atts['link'] ) {
        $label = $atts['label'] ?: $email;
        return '<a href="mailto:' . esc_attr( $email ) . '" class="' . esc_attr( trim( $class ) ) . '">' . esc_html( $label ) . '</a>';
    }
    return '<span class="' . esc_attr( trim( $class ) ) . '">' . esc_html( $email ) . '</span>';
} );

// [cim_phone link="no" label="" class=""]
add_shortcode( 'cim_phone', function( $atts ) {
    $atts  = shortcode_atts( [ 'link' => 'no', 'label' => '', 'class' => '' ], $atts );
    $phone = cim_get( 'phone' );
    if ( ! $phone ) return '';
    $class = 'cim-phone ' . sanitize_html_class( $atts['class'] );
    if ( 'yes' === $atts['link'] ) {
        $label = $atts['label'] ?: $phone;
        $tel   = preg_replace( '/\D/', '', $phone );
        return '<a href="tel:' . esc_attr( $tel ) . '" class="' . esc_attr( trim( $class ) ) . '">' . esc_html( $label ) . '</a>';
    }
    return '<span class="' . esc_attr( trim( $class ) ) . '">' . esc_html( $phone ) . '</span>';
} );

// ── Social generic helper ──
function cim_social_shortcode( $atts, $key, $default_label, $css_class ) {
    $atts     = shortcode_atts( [ 'label' => '', 'class' => '', 'show_icon' => 'yes', 'icon_size' => 20 ], $atts );
    $url      = cim_get( $key );
    if ( ! $url ) return '';

    $extra    = sanitize_html_class( $atts['class'] );
    $class    = trim( $css_class . ' ' . $extra );

    // Build icon HTML (custom upload or empty)
    $icon_html = '';
    if ( 'yes' === $atts['show_icon'] ) {
        $icon_id = cim_get( $key . '_icon' );
        if ( $icon_id ) {
            $icon_size = absint( $atts['icon_size'] );
            $icon_html = hdtc_img( $icon_id, 'thumbnail', array(
                'alt'   => '',
                'class' => 'cim-social-icon-img', // cỡ hiển thị do CSS quyết định
                'sizes' => $icon_size . 'px',
            ) );
        }
    }

    if ( $atts['label'] !== '' ) {
        $label = esc_html( $atts['label'] );
        return '<a href="' . esc_url( $url ) . '" class="cim-social-btn ' . esc_attr( $class ) . '" target="_blank" rel="noopener noreferrer">'
            . $icon_html
            . $label
            . '</a>';
    }

    // No label — plain link with optional icon
    if ( $icon_html ) {
        return '<a href="' . esc_url( $url ) . '" class="cim-social-link cim-social-icon-link ' . esc_attr( $class ) . '" target="_blank" rel="noopener noreferrer">'
            . $icon_html
            . '</a>';
    }
    return '<a href="' . esc_url( $url ) . '" class="cim-social-link ' . esc_attr( $class ) . '" target="_blank" rel="noopener noreferrer">' . esc_url( $url ) . '</a>';
}

// [cim_youtube label="" class=""]
add_shortcode( 'cim_youtube',  fn( $a ) => cim_social_shortcode( $a, 'youtube',  'YouTube',  'cim-youtube'  ) );

// [cim_facebook label="" class=""]
add_shortcode( 'cim_facebook', fn( $a ) => cim_social_shortcode( $a, 'facebook', 'Facebook', 'cim-facebook' ) );

// [cim_tiktok label="" class=""]
add_shortcode( 'cim_tiktok',   fn( $a ) => cim_social_shortcode( $a, 'tiktok',   'TikTok',   'cim-tiktok'   ) );

// ── [cim_footer_desc tag="p" class=""] — mô tả ngắn footer ──
add_shortcode( 'cim_footer_desc', function( $atts ) {
    $atts  = shortcode_atts( [ 'tag' => 'p', 'class' => '' ], $atts );
    $value = cim_get( 'footer_desc' );
    if ( ! $value ) return '';
    $tag   = in_array( $atts['tag'], ['div', 'p', 'span'], true ) ? $atts['tag'] : 'p';
    $class = trim( 'cim-footer-desc ' . sanitize_html_class( $atts['class'] ) );
    return '<' . $tag . ' class="' . esc_attr( $class ) . '">' . wp_kses_post( $value ) . '</' . $tag . '>';
} );

// ── [cim_address class=""] — danh sách chi nhánh ──
add_shortcode( 'cim_address', function( $atts ) {
    $atts     = shortcode_atts( [ 'class' => '', 'maps_link' => 'no' ], $atts );
    $raw      = cim_get( 'branches' );
    $branches = is_string( $raw ) ? json_decode( $raw, true ) : [];
    if ( empty( $branches ) || ! is_array( $branches ) ) return '';

    $class = trim( 'cim-address-list ' . sanitize_html_class( $atts['class'] ) );
    $html  = '<div class="' . esc_attr( $class ) . '">';
    foreach ( $branches as $branch ) {
        $title = sanitize_text_field( $branch['title']   ?? '' );
        $addr  = sanitize_text_field( $branch['address'] ?? '' );
        if ( ! $title && ! $addr ) continue;
        $html .= '<div class="cim-branch">';
        if ( $title ) $html .= '<span class="cim-branch-title">' . esc_html( $title ) . ':</span> ';
        if ( $addr ) {
            if ( 'yes' === $atts['maps_link'] ) {
                $url   = 'https://maps.google.com/?q=' . rawurlencode( $addr );
                $html .= '<a href="' . esc_url( $url ) . '" class="cim-branch-address" target="_blank" rel="noopener noreferrer">' . esc_html( $addr ) . '</a>';
            } else {
                $html .= '<span class="cim-branch-address">' . esc_html( $addr ) . '</span>';
            }
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
} );

// ── [cim_footer_bg] — trả về URL ảnh nền footer ──
add_shortcode( 'cim_footer_bg', function( $atts ) {
    $id = cim_get( 'footer_bg' );
    if ( ! $id ) return '';
    return esc_url( wp_get_attachment_image_url( $id, 'full' ) );
} );

// ── [cim_footer_section class="" height=""] — render section với background footer ──
add_shortcode( 'cim_footer_section', function( $atts, $content = '' ) {
    $atts  = shortcode_atts( [ 'class' => '', 'height' => '', 'tag' => 'section' ], $atts );
    $id    = cim_get( 'footer_bg' );
    if ( ! $id ) return $content ? '<' . $atts['tag'] . '>' . do_shortcode( $content ) . '</' . $atts['tag'] . '>' : '';
    $url   = wp_get_attachment_image_url( $id, 'full' );
    $tag   = in_array( $atts['tag'], ['section', 'div', 'footer', 'header'], true ) ? $atts['tag'] : 'section';
    $class = trim( 'cim-footer-section ' . sanitize_html_class( $atts['class'] ) );
    $style = 'background-image:url(' . esc_url( $url ) . ');background-size:cover;background-position:center;background-repeat:no-repeat;';
    if ( $atts['height'] ) $style .= 'min-height:' . esc_attr( $atts['height'] ) . ';';
    return '<' . $tag . ' class="' . esc_attr( $class ) . '" style="' . $style . '">' . do_shortcode( (string) $content ) . '</' . $tag . '>';
} );

// ── [cim_signature] — chữ ký đã bị gỡ ──
// Vẫn đăng ký shortcode, nhưng không in gì. Bài cũ nào đã chèn [cim_signature]
// mà bỏ đăng ký thì nguyên chuỗi "[cim_signature]" sẽ hiện ra trên trang. Tên
// shortcode cũng nằm trong danh sách định danh không được đổi (README).
add_shortcode( 'cim_signature', '__return_empty_string' );

// ── [cim_card] — card tổng hợp ──
add_shortcode( 'cim_card', function( $atts ) {
    $atts   = shortcode_atts( [ 'avatar_size' => 96, 'class' => '' ], $atts );
    $data   = get_option( CIM_OPTION_KEY, cim_default_data() );
    $size   = absint( $atts['avatar_size'] );
    $extra  = sanitize_html_class( $atts['class'] );

    $avatar_html = '';
    if ( $data['avatar'] ) {
        // width/height của thẻ img là kích thước ảnh gốc, nên cỡ hiển thị
        // phải đặt bằng style.
        $avatar_html = hdtc_img( $data['avatar'], 'full', array(
            'alt'   => $data['full_name'],
            'class' => 'cim-card-avatar',
            'style' => 'width:' . $size . 'px;height:' . $size . 'px;',
            'sizes' => $size . 'px',
        ) );
    }

    $social = '';
    foreach ( [
        'youtube'  => ['▶', 'YouTube',  'cim-btn-yt'],
        'facebook' => ['f', 'Facebook', 'cim-btn-fb'],
        'tiktok'   => ['♪', 'TikTok',   'cim-btn-tt'],
    ] as $key => [$default_icon, $label, $cls] ) {
        if ( ! $data[ $key ] ) continue;
        $icon_id  = $data[ $key . '_icon' ] ?? 0;
        $icon_html = '';
        if ( $icon_id ) {
            $icon_html = hdtc_img( $icon_id, 'thumbnail', array(
                'alt'   => '',
                'class' => 'cim-card-btn-icon', // cỡ hiển thị do CSS quyết định
                'sizes' => '20px',
            ) );
        } else {
            $icon_html = '<span class="cim-card-btn-default-icon">' . $default_icon . '</span>';
        }
        $social .= '<a href="' . esc_url( $data[ $key ] ) . '" class="cim-card-btn ' . $cls . '" target="_blank" rel="noopener noreferrer">' . $icon_html . ' ' . $label . '</a>';
    }

    $name  = $data['full_name'] ? '<div class="cim-card-name">' . esc_html( $data['full_name'] ) . '</div>' : '';
    $bio   = $data['bio']       ? '<div class="cim-card-bio">'  . wp_kses_post( $data['bio'] )   . '</div>' : '';
    $email = $data['email']     ? '<div class="cim-card-contact"><span class="cim-icon">📧</span><a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a></div>' : '';
    $phone = $data['phone']     ? '<div class="cim-card-contact"><span class="cim-icon">📞</span><a href="tel:' . esc_attr( preg_replace( '/\D/', '', $data['phone'] ) ) . '">' . esc_html( $data['phone'] ) . '</a></div>' : '';

    return '<div class="cim-card-widget ' . esc_attr( $extra ) . '">'
        . ( $avatar_html ? '<div class="cim-card-avatar-wrap">' . $avatar_html . '</div>' : '' )
        . '<div class="cim-card-body">'
        . $name . $bio . $email . $phone
        . ( $social ? '<div class="cim-card-social">' . $social . '</div>' : '' )
        . '</div>'
        . '</div>';
} );
