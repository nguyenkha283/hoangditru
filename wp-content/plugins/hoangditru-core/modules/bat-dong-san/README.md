# Plugin: Bất Động Sản – Khaiditru

## Cài đặt

1. Upload thư mục `bat-dong-san-plugin` vào `/wp-content/plugins/`
2. Kích hoạt plugin tại **Plugins > Installed Plugins**
3. Truy cập menu **Bất động sản** ở sidebar admin

---

## Tính năng

### Custom Post Type: `bat-dong-san`
- Slug URL: `/bat-dong-san/`
- Hỗ trợ: Tiêu đề, Nội dung (mô tả), Ảnh đại diện

### Admin Meta Boxes

| Meta Box | Trường |
|---|---|
| Thông tin cơ bản | Địa chỉ, Khoảng giá, Loại hình |
| Hình ảnh Carousel | Upload nhiều ảnh, kéo thả sắp xếp |
| Tổng quan dự án | 10 trường mặc định + thêm mới tùy ý |
| Mặt bằng | Accordion (tiêu đề + nhiều ảnh mỗi mục) |

---

## Frontend Layout

```
Breadcrumb  (bg #2d2d2d, width 50%)
  Trang chủ › Bất động sản › {Tiêu đề}

H3 intro:
  "Thông tin dự án {tiêu đề} ở {địa chỉ} cập nhật mới nhất tháng {M/YYYY} từ Khaiditru."

Text:
  "Cập nhật mới nhất ngày {dd/mm/yyyy}."

H1 {Tiêu đề trang}

Carousel (Slick Syncing – ảnh lớn + thumbnail nav)

  [Giá - màu #ff7200, căn trái]      [LOẠI HÌNH UPPERCASE, căn phải]

Section: Tổng quan
  ─────────────────
  Bảng 2 cột (label | value) không border

Section: Mô tả dự án
  ─────────────────
  WordPress the_content()

Section: Mặt bằng
  ─────────────────
  Accordion: [Tiêu đề ▼]
    [Ảnh mặt bằng grid]
```

---

## Yêu cầu

- WordPress 5.8+
- PHP 7.4+
- jQuery (đã có sẵn trong WordPress)
- Slick Carousel (load từ CDN tự động)

---

## Cấu trúc file

```
bat-dong-san-plugin/
├── bat-dong-san.php          # Main plugin file
├── includes/
│   ├── cpt.php               # Đăng ký Custom Post Type
│   ├── meta-boxes.php        # Admin meta boxes + lưu dữ liệu
│   ├── frontend.php          # Override template
│   └── shortcodes.php        # Shortcodes (dự phòng)
├── templates/
│   └── single-bat-dong-san.php  # Template frontend
└── assets/
    ├── css/style.css
    └── js/main.js
```
