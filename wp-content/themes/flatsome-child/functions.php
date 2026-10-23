<?php
add_action( 'wp_enqueue_scripts', 'flatsome_child_enqueue_styles' );
function flatsome_child_enqueue_styles() {
    wp_enqueue_style(
        'flatsome-parent-style',
        get_template_directory_uri() . '/style.css'
    );
    wp_enqueue_style(
        'flatsome-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'flatsome-parent-style' )
    );
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

