# hoangditru — code website, deploy GitHub → Hostinger

Repo chứa **code tự viết** của site (lấy từ `public_html` của hosting cPanel cũ), đã tách khỏi
theme Flatsome. Push lên `main` → GitHub Actions kiểm cú pháp PHP → cập nhật nhánh `deploy` →
webhook → Hostinger `git pull` vào `wp-content/app`.

```
main ──push──▶ GitHub Actions (php -l) ──▶ nhánh "deploy"
                                               │ webhook
Hostinger: hPanel → Git kéo nhánh deploy vào  public_html/wp-content/app/
wp-content/themes/hoangditru   ─symlink─▶  app/themes/hoangditru
wp-content/plugins/<8 plugin>  ─symlink─▶  app/plugins/<...>
```

**Không cần theme Flatsome nữa.** Site chạy bằng theme `hoangditru` độc lập.

---

## Có gì trong repo

**Theme `hoangditru`** (v2.0.0) — theme độc lập, trước đây là `flatsome-child`

| File | |
|---|---|
| `style.css` | font UTMAptima + Phudu, typography, CSS nền, khung trang `.hdt-*` |
| `functions.php` | khai báo tính năng theme, `register_nav_menus`, tính năng ghim bài viết, shim shortcode cũ |
| `header.php` | chỉ doctype + `wp_head()` + `wp_body_open()` — **không** render nav |
| `footer.php` | chỉ `wp_footer()` |
| `index.php`, `page.php`, `archive.php`, `search.php`, `404.php`, `searchform.php` | template WordPress chuẩn, mới viết |
| `category.php`, `single.php` | giữ nguyên từ bản cũ, không sửa |
| `template-parts/content-card.php` | thẻ bài dùng chung |

**Plugin tự viết** (8 cái, không sửa gì)

| Plugin | Version | Vai trò đáng chú ý |
|---|---|---|
| `trang-chu` | 1.1.0 | **dựng thanh nav của toàn site** + template trang chủ |
| `cim-footer` | 1.0.0 | **dựng footer của toàn site** |
| `bat-dong-san-plugin` | 1.1.0 | CPT bất động sản, slider + lightbox riêng (slick từ CDN) |
| `citizenship-investment-plugin` | 1.0.0 | trang đầu tư quốc tịch |
| `landing-page-plugin` | 1.2.0 | landing page builder |
| `contact-info-manager` | 1.1.0 | thông tin liên hệ, nguồn hotline cho nav + footer |
| `smart-breadcrumb` | 1.0.0 | breadcrumb |
| `snera-youtube-embed` | 1.0.0 | nút nhúng YouTube cho TinyMCE |

## Vì sao tách được khỏi Flatsome

Đo trên code và DB thật:

- `style.css` của bản cũ có `.header-main{display:none}` → **header của Flatsome vốn đã bị ẩn hoàn
  toàn**. Nav hiển thị do `trang-chu` inject vào `wp_body_open`.
- Footer là `#cim-footer` của plugin `cim-footer`; plugin này **đã có sẵn fallback** sang hook
  `wp_footer` khi theme không phải Flatsome.
- Markup tự viết **không** dùng grid của Flatsome. Toàn bộ class đều có tiền tố riêng
  (`tc-`, `lpb-`, `bds-`, `cimf-`, `ci-`, `tcs-`) và có CSS riêng đi kèm.
- **Không** gọi hàm nào của Flatsome (`flatsome_option`…). Chỉ còn 3 tên *hook* của Flatsome trong
  plugin — không có Flatsome thì chúng không bao giờ fire, vô hại.
- **Không** phụ thuộc JS của Flatsome. `bat-dong-san-plugin` tự nạp slick từ jsDelivr và khai báo
  `['jquery','slick-js']`; jQuery do WordPress core cấp.
- Nội dung **không** dùng UX Builder: cả DB chỉ có 1 `[ux_image]`, 5 `[ux_text]`, 5 `[section]`.
  `functions.php` có shim nhả nội dung bên trong các shortcode này nên không lộ shortcode thô.

Phần theme phải tự lo sau khi tách: khung HTML (`header.php`/`footer.php`), 5 template WordPress
chuẩn, `register_nav_menus('primary')`, `add_theme_support('post-thumbnails')` (plugin gọi
`has_post_thumbnail()` ở 4 chỗ), và một ít CSS nền.

## Không có gì trong repo (và vì sao)

