# hoangditru — code website, deploy GitHub → Hostinger

Repo chứa **code tự viết** của site (lấy từ `public_html` của hosting cPanel cũ), đã tách khỏi
theme Flatsome. Gói Hostinger **không có SSH**, nên deploy bằng **FTPS từ GitHub Actions** — file
lên đúng `wp-content/themes` và `wp-content/plugins`, WordPress thấy ở vị trí chuẩn, không cần
loader hay symlink.

```
pull request ──▶ GitHub Actions
                 └─ lint          php -l + 5 test script; CHỈ kiểm tra, không deploy

push main ─────▶ GitHub Actions
                 ├─ lint          php -l mọi file; lỗi cú pháp thì DỪNG, không deploy
                 ├─ ftp           FTPS → /public_html/wp-content/
                 └─ deploy-branch dựng nhánh deploy cho hPanel Git
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

**Plugin lõi `hoangditru-core`** — 8 plugin cũ gộp thành một, mỗi cái thành một module, cộng bốn
module viết mới: `phan-quyen`, `bang-dieu-khien`, `menu-tu-dong`, `xoa-cache`

| Module | Vai trò đáng chú ý |
|---|---|
| `phan-quyen` | **giới hạn role Biên tập viên** còn 10 mục nội dung — xem bên dưới |
| `bang-dieu-khien` | thay Trang quản trị bằng bốn ô lối tắt thêm mới nội dung |
| `menu-tu-dong` | bài / danh mục mới tự vào đúng cụm trong menu — xem bên dưới |
| `xoa-cache` | trang Xoá cache cho Biên tập viên, gọi `litespeed_purge_all` |
| `trang-chu` | **dựng thanh nav của toàn site** + template trang chủ |
| `cim-footer` | **dựng footer của toàn site** |
| `bat-dong-san` | CPT bất động sản, slider + lightbox riêng (slick từ CDN) |
| `citizenship-investment` | trang đầu tư quốc tịch |
| `landing-page` | landing page builder |
| `contact-info-manager` | thông tin liên hệ, nguồn hotline cho nav + footer |
| `smart-breadcrumb` | breadcrumb |
| `snera-youtube-embed` | nút nhúng YouTube cho TinyMCE |

**Plugin `hoangditru-webp`** — chuyển ảnh sang WebP, srcset, lightbox. Plugin riêng, tắt được mà
site không hỏng. Xem [*Ảnh: WebP, srcset, lightbox*](#ảnh-webp-srcset-lightbox).

**`plugins-khac/webp-srcset-lightbox`** — bản thứ hai của plugin trên, dùng cho **site khác**. Nằm
ngoài `wp-content/` nên **không** bị deploy lên hoangditru.

Gộp vì đây là **code của site, không phải tiện ích thêm vào**: tắt bất kỳ cái nào là site hỏng —
mất nav, mất footer, mất trang chủ, mất CPT. Gộp lại thì không ai tắt nhầm, và deploy chỉ còn một
thư mục.

### Định danh KHÔNG được đổi

Những thứ này đang nằm trong database. Đổi là mất dữ liệu đang có:

| Loại | Giá trị |
|---|---|
| post type | `bat-dong-san`, `citizenship_invest`, `golden_visa` |
| taxonomy | `bds_category`, `golden_visa_category` |
| option | `cim_contact_info`, `smart_breadcrumb_options`, `trang_chu_settings`, `hdt_phan_quyen_version` |
| capability | `hdt_manage_site_content` — gác trang Trang chủ + Thông tin liên hệ |
| page template | `tct-trang-chu` và `landing-page-plugin/page-templates/template-landing.php` |
| shortcode | 16 cái: `cim_*`, `youtube_embed` — riêng `[cim_signature]` còn đăng ký nhưng không in gì: tính năng chữ ký đã gỡ, bỏ đăng ký thì bài cũ lộ nguyên chuỗi shortcode |
| post meta | `_bds_*`, `s3_title_orange`, `s4_title_orange`, … |
| post meta (menu tự động) | `_hdt_menu_da_them` — dấu "bài này đã được tự thêm vào menu", để mục bị xoá tay không mọc lại |
| post meta (plugin WebP) | `_wsl_webp` — danh sách file đã có WebP của từng ảnh |
| tên file WebP | `ảnh.jpg.webp` nằm cạnh `ảnh.jpg` trong `uploads/` — đổi quy ước là mọi file đã chuyển thành rác |

Chuỗi template của landing page **còn giữ tên thư mục cũ** `landing-page-plugin/…`. Đó là cố ý: nó
là khoá lưu trong `_wp_page_template` của các post `golden_visa`, không phải đường dẫn file. Đường
dẫn thật lấy từ hằng `LPB_DIR`. Đừng "dọn" chuỗi đó.

### Hai chốt chặn khi nạp

`hoangditru-core.php` kiểm tra 8 hàm đặc trưng của các plugin cũ trước khi nạp module. Nếu một
plugin cũ còn bật thì nó **không nạp gì cả** và hiện thông báo trong admin, thay vì "cannot
redeclare function" và trắng trang.

[`scripts/plugin-load.test.php`](scripts/plugin-load.test.php) nạp thật plugin với WordPress giả
lập và kiểm đủ 9 module + các shortcode quan trọng. `php -l` chỉ kiểm cú pháp từng file, không bắt
được require sai đường dẫn hay trùng tên hàm. Test này chạy trong CI trước mỗi lần deploy.

## Quyền của role Biên tập viên

Module `phan-quyen` đặt lại capabilities của role `editor` (WordPress tiếng Việt gọi là **Biên tập
viên**). Áp dụng ngay cho mọi tài khoản đang mang role đó, không phải gán lại role cho từng người.

**Được thêm / sửa / xoá nội dung của 10 mục:**

| Mục | Màn hình |
|---|---|
| Bài viết | `edit.php`, kèm Danh mục và Thẻ |
| Trang | `edit.php?post_type=page` |
| Media | `upload.php`, `media-new.php` |
| Golden Visa | `edit.php?post_type=golden_visa`, kèm Danh mục Golden Visa |
| Bất động sản | `edit.php?post_type=bat-dong-san`, kèm Danh mục BĐS |
| Đầu tư quốc tịch | `edit.php?post_type=citizenship_invest` |
| Trang chủ | `admin.php?page=trang-chu-settings` |
| Thông tin liên hệ | `admin.php?page=contact-info-manager` |
| Menu điều hướng | `nav-menus.php` — chỉ màn này trong cụm Giao diện |
| Xoá cache | `admin.php?page=hdt-xoa-cache` |

Cộng **Bảng tin** và **Hồ sơ cá nhân** (để tự đổi mật khẩu).

**Bị chặn hoàn toàn:** Bình luận, Plugin, Người dùng, Công cụ, Cài đặt — kể cả trang Smart
Breadcrumb, và mọi menu do plugin cài thêm sau này. Trong cụm **Giao diện** chỉ mở màn Menu; Chủ đề,
Customizer, Widget và Sửa file theme vẫn chặn.

### Mục Trang nằm trong danh sách, và đó là bắt buộc

`golden_visa` đăng ký với `capability_type => 'page'`. Quyền sửa Golden Visa và quyền sửa Trang là
**cùng một bộ cap**, không tách được mà không đổi định danh cap của CPT. Biên tập viên được giao cả
mục Trang nên giữ nguyên.

### Bốn tầng chặn

Một tầng không đủ, nên có bốn:

| Tầng | Chặn được gì | Vì sao cần |
|---|---|---|
| Capabilities | REST API, XML-RPC, mọi lối vào | tầng duy nhất có hiệu lực thật |
| Xoá menu (`admin_menu`, priority 999) | menu và submenu ngoài allowlist | menu **Bình luận** và **Công cụ** của WordPress đăng ký bằng cap `edit_posts` nên editor vẫn thấy dù không còn quyền |
| `wp_die` 403 (`admin_init`) | người gõ thẳng URL | ẩn menu chỉ làm mất đường dẫn |
| `map_meta_cap` | post thuộc post type ngoài allowlist | CPT của plugin thứ ba để `capability_type` mặc định là `post` thì editor tự động sửa được qua REST, ẩn menu không với tới |

Cách làm là **allowlist**: cái gì không có trong danh sách thì biến mất. Cài thêm plugin sau này
cũng tự động bị ẩn khỏi Biên tập viên, không phải sửa file.

### Hai trang cài đặt dùng cap riêng

Trang chủ và Thông tin liên hệ trước đây gác bằng `manage_options`. Cấp `manage_options` cho editor
là mở luôn Cài đặt, Plugin, Người dùng, Giao diện. Nên hai trang đó chuyển sang cap riêng
`hdt_manage_site_content`, cấp cho `administrator` và `editor`, không kéo theo gì khác.

### Menu điều hướng: mở đúng một màn trong cụm Giao diện

WordPress không có cap riêng cho menu điều hướng — `edit_theme_options` gác chung cả Chủ đề,
Customizer, Widget và Sửa file theme. Không cấp thì không sửa được menu, cấp thẳng thì mở cả cụm.
Nên cap đó đi kèm ba thứ chặn lại phần không cần:

- `HDTQ_SCREENS` chỉ thêm `nav-menus.php`. `themes.php`, `customize.php`, `widgets.php`,
  `theme-editor.php` vẫn bị tầng `wp_die` trả 403.
- `HDTQ_MENU_PARENTS` giữ mục cha **Giao diện** trên sidebar để nó chở được mục con **Menu** — xoá
  cha là mất luôn con. Cap của mục cha bị hạ xuống `edit_theme_options`; link của nó thì
  `_wp_menu_output()` tự trỏ sang mục con đầu tiên còn lại. Hết con được phép thì mục cha cũng xoá,
  để không còn lại một mục bấm vào là 403.
- `hdtq_prune_admin_bar()` gỡ các mục Giao diện trên thanh admin, vì chúng dẫn vào màn bị 403.
- `HDTQ_BLOCKED_CAPS` từ chối thẳng `customize` và `edit_css` ở tầng `map_meta_cap`. Cần vì phần
  lưu của Customizer đi qua `admin-ajax.php`, chỗ tầng `wp_die` buộc phải thoát sớm — không chặn
  thì một request tự dựng vẫn đổi được logo site và Additional CSS. Tiêu đề và mô tả site thì
  không: hai setting ấy đòi `manage_options`.

Theme này không đăng ký sidebar nào, nên màn Widget cũng không có gì để sửa.

### LiteSpeed Cache: menu của nó không mở được cho editor

LiteSpeed chốt cứng `manage_options` cho cả menu lẫn nút purge trên thanh admin, và không để lại
filter nào đổi cap đó — bản 7.9.2, xem `src/admin.cls.php`, `src/admin-display.cls.php`,
`src/gui.cls.php`. Cấp `manage_options` cho editor là mở Cài đặt, Plugin, Người dùng ở tầng REST và
XML-RPC, tức phá đúng thứ bốn tầng trên tồn tại để chặn.

Nên thay bằng module `xoa-cache`: trang **Xoá cache** gác bằng `hdt_manage_site_content`, gọi hook
công khai `litespeed_purge_all` của LiteSpeed (khai ở `src/api.cls.php`). Biên tập viên xoá được
cache sau khi sửa nội dung; phần cấu hình LiteSpeed vẫn là việc của administrator. Nút chỉ hiện cho
người **không** có `manage_options` — ai có thì đã có nút purge của chính LiteSpeed.

Hộp **LiteSpeed** trong màn sửa bài thì editor đã thấy sẵn từ trước: hộp đó không kiểm quyền.

### Vài điểm cần biết

- **Người có `manage_options` không bị chặn**, kể cả khi họ mang thêm role editor — để không tự khoá
  chính mình.
- Hai widget Bảng tin **Tổng quan** và **Hoạt động** bị bỏ với Biên tập viên: cả hai gọi thẳng hàm
  bình luận của WordPress, không kiểm `moderate_comments`, nên bỏ cap thôi là chưa đủ. Cột **Bình
  luận** trong danh sách bài viết cũng bị bỏ vì nó trỏ tới màn hình đã chặn.
- Giữ `unfiltered_html` cho Biên tập viên vì meta box của landing page và Golden Visa cần dán được
  iframe/embed.
- **Khối tái sử dụng** (post type `wp_block`) không nằm trong allowlist, nên Biên tập viên không
  chèn được khối tái sử dụng trong trình soạn thảo khối — đúng theo yêu cầu "chặn các mục khác",
  không phải lỗi. Nếu site có dùng tới, thêm `'wp_block'` vào `HDTQ_POST_TYPES` và bump
  `HDTQ_VERSION`. Trình soạn thảo vẫn chạy bình thường khi thiếu nó, chỉ hiện thông báo trong
  trình chèn khối.
- Sau khi sửa `HDTQ_CAPS`, **phải bump `HDTQ_VERSION`** trong `phan-quyen.php`, nếu không quyền
  không được ghi lại. Giá trị đang lưu nằm ở option `hdt_phan_quyen_version`.
- [`scripts/phan-quyen.test.php`](scripts/phan-quyen.test.php) kiểm 107 trường hợp của allowlist và
  chạy trong CI. Việc menu có thật sự biến mất trên site thật thì chưa kiểm được — xem mục dưới.

## Menu: mục mới tự vào đúng cụm

Menu chính xếp theo cụm: mục cha **Golden Visa** chở các bài `golden_visa`, **Đầu tư quốc tịch** chở
các bài `citizenship_invest`, **Bất động sản** chở các term `bds_category`. WordPress chỉ tự thêm
vào menu được với `page` (tuỳ chọn *Tự động thêm trang*), không có gì cho CPT và taxonomy — nên
trước đây mỗi lần thêm nội dung lại phải vào Giao diện → Menu kéo tay, và quên là trang mới không ai
vào được từ menu.

Module `menu-tu-dong` làm việc đó:

| Khi nào | Thêm gì |
|---|---|
| bài `golden_visa` xuất bản **lần đầu** | mục con dưới mục cha đang chở các bài `golden_visa` |
| bài `citizenship_invest` xuất bản lần đầu | tương tự, dưới mục cha của loại đó |
| term `bds_category` được tạo | mục con dưới mục cha đang chở các term `bds_category` |

Mục cha **không chép cứng theo tên**: module xem chính menu đang có, lấy mục cha của các mục cùng
loại (theo số đông, nên một mục bị kéo lệch ra ngoài cụm không lái cả nhóm theo). Đổi tên mục cha,
dựng lại menu, hay thêm menu thứ hai cũng chạy đúng, không phải sửa code. Menu nào đang chở loại đó
thì menu đó nhận mục mới — kể cả menu chân trang.

Nhãn của mục để **rỗng**, nên WordPress lấy tiêu đề bài / tên term mỗi lần dựng menu: đổi tên nội
dung là menu đổi theo. Điền sẵn tiêu đề thì nhãn đóng băng tại thời điểm thêm.

**Không thêm khi:** sửa bài đã xuất bản, hạ bài xuống nháp, bài viết thường, bất động sản (vào menu
qua danh mục chứ không từng bài), hoặc mục đó vốn đã có trong menu. Xoá mục khỏi menu bằng tay thì
nó **không mọc lại**: post meta `_hdt_menu_da_them` ghi là đã làm xong việc với bài đó.

Chưa menu nào chở loại đó — menu mới dựng — thì mục mới vào cấp 1 của menu ở vị trí `primary`: hiện
sai chỗ thì còn sửa được, chứ không hiện thì không ai biết là thiếu. Chưa gán menu vào vị trí nào
thì không thêm và cũng không đánh dấu, nên gán menu xong là lần xuất bản sau chạy đúng.

[`scripts/menu-tu-dong.test.php`](scripts/menu-tu-dong.test.php) dựng lại đúng cấu trúc menu đang
chạy ngoài site rồi kiểm 37 trường hợp.

## Thứ tự hộp trong màn soạn bài

Ba màn soạn Golden Visa, Đầu tư quốc tịch, Bất động sản xếp hộp theo đúng thứ tự hiển thị ngoài
trang. Việc đó do `hdtc_admin_layout()` trong `hoangditru-core/includes/admin-layout.php` làm, và có
hai cái bẫy đã dính:

**1. Thứ tự đăng ký không có tác dụng.** WordPress nhớ thứ tự kéo thả theo *từng người dùng* trong
user meta `meta-box-order_<post_type>`, và thứ tự đó đè lên thứ tự `add_meta_box()`. Nên phải ép qua
filter `get_user_option_meta-box-order_<post_type>`, không phải sắp lại lúc đăng ký.

**2. Hộp priority `high` luôn nằm trên tất cả.** `do_meta_boxes()` render các priority theo thứ tự
`high → sorted → core → default → low`, mà thứ tự ép ở trên nằm ở **`sorted`**. Nên câu "hộp không
liệt kê thì xếp sau" chỉ đúng với `core`, `default`, `low`:

| Plugin | Hộp | Đăng ký | Kết quả |
|---|---|---|---|
| LiteSpeed Cache | `litespeed_meta_boxes` | `side` / `core` | tự xuống dưới, không cần làm gì |
| Rank Math SEO | `rank_math_metabox_link_suggestions` | `side` / `default` | tự xuống dưới |
| Rank Math SEO | `rank_math_metabox` | `normal` / **`high`** | chen lên trên cùng |

Cờ `'tail' => true` giải quyết hộp loại cuối: `hdtc_demote_unlisted_boxes()` hạ **mọi** hộp không
liệt kê ở cột chính xuống `low`, nên không phải chép cứng id hộp nào và plugin cài thêm sau này cũng
tự xuống dưới. Bật cho cả ba màn.

Hàm đó **không** dời giá trị `false` — đó là dấu `remove_meta_box()` để lại ở cả bốn priority, dời đi
là mất dấu rồi một `add_meta_box()` sau đó dựng lại đúng hộp đáng ra đã gỡ. Màn Golden Visa gỡ hai
hộp ở cột phải theo cách đó nên chuyện này có thật, không phải phòng xa.

Ô nội dung của WordPress thì không kéo thả được: `edit-form-advanced.php` render nó ngoài hệ thống
meta box nên nó luôn ghim sát dưới tiêu đề. Ba màn này đều gỡ `editor` khỏi `supports` rồi dựng lại
bằng `wp_editor()` trong một meta box — id trình soạn **phải** giữ đúng `content`, vì
`_wp_translate_postdata()` đọc `$_POST['content']` để gán vào `post_content`.

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

Phân quyền Biên tập viên cũng vậy: allowlist có test, nhưng việc menu thật sự biến mất và `wp_die`
thật sự chặn thì phải đăng nhập bằng một tài khoản Biên tập viên rồi xem bằng mắt — thấy đúng 8 mục
cộng Bảng tin và Hồ sơ cá nhân, và gõ tay `/wp-admin/options-general.php` phải ra 403.

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
2. **Plugin** → kích hoạt *Hoàng Di Trú Core* và *Hoàng Di Trú WebP* (xem mục WebP để chuyển ảnh cũ)
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

Làm qua pull request thì job `lint` chạy ngay trên PR, còn `ftp` và `deploy-branch` tự loại mình ra
(`if: github.event_name != 'pull_request'`). Nên **CI xanh trên PR không có nghĩa là site đã cập
nhật** — chỉ push/merge vào `main` mới deploy.

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

Ngày hôm sau chuyện lặp lại đúng như vậy: `a61fe42` lúc 01:34 xanh, `87400b8` lúc **01:35** đỏ cả
hai lần đẩy. Hai push cách nhau một phút.

Bốn thay đổi để không lặp lại:

| | |
|---|---|
| **Giảm số phiên** | 1 phiên khi có `FTP_PATH`, 2–3 khi phải dò. Dừng ở kết quả đầu tiên thay vì thử hết; bỏ phiên "thăm dò TLS" riêng, gộp vào lần liệt kê đầu |
| **Huỷ run cũ** | `concurrency.cancel-in-progress: true` — push mới huỷ run đang chạy, nên hai push sát nhau chỉ tạo **một** cụm phiên FTP thay vì hai. `FTP-Deploy-Action` chỉ ghi file trạng thái khi thành công, nên run bị huỷ giữa chừng không để lại trạng thái sai |
| **Tự thử lại** | `ftp_list` thử tối đa 3 lần, giãn 15s → 45s. Bước đẩy file hỏng thì nghỉ **4 phút** rồi đẩy lần hai — đã thử 90 giây và không đủ |
| **Không nói dối** | `continue-on-error` ở lần đẩy đầu khiến API báo bước đó `success` dù nó hỏng, nên có bước *Kết luận* với `if: always()` tự đánh đỏ. Thiếu `always()` thì chính bước đó bị skip khi lần hai hỏng |

Dù vậy vẫn nên **gom thay đổi rồi push một lần**, đừng đẩy nhiều commit trong vài phút.

### Đọc kết quả run cho đúng

API `actions/runs/<id>/jobs` trả `conclusion` **sau khi** `continue-on-error` che lỗi, nên bước
*FTPS deploy* có thể hiện `success` trong khi nó đã hỏng. Dấu hiệu nhận biết: bước *Nghỉ 4 phút* và
*FTPS deploy (lần 2)* có chạy hay không — chúng chỉ chạy khi lần đầu thất bại.

## Chuyển sang hPanel Git

FTP chạy được nhưng phải vá nhiều: giảm phiên, retry, huỷ run cũ — toàn bộ để né việc Hostinger
chặn phiên FTP. hPanel Git không có lớp vấn đề đó: server tự `git pull` qua HTTPS, **không phiên FTP
nào**, và **không cần mật khẩu FTP trong GitHub Secrets**.

### Luật sắt

**hPanel Git XOÁ SẠCH thư mục đích trước mỗi lần kéo code.** Ngày 2026-10-23 nó được trỏ vào
`public_html` và đã xoá toàn bộ file PHP gốc của WordPress; site trả 403, phải khôi phục thủ công.

Vì vậy install path **bắt buộc** là `wp-content/app` — thư mục riêng, rỗng, không chứa gì ngoài nội
dung nhánh `deploy`.

### Vì sao cần loader

WordPress chỉ tìm theme ở `wp-content/themes` và plugin ở `wp-content/plugins`. hPanel Git chỉ cài
được **1 kho / 1 nhánh / 1 thư mục**, nên code buộc phải nằm gom trong `app/`.
[`deploy/mu-plugin.php`](deploy/mu-plugin.php) bắc cầu:

| | |
|---|---|
| Theme | `register_theme_directory( app/themes )`. WordPress tự suy ra URL vì `app/` nằm trong `wp-content` |
| Plugin lõi | `require` thẳng `app/plugins/hoangditru-core/hoangditru-core.php` |
| URL asset | filter `plugins_url` dựng lại URL cho file nằm ngoài `wp-content/plugins`, nếu không mọi CSS/JS của plugin sẽ thành `…/wp-content/plugins/home/u…/wp-content/app/…`. Có test riêng: [`scripts/loader-url.test.php`](scripts/loader-url.test.php), 7 ca |

Loader đặt ở `wp-content/mu-plugins/hoangditru-loader.php` — **ngoài** `app/` nên Git không đụng tới,
và mu-plugin thì luôn chạy, không ai tắt được.

### Chốt tự bảo vệ, để chuyển đổi lùi được

Nếu `hoangditru-core` vẫn đang bật như plugin thường, loader **không nạp** bản trong `app/` và hiện
thông báo trong admin. Nạp cả hai là "cannot redeclare function" và trắng trang. Nhờ chốt này, việc
chuyển đổi làm được từng bước, và muốn lùi chỉ cần bật lại plugin thường.

### Các bước chuyển đổi

1. Push xong, kiểm nhánh `deploy` trên GitHub: gốc nhánh phải là `themes/`, `plugins/`,
   `mu-plugin.php`.
2. hPanel → **Advanced → GIT** → *Create a new repository*: repo này, branch **`deploy`**, install
   path **`wp-content/app`**. Copy webhook ở mục Auto-Deployment sang GitHub →
   Settings → Webhooks.
3. Kiểm `wp-content/app/` đã có `themes/`, `plugins/`, `mu-plugin.php`.
4. File Manager: copy `wp-content/app/mu-plugin.php` thành
   `wp-content/mu-plugins/hoangditru-loader.php`. Site **chưa đổi gì** — loader thấy plugin thường
   còn bật nên tự đứng ngoài.
5. WP admin → **Plugin** → tắt *Hoàng Di Trú Core*. Loader tiếp quản ngay.
6. WP admin → **Giao diện** → kích hoạt lại *Hoangditru* để `template_root` trỏ sang `/app/themes`.
7. `bash scripts/check-site.sh https://hoangditru.com`. Script thử asset ở cả `wp-content/plugins`
   lẫn `wp-content/app/plugins` và in ra chỗ nào đang phục vụ, nên chạy được trước và sau khi
   chuyển. Thấy `(app/plugins, deploy qua Git)` là loader đã tiếp quản.
