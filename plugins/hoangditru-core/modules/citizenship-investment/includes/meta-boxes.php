<?php
defined( 'ABSPATH' ) || exit;

/* ============================================================
   Register all meta boxes
============================================================ */
add_action( 'add_meta_boxes', 'ci_register_meta_boxes' );
function ci_register_meta_boxes() {
    $pt = 'citizenship_invest';
    $boxes = [
        [ 'ci_carousel',     'Carousel – Slide ảnh',                'ci_mb_carousel'     ],
        [ 'ci_features',     'Đặc điểm nổi bật',                   'ci_mb_features'     ],
        [ 'ci_why_us',       'Why Us',                              'ci_mb_why_us'       ],
        [ 'ci_du_an',        'Dự án bất động sản',                  'ci_mb_du_an'        ],
        [ 'ci_du_an',        'Danh mục dự án (BĐS)',               'ci_mb_du_an'        ],
        [ 'ci_programs',     'Section Chương trình',                'ci_mb_programs'     ],
        [ 'ci_news',         'Section Tin tức',                     'ci_mb_news'         ],
    ];
    foreach ( $boxes as $b ) {
        add_meta_box( $b[0], $b[1], $b[2], $pt, 'normal', 'high' );
    }
    // Page icon + h2 overlay (fixed over carousel)
    add_meta_box( 'ci_page_overlay', 'Tải lên cờ quốc gia', 'ci_mb_page_overlay', $pt, 'normal', 'high' );
}

/* ============================================================
   Save
============================================================ */
add_action( 'save_post_citizenship_invest', 'ci_save_meta' );
function ci_save_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    /* --- Carousel --- */
    $slides = isset( $_POST['ci_carousel_slides'] ) ? array_filter( array_map( 'absint', $_POST['ci_carousel_slides'] ) ) : [];
    update_post_meta( $post_id, '_ci_carousel_slides', $slides );

    /* --- Overlay --- */
    update_post_meta( $post_id, '_ci_overlay_icon', absint( $_POST['ci_overlay_icon'] ?? 0 ) );

    /* --- Features --- */
    $feats = [];
    if ( isset( $_POST['ci_feat_icon'] ) ) {
        foreach ( $_POST['ci_feat_icon'] as $i => $icon ) {
            $feats[] = [
                'icon' => absint( $icon ),
                'text' => sanitize_text_field( $_POST['ci_feat_text'][ $i ] ?? '' ),
            ];
        }
    }
    update_post_meta( $post_id, '_ci_features', $feats );

    /* --- Why Us --- */
    update_post_meta( $post_id, '_ci_why_bg',       absint( $_POST['ci_why_bg'] ?? 0 ) );
    $blocks = [];
    for ( $i = 0; $i < 3; $i++ ) {
        $blocks[] = [
            'title'   => sanitize_text_field( $_POST['ci_why_title'][ $i ] ?? '' ),
            'content' => wp_kses_post( $_POST['ci_why_content'][ $i ] ?? '' ),
        ];
    }
    update_post_meta( $post_id, '_ci_why_blocks', $blocks );

    /* --- Du an BDS --- */
    update_post_meta( $post_id, '_ci_bds_category', absint( $_POST['ci_bds_category'] ?? 0 ) );
    $bds_ids = [];
    if ( ! empty( $_POST['ci_bds_ids'] ) && is_array( $_POST['ci_bds_ids'] ) ) {
        $bds_ids = array_map( 'absint', $_POST['ci_bds_ids'] );
    }
    update_post_meta( $post_id, '_ci_bds_ids', $bds_ids );

    /* --- Programs & News (post IDs) --- */
    $programs = isset( $_POST['ci_programs'] ) ? array_filter( array_map( 'absint', (array) $_POST['ci_programs'] ) ) : [];
    update_post_meta( $post_id, '_ci_programs', $programs );

    $news = isset( $_POST['ci_news'] ) ? array_filter( array_map( 'absint', (array) $_POST['ci_news'] ) ) : [];
    update_post_meta( $post_id, '_ci_news', $news );
}

/* ============================================================
   Render helpers
============================================================ */
function ci_get_posts_by_category() {
    $cats = get_categories( [ 'hide_empty' => false ] );
    $out  = [];
    foreach ( $cats as $cat ) {
        $posts = get_posts( [ 'category' => $cat->term_id, 'numberposts' => -1, 'post_status' => 'publish' ] );
        if ( $posts ) $out[ $cat->name ] = $posts;
    }
    return $out;
}

