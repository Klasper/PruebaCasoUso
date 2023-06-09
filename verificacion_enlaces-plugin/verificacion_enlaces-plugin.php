<?php
/*
 * The plugin bootstrap file
 *
 * This file is responsible for starting the plugin using the main plugin class file.
 *
 * @since 0.0.1
 * @package verificacion_enlaces-plugin.php
 *
 * @wordpress-plugin
 * Plugin Name:     Verificacion Enlaces
 * Description:     publicaciones tienen múltiples enlaces erróneos dentro del post_content
 * Version:         0.0.1
 * Author:          Johan Sebastian Perez Rodriguez
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     Verificacion Enlaces
 * Domain Path:     /lang
 */

// Crear la página de administración
function enlaces_erroneos_plugin_admin_page() {
    add_menu_page(
        'Enlaces Erróneos',
        'Enlaces Erróneos',
        'manage_options',
        'enlaces-erroneos-plugin',
        'enlaces_erroneos_plugin_render_admin_page',
        'dashicons-warning',
        20
    );
}

// Función para mostrar la página de administración
function enlaces_erroneos_plugin_render_admin_page() {
    require_once plugin_dir_path(__FILE__) . 'includes/functions/admin-page.php';
}

// Agregar acción para crear la página de administración
add_action('admin_menu', 'enlaces_erroneos_plugin_admin_page');

// Ejemplo de código para verificar enlaces erróneos en el post_content
function verificar_enlaces_erroneos() {
    // Obtener todas las publicaciones
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);

    // Verificar los enlaces en cada publicación
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_content = get_the_content();

            // Realizar la verificación de enlaces en $post_content
            $enlaces_erroneos = array();

            // Verificar enlaces inseguros
            $pattern = '/href="http:\/\/[^"]+"/i';
            preg_match_all($pattern, $post_content, $matches);
            foreach ($matches[0] as $match) {
                $enlace_erroneo = array(
                    'url' => get_permalink($post_id),
                    'estado' => 'Enlace inseguro',
                    'origen' => get_the_title($post_id),
                );
                $enlaces_erroneos[] = $enlace_erroneo;
            }

            // Verificar protocolo no especificado
            $pattern = '/href="(?:https?:)?\/\/[^"]+"/i';
            preg_match_all($pattern, $post_content, $matches);
            foreach ($matches[0] as $match) {
                $enlace_erroneo = array(
                    'url' => get_permalink($post_id),
                    'estado' => 'Protocolo no especificado',
                    'origen' => get_the_title($post_id),
                );
                $enlaces_erroneos[] = $enlace_erroneo;
            }

            // Verificar enlaces malformados
            $pattern = '/href="https?:\/\/[^"]+\/[^"]+"/i';
            preg_match_all($pattern, $post_content, $matches);
            foreach ($matches[0] as $match) {
                $enlace_erroneo = array(
                    'url' => get_permalink($post_id),
                    'estado' => 'Enlace malformado',
                    'origen' => get_the_title($post_id),
                );
                $enlaces_erroneos[] = $enlace_erroneo;
            }

            // Verificar enlaces con status code incorrecto
            $pattern = '/href="([^"]+)"/i';
            preg_match_all($pattern, $post_content, $matches);
            foreach ($matches[1] as $match) {
                $url = $match;
                $response = wp_remote_head($url);
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code >= 400 && $status_code < 600) {
                    $enlace_erroneo = array(
                        'url' => $url,
                        'estado' => 'Status Code incorrecto (' . $status_code . ')',
                        'origen' => get_the_title($post_id),
                    );
                    $enlaces_erroneos[] = $enlace_erroneo;
                }
            }

            // Guardar los enlaces erróneos en una opción de WordPress
            $option_name = 'enlaces_erroneos_plugin_enlaces_erroneos';
            $enlaces_erroneos_guardados = get_option($option_name, array());
            $enlaces_erroneos_guardados[$post_id] = $enlaces_erroneos;
            update_option($option_name, $enlaces_erroneos_guardados);
        }
        wp_reset_postdata();
    }
}
// Agregar acción para verificar enlaces erróneos a través de un CronJob
add_action('enlaces_erroneos_plugin_verificar_enlaces_cron', 'verificar_enlaces_erroneos');

// Agregar función para registrar el CronJob
function enlaces_erroneos_plugin_schedule_cron() {
    if (!wp_next_scheduled('enlaces_erroneos_plugin_verificar_enlaces_cron')) {
        wp_schedule_event(time(), '4days', 'enlaces_erroneos_plugin_verificar_enlaces_cron');
    }
}
register_activation_hook(__FILE__, 'enlaces_erroneos_plugin_schedule_cron');
// Agregar función para desactivar el CronJob al desactivar el plugin
function enlaces_erroneos_plugin_deactivate() {
    wp_clear_scheduled_hook('enlaces_erroneos_plugin_verificar_enlaces_cron');
}
register_deactivation_hook(__FILE__, 'enlaces_erroneos_plugin_deactivate');