| Bị loại | Lý do | Cách xử lý trên Hostinger |
|---|---|---|
| `themes/flatsome` | Theme trả phí, và **không còn cần** | không cài |
| 8 plugin bên thứ ba | `advanced-custom-fields` (free), `classic-editor`, `contact-form-7`, `litespeed-cache`, `really-simple-ssl`, `seo-by-rank-math`, `wp-mail-smtp`, `wpforms-lite` | `wp plugin install` |
| `plugins/backup` (**JetBackup**) | Plugin backup của hosting cPanel cũ | bỏ hẳn |
| `wp-admin/`, `wp-includes/`, `wp-*.php` | Nhân WordPress 7.1.3 | `wp core download` |
| `wp-config.php` | DB password + 8 salt key | `wp config create` |
| `wp-content/uploads/` | Ảnh/file — không cần | có `uploads.zip` nếu sau này cần |
| `debug.log` (2 MB), `error_log` | Log | bỏ |
| 5 file `.htaccess.bak*`, `.htaccess_back_*` | Rác backup | bỏ |
| 2 file `functions.php.bak-og-*` | Bản nháp cũ | bỏ |
| `upgrade/`, `upgrade-temp-backup/`, `litespeed/`, `maintenance/` | Thư mục tạm | bỏ |
| `{assets` trong `landing-page-plugin` | Thư mục rỗng do `mkdir` không nở brace | bỏ |

## Kết quả kiểm tra code

- **Không secret hardcode.** API key Mailchimp của `contact-info-manager` lưu trong DB, mặc định rỗng.
- **Không mã nghi vấn**: không `eval`, `base64_decode`, `gzinflate`, `shell_exec`, `system`.
- **Không đường dẫn tuyệt đối** `/home/<user>/...`.
- **Không plugin nào tạo bảng riêng** (không `CREATE TABLE`/`dbDelta`) → không cần import structure
  bảng custom. File `.sql` chỉ dùng nếu muốn lấy lại nội dung.
- Table prefix của site cũ: `tuC1OPNu_` (chỉ cần nếu sau này import nội dung từ `.sql`).
- Toàn bộ file PHP qua `php -l`, cả trên máy và trong GitHub Actions.

## Chưa được kiểm tra

Theme `hoangditru` **chưa render thử lần nào** — máy này không có WordPress chạy được (Docker
Desktop đang tắt) và chưa có SSH Hostinger. Cần xem bằng mắt sau khi deploy:

- trang chủ, 1 bài viết, 1 category, 1 trang tĩnh, 1 landing page, 1 trang bất động sản
- nav và footer có lên đúng không
- không có `Fatal error` / `Warning` trong HTML

---

## Cài trên Hostinger

### 1. Chuẩn bị site

Database tạo trước ở hPanel → **Databases → Management** (lưu password ngay, không xem lại được).

```bash
# SSH: hPanel → site → Advanced → SSH Access (port thường 65002)
cd ~/domains/<domain-moi>/public_html
wp core download --locale=vi
wp config create --dbname=<db> --dbuser=<user> --dbpass='<pass>' --dbhost=localhost
wp core install --url=https://<domain-moi> --title="<Tên site>" \
                --admin_user=<admin> --admin_email=<email> --admin_password='<pass>'
```

Đừng dùng **Auto Installer** của hPanel: nó cài kèm plugin riêng của Hostinger và theme mặc định.

### 2. Plugin bên thứ ba

```bash
wp plugin install advanced-custom-fields classic-editor contact-form-7 \
                  litespeed-cache really-simple-ssl seo-by-rank-math \
                  wp-mail-smtp wpforms-lite
```

### 3. Nối hPanel Git

hPanel → site → **Advanced → GIT** → *Create a new repository*

| Trường | Giá trị |
|---|---|
| Repository address | `https://github.com/nguyenkha283/hoangditru.git` |
| Branch | `deploy` |
| Install path | `wp-content/app` |

⚠️ **Install path phải đúng `wp-content/app`.** Để trống (= `public_html`) hoặc để `wp-content`
sẽ bị xoá sạch dữ liệu khi deploy.

Repo công khai nên không cần deploy key. Sau khi tạo xong, mục **Auto-Deployment** → copy webhook
URL → GitHub repo → **Settings → Webhooks → Add webhook**: Payload URL = webhook đó, Content type
`application/json`, trigger *Just the push event*.

### 4. Symlink + bật theme/plugin

```bash
cd ~/domains/<domain-moi>/public_html
curl -sO https://raw.githubusercontent.com/nguyenkha283/hoangditru/main/scripts/hostinger-link.sh
bash hostinger-link.sh ~/domains/<domain-moi>/public_html

wp theme activate hoangditru
wp plugin activate --all
wp rewrite flush
```

Script bỏ qua (không ghi đè) bất kỳ thư mục **thật** đã tồn tại trong `themes/` hoặc `plugins/`.

