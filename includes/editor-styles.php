<?php
/**
 * Editor canvas styles.
 *
 * Loads the project's front-end stylesheet into the editor canvas so block
 * previews match the live site. Since WordPress 7.1 the post editor is
 * always iframed, so nothing reaches the canvas unless it is enqueued for
 * it explicitly.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue the front-end stylesheet inside the editor canvas.
 *
 * The enqueue_block_assets hook runs for both the front end and the iframed
 * editor. The is_admin() guard limits this to the editor, since the theme
 * already loads its own stylesheet on the front end.
 *
 * @return void
 */
function bb_enqueue_editor_canvas_styles() {
    if ( ! is_admin() ) {
        return;
    }

    if ( ! defined( 'BB_FRONT_CSS' ) || '' === BB_FRONT_CSS ) {
        return;
    }

    wp_enqueue_style(
        'bb-editor-canvas',
        BB_FRONT_CSS,
        array(),
        BB_VERSION
    );
}
add_action( 'enqueue_block_assets', 'bb_enqueue_editor_canvas_styles' );

/**
 * Warn when no editor stylesheet is configured.
 *
 * Block previews still work without one, but they render unstyled, which
 * is easy to mistake for a broken block.
 *
 * @return void
 */
function bb_editor_styles_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( defined( 'BB_FRONT_CSS' ) && '' !== BB_FRONT_CSS ) {
        return;
    }

    printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        esc_html__(
            'BlockBridge: no editor stylesheet is configured. Define BB_FRONT_CSS in wp-config.php so block previews match the front end.',
            'blockbridge'
        )
    );
}
add_action( 'admin_notices', 'bb_editor_styles_admin_notice' );
