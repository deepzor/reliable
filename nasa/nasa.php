<?php
/*
 * Plugin Name: Nasa Plugin
 */

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function enqueue_scripts()
{
    wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-3.5.1.js');
    wp_enqueue_style( 'slick-styles', '//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css' );
    wp_enqueue_script( 'slick', '//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js' );
    wp_enqueue_script( 'main', plugin_dir_url( __FILE__ ) . 'assets/main.js');
}
add_action('wp_enqueue_scripts', 'enqueue_scripts');

//Install and Uninstall Hooks
register_activation_hook( __FILE__, 'nasa_activate' );

register_uninstall_hook( __FILE__, 'nasa_uninstall' );

add_action( 'init', 'nasa_custom_type' );

function nasa_activate() {
    nasa_custom_type();
    wp_clear_scheduled_hook( 'nasa_get_data_hook' );
    wp_schedule_event( time(), 'daily', 'nasa_get_data_hook');
    flush_rewrite_rules();
}

function nasa_uninstall() {
    unregister_post_type( 'nasa' );
    wp_clear_scheduled_hook( 'nasa_get_data_hook' );
}

//Creating Post Type Nasa Gallery
function nasa_custom_type(){
    register_post_type('post-nasa-gallery', array(
        'labels'             => array(
            'name'               => 'Nasa Gallery',
            'singular_name'      => 'Nasa Gallery',
            'add_new'            => 'Create New',
            'add_new_item'       => 'Create New Nasa Post',
            'edit_item'          => 'Edit Nasa Post',
            'new_item'           => 'New Nasa Post',
            'view_item'          => 'View Nasa Post',
            'search_items'       => 'Search Nasa Post',
            'not_found'          => 'No Nasa Post found',
            'not_found_in_trash' => 'Trash is empty',
            'parent_item_colon'  => '',
            'menu_name'          => 'Nasa Gallery'

        ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => true,
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title','editor','author','thumbnail','excerpt','comments')
    ) );
}

//Getting Data from API and creating post in Nasa Gallery
add_action( 'nasa_get_data_hook', 'nasa_get_data' );
function nasa_get_data(){
    $data = wp_remote_get('https://api.nasa.gov/planetary/apod?api_key=GbkYpKckZ6z0Li2o1uP7evxYnbpQKBPjVEbZpGUK');
    $data = (json_decode($data['body'], true));
    $args = array(
        'post_title'    => wp_strip_all_tags( $data['date'] ),
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_type'     => 'post-nasa-gallery'
    );
    $postid = wp_insert_post( $args );
    $thumbnail_id = media_sideload_image( $data['hdurl'], $postid, $data['title'], 'id');
    set_post_thumbnail( $postid, $thumbnail_id );
}

//Creating Shortcode to show Gallery
add_shortcode( 'nasa-gallery', 'nasa_gallery' );

function nasa_gallery(){
    $args = array(
        'post_type' => 'post-nasa-gallery',
        'posts_per_page' => -1
    );
    $query = new WP_Query( $args );
    global $post;
    if ( $query->have_posts() ):?>
        <div class="nasa-gallery" style="display:flex;justify-content: center">
            <?php while ( $query->have_posts() ):
                $query->the_post();?>
                <div class="nasa-gallery__item" style="display:flex;justify-content: center">
                    <?php the_post_thumbnail('medium_large'); ?>
                </div>
            <?php endwhile;?>
        </div>
    <?php endif;
    wp_reset_postdata();
}

