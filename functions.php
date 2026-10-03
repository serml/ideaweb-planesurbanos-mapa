<?php
/**
 * Neve Child Theme functions
 *
 * @package Neve_Child
 */

defined('ABSPATH') || exit;

define('NEVE_CHILD_VERSION', '1.0.15');
define('NEVE_CHILD_DIR', get_stylesheet_directory());
define('NEVE_CHILD_URI', get_stylesheet_directory_uri());

/**
 * Enqueue parent and child theme styles
 */
add_action('wp_enqueue_scripts', function () {
    // Parent theme style (already loaded by Neve, but ensure it)
    wp_enqueue_style('neve-parent', get_template_directory_uri() . '/style-main-new.min.css', [], NEVE_CHILD_VERSION);

    // Child theme style
    wp_enqueue_style('neve-child', get_stylesheet_uri(), ['neve-parent'], NEVE_CHILD_VERSION);
});

/**
 * Register custom post types and taxonomies
 */
require_once NEVE_CHILD_DIR . '/inc/post-types.php';

/**
 * Register custom meta boxes
 */
require_once NEVE_CHILD_DIR . '/inc/meta-boxes.php';

/**
 * Enqueue map assets only on the map page template
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_page_template('page-templates/template-mapa-urbanos.php')) {
        return;
    }

    // Google Fonts
    wp_enqueue_style('google-fonts-fraunces', 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Outfit:wght@400;500;600&display=swap', [], null);

    // Leaflet from CDN
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

    // Map theme assets
    wp_enqueue_style('mapa-urbanos-css', NEVE_CHILD_URI . '/assets/css/mapa-urbanos.css', ['leaflet-css', 'google-fonts-fraunces'], NEVE_CHILD_VERSION);
    wp_enqueue_script('mapa-urbanos-js', NEVE_CHILD_URI . '/assets/js/mapa-urbanos.js', ['leaflet-js'], NEVE_CHILD_VERSION, true);

    // Localize config for JS
    wp_localize_script('mapa-urbanos-js', 'mapaConfig', [
        'restUrl' => rest_url('wp/v2/'),
        'projectsUrl' => rest_url('ideaweb/v1/map-projects'),
        'nonce'   => wp_create_nonce('wp_rest'),
        'center'  => [40.4168, -3.7038],
        'zoom'    => 12,
    ]);
});

/**
 * Add body class for map template
 */
add_filter('body_class', function ($classes) {
    if (is_page_template('page-templates/template-mapa-urbanos.php')) {
        $classes[] = 'mapa-urbanos-page';
    }
    return $classes;
});
