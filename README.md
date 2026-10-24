# hoangditru — code website, deploy GitHub → Hostinger

Repo chứa **code tự viết** của site (lấy từ `public_html` của hosting cPanel cũ), đã tách khỏi
theme Flatsome. Gói Hostinger **không có SSH**, nên deploy bằng **FTPS từ GitHub Actions** — file
lên đúng `wp-content/themes` và `wp-content/plugins`, WordPress thấy ở vị trí chuẩn, không cần
loader hay symlink.

```
push main ──▶ GitHub Actions
                 ├─ lint          php -l mọi file; lỗi cú pháp thì DỪNG, không deploy
                 ├─ ftp           FTPS → /public_html/wp-content/
                 └─ ftp           FTPS → /public_html/wp-content/
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

### 1. Cài WordPress bằng Auto Installer

Domain mới là **domain chính của gói**, thư mục là `public_html`. Trỏ nameserver của domain về
Hostinger và đợi báo active trước.

hPanel → **Website → Auto Installer** → WordPress. Điền domain, tên site, user/password admin,
email. Auto Installer tự tạo database và `wp-config.php`.

Xong thì vào WP admin dọn những thứ Hostinger cài kèm: **Plugin** → xoá plugin của Hostinger nếu
không dùng; **Giao diện** → để nguyên theme mặc định, bước 4 sẽ đổi.

### 2. Tạo tài khoản FTP

hPanel → **Files → FTP Accounts**. Trang này cho **FTP host**, **username**, và cho đổi password.
Ghi lại đường dẫn gốc của tài khoản (thường là `/home/uXXXXXXXXX` hoặc thẳng vào `public_html`).

### 3. Đặt secret trên GitHub

GitHub repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Giá trị |
|---|---|
| `FTP_HOST` | IP máy chủ FTP, đúng như hPanel hiện (xem ghi chú về TLS bên dưới) |
| `FTP_USER` | username FTP |
| `FTP_PASSWORD` | password FTP |
| `FTP_PATH` | *(tuỳ chọn)* — site này là domain chính nên để trống là đúng |

Secret chỉ GitHub Actions đọc được, không hiện lại trong giao diện và không có trong code.

**`FTP_PATH` — nên đặt, dù không bắt buộc.** Với site này là:

```
/public_html/wp-content/
```

Đặt rồi thì mỗi lần chạy chỉ tốn **1 phiên đăng nhập FTP**; để trống thì script phải dò và tốn 2–3
phiên. Nghe nhỏ nhưng đó chính là nguyên nhân deploy từng hỏng — xem mục *Độ tin cậy* bên dưới.

Khi `FTP_PATH` trống, bước *Dò wp-content trên server* thử `/public_html/wp-content/` rồi
`/wp-content/`, **dừng ngay ở cái đầu tiên** có đủ `themes/`, `plugins/` và `wp-includes/` bên
cạnh; chỉ khi cả hai trượt mới quét `/domains/`. Nhiều site cùng khớp thì dừng hẳn và bắt đặt
`FTP_PATH`, để không deploy nhầm site.

Logic ở [`scripts/ftp-resolve.sh`](scripts/ftp-resolve.sh), test ở
[`scripts/ftp-resolve.test.sh`](scripts/ftp-resolve.test.sh) — chạy được ở máy nhờ `FTP_FAKE_TREE`
(đọc thư mục thật thay cho curl), 11 trường hợp, trong đó có 3 ca **ràng buộc số phiên FTP**.

### Về TLS của FTPS

Kết nối được mã hoá, nhưng **không chứng thực được danh tính máy chủ**. Đã kiểm 2026-10-23 trên
`145.223.108.128:21`:

- chứng chỉ là `CN=*.hstgr.io`, SAN chỉ có `*.hstgr.io` và `hstgr.io`
- hPanel chỉ công bố IP, không công bố hostname nào
- `ftp.hoangditru.com` và `hoangditru.com` trỏ đúng IP nhưng vẫn mismatch (mã 62)
- `srv1760.hstgr.io` trỏ sang `153.92.15.39`, không phải máy chủ FTP
- banner FTP là `220 FTP Server ready.`, không lộ tên máy

Nên không có tên nào để TLS xác thực đúng — đổi `FTP_HOST` sang tên miền **không** giải quyết được,
chỉ làm config trông đẹp hơn. Script tự nhận ra và chạy tiếp ở chế độ bỏ qua kiểm tra chứng chỉ.

Cách giảm thiệt hại nếu muốn chặt hơn: tạo **một tài khoản FTP riêng** trong hPanel → Files →
FTP Accounts, giới hạn vào đúng thư mục của site, và dùng tài khoản đó cho
`FTP_USER`/`FTP_PASSWORD`. Mật khẩu đi qua kênh không chứng thực được thì ít nhất nó không phải
chìa khoá của cả gói.

**Dự án này đã cân nhắc và chọn dùng tài khoản FTP chính** (`u133340861`) — đây là quyết định có ý
thức, không phải thiếu sót. Nếu sau này site có dữ liệu thật hoặc gói hosting chứa thêm website
khác thì nên xem lại.

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
push. (`manifest.txt` dùng cho bước lint; job `ftp` đẩy cả `wp-content/`.)

## Độ tin cậy của deploy

Máy chủ FTP của Hostinger **chặn tạm thời khi có quá nhiều phiên đăng nhập trong thời gian ngắn**.
Ngày 2026-10-23 điều này đã xảy ra, và mốc thời gian nói rõ hơn mọi log:

```
08:47  success
09:13  success
09:17  success   ← 4 phút sau lần trước
09:22  FAILURE   ← 5 phút sau
09:30  FAILURE   ← 8 phút sau
10:16  success   ← sau 46 phút nghỉ
```

Hai lần đỏ ở **hai bước FTP khác nhau**, lint xanh cả hai lần, rồi tự khỏi mà không sửa gì. Bản đầu
của `ftp-resolve.sh` mở một phiên FTP mới cho **mỗi** lần liệt kê thư mục — 3 đến 9 phiên mỗi lần
chạy, cộng phiên của `FTP-Deploy-Action`. Bốn lần deploy trong 17 phút thành 16–36 lần đăng nhập.

Ba thay đổi để không lặp lại:

| | |
|---|---|
| **Giảm số phiên** | 1 phiên khi có `FTP_PATH`, 2–3 khi phải dò. Dừng ở kết quả đầu tiên thay vì thử hết; bỏ phiên "thăm dò TLS" riêng, gộp vào lần liệt kê đầu |
| **Tự thử lại** | `ftp_list` thử lại tối đa 3 lần với giãn cách 15s → 45s khi gặp lỗi kết nối tạm thời. Bước đẩy file hỏng thì nghỉ 90 giây rồi đẩy lần hai |
| **Không nói dối** | `continue-on-error` ở lần đẩy đầu khiến job vẫn xanh kể cả khi cả hai lần hỏng, nên có bước *Kết luận* tự đánh đỏ |

Vẫn nên **tránh push liên tiếp nhiều commit trong vài phút**. Gom thay đổi lại rồi push một lần.

## ⚠️ KHÔNG dùng hPanel → Git cho site này

**hPanel Git xoá sạch thư mục đích trước mỗi lần kéo code.** Trỏ nó vào `wp-content` là mất theme
mặc định, mất plugin của Hostinger, mất `uploads/` — chỉ còn đúng nội dung của nhánh được cài.

Chuyện này đã xảy ra với site `hoangditru.com` ngày 2026-10-23: `wp-content` chỉ còn
`themes/hoangditru` và `plugins/*` của repo, `themes/twentytwentyfive` và `plugins/hostinger` biến
mất, và site trả 403.

Vì vậy:

- Nhánh `deploy` **đã bị xoá**, và job dựng nhánh đó đã bỏ khỏi workflow.
- Workflow chỉ còn quyền `contents: read`, không thể đẩy nhánh nào nữa.
- Nếu trong hPanel → **Advanced → GIT** còn repository nào được cấu hình, **xoá nó đi**.
- Deploy duy nhất được dùng: job `ftp` trong workflow này.

Kiến trúc `wp-content/app` + symlink (để dùng được hPanel Git an toàn) cần SSH, mà gói này không có.

## Nếu sau này có SSH

Khi đó dùng được `wp-cli`: `wp theme activate hoangditru`, `wp plugin activate --all`,
`wp rewrite flush`, và `mysqldump` để backup. Lưu ý Hostinger tắt `proc_open` nên
`wp db export|import|query` không chạy — phải gọi `mysql`/`mysqldump` trực tiếp với file `.cnf` tạm.

## Import dữ liệu và uploads của site cũ

Làm khi muốn site mới có sẵn nội dung thật thay vì dựng dữ liệu mẫu bằng tay. Không cần SSH.

**Backup trước.** hPanel → Files → Backups, và phpMyAdmin → Export database hiện tại.

### 1. Đổi table prefix

File Manager → `public_html/wp-config.php` → sửa thành:

```php
$table_prefix = 'tuC1OPNu_';
```

Dump dùng prefix đó. Giữ nguyên prefix của dump đơn giản hơn nhiều so với đổi tên 34 bảng.

### 2. Import dump

hPanel → **Databases → phpMyAdmin** → chọn database của hoangditru.com → tab **Import** → chọn
file `.sql` của site cũ → Thực hiện.

- File 6,3 MB, nằm dưới giới hạn upload của phpMyAdmin.
- Dump có 34 `CREATE TABLE`, 135 `INSERT`, 64 `ALTER TABLE`, **không có** `DROP TABLE` và không
  dùng `IF NOT EXISTS`. Nghĩa là nếu bảng `tuC1OPNu_*` đã tồn tại từ một lần thử trước thì phải
  xoá chúng đi rồi mới import lại, không thì báo lỗi trùng bảng.
- Bảng do Auto Installer tạo (prefix khác) vẫn nằm đó, vô hại. Xoá sau cùng.
- Collation trong dump là `utf8mb4_unicode_520_ci`, MariaDB của Hostinger nhận được.

### 3. Chạy `scripts/import-fixes.sql`

phpMyAdmin → tab **SQL** → dán toàn bộ nội dung [`scripts/import-fixes.sql`](scripts/import-fixes.sql)
→ Thực hiện. Nó đưa `siteurl`/`home` về domain mới (phải làm trước, nếu không mở `/wp-admin` sẽ bị
đá sang domain cũ), đặt theme về `hoangditru`, đổi tên `theme_mods_flatsome-child` thành
`theme_mods_hoangditru` để giữ **vị trí menu** đã gán, và dọn transient.

### 4. Đăng nhập lại

Dump mang theo bảng users của site cũ, nên tài khoản admin do Auto Installer tạo **biến mất**.
Từ giờ đăng nhập bằng `admin_zp3m9y06`. Không nhớ mật khẩu thì bỏ comment khối cuối trong
`import-fixes.sql`, đổi mật khẩu rồi chạy.

### 5. Thay URL còn lại — bằng plugin, không bằng SQL

Plugin → Cài mới → **Better Search Replace** → Tools → Better Search Replace:

| | |
|---|---|
| Search for | `khaiditru.com` |
| Replace with | `hoangditru.com` |
| Select tables | chọn **tất cả** |

Chạy **Run as dry run** trước để xem số lượng, rồi bỏ tick chạy thật. Trong dump có khoảng **2193**
chỗ chứa `khaiditru.com`.

⚠️ **Đừng dùng `REPLACE()` trong SQL.** Rất nhiều giá trị là dữ liệu PHP serialize dạng
`s:25:"https://khaiditru.com/..."`. Đổi chuỗi mà không sửa số độ dài `s:N:` là hỏng toàn bộ widget,
cài đặt plugin và post meta. Better Search Replace giải serialize rồi đóng gói lại nên an toàn.

### 6. Upload uploads

`wp-content/uploads` của site cũ nặng **393 MB / 3635 file**. Dùng FileZilla với tài khoản FTP,
upload thư mục `uploads/2026/` vào `public_html/wp-content/uploads/`.

| Tháng | Dung lượng | | Tháng | Dung lượng |
|---|---|---|---|---|
| 04 | 151 MB | | 09 | 19 MB |
| 05 | 130 MB | | 07 | 13 MB |
| 10 | 73 MB | | 06 | 5 MB |
| | | | 08 | 2 MB |

Bỏ `jetbackup-d4dda5424e05` (backup của host cũ, không liên quan). `rank-math`, `rsssl`,
`wpforms`, `wpcf7_uploads` nhỏ, tuỳ bạn.

⚠️ File `uploads.zip` trong Downloads chỉ có 621 mục, **không đủ** — dùng thư mục `uploads` trong
bản `public_html` đã tải về.

### 7. Dọn và kiểm tra

- Cài lại plugin bên thứ ba. Dump mang theo `active_plugins` cũ; plugin nào không có trên server
  thì WordPress tự tắt và báo trong trang Plugin.
- Cài đặt → Đường dẫn tĩnh → Lưu (flush rewrite; dump dùng `/%postname%/`).
- `bash scripts/check-site.sh https://hoangditru.com`
- Xoá các bảng thừa mang prefix của Auto Installer trong phpMyAdmin.

### Lưu ý về SEO

Sau bước này hoangditru.com và khaiditru.com có **nội dung trùng nhau hoàn toàn**. Nếu site mới
chưa định cho công khai, bật Cài đặt → Đọc → *Ngăn các công cụ tìm kiếm đánh chỉ mục trang này*
cho tới khi chốt phương án chuyển domain.

## Màu và font của site

| | |
|---|---|
| Màu nhấn | `#1e3a73` |
| Font body | Roboto |
| Font tiêu đề | Roboto Condensed |

Google Fonts nạp bằng `wp_enqueue_style` trong `functions.php` với dải
`wght@400..800` — cả hai họ là variable font nên một dải trả về một file cho mỗi tập ký tự, thay vì
một file cho mỗi weight. Site dùng weight 400, 500, 600, 700, 800.

Màu nhấn nằm ở bốn nơi, đổi màu phải đụng cả bốn:

| Nơi | Cách đổi |
|---|---|
| `--hdt-accent` trong `themes/hoangditru/style.css` | phần thuộc theme, kể cả CSS inline trong `category.php` / `single.php` |
| `--tc-orange`, `--lpb-orange`, `--bds-orange` trong CSS plugin | tên biến giữ nguyên vì `s3_title_orange`/`s4_title_orange` là khoá post meta trong DB |
| Mã màu viết thẳng cho hover và nền alpha | `rgba(30,58,115,α)`, các sắc tối/sáng hơn tính từ màu gốc bằng cách giữ H và S, dịch L |
| Option `smart_breadcrumb_options` trong **database** | plugin lưu màu dạng serialize; sửa code không đổi được site đã có dữ liệu — xem mục 5 trong [`scripts/import-fixes.sql`](scripts/import-fixes.sql) |

Khi đổi màu, dò bằng **sắc độ HSL** thay vì đoán mã màu — cách đó bắt được cả các biến thể hover và
gradient mà tìm chuỗi thường bỏ sót.

## Khôi phục khi nhân WordPress bị xoá

Dấu hiệu: site trả **403**, `https://domain/index.php` trả 404, nhưng `wp-admin/` và `wp-includes/`
vẫn còn. Nghĩa là các file PHP ở gốc đã bị xoá, không còn gì để LiteSpeed serve.

1. **hPanel → Advanced → GIT → xoá repository đang cấu hình.** Làm trước tiên, nếu không mọi thứ
   sửa sau đó sẽ bị ghi đè ở lần deploy kế tiếp.
2. **Dọn rác repo khỏi docroot:** `.git`, `.github`, `deploy`, `scripts`, `README.md`,
   `.gitignore`, `.gitattributes`. Giữ `wp-admin`, `wp-includes`, `wp-content`, `wp-config.php`,
   `.htaccess`.
3. **hPanel → Files → Backups** — có bản trước lúc hỏng thì restore, xong luôn. Không có thì làm tiếp:
4. Tải `https://vi.wordpress.org/latest-vi.zip`, upload vào docroot qua File Manager, giải nén →
   thư mục `wordpress/`.
5. Từ `wordpress/wp-content/` copy `index.php` và `themes/twentytwentyfive` sang `wp-content/` của
   site (theme dự phòng, phòng khi theme chính lỗi). Rồi xoá `wordpress/wp-content/`.
6. Xoá `wp-admin/` và `wp-includes/` cũ trong docroot — tránh trộn file hai phiên bản.
7. Chuyển toàn bộ nội dung còn lại của `wordpress/` ra docroot. Xoá `wordpress/` và file zip.

Bản zip không chứa `wp-config.php` (chỉ có `wp-config-sample.php`) nên không đè config, database
giữ nguyên. Xong bước 7 là site lên lại; đăng nhập `/wp-admin` rồi làm tiếp từ mục *Bật theme và
plugin trong WP admin*.

## Secret / token của site

Không đặt trong Git. Tạo file `wp-content/app-config.php` bằng File Manager rồi `require` từ
`wp-config.php` — `.gitignore` đã chặn `*-config.php`.

## Lỗi thường gặp

| Triệu chứng | Nguyên nhân | Xử lý |
|---|---|---|
| Actions xanh nhưng server không có file mới | Chưa đặt secret → job `ftp` bị bỏ qua | Xem log job `ftp`, đặt 3 secret |
| Job `ftp` đỏ: *Không thấy 'themes' trong …* | `FTP_PATH` trỏ sai chỗ, hoặc WordPress chưa được cài | Log in ra danh sách thư mục đích — đối chiếu rồi sửa `FTP_PATH`. Không file nào bị đẩy lên |
| Job `ftp` đỏ: *Không liệt kê được …* | Sai `FTP_HOST`/`FTP_USER`/`FTP_PASSWORD`, hoặc host chặn FTPS | Thử đăng nhập bằng FileZilla với đúng 3 giá trị đó |
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
