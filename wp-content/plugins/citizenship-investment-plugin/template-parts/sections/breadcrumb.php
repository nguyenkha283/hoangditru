<?php
/**
 * breadcrumb.php — Deprecated.
 * Breadcrumb now handled by Smart Breadcrumb plugin via sbc_citizenship_single().
 * This file is kept to avoid fatal errors if ci_part('breadcrumb') is called elsewhere.
 */
defined( 'ABSPATH' ) || exit;
if ( function_exists( 'sbc_citizenship_single' ) ) {
    sbc_citizenship_single();
}
