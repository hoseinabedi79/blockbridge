<?php
/**
 * Block parser.
 *
 * Turns post content into a clean array of blocks, ready to be served by
 * any API layer. ACF blocks are returned as structured field data, core
 * blocks as rendered HTML.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parse a post's content into an array of blocks.
 *
 * @param int $post_id Post ID.
 * @return array List of parsed blocks.
 */
function bb_parse_post_blocks( $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post || empty( $post->post_content ) ) {
        return array();
    }

    $blocks = parse_blocks( $post->post_content );
    $output = array();

    foreach ( $blocks as $block ) {
        $parsed = bb_parse_single_block( $block, $post_id );

        if ( null !== $parsed ) {
            $output[] = $parsed;
        }
    }

    return $output;
}

/**
 * Parse a single block into its API representation.
 *
 * Returns null for blocks that carry no meaningful content, such as the
 * empty separators parse_blocks() inserts between real blocks.
 *
 * @param array $block   A block from parse_blocks().
 * @param int   $post_id Post ID the block belongs to.
 * @return array|null Parsed block, or null when the block should be skipped.
 */
function bb_parse_single_block( $block, $post_id ) {
    // parse_blocks() returns spacer entries with no block name. Skip them.
    if ( empty( $block['blockName'] ) ) {
        return null;
    }

    if ( bb_is_acf_block( $block['blockName'] ) ) {
        return bb_parse_acf_block( $block, $post_id );
    }

    return bb_parse_core_block( $block );
}

/**
 * Determine whether a block name belongs to an ACF block.
 *
 * @param string $block_name Full block name, such as acf/example.
 * @return bool True for ACF blocks.
 */
function bb_is_acf_block( $block_name ) {
    return 0 === strpos( $block_name, 'acf/' );
}

/**
 * Build the API representation of an ACF block.
 *
 * The finished data passes through two filters so individual blocks can
 * reshape their own output without touching the parser. Blocks hook the
 * name-specific filter from their own api.php file.
 *
 * @param array $block   A block from parse_blocks().
 * @param int   $post_id Post ID the block belongs to.
 * @return array Parsed ACF block.
 */
function bb_parse_acf_block( $block, $post_id ) {
    $name = str_replace( 'acf/', '', $block['blockName'] );
    $data = bb_get_acf_block_data( $block, $post_id );

    /**
     * Filter the parsed data of a single block by name.
     *
     * @param array $data    Field name and value pairs.
     * @param array $block   The raw block from parse_blocks().
     * @param int   $post_id Post ID the block belongs to.
     */
    $data = apply_filters( "bb_block_data_{$name}", $data, $block, $post_id );

    /**
     * Filter the parsed data of every block.
     *
     * @param array  $data    Field name and value pairs.
     * @param string $name    Block name without the acf/ prefix.
     * @param array  $block   The raw block from parse_blocks().
     * @param int    $post_id Post ID the block belongs to.
     */
    $data = apply_filters( 'bb_block_data', $data, $name, $block, $post_id );

    return array(
        'name' => $name,
        'type' => 'acf',
        'data' => $data,
    );
}

/**
 * Build the API representation of a core block.
 *
 * Core blocks carry no structured field data, so their rendered HTML is
 * returned for the front end to output as-is.
 *
 * @param array $block A block from parse_blocks().
 * @return array|null Parsed core block, or null when it renders to nothing.
 */
function bb_parse_core_block( $block ) {
    $html = trim( render_block( $block ) );

    if ( '' === $html ) {
        return null;
    }

    return array(
        'name' => $block['blockName'],
        'type' => 'core',
        'html' => $html,
    );
}

/**
 * Read every ACF field value belonging to a single block instance.
 *
 * ACF resolves field values against the current block context, so the
 * block is activated first, its fields read, and the context reset
 * afterwards to avoid leaking into the next block.
 *
 * @param array $block   A block from parse_blocks().
 * @param int   $post_id Post ID the block belongs to.
 * @return array Field name and value pairs.
 */
function bb_get_acf_block_data( $block, $post_id ) {
    if ( ! function_exists( 'acf_setup_meta' ) ) {
        return array();
    }

    $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();

    // ACF stores block field values under the data key of the block attributes.
    $fields = isset( $attrs['data'] ) ? $attrs['data'] : array();

    if ( empty( $fields ) ) {
        return array();
    }

    // Each block instance needs a unique id so ACF can scope its lookups.
    $block_id = isset( $attrs['id'] ) ? $attrs['id'] : uniqid( 'block_' );

    acf_setup_meta( $fields, $block_id, true );

    $data = array();

    foreach ( $fields as $key => $value ) {
        // ACF stores a hidden reference key alongside every field. Skip those.
        if ( 0 === strpos( $key, '_' ) ) {
            continue;
        }

        $data[ $key ] = bb_slim_field_value( get_field( $key, $block_id ) );
    }

    acf_reset_meta( $block_id );

    return $data;
}

/**
 * Reduce ACF image arrays to the keys the front end actually needs.
 *
 * ACF returns around thirty keys per image. Everything except the URL and
 * alt text is dropped to keep API responses small.
 *
 * @param mixed $value A field value of any type.
 * @return mixed The value with image arrays reduced.
 */
function bb_slim_field_value( $value ) {
    if ( ! is_array( $value ) ) {
        return $value;
    }

    if ( bb_is_image_array( $value ) ) {
        return array(
            'url' => $value['url'],
            'alt' => isset( $value['alt'] ) ? $value['alt'] : '',
        );
    }

    // Walk nested arrays so repeater rows and groups are covered too.
    foreach ( $value as $key => $item ) {
        $value[ $key ] = bb_slim_field_value( $item );
    }

    return $value;
}

/**
 * Determine whether an array is an ACF image array.
 *
 * @param array $value The array to inspect.
 * @return bool True when the array describes an image attachment.
 */
function bb_is_image_array( $value ) {
    return isset( $value['url'], $value['mime_type'] ) && isset( $value['type'] ) && 'image' === $value['type'];
}