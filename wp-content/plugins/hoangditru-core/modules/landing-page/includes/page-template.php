<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LPB_Page_Template {
    private $templates = [];

    public function __construct() {
        $this->templates = [
            'landing-page-plugin/page-templates/template-landing.php' => 'Landing Page',
        ];
        // Đăng ký template vào CPT golden_visa
        add_filter( 'theme_golden_visa_templates', [ $this, 'register' ] );
        add_filter( 'template_include',            [ $this, 'load' ] );
    }

    public function register( $templates ) {
        return array_merge( $templates, $this->templates );
    }

    /**
     * Áp template cho MỌI bài golden_visa.
     *
     * Trước đây hàm này đòi _wp_page_template khớp khoá template. Nhưng ô chọn
     * Mẫu trang đã bị ẩn khỏi màn soạn (xem includes/admin-ui.php) cho gọn, nên
     * bài tạo mới sẽ không có meta đó và rơi về template mặc định — trang hỏng.
     *
     * CPT này chỉ có đúng một template, nên điều kiện kia không còn ý nghĩa gì
     * ngoài việc tạo ra một cách để hỏng. Bài cũ đã lưu meta vẫn chạy bình
     * thường, meta không bị xoá.
     *
     * $this->templates vẫn đăng ký qua filter theme_golden_visa_templates để
     * khoá 'landing-page-plugin/page-templates/template-landing.php' còn hợp lệ
     * với dữ liệu cũ — khoá đó nằm trong _wp_page_template của các bài hiện có.
     *
     * @param string $template Template WordPress đã chọn.
     * @return string
     */
    public function load( $template ) {
        if ( ! is_singular( 'golden_visa' ) ) return $template;

        $file = LPB_DIR . 'page-templates/template-landing.php';
        return file_exists( $file ) ? $file : $template;
    }
}

new LPB_Page_Template();
