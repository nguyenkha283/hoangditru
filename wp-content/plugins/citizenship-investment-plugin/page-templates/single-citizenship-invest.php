<?php
/**
 * Single template: Đầu tư quốc tịch
 */
defined( 'ABSPATH' ) || exit;

get_header();

function ci_part( $name ) {
    $f = CI_DIR . "template-parts/sections/{$name}.php";
    if ( file_exists( $f ) ) include $f;
}

while ( have_posts() ) : the_post(); ?>

<?php if ( function_exists( 'sbc_citizenship_single' ) ) sbc_citizenship_single(); ?>

<div class="ci-page-boxed">
<main id="ci-page" class="ci-page">
    <?php
    //ci_part( 'page-title' );
    ci_part( 'carousel' );
    ci_part( 'page-desc' );
    ci_part( 'features' );
    ci_part( 'why-us' );
    ci_part( 'du-an' );
    ci_part( 'programs' );
    ci_part( 'news' );
    ?>
</main>
</div>

<?php endwhile;

get_footer();
