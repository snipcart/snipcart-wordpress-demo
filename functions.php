<?php

add_action( 'wp_enqueue_scripts', 'snipcart_enqueue_styles' );
function snipcart_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'snipcart-style', 'https://cdn.snipcart.com/themes/2.0/base/snipcart.min.css');
}

add_action('wp_ajax_nopriv_snipcart_endpoint', 'snipcart_endpoint');
//Allow authenticated call on the endpoint. Only needed for debugging purposes.
add_action('wp_ajax_snipcart_endpoint', 'snipcart_endpoint');
function snipcart_endpoint() {
    $json = file_get_contents('php://input');
    $body = json_decode($json, true);

    if (is_null($body) or !isset($body['eventName'])) {
        header('HTTP/1.1 400 Bad Request');
        wp_die();
    }

    switch ($body['eventName']) {
    case 'order.completed':
        foreach($body['content']['items'] as $item) {
            handle_item($item);
        }
        break;
    }

    wp_die();
}

function handle_item($item) {
    global $wpdb;

    $id = $wpdb->get_var( $wpdb->prepare( 
        "
		SELECT post_id
		FROM $wpdb->postmeta
		WHERE meta_value = %s AND meta_key = 'id'
	", $item['id']
    ) );

    $qte = get_post_meta($id, 'inventory')[0];
    update_post_meta($id, 'inventory', $qte - $item['quantity']);
}