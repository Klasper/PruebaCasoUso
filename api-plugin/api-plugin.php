<?php
/**
 * Plugin Name: API Plugin
 * Plugin URI: https://www.example.com/my-custom-api-plugin
 * Description: Custom REST API plugin for managing posts.
 * Version: 1.0.0
 * Author: Johan Perez
 * Text Domain: my-custom-api-plugin
 * Domain Path: /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the plugin settings page.
function my_custom_api_plugin_settings_page() {
    add_options_page(
        'Custom API Plugin Settings',
        'API Plugin',
        'manage_options',
        'my-custom-api-plugin',
        'my_custom_api_plugin_render_settings_page'
    );
}
add_action('admin_menu', 'my_custom_api_plugin_settings_page');

// Render the plugin settings page.
function my_custom_api_plugin_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Custom API Plugin Settings', 'my-custom-api-plugin'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('my_custom_api_plugin_settings');
            do_settings_sections('my-custom-api-plugin');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the plugin settings and fields.
function my_custom_api_plugin_register_settings() {
    register_setting('my_custom_api_plugin_settings', 'my_custom_api_plugin_authentication_key');
    add_settings_section(
        'my_custom_api_plugin_section',
        'API Authentication',
        'my_custom_api_plugin_section_callback',
        'my-custom-api-plugin'
    );
    add_settings_field(
        'my_custom_api_plugin_authentication_key',
        'Authentication Key',
        'my_custom_api_plugin_authentication_key_callback',
        'my-custom-api-plugin',
        'my_custom_api_plugin_section'
    );
}
add_action('admin_init', 'my_custom_api_plugin_register_settings');

// Render the settings section.
function my_custom_api_plugin_section_callback() {
    echo '<p>' . esc_html__('Enter the authentication key for API requests that require authentication.', 'my-custom-api-plugin') . '</p>';
}

// Render the authentication key field.
function my_custom_api_plugin_authentication_key_callback() {
    $authentication_key = get_option('my_custom_api_plugin_authentication_key');
    echo '<input type="text" name="my_custom_api_plugin_authentication_key" value="' . esc_attr($authentication_key) . '" />';
}

// Register the custom REST API routes.
function my_custom_api_plugin_register_routes() {
    register_rest_route('my-custom-api/v1', '/posts', array(
        'methods' => 'GET',
        'callback' => 'my_custom_api_plugin_get_posts',
    ));
    register_rest_route('my-custom-api/v1', '/posts/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'my_custom_api_plugin_get_post',
    ));
    register_rest_route('my-custom-api/v1', '/posts', array(
        'methods' => 'POST',
        'callback' => 'my_custom_api_plugin_create_post',
        'permission_callback' => 'my_custom_api_plugin_check_authentication',
    ));
    register_rest_route('my-custom-api/v1', '/posts/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'my_custom_api_plugin_update_post',
        'permission_callback' => 'my_custom_api_plugin_check_authentication',
    ));
    register_rest_route('my-custom-api/v1', '/posts/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'my_custom_api_plugin_delete_post',
        'permission_callback' => 'my_custom_api_plugin_check_authentication',
    ));
        // Additional route for /react/v1/
        register_rest_route('my-custom-api/v1', '/react/v1/posts', array(
            'methods' => 'GET',
            'callback' => 'my_custom_api_plugin_get_posts',
        ));
}
add_action('rest_api_init', 'my_custom_api_plugin_register_routes');

// Callback function for getting all posts.
function my_custom_api_plugin_get_posts($request) {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
    );

    $posts = get_posts($args);
    $formatted_posts = array();

    foreach ($posts as $post) {
        $formatted_posts[] = my_custom_api_plugin_format_post($post);
    }

    return $formatted_posts;
}

// Callback function for getting a specific post by ID.
function my_custom_api_plugin_get_post($request) {
    $post_id = $request['id'];

    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'post') {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }

    $formatted_post = my_custom_api_plugin_format_post($post);

    return $formatted_post;
}

// Callback function for creating a new post.
function my_custom_api_plugin_create_post($request) {
    $title = $request->get_param('title');
    $content = $request->get_param('content');
    $meta_fields = $request->get_param('meta_fields');

    // Validate required fields
    if (empty($title) || empty($content)) {
        return new WP_Error('invalid_params', 'Invalid parameters', array('status' => 400));
    }

    // Create the post
    $post_id = wp_insert_post(array(
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'post',
    ));

    // Add meta fields
    if (!empty($meta_fields) && is_array($meta_fields)) {
        foreach ($meta_fields as $meta_field) {
            $key = sanitize_text_field($meta_field['key']);
            $value = sanitize_text_field($meta_field['value']);
            update_post_meta($post_id, $key, $value);
        }
    }

    $post = get_post($post_id);
    $formatted_post = my_custom_api_plugin_format_post($post);

    return $formatted_post;
}

// Callback function for updating an existing post.
function my_custom_api_plugin_update_post($request) {
    $post_id = $request['id'];
    $title = $request->get_param('title');
    $content = $request->get_param('content');
    $meta_fields = $request->get_param('meta_fields');

    // Validate required fields
    if (empty($title) || empty($content)) {
        return new WP_Error('invalid_params', 'Invalid parameters', array('status' => 400));
    }

    // Update the post
    $post = array(
        'ID' => $post_id,
        'post_title' => $title,
        'post_content' => $content,
    );
    wp_update_post($post);

    // Update meta fields
    if (!empty($meta_fields) && is_array($meta_fields)) {
        foreach ($meta_fields as $meta_field) {
            $key = sanitize_text_field($meta_field['key']);
            $value = sanitize_text_field($meta_field['value']);
            update_post_meta($post_id, $key, $value);
        }
    }

    $updated_post = get_post($post_id);
    $formatted_post = my_custom_api_plugin_format_post($updated_post);

    return $formatted_post;
}

// Callback function for deleting a post.
function my_custom_api_plugin_delete_post($request) {
    $post_id = $request['id'];

    $result = wp_delete_post($post_id, true);

    if ($result === false) {
        return new WP_Error('delete_error', 'Error deleting post', array('status' => 500));
    }

    return array('success' => true);
}

// Format a post object into the desired response format.
function my_custom_api_plugin_format_post($post) {
    $formatted_post = array(
        'id' => $post->ID,
        'slug' => $post->post_name,
        'link' => get_permalink($post),
        'title' => $post->post_title,
        'featured_image' => get_the_post_thumbnail_url($post, 'full'),
        'categories' => array(),
        'content' => $post->post_content,
        'meta_fields' => array(),
    );

    $categories = get_the_terms($post, 'category');
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            $formatted_category = array(
                'id' => $category->term_id,
                'title' => $category->name,
                'description' => $category->description,
            );
            $formatted_post['categories'][] = $formatted_category;
        }
    }

    $meta_fields = get_post_meta($post->ID);
    foreach ($meta_fields as $key => $value) {
        $formatted_meta_field = array(
            'key' => $key,
            'value' => $value[0],
        );
        $formatted_post['meta_fields'][] = $formatted_meta_field;
    }

    return $formatted_post;
}

// Check if authentication is required and validate the authentication key.
function my_custom_api_plugin_check_authentication() {
    $authentication_key = get_option('my_custom_api_plugin_authentication_key');

    if (empty($authentication_key)) {
        return true;
    }

    $request_key = $_SERVER['HTTP_AUTHENTICATION_KEY'];

    if ($request_key !== $authentication_key) {
        return new WP_Error('authentication_failed', 'Authentication failed', array('status' => 401));
    }

    return true;
}
