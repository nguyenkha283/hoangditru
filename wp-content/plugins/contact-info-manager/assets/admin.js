/* Contact Info Manager – Admin JS */
(function ($) {
    'use strict';

    // ── Avatar uploader ──
    var avatarUploader;

    $('#cim-upload-avatar').on('click', function (e) {
        e.preventDefault();
        if (avatarUploader) { avatarUploader.open(); return; }

        avatarUploader = wp.media({
            title: 'Chọn ảnh đại diện',
            button: { text: 'Dùng ảnh này' },
            multiple: false,
            library: { type: 'image' }
        });

        avatarUploader.on('select', function () {
            var attachment = avatarUploader.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail
                      ? attachment.sizes.thumbnail.url : attachment.url;
            $('#cim_avatar').val(attachment.id);
            $('#cim-avatar-preview').attr('src', url).show();
            $('#cim-remove-avatar').show();
        });

        avatarUploader.open();
    });

    $('#cim-remove-avatar').on('click', function (e) {
        e.preventDefault();
        $('#cim_avatar').val('');
        $('#cim-avatar-preview').attr('src', '').hide();
        $(this).hide();
    });

    // ── Cover image uploader ──
    var coverUploader;

    $('#cim-upload-cover').on('click', function (e) {
        e.preventDefault();
        if (coverUploader) { coverUploader.open(); return; }

        coverUploader = wp.media({
            title: 'Chọn ảnh bìa',
            button: { text: 'Dùng ảnh này' },
            multiple: false,
            library: { type: 'image' }
        });

        coverUploader.on('select', function () {
            var attachment = coverUploader.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.medium
                      ? attachment.sizes.medium.url : attachment.url;
            $('#cim_cover_image').val(attachment.id);
            $('#cim-cover-placeholder').hide();
            $('#cim-cover-preview').attr('src', url).show();
            $('#cim-remove-cover').show();
        });

        coverUploader.open();
    });

    $('#cim-remove-cover').on('click', function (e) {
        e.preventDefault();
        $('#cim_cover_image').val('');
        $('#cim-cover-preview').attr('src', '').hide();
        $('#cim-cover-placeholder').show();
        $(this).hide();
    });

    // ── Footer background uploader ──
    var footerBgUploader;

    $('#cim-upload-footer-bg').on('click', function (e) {
        e.preventDefault();
        if (footerBgUploader) { footerBgUploader.open(); return; }

        footerBgUploader = wp.media({
            title: 'Chọn ảnh nền footer',
            button: { text: 'Dùng ảnh này' },
            multiple: false,
            library: { type: 'image' }
        });

        footerBgUploader.on('select', function () {
            var attachment = footerBgUploader.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.medium
                      ? attachment.sizes.medium.url : attachment.url;
            $('#cim_footer_bg').val(attachment.id);
            $('#cim-footer-bg-placeholder').hide();
            $('#cim-footer-bg-preview').attr('src', url).show();
            $('#cim-remove-footer-bg').show();
        });

        footerBgUploader.open();
    });

    $('#cim-remove-footer-bg').on('click', function (e) {
        e.preventDefault();
        $('#cim_footer_bg').val('');
        $('#cim-footer-bg-preview').attr('src', '').hide();
        $('#cim-footer-bg-placeholder').show();
        $(this).hide();
    });

    // ── Toggle API key visibility ──
    $('.cim-toggle-api-key').on('click', function () {
        var $input = $('#cim_mailchimp_api_key');
        var $icon  = $(this).find('.dashicons');
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // ── Social icon uploaders ──
    var socialUploaders = {};

    $(document).on('click', '.cim-upload-social-icon', function (e) {
        e.preventDefault();
        var key = $(this).data('key');

        if (socialUploaders[key]) {
            socialUploaders[key].open();
            return;
        }

        socialUploaders[key] = wp.media({
            title: 'Chọn icon cho ' + key,
            button: { text: 'Dùng ảnh này' },
            multiple: false,
            library: { type: 'image' }
        });

        socialUploaders[key].on('select', function () {
            var attachment = socialUploaders[key].state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail
                      ? attachment.sizes.thumbnail.url : attachment.url;

            $('#cim_' + key + '_icon').val(attachment.id);
            $('#cim-icon-preview-' + key).attr('src', url).show();
            $('#cim-icon-default-' + key).hide();
            $('.cim-remove-social-icon[data-key="' + key + '"]').show();
        });

        socialUploaders[key].open();
    });

    $(document).on('click', '.cim-remove-social-icon', function (e) {
        e.preventDefault();
        var key = $(this).data('key');
        $('#cim_' + key + '_icon').val('');
        $('#cim-icon-preview-' + key).attr('src', '').hide();
        $('#cim-icon-default-' + key).show();
        $(this).hide();
    });

    // ── Chi nhánh động ──
    var $branchList = $('#cim-branches-list');

    function cimBranchRow() {
        return '<div class="cim-branch-row">' +
            '<div class="cim-branch-fields">' +
                '<input type="text" name="cim_branch_title[]" value="" ' +
                    'placeholder="Tiêu đề (VD: Chi nhánh 1, Trụ sở chính...)" ' +
                    'class="regular-text cim-branch-title">' +
                '<input type="text" name="cim_branch_address[]" value="" ' +
                    'placeholder="Địa chỉ chi tiết..." ' +
                    'class="regular-text cim-branch-address">' +
            '</div>' +
            '<button type="button" class="button cim-remove-branch" title="Xoá chi nhánh này">' +
                '<span class="dashicons dashicons-trash"></span>' +
            '</button>' +
        '</div>';
    }

    function cimUpdateRemoveButtons() {
        var $rows = $branchList.find('.cim-branch-row');
        $rows.find('.cim-remove-branch').css('visibility', $rows.length <= 1 ? 'hidden' : 'visible');
    }

    $('#cim-add-branch').on('click', function () {
        $branchList.append( cimBranchRow() );
        cimUpdateRemoveButtons();
        $branchList.find('.cim-branch-row:last .cim-branch-title').focus();
    });

    $branchList.on('click', '.cim-remove-branch', function () {
        $(this).closest('.cim-branch-row').remove();
        cimUpdateRemoveButtons();
    });

}(jQuery));
