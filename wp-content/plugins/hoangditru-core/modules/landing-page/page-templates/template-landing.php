<?php
/**
 * Template Name: Landing Page
 * Template Post Type: golden_visa
 * Plugin: Landing Page Builder
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<main id="lpb-landing">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <?php lpb_part( 'banner' ); ?>
        <?php lpb_part( 'advantages' ); ?>
        <?php lpb_part( 'benefits' ); ?>
        <?php lpb_part( 'conditions' ); ?>
        <?php lpb_part( 'process' ); ?>
        <?php lpb_part( 'du-an' ); ?>
        <?php //lpb_part( 'news' ); ?>
        <?php lpb_part( 'faqs' ); ?>
    <?php endwhile; endif; ?>
</main>

<?php get_footer();
