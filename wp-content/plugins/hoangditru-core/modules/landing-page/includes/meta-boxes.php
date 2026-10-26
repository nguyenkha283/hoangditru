<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'lpb_register_meta_boxes' );

function lpb_register_meta_boxes() {
    $screen = [ 'golden_visa' ];
    $boxes  = [
        [ 'lpb_hero_icon',  'Hero — Icon góc dưới trái', 'lpb_mb_hero_icon'  ],
        [ 'lpb_advantages', 'Ưu điểm nổi bật',           'lpb_mb_advantages' ],
        [ 'lpb_benefits',   'Quyền lợi',                  'lpb_mb_benefits'   ],
        [ 'lpb_conditions', 'Điều kiện chương trình',     'lpb_mb_conditions' ],
        [ 'lpb_process',    'Quy trình',                  'lpb_mb_process'    ],
        [ 'lpb_du_an',      'Danh mục dự án (BĐS)',       'lpb_mb_du_an'      ],
    ];
    foreach ( $boxes as $b ) {
        add_meta_box( $b[0], $b[1], $b[2], $screen, 'normal', 'default' );
    }
}

/* ── HERO ICON ─────────────────────────────────────────────────── */
function lpb_mb_hero_icon( $post ) {
    wp_nonce_field( 'lpb_save_meta', 'lpb_nonce' );
    $icon = get_post_meta( $post->ID, 'lpb_hero_icon', true );
    ?>
    <p class="description" style="margin-bottom:10px">
        Icon hiển thị góc dưới trái banner. Khuyến nghị: PNG nền trong, ~60×60px.
    </p>
    <div class="lpb-image-picker">
        <input type="hidden" name="lpb_hero_icon" id="lpb_hero_icon"
               value="<?php echo esc_attr( $icon ); ?>" class="lpb-img-input">
        <div class="lpb-img-preview" id="lpb_hero_icon_preview">
            <?php if ( $icon ) echo '<img src="' . esc_url( $icon ) . '">'; ?>
        </div>
        <button type="button" class="button button-secondary lpb-upload-btn"
                data-target="lpb_hero_icon" data-preview="lpb_hero_icon_preview"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
        <button type="button" class="button lpb-remove-btn"
                data-target="lpb_hero_icon" data-preview="lpb_hero_icon_preview"
                <?php echo $icon ? '' : 'style="display:none"'; ?>><span class="dashicons dashicons-trash"></span> Xoá</button>
    </div>
    <?php
}

/* ── ƯU ĐIỂM NỔI BẬT ───────────────────────────────────────────── */
function lpb_mb_advantages( $post ) {
    $rows = get_post_meta( $post->ID, 'lpb_advantages', true ) ?: [];
    ?>
    <div class="lpb-repeater">
        <div class="lpb-rows">
        <?php foreach ( $rows as $i => $row ) :
            $icon = $row['icon'] ?? '';
            $text = $row['text'] ?? '';
        ?>
            <div class="lpb-row">
                <div class="lpb-row-handle">☰</div>
                <div class="lpb-row-fields">
                    <div class="lpb-image-picker lpb-inline">
                        <input type="hidden" name="lpb_advantages[<?php echo $i; ?>][icon]"
                               value="<?php echo esc_attr( $icon ); ?>" class="lpb-img-input">
                        <div class="lpb-img-preview lpb-sm">
                            <?php if ( $icon ) echo '<img src="' . esc_url( $icon ) . '">'; ?>
                        </div>
                        <button type="button" class="button button-secondary lpb-upload-btn"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
                    </div>
                    <input type="text" name="lpb_advantages[<?php echo $i; ?>][text]"
                           value="<?php echo esc_attr( $text ); ?>"
                           placeholder="Nội dung ưu điểm" class="widefat">
                </div>
                <button type="button" class="button lpb-remove-row">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button lpb-add-row"
                data-group="lpb_advantages" data-template="advantage">+ Thêm ưu điểm</button>
    </div>
    <?php
}