8. Chạy ổn rồi thì xoá bản thừa: `wp-content/plugins/hoangditru-core/`,
   `wp-content/themes/hoangditru/`, và 8 thư mục plugin cũ.
9. Cuối cùng đổi job `ftp` sang chỉ chạy tay (`workflow_dispatch`) để nó không ghi lại các thư mục
   vừa xoá.

Giữ `twentytwentyfive` trong `wp-content/themes` làm theme dự phòng: nếu `app/` có sự cố, WordPress
còn thứ để rơi về thay vì chết hẳn.

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

## Ảnh: WebP, srcset, lightbox

Ba việc, một plugin:

| | |
|---|---|
| **WebP** | Mỗi ảnh JPEG/PNG trong thư viện Media, và **mọi size** của nó, có thêm một bản `ảnh.jpg.webp` nằm cạnh. **Ảnh gốc giữ nguyên.** Ảnh upload từ giờ được chuyển ngay lúc upload; ảnh đang có thì chuyển một lần ở *Media → Chuyển WebP*. Bản WebP nào không nhỏ hơn ảnh gốc (hay gặp ở PNG ít màu) thì bị bỏ, trang dùng ảnh gốc |
| **srcset** | Trình duyệt chọn size vừa với khung hiển thị thay vì luôn tải `large`/`full`. Thẻ BĐS rộng 290px trên điện thoại không còn tải ảnh 1024px |
| **Lightbox** | Trang chỉ tải bản vừa khung. Bản lớn nằm ở `data-full` và **chỉ tải khi người dùng bấm**; trong lúc chờ, lightbox hiện ngay bản nhỏ đã có trong cache. Áp dụng toàn site, **trừ** phần mặt bằng của trang BĐS (đã có `#bds-lightbox` riêng) và thumbnail điều hướng của slider BĐS |