### 5. Gán menu — bước dễ quên nhất

Plugin `trang-chu` gọi `wp_nav_menu(['theme_location' => 'primary'])`. Nếu chưa có menu nào được
**gán vào vị trí "Menu chính"**, nav sẽ rơi vào fallback và chỉ còn một link "Trang chủ".

WordPress admin → **Giao diện → Menu** → tạo menu → tick **Menu chính** → Lưu. Hoặc:

```bash
wp menu create "Menu chính"
wp menu location assign "menu-chinh" primary
wp menu item add-post menu-chinh <ID-trang> --title="Trang chủ"
```

Hotline trên nav và footer lấy từ `cim_contact_info` → nhập trong trang quản trị của
`contact-info-manager`.

### 6. Kiểm tra

```bash
curl -sI https://<domain-moi> | head -1
curl -s https://<domain-moi> | grep -ciE "fatal error|warning:"              # phải ra 0
curl -s https://<domain-moi> | grep -c "tc-nav-bar\|tc-nav__list"            # nav có lên
curl -s https://<domain-moi> | grep -c 'id="cim-footer"'                     # footer có lên
curl -sI https://<domain-moi>/wp-content/themes/hoangditru/style.css | head -1
```

Nếu CSS/JS của theme hoặc plugin bị **403**, nghĩa là Apache/LiteSpeed không theo symlink. Thêm vào
`wp-content/.htaccess`:

```apache
Options +FollowSymLinks
```

---

## Quy trình từ đây về sau

```bash
git add -A && git commit -m "..." && git push
```

Actions build → nhánh `deploy` → webhook → Hostinger pull. Khoảng 30–60 giây.

Thêm theme/plugin tự viết mới → thêm một dòng vào [`deploy/manifest.txt`](deploy/manifest.txt),
rồi chạy `hostinger-link.sh` lại một lần.

Lỗi cú pháp PHP → Actions đỏ, nhánh `deploy` **không** được cập nhật, server giữ nguyên bản cũ.

Đừng sửa tay file trong `wp-content/app` trên server — lần deploy sau ghi đè.

## Secret / token

Không đặt trong Git. Tạo tay qua SSH ở `wp-content/app-config.php` rồi `require` từ
`wp-config.php` — `.gitignore` đã chặn `*-config.php`.

## Lỗi thường gặp trên Hostinger

| Triệu chứng | Nguyên nhân | Xử lý |
|---|---|---|
| Nav chỉ có "Trang chủ" | Chưa gán menu vào vị trí `primary` | Bước 5 |
| Footer trống | Chưa nhập dữ liệu `contact-info-manager`, hoặc plugin `cim-footer` chưa bật | `wp plugin list`, nhập thông tin liên hệ |
| Deploy xong vẫn thấy trang cũ | LiteSpeed cache (`X-LiteSpeed-Cache: hit`), khối `# BEGIN LSCACHE` trong `.htaccess` | Backup `.htaccess` rồi xoá khối LSCACHE/NON_LSCACHE; thử bằng `curl -A "Mozilla/5.0 (iPhone)"` không cookie |
| CSS/JS 403 | Apache không theo symlink | `Options +FollowSymLinks` trong `wp-content/.htaccess` |
| Mất `uploads/` sau deploy | Install path trỏ vào thư mục có dữ liệu | Đổi về `wp-content/app`, khôi phục từ backup |
| `wp db import` báo `proc_open disabled` | Hostinger tắt `proc_open` | Dùng `mysql --defaults-extra-file=` (xem mục Backup) |
| Nội dung hiện chuỗi `[ux_text ...]` | Shim shortcode chưa chạy → theme chưa active | `wp theme list` |
| Upload ảnh báo "Lỗi khi tải lên" | `async-upload.php` trả HTML *Error establishing a database connection* | Không phải lỗi code → ticket Hostinger |

## Backup trước mọi thao tác ghi đè

```bash
D=~/backup/$(date +%Y%m%d-%H%M); mkdir -p "$D"; chmod 700 ~/backup "$D"
cd ~/domains/<domain>/public_html
umask 077
printf "[client]\nuser=%s\npassword=%s\nhost=%s\n" "$(wp config get DB_USER)" "$(wp config get DB_PASSWORD)" "$(wp config get DB_HOST)" > /tmp/my.cnf
mysqldump --defaults-extra-file=/tmp/my.cnf --single-transaction --no-tablespaces "$(wp config get DB_NAME)" > "$D/db.sql"
rm -f /tmp/my.cnf
tar -czf "$D/wp-content.tar.gz" --exclude=wp-content/app wp-content
```
