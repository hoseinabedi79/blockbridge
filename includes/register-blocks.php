<?php
/**
 * Automatic block registration.
 *
 * Scans the blocks directory and registers every subdirectory that
 * contains a block.json file. Adding a block means adding a folder, with
 * no changes to this file.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Absolute path to the blocks directory, without a trailing slash.
 */
define( 'BB_BLOCKS_PATH', untrailingslashit( BB_PATH ) . '/blocks' );

/**
 * Register every block found in the blocks directory.
 *
 * Runs on init because block types must be registered before the editor
 * requests them.
 *
 * @return void
 */
function bb_register_blocks() {
    if ( ! is_dir( BB_BLOCKS_PATH ) ) {
        return;
    }

    $directories = glob( BB_BLOCKS_PATH . '/*', GLOB_ONLYDIR );

    if ( empty( $directories ) ) {
        return;
    }
    foreach ( $directories as $directory ) {
        // A directory without a block.json is not a block. Skip it silently.
        if ( ! file_exists( $directory . '/block.json' ) ) {
            continue;
        }

        register_block_type( $directory );
    }
}
add_action( 'init', 'bb_register_blocks' );
