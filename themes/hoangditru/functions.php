<?php
/**
 * Theme Hoangditru — độc lập, không còn là theme con của Flatsome.
 *
 * Header hiển thị do plugin trang-chu dựng (hook wp_body_open), footer do plugin
 * cim-footer dựng (hook wp_footer). Theme chỉ lo khung HTML, khai báo tính năng
 * và các template WordPress chuẩn.
 */

define( 'HDT_VERSION', '2.1.4' );

// ------------------------------------------------------------------
// 1. Khai báo tính năng theme
// ------------------------------------------------------------------
add_action( 'after_setup_theme', 'hdt_setup' );
function hdt_setup() {
    load_theme_textdomain( 'hoangditru', get_template_directory() . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );   // plugin dùng has_post_thumbnail()
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', array(
        'search-form', 'gallery', 'caption', 'style', 'script',
    ) );

    // Plugin trang-chu gọi wp_nav_menu( 'theme_location' => 'primary' ) ở 2 chỗ.
    // Không khai báo thì menu rơi vào fallback và chỉ còn link "Trang chủ".
    register_nav_menus( array(
        'primary' => __( 'Menu chính', 'hoangditru' ),
        'footer'  => __( 'Menu chân trang', 'hoangditru' ),
    ) );
}

// ------------------------------------------------------------------
// 2. CSS
// ------------------------------------------------------------------
add_action( 'wp_enqueue_scripts', 'hdt_enqueue_styles' );
function hdt_enqueue_styles() {
    /*
     * Google Fonts: chỉ Phudu, dùng cho tiêu đề. Font body là UTMAptima, file
     * local khai bằng @font-face trong style.css nên không nạp ở đây.
     *
     * Dải wght@300..900 thay vì liệt kê từng weight: Phudu là variable font
     * trên Google Fonts nên một dải trả về MỘT file cho mỗi tập ký tự, thay vì
     * một file cho mỗi weight. Dải này là toàn bộ khoảng Phudu có; theme dùng
     * 700, CSS của plugin dùng thêm 400–800, dải này phủ hết.
     *
     * Version để null: Google không nhận tham số ?ver=, mà WordPress sẽ tự
     * thêm nếu truyền chuỗi version.
     */
    wp_enqueue_style(
        'hoangditru-fonts',
        'https://fonts.googleapis.com/css2?family=Phudu:wght@300..900&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'hoangditru-style',
        get_stylesheet_uri(),
        array( 'hoangditru-fonts' ),
        HDT_VERSION
    );
}

/**
 * Mở sẵn kết nối tới Google Fonts để font về sớm hơn.
 *
 * @param array  $urls          Danh sách URL.
 * @param string $relation_type Loại resource hint.
 * @return array
 */
add_filter( 'wp_resource_hints', 'hdt_resource_hints', 10, 2 );
function hdt_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        );
    }

    return $urls;
}

// ------------------------------------------------------------------
// 3. Shim shortcode của Flatsome
//
// Nội dung cũ còn sót vài shortcode UX Builder (trong DB: 1 [ux_image],
// 5 [ux_text], 5 [section]). Không có Flatsome thì WordPress in nguyên chuỗi
// "[ux_text ...]" ra trang. Các shim dưới đây chỉ nhả phần nội dung bên trong
// để trang không bị lộ shortcode thô.
// ------------------------------------------------------------------
add_action( 'init', 'hdt_register_legacy_shortcodes' );
function hdt_register_legacy_shortcodes() {
    foreach ( array( 'ux_text', 'section', 'row', 'col', 'ux_banner' ) as $tag ) {
        if ( ! shortcode_exists( $tag ) ) {
            add_shortcode( $tag, 'hdt_legacy_shortcode_passthrough' );
        }
    }

    if ( ! shortcode_exists( 'ux_image' ) ) {
        add_shortcode( 'ux_image', 'hdt_legacy_ux_image' );
    }
}

/**
 * Nhả nội dung bên trong, bỏ thẻ bao.
 *
 * @param array       $atts    Thuộc tính shortcode (không dùng).
 * @param string|null $content Nội dung bên trong.
 * @return string
 */
function hdt_legacy_shortcode_passthrough( $atts, $content = null ) {
    return null === $content ? '' : do_shortcode( $content );
}

/**
 * [ux_image id="123"] → thẻ img.
 *
 * @param array $atts Thuộc tính shortcode.
 * @return string
 */
function hdt_legacy_ux_image( $atts ) {
    $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'ux_image' );
    $id   = absint( $atts['id'] );

    return $id ? wp_get_attachment_image( $id, 'large' ) : '';
}


/**
 * ============================================================
 * PINNED POST FEATURE — Chọn ghim trực tiếp từ Edit Post
 * Thêm đoạn code này vào functions.php của theme
 * ============================================================
 */

