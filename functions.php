<?php

add_action( 'wp_enqueue_scripts', 'snipcart_enqueue_styles' );
function snipcart_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'snipcart-style', 'https://cdn.snipcart.com/themes/2.0/base/snipcart.min.css');
}