Lightbox chỉ mở cho ảnh có bản lớn hơn rõ rệt (rộng ≥ 600px và ≥ 1,25 lần cỡ đang hiện), không nằm
trong link tới trang khác (thẻ bài, thẻ dự án vẫn là link), và đang hiển thị rộng từ 120px (bỏ icon,
avatar). Có prev/next, phím ← → Esc, vuốt trên điện thoại, không cần jQuery. Thêm class
`no-lightbox` hoặc thuộc tính `data-no-lightbox` vào một vùng để loại trừ.

### Hai bản

| | `wp-content/plugins/hoangditru-webp` | `plugins-khac/webp-srcset-lightbox` |
|---|---|---|
| Dùng cho | **hoangditru** | site khác, theme không sửa được |
| Cách đổi URL | bám vào API ảnh của WordPress (`wp_get_attachment_image_src`, `wp_calculate_image_srcset`) | output buffer lọc **toàn bộ HTML** của trang |
| Điều kiện | template in ảnh bằng `wp_get_attachment_image()`/`hdtc_img()` — đã sửa tay, xem dưới | không có |
| Chi phí mỗi trang | gần như không | một lượt regex trên HTML + tối đa 2 truy vấn DB; LiteSpeed cache lại HTML đã lọc nên chỉ trả một lần |
| Deploy | có, theo `deploy/manifest.txt` | **không** — nằm ngoài `wp-content/`. Zip thư mục đó rồi *Plugin → Cài mới → Tải plugin lên* trên site kia |

