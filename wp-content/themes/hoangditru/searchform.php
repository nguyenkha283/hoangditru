<?php
/**
 * Ô tìm kiếm.
 */
?>
<form role="search" method="get" class="hdt-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label class="screen-reader-text" for="hdt-s"><?php esc_html_e( 'Tìm kiếm', 'hoangditru' ); ?></label>
    <input type="search" id="hdt-s" name="s" value="<?php echo get_search_query(); ?>"
           placeholder="<?php esc_attr_e( 'Tìm kiếm…', 'hoangditru' ); ?>">
    <button type="submit"><?php esc_html_e( 'Tìm', 'hoangditru' ); ?></button>
</form>
