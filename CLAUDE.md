# Quy ước làm việc với repo này

## Nhánh mới: luôn tự mở PR và theo dõi CI/CD

Khi làm việc trên một nhánh mới (`claude/*` hoặc nhánh tính năng nào khác), sau khi push:

1. **Tự mở pull request** về `main`, không cần đợi được yêu cầu.
2. **Theo dõi CI/CD** của PR đó cho tới khi xanh: `subscribe_pr_activity`, rồi tự sửa và push lại
   khi workflow `deploy.yml` fail hoặc có review comment.
3. Báo lại khi CI xanh, hoặc khi failure **không phải** do thay đổi trong PR (nêu rõ check nào và
   vì sao).

Workflow `.github/workflows/deploy.yml` chạy `php -l`, 5 test script trong `scripts/`, rồi đẩy
`wp-content` lên Hostinger qua FTPS. **Job deploy chỉ chạy trên `main`** — PR chỉ chạy phần kiểm
tra, nên CI xanh trên PR không có nghĩa là site đã cập nhật.

## Trước khi push

- `find wp-content -name '*.php' -print0 | xargs -0 -n1 php -l`
- `php scripts/plugin-load.test.php`
- `php scripts/phan-quyen.test.php`
- `php scripts/menu-tu-dong.test.php`
- `php scripts/loader-url.test.php`
- `php scripts/template-scope.test.php`
- `bash scripts/ftp-resolve.test.sh`
- `php scripts/webp.test.php`

## Đụng vào đâu thì đọc README trước

README là tài liệu chính, không phải phụ lục. Hai mục bắt buộc đọc trước khi sửa:

- **Định danh KHÔNG được đổi** — post type, taxonomy, option, page template, shortcode, post meta,
  capability. Tất cả đang nằm trong database của site thật; đổi là mất dữ liệu đang có.
- **Quyền của role Biên tập viên** — sửa `HDTQ_CAPS` thì phải bump `HDTQ_VERSION`, nếu không quyền
  không được ghi lại.

Plugin WebP có **hai bản** (`wp-content/plugins/hoangditru-webp` và `plugins-khac/webp-srcset-lightbox`)
dùng chung các file liệt kê trong `scripts/webp-shared.txt`. Sửa ở bản hoangditru rồi chạy
`bash scripts/webp-sync.sh`; test sẽ đỏ nếu hai bản lệch nhau. Template mới in ảnh thư viện Media thì
dùng `hdtc_img()` (plugin lõi) hoặc `wp_get_attachment_image()`, đừng tự viết `<img src="…">` — xem
mục *Ảnh: WebP, srcset, lightbox* trong README.

Sửa CSS/JS của theme thì bump `HDT_VERSION` trong `functions.php` **và** dòng `Version:` trong
`style.css` cho khớp, nếu không trình duyệt và LiteSpeed vẫn trả bản cũ.
