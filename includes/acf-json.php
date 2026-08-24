<?php
/**
 * ACF Local JSON integration.
 *
 * Keeps block field groups in version control alongside the blocks they
 * belong to, so a block and its fields travel together.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Absolute path to this plugin's Local JSON directory, without a trailing slash.
 */
define( 'BB_ACF_JSON_PATH', untrailingslashit( BB_PATH ) . '/acf-json' );

/**
 * Register the plugin's Local JSON directory as a load location.
 *
 * ACF reads from every registered location, so this is additive and leaves
 * field groups stored elsewhere untouched.
 *
 * @param array $paths Existing load paths.
 * @return array Filtered load paths.
 */
function bb_acf_json_load_point( $paths ) {
    $paths[] = BB_ACF_JSON_PATH;

    return $paths;
}
add_filter( 'acf/settings/load_json', 'bb_acf_json_load_point' );

/**
 * Route block field groups to this plugin's Local JSON directory.
 *
 * ACF passes every candidate save path for the item being saved and expects
 * an array in return. Field groups located on an ACF block are written here;
 * everything else keeps the paths ACF already resolved.
 *
 * @param array $paths Candidate save paths.
 * @param array $post  The item being saved.
 * @return array Save paths to use.
 */
function bb_acf_json_save_paths( $paths, $post ) {
    if ( ! bb_is_block_field_group( $post ) ) {
        return $paths;
    }

    // ACF does not create the directory, so make sure it exists first.
    if ( ! is_dir( BB_ACF_JSON_PATH ) ) {
        wp_mkdir_p( BB_ACF_JSON_PATH );
    }

    return array( BB_ACF_JSON_PATH );
}
add_filter( 'acf/json/save_paths', 'bb_acf_json_save_paths', 10, 2 );

/**
 * Determine whether an item is a field group assigned to an ACF block.
 *
 * Location rules are stored as an array of OR groups, each holding an array
 * of AND rules. A single block rule anywhere is enough to treat the group as
 * block-owned. Items without location rules, such as post types and options
 * pages, never match.
 *
 * @param array $post The item being saved.
 * @return bool True when any location rule targets a block.
 */
function bb_is_block_field_group( $post ) {
    if ( empty( $post['location'] ) || ! is_array( $post['location'] ) ) {
        return false;
    }

    foreach ( $post['location'] as $group ) {
        if ( ! is_array( $group ) ) {
            continue;
        }

        foreach ( $group as $rule ) {
            if ( isset( $rule['param'] ) && 'block' === $rule['param'] ) {
                return true;
            }
        }
    }

    return false;
}