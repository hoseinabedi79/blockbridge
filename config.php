<?php
/**
 * Project-specific configuration.
 *
 * Every value can be overridden from wp-config.php. Define the matching
 * constant there and this file will defer to it. Use that for anything
 * environment-specific or sensitive, since this file is committed to
 * version control.
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Front-end stylesheet loaded into the editor canvas so block previews
 * match the live site.
 *
 * Define this in wp-config.php with an absolute URL. Left empty, block
 * previews still work but are rendered without front-end styles.
 */
if ( ! defined( 'BB_FRONT_CSS' ) ) {
    define( 'BB_FRONT_CSS', '' );
}

/**
 * Slug for the block category. Lowercase letters and dashes only.
 */
if ( ! defined( 'BB_CATEGORY_SLUG' ) ) {
    define( 'BB_CATEGORY_SLUG', 'blockbridge' );
}

/**
 * Human-readable block category label shown in the editor inserter.
 */
if ( ! defined( 'BB_CATEGORY_TITLE' ) ) {
    define( 'BB_CATEGORY_TITLE', 'Site Blocks' );
}

/**
 * Enable the REST API layer. Headless projects only.
 */
if ( ! defined( 'BB_ENABLE_REST' ) ) {
    define( 'BB_ENABLE_REST', false );
}

/**
 * Enable the GraphQL API layer. Headless projects only.
 */
if ( ! defined( 'BB_ENABLE_GRAPHQL' ) ) {
    define( 'BB_ENABLE_GRAPHQL', false );
}