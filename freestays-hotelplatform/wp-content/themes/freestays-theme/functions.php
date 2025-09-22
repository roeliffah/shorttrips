<?php

// Theme setup
function freestays_theme_setup() {
    // Add support for featured images
    add_theme_support('post-thumbnails');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'freestays'),
        'footer' => __('Footer Menu', 'freestays'),
    ));
}
add_action('after_setup_theme', 'freestays_theme_setup');

// Enqueue styles and scripts
function freestays_enqueue_scripts() {
    wp_enqueue_style('freestays-style', get_stylesheet_uri());
    wp_enqueue_script('freestays-script', get_template_directory_uri() . '/assets/js/freestays.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'freestays_enqueue_scripts');

// Register widget area
function freestays_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'freestays'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here to appear in your sidebar.', 'freestays'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'freestays_widgets_init');

// Custom post type for hotels
function freestays_register_hotel_post_type() {
    $labels = array(
        'name'               => __('Hotels', 'freestays'),
        'singular_name'      => __('Hotel', 'freestays'),
        'menu_name'          => __('Hotels', 'freestays'),
        'name_admin_bar'     => __('Hotel', 'freestays'),
        'add_new'            => __('Add New', 'freestays'),
        'add_new_item'       => __('Add New Hotel', 'freestays'),
        'new_item'           => __('New Hotel', 'freestays'),
        'edit_item'          => __('Edit Hotel', 'freestays'),
        'view_item'          => __('View Hotel', 'freestays'),
        'all_items'          => __('All Hotels', 'freestays'),
        'search_items'       => __('Search Hotels', 'freestays'),
        'not_found'          => __('No hotels found.', 'freestays'),
        'not_found_in_trash' => __('No hotels found in Trash.', 'freestays'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'hotel'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
    );

    register_post_type('hotel', $args);
}
add_action('init', 'freestays_register_hotel_post_type');