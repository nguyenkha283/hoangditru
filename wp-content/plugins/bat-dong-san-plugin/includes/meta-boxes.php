<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------
 * Đăng ký tất cả meta boxes
 * ----------------------------------------------------- */
add_action( 'add_meta_boxes', 'bds_add_meta_boxes' );
function bds_add_meta_boxes() {
    add_meta_box( 'bds_thong_tin',  'Thông tin bất động sản', 'bds_meta_thong_tin_cb',  'bat-dong-san', 'normal', 'high' );
    add_meta_box( 'bds_carousel',   'Hình ảnh carousel',      'bds_meta_carousel_cb',   'bat-dong-san', 'normal', 'high' );
    add_meta_box( 'bds_tong_quan',  'Tổng quan dự án',        'bds_meta_tong_quan_cb',  'bat-dong-san', 'normal', 'default' );
    add_meta_box( 'bds_mat_bang',   'Mặt bằng',               'bds_meta_mat_bang_cb',   'bat-dong-san', 'normal', 'default' );
}

/* -------------------------------------------------------
 * 1. Meta box: Thông tin cơ bản
 * ----------------------------------------------------- */
function bds_meta_thong_tin_cb( $post ) {
    wp_nonce_field( 'bds_save_meta', 'bds_nonce' );
    $dia_chi   = get_post_meta( $post->ID, '_bds_dia_chi',   true );
    $khoang_gia = get_post_meta( $post->ID, '_bds_khoang_gia', true );
    $loai_hinh  = get_post_meta( $post->ID, '_bds_loai_hinh',  true );
    ?>
    <style>
        .bds-meta-row { display:flex; gap:20px; margin-bottom:14px; }
        .bds-meta-row label { font-weight:600; display:block; margin-bottom:4px; }
        .bds-meta-row .bds-field { flex:1; }
        .bds-meta-row input, .bds-meta-row select { width:100%; padding:6px 10px; border:1px solid #ddd; border-radius:4px; }
    </style>
    <div class="bds-meta-row">
        <div class="bds-field">
            <label>Địa chỉ</label>
            <input type="text" name="bds_dia_chi" value="<?php echo esc_attr($dia_chi); ?>" placeholder="Nhập địa chỉ dự án..." />
        </div>
    </div>
    <div class="bds-meta-row">
        <div class="bds-field">
            <label>Khoảng giá</label>
            <input type="text" name="bds_khoang_gia" value="<?php echo esc_attr($khoang_gia); ?>" placeholder="VD: 2 tỷ - 5 tỷ" />
        </div>
        <div class="bds-field">
            <label>Loại hình</label>
            <input type="text" name="bds_loai_hinh" value="<?php echo esc_attr($loai_hinh); ?>" placeholder="VD: Căn hộ chung cư" />
        </div>
    </div>
    <?php
}

/* -------------------------------------------------------
 * 2. Meta box: Carousel (Slick Syncing)
 * ----------------------------------------------------- */
function bds_meta_carousel_cb( $post ) {
    $images = get_post_meta( $post->ID, '_bds_carousel_images', true );
    $images = $images ? $images : [];
    ?>
    <style>
        #bds-carousel-wrap { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px; min-height:60px; border:2px dashed #ddd; padding:10px; border-radius:6px; }
        .bds-carousel-img-item { position:relative; width:120px; height:80px; }
        .bds-carousel-img-item img { width:100%; height:100%; object-fit:cover; border-radius:4px; }
        .bds-carousel-img-item .remove-img { position:absolute; top:-8px; right:-8px; background:#e74c3c; color:#fff; border:none; border-radius:50%; width:22px; height:22px; cursor:pointer; font-size:14px; line-height:22px; text-align:center; padding:0; }
        #bds-add-carousel-img { margin-top:4px; }
    </style>
    <div id="bds-carousel-wrap">
        <?php foreach ( $images as $img_id ) :
            $src = wp_get_attachment_image_src( $img_id, 'thumbnail' );
            if ( ! $src ) continue; ?>
            <div class="bds-carousel-img-item" data-id="<?php echo esc_attr($img_id); ?>">
                <img src="<?php echo esc_url($src[0]); ?>" />
                <button type="button" class="remove-img" title="Xóa">&times;</button>
                <input type="hidden" name="bds_carousel_images[]" value="<?php echo esc_attr($img_id); ?>" />
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="bds-add-carousel-img" class="button">+ Thêm hình ảnh</button>

    <script>
    jQuery(function($){
        $('#bds-add-carousel-img').on('click', function(){
            var frame = wp.media({ title: 'Chọn hình ảnh carousel', button: { text: 'Chọn' }, multiple: true });
            frame.on('select', function(){
                frame.state().get('selection').each(function(attachment){
                    var id  = attachment.toJSON().id;
                    var src = attachment.toJSON().sizes && attachment.toJSON().sizes.thumbnail
                              ? attachment.toJSON().sizes.thumbnail.url
                              : attachment.toJSON().url;
                    var item = $('<div class="bds-carousel-img-item" data-id="'+id+'">'+
                        '<img src="'+src+'" />'+
                        '<button type="button" class="remove-img" title="Xóa">&times;</button>'+
                        '<input type="hidden" name="bds_carousel_images[]" value="'+id+'" />'+
                    '</div>');
                    $('#bds-carousel-wrap').append(item);
                });
            });
            frame.open();
        });
        $(document).on('click', '.remove-img', function(){
            $(this).closest('.bds-carousel-img-item').remove();
        });
    });
    </script>
    <?php
}

/* -------------------------------------------------------
 * 3. Meta box: Tổng quan
 * ----------------------------------------------------- */
function bds_meta_tong_quan_cb( $post ) {
    $tq_fields = get_post_meta( $post->ID, '_bds_tong_quan', true );
    $defaults  = [
        ['label' => 'Tên dự án',            'value' => ''],
        ['label' => 'Chủ đầu tư',           'value' => ''],
        ['label' => 'Diện tích đất',        'value' => ''],
        ['label' => 'Loại hình sản phẩm',   'value' => ''],
        ['label' => 'Số toà',               'value' => ''],
        ['label' => 'Chiều cao',             'value' => ''],
        ['label' => 'Tổng số sản phẩm',     'value' => ''],
        ['label' => 'Diện tích trung bình',  'value' => ''],
        ['label' => 'Năm bàn giao',         'value' => ''],
        ['label' => 'Tiêu chuẩn bàn giao',  'value' => ''],
    ];
    $rows = $tq_fields ? $tq_fields : $defaults;
    ?>
    <style>
        #bds-tq-table { width:100%; border-collapse:collapse; }
        #bds-tq-table th { text-align:left; padding:8px; background:#f5f5f5; font-size:12px; color:#666; }
        #bds-tq-table td { padding:6px 4px; }
        #bds-tq-table input { width:100%; padding:5px 8px; border:1px solid #ddd; border-radius:3px; box-sizing:border-box; }
        .bds-tq-remove { background:#e74c3c; color:#fff; border:none; border-radius:3px; padding:4px 8px; cursor:pointer; }
        #bds-tq-add { margin-top:10px; }
    </style>
    <table id="bds-tq-table">
        <thead><tr><th>Tiêu đề</th><th>Giá trị</th><th></th></tr></thead>
        <tbody id="bds-tq-body">
        <?php foreach ( $rows as $i => $row ) : ?>
            <tr>
                <td><input type="text" name="bds_tq[<?php echo $i; ?>][label]" value="<?php echo esc_attr($row['label']); ?>" placeholder="Tiêu đề..." /></td>
                <td><input type="text" name="bds_tq[<?php echo $i; ?>][value]" value="<?php echo esc_attr($row['value']); ?>" placeholder="Giá trị..." /></td>
                <td><button type="button" class="bds-tq-remove">✕</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" id="bds-tq-add" class="button">+ Thêm mới giá trị</button>
    <script>
    jQuery(function($){
        $('#bds-tq-add').on('click', function(){
            var idx = $('#bds-tq-body tr').length;
            $('#bds-tq-body').append(
                '<tr>'+
                  '<td><input type="text" name="bds_tq['+idx+'][label]" placeholder="Tiêu đề..." /></td>'+
                  '<td><input type="text" name="bds_tq['+idx+'][value]" placeholder="Giá trị..." /></td>'+
                  '<td><button type="button" class="bds-tq-remove">✕</button></td>'+
                '</tr>'
            );
        });
        $(document).on('click', '.bds-tq-remove', function(){
            $(this).closest('tr').remove();
            // Re-index
            $('#bds-tq-body tr').each(function(i){
                $(this).find('input').each(function(){
                    var n = $(this).attr('name').replace(/\[\d+\]/, '['+i+']');
                    $(this).attr('name', n);
                });
            });
        });
    });
    </script>
    <?php
}

/* -------------------------------------------------------
 * 4. Meta box: Mặt bằng (accordion)
 * ----------------------------------------------------- */
function bds_meta_mat_bang_cb( $post ) {
    $mat_bang = get_post_meta( $post->ID, '_bds_mat_bang', true );
    $mat_bang = $mat_bang ? $mat_bang : [];
    ?>
    <style>
        .bds-mb-item { border:1px solid #e0e0e0; border-radius:6px; margin-bottom:12px; padding:12px; background:#fafafa; }
        .bds-mb-item input[type="text"] { width:100%; padding:6px 8px; border:1px solid #ddd; border-radius:3px; margin-bottom:8px; box-sizing:border-box; }
        .bds-mb-images { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px; min-height:40px; border:1px dashed #ccc; padding:8px; border-radius:4px; }
        .bds-mb-img-item { position:relative; width:80px; height:60px; }
        .bds-mb-img-item img { width:100%; height:100%; object-fit:cover; border-radius:3px; }
        .bds-mb-img-item .rem-mb-img { position:absolute; top:-6px; right:-6px; background:#e74c3c; color:#fff; border:none; border-radius:50%; width:18px; height:18px; cursor:pointer; font-size:11px; line-height:18px; text-align:center; padding:0; }
        .bds-mb-actions { display:flex; gap:8px; }
        .bds-mb-remove { background:#e74c3c; color:#fff; border:none; border-radius:3px; padding:5px 10px; cursor:pointer; }
    </style>
    <div id="bds-mb-wrap">
    <?php foreach ( $mat_bang as $i => $mb ) :
        $img_ids = isset($mb['images']) ? $mb['images'] : [];
    ?>
        <div class="bds-mb-item">
            <input type="text" name="bds_mat_bang[<?php echo $i; ?>][title]" value="<?php echo esc_attr($mb['title']); ?>" placeholder="Tiêu đề mặt bằng (VD: Tầng 1-5)..." />
            <div class="bds-mb-images">
                <?php foreach ( $img_ids as $img_id ) :
                    $src = wp_get_attachment_image_src( $img_id, 'thumbnail' );
                    if ( ! $src ) continue; ?>
                    <div class="bds-mb-img-item">
                        <img src="<?php echo esc_url($src[0]); ?>" />
                        <button type="button" class="rem-mb-img">&times;</button>
                        <input type="hidden" name="bds_mat_bang[<?php echo $i; ?>][images][]" value="<?php echo esc_attr($img_id); ?>" />
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="bds-mb-actions">
                <button type="button" class="button bds-mb-add-img" data-index="<?php echo $i; ?>">+ Thêm hình</button>
                <button type="button" class="bds-mb-remove">✕ Xóa mục này</button>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <button type="button" id="bds-mb-add-item" class="button" style="margin-top:8px;">+ Thêm mặt bằng mới</button>

    <script>
    jQuery(function($){
        function openMediaFrame(container, idx) {
            var frame = wp.media({ title: 'Chọn hình mặt bằng', button: { text: 'Chọn' }, multiple: true });
            frame.on('select', function(){
                frame.state().get('selection').each(function(a){
                    var id  = a.toJSON().id;
                    var src = a.toJSON().sizes && a.toJSON().sizes.thumbnail
                              ? a.toJSON().sizes.thumbnail.url : a.toJSON().url;
                    container.find('.bds-mb-images').append(
                        '<div class="bds-mb-img-item">'+
                        '<img src="'+src+'" />'+
                        '<button type="button" class="rem-mb-img">&times;</button>'+
                        '<input type="hidden" name="bds_mat_bang['+idx+'][images][]" value="'+id+'" />'+
                        '</div>'
                    );
                });
            });
            frame.open();
        }

        $(document).on('click', '.bds-mb-add-img', function(){
            var item = $(this).closest('.bds-mb-item');
            var idx  = item.find('input[type="text"]').attr('name').match(/\[(\d+)\]/)[1];
            openMediaFrame(item, idx);
        });

        $(document).on('click', '.rem-mb-img', function(){
            $(this).closest('.bds-mb-img-item').remove();
        });

        $(document).on('click', '.bds-mb-remove', function(){
            $(this).closest('.bds-mb-item').remove();
            reIndexMB();
        });

        $('#bds-mb-add-item').on('click', function(){
            var idx = $('#bds-mb-wrap .bds-mb-item').length;
            var item = $('<div class="bds-mb-item">'+
                '<input type="text" name="bds_mat_bang['+idx+'][title]" placeholder="Tiêu đề mặt bằng..." />'+
                '<div class="bds-mb-images"></div>'+
                '<div class="bds-mb-actions">'+
                  '<button type="button" class="button bds-mb-add-img" data-index="'+idx+'">+ Thêm hình</button>'+
                  '<button type="button" class="bds-mb-remove">✕ Xóa mục này</button>'+
                '</div></div>');
            $('#bds-mb-wrap').append(item);
        });

        function reIndexMB(){
            $('#bds-mb-wrap .bds-mb-item').each(function(i){
                $(this).find('[name]').each(function(){
                    var n = $(this).attr('name').replace(/\[\d+\]/, '['+i+']');
                    $(this).attr('name', n);
                });
            });
        }
    });
    </script>
    <?php
}

/* -------------------------------------------------------
 * Save meta
 * ----------------------------------------------------- */
add_action( 'save_post_bat-dong-san', 'bds_save_meta' );
function bds_save_meta( $post_id ) {
    if ( ! isset($_POST['bds_nonce']) || ! wp_verify_nonce($_POST['bds_nonce'], 'bds_save_meta') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;

    // Thông tin cơ bản
    $fields = ['bds_dia_chi' => '_bds_dia_chi', 'bds_khoang_gia' => '_bds_khoang_gia', 'bds_loai_hinh' => '_bds_loai_hinh'];
    foreach ( $fields as $key => $meta_key ) {
        if ( isset($_POST[$key]) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field($_POST[$key]) );
        }
    }

    // Carousel
    if ( isset($_POST['bds_carousel_images']) && is_array($_POST['bds_carousel_images']) ) {
        $ids = array_map('absint', $_POST['bds_carousel_images']);
        update_post_meta( $post_id, '_bds_carousel_images', $ids );
    } else {
        delete_post_meta( $post_id, '_bds_carousel_images' );
    }

    // Tổng quan
    if ( isset($_POST['bds_tq']) && is_array($_POST['bds_tq']) ) {
        $rows = [];
        foreach ( $_POST['bds_tq'] as $row ) {
            $rows[] = [
                'label' => sanitize_text_field($row['label']),
                'value' => sanitize_text_field($row['value']),
            ];
        }
        update_post_meta( $post_id, '_bds_tong_quan', $rows );
    }

    // Mặt bằng
    if ( isset($_POST['bds_mat_bang']) && is_array($_POST['bds_mat_bang']) ) {
        $mbs = [];
        foreach ( $_POST['bds_mat_bang'] as $mb ) {
            $imgs = isset($mb['images']) && is_array($mb['images']) ? array_map('absint', $mb['images']) : [];
            $mbs[] = [
                'title'  => sanitize_text_field($mb['title']),
                'images' => $imgs,
            ];
        }
        update_post_meta( $post_id, '_bds_mat_bang', $mbs );
    }
}
