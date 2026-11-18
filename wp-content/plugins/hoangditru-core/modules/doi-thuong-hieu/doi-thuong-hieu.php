<?php
/**
 * Module: Đổi tên thương hiệu
 *
 * Nội dung site được chép (có cho phép) từ site khác nên còn tên "Khải Di Trú"
 * và số 0906.225.610 của site gốc. Module này cho administrator một trang
 * Công cụ → Đổi tên thương hiệu: xem trước từng chỗ, rồi thay một lần.
 *
 *   includes/thay-the.php     luật thay — thuần PHP, có test
 *   includes/admin.php        quét/ghi database + trang admin
 *   includes/chuyen-huong.php 301 từ slug cũ, cho Trang, BĐS và term
 *
 * Thay xong có thể gỡ trang admin, nhưng phải GIỮ chuyen-huong.php (và
 * thay-the.php mà nó dùng): link cũ ngoài internet vẫn trỏ về slug cũ.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/thay-the.php';
require_once __DIR__ . '/includes/chuyen-huong.php';

if ( is_admin() ) {
	require_once __DIR__ . '/includes/admin.php';
}