Phần chuyển đổi, lightbox, trang admin, phần tắt WebP của LiteSpeed **giống hệt từng byte** giữa hai
bản (danh sách ở [`scripts/webp-shared.txt`](scripts/webp-shared.txt)). Sửa ở bản hoangditru rồi
`bash scripts/webp-sync.sh`; [`scripts/webp.test.php`](scripts/webp.test.php) đỏ nếu hai bản lệch
nhau. Bật cả hai trên cùng site thì bản bật sau tự đứng ngoài và báo trong admin.

### 52 thẻ `<img>` tự viết

Trước đây template tự viết `<img src="<?php echo $url ?>">` — một file duy nhất, không srcset. Bản
hoangditru sửa tay từng thẻ sang `hdtc_img()` ([`hoangditru-core/includes/img.php`](wp-content/plugins/hoangditru-core/includes/img.php))
hoặc `wp_get_attachment_image()` (trong theme, để theme không phụ thuộc plugin). Hàm đó **không phụ
thuộc plugin WebP**: tắt plugin thì vẫn có srcset, chỉ là JPEG.

| | Số thẻ | Xử lý |
|---|---|---|
| Frontend, ảnh thư viện Media | 23 | sang `hdtc_img()`/`wp_get_attachment_image()`, kèm `sizes` theo lưới CSS thật (thẻ BĐS 4→3→2→1 cột, carousel 100vw, icon 36–90px…) |
| Thẻ BĐS lazy-load bằng `data-src` | 1 | thêm `data-srcset`/`data-sizes`; `category.js` gắn chúng trước `src` |
| Avatar tác giả trong `single.php` | 1 | sang `get_avatar()` (có srcset 2x). Ảnh Gravatar ở máy chủ ngoài nên **không** đổi WebP được |
| `placeholder.jpg` của theme trong `category.php` | 2 | giữ nguyên: không phải ảnh Media. Lưu ý: file `themes/hoangditru/assets/images/placeholder.jpg` **không có trong repo**, nên bài không có ảnh đại diện đang hiện ảnh vỡ |
| `.bds-lb-img` của lightbox BĐS | 1 | giữ nguyên: `src` do JS gắn |
| Ảnh xem trước trong meta box/trang cài đặt (admin) | 24 | giữ nguyên, có chủ ý: JS của trình chọn ảnh đổi `src` khi chọn ảnh mới — thêm `srcset` vào thì `srcset` thắng và ảnh xem trước đứng im. Admin cũng không ảnh hưởng tốc độ trang |

