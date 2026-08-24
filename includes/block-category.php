<?php
/**
 * Custom block category.
 *
 * Groups this plugin's blocks under a single category in the editor
 * inserter so editors do not have to hunt for them among core blocks.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the plugin's block category.
 *
 * Prepended so it appears above the core categories, which is where
 * editors expect the site's own blocks to be.
 *
 * @param array $categories Existing block categories.
 * @return array Filtered block categories.
 */
function bb_register_block_category( $categories ) {
    array_unshift(
        $categories,
        array(
            'slug'  => BB_CATEGORY_SLUG,
            'title' => BB_CATEGORY_TITLE,
            'icon'  => null,
        )
    );

    return $categories;
}
add_filter( 'block_categories_all', 'bb_register_block_category' );