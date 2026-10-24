<?php
/**
 * Khung mở đầu trang.
 *
 * Theme không render header hiển thị: thanh nav do plugin trang-chu inject vào
 * hook wp_body_open. Riêng trang chủ dùng template của plugin và tự dựng nav.
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
