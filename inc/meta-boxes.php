<?php
/**
 * Custom meta boxes for Proyectos and Artículos Urbanos
 *
 * @package Neve_Child
 */

defined('ABSPATH') || exit;

/**
 * Add meta boxes
 */
add_action('add_meta_boxes', function () {
    // Proyecto Urbano meta box
    add_meta_box(
        'proyecto_urbano_details',
        'Datos del Proyecto en el Mapa',
        'render_proyecto_meta_box',
        'proyecto_urbano',
        'normal',
        'high'
    );

    // Articulo Urbano meta box
    add_meta_box(
        'articulo_urbano_details',
        'Datos del Artículo',
        'render_articulo_meta_box',
        'articulo_urbano',
        'normal',
        'high'
    );
});

/**
 * Render the Proyecto Urbano meta box
 */
function render_proyecto_meta_box($post) {
    wp_nonce_field('proyecto_urbano_meta', 'proyecto_urbano_nonce');

    $emoji       = get_post_meta($post->ID, '_proyecto_emoji', true);
    $code        = get_post_meta($post->ID, '_proyecto_code', true);
    $district    = get_post_meta($post->ID, '_proyecto_district', true);
    $status      = get_post_meta($post->ID, '_proyecto_status', true);
    $status      = mapa_normalize_status($status);
    $lat         = get_post_meta($post->ID, '_proyecto_lat', true);
    $lng         = get_post_meta($post->ID, '_proyecto_lng', true);
    $area        = get_post_meta($post->ID, '_proyecto_area', true);
    ?>
    <style>.proyecto-meta-table td{padding:6px 8px;vertical-align:top;}.proyecto-meta-table label{font-weight:600;display:block;margin-bottom:4px;font-size:13px;}.proyecto-meta-table input,.proyecto-meta-table textarea,.proyecto-meta-table select{width:100%;padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px;}.proyecto-meta-table textarea{height:80px;}</style>
    <table class="proyecto-meta-table" style="width:100%">
        <tr>
            <td style="width:50%">
                <label for="proyecto_emoji">Emoji del marcador</label>
                <input type="text" id="proyecto_emoji" name="proyecto_emoji" value="<?php echo esc_attr($emoji); ?>" placeholder="e.g. 🏞️">
            </td>
            <td>
                <label for="proyecto_code">Código de clasificación</label>
                <input type="text" id="proyecto_code" name="proyecto_code" value="<?php echo esc_attr($code); ?>" placeholder="e.g. O1">
            </td>
        </tr>
        <tr>
            <td>
                <label for="proyecto_district">Distrito / Ámbito</label>
                <input type="text" id="proyecto_district" name="proyecto_district" value="<?php echo esc_attr($district); ?>" placeholder="e.g. La Latina · Centro">
            </td>
            <td></td>
        </tr>
        <tr>
            <td>
                <label for="proyecto_status">Estado</label>
                <select id="proyecto_status" name="proyecto_status">
                    <option value="en_curso" <?php selected($status, 'en_curso'); ?>>En curso</option>
                    <option value="terminado" <?php selected($status, 'terminado'); ?>>Terminado</option>
                    <option value="pendiente" <?php selected($status, 'pendiente'); ?>>Pendiente</option>
                </select>
            </td>
            <td>
                <label>Categoría (usar taxonomía)</label>
                <p style="font-size:12px;color:#666;margin:4px 0 0">Asignar en el panel de la derecha</p>
            </td>
        </tr>
        <tr>
            <td>
                <label for="proyecto_lat">Latitud</label>
                <input type="text" id="proyecto_lat" name="proyecto_lat" value="<?php echo esc_attr($lat); ?>" placeholder="e.g. 40.4108">
            </td>
            <td>
                <label for="proyecto_lng">Longitud</label>
                <input type="text" id="proyecto_lng" name="proyecto_lng" value="<?php echo esc_attr($lng); ?>" placeholder="e.g. -3.7148">
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <label for="proyecto_area">Coordenadas del polígono (JSON)</label>
                <textarea id="proyecto_area" name="proyecto_area" placeholder='[[40.4128,-3.7118],[40.4124,-3.7114],...]'><?php echo esc_textarea($area); ?></textarea>
                <p style="font-size:11px;color:#888;margin:4px 0 0">Formato: array de pares [lat, lng]. Ejemplo: [[40.4128,-3.7118],[40.4124,-3.7114]]</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render the Articulo Urbano meta box
 */
function render_articulo_meta_box($post) {
    wp_nonce_field('articulo_urbano_meta', 'articulo_urbano_nonce');

    $emoji       = get_post_meta($post->ID, '_articulo_emoji', true);
    $reading_time = get_post_meta($post->ID, '_articulo_reading_time', true);
    $pull_quote  = get_post_meta($post->ID, '_articulo_pull_quote', true);
    $lat         = get_post_meta($post->ID, '_articulo_lat', true);
    $lng         = get_post_meta($post->ID, '_articulo_lng', true);
    $zoom        = get_post_meta($post->ID, '_articulo_zoom', true);
    ?>
    <style>.articulo-meta-table td{padding:6px 8px;vertical-align:top;}.articulo-meta-table label{font-weight:600;display:block;margin-bottom:4px;font-size:13px;}.articulo-meta-table input,.articulo-meta-table textarea{width:100%;padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px;}.articulo-meta-table textarea{height:80px;}</style>
    <table class="articulo-meta-table" style="width:100%">
        <tr>
            <td style="width:50%">
                <label for="articulo_emoji">Emoji del héroe</label>
                <input type="text" id="articulo_emoji" name="articulo_emoji" value="<?php echo esc_attr($emoji); ?>" placeholder="e.g. 🏛️">
            </td>
            <td>
                <label for="articulo_reading_time">Tiempo de lectura (min)</label>
                <input type="number" id="articulo_reading_time" name="articulo_reading_time" value="<?php echo esc_attr($reading_time); ?>" min="1" max="60" placeholder="5">
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <label for="articulo_pull_quote">Frase destacada (pull quote)</label>
                <textarea id="articulo_pull_quote" name="articulo_pull_quote" rows="3" placeholder="Frase que aparecerá como cita destacada"><?php echo esc_textarea($pull_quote); ?></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <label for="articulo_lat">Latitud (ubicación en mapa)</label>
                <input type="text" id="articulo_lat" name="articulo_lat" value="<?php echo esc_attr($lat); ?>" placeholder="e.g. 40.4145">
            </td>
            <td>
                <label for="articulo_lng">Longitud</label>
                <input type="text" id="articulo_lng" name="articulo_lng" value="<?php echo esc_attr($lng); ?>" placeholder="e.g. -3.7078">
            </td>
        </tr>
        <tr>
            <td>
                <label for="articulo_zoom">Zoom del mapa miniatura</label>
                <input type="number" id="articulo_zoom" name="articulo_zoom" value="<?php echo esc_attr($zoom); ?>" min="9" max="19" placeholder="16">
            </td>
            <td></td>
        </tr>
    </table>
    <?php
}

/**
 * Save Proyecto Urbano meta
 */
add_action('save_post_proyecto_urbano', function ($post_id) {
    if (!isset($_POST['proyecto_urbano_nonce']) || !wp_verify_nonce($_POST['proyecto_urbano_nonce'], 'proyecto_urbano_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = ['proyecto_emoji', 'proyecto_code', 'proyecto_district', 'proyecto_status', 'proyecto_lat', 'proyecto_lng', 'proyecto_area'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field(wp_unslash($_POST[$field]));
            if ($field === 'proyecto_status') {
                $value = mapa_normalize_status($value);
            }
            update_post_meta($post_id, '_' . $field, $value);
        }
    }
});

/**
 * Save Articulo Urbano meta
 */
add_action('save_post_articulo_urbano', function ($post_id) {
    if (!isset($_POST['articulo_urbano_nonce']) || !wp_verify_nonce($_POST['articulo_urbano_nonce'], 'articulo_urbano_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = ['articulo_emoji', 'articulo_reading_time', 'articulo_pull_quote', 'articulo_lat', 'articulo_lng', 'articulo_zoom'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }
});
