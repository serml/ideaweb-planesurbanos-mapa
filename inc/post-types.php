<?php
/**
 * Register Custom Post Types: Proyectos Urbanos and Artículos Urbanos
 *
 * @package Neve_Child
 */

defined('ABSPATH') || exit;

/**
 * Map legacy status values onto the three current project states.
 *
 * @param string $status Raw status value.
 * @return string One of: en_curso, terminado, pendiente.
 */
function mapa_normalize_status($status) {
    $alias = [
        'riesgo'    => 'en_curso',
        'alerta'    => 'en_curso',
        'estudio'   => 'en_curso',
        'activo'    => 'en_curso',
        'en_curso'  => 'en_curso',
        'terminado' => 'terminado',
        'pendiente' => 'pendiente',
    ];

    return $alias[$status] ?? 'en_curso';
}

/**
 * Register the Categoria Proyecto taxonomy
 */
add_action('init', function () {
    register_taxonomy('categoria_proyecto', ['proyecto_urbano'], [
        'labels' => [
        'name'          => 'Categorías de Proyecto',
            'singular_name' => 'Categoría de Proyecto',
            'search_items'  => 'Buscar categorías',
            'all_items'     => 'Todas las categorías',
            'edit_item'     => 'Editar categoría',
            'add_new_item'  => 'Añadir nueva categoría',
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'categoria-proyecto'],
    ]);

    // Categories used by project markers, including urban interventions.
});

/**
 * Register the Proyecto Urbano post type
 */
add_action('init', function () {
    register_post_type('proyecto_urbano', [
        'labels' => [
            'name'               => 'Proyectos Urbanos',
            'singular_name'      => 'Proyecto Urbano',
            'add_new'            => 'Añadir nuevo',
            'add_new_item'       => 'Añadir nuevo proyecto',
            'edit_item'          => 'Editar proyecto',
            'new_item'           => 'Nuevo proyecto',
            'view_item'          => 'Ver proyecto',
            'search_items'       => 'Buscar proyectos',
            'not_found'          => 'No se encontraron proyectos',
            'not_found_in_trash' => 'No se encontraron proyectos en la papelera',
        ],
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-location-alt',
        'supports'     => ['title', 'editor', 'thumbnail'],
        'rewrite'      => ['slug' => 'proyectos'],
    ]);
});

/**
 * Expose map metadata on the public REST API used by the frontend map.
 */