// ------------------------------------------------------------------
// 1. Thêm Meta Box "Ghim bài viết" trong trang Edit Post
// ------------------------------------------------------------------
add_action( 'add_meta_boxes', 'catpin_add_meta_box' );
function catpin_add_meta_box() {
    add_meta_box(
        'catpin_pin_box',
        '📌 Ghim bài viết',
        'catpin_render_meta_box',
        'post',
        'side',   // Hiển thị cột bên phải
        'high'    // Hiển thị gần đầu
    );
}

function catpin_render_meta_box( $post ) {
    wp_nonce_field( 'catpin_save_post_meta', 'catpin_post_nonce' );

    // Lấy tất cả category của bài viết này
    $post_cats = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );

    if ( empty( $post_cats ) ) {
        echo '<p style="color:#999;font-size:12px;">Bài viết chưa thuộc category nào.<br>Hãy chọn category trước.</p>';
        return;
    }
    ?>
    <style>
        #catpin_pin_box .inside { padding: 8px 12px 12px; }
        .catpin-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid #f0f0f0;
            gap: 8px;
        }
        .catpin-row:last-child { border-bottom: none; }
        .catpin-cat-name {
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            flex: 1;
            min-width: 0;
        }
        .catpin-cat-name .catpin-sub {
            display: block;
            font-size: 11px;
            font-weight: 400;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .catpin-sub.is-pinned  { color: #2271b1; }
        .catpin-sub.other-pinned { color: #b32d2e; }
        /* Toggle switch */
        .catpin-toggle {
            position: relative;
            width: 40px;
            height: 22px;
            flex-shrink: 0;
        }
        .catpin-toggle input { opacity: 0; width: 0; height: 0; }
        .catpin-slider {
            position: absolute;
            inset: 0;
            background: #ccc;
            border-radius: 22px;
            cursor: pointer;
            transition: .25s;
        }
        .catpin-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .25s;
            box-shadow: 0 1px 3px rgba(0,0,0,.3);
        }
        .catpin-toggle input:checked + .catpin-slider { background: #2271b1; }
        .catpin-toggle input:checked + .catpin-slider::before { transform: translateX(18px); }
    </style>

    <p style="font-size:12px;color:#666;margin:0 0 10px;line-height:1.5;">
        Bật toggle để ghim bài viết này làm <strong>bài nổi bật</strong> của category tương ứng.
    </p>

    <?php foreach ( $post_cats as $cat ) :
        $pinned_id   = (int) get_term_meta( $cat->term_id, '_pinned_post_id', true );
        $is_pinned   = ( $pinned_id === $post->ID );

        // Bài khác đang được ghim
        $other_title = '';
        if ( $pinned_id && ! $is_pinned ) {
            $other = get_post( $pinned_id );
            $other_title = $other ? wp_trim_words( $other->post_title, 6 ) : '';
        }
    ?>
    <div class="catpin-row">
        <div class="catpin-cat-name">
            <?php echo esc_html( $cat->name ); ?>
            <?php if ( $is_pinned ) : ?>
                <span class="catpin-sub is-pinned">✓ Đang được ghim</span>
            <?php elseif ( $other_title ) : ?>
                <span class="catpin-sub other-pinned" title="<?php echo esc_attr( $other_title ); ?>">
                    Đang ghim: <?php echo esc_html( $other_title ); ?>
                </span>
            <?php endif; ?>
        </div>

        <label class="catpin-toggle"
               title="Ghim vào «<?php echo esc_attr( $cat->name ); ?>»">
            <input type="checkbox"
                   name="catpin_pin[<?php echo esc_attr( $cat->term_id ); ?>]"
                   value="1"
                   <?php checked( $is_pinned ); ?>>
            <span class="catpin-slider"></span>
        </label>
    </div>
    <?php endforeach; ?>
    <?php
}

// ------------------------------------------------------------------
// 2. Lưu khi Save / Update bài viết
// ------------------------------------------------------------------
add_action( 'save_post_post', 'catpin_save_post_meta', 10, 2 );
function catpin_save_post_meta( $post_id, $post ) {
    // Bỏ qua autosave, revision
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;

    // Verify nonce
    if (
        ! isset( $_POST['catpin_post_nonce'] ) ||
        ! wp_verify_nonce( $_POST['catpin_post_nonce'], 'catpin_save_post_meta' )
    ) return;

    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $post_cats   = wp_get_post_categories( $post_id );
    $pinned_cats = isset( $_POST['catpin_pin'] )
        ? array_map( 'absint', array_keys( (array) $_POST['catpin_pin'] ) )
        : array();

    foreach ( $post_cats as $cat_id ) {
        if ( in_array( $cat_id, $pinned_cats, true ) ) {
            // Ghim bài này vào category (ghi đè bài cũ)
            update_term_meta( $cat_id, '_pinned_post_id', $post_id );
        } else {
            // Nếu bài này đang được ghim ở category đó → bỏ ghim
            $current = (int) get_term_meta( $cat_id, '_pinned_post_id', true );
            if ( $current === $post_id ) {
                delete_term_meta( $cat_id, '_pinned_post_id' );
            }
        }
    }
}

// ------------------------------------------------------------------
// 3. Cột "📌" trong danh sách Posts
// ------------------------------------------------------------------
add_filter( 'manage_posts_columns', 'catpin_add_posts_column' );
function catpin_add_posts_column( $columns ) {
    $new = array();
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['catpin_status'] = '📌';
        }
    }
    return $new;
}

