<?php
/**
 * Project-specific configuration.
 *
 * This is the only file that should be edited per project.
 * Do not overwrite it when updating the plugin on an existing site.
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
 * Classic projects: point to the active theme's compiled stylesheet.
 * Headless projects: point to a build handed over by the front-end team
 * and placed in this plugin's assets directory.
 *
 * Set to an empty string to disable editor styles entirely.
 */
define( 'BB_FRONT_CSS', get_stylesheet_directory_uri() . '/dist/app.css' );

/**
 * Slug for the block category. Lowercase letters and dashes only.
 */
define( 'BB_CATEGORY_SLUG', 'blockbridge' );

/**
 * Human-readable block category label shown in the editor inserter.
 */
define( 'BB_CATEGORY_TITLE', 'Site Blocks' );
