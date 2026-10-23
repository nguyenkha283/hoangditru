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

    public function load( $template ) {
        global $post;
        if ( ! $post ) return $template;

        // Chỉ xử lý CPT golden_visa
        if ( $post->post_type !== 'golden_visa' ) return $template;

        $selected = get_post_meta( $post->ID, '_wp_page_template', true );
        if ( ! isset( $this->templates[ $selected ] ) ) return $template;

        $file = LPB_DIR . 'page-templates/template-landing.php';
        return file_exists( $file ) ? $file : $template;
    }
}

new LPB_Page_Template();
