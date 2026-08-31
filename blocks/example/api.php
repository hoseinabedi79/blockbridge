<?php
/**
 * Example block API output.
 *
 * OPTIONAL FILE. Delete it when a block needs no custom shaping.
 *
 * Loaded automatically when present next to a block's block.json.
 * Use it to reshape a single block's API data before it is served,
 * without touching the shared parser. The changes apply to every API
 * layer, REST and GraphQL alike.
 *
 * Hook name is bb_block_data_{block-name}, where the block name is the
 * one from block.json without the acf/ prefix. This block is registered
 * as acf/_example, so the hook is bb_block_data__example.
 *
 * Typical uses:
 *   - rename a field for the front end
 *   - add a value that does not come from an ACF field
 *   - pull in data from the post the block belongs to
 *   - remove a field that should stay out of the API
 *
 * Rules that apply to a field type rather than a single block, such as
 * trimming every image down to a URL and alt text, belong in the shared
 * parser instead. Putting them here means repeating them for every block.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shape the Example block's API output.
 *
 * @param array $data    Field name and value pairs.
 * @param array $block   The raw block from parse_blocks().
 * @param int   $post_id Post ID the block belongs to.
 * @return array Reshaped data.
 */
function bb_example_block_data( $data, $block, $post_id ) {
    // Nothing to change yet. Return the data untouched.
    return $data;
}
add_filter( 'bb_block_data_example', 'bb_example_block_data', 10, 3 );