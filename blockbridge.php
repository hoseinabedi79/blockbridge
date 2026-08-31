<?php
/**
 * Plugin Name:       BlockBridge
 * Description:       A lightweight engine for building ACF-powered custom blocks.
 * Version:           1.0.0
 * Author:            Hossein Abedi
 * Text Domain:       blockbridge
 *
 * @package BlockBridge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Absolute filesystem path to the plugin directory, with trailing slash.
 * Use for including PHP files and reading files from disk.
 */
define( 'BB_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Public URL to the plugin directory, with trailing slash.
 * Use for enqueueing assets such as stylesheets.
 */
define( 'BB_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin version. Used as the asset cache-busting version string.
 */
define( 'BB_VERSION', '1.0.0' );

// Project-specific settings. This is the only file that changes per project.
require_once BB_PATH . 'config.php';

// Core engine. These files stay the same across every project.
require_once BB_PATH . 'includes/acf-json.php';
require_once BB_PATH . 'includes/block-category.php';
require_once BB_PATH . 'includes/register-blocks.php';
require_once BB_PATH . 'includes/editor-styles.php';
require_once BB_PATH . 'includes/block-parser.php';
if ( BB_ENABLE_REST ) {
    require_once BB_PATH . 'includes/api-rest.php';
}
if ( BB_ENABLE_GRAPHQL ) {
    require_once BB_PATH . 'includes/api-graphql.php';
}