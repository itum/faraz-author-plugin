<?php
/**
 * Unsplash REST API Endpoint
 */

add_action('rest_api_init', 'faraz_unsplash_register_api_routes');

function faraz_unsplash_register_api_routes() {
    register_rest_route('faraz/v1', '/unsplash/search', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'faraz_unsplash_api_search_callback',
        'permission_callback' => 'faraz_unsplash_api_permission_callback',
        'args' => [
            'keyword' => [
                'required' => true,
                'type' => 'string',
                'description' => 'The search keyword.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'count' => [
                'required' => false,
                'type' => 'integer',
                'description' => 'Number of images to return.',
                'sanitize_callback' => 'absint',
                'default' => 5,
            ],
        ],
    ]);
}

function faraz_unsplash_api_permission_callback() {
    return current_user_can('manage_options');
}

function faraz_unsplash_api_search_callback(WP_REST_Request $request) {
    $api_key = get_option('faraz_unsplash_api_key');
    if (empty($api_key)) {
        return new WP_Error('api_key_not_set', 'Unsplash API key is not configured.', ['status' => 500]);
    }

    $keyword = $request->get_param('keyword');
    $count = $request->get_param('count');
    $resolution = get_option('faraz_unsplash_image_resolution', 'regular');

    $url = add_query_arg([
        'query' => urlencode($keyword),
        'client_id' => $api_key,
        'per_page' => $count,
        'orientation' => 'landscape',
    ], 'https://api.unsplash.com/search/photos');

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return new WP_Error('api_connection_error', $response->get_error_message(), ['status' => 500]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['results'])) {
        return new WP_Error('no_images_found', 'No images found for the specified keyword.', ['status' => 404]);
    }

    // Return a simplified version of the results
    $simplified_results = array_map(function($image) use ($resolution) {
        return [
            'id' => $image['id'],
            'url' => $image['urls'][$resolution] ?? $image['urls']['regular'],
            'alt' => $image['alt_description'],
            'user' => [
                'name' => $image['user']['name'],
                'link' => $image['user']['links']['html'],
            ],
        ];
    }, $data['results']);

    return new WP_REST_Response($simplified_results, 200);
}
