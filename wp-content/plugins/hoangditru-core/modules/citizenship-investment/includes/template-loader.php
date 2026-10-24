<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'template_include', 'ci_template_loader' );
function ci_template_loader( $template ) {
    if ( is_singular( 'citizenship_invest' ) ) {
        $plugin_tpl = CI_DIR . 'page-templates/single-citizenship-invest.php';
        if ( file_exists( $plugin_tpl ) ) return $plugin_tpl;
    }
    return $template;
}
