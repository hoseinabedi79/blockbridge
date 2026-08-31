<?php
/**
 * Example block GraphQL type.
 *
 * OPTIONAL FILE. Only needed when the site serves blocks over GraphQL.
 * Without it the block still renders and still appears in REST, but it
 * cannot be queried by its own fields in GraphQL.
 *
 * The type name must match what the parser derives from the block name:
 * acf/_example becomes BbBlockExample. Field names here must match the
 * keys the parser produces, including any renaming done in api.php.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Example block's GraphQL type.
 *
 * @return void
 */
function bb_register_example_block_graphql_type() {
    if ( ! function_exists( 'register_graphql_object_type' ) ) {
        return;
    }

    register_graphql_object_type(
        'BbBlockExample',
        [
            'description' => __( 'The Example block.', 'blockbridge' ),
            'interfaces'  => [ 'BbBlock' ],
            'fields'      => [
                'heading' => [
                    'type'    => 'String',
                    'resolve' => function ( $block ) {
                        return $block['data']['heading'] ?? null;
                    },
                ],
                'text'    => [
                    'type'    => 'String',
                    'resolve' => function ( $block ) {
                        return $block['data']['text'] ?? null;
                    },
                ],
                'image'   => [
                    'type'    => 'BbImage',
                    'resolve' => function ( $block ) {
                        return $block['data']['image'] ?? null;
                    },
                ],
            ],
        ]
    );
}
add_action( 'graphql_register_types', 'bb_register_example_block_graphql_type' );