Template mới thì đừng tự viết `<img src>` cho ảnh Media nữa — dùng `hdtc_img( $id_hoặc_url, $size, $attr )`.

`width`/`height` mà `wp_get_attachment_image()` thêm vào là kích thước file. Theme có
`img { height: auto }` nên không méo; riêng `.lpb-hero__icon` chỉ đặt `height` nên đã thêm
`width: auto`, và hai avatar của `[cim_avatar]`/`[cim_card]` đặt cỡ bằng `style`.

Ảnh trong **nội dung bài** đi qua bộ lọc HTML ở `the_content`: đổi WebP, thêm srcset cho ảnh dán URL
tay (không có class `wp-image-N`), gắn `data-full`. Ảnh nền `url(...)` lấy từ
`wp_get_attachment_image_url()` (footer, banner landing) cũng thành WebP.

**Không** đổi sang WebP: trong wp-admin (chèn ảnh vào bài vẫn là URL gốc), REST API (trình soạn
thảo khối), feed RSS, và trong `<head>` — `og:image` của Rank Math giữ JPEG vì Zalo/Facebook cũ
không đọc được WebP.

### LiteSpeed Cache

WebP của LiteSpeed (*Image Optimization → Image WebP Replacement*, *WebP For Extra srcset*) bị tắt
theo hai lớp: khi plugin bật, filter `pre_option_litespeed.conf.*` ép các tuỳ chọn đó về 0 dù ai
bật lại trong trang cài đặt; và lúc kích hoạt plugin ghi hẳn 0 vào DB, rồi purge toàn bộ cache. Cache
trang của LiteSpeed giữ nguyên.

