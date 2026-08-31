<?php
/**
 * GraphQL API layer.
 *
 * Registers the shared block interface and attaches a blocks field to
 * every post type that supports the editor. Individual blocks register
 * their own object types from their graphql.php file.
 *
 * Only loaded when BB_ENABLE_GRAPHQL is on.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the shared GraphQL types and fields.
 *
 * Bails out when WPGraphQL is not active so the plugin stays usable on
 * sites that do not run it.
 *
 * @return void
 */
function bb_register_graphql_types() {
    if ( ! function_exists( 'register_graphql_interface_type' ) ) {
        return;
    }

    // The shape every block shares. Individual blocks extend it.
    register_graphql_interface_type(
        'BbBlock',
        [
            'description' => __( 'A block parsed from post content.', 'blockbridge' ),
            'fields'      => [
                'name' => [
                    'type'        => 'String',
                    'description' => __( 'Block name without the acf/ prefix.', 'blockbridge' ),
                ],
                'type' => [
                    'type'        => 'String',
                    'description' => __( 'Either acf or core.', 'blockbridge' ),
                ],
            ],
            'resolveType' => 'bb_resolve_block_type',
        ]
    );

    // Core blocks carry rendered HTML rather than structured fields.
    register_graphql_object_type(
        'BbBlockCore',
        [
            'description' => __( 'A WordPress core block, returned as rendered HTML.', 'blockbridge' ),
            'interfaces'  => [ 'BbBlock' ],
            'fields'      => [
                'html' => [
                    'type'        => 'String',
                    'description' => __( 'The block rendered to HTML.', 'blockbridge' ),
                ],
            ],
        ]
    );

    // Shared shape for every image field across all blocks.
    register_graphql_object_type(
        'BbImage',
        [
            'description' => __( 'An image field value.', 'blockbridge' ),
            'fields'      => [
                'url' => [ 'type' => 'String' ],
                'alt' => [ 'type' => 'String' ],
            ],
        ]
    );

    bb_attach_blocks_field();
}
add_action( 'graphql_register_types', 'bb_register_graphql_types' );

/**
 * Attach the blocks field to every post type exposed in GraphQL.
 *
 * @return void
 */
function bb_attach_blocks_field() {
    $post_types = get_post_types(
        [
            'show_in_graphql' => true,
        ],
        'objects'
    );

    foreach ( $post_types as $post_type ) {
        if ( empty( $post_type->graphql_single_name ) ) {
            continue;
        }

        register_graphql_field(
            $post_type->graphql_single_name,
            'blocks',
            [
                'type'        => [ 'list_of' => 'BbBlock' ],
                'description' => __( 'Blocks parsed from this post content.', 'blockbridge' ),
                'resolve'     => 'bb_resolve_blocks_field',
            ]
        );
    }
}

/**
 * Resolve the blocks field for a post.
 *
 * @param object $source The post being queried.
 * @return array Parsed blocks.
 */
function bb_resolve_blocks_field( $source ) {
    if ( empty( $source->databaseId ) ) {
        return [];
    }

    return bb_parse_post_blocks( $source->databaseId );
}

/**
 * Decide which GraphQL object type a parsed block maps to.
 *
 * ACF blocks map to a type named after the block, so acf/example becomes
 * BbBlockExample. Blocks without a registered type fall back to the core
 * type so an unmapped block never breaks the query.
 *
 * @param array $block A parsed block from the parser.
 * @return string The GraphQL type name.
 */
function bb_resolve_block_type( $block ) {
    if ( ! isset( $block['type'] ) || 'acf' !== $block['type'] ) {
        return 'BbBlockCore';
    }

    $type_name = bb_block_name_to_graphql_type( $block['name'] );

    // Fall back when the block has no graphql.php of its own.
    if ( ! bb_graphql_type_exists( $type_name ) ) {
        return 'BbBlockCore';
    }

    return $type_name;
}

/**
 * Convert a block name into its GraphQL type name.
 *
 * Example: hero-banner becomes BbBlockHeroBanner.
 *
 * @param string $name Block name without the acf/ prefix.
 * @return string GraphQL type name.
 */
function bb_block_name_to_graphql_type( $name ) {
    $clean = str_replace( [ '-', '_' ], ' ', $name );
    $clean = ucwords( $clean );
    $clean = str_replace( ' ', '', $clean );

    return 'BbBlock' . $clean;
}

/**
 * Check whether a GraphQL type has been registered.
 *
 * @param string $type_name The type name to look for.
 * @return bool True when the type exists.
 */
function bb_graphql_type_exists( $type_name ) {
    $registered = \WPGraphQL::get_type_registry();

    return null !== $registered->get_type( $type_name );
}
