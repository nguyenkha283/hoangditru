=== Contact Info Manager ===
Contributors: yourname
Tags: contact, shortcode, social media, profile
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later

Quản lý thông tin liên hệ cá nhân/tổ chức và hiển thị bằng shortcode linh hoạt.

== Cài đặt ==

1. Upload thư mục `contact-info-manager` vào `/wp-content/plugins/`
2. Kích hoạt plugin trong menu **Plugins**
3. Vào menu **Thông Tin Liên Hệ** trong sidebar để nhập thông tin
4. Dùng shortcode ở bất kỳ trang/bài viết/widget nào

== Danh sách Shortcode ==

=== Từng trường riêng lẻ ===

[cim_avatar]
  - size="96"         → kích thước pixel (mặc định: 96)
  - class=""          → class CSS tuỳ chỉnh
  - alt="Avatar"      → alt text

[cim_full_name]
  - tag="span"        → thẻ HTML (span, h1, h2, h3, h4, p, strong)
  - class=""

[cim_bio]
  - tag="p"           → thẻ HTML (p, div, span)
  - class=""

[cim_email]
  - link="no"         → "yes" để xuất thẻ <a href="mailto:...">
  - label=""          → nhãn tuỳ chỉnh khi link="yes"
  - class=""

[cim_phone]
  - link="no"         → "yes" để xuất thẻ <a href="tel:...">
  - label=""
  - class=""

[cim_youtube]
  - label=""          → nếu có label → hiển thị nút bấm; nếu không → hiển thị URL
  - class=""

[cim_facebook]  (tương tự cim_youtube)

[cim_tiktok]    (tương tự cim_youtube)

=== Card tổng hợp ===

[cim_card]
  - avatar_size="96"  → kích thước avatar trong card
  - class=""          → class CSS bổ sung cho card

== Ví dụ sử dụng ==

Hiển thị avatar tròn 120px:
  [cim_avatar size="120"]

Hiển thị tên dạng tiêu đề:
  [cim_full_name tag="h2"]

Email dạng link:
  [cim_email link="yes" label="Gửi email cho tôi"]

SĐT dạng link gọi điện:
  [cim_phone link="yes"]

Nút YouTube:
  [cim_youtube label="Xem kênh YouTube"]

Card đầy đủ với avatar 80px:
  [cim_card avatar_size="80"]

== Changelog ==

= 1.0.0 =
* Phiên bản đầu tiên
