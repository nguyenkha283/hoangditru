# hoangditru — code website, deploy GitHub → Hostinger

Repo chỉ chứa **code tự viết** của site (lấy từ `public_html` của hosting cPanel cũ).
Push lên `main` → GitHub Actions kiểm cú pháp PHP → cập nhật nhánh `deploy` → webhook →
Hostinger `git pull` vào `wp-content/app`.

```
main ──push──▶ GitHub Actions (php -l) ──▶ nhánh "deploy"
                                               │ webhook
Hostinger: hPanel → Git kéo nhánh deploy vào  public_html/wp-content/app/
wp-content/themes/flatsome-child   ─symlink─▶  app/themes/flatsome-child
wp-content/plugins/<8 plugin>      ─symlink─▶  app/plugins/<...>
```

---

## Có gì trong repo

**Theme**

| | |
|---|---|
| `flatsome-child` | theme con tự viết — `category.php`, `single.php`, `functions.php`, `style.css`, `assets/` |

**Plugin tự viết** (8 cái)

| Plugin | Version |
|---|---|
| `bat-dong-san-plugin` | 1.1.0 |
| `cim-footer` | 1.0.0 |
| `citizenship-investment-plugin` | 1.0.0 |
| `contact-info-manager` | 1.1.0 |
| `landing-page-plugin` | 1.2.0 |
| `smart-breadcrumb` | 1.0.0 |
| `snera-youtube-embed` | 1.0.0 |
| `trang-chu` | 1.1.0 |

Tổng ~2 MB.

## Không có gì trong repo (và vì sao)

| Bị loại | Lý do | Cách đưa lên Hostinger |
|---|---|---|
| `themes/flatsome` | Theme **trả phí** (ThemeForest). Repo này **công khai** → không được phát hành lại | Upload thủ công vào `wp-content/themes/flatsome` qua File Manager của hPanel |
| `plugins/advanced-custom-fields` 6.8.10 (bản free) | Plugin bên thứ ba | `wp plugin install advanced-custom-fields` |
| `plugins/classic-editor`, `contact-form-7`, `litespeed-cache`, `really-simple-ssl`, `seo-by-rank-math`, `wp-mail-smtp`, `wpforms-lite` | Plugin bên thứ ba | `wp plugin install <slug>` |
| `plugins/backup` (**JetBackup** 3.1.24.10) | Plugin backup của hosting cPanel cũ, Hostinger không dùng | bỏ hẳn |
| `wp-admin/`, `wp-includes/`, `wp-*.php` | Nhân WordPress 7.1.3 | `wp core download` |
| `wp-config.php` | Chứa DB password + salt | tạo mới bằng `wp config create` |
| `wp-content/uploads/` | Ảnh/file — bạn nói không cần | (có `uploads.zip` nếu sau này muốn) |
| `wp-content/debug.log` (2 MB), `error_log` | Log | bỏ |
| `.htaccess.bak-*`, `.htaccess.bk`, `.htaccess_back_*` (5 file) | Rác backup | bỏ |
| `functions.php.bak-og-facebook-*`, `functions.php.bak-og-meta-*` | 2 bản nháp cũ trong `flatsome-child` | bỏ |
| `wp-content/upgrade/`, `upgrade-temp-backup/`, `litespeed/`, `maintenance/`, `rsssl-managed-htaccess.lock` | Thư mục tạm WordPress/plugin tự sinh | bỏ |
| `robot-1.txt`, `robots-1.txt`, `cgi-bin/`, `.well-known/` | Rác / do hosting quản lý | bỏ |

## Kết quả kiểm tra code

- **Không có secret nào hardcode.** API key Mailchimp của `contact-info-manager` lưu trong DB
  (mặc định rỗng), không nằm trong code.
- **Không có mã nghi vấn** — không `eval`, `base64_decode`, `gzinflate`, `shell_exec`, `system`.
- **Không có đường dẫn tuyệt đối** `/home/<user>/...`.
- Domain cũ `https://khaiditru.com` chỉ xuất hiện ở header `Plugin URI` của
  `bat-dong-san-plugin` — vô hại, không ảnh hưởng chạy.
- **Không plugin nào tạo bảng riêng** (không `CREATE TABLE`/`dbDelta`) → không cần import
  structure bảng custom. File SQL chỉ còn dùng khi muốn lấy lại nội dung.
- Table prefix của site cũ: **`tuC1OPNu_`**. Nếu sau này import dữ liệu từ `.sql` thì site mới
  phải tạo bằng `--dbprefix=tuC1OPNu_`.

## Vì sao dùng symlink, không dùng mu-plugin loader

hPanel Git chỉ cài được **1 kho / 1 nhánh / 1 thư mục**, và **xoá sạch thư mục đích** mỗi lần
deploy → thư mục đích phải riêng và rỗng (`wp-content/app`), tuyệt đối không phải `wp-content`.