add_action('init', function () {
    $project_meta = [
        '_proyecto_code'    => 'string',
        '_proyecto_emoji'   => 'string',
        '_proyecto_district'=> 'string',
        '_proyecto_status'  => 'string',
        '_proyecto_lat'     => 'string',
        '_proyecto_lng'     => 'string',
        '_proyecto_area'    => 'string',
    ];

    foreach ($project_meta as $key => $type) {
        register_post_meta('proyecto_urbano', $key, [
            'type'              => $type,
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    $article_meta = [
        '_articulo_emoji'        => 'string',
        '_articulo_reading_time' => 'string',
        '_articulo_pull_quote'   => 'string',
        '_articulo_lat'          => 'string',
        '_articulo_lng'          => 'string',
        '_articulo_zoom'         => 'string',
    ];

    foreach ($article_meta as $key => $type) {
        register_post_meta('articulo_urbano', $key, [
            'type'              => $type,
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
});

/**
 * Register the Articulo Urbano post type
 */
add_action('init', function () {
    register_post_type('articulo_urbano', [
        'labels' => [
            'name'               => 'Artículos Urbanos',
            'singular_name'      => 'Artículo Urbano',
            'add_new'            => 'Añadir nuevo',
            'add_new_item'       => 'Añadir nuevo artículo',
            'edit_item'          => 'Editar artículo',
            'new_item'           => 'Nuevo artículo',
            'view_item'          => 'Ver artículo',
            'search_items'       => 'Buscar artículos',
            'not_found'          => 'No se encontraron artículos',
            'not_found_in_trash' => 'No se encontraron artículos en la papelera',
        ],
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-media-text',
        'supports'     => ['title', 'editor', 'thumbnail'],
        'rewrite'      => ['slug' => 'articulos-urbanos'],
    ]);
});

/**
 * Register the Categoria Articulo taxonomy
 */
add_action('init', function () {
    register_taxonomy('categoria_articulo', ['articulo_urbano'], [
        'labels' => [
            'name'          => 'Categorías de Artículo',
            'singular_name' => 'Categoría de Artículo',
            'search_items'  => 'Buscar categorías',
            'all_items'     => 'Todas las categorías',
            'edit_item'     => 'Editar categoría',
            'add_new_item'  => 'Añadir nueva categoría',
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'categoria-articulo'],
    ]);
});

/**
 * Flush rewrite rules on theme activation
 */
add_action('after_switch_theme', function () {
    flush_rewrite_rules();
});

/**
 * Public, normalized data endpoint for Leaflet. This avoids protected post-meta
 * fields being omitted from anonymous WP REST responses.
 */
add_action('rest_api_init', function () {
    register_rest_route('ideaweb/v1', '/map-projects', [
        'methods'             => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback'            => function () {
            $posts = get_posts([
                'post_type'      => 'proyecto_urbano',
                'post_status'    => 'publish',
                'posts_per_page' => 200,
                'orderby'        => 'menu_order date',
                'order'          => 'ASC',
            ]);

            $projects = [];
            foreach ($posts as $post) {
                $terms = wp_get_post_terms($post->ID, 'categoria_proyecto');
                $category = (!is_wp_error($terms) && !empty($terms)) ? $terms[0]->slug : 'paisaje';
                $title = get_the_title($post);
                $known_codes = [
                    'Reforma Alonso Martínez' => 'O1',
                    'Densificación de Arroyo del Fresno' => 'U1',
                ];
                $code = (string) (get_post_meta($post->ID, '_proyecto_code', true) ?: ($known_codes[$title] ?? ''));
                $category_prefixes = [
                    'U' => 'urbano',
                    'O' => 'objeto',
                    'G' => 'general',
                ];
                $prefix = strtoupper(substr($code, 0, 1));
                if (isset($category_prefixes[$prefix])) {
                    $category = $category_prefixes[$prefix];
                }

                $lat_raw = get_post_meta($post->ID, '_proyecto_lat', true);
                $lng_raw = get_post_meta($post->ID, '_proyecto_lng', true);
                $lat = is_numeric($lat_raw) ? (float) $lat_raw : null;
                $lng = is_numeric($lng_raw) ? (float) $lng_raw : null;
                $latlng = ($lat !== null && $lng !== null && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180)
                    ? [$lat, $lng]
                    : null;

                $area = json_decode((string) get_post_meta($post->ID, '_proyecto_area', true), true);
                if (!is_array($area)) {
                    $area = [];
                }
                $area = array_values(array_filter($area, function ($point) {
                    return is_array($point) && count($point) >= 2 &&
                        is_numeric($point[0]) && is_numeric($point[1]) &&
                        (float) $point[0] >= -90 && (float) $point[0] <= 90 &&
                        (float) $point[1] >= -180 && (float) $point[1] <= 180;
                }));

                $description = $post->post_excerpt;
                if ($description === '') {
                    $description = wp_trim_words(wp_strip_all_tags($post->post_content), 45, '…');
                }

                $projects[] = [
                    'id'       => (int) $post->ID,
                    'type'     => $category,
                    'code'     => $code,
                    'emoji'    => (string) (get_post_meta($post->ID, '_proyecto_emoji', true) ?: '📍'),
                    'title'    => $title,
                    'district' => (string) get_post_meta($post->ID, '_proyecto_district', true),
                    'status'   => mapa_normalize_status((string) get_post_meta($post->ID, '_proyecto_status', true)),
                    'desc'     => wp_strip_all_tags($description),
                    'latlng'   => $latlng,
                    'area'     => $area,
                ];
            }

            return rest_ensure_response($projects);
        },
    ]);
});
