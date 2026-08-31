<?php
/**
 * REST API layer.
 *
 * Exposes parsed blocks over custom REST endpoints. Only loaded when
 * BB_ENABLE_REST is on, so classic projects carry no unused routes.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the plugin's REST routes.
 *
 * @return void
 */
function bb_register_rest_routes() {
    register_rest_route(
        'blockbridge/v1',
        '/blocks/(?P<id>\d+)',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'bb_rest_get_blocks_by_id',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'blockbridge/v1',
        '/blocks/(?P<type>[a-z0-9_-]+)/(?P<slug>[a-z0-9_-]+)',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'bb_rest_get_blocks_by_slug',
            'permission_callback' => '__return_true',
        )
    );
}
add_action( 'rest_api_init', 'bb_register_rest_routes' );

/**
 * Return parsed blocks for a post looked up by ID.
 *
 * @param WP_REST_Request $request Incoming request.
 * @return WP_REST_Response|WP_Error Response, or an error when not found.
 */
function bb_rest_get_blocks_by_id( $request ) {
    $post = get_post( (int) $request['id'] );

    return bb_rest_build_response( $post );
}

/**
 * Return parsed blocks for a post looked up by post type and slug.
 *
 * @param WP_REST_Request $request Incoming request.
 * @return WP_REST_Response|WP_Error Response, or an error when not found.
 */
function bb_rest_get_blocks_by_slug( $request ) {
    $posts = get_posts(
        array(
            'name'           => $request['slug'],
            'post_type'      => $request['type'],
            'post_status'    => 'publish',
            'posts_per_page' => 1,
        )
    );

    $post = ! empty( $posts ) ? $posts[0] : null;

    return bb_rest_build_response( $post );
}

/**
 * Build the response for a post, or an error when it is unavailable.
 *
 * Only published posts are exposed. Drafts and private posts are treated
 * as not found so unpublished content never leaks.
 *
 * @param WP_Post|null $post The post to serve.
 * @return WP_REST_Response|WP_Error Response or error.
 */
function bb_rest_build_response( $post ) {
    if ( ! $post || 'publish' !== $post->post_status ) {
        return new WP_Error(
            'bb_not_found',
            __( 'No published post was found for this request.', 'blockbridge' ),
            array( 'status' => 404 )
        );
    }

    return rest_ensure_response(
        array(
            'id'     => $post->ID,
            'slug'   => $post->post_name,
            'title'  => get_the_title( $post ),
            'type'   => $post->post_type,
            'blocks' => bb_parse_post_blocks( $post->ID ),
        )
    );
}