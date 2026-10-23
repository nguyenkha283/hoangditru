# hoangditru — code website, deploy GitHub → Hostinger

Repo chứa **code tự viết** của site (lấy từ `public_html` của hosting cPanel cũ), đã tách khỏi
theme Flatsome. Gói Hostinger **không có SSH**, nên deploy bằng **FTPS từ GitHub Actions** — file
lên đúng `wp-content/themes` và `wp-content/plugins`, WordPress thấy ở vị trí chuẩn, không cần
loader hay symlink.

```
push main ──▶ GitHub Actions
                 ├─ lint          php -l mọi file; lỗi cú pháp thì DỪNG, không deploy
                 ├─ ftp           FTPS → /public_html/wp-content/
                 └─ deploy-branch dựng nhánh "deploy" (chỉ dùng nếu gói có hPanel Git)
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
- **Không** gọi hàm nào của Flatsome. Chỉ còn 3 tên *hook* của Flatsome trong plugin — không có
  Flatsome thì chúng không bao giờ fire, vô hại.
- **Không** phụ thuộc JS của Flatsome. `bat-dong-san-plugin` tự nạp slick từ jsDelivr và khai báo
  `['jquery','slick-js']`; jQuery do WordPress core cấp.
- Nội dung **không** dùng UX Builder: cả DB chỉ có 1 `[ux_image]`, 5 `[ux_text]`, 5 `[section]`.
  `functions.php` có shim nhả nội dung bên trong các shortcode này nên không lộ shortcode thô.

## Không có gì trong repo (và vì sao)

| Bị loại | Lý do | Cách xử lý trên Hostinger |
|---|---|---|
| `themes/flatsome` | Theme trả phí, và **không còn cần** | không cài |
| 8 plugin bên thứ ba | `advanced-custom-fields` (free), `classic-editor`, `contact-form-7`, `litespeed-cache`, `really-simple-ssl`, `seo-by-rank-math`, `wp-mail-smtp`, `wpforms-lite` | cài từ WP admin |
| `plugins/backup` (**JetBackup**) | Plugin backup của hosting cPanel cũ | bỏ hẳn |
| `wp-admin/`, `wp-includes/`, `wp-*.php` | Nhân WordPress | Auto Installer của hPanel |
| `wp-config.php` | DB password + 8 salt key | Auto Installer tạo |
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

Theme `hoangditru` **chưa render thử lần nào**. Phân tích dựa trên đọc code, HTML render thật của
site cũ và file SQL — không phải trên một lần chạy thật. Sau khi deploy phải xem bằng mắt: trang
chủ, một bài viết, một category, một trang tĩnh, một landing page, một trang bất động sản.

---

## Cài trên Hostinger (không cần SSH)

### 1. Thêm domain phụ, rồi cài WordPress bằng Auto Installer

Domain mới là **addon domain**, nên phải thêm nó vào gói trước: hPanel → **Domains → Add domain**.
Trỏ DNS/nameserver về Hostinger và đợi domain báo active. Hostinger sẽ tạo thư mục
`domains/<domain-moi>/public_html`.

Sau đó: hPanel → **Website → Auto Installer** → WordPress, **chọn đúng domain phụ** ở mục domain
(đừng để mặc định domain chính), điền tên site, user/password admin, email. Auto Installer tự tạo
database và `wp-config.php`.

Xong thì vào WP admin dọn những thứ Hostinger cài kèm: **Plugin** → xoá plugin của Hostinger nếu
không dùng; **Giao diện** → để nguyên theme mặc định, bước 4 sẽ đổi.

### 2. Tạo tài khoản FTP

hPanel → **Files → FTP Accounts**. Trang này cho **FTP host**, **username**, và cho đổi password.
Ghi lại đường dẫn gốc của tài khoản (thường là `/home/uXXXXXXXXX` hoặc thẳng vào `public_html`).

### 3. Đặt secret trên GitHub

GitHub repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Giá trị |
|---|---|
| `FTP_HOST` | FTP host từ bước 2 (vd `ftp.domain-cua-ban.com` hoặc IP) |
| `FTP_USER` | username FTP |
| `FTP_PASSWORD` | password FTP |
| `FTP_PATH` | **bắt buộc với site này** — xem bên dưới |

Secret chỉ GitHub Actions đọc được, không hiện lại trong giao diện và không có trong code.

**`FTP_PATH` cho domain phụ.** Site này chạy trên **addon domain**, không phải domain chính của
gói, nên đường dẫn mặc định `/public_html/wp-content/` là **sai** — đó là domain chính. Dùng:

```
/domains/<domain-moi>/public_html/wp-content/
```

Thay `<domain-moi>` bằng tên domain thật, giữ dấu `/` ở cả đầu và cuối.

Nếu tài khoản FTP được tạo riêng cho thư mục của domain phụ (hPanel cho chọn thư mục gốc khi tạo
FTP account), thì gốc đăng nhập đã là `public_html` của domain đó và đường dẫn chỉ còn
`/wp-content/`.

Không chắc thì mở FileZilla bằng tài khoản FTP đó và xem đường dẫn thật tới `wp-content` — hoặc cứ
điền rồi chạy workflow: bước **Kiểm tra FTP_PATH trên server** sẽ liệt kê thư mục đích và **dừng
với lỗi đỏ** nếu không thấy `themes/` và `plugins/` trong đó, nên path sai không làm hỏng gì trên
server.

### 4. Chạy deploy

GitHub repo → tab **Actions** → workflow **Deploy** → *Run workflow*. Hoặc push một commit.

Job `ftp` sẽ đẩy `wp-content/themes/hoangditru` và 8 thư mục plugin lên server. Lần đầu mất vài
phút; những lần sau chỉ đẩy file đã đổi (action lưu `.ftp-deploy-sync-state.json` trên server).

⚠️ Job này **chỉ đẩy lên, không xoá** `uploads/` hay bất cứ thứ gì khác trên server, vì những file
đó không nằm trong `local-dir`.

Thiếu secret thì job `ftp` chỉ in cảnh báo rồi bỏ qua — workflow vẫn xanh, nên đừng tưởng là đã deploy.

### 5. Bật theme và plugin trong WP admin

1. **Giao diện → Giao diện** → kích hoạt **Hoangditru**
2. **Plugin** → kích hoạt cả 8 plugin tự viết
3. **Plugin → Cài mới** → cài và bật: Advanced Custom Fields, Classic Editor, Contact Form 7,
   LiteSpeed Cache, Really Simple SSL, Rank Math SEO, WP Mail SMTP, WPForms Lite
4. **Cài đặt → Đường dẫn tĩnh** → chọn lại cấu trúc rồi Lưu (để flush rewrite)

### 6. Gán menu — bước dễ quên nhất

Plugin `trang-chu` gọi `wp_nav_menu(['theme_location' => 'primary'])`. Nếu chưa có menu nào được
**gán vào vị trí "Menu chính"**, nav sẽ rơi vào fallback và chỉ còn một link "Trang chủ".

**Giao diện → Menu** → tạo menu → thêm trang → tick **Menu chính** → Lưu.

Hotline trên nav và footer lấy từ `cim_contact_info` → nhập trong trang quản trị của
`contact-info-manager`.

### 7. Kiểm tra

```bash
bash scripts/check-site.sh https://domain-moi.com
```

Script curl trang chủ, trang 404, trang tìm kiếm; kiểm `Fatal error`/`Warning`, shortcode thô còn
lộ, CSS của theme và 3 plugin có tải được, nav và footer có render. Gửi lại output nếu có dấu `[X]`.

---

## Quy trình từ đây về sau

```bash
git add -A && git commit -m "..." && git push
```

Actions lint rồi đẩy FTPS. Lỗi cú pháp PHP → job `lint` đỏ, job `ftp` **không chạy**, server giữ
nguyên bản cũ.

Thêm theme/plugin tự viết mới → thêm một dòng vào [`deploy/manifest.txt`](deploy/manifest.txt) rồi
push. (`manifest.txt` dùng cho bước lint và cho nhánh `deploy`; job FTP đẩy cả `wp-content/`.)

## Nếu gói hosting có hPanel Git

Job `deploy-branch` vẫn dựng nhánh `deploy` (chỉ có `themes/` và `plugins/` ở gốc). Nhưng hPanel Git
cài vào **một thư mục duy nhất** và **xoá sạch thư mục đó** mỗi lần deploy, nên nó phải là
`wp-content/app` — rồi cần symlink sang `wp-content/themes|plugins`, mà symlink thì **phải có SSH**.
Không SSH thì dùng FTP như trên; nhánh `deploy` để dành cho sau này nếu nâng gói.

## Nếu sau này có SSH

Khi đó dùng được `wp-cli`: `wp theme activate hoangditru`, `wp plugin activate --all`,
`wp rewrite flush`, và `mysqldump` để backup. Lưu ý Hostinger tắt `proc_open` nên
`wp db export|import|query` không chạy — phải gọi `mysql`/`mysqldump` trực tiếp với file `.cnf` tạm.

## Secret / token của site

Không đặt trong Git. Tạo file `wp-content/app-config.php` bằng File Manager rồi `require` từ
`wp-config.php` — `.gitignore` đã chặn `*-config.php`.

## Lỗi thường gặp

| Triệu chứng | Nguyên nhân | Xử lý |
|---|---|---|
| Actions xanh nhưng server không có file mới | Chưa đặt secret → job `ftp` bị bỏ qua | Xem log job `ftp`, đặt 3 secret |
| Job `ftp` đỏ: *Không thấy 'themes' trong …* | `FTP_PATH` trỏ sai chỗ, hoặc chưa cài WordPress ở domain phụ | Log in ra danh sách thư mục đích — đối chiếu rồi sửa `FTP_PATH`. Không file nào bị đẩy lên |
| Job `ftp` đỏ: *Không liệt kê được …* | Sai `FTP_HOST`/`FTP_USER`/`FTP_PASSWORD`, hoặc host chặn FTPS | Thử đăng nhập bằng FileZilla với đúng 3 giá trị đó |
| Code lên domain chính thay vì domain phụ | `FTP_PATH` để mặc định `/public_html/wp-content/` | Đổi thành `/domains/<domain-moi>/public_html/wp-content/` |
| Nav chỉ có "Trang chủ" | Chưa gán menu vào vị trí `primary` | Bước 6 |
| Footer trống | Chưa nhập dữ liệu `contact-info-manager`, hoặc `cim-footer` chưa bật | Kiểm tra trang Plugin |
| Deploy xong vẫn thấy trang cũ | LiteSpeed cache (`X-LiteSpeed-Cache: hit`) | WP admin → LiteSpeed Cache → Purge All; hoặc xoá khối `# BEGIN LSCACHE` trong `.htaccess` |
| Nội dung hiện chuỗi `[ux_text ...]` | Theme `hoangditru` chưa được kích hoạt | Bước 5 |
| Trang trắng sau khi bật theme | Lỗi PHP runtime | Bật `WP_DEBUG_LOG` trong `wp-config.php` qua File Manager, đọc `wp-content/debug.log` |
| Upload ảnh báo "Lỗi khi tải lên" | `async-upload.php` trả HTML *Error establishing a database connection* | Không phải lỗi code → ticket Hostinger |

## Backup khi không có SSH

- **File:** hPanel → **Files → Backups** (tạo và tải bản backup), hoặc File Manager → nén
  `wp-content` → tải về.
- **Database:** hPanel → **Databases → phpMyAdmin** → tab **Export** → Quick → SQL.

Làm backup trước mỗi lần đổi lớn (đổi theme, đổi plugin, sửa `.htaccess`).