/* ============================================================
   MB: Carousel
============================================================ */
function ci_mb_carousel( $post ) {
    $slides = get_post_meta( $post->ID, '_ci_carousel_slides', true ) ?: [];
    ?>
    <div class="ci-gallery-wrap">
        <p><strong>Slide ảnh (kéo thả để sắp xếp thứ tự)</strong></p>
        <ul id="ci-carousel-list" class="ci-img-list">
            <?php foreach ( $slides as $att_id ) :
                $url = wp_get_attachment_image_url( $att_id, 'thumbnail' );
                if ( ! $url ) continue; ?>
                <li data-id="<?php echo $att_id; ?>">
                    <img src="<?php echo esc_url( $url ); ?>">
                    <span class="ci-remove dashicons dashicons-trash"></span>
                    <input type="hidden" name="ci_carousel_slides[]" value="<?php echo $att_id; ?>">
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="button button-secondary ci-add-images" data-target="ci-carousel-list" data-input="ci_carousel_slides[]"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
    </div>
    <?php
}

/* ============================================================
   MB: Overlay icon + h2
============================================================ */
function ci_mb_page_overlay( $post ) {
    $att_id = (int) get_post_meta( $post->ID, '_ci_overlay_icon', true );
    $url    = $att_id ? wp_get_attachment_image_url( $att_id, 'thumbnail' ) : '';
    ?>
    <p><strong>Hình ảnh cờ quốc gia</strong> — hiển thị bên trái tiêu đề trang ở góc dưới carousel.</p>
    <div class="ci-single-img" data-field="ci_overlay_icon">
        <?php if ( $url ) : ?>
            <img src="<?php echo esc_url( $url ); ?>" style="max-height:60px;display:block;margin-bottom:8px;border:1px solid #ddd;padding:3px;border-radius:3px">
        <?php endif; ?>
        <input type="hidden" name="ci_overlay_icon" id="ci_overlay_icon" value="<?php echo esc_attr( $att_id ); ?>">
        <button type="button" class="button button-secondary ci-pick-img" data-field="ci_overlay_icon"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
        <button type="button" class="button ci-clear-img" data-field="ci_overlay_icon" <?php echo $att_id ? '' : 'style="display:none"'; ?>><span class="dashicons dashicons-trash"></span> Xoá</button>
    </div>
    <p class="description" style="margin-top:8px">Tiêu đề h2 lấy từ tiêu đề trang tự động.</p>
    <?php
}


/* ============================================================
   MB: Features
============================================================ */
function ci_mb_features( $post ) {
    $feats = get_post_meta( $post->ID, '_ci_features', true ) ?: [];
    ?>
    <div id="ci-features-wrap">
        <?php foreach ( $feats as $i => $f ) :
            $att_id  = (int) ( $f['icon'] ?? 0 );
            $img_url = $att_id ? wp_get_attachment_image_url( $att_id, 'thumbnail' ) : '';
        ?>
            <div class="ci-feat-row">
                <span class="ci-drag dashicons dashicons-menu"></span>

                <div class="ci-feat-icon-cell">
                    <?php if ( $img_url ) : ?>
                        <img src="<?php echo esc_url( $img_url ); ?>" class="ci-feat-thumb">
                    <?php else : ?>
                        <span class="ci-feat-thumb ci-feat-thumb--empty">?</span>
                    <?php endif; ?>
                    <input type="hidden" name="ci_feat_icon[]" value="<?php echo esc_attr( $att_id ); ?>">
                    <button type="button" class="button button-secondary ci-feat-pick-img"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
                </div>

                <input type="text" name="ci_feat_text[]"
                       value="<?php echo esc_attr( $f['text'] ); ?>"
                       placeholder="Nội dung đặc điểm..."
                       style="flex:1">
                <span class="ci-remove-feat dashicons dashicons-trash" style="cursor:pointer;color:#c00;margin-left:8px"></span>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button" id="ci-add-feat">+ Thêm đặc điểm</button>
    <?php
}