/* ── QUYỀN LỢI ─────────────────────────────────────────────────── */
function lpb_mb_benefits( $post ) {
    $rows = get_post_meta( $post->ID, 'lpb_benefits', true ) ?: [];
    ?>
    <div class="lpb-repeater">
        <div class="lpb-rows">
        <?php foreach ( $rows as $i => $row ) :
            $img  = $row['image'] ?? '';
            $desc = $row['desc']  ?? '';
        ?>
            <div class="lpb-row">
                <div class="lpb-row-handle">☰</div>
                <div class="lpb-row-fields lpb-vertical">
                    <div class="lpb-image-picker">
                        <input type="hidden" name="lpb_benefits[<?php echo $i; ?>][image]"
                               value="<?php echo esc_attr( $img ); ?>" class="lpb-img-input">
                        <div class="lpb-img-preview">
                            <?php if ( $img ) echo '<img src="' . esc_url( $img ) . '">'; ?>
                        </div>
                        <button type="button" class="button button-secondary lpb-upload-btn"><span class="dashicons dashicons-upload"></span> Chọn ảnh</button>
                    </div>
                    <textarea name="lpb_benefits[<?php echo $i; ?>][desc]" rows="2"
                              placeholder="Mô tả quyền lợi" class="widefat"><?php echo esc_textarea( $desc ); ?></textarea>
                </div>
                <button type="button" class="button lpb-remove-row">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button lpb-add-row"
                data-group="lpb_benefits" data-template="benefit">+ Thêm quyền lợi</button>
    </div>
    <?php
}

/* ── ĐIỀU KIỆN CHƯƠNG TRÌNH ─────────────────────────────────────── */
function lpb_mb_conditions( $post ) {
    $rows = get_post_meta( $post->ID, 'lpb_conditions', true ) ?: [];
    ?>
    <div class="lpb-repeater">
        <div class="lpb-rows">
        <?php foreach ( $rows as $i => $row ) :
            $title   = $row['title']   ?? '';
            $content = $row['content'] ?? '';
        ?>
            <div class="lpb-row lpb-row-block">
                <div class="lpb-row-top">
                    <div class="lpb-row-handle">☰</div>
                    <input type="text" name="lpb_conditions[<?php echo $i; ?>][title]"
                           value="<?php echo esc_attr( $title ); ?>"
                           placeholder="Tiêu đề điều kiện" class="widefat">
                    <button type="button" class="button lpb-remove-row">✕</button>
                </div>
                <div class="lpb-row-body">
                    <textarea name="lpb_conditions[<?php echo $i; ?>][content]" rows="4"
                              placeholder="Nội dung (hiển thị khi mở accordion)"
                              class="widefat"><?php echo esc_textarea( $content ); ?></textarea>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button lpb-add-row"
                data-group="lpb_conditions" data-template="condition">+ Thêm điều kiện</button>
    </div>
    <?php
}

/* ── QUY TRÌNH ─────────────────────────────────────────────────── */
function lpb_mb_process( $post ) {
    $rows = get_post_meta( $post->ID, 'lpb_process', true ) ?: [];
    ?>
    <div class="lpb-repeater">
        <div class="lpb-rows">
        <?php foreach ( $rows as $i => $row ) :
            $title = $row['title'] ?? '';
            $desc  = $row['desc']  ?? '';
        ?>
            <div class="lpb-row lpb-row-block">
                <div class="lpb-row-top">
                    <div class="lpb-row-handle">☰</div>
                    <span class="lpb-step-num">Bước <?php echo $i + 1; ?></span>
                    <input type="text" name="lpb_process[<?php echo $i; ?>][title]"
                           value="<?php echo esc_attr( $title ); ?>"
                           placeholder="Tiêu đề bước" class="widefat">
                    <button type="button" class="button lpb-remove-row">✕</button>
                </div>
                <div class="lpb-row-body">
                    <textarea name="lpb_process[<?php echo $i; ?>][desc]" rows="2"
                              placeholder="Mô tả bước"
                              class="widefat"><?php echo esc_textarea( $desc ); ?></textarea>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="button lpb-add-row"
                data-group="lpb_process" data-template="process">+ Thêm bước</button>
    </div>
    <?php
}