Để cả hai cùng chạy thì LiteSpeed thay URL lần nữa, thêm biến thể cache theo trình duyệt, và hai bộ
`.webp` nằm cạnh nhau.

### Cài trên hoangditru

1. Merge vào `main` → job `ftp` đẩy `wp-content/plugins/hoangditru-webp/` lên.
2. WP admin → **Plugin** → bật **Hoàng Di Trú WebP**. Lúc này LiteSpeed đã bị tắt WebP và cache đã purge.
3. **Media → Chuyển WebP** → *Chuyển tất cả*. Trang chạy từng mẻ ~15 giây qua AJAX, có thanh tiến
   độ; đóng tab giữa chừng không sao — chạy lại thì ảnh đã xong được bỏ qua. 393 MB / 3635 file
   uploads cũ thì mất cỡ vài chục phút. Xong tự purge LiteSpeed.
4. LiteSpeed Cache → *Image Optimization* → *Image WebP Replacement* → OFF → **Lưu** một lần, để
   LiteSpeed viết lại `.htaccess` không còn luật WebP của nó.
5. Mở trang bằng DevTools → Network → lọc `webp`: ảnh phải là `….jpg.webp`, và bấm vào ảnh trong
   bài mới thấy request bản lớn.

Khi chuyển sang **hPanel Git**: loader trong `mu-plugins` chỉ nạp `hoangditru-core` từ `app/`, không
nạp plugin này. Giữ bản ở `wp-content/plugins/hoangditru-webp/` (bước 8 của mục chuyển đổi chỉ xoá
`hoangditru-core` và theme), và cập nhật nó bằng tay khi có bản mới.