Cả 8 plugin đều dùng `plugin_dir_url()` / `plugins_url()` để sinh URL asset, và 3 cái
(`bat-dong-san-plugin`, `contact-info-manager`, `landing-page-plugin`) có
`register_activation_hook`. Nếu nạp chúng kiểu mu-plugin từ `app/` thì URL CSS/JS sẽ sai và hook
kích hoạt không chạy. Vì vậy symlink chúng vào `wp-content/plugins` để WordPress coi như plugin
bình thường — WordPress xử lý plugin symlink đúng qua `wp_register_plugin_realpath()`.

---

## Cài trên Hostinger

### 1. Chuẩn bị site

```bash
# SSH: hPanel → site → Advanced → SSH Access (port thường 65002)
cd ~/domains/<domain-moi>/public_html
wp core download --locale=vi
wp config create --dbname=<db> --dbuser=<user> --dbpass='<pass>' --dbhost=localhost
wp core install --url=https://<domain-moi> --title="<Tên site>" \
                --admin_user=<admin> --admin_email=<email> --admin_password='<pass>'
```

Database tạo trước ở hPanel → **Databases → Management** (lưu password ngay, không xem lại được).

### 2. Theme flatsome (trả phí, upload bằng tay)

hPanel → **File Manager** → `domains/<domain>/public_html/wp-content/themes` → upload thư mục
`flatsome` (nén zip rồi giải nén tại chỗ). Không bỏ vào Git.

### 3. Plugin bên thứ ba

```bash
wp plugin install advanced-custom-fields classic-editor contact-form-7 \
                  litespeed-cache really-simple-ssl seo-by-rank-math \
                  wp-mail-smtp wpforms-lite
```

Chưa activate cũng được, bước 5 bật hết một lượt.

### 4. Nối hPanel Git

hPanel → site → **Advanced → GIT** → *Create a new repository*

| Trường | Giá trị |
|---|---|
| Repository address | `https://github.com/nguyenkha283/hoangditru.git` |
| Branch | `deploy` |
| Install path | `wp-content/app` |

⚠️ **Install path phải đúng `wp-content/app`.** Để trống (= `public_html`) hoặc để `wp-content`
sẽ bị xoá sạch dữ liệu khi deploy.

Repo công khai nên không cần deploy key. Sau khi tạo xong, trong khung repo có mục
**Auto-Deployment** → copy webhook URL → GitHub repo → **Settings → Webhooks → Add webhook**:
Payload URL = webhook đó, Content type `application/json`, trigger *Just the push event*.

### 5. Tạo symlink + bật theme/plugin

```bash
cd ~/domains/<domain-moi>/public_html
curl -sO https://raw.githubusercontent.com/nguyenkha283/hoangditru/main/scripts/hostinger-link.sh
bash hostinger-link.sh ~/domains/<domain-moi>/public_html

wp theme activate flatsome-child
wp plugin activate --all
wp rewrite flush
```

Script bỏ qua (không ghi đè) bất kỳ thư mục **thật** đã tồn tại trong `themes/` hoặc `plugins/`.

### 6. Kiểm tra

```bash
curl -sI https://<domain-moi> | head -1
curl -s https://<domain-moi> | grep -ciE "fatal error|warning:"      # phải ra 0
curl -sI https://<domain-moi>/wp-content/plugins/trang-chu/assets/   # asset qua symlink
```

Nếu asset tĩnh trong plugin/theme bị **403**, nghĩa là Apache/LiteSpeed không theo symlink. Thêm
vào `wp-content/.htaccess`:

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
rồi chạy `hostinger-link.sh` lại một lần để tạo symlink.

Lỗi cú pháp PHP → Actions đỏ, nhánh `deploy` **không** được cập nhật, server giữ nguyên bản cũ.

Đừng sửa tay file trong `wp-content/app` trên server — lần deploy sau ghi đè.

## Secret / token

Không đặt trong Git. Tạo tay qua SSH ở `wp-content/app-config.php` rồi `require` từ
`wp-config.php` — `.gitignore` đã chặn `*-config.php`.

## Lỗi thường gặp trên Hostinger

| Triệu chứng | Nguyên nhân | Xử lý |
|---|---|---|
| Deploy xong vẫn thấy trang cũ | LiteSpeed cache (`X-LiteSpeed-Cache: hit`), khối `# BEGIN LSCACHE` trong `.htaccess` | Backup `.htaccess` rồi xoá khối LSCACHE/NON_LSCACHE; thử bằng `curl -A "Mozilla/5.0 (iPhone)"` không cookie |
| Asset plugin/theme 403 | Apache không theo symlink | `Options +FollowSymLinks` trong `wp-content/.htaccess` |
| Mất `uploads/` sau deploy | Install path trỏ vào thư mục có dữ liệu | Đổi về `wp-content/app`, khôi phục từ backup |
| `wp db import` báo `proc_open disabled` | Hostinger tắt `proc_open` | Dùng `mysql --defaults-extra-file=` (xem mục Backup) |
| Theme con báo thiếu parent | Chưa upload `flatsome` | Làm bước 2 |
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