/* ── DANH MỤC DỰ ÁN (BĐS) ─────────────────────────────────────── */
function lpb_mb_du_an( $post ) {
    $saved_cat     = get_post_meta( $post->ID, 'lpb_bds_category', true );
    $saved_ids     = get_post_meta( $post->ID, 'lpb_bds_ids',      true ) ?: [];
    $categories    = get_terms( [ 'taxonomy' => 'bds_category', 'hide_empty' => false ] );
    ?>
    <p class="description" style="margin-bottom:12px">
        Chọn danh mục → tích chọn tối đa bất động sản muốn hiển thị (chỉ 4 BĐS đầu được hiển thị ra frontend).
    </p>

    <?php if ( is_wp_error( $categories ) || empty( $categories ) ) : ?>
        <p><em>Chưa có danh mục BĐS nào. Vui lòng tạo danh mục trong plugin Bất động sản trước.</em></p>
    <?php else : ?>

    <!-- Dropdown chọn danh mục -->
    <div style="margin-bottom:14px">
        <label style="font-weight:600;display:block;margin-bottom:6px">Danh mục bất động sản</label>
        <select id="lpb_bds_category" name="lpb_bds_category" style="min-width:260px;padding:6px 10px;">
            <option value="">— Chọn danh mục —</option>
            <?php foreach ( $categories as $cat ) : ?>
                <option value="<?php echo esc_attr( $cat->term_id ); ?>"
                    <?php selected( $saved_cat, $cat->term_id ); ?>>
                    <?php echo esc_html( $cat->name ); ?> (<?php echo $cat->count; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- List BĐS theo danh mục -->
    <div id="lpb-bds-list-wrap">
        <?php
        // Nếu đã có danh mục lưu, render list ngay
        if ( $saved_cat ) {
            $bds_posts = get_posts( [
                'post_type'      => 'bat-dong-san',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'tax_query'      => [ [ 'taxonomy' => 'bds_category', 'field' => 'term_id', 'terms' => $saved_cat ] ],
            ] );
            if ( $bds_posts ) {
                echo '<div class="lpb-bds-list">';
                echo '<p style="font-weight:600;margin-bottom:8px">Chọn bất động sản hiển thị <small style="color:#888;font-weight:400">(tối đa 4 BĐS đầu được hiển thị)</small></p>';
                foreach ( $bds_posts as $p ) {
                    $checked = in_array( $p->ID, (array) $saved_ids ) ? 'checked' : '';
                    echo '<label class="lpb-bds-item">';
                    echo '<input type="checkbox" name="lpb_bds_ids[]" value="' . $p->ID . '" ' . $checked . '>';
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
        $('#lpb_bds_category').on('change', function(){
            var termId = $(this).val();
            var wrap   = $('#lpb-bds-list-wrap');
            if ( ! termId ) { wrap.html(''); return; }

            wrap.html('<p><em>Đang tải...</em></p>');

            $.post(ajaxurl, {
                action:  'lpb_get_bds_by_cat',
                term_id: termId,
                nonce:   '<?php echo wp_create_nonce("lpb_bds_ajax"); ?>'
            }, function(res){
                if ( res.success ) {
                    wrap.html(res.data.html);
                } else {
                    wrap.html('<p><em>' + res.data + '</em></p>');
                }
            });
        });
    })(jQuery);
    </script>

    <style>
        .lpb-bds-list { border:1px solid #ddd; border-radius:4px; max-height:300px; overflow-y:auto; padding:8px; }
        .lpb-bds-item { display:flex; align-items:center; gap:8px; padding:7px 8px; border-bottom:1px solid #f0f0f0; cursor:pointer; font-size:13px; }
        .lpb-bds-item:last-child { border-bottom:none; }
        .lpb-bds-item:hover { background:#f9f9f9; }
        .lpb-bds-item input { flex-shrink:0; }
    </style>
    <?php
}