Tuỳ biến bằng filter: `wsl_quality` (mặc định 82), `wsl_lightbox_exclude`, `wsl_lightbox_enabled`,
`wsl_lightbox_min_width`, `wsl_upload_hosts` (CDN), `wsl_convert_on_upload`. Thuộc tính
`data-no-webp` trên một `<img>` để bỏ qua ảnh đó. Có WP-CLI thì `wp wsl convert [--force]`.

### Đã kiểm thế nào

- [`scripts/webp.test.php`](scripts/webp.test.php), 77 trường hợp, chạy trong CI.
- Chạy thật trên **WordPress 6.5 + SQLite** với theme và plugin lõi của repo: upload ảnh → đủ
  `.webp` cho mọi size; trang chuyển hàng loạt chạy hết rồi lần hai bỏ qua hết; HTML của trang chủ,
  bài viết, danh mục, BĐS, Golden Visa đúng WebP + srcset; thư viện Media trong admin vẫn URL gốc.
  Bản lọc toàn trang được thử trên **template gốc chưa sửa** — đúng tình huống site khác.
- Chromium (Playwright): ảnh lớn chỉ được request sau khi bấm; ←/→/Esc; ảnh nhỏ không mở; link bọc
  ảnh không điều hướng sang file; mặt bằng BĐS vẫn mở `#bds-lightbox` chứ không mở lightbox chung;
  thẻ BĐS trên điện thoại chọn đúng size theo `data-srcset`.