/* ============================================================
   MB: Why Us
============================================================ */
function ci_mb_why_us( $post ) {
    $bg_id  = get_post_meta( $post->ID, '_ci_why_bg', true );
    $blocks = get_post_meta( $post->ID, '_ci_why_blocks', true );
    if ( ! is_array( $blocks ) || count( $blocks ) < 3 ) {
        $blocks = array_replace( [ [], [], [] ], (array) $blocks );
    }
    $bg_url = $bg_id ? wp_get_attachment_image_url( $bg_id, 'large' ) : '';

    // wp_editor() requires unique static IDs — unroll 3 blocks explicitly
    $titles   = [ $blocks[0]['title'] ?? '', $blocks[1]['title'] ?? '', $blocks[2]['title'] ?? '' ];
    $contents = [ $blocks[0]['content'] ?? '', $blocks[1]['content'] ?? '', $blocks[2]['content'] ?? '' ];

    $editor_settings = [
        'textarea_name' => '', // set per-call below
        'textarea_rows' => 8,
        'media_buttons' => false,
        'teeny'         => false,
        'quicktags'     => true,
        'tinymce'       => [
            'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,removeformat',
            'toolbar2' => '',
        ],
    ];
    ?>
    <table class="form-table" style="margin-bottom:16px">
        <tr>
            <th style="width:140px">Background section</th>
            <td>
                <div class="ci-single-img" data-field="ci_why_bg">
                    <?php if ( $bg_url ) : ?>
                        <img src="<?php echo esc_url( $bg_url ); ?>" style="max-width:260px;display:block;margin-bottom:6px">
                    <?php endif; ?>
                    <input type="hidden" name="ci_why_bg" id="ci_why_bg" value="<?php echo esc_attr( $bg_id ); ?>">
                    <button type="button" class="button button-secondary ci-pick-img" data-field="ci_why_bg"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
                    <button type="button" class="button ci-clear-img" data-field="ci_why_bg" <?php echo $bg_id ? '' : 'style="display:none"'; ?>><span class="dashicons dashicons-trash"></span> Xoá</button>
                </div>
            </td>
        </tr>
    </table>

    <?php for ( $i = 0; $i < 3; $i++ ) : ?>
    <div style="border:1px solid #ddd;border-radius:4px;overflow:hidden;margin-bottom:16px">
        <div style="background:#f9f9f9;padding:8px 14px;border-bottom:1px solid #ddd">
            <strong>Khối <?php echo $i + 1; ?></strong>
        </div>
        <div style="padding:14px">
            <p style="margin:0 0 10px">
                <label style="display:block;font-weight:600;margin-bottom:4px;font-size:12px">Tiêu đề</label>
                <input type="text"
                       name="ci_why_title[]"
                       value="<?php echo esc_attr( $titles[ $i ] ); ?>"
                       placeholder="Tiêu đề khối..."
                       class="widefat">
            </p>
            <label style="display:block;font-weight:600;margin-bottom:6px;font-size:12px">Nội dung</label>
            <?php
            $s = $editor_settings;
            $s['textarea_name'] = 'ci_why_content[]';
            wp_editor( $contents[ $i ], 'ci_why_block_' . $i, $s );
            ?>
        </div>
    </div>
    <?php endfor; ?>
    <?php
}

/* ============================================================
   MB: Programs & News (chọn bài viết)
============================================================ */
function ci_mb_render_post_selector( $post, $meta_key, $saved_ids ) {
    $grouped = ci_get_posts_by_category();
    ?>
    <p class="description">Chọn bài viết muốn hiển thị trong section. Bỏ chọn tất cả để ẩn section.</p>
    <div style="max-height:260px;overflow-y:auto;border:1px solid #ddd;padding:10px;border-radius:4px">
        <?php foreach ( $grouped as $cat_name => $posts ) : ?>
            <p style="margin:6px 0 2px;font-weight:600;color:#555"><?php echo esc_html( $cat_name ); ?></p>
            <?php foreach ( $posts as $p ) : ?>
                <label style="display:block;padding:3px 0">
                    <input type="checkbox" name="<?php echo esc_attr( $meta_key ); ?>[]"
                           value="<?php echo $p->ID; ?>"
                           <?php checked( in_array( $p->ID, (array) $saved_ids ) ); ?>>
                    <?php echo esc_html( $p->post_title ); ?>
                </label>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ( empty( $grouped ) ) echo '<p>Chưa có bài viết nào.</p>'; ?>
    </div>
    <?php
}