add_action( 'manage_posts_custom_column', 'catpin_render_posts_column', 10, 2 );
function catpin_render_posts_column( $column, $post_id ) {
    if ( 'catpin_status' !== $column ) return;

    $post_cats = wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );
    $pinned_in = array();

    foreach ( $post_cats as $cat ) {
        $pinned_id = (int) get_term_meta( $cat->term_id, '_pinned_post_id', true );
        if ( $pinned_id === $post_id ) {
            $pinned_in[] = $cat->name;
        }
    }

    if ( ! empty( $pinned_in ) ) {
        echo '<span title="Ghim trong: ' . esc_attr( implode( ', ', $pinned_in ) ) . '" '
           . 'style="font-size:16px;cursor:help;display:block;text-align:center;">📌</span>'
           . '<div style="font-size:10px;color:#2271b1;text-align:center;line-height:1.3;">'
           . esc_html( implode( ', ', $pinned_in ) ) . '</div>';
    } else {
        echo '<span style="color:#ddd;font-size:16px;display:block;text-align:center;">○</span>';
    }
}

// ------------------------------------------------------------------
// 4. Helper dùng trong category.php
// ------------------------------------------------------------------
if ( ! function_exists( 'catpin_get_pinned_post' ) ) {
    /**
     * Trả về WP_Post đang được ghim của category, hoặc null.
     *
     * @param int $cat_id Term ID của category.
     * @return WP_Post|null
     */
    function catpin_get_pinned_post( $cat_id ) {
        $pinned_id = get_term_meta( $cat_id, '_pinned_post_id', true );
        if ( ! $pinned_id ) return null;
        $post = get_post( $pinned_id );
        if ( $post && 'publish' === $post->post_status ) return $post;
        return null;
    }
}


// ------------------------------------------------------------------
// 5. Trang chương trình: Golden Visa và Đầu tư quốc tịch
//
// Hai post type này là trang giới thiệu chương trình, sống nhiều năm và được
// sửa dần chứ không phải tin bài. In ngày đăng lên đó là nói với khách rằng
// nội dung có từ 2026 và từ đó chưa ai đụng vào — ngược hẳn ý muốn. Tin tức và
// Kiến thức thì ngày đăng vẫn có nghĩa, nên category.php giữ nguyên.
// ------------------------------------------------------------------
const HDT_PROGRAM_POST_TYPES = array( 'golden_visa', 'citizenship_invest' );

/**
 * Post type này có phải trang chương trình không.
 *
 * Quyết định theo POST TYPE chứ không theo màn đang xem: một bài Golden Visa
 * lọt vào trang kết quả tìm kiếm cũng không nên có ngày đăng. Theo màn thì hai
 * chỗ hiển thị khác nhau cho cùng một bài.
 *
 * @param string|null $post_type Bỏ trống thì lấy post type của bài đang lặp.
 * @return bool
 */
function hdt_is_program_post_type( $post_type = null ) {
    if ( null === $post_type ) {
        $post_type = get_post_type();
    }

    return in_array( $post_type, HDT_PROGRAM_POST_TYPES, true );
}

// ------------------------------------------------------------------
// 6. Truy vấn danh mục: 9 bài mỗi trang, trừ bài ghim
//
// category.php xếp bài ghim riêng một khu rồi liệt kê 9 bài còn lại bên dưới.
// Trước đây nó tự dựng một WP_Query thứ hai để làm việc đó, và phân trang đếm
// theo query thứ hai ấy — trong khi WordPress quyết định 404 theo query CHÍNH.
// Hai bên lệch nhau là ra link dẫn vào trang 404: Kiến thức có 20 bài, query
// chính 10 bài/trang ra 2 trang, còn phân trang đếm 9 bài/trang ra 3 trang nên
// in ra link /page/3/ mà mở lên là "không tìm thấy".
//
// Nắn thẳng query chính ở đây thì chỉ còn MỘT truy vấn, hai con số không còn
// đường lệch nhau nữa. Đổi số 9 ở đây là phân trang và 404 tự đi theo.
// ------------------------------------------------------------------
const HDT_CATEGORY_PER_PAGE = 9;

add_action( 'pre_get_posts', 'hdt_category_query' );
function hdt_category_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() ) {
        return;
    }

    // Feed có số bài riêng trong Cài đặt → Đọc, không đụng vào.
    if ( $query->is_feed() ) {
        return;
    }

    $query->set( 'posts_per_page', HDT_CATEGORY_PER_PAGE );

    $pinned_id = (int) get_term_meta( $query->get_queried_object_id(), '_pinned_post_id', true );

    if ( $pinned_id ) {
        $query->set( 'post__not_in', array( $pinned_id ) );
    }
}