- **Chưa** thử trên Hostinger và LiteSpeed thật: tên tuỳ chọn `litespeed.conf.img_optm-webp*` lấy
  theo mã nguồn LiteSpeed Cache, chưa đối chiếu trên site. Bước 4 ở trên là để chắc ăn.

## Màu và font của site

| | |
|---|---|
| Màu nhấn | `#1e3a73` |
| Font body | UTMAptima — file local `assets/fonts/UTMAptima.woff`, khai bằng `@font-face` |
| Font tiêu đề | Phudu — Google Fonts |

Phudu nạp bằng `wp_enqueue_style` trong `functions.php` (không `@import`: `@import` chặn render vì
trình duyệt phải tải xong `style.css` mới biết có font cần tải, còn thẻ `<link>` tải song song).
Dải `wght@300..900` vì Phudu là variable font: một dải trả về một file cho mỗi tập ký tự, thay vì
một file cho mỗi weight. Kèm `preconnect` tới `fonts.gstatic.com` qua `wp_resource_hints`.

UTMAptima **chỉ có một file, weight normal**. Chữ in đậm trong body là trình duyệt tự làm dày
(synthetic bold), không phải file riêng. Có file bold thật thì thêm một `@font-face` nữa với
`font-weight: 700`.

Mọi CSS của plugin đều dùng `font-family: inherit`, nên đổi font chỉ phải sửa ở theme.

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
| Ảnh vẫn là `.jpg` chứ không phải `.jpg.webp` | Ảnh cũ chưa chuyển, hoặc trang đang lấy từ cache | Media → Chuyển WebP → *Chuyển tất cả*, rồi LiteSpeed Cache → Purge All |
| Trang chuyển WebP báo máy chủ không ghi được WebP | PHP thiếu GD có `imagewebp` lẫn Imagick có WEBP | hPanel → Advanced → PHP Configuration → bật extension `gd`/`imagick`; ảnh vẫn hiện bằng bản gốc trong lúc chờ |
| Nội dung hiện chuỗi `[ux_text ...]` | Theme `hoangditru` chưa được kích hoạt | Bước 5 |
| Trang trắng sau khi bật theme | Lỗi PHP runtime | Bật `WP_DEBUG_LOG` trong `wp-config.php` qua File Manager, đọc `wp-content/debug.log` |
| Upload ảnh báo "Lỗi khi tải lên" | `async-upload.php` trả HTML *Error establishing a database connection* | Không phải lỗi code → ticket Hostinger |

## Backup khi không có SSH

- **File:** hPanel → **Files → Backups** (tạo và tải bản backup), hoặc File Manager → nén
  `wp-content` → tải về.
- **Database:** hPanel → **Databases → phpMyAdmin** → tab **Export** → Quick → SQL.

Làm backup trước mỗi lần đổi lớn (đổi theme, đổi plugin, sửa `.htaccess`).