/* ============================================================
   MB: Du an BDS
============================================================ */
function ci_mb_du_an( $post ) {
    $saved_cat  = get_post_meta( $post->ID, '_ci_bds_category', true );
    $saved_ids  = get_post_meta( $post->ID, '_ci_bds_ids',      true ) ?: [];
    $categories = get_terms( [ 'taxonomy' => 'bds_category', 'hide_empty' => false ] );
    ?>
    <p class="description" style="margin-bottom:12px">
        Chọn danh mục → tích chọn tối đa bất động sản muốn hiển thị (chỉ 4 BĐS đầu được hiển thị ra frontend).
    </p>

    <?php if ( is_wp_error( $categories ) || empty( $categories ) ) : ?>
        <p><em>Chưa có danh mục BĐS nào. Vui lòng tạo danh mục trong plugin Bất động sản trước.</em></p>
    <?php else : ?>

    <div style="margin-bottom:14px">
        <label style="font-weight:600;display:block;margin-bottom:6px">Danh mục bất động sản</label>
        <select id="ci_bds_category" name="ci_bds_category" style="min-width:260px;padding:6px 10px;">
            <option value="">— Chọn danh mục —</option>
            <?php foreach ( $categories as $cat ) : ?>
                <option value="<?php echo esc_attr( $cat->term_id ); ?>"
                    <?php selected( $saved_cat, $cat->term_id ); ?>>
                    <?php echo esc_html( $cat->name ); ?> (<?php echo $cat->count; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="ci-bds-list-wrap">
        <?php
        if ( $saved_cat ) {
            $bds_posts = get_posts( [
                'post_type'      => 'bat-dong-san',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'tax_query'      => [ [ 'taxonomy' => 'bds_category', 'field' => 'term_id', 'terms' => $saved_cat ] ],
            ] );
            if ( $bds_posts ) {
                echo '<div class="ci-bds-list">';
                echo '<p style="font-weight:600;margin-bottom:8px">Chọn bất động sản hiển thị <small style="color:#888;font-weight:400">(tối đa 4 BĐS đầu được hiển thị)</small></p>';
                foreach ( $bds_posts as $p ) {
                    $checked = in_array( $p->ID, (array) $saved_ids ) ? 'checked' : '';
                    echo '<label class="ci-bds-item">';
                    echo '<input type="checkbox" name="ci_bds_ids[]" value="' . esc_attr( $p->ID ) . '" ' . $checked . '>';
                    echo '<span>' . esc_html( $p->post_title ) . '</span>';
                    echo '</label>';
                }
                echo '</div>';
            } else {
                echo '<p><em>Danh mục này chưa có bất động sản nào.</em></p>';
            }
        }
        ?>
    </div>

    <?php endif; ?>

    <script>
    (function($){
        $('#ci_bds_category').on('change', function(){
            var termId = $(this).val();
            var wrap   = $('#ci-bds-list-wrap');
            if ( ! termId ) { wrap.html(''); return; }
            wrap.html('<p><em>Đang tải...</em></p>');
            $.post(ajaxurl, {
                action:  'ci_get_bds_by_cat',
                term_id: termId,
                nonce:   '<?php echo wp_create_nonce("ci_bds_ajax"); ?>'
            }, function(res){
                if ( res.success ) { wrap.html(res.data.html); }
                else { wrap.html('<p><em>' + res.data + '</em></p>'); }
            });
        });
    })(jQuery);
    </script>

    <style>
        .ci-bds-list { border:1px solid #ddd; border-radius:4px; max-height:300px; overflow-y:auto; padding:8px; }
        .ci-bds-item { display:flex; align-items:center; gap:8px; padding:7px 8px; border-bottom:1px solid #f0f0f0; cursor:pointer; font-size:13px; }
        .ci-bds-item:last-child { border-bottom:none; }
        .ci-bds-item:hover { background:#f9f9f9; }
        .ci-bds-item input { flex-shrink:0; }
    </style>
    <?php
}

function ci_mb_programs( $post ) {
    $saved = get_post_meta( $post->ID, '_ci_programs', true ) ?: [];
    ci_mb_render_post_selector( $post, 'ci_programs', $saved );
}

function ci_mb_news( $post ) {
    $saved = get_post_meta( $post->ID, '_ci_news', true ) ?: [];
    ci_mb_render_post_selector( $post, 'ci_news', $saved );
}

