<?php
/**
 * Thẻ bài viết dùng chung cho index.php, archive.php, search.php.
 */
?>
<li class="hdt-card">
    <?php if ( has_post_thumbnail() ) : ?>
        <a class="hdt-card__thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
            <?php the_post_thumbnail( 'medium_large' ); ?>
        </a>
    <?php endif; ?>

    <h2 class="hdt-card__title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h2>

    <?php if ( ! hdt_is_program_post_type() ) : ?>
        <div class="hdt-card__meta">
            <?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?>
        </div>
    <?php endif; ?>
</li>